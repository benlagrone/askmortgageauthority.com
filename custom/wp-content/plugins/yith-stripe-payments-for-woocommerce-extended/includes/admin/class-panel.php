<?php
/**
 * Admin panel class
 *
 * @author  YITH
 * @package YITH\StripePayments\Classes
 * @version 1.0.0
 */

namespace YITH\StripePayments\Admin;

use YITH\StripePayments\Gateways;
use YITH\StripePayments\Traits\Singleton;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Admin\Panel' ) ) {
	/**
	 * Stripe Payments admin panel
	 * Register tha panel, and handles custom actions
	 *
	 * @since 1.0.0
	 */
	class Panel {

		use Singleton;

		/**
		 * List of available tab for affiliates panel
		 *
		 * @var array
		 * @access public
		 * @since  1.0.0
		 */
		protected $available_tabs = array();

		/**
		 * Admin page slug
		 *
		 * @var string
		 */
		protected $page_slug = 'yith_stripe_payments_panel';

		/**
		 * Instance of panel object
		 *
		 * @var \YIT_Plugin_Panel_WooCommerce
		 */
		protected $panel;

		/**
		 * Constructor method; init admin handling
		 */
		protected function __construct() {
			add_action( 'admin_menu', array( $this, 'register' ), 5 );
			add_action( 'current_screen', array( $this, 'redirect_from_wc' ), 10 );
			add_filter( 'yith_plugin_fw_panel_has_help_tab', array( $this, 'register_help_tab' ), 10, 2 );
		}

		/* === PANEL REGISTRATION === */

		/**
		 * Returns minimum capability needed to manage plugin's panel
		 *
		 * @return string Capability, filtered by yith_stripe_payments_panel_capability filter.
		 */
		public function get_capability() {
			/**
			 * APPLY_FILTERS: yith_stripe_payments_panel_capability
			 *
			 * Filters the minimum capability needed to manage the plugin panel.
			 *
			 * @param string $capability Capability.
			 */
			return apply_filters( 'yith_stripe_payments_panel_capability', 'manage_woocommerce' );
		}

		/**
		 * Return true if current user can manage admin panel
		 *
		 * @return bool Whether current user can manage panel.
		 */
		public function current_user_can_manage() {
			return current_user_can( $this->get_capability() );
		}

		/**
		 * Register panel
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function register() {
			$args = array(
				'create_menu_page' => true,
				'ui_version'       => 2,
				'parent_slug'      => '',
				'page_title'       => 'YITH Stripe Payments for WooCommerce',
				'menu_title'       => 'Stripe Payments for WooCommerce',
				'capability'       => $this->get_capability(),
				'class'            => yith_set_wrapper_class( 'yith-plugin-ui--classic-wp-list-style' ),
				'parent'           => '',
				'parent_page'      => 'yith_plugin_panel',
				'page'             => $this->page_slug,
				'admin-tabs'       => $this->get_available_tabs(),
				'options-path'     => YITH_STRIPE_PAYMENTS_DIR . 'plugin-options',
				'plugin_slug'      => YITH_STRIPE_PAYMENTS_SLUG,
				'plugin-url'       => YITH_STRIPE_PAYMENTS_URL,
				'is_extended'      => true,
			);

			// load plugin-fw class when needed.
			if ( ! class_exists( 'YIT_Plugin_Panel_WooCommerce' ) ) {
				require_once YITH_STRIPE_PAYMENTS_DIR . 'plugin-fw/lib/yit-plugin-panel-wc.php';
			}

			$this->panel = new \YIT_Plugin_Panel_WooCommerce( $args );
		}

		/**
		 * Redirect customer that loads WC default Gateway page to panel page.
		 */
		public function redirect_from_wc() {
			$screen   = get_current_screen();
			$gateways = Gateways::get_instance()->get_slugs();
			$section  = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : false; // phpcs:ignore

			if (
				'woocommerce_page_wc-settings' !== $screen->id ||
				! $section ||
				! in_array( $section, $gateways, true )
			) {
				return;
			}

			$tabs = array_keys( $this->get_available_tabs() );

			if ( 'element' === $section || ! in_array( $section, $tabs, true ) ) {
				$section = 'general';
			}

			wp_safe_redirect( $this->get_url( $section ) );
			die;
		}

		/**
		 * Show the help tab for this plugin
		 *
		 * @param bool              $show  Whether to show the tab.
		 * @param \YIT_Plugin_Panel $panel Current panel.
		 *
		 * @return bool
		 */
		public function register_help_tab( $show, $panel ) {
			if ( isset( $panel->settings['plugin_slug'] ) && YITH_STRIPE_PAYMENTS_SLUG === $panel->settings['plugin_slug'] ) {
				$show = true;
			}
			return $show;
		}

		/* === TABS HELPER METHODS === */

		/**
		 * Returns available tabs
		 *
		 * @return array.
		 */
		public function get_available_tabs() {
			// sets available tab.
			if ( empty( $this->available_tabs ) ) {
				/**
				 * APPLY_FILTERS: yith_stripe_payments_available_admin_tabs
				 *
				 * Filter the available tabs in the plugin panel.
				 *
				 * @param array $tabs Admin tabs.
				 */
				$this->available_tabs = apply_filters(
					'yith_stripe_payments_available_admin_tabs',
					array(
						'general'    => array(
							'title'       => _x( 'Settings', '[ADMIN] Panel tabs', 'yith-stripe-payments-for-woocommerce' ),
							'icon'        => 'settings',
							'description' => __( 'Connect to Stripe and configure gateway settings', 'yith-stripe-payments-for-woocommerce' ),
						),
						'appearance' => array(
							'title'       => _x( 'Appearance', '[ADMIN] Panel tabs', 'yith-stripe-payments-for-woocommerce' ),
							'icon'        => <<<EOSVG
								<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
								  <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42"></path>
								</svg>
							EOSVG,
							'description' => __( 'Customize the appearance of the Stripe Payment Element.', 'yith-stripe-payments-for-woocommerce' ),
						),
					)
				);
			}

			// returns existing tabs.
			return $this->available_tabs;
		}

		/**
		 * Returns current tab
		 *
		 * @return string|bool Current tab, or false if not on a plugin's tab.
		 */
		public function get_current_tab() {
			if ( ! $this->is_own_screen() ) {
				return false;
			}

			$available_tabs = array_keys( $this->get_available_tabs() );

			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : $available_tabs[0];
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			if ( ! in_array( $current_tab, $available_tabs, true ) ) {
				return false;
			}

			return $current_tab;
		}

		/**
		 * Checks if we're currently on the passed tab
		 *
		 * @param string $tab Tab to check.
		 * @return bool Whether we're in submitted tab or not.
		 */
		public function is_tab( $tab ) {
			return $tab === $this->get_current_tab();
		}

		/* === SCREEN HELPER METHODS === */

		/**
		 * Return array of screen ids related to Stripe Payments plugin
		 *
		 * @return mixed Array of available screens
		 * @since 1.0.0
		 */
		public function get_screen_ids() {
			$base           = sanitize_title( 'YITH Plugins' );
			$main_screen_id = "{$base}_page_{$this->page_slug}";

			$screen_ids = array(
				$main_screen_id,
				"{$main_screen_id}_settings",
			);

			/**
			 * APPLY_FILTERS: yith_stripe_payments_screen_ids
			 *
			 * Filters the screen ids related to the plugin.
			 *
			 * @param array $screen_ids Screen ids.
			 */
			return apply_filters( 'yith_stripe_payments_screen_ids', $screen_ids );
		}

		/**
		 * Returns true if current screen belongs to Stripe Payments plugin
		 *
		 * @return bool Whether we're on an internal screen or not.
		 */
		public function is_own_screen() {
			$is_own_screen = false;

			if ( ! did_action( 'current_screen' ) ) {
				// if we don't have screen yet, fallback to query string check.
				$is_own_screen = isset( $_GET['page'] ) && $this->page_slug === $_GET['page']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} else {
				// otherwise rely on screen object.
				$screen = get_current_screen();

				$is_own_screen = $screen && in_array( $screen->id, $this->get_screen_ids(), true );
			}

			return $is_own_screen;
		}

		/**
		 * Add notice to panel
		 *
		 * @param string $message The notice message.
		 * @param string $type The notice type.
		 */
		public function add_notice( $message, $type ) {
			if ( $message && $this->panel instanceof \YIT_Plugin_Panel_WooCommerce ) {
				$this->panel->add_notice( $message, $type );
			}
		}

		/**
		 * Returns slug for the panel's page
		 *
		 * @return string Panel's page slug
		 */
		public function get_slug() {
			return $this->page_slug;
		}

		/**
		 * Returns base url of the panel
		 *
		 * @param string $tab Optional tab to add to url.
		 * @return string Panel's base url
		 */
		public function get_url( $tab = '' ) {
			$args = array(
				'page' => $this->page_slug,
			);

			// optionally specify tab in the url.
			if ( $tab && in_array( $tab, array_keys( $this->get_available_tabs() ), true ) ) {
				$args['tab'] = $tab;
			}

			/**
			 * APPLY_FILTERS: yith_stripe_payments_admin_panel_url
			 *
			 * Filters the url of the plugin panel.
			 *
			 * @param string $panel_url Plugin panel url.
			 */
			return apply_filters( 'yith_stripe_payments_admin_panel_url', add_query_arg( $args, admin_url( 'admin.php' ) ) );
		}

		/**
		 * Returns screen id of the main plugin panel
		 *
		 * @return string Plugin panel screen id
		 */
		public function get_main_screen_id() {
			$screen_ids = $this->get_screen_ids();
			$base_id    = is_array( $screen_ids ) ? array_shift( $screen_ids ) : '';

			/**
			 * APPLY_FILTERS: yith_stripe_payments_admin_panel_screen_id
			 *
			 * Filters the screen id of the plugin panel.
			 *
			 * @param string $base_id Screen id of the plugin panel.
			 */
			return apply_filters( 'yith_stripe_payments_admin_panel_screen_id', $base_id );
		}
	}
}
