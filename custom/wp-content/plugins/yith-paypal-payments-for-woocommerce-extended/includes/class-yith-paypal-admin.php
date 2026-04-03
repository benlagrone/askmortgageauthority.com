<?php
/**
 * Admin class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH PayPal Payments for WooCommerce
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class YITH_PayPal_Admin
 */
class YITH_PayPal_Admin {

	/**
	 * Plugin panel page
	 *
	 * @const string
	 */
	const PANEL_PAGE = 'yith_paypal_payments';

	/**
	 * Plugin panel page
	 *
	 * @const string
	 */
	const BH_ONBOARDING_COMPLETED_OPTION = 'nfd-ecommerce-captive-flow-paypal';
	/**
	 * Plugin panel object
	 *
	 * @var YITH_PayPal_Admin
	 */
	protected $panel = null;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Plugin Panel.
		add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );
		add_filter( 'yith_plugin_fw_panel_has_help_tab', array( $this, 'has_help_tab' ), 10, 2 );

		// Panel custom type.
		add_action( 'woocommerce_admin_field_yith_ppwc_login_button', array( $this, 'paypal_login_button' ), 10 );

		// Make sure gateway correctly load options after a save or reset action.
		add_action( 'yit_panel_wc_after_update', array( $this, 'sync_environment_change' ), 5 );
		add_action( 'yit_panel_wc_after_update', array( $this, 'reload_after_save' ) );
		add_action( 'reload_after_save', array( $this, 'reload_after_save' ) );

		// Listen the query string to catch the merchant data.
		add_action( 'wp_loaded', array( $this, 'login_merchant_from_query' ), 5 );
		// Handle merchant admin action.
		add_action( 'admin_init', array( $this, 'logout_merchant' ) );
		add_action( 'admin_init', array( $this, 'refresh_merchant' ) );

		add_action( 'admin_init', array( $this, 'redirect_panel_page' ) );


		// Add action links.
		add_filter( 'plugin_action_links_' . plugin_basename( YITH_PAYPAL_PAYMENTS_PATH . '/' . basename( YITH_PAYPAL_PAYMENTS_FILE ) ), array(
			$this,
			'action_links'
		) );
		add_filter( 'yith_show_plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 3 );

		// BH onboarding.
		add_action( 'nfd-ecommerce-captive-flow-paypal', array( $this, 'show_onboarding_content' ) );
		add_action( 'admin_footer', array( $this, 'show_onboarding_pp_button' ) ); // For back-end
	}

	/**
	 * Add the onboarding button inside a hidden div
	 *
	 * @return void
	 */
	public function show_onboarding_pp_button() {
		$wc_ecommerce_version = get_option( 'nfd_ecommerce_module_version', '1.3.25' );

		if ( version_compare( $wc_ecommerce_version, '1.3.26', '<' ) || ! isset( $_GET['page'] ) || ! in_array( sanitize_text_field( wp_unslash( $_GET['page'] ) ), array(
				'bluehost',
				YITH_PayPal::get_instance()->get_newfold_id()
			), true ) ) {
			return;
		}

		?>
        <div class="yith-ppcp-hidden-button-wrapper">
            <a
                    href="<?php echo esc_url( YITH_PayPal::get_instance()->get_gateway()->get_login_url() ); ?>"
                    class="nfd-button nfd-button--primary yith-btn-paypal nfd-text-white"
                    data-paypal-onboard-complete="onboardedCallback"
                    data-paypal-button="PPLtBlue"
					data-yith-paypal-onboard-button="true"
                    target="PPFrame"
            >
				<?php esc_html_e( 'Connect', 'yith-paypal-payments-for-woocommerce' ); ?>

            </a>
        </div>
		<?php

	}

	/**
	 * The login PayPal API credentials button
	 *
	 * @param array $options Options array.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function paypal_login_button( $options ) {

		// check if merchant is currently logged in.
		$merchant = YITH_PayPal_Merchant::get_merchant();

		if ( ! $merchant->is_valid() ) { // needs login.
			// then get the login url.
			$login_url = YITH_PayPal::get_instance()->get_gateway()->get_login_url();
		} else {
			// Logout url action.
			$logout_url = add_query_arg(
				array(
					'page'   => self::PANEL_PAGE,
					'action' => 'logout_merchant',
					'nonce'  => wp_create_nonce( 'logout_merchant' ),
				),
				admin_url( 'admin.php' )
			);
			// Refresh url action.
			$refresh_url = add_query_arg(
				array(
					'page'   => self::PANEL_PAGE,
					'action' => 'refresh_merchant',
					'nonce'  => wp_create_nonce( 'refresh_merchant' ),
				),
				admin_url( 'admin.php' )
			);
		}

		include YITH_PAYPAL_PAYMENTS_PATH . 'templates/admin/login-button.php';
	}

	/**
	 * Add a panel under YITH Plugins tab
	 *
	 * @since 1.0.0
	 */
	public function register_panel() {

		if ( ! empty( $this->panel ) ) {
			return;
		}

		$admin_tabs = array(
			'settings'        => array(
				'title'       => __( 'Settings', 'yith-paypal-payments-for-woocommerce' ),
				'icon'        => 'settings',
				'description' => _x( 'Set the general behaviour of the plugin', 'Admin: Settings tab description', 'yith-paypal-payments-for-woocommerce' ),
			),
			'payment-methods' => array(
				'title'       => __( 'Payment methods', 'yith-paypal-payments-for-woocommerce' ),
				'icon'        => '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"></path></svg>',
				'description' => _x( 'Set the payment options for your shop', 'Admin: Settings tab description', 'yith-paypal-payments-for-woocommerce' ),
			),
			'customization'   => array(
				'title'       => __( 'Customization', 'yith-paypal-payments-for-woocommerce' ),
				'description' => _x( 'Customize the buttons behaviour and style', 'Admin: Settings tab description', 'yith-paypal-payments-for-woocommerce' ),
				'icon'        => '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 11.25l1.5 1.5.75-.75V8.758l2.276-.61a3 3 0 10-3.675-3.675l-.61 2.277H12l-.75.75 1.5 1.5M15 11.25l-8.47 8.47c-.34.34-.8.53-1.28.53s-.94.19-1.28.53l-.97.97-.75-.75.97-.97c.34-.34.53-.8.53-1.28s.19-.94.53-1.28L12.75 9M15 11.25L12.75 9"></path></svg>',
			),
		);

		$args = array(
			'create_menu_page' => true,
			'parent_slug'      => '',
			'page_title'       => 'YITH PayPal Payments for WooCommerce',
			'menu_title'       => 'PayPal Payments for WooCommerce',
			'capability'       => 'manage_woocommerce',
			'parent'           => '',
			'parent_page'      => 'yith_plugin_panel',
			'page'             => self::PANEL_PAGE,
			'admin-tabs'       => $admin_tabs,
			'options-path'     => YITH_PAYPAL_PAYMENTS_PATH . '/plugin-options',
			'class'            => yith_set_wrapper_class(),
			'plugin_slug'      => YITH_PAYPAL_PAYMENTS_SLUG,
			'is_extended'      => true,
			'ui_version'       => 2,
		);

		/* === Fixed: not updated theme  === */
		if ( ! class_exists( 'YIT_Plugin_Panel_WooCommerce' ) ) {
			require_once YITH_PAYPAL_PAYMENTS_PATH . '/plugin-fw/lib/yit-plugin-panel-wc.php';
		}

		$this->panel = new YIT_Plugin_Panel_WooCommerce( $args );
	}

	/**
	 * Login and set merchant data from query string.
	 * The url is the one returned by PayPal login window
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function login_merchant_from_query() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'], $_GET['merchantIdInPayPal'] ) || ! in_array( sanitize_text_field( wp_unslash( $_GET['page'] ) ), array(
			'bluehost',
			YITH_PayPal::get_instance()->get_newfold_id(),
			self::PANEL_PAGE,
			'nfd-ecommerce-captive-flow-paypal'
		), true ) ) {
			return;
		}

		try {
			$operation_transient = 'yith_paypal_login_merchant_process';
			if ( false !== get_transient( $operation_transient ) ) {
				// redirect
				sleep( 3 );
				wp_safe_redirect( add_query_arg( $_GET, self::get_redirect_page_url() ) );
				exit;
			}

			// lock operation
			set_transient( $operation_transient, 'processing', 2 * MINUTE_IN_SECONDS );

			$login_data = isset( $_COOKIE['yith_ppwc_login'] ) ? json_decode( wp_unslash( $_COOKIE['yith_ppwc_login'] ), true ) : false; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( empty( $login_data ) ) {
				throw new Exception( 'Empty login data for merchant.' );
			}

			$merchant = YITH_PayPal_Merchant::get_merchant();
			if ( $merchant->is_valid() ) {
				throw new Exception( 'Merchant already logged in!' );
			}

			if ( $merchant->login( $login_data ) ) {
				// Set the merchant ID.
				$merchant->set( 'merchant_id', sanitize_text_field( wp_unslash( $_GET['merchantIdInPayPal'] ) ) );
				$merchant->set( 'merchant_email', sanitize_text_field( wp_unslash( $_GET['merchantId'] ) ) );
				// Additional fields.
				isset( $_GET['permissionsGranted'] ) && $merchant->set( 'permissions_granted', sanitize_text_field( wp_unslash( $_GET['permissionsGranted'] ) ) );
				isset( $_GET['accountStatus'] ) && $merchant->set( 'account_status', sanitize_text_field( wp_unslash( $_GET['accountStatus'] ) ) );
				isset( $_GET['consentStatus'] ) && $merchant->set( 'consent_status', sanitize_text_field( wp_unslash( $_GET['consentStatus'] ) ) );

				// Save.
				$merchant->refresh();
				$merchant->save();
				update_option( self::BH_ONBOARDING_COMPLETED_OPTION, 'true' );
			} else {
				// If merchant is not valid reset and force delete saved data.
				$merchant->logout();
				update_option( self::BH_ONBOARDING_COMPLETED_OPTION, 'false' );
				throw new Exception( sprintf( 'Error login merchant with current login data %s', print_r( $login_data, true ) ) );
			}
		} catch ( Exception $e ) {
			YITH_PayPal_Logger::log( '[MERCHANT LOGIN ERROR] - ' . $e->getMessage() );
		} finally {

			// unlock operation.
			delete_transient( $operation_transient );

			// redirect.
			wp_safe_redirect( self::get_redirect_page_url() );
			exit;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Manage a logout merchant request
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function logout_merchant() {
		if ( ! isset( $_GET['action'] ) || ! isset( $_GET['nonce'] )
		     || 'logout_merchant' !== sanitize_text_field( wp_unslash( $_GET['action'] ) ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'logout_merchant' ) ) {
			return;
		}

		$merchant = YITH_PayPal_Merchant::get_merchant();
		$merchant->logout();
		update_option( self::BH_ONBOARDING_COMPLETED_OPTION, 'false' );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::get_redirect_page() ) );
		exit;
	}

	/**
	 * Manage a logout merchant request
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function refresh_merchant() {
		if ( ! isset( $_GET['action'] ) || ! isset( $_GET['nonce'] )
		     || 'refresh_merchant' !== sanitize_text_field( wp_unslash( $_GET['action'] ) ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'refresh_merchant' ) ) {
			return;
		}

		$merchant = YITH_PayPal_Merchant::get_merchant();
		$merchant->check_status( true );

		wp_safe_redirect( self::get_redirect_page_url() );
		exit;
	}

	/**
	 * Reload admin section to make sure gateway is correctly initialized
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function reload_after_save() {

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_REQUEST['page'] ) || self::PANEL_PAGE !== sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) ) {
			return;
		}

		$tab = isset( $_REQUEST['tab'] ) ? '&tab=' . sanitize_text_field( wp_unslash( $_REQUEST['tab'] ) ) : '';
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::get_redirect_page() . $tab ) );
		exit;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Redirect the wc-setting page to the YITH panel
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function redirect_panel_page() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$redirect_to = '';
		if ( isset( $_GET['page'], $_GET['tab'], $_GET['section'] ) && 'wc-settings' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) && 'checkout' === sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) {
			switch ( $_GET['section'] ) {
				case 'yith_paypal_payments':
					$redirect_to = add_query_arg( array( 'page' => self::get_redirect_page() ), admin_url( 'admin.php' ) );
					break;
				case 'yith_paypal_payments_apple_pay':
				case 'yith_paypal_payments_google_pay':
				case 'yith_paypal_payments_custom_card':
					$redirect_to = add_query_arg(
						array(
							'page' => self::PANEL_PAGE,
							'tab'  => 'payment-methods',
						),
						admin_url( 'admin.php' )
					);
					break;

				default:
			}
		}

		! empty( $redirect_to ) && wp_safe_redirect( $redirect_to );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Action Links
	 * Add the action links to plugin admin page
	 *
	 * @param string $links | links plugin array.
	 *
	 * @return   mixed
	 * @since 1.0.0
	 */
	public function action_links( $links ) {
		$links = yith_add_action_links( $links, self::PANEL_PAGE, false );

		return $links;
	}

	/**
	 * Plugin row_meta
	 * Add the action links to plugin admin page.
	 *
	 * @param array    $new_row_meta_args An array of plugin row meta.
	 * @param string[] $plugin_meta       An array of the plugin's metadata,
	 *                                    including the version, author,
	 *                                    author URI, and plugin URI.
	 * @param string   $plugin_file       Path to the plugin file relative to the plugin directory.
	 *                                    'Inactive', 'Recently Activated', 'Upgrade', 'Must-Use',
	 *                                    'Drop-ins', 'Search', 'Paused'.
	 *
	 * @return   array
	 * @since 1.0.0
	 */
	public function plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file ) {

		if ( defined( 'YITH_PAYPAL_PAYMENTS_INIT' ) && YITH_PAYPAL_PAYMENTS_INIT === $plugin_file ) {
			$new_row_meta_args['to_show']     = array( 'documentation', 'support' );
			$new_row_meta_args['slug']        = YITH_PAYPAL_PAYMENTS_SLUG;
			$new_row_meta_args['is_extended'] = true;


		}

		return $new_row_meta_args;
	}

	/**
	 * Sync options environment change
	 *
	 * @return void
	 * @since 1.3.1
	 */
	public function sync_environment_change() {

		if ( ! isset( $_POST['yith_ppwc_gateway_options'] ) ) {
			return;
		}

		$pp_options = get_option( 'yith_ppwc_gateway_options', array() );
		if ( ! empty( $pp_options['environment'] ) ) {
			$cc_options = get_option( 'yith_ppwc_cc_gateway_options', array() );
			if ( ! empty( $cc_options ) ) {
				$cc_options['environment'] = $pp_options['environment'];
				update_option( 'yith_ppwc_cc_gateway_options', $cc_options );
			}
		}

	}

	/**
	 * Show the help tab for extended version
	 *
	 * @param bool             $show  Show the tab.
	 * @param YIT_Plugin_Panel $panel Current panel.
	 *
	 * @return bool|mixed
	 */
	public function has_help_tab( $show, $panel ) {
		if ( isset( $panel->settings['plugin_slug'] ) && YITH_PAYPAL_PAYMENTS_SLUG === $panel->settings['plugin_slug'] ) {
			$show = true;
		}

		return $show;
	}

	/**
	 * Add the action to show the onboarding content of PayPal Payments
	 *
	 * @return void
	 */
	public function show_onboarding_content() {
		$redirect_id = YITH_PayPal::get_instance()->get_newfold_id();
		wp_safe_redirect( add_query_arg( array( 'page' => $redirect_id . '#/store/payments' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Return the page of redirect based on the BH onboarding section.
	 *
	 * @return string
	 */
	public static function get_redirect_page() {
		$page = 'nfd-ecommerce-captive-flow-paypal'; //phpcs:ignore

		return defined( 'DOING_YITH_BH_ONBOARDING' ) || ( isset( $_GET['page'] ) &&  !in_array( $_GET['page'], array( self::PANEL_PAGE, 'wc-settings' ) ) )  ? $page : self::PANEL_PAGE; //phpcs:ignore
	}

	/**
	 * Return the page of redirect based on the BH onboarding section.
	 *
	 * @return string
	 */
	public static function get_redirect_login_page() {
		$page = 'nfd-ecommerce-captive-flow-paypal';

		return defined( 'DOING_YITH_BH_ONBOARDING' ) || ( isset( $_GET['page'] ) && $_GET['page'] !== self::PANEL_PAGE ) ? $page : self::PANEL_PAGE; //phpcs:ignore
	}

	/**
	 * Return the page of redirect based on the BH onboarding section.
	 *
	 * @return string
	 */
	public static function get_redirect_page_url() {
		return admin_url( 'admin.php?page=' . self::get_redirect_page() );
	}
}
