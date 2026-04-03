<?php

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * PayPal Payment Custom Card payment method integration
 *
 * @since 2.12.0
 */
final class YITHPPWCC_Card extends AbstractPaymentMethodType {
	/**
	 * Payment method name/id/slug (matches id in WC_Gateway_BACS in core).
	 *
	 * @var string
	 */
	protected $name = YITH_Paypal::GATEWAY_ID . '_custom_card';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'yith_ppwc_transaction_build_request', array( $this, 'add_3d_secure_to_request' ) );
	}

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( $this->get_option_key(), array() );
		// add the scripts on frontend.
		if ( $this->is_active() ) {
			add_filter( 'yith_ppwc_load_frontend_scripts', '__return_true' );
		}
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
		$dep = include YITH_PAYPAL_PAYMENTS_PATH . 'dist/wc-blocks/wc-payment-method-yith-paypal-payments-custom-card/index.asset.php';
		wp_register_script(
			'wc-payment-method-yith-paypal-payments-custom-card',
			YITH_PAYPAL_PAYMENTS_URL . '/dist/wc-blocks/wc-payment-method-yith-paypal-payments-custom-card/index.js',
			$dep['dependencies'],
			time(),
			true
		);
		$gateway  = YITH_PayPal::get_instance()->get_gateway();
		$merchant = YITH_PayPal_Merchant::get_merchant();

		wp_localize_script(
			'wc-payment-method-yith-paypal-payments-custom-card',
			'yith_ppwc_card_settings',
			array(
				'ajaxUrl'             => WC_AJAX::get_endpoint( YITH_PayPal_Ajax::AJAX_ACTION ),
				'ajaxNonce'     => wp_create_nonce( YITH_PayPal_Ajax::AJAX_ACTION ),
				'title'               => $this->get_setting( 'title' ),
				'description'         => $this->get_setting( 'description' ),
				'cardNumberLabel'     => __( 'Card Number', 'yith-paypal-payments-for-woocommerce' ),
				'expirationDateLabel' => __( 'Expiration Date', 'yith-paypal-payments-for-woocommerce' ),
				'cvvLabel'            => __( 'CVV', 'yith-paypal-payments-for-woocommerce' ),
				'threedsecure'        => 'yes' === $this->get_setting( '3d_secure_setting' ),
				'liabilityList'       => $this->get_setting( '3d_secure_liability_shift' ),
				'options'             => array(
					'clientId'   => $merchant->get_client_id(),
					'merchantId' => $merchant->get( 'merchant_id' ),
					'intent'     => $gateway->get_intent(),
					'vault'      => false,
					'locale'     => get_locale(),
					'components' => 'hosted-fields,buttons,card-fields',
					'currency'   => get_woocommerce_currency(),
					'commit'     => true,
				),
			)
		);

		return [ 'wc-payment-method-yith-paypal-payments-custom-card' ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return [
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
		return 'yith_ppwc_cc_gateway_options';
	}

	/**
	 * Add 3d secure to the request.
	 *
	 * @param   array  $body  Content of request.
	 *
	 * @return array
	 */
	public function add_3d_secure_to_request( $body ) {
		$tredsecure = $this->get_setting( '3d_secure_setting' );

		if ( 'yes' === $tredsecure ) {
			$body['payment_source']['card'] = array(
				'attributes' => array(
					'verification' => array(
						'method' => 'SCA_WHEN_REQUIRED',
					),
				),
			);
		}

		return $body;
	}
}
