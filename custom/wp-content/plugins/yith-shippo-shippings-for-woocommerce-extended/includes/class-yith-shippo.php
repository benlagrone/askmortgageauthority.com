<?php
/**
 * Main Class
 *
 * @class YITH_Shippo
 * @package YITH/Shippo
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main Class
 */
class YITH_Shippo {

	use YITH_Shippo_Trait_Singleton;

	/**
	 * Single instance of the class
	 *
	 * @var YITH_Shippo_Admin|YITH_Shippo_Admin_Extended
	 */
	public $admin;

	/**
	 * Single instance of the class
	 *
	 * @var YITH_Shippo_Admin|YITH_Shippo_Admin_Extended
	 */
	public $webhook;

	/**
	 * Single instance of the class
	 *
	 * @var YITH_Shippo_Request
	 */
	public $request;

	/**
	 * Single instance of frontend class
	 *
	 * @var YITH_Shippo_Shipping_Frontend|YITH_Shippo_Shipping_Frontend_Extended
	 */
	public $frontend;

	/**
	 * Shipping zone list
	 *
	 * @var array
	 */
	public $shipping_zone = false;


	/**
	 * Constructor
	 */
	protected function __construct() {
		// Plugin framework implementation.
		add_action( 'init', array( $this, 'load_text_domain' ), 0 );
		add_action( 'plugins_loaded', array( $this, 'load' ), 20 );
		add_action( 'woocommerce_shipping_init', array( $this, 'load_yith_shippo_shipping_method' ), 10 );
		add_filter( 'woocommerce_shipping_methods', array( $this, 'register_yith_shippo_method' ) );
		add_action( 'init', array( $this, 'add_min_php_version_in_system_status' ), 20 );
		add_action( 'before_woocommerce_init', array( $this, 'declare_wc_features_support' ) );
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function load() {

		if ( ! doing_action( 'plugins_loaded' ) ) {
			_doing_it_wrong( __METHOD__, 'This method should be called only once on plugins loaded!', '1.0.0' );

			return;
		}

		YITH_Shippo_Assets::init();
		YITH_Shippo_Parcels::get_instance();
		YITH_Shippo_Shipping_Email::get_instance();
		YITH_Shippo_Autotracking::get_instance();
		$this->request = new YITH_Shippo_Request();
		$this->webhook = new YITH_Shippo_Webhook();

		if ( $this->is_admin() ) {
			// include admin classes.
			$admin_name  = $this->get_class_name( 'YITH_Shippo_Admin' );
			$this->admin = new $admin_name();
			YITH_Shippo_Ajax::get_instance();
		} else {
			$frontend_name  = $this->get_class_name( 'YITH_Shippo_Shipping_Frontend' );
			$this->frontend = new $frontend_name();
		}

	}

	/**
	 * Check if exsist the premium or the extended version of the class
	 *
	 * @param string $class_name The class name.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	protected function get_class_name( $class_name ) {

		if ( class_exists( $class_name . '_Premium' ) ) {
			$class_name = $class_name . '_Premium';
		} elseif ( class_exists( $class_name . '_Extended' ) ) {
			$class_name = $class_name . '_Extended';
		}

		return $class_name;
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
	 * Add YITH Shipping WC Shipping in the methods
	 *
	 * @param array $methods The shipping methods.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function register_yith_shippo_method( $methods ) {
		$methods['yith_shippo'] = 'YITH_Shippo_WC_Shipping';

		return $methods;
	}

	/**
	 * Load the Shipping class
	 *
	 * @since 1.0.0
	 */
	public function load_yith_shippo_shipping_method() {
		include_once YITH_SHIPPO_INC . 'class-yith-shippo-wc-shipping.php';
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/yith-shippo-shippings-for-woocommerce{version}/yith-shippo-shippings-for-woocommerce-LOCALE.mo
	 *      - WP_LANG_DIR/plugins//yith-shippo-shippings-for-woocommerce-LOCALE.mo
	 */
	public function load_text_domain() {
		$locale = determine_locale();

		/**
		 * APPLY_FILTERS: plugin_locale
		 *
		 * Filter the locale.
		 *
		 * @param string $locale the locale.
		 * @param string $text_domain The text domain.
		 *
		 * @return string
		 */
		$locale = apply_filters( 'plugin_locale', $locale, 'yith-shippo-shippings-for-woocommerce' );
		$suffix = '';
		if ( defined( 'YITH_SHIPPO_PREMIUM' ) ) {
			$suffix = '-premium';
		} elseif ( defined( 'YITH_SHIPPO_EXTENDED' ) ) {
			$suffix = '-extended';
		}

		unload_textdomain( 'yith-shippo-shippings-for-woocommerce' );
		load_textdomain( 'yith-shippo-shippings-for-woocommerce', WP_LANG_DIR . '/yith-shippo-shippings-for-woocommerce' . $suffix . '/yith-shippo-shippings-for-woocommerce-' . $locale . '.mo' );
		load_plugin_textdomain( 'yith-shippo-shippings-for-woocommerce', false, plugin_basename( YITH_SHIPPO_DIR ) . '/languages' );
	}

	/**
	 * Return the shipping zone list
	 *
	 * @return array
	 * @since 1.0
	 */
	public function get_shipping_zones() {

		if ( $this->shipping_zone ) {
			return $this->shipping_zone;
		}

		$this->shipping_zone = yith_shippo_get_shipping_zones();

		return $this->shipping_zone;
	}

	/**
	 * Add in the system status the MIN PHP version
	 *
	 * @since 1.0.0
	 */
	public function add_min_php_version_in_system_status() {
		yith_plugin_fw_add_requirements(
			'YITH Shippo Shippings for WooCommerce',
			array(
				'min_php_version' => '7.4',
			)
		);
	}

	/**
	 * Declare support for WooCommerce features.
	 */
	public function declare_wc_features_support() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', YITH_SHIPPO_INIT, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', YITH_SHIPPO_INIT, true );
		}
	}
}

