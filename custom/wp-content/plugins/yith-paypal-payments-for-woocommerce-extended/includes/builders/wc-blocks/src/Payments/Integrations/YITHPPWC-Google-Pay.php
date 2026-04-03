<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * PayPal Google Pay Payment method integration
 *
 * @since 3.0.0
 */
final class YITHPPWC_Google_Pay extends AbstractPaymentMethodType {
	/**
	 * Payment method name/id/slug (matches id in WC_Gateway_BACS in core).
	 *
	 * @var string
	 */
	protected $name = 'yith_ppwc_google_pay';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'yith_ppwc_button_on', '__return_true' );
		add_filter( 'yith_ppwc_sdk_components_block', array( $this, 'add_gpay_to_components' ) );
	}

	/**
	 * Add google pay to paypal sdk components.
	 *
	 * @param string $components list of the components.
	 */
	public function add_gpay_to_components( $components ) {
		if ( YITH_PayPal_Google_Pay::get_instance()->is_enabled() ) {
			$components .= ',googlepay';
		}
		return $components;
	}
	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( $this->get_option_key(), array() );
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return YITH_PayPal_Google_Pay::get_instance()->is_enabled();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {

		$dep = include YITH_PAYPAL_PAYMENTS_PATH . 'dist/wc-blocks/wc-payment-method-yith-paypal-payments-google-pay/index.asset.php';

		$enqueued = wp_script_is( 'wc-payment-method-yith-paypal-payments-google-pay', 'enqueued' );

		wp_register_script(
			'wc-payment-method-yith-paypal-payments-google-pay',
			YITH_PAYPAL_PAYMENTS_URL . 'dist/wc-blocks/wc-payment-method-yith-paypal-payments-google-pay/index.js',
			array_merge( $dep['dependencies'] ),
			time(),
			true
		);

		$button_visible_on_cart     = false;
		$button_visible_on_checkout = false;
		$is_confirmed_page          = false;
		$is_google_paying           = false;

		if ( ! is_null( YITH_PayPal::get_instance()->frontend ) ) {
			$button_visible_on_cart     = YITH_PayPal::get_instance()->frontend->is_button_visible( 'edit' );
			$button_visible_on_checkout = YITH_PayPal::get_instance()->frontend->is_button_visible();
			$is_confirmed_page          = isset( WC()->session ) && WC()->session->get( 'checkout_as_confirm_page', false );
		}

		if ( class_exists( 'YITH_PayPal_Google_Pay' ) && WC()->session ) {
			$funding_source   = WC()->session->get( 'yith_ppwc_funding_source' );
			$is_google_paying = YITH_PayPal_Google_Pay::get_funding_source() === $funding_source;
		}

		$args = array(
			'ajaxUrl'        => WC_AJAX::get_endpoint( YITH_PayPal_Ajax::AJAX_ACTION ),
			'ajaxNonce'      => wp_create_nonce( YITH_PayPal_Ajax::AJAX_ACTION ),
			'checkoutURL'    => wc_get_checkout_url(),
			'cancelContent'  => $this->get_cancel_content(),
			'sdkUrl'         => YITH_PayPal_Google_Pay::get_instance()->get_sdk_url(),
			'isCart'         => is_cart() && $button_visible_on_cart,
			'isCheckout'     => is_checkout() && $button_visible_on_checkout,
			'isConfirmed'    => is_checkout() && $is_confirmed_page,
			'isGooglePaying' => $is_google_paying,
		);

		$args_base = YITH_PayPal_Google_Pay::get_instance()->get_script_localize_args();
		$args      = array_merge( $args_base, $args );

		if ( ! empty( $args['context'] ) ) {
			// this is to avoid multiple enqueue on the same page.
			wp_localize_script( 'wc-payment-method-yith-paypal-payments-google-pay', 'yith_ppwc_google_pay_blocks', $args );
		}

		return [ 'wc-payment-method-yith-paypal-payments-google-pay' ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return [
			'id'          => $this->name,
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => $this->get_supported_features(),
		];
	}

	/**
	 * Get option key for this gateway.
	 *
	 * @since 1.0.0
	 */
	public function get_option_key() {
		return 'yith_ppwc_gateway_options';
	}

	/**
	 * When the payment has been accepted this html is showed to customer
	 *
	 * @return string
	 */
	private function get_cancel_content() {
		ob_start();
		?>
		<p id="yith-ppcp-cancel" class="yith-ppcp-cancel yith-ppcp-googlepay">
			<?php
			printf(
				// translators: the string is the name of the payment.
				esc_html__(
					'You are currently paying with %1$s. %2$sPlace Order to confirm your payment.',
					'yith-paypal-payments-for-woocommerce'
				),
				YITH_PayPal_Google_Pay::get_funding_source_label(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				'<br/>'
			);
			?>
		</p>
		<?php
		return (string) ob_get_clean();
	}
}
