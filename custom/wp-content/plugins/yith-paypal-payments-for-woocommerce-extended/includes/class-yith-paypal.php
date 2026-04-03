<?php
/**
 * Main class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH PayPal Payments for WooCommerce
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class YITH_PayPal
 */
final class YITH_PayPal {

	/**
	 * The gateway ID
	 *
	 * @var string
	 */
	const GATEWAY_ID = 'yith_paypal_payments';

	/**
	 * Single instance of the class
	 *
	 * @var YITH_Paypal
	 * @since 1.0.0
	 */
	protected static $instance;

	/**
	 * The gateway class
	 *
	 * @var YITH_PayPal_Gateway
	 * @since 1.0.0
	 */
	protected $gateway = null;

	/**
	 * The frontend class
	 *
	 * @var YITH_PayPal_Frontend
	 * @since 1.0.0
	 */
	public $frontend = null;


	/**
	 * Newfold hosting information
	 *
	 * @var array
	 * @since 2.1.5
	 */
	protected $nf_config = null;

	/**
	 * Returns single instance of the class
	 *
	 * @return YITH_Paypal
	 * @since 1.0.0
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.1
	 */
	public function __clone() {
		wc_doing_it_wrong( __FUNCTION__, 'Cloning is forbidden.', '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.1
	 */
	public function __wakeup() {
		wc_doing_it_wrong( __FUNCTION__, 'Unserializing instances of this class is forbidden.', '1.0.0' );
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {

		include_once YITH_PAYPAL_PAYMENTS_PATH . 'includes/yith-paypal-functions.php';
		include_once YITH_PAYPAL_PAYMENTS_PATH . 'includes/order/class-yith-paypal-order-helper.php';

		// Register the gateway on the list of available WooCommerce gateway.
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ), 10, 1 );

		add_action( 'init', array( $this, 'init' ), 10 );

		// Compatibility issue with WPC Product Bundles for WooCommerce.
		add_filter( 'yith_ppwc_build_request_details', array( $this, 'skip_details_for_wpc_bundles' ), 10, 2 );

		add_action( 'before_woocommerce_init', array( $this, 'declare_wc_features_support' ) );
		add_action( 'option_woocommerce_gateway_order', array( $this, 'change_gateway_order' ) );
	}


	/**
	 * Init plugin
	 *
	 * @since 1.0.0
	 */
	public function init() {
		try {
			$this->load_gateway();
			$this->includes();
		} catch ( Exception $e ) {
			$message = '[Error] There was an error on booting plugin process: ' . $e->getMessage();
			YITH_PayPal_Logger::log( $message );
		}

		if ( function_exists( 'yith_nfbm_get_container_plugin_attribute' ) ) {
			$this->nf_config = array(
				'id'     => yith_nfbm_get_container_plugin_attribute( 'id' ),
				'brand'  => yith_nfbm_get_container_plugin_attribute( 'brand' ),
				'region' => yith_nfbm_get_container_plugin_attribute( 'region' ),
			);

			$this->nf_integration();
		}
	}


	/**
	 * Include plugin required class and file
	 *
	 * @since 1.0.0
	 */
	protected function includes() {
		include_once YITH_PAYPAL_PAYMENTS_PATH . 'includes/class-yith-paypal-scripts.php';
		include_once YITH_PAYPAL_PAYMENTS_PATH . 'includes/class-yith-paypal-ajax.php';
		include_once YITH_PAYPAL_PAYMENTS_PATH . 'includes/class-yith-paypal-google-pay.php';
		include_once YITH_PAYPAL_PAYMENTS_PATH . 'includes/class-yith-paypal-apple-pay.php';

		if ( $this->is_admin() ) {
			// include admin classes.
			new YITH_PayPal_Admin();
		} else {
			$this->frontend = include_once YITH_PAYPAL_PAYMENTS_PATH . 'includes/class-yith-paypal-frontend.php';
		}

		YITH_PayPal_Webhook::get_webhook();

		/* Start Google Pay */
		YITH_PayPal_Google_Pay::get_instance();
		/* Start Apple Pay */
		YITH_PayPal_Apple_Pay::get_instance();
	}

	/**
	 * Check if is admin or not and load the correct class
	 *
	 * @return boolean
	 * @since 1.0.0
	 */
	public function is_admin() {
		$check_ajax    = defined( 'DOING_AJAX' ) && DOING_AJAX;
		$check_context = isset( $_REQUEST['context'] ) && 'frontend' === sanitize_text_field( wp_unslash( $_REQUEST['context'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return is_admin() && ! ( $check_ajax && $check_context );
	}

	/**
	 * Register the gateway to WooCommerce gateways list
	 *
	 * @param array $gateways  An array of gateways.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function register_gateway( $gateways ) {
		$gateways[] = 'YITH_PayPal_Gateway';
		if ( 'yes' === get_option( 'yith_ppwc_gateway_enabled_to_manage_custom_card', 'no' ) ) {
			$gateways[] = 'YITH_PayPal_Custom_Card_Gateway';
		}

		return $gateways;
	}


	/**
	 * Load the gateway instance and set the class variable
	 *
	 * @return void
	 * @throws Exception Throws Exception.
	 * @since 1.0.0
	 */
	protected function load_gateway() {
		$gateways = WC()->payment_gateways()->payment_gateways();
		if ( empty( $gateways[ self::GATEWAY_ID ] ) ) {
			throw new Exception( 'PayPal gateway not found.' );
		}

		$this->gateway = $gateways[ self::GATEWAY_ID ];
	}

	/**
	 * Get the gateway instance
	 *
	 * @return YITH_PayPal_Gateway|null
	 * @since 1.0.0
	 */
	public function get_gateway() {
		return $this->gateway;
	}



	/**
	 * Skip include order/cart details when WPC Product Bundles for WooCommerce is active.
	 * This plugin wrong set item meta value to negative value.
	 *
	 * @param boolean $include  True to include details, false otherwise.
	 * @param string  $section  Optional. Current request section (cart|order).
	 *
	 * @return boolean
	 * @since 1.2.4
	 */
	public function skip_details_for_wpc_bundles( $include, $section = 'cart' ) {
		return $include && ! defined( 'WOOSB_FILE' );
	}

	/**
	 * Declare support for WooCommerce features.
	 */
	public function declare_wc_features_support() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', YITH_PAYPAL_PAYMENTS_INIT, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', YITH_PAYPAL_PAYMENTS_INIT, true );
		}
	}

	/**
	 * Set a list of hook based on Newfold host installation
	 *
	 * @retun void
	 * @since 2.15.0
	 */
	public function nf_integration() {
		if ( ! is_null( $this->nf_config ) ) {
			$store_country = WC()->countries->get_base_country();

			if ( 'BR' === $store_country || ( empty( $store_country ) && 'hostgator' === $this->nf_config['id'] && 'BR' === $this->nf_config['region'] ) ) {
				add_filter( 'yith_ppwc_is_custom_credit_card_enabled', '__return_false' );
				add_filter( 'yith_paypal_payments_remove_cc_settings', '__return_true' );
			}
		}
	}

	/**
	 * Return the product type based on Newfold hosting
	 *
	 * @return string
	 * @since 2.15.0
	 */
	public function get_product_type() {
		$product_type = 'PPCP';
		if ( ! is_null( $this->nf_config ) ) {
			$store_country = WC()->countries->get_base_country();
			if ( 'BR' === $store_country || ( empty( $store_country ) && 'hostgator' === $this->nf_config['id'] && 'BR' === $this->nf_config['region'] ) ) {
				$product_type = 'EXPRESS_CHECKOUT';
			}
		}

		return $product_type;
	}

	/**
	 * Return the country to set the locale
	 *
	 * @since 2.15.0
	 * @return string|bool
	 */
	public function get_onboarding_language() {
		$wp_locale = get_user_locale();

		$locale = false;
		if ( strpos( $wp_locale, '_' ) !== false ) {
			$split  = explode( '_', $wp_locale );
			$locale = $split[1];
		}

		return $locale;
	}

	/**
	 * Return the country to set the locale
	 *
	 * @since 2.15.0
	 * @return string|bool
	 */
	public function get_newfold_id() {
		return $this->nf_config['id'] ?? 'bluehost';
	}

	/**
	 * Change the gateway order adding the YITH PayPal Gateways on Top
	 *
	 * @param array $order the payment methods.
	 * @return array
	 */
	public function change_gateway_order( $order ) {
		unset( $order['yith_paypal_payments'] );
		unset( $order['yith_paypal_payments_custom_card'] );

		$new_order = array(
			'yith_paypal_payments' => 0,
			'yith_paypal_payments_custom_card' => 1,
		);

		foreach ( $order as $gateway => $index ) {
			$new_order[ $gateway ] = $index + 2;
		}

		return $new_order;
	}
}
