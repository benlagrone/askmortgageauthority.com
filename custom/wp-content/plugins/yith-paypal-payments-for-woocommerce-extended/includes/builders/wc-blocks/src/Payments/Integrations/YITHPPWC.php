<?php

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * PayPal Payment method integration
 *
 * @since 2.12.0
 */
final class YITHPPWC extends AbstractPaymentMethodType {
	/**
	 * Payment method name/id/slug (matches id in WC_Gateway_BACS in core).
	 *
	 * @var string
	 */
	protected $name = YITH_Paypal::GATEWAY_ID;

	/**
	 * An instance of the Asset Api
	 *
	 * @var Api
	 */
	private $asset_api;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'yith_ppwc_button_on', '__return_true' );
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
		return 'yes' === $this->get_setting( 'enabled', false );
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {

		$dep = include YITH_PAYPAL_PAYMENTS_PATH . "dist/wc-blocks/wc-payment-method-yith-paypal-payments/index.asset.php";

		wp_register_script(
			'wc-payment-method-yith-paypal-payments',
			YITH_PAYPAL_PAYMENTS_URL . '/dist/wc-blocks/wc-payment-method-yith-paypal-payments/index.js',
			array_merge( $dep['dependencies'] ),
			time(),
			true
		);


		$gateway = YITH_PayPal::get_instance()->get_gateway();

		$merchant   = YITH_PayPal_Merchant::get_merchant();
		$cc_enabled = yith_ppwc_is_custom_credit_card_enabled();

		$button_visible_on_cart     = false;
		$button_visible_on_checkout = false;
		$is_confirmed_page          = false;

		if ( ! is_null( YITH_PayPal::get_instance()->frontend ) ) {
			$button_visible_on_cart     = YITH_PayPal::get_instance()->frontend->is_button_visible( 'edit' );
			$button_visible_on_checkout = YITH_PayPal::get_instance()->frontend->is_button_visible();
			$is_confirmed_page          = YITH_PayPal::get_instance()->frontend->is_confirmed_page();
		}

		$components = ( is_checkout() && $cc_enabled ) ? 'hosted-fields,buttons,card-fields' : 'buttons';
		/**
		 * APPLY_FILTERS: yith_ppwc_sdk_components_block
		 *
		 * Manage components to paypal sdk for blocks.
		 *
		 * @param string $components
		 */
		$components = apply_filters( 'yith_ppwc_sdk_components_block', $components );

		$args = array(
			'ajaxUrl'       => WC_AJAX::get_endpoint( YITH_PayPal_Ajax::AJAX_ACTION ),
			'checkoutURL'   => wc_get_checkout_url(),
			'isPending'     => isset( $_REQUEST['ppcp-pending'] ),
			'cancelContent' => $this->get_cancel_content(),
			'isCart'        => is_cart() && $button_visible_on_cart,
			'isCheckout'    => is_checkout() && $button_visible_on_checkout,
			'isConfirmed'   => is_checkout() && $is_confirmed_page,
			'ajaxNonce'     => wp_create_nonce( YITH_PayPal_Ajax::AJAX_ACTION ),
			'options'       => array(
				'clientId'   => $merchant->get_client_id(),
				'merchantId' => $merchant->get( 'merchant_id' ),
				'intent'     => $gateway->get_intent(),
				'vault'      => false,
				'locale'     => get_locale(),
				'components' => $components,
				'currency'   => get_woocommerce_currency(),
				'commit'     => true
			)
		);

		$enabled_funding_sources = yith_ppwc_get_enabled_funding( false );
		if ( $enabled_funding_sources ) {
			if ( $cc_enabled && is_checkout() && ! in_array( 'card', $enabled_funding_sources, true ) ) {
				$enabled_funding_sources[] = 'card';
			}
			$args['fundingSource'] = $enabled_funding_sources;
		}

		wp_localize_script( 'wc-payment-method-yith-paypal-payments', 'yith_ppwc_settings', $args );

		return [ 'wc-payment-method-yith-paypal-payments' ];
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
        <p id="yith-ppcp-cancel"
           class="yith-ppcp-cancel"
        >
			<?php
			printf(
			// translators: the string is the name of the payment.
				esc_html__( 'You are currently paying with %s. %sPlace Order to confirm your payment.', 'yith-paypal-payments-for-woocommerce' ),
				$this->get_setting( 'title' ), '<br/>'
			);
			?>
        </p>
		<?php
		return (string) ob_get_clean();
	}
}
