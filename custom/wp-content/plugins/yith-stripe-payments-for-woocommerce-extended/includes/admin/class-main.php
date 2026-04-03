<?php
/**
 * Main admin class
 *
 * @author  YITH
 * @package YITH\StripePayments
 * @version 1.0.0
 */

namespace YITH\StripePayments\Admin;

use YITH\StripePayments\Traits\Singleton;
use YITH\StripePayments\Traits\Environment_Access;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Admin\Main' ) ) {
	/**
	 * Stripe Payments admin class
	 * Register tha panel, and handles admin actions
	 *
	 * @since 1.0.0
	 */
	class Main {

		use Singleton, Environment_Access;

		/**
		 * Single instance of the admin panel class
		 *
		 * @var Panel
		 */
		protected $panel;

		/**
		 * Constructor method; init admin handling
		 */
		protected function __construct() {
			$this->install();

			// enqueue general assets.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );

			// init onboarding.
			add_action( 'admin_init', array( $this, 'init_onboarding' ), 20 );

			// init admin actions.
			add_action( 'admin_init', array( $this, 'init_ajax' ) );

			// init orders changes.
			add_action( 'current_screen', array( $this, 'init_orders' ) );

			// init plugin row meta / admin actions.
			add_filter( 'yith_show_plugin_row_meta', array( $this, 'init_row_meta' ), 10, 3 );
			add_filter( 'plugin_action_links_' . YITH_STRIPE_PAYMENTS_INIT, array( $this, 'init_action_links' ) );

			// trigger correct handling for environment update.
			add_action( 'woocommerce_admin_settings_sanitize_option_' . self::get_env_option_name(), array( $this, 'set_env' ) );

			// Add plugin cache flush option in WC Tools
			add_filter( 'woocommerce_debug_tools', array( $this, 'add_clear_cache_action_to_wc_tools_array' ) );
		}

		/**
		 * Enqueue assets global to the entire admin panel
		 */
		public function enqueue() {
			$suffix = defined( 'SCRIPT_DEBUG' ) && \SCRIPT_DEBUG ? '' : '.min';

			wp_register_style( 'yith-stripe-payments-admin', YITH_STRIPE_PAYMENTS_URL . "assets/css/yith-stripe-payments-admin{$suffix}.css", array(), YITH_STRIPE_PAYMENTS_VERSION );

			if ( apply_filters( 'yith_stripe_payments_enqueue_admin_scripts', $this->panel->is_own_screen() ) ) {
				// Enqueue common style for admin section.
				wp_enqueue_style( 'yith-stripe-payments-admin' );

				// Localize global variable to be used in all admin scripts.
				wp_localize_script(
					'jquery',
					'yithStripePayments',
					array(
						'env'      => $this->get_env(),
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'labels'   => array(
							'confirm_revoke_title'   => __( 'Are you sure you want to disconnect from Stripe?', 'yith-stripe-payments-for-woocommerce' ),
							'confirm_revoke_message' => __( 'If you proceed, all account information will be removed and payments will be disabled, if you want to retain your account information, please select the disable payments option instead', 'yith-stripe-payments-for-woocommerce' ),
							'confirm_revoke_button'  => __( 'Disconnect', 'yith-stripe-payments-for-woocommerce' ),
						),
						'nonces'   => array(
							'process_onboarding'        => wp_create_nonce( 'process_onboarding' ),
							'refresh_connection_status' => wp_create_nonce( 'refresh_connection_status' ),
							'revoke_connection'         => wp_create_nonce( 'revoke_connection' ),
						),
					)
				);
			}
		}

		/**
		 * Performs action to bootstrap admin section
		 */
		public function install() {
			// init admin panel.
			$this->panel = Panel::get_instance();

			Notices::get_instance();
		}

		/**
		 * Init all actions and processes related to onboarding
		 */
		public function init_onboarding() {
			if ( apply_filters( 'yith_stripe_payments_init_onboarding', $this->panel->is_tab( 'general' ) || Onboarding::is_return_url() ) ) {
				Onboarding::get_instance();
			}
		}

		/**
		 * Init all actions and processes related to onboarding
		 */
		public function init_orders() {
			$screen = get_current_screen();

			if ( ! $screen || ! in_array( $screen->id, array( 'shop_order', 'woocommerce_page_wc-orders' ), true ) ) {
				return;
			}

			Orders::get_instance();
		}

		/**
		 * Init admin-only ajax handling for this plugin
		 */
		public function init_ajax() {
			Ajax::init();
		}

		/**
		 * Filters action links available for current plugin, in order to add reference to settings page
		 *
		 * @param array $links Existing array of action links.
		 *
		 * @return array Filtered array of action links.
		 */
		public function init_action_links( $links ) {
			$links = yith_add_action_links( $links, $this->panel->get_slug(), false );

			return $links;
		}

		/**
		 * Filters row meta available for current plugin, in order to add reference to support and documentation
		 *
		 * @param array    $row_meta    An array of plugin row meta.
		 * @param string[] $plugin_meta An array of the plugin's metadata, including the version, author, author URI, and plugin URI.
		 * @param string   $plugin_file Path to the plugin file relative to the plugin directory.
		 *
		 * @return array Filtered array of plugin row meta.
		 */
		public function init_row_meta( $row_meta, $plugin_meta, $plugin_file ) {
			if ( YITH_STRIPE_PAYMENTS_INIT === $plugin_file ) {
				$row_meta[ 'is_extended' ] = true;
				$row_meta[ 'to_show' ]     = array( 'documentation', 'support' );
				$row_meta[ 'slug' ]        = YITH_STRIPE_PAYMENTS_SLUG;
			}

			return $row_meta;
		}

		/**
		 * Add clear plugin cache action into WooCommerce Tools
		 *
		 * @param array $tools The tools.
		 *
		 * @return array
		 */
		public function add_clear_cache_action_to_wc_tools_array( $tools ) {
			$tools[ 'yith_stripe_payments_clear_cache' ] = array(
				'name'     => __( 'Clear YITH Stripe Payments fo WooCommerce cache', 'yith-stripe-payments-for-woocommerce' ),
				'desc'     => __( 'This tool will clear the options and transients cache used in the plugin.', 'yith-stripe-payments-for-woocommerce' ),
				'button'   => __( 'Clear', 'yith-stripe-payments-for-woocommerce' ),
				'callback' => array( 'YITH\StripePayments\Cache_Helper', 'invalidate_cache' ),
			);

			return $tools;
		}
	}
}
