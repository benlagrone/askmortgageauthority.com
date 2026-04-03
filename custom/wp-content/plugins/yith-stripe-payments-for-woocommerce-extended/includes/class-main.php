<?php
/**
 * Main class
 *
 * @author  YITH
 * @package YITH\StripePayments
 * @version 1.0.0
 */

namespace YITH\StripePayments;

use YITH\StripePayments\Admin\Main as Admin;
use YITH\StripePayments\RestApi\Listener as RestApi;
use YITH\StripePayments\Integrations\Handler as Integrations;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Main' ) ) {
	/**
	 * Stripe Payments main class
	 * Init the entire plugin
	 *
	 * @since 1.0.0
	 */
	class Main {

		/**
		 * Plugin version
		 *
		 * @const string
		 * @since 2.0.0
		 */
		const VERSION = '1.0.0';

		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 * @var Main
		 */
		protected static $instance;

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->install();

			// init common AJAX actions.
			add_action( 'init', array( $this, 'init_ajax' ) );

			// load nf-brand module
			add_action( 'plugins_loaded', array( $this, 'nf_brands_module_loader' ), 15 );

			// Declare WooCommerce supported features.
			add_action( 'before_woocommerce_init', array( $this, 'declare_wc_features_support' ) );
		}

		/**
		 * Performs action to bootstrap plugin
		 *
		 * @return void
		 */
		public function install() {
			// load autoloader class.
			require_once YITH_STRIPE_PAYMENTS_INC . 'class-autoloader.php';

			// load functions file.
			require_once YITH_STRIPE_PAYMENTS_INC . 'functions.php';

			// load client library.
			require_once YITH_STRIPE_PAYMENTS_INC . 'lib/yith-stripe-payments-client/init.php';

			// starts proper class.
			if ( is_admin() ) {
				Admin::get_instance();
			} else {
				Frontend::get_instance();
			}

			// common functions.
			Common::get_instance();

			// customers handling.
			Customers::get_instance();

			// gateways handling.
			Gateways::get_instance();

			// REST API handling.
			RestApi::get_instance();

			// Integrations handling.
			Integrations::get_instance();

			// Apple pay registration handling.
			Apple_Pay_Registration::get_instance();
		}

		/**
		 * Init frontend ajax handling for this plugin
		 */
		public function init_ajax() {
			Ajax::init();
		}

		/**
		 * Loads Newfold brands module, if not yet created
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function nf_brands_module_loader() {
			$path = YITH_STRIPE_PAYMENTS_INC . 'lib/yith-nf-brands-module/init.php';
			if ( ! defined( 'YITH_NFBM' ) && file_exists( $path ) ) {
				require_once $path;
			}
		}

		/**
		 * Declare support for WooCommerce features.
		 *
		 * @since 1.0.3
		 */
		public function declare_wc_features_support() {
			$compatible_features = array(
				'custom_order_tables',
			);

			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				foreach ( $compatible_features as $feature ) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( $feature, YITH_STRIPE_PAYMENTS_INIT, true );
				}
			}
		}

		/**
		 * Returns single instance of the class
		 *
		 * @return Main
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				static::$instance = new static();
			}

			return static::$instance;
		}
	}
}
