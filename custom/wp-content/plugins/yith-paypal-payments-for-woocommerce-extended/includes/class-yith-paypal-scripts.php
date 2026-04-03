<?php
/**
 * Scripts class. This class handle all scripts and style resource
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH PayPal Payments for WooCommerce
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class YITH_PayPal_Scripts
 */
class YITH_PayPal_Scripts {

	/**
	 * Constructor for the gateway.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts_style' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_style' ) );

		add_filter( 'script_loader_src', array( $this, 'sdk_remove_ver_css_js' ), 9999, 2 );
		add_filter( 'script_loader_tag', array( $this, 'add_attribute_to_script_tag' ), 10, 2 );

		add_filter( 'script_loader_tag', array( $this, 'add_bn_code_on_sdk' ), 10, 3 );
	}

	/**
	 * Get an assets url
	 *
	 * @param string $asset Asset url.
	 *
	 * @return string
	 * @since  1.0.0
	 */
	protected function asset_url( $asset ) {
		$debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		$asset = $debug ? str_replace( '.min.js', '.js', $asset ) : $asset;

		return YITH_PAYPAL_PAYMENTS_URL . $asset;
	}

	/**
	 * Load styles
	 *
	 * @return array
	 * @since  1.0.0
	 */
	protected function get_styles() {
		return array(
			'yith-ppwc-backend'  => array(
				'src'   => $this->asset_url( 'assets/css/backend.css' ),
				'scope' => 'admin',
			),
			'yith-ppwc-frontend' => array(
				'src'          => $this->asset_url( 'assets/css/frontend.css' ),
				'inline_style' => true,
				'scope'        => 'frontend',
			),
		);
	}

	/**
	 * Load scripts
	 *
	 * @return array
	 * @since  1.0.0
	 */
	protected function get_scripts() {

		$gateway = YITH_PayPal::get_instance()->get_gateway();

		return array(
			'yith-ppwc-backend'         => array(
				'src'           => $this->asset_url( 'assets/js/backend.min.js' ),
				'scope'         => 'admin',
				'deps'          => array( 'jquery', 'jquery-ui-dialog' ),
				'localized_var' => 'yith_ppwc_admin',
			),
			'yith-ppwc-login-handler'   => array(
				'src'           => $this->asset_url( 'assets/js/login.min.js' ),
				'deps'          => array( 'jquery' ),
				'scope'         => 'admin',
				'localized_var' => 'yith_ppwc_login',
				'in_footer'     => false,
			),
			'yith-ppwc-login'           => array(
				'src'   => $this->asset_url( 'assets/js/on-boarding.min.js' ),
				'deps'  => array( 'yith-ppwc-login-handler' ),
				'scope' => 'admin',
				'in_footer' => true,
				//'localized_var' => 'yith_ppwc_login',
			),
			'yith-ppwc-partial-payment' => array(
				'src'           => $this->asset_url( 'assets/js/partial-payment.min.js' ),
				'scope'         => 'admin-order',
				'deps'          => array( 'jquery' ),
				'localized_var' => 'yith_ppwc_partial_payment',
			),
			'yith-ppwc-sdk'             => array(
				'src'       => $gateway->get_sdk_url(),
				'deps'      => false,
				'scope'     => 'frontend',
				'in_footer' => false,
			),
			'yith-ppwc-applepay-sdk'    => array(
				'src'           => YITH_PayPal_Apple_Pay::get_sdk_url(),
				'deps'          => array( 'yith-ppwc-sdk' ),
				'scope'         => 'frontend',
				'enqueue'       => false,
				'localized_var' => 'yith_ppwc_apple_pay',
			),
			'yith-ppwc-applepay'        => array(
				'src'           => $this->asset_url( 'assets/js/applepay.min.js' ),
				'deps'          => array( 'yith-ppwc-applepay-sdk' ),
				'scope'         => 'frontend',
				'enqueue'       => false,
				'localized_var' => 'yith_ppwc_apple_pay',
			),
			'yith-ppwc-googlepay-sdk'   => array(
				'src'           => YITH_PayPal_Google_Pay::get_sdk_url(),
				'deps'          => array( 'yith-ppwc-sdk' ),
				'scope'         => 'frontend',
				'enqueue'       => false,
				'localized_var' => 'yith_ppwc_google_pay_sdk',
			),
			'yith-ppwc-googlepay'       => array(
				'src'           => $this->asset_url( 'assets/js/googlepay.min.js' ),
				'deps'          => array( 'yith-ppwc-googlepay-sdk' ),
				'scope'         => 'frontend',
				'enqueue'       => false,
				'localized_var' => 'yith_ppwc_google_pay',
			),
			'yith-ppwc-frontend'        => array(
				'src'           => $this->asset_url( 'assets/js/frontend.min.js' ),
				'deps'          => array( 'jquery', 'yith-ppwc-sdk' ),
				'scope'         => 'frontend',
				'localized_var' => 'yith_ppwc_frontend',
			),
		);
	}

	/**
	 * Get localized data for script
	 *
	 * @param string $handle The script handle.
	 *
	 * @return array
	 * @since  1.0.0
	 */
	protected function get_localized_script_data( $handle ) {

		$data    = array();
		$default = array(
			'ajaxAction' => YITH_PayPal_Ajax::AJAX_ACTION,
			'ajaxNonce'  => wp_create_nonce( YITH_PayPal_Ajax::AJAX_ACTION ),
			'ajaxLoader' => YITH_PAYPAL_PAYMENTS_URL . 'assets/images/ajax-loader.gif',
		);

		switch ( $handle ) {
			case 'yith-ppwc-frontend':
				$enabled_funding_sources = get_option( 'yith_ppwc_button_funding_sources', array() );
				ob_start();
				wc_print_notice( __( 'An error occurred while processing the request!', 'yith-paypal-payments-for-woocommerce' ), 'error' );
				$error_message = ob_get_clean();
				$button_color  = str_replace( '-rect', '', get_option( 'yith_ppwc_button_color', 'gold-rect' ) );
				$button_color  = str_replace( '-pill', '', $button_color );
				$data          = array(
					'ajaxUrl'             => WC_AJAX::get_endpoint( YITH_PayPal_Ajax::AJAX_ACTION ),
					'ajaxNonce'           => wp_create_nonce( YITH_PayPal_Ajax::AJAX_ACTION ),
					'customCardPaymentID' => YITH_Paypal::GATEWAY_ID . '_custom_card',
					'buttonShape'         => get_option( 'yith_ppwc_button_shape', 'rect' ),
					'buttonColor'         => $button_color,
					'layout'              => ( empty( $enabled_funding_sources ) || is_product() ) ? 'horizontal' : 'vertical',
					'secure_3d_unknown'   => esc_html_x( 'An error occurred during the payment authorization. Please, try again.', 'Error message on checkout page during the 3D Secure', 'yith-paypal-payments-for-woocommerce' ),
					'secure_3d_no'        => esc_html_x( 'There were some issues with the payment authorization. It is not possible to complete this transaction.', 'Error message on checkout page during the 3D Secure', 'yith-paypal-payments-for-woocommerce' ),
					'errorMessage'        => $error_message,

				);
				break;
			case 'yith-ppwc-partial-payment':
				$data = array(
					'do_payment_confirm' => __( 'Are you sure you wish to proceed with this partial payment? This action cannot be undone.', 'yith-paypal-payments-for-woocommerce' ),
					'void_confirm'       => __( 'Are you sure you wish to proceed to void this payment authorization? You will not be able to ask for the payment anymore.', 'yith-paypal-payments-for-woocommerce' ),
				);
				break;
			case 'yith-ppwc-backend':
				$data = array(
					'confirm_logout_title'   => esc_html__( 'Disconnect your PayPal account', 'yith-paypal-payments-for-woocommerce' ),
					'confirm_logout_content' => esc_html__( 'Disconnecting your PayPal account will prevent you from offering PayPal services and products on your website. Do you want to continue?', 'yith-paypal-payments-for-woocommerce' ),
					'continue'               => esc_html_x( 'Continue', 'Label button of a dialog popup', 'yith-paypal-payments-for-woocommerce' ),
					'cc_enabled'             => yith_ppwc_is_custom_credit_card_enabled(),
					'cc_not_available'       => __( 'Not available if custom credit card option is enabled.', 'yith-paypal-payments-for-woocommerce' ),
				);
				break;
			case 'yith-ppwc-login-handler':
				$environment = defined( 'ONBOARDING_TEST' ) && ONBOARDING_TEST ? 'sandbox' : 'production';
				$merchant    = get_option( "yith_ppwc_merchant_data_{$environment}", false );

				$data = array(
				//	'loginURL'      => YITH_PayPal::get_instance()->get_gateway()->get_login_url( $environment ),
					'partnerId'     => YITH_PayPal_Gateway::PARTNER_ID,
					'liveConnected' => ! empty( $merchant ),
				);
				break;
			case 'yith-ppwc-applepay':
				$data = YITH_PayPal_Apple_Pay::get_instance()->get_script_localize_args();
				break;
			case 'yith-ppwc-googlepay':
				$data = YITH_PayPal_Google_Pay::get_instance()->get_script_localize_args();
				break;
			default:
				break;
		}

		return array_merge( $default, $data );
	}


	/**
	 * Get inline style for the styles
	 *
	 * @param string $handle The style handle.
	 *
	 * @return string
	 * @since  1.0.0
	 */
	protected function get_inline_style( $handle ) {

		$style = '';

		switch ( $handle ) {
			case 'yith-ppwc-frontend':
				$dimensions = yith_plugin_fw_get_dimensions_by_option( 'yith_ppwc_button_size', true );
				$style      = '.yith-ppwc-button{ width:' . $dimensions['width'] . ';}';
				break;
			default:
				break;
		}

		return $style;
	}

	/**
	 * Enqueue plugin styles
	 *
	 * @param string $scope Where embed the file.
	 *
	 * @return void
	 * @since  1.0.0
	 */
	protected function enqueue_styles( $scope = 'frontend' ) {

		$default = array(
			'deps'         => array(),
			'version'      => YITH_PAYPAL_PAYMENTS_VERSION,
			'inline_style' => false,
			'media'        => 'all',
		);

		foreach ( $this->get_styles() as $handle => $data ) {
			// merge data with default.
			$data = array_merge( $default, $data );
			wp_register_style( $handle, $data['src'], $data['deps'], $data['version'], $data['media'] );

			if ( isset( $data['scope'] ) && $scope !== $data['scope'] ) {
				continue;
			}
			// Enqueue registered style.
			wp_enqueue_style( $handle );

			$data['inline_style'] && wp_add_inline_style( $handle, $this->get_inline_style( $handle ) );

		}
	}

	/**
	 * Enqueue plugin scripts
	 *
	 * @param string $scope Where embed the script.
	 *
	 * @return void
	 * @since  1.0.0
	 */
	protected function enqueue_scripts( $scope = 'frontend' ) {

		$default = array(
			'deps'          => array( 'jquery' ),
			'version'       => YITH_PAYPAL_PAYMENTS_VERSION,
			'in_footer'     => true,
			'localized_var' => '',
		);

		$wc_ecommerce_version = get_option( 'nfd_ecommerce_module_version', '1.3.25' );
		foreach ( $this->get_scripts() as $handle => $data ) {

			// Merge data with default.
			$data = array_merge( $default, $data );
			wp_register_script( $handle, $data['src'], $data['deps'], $data['version'], $data['in_footer'] );
			if ( isset( $data['scope'] ) && $scope !== $data['scope'] ) {
				continue;
			}

			if ( version_compare( $wc_ecommerce_version, '1.3.26', '<' ) ) {
				if ( 'yith-ppwc-login' !== $handle ) {
					wp_enqueue_script( $handle );
				}
			} else {
				wp_enqueue_script( $handle );
			}

			// Maybe localize data.
			if ( $data['localized_var'] ) {
				$localized = $this->get_localized_script_data( $handle );
				wp_localize_script( $handle, $data['localized_var'], $localized );
			}
		}
	}

	/**
	 * Admin enqueue scripts and styles
	 *
	 * @param string $hook The hook.
	 *
	 * @return void
	 * @author Emanuela Castorina <emanuela.castorina@yithemes.com>
	 * @author Francesco Licandro <francesco.licandro@yithemes.com>
	 * @since  1.0.0
	 */
	public function admin_enqueue_scripts_style( $hook ) {

		global $current_screen;

		$redirect_id = YITH_PayPal::get_instance()->get_newfold_id();

		if ( isset( $_GET['page'] ) && (
				'nfd-ecommerce-captive-flow-paypal' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ||
				YITH_PayPal_Admin::PANEL_PAGE === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ||
				( 'wc-settings' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) && isset( $_GET['tab'] ) && 'checkout' === sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) ||
				'toplevel_page_' . $redirect_id === $hook
			)
		) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing

			$page = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
			$this->enqueue_styles( 'admin' );
			$this->enqueue_scripts( 'admin' );

			if ( in_array( $page, array (
				YITH_PayPal_Admin::PANEL_PAGE,
				YITH_PayPal::get_instance()->get_newfold_id(),
			), true ) ) {
				wp_enqueue_script( 'yith-ppwc-login' );
			}
		}

		if ( is_null( $current_screen ) ) {
			return;
		}
		if ( ( 'post' === $current_screen->base && 'shop_order' === $current_screen->post_type ) || 'woocommerce_page_wc-orders' === $current_screen->base ) {
			$this->enqueue_scripts( 'admin-order' );
			$this->enqueue_styles( 'admin' );
		}
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function enqueue_scripts_style() {
		/**
		 * APPLY_FILTERS:yith_ppwc_load_frontend_scripts
		 *
		 * Filter if load scripts on frontend.
		 *
		 * @return boolean
		 */

		if ( YITH_PayPal::get_instance()->get_gateway()->is_enabled() && YITH_PayPal_Merchant::get_merchant()->is_valid() && ( YITH_PayPal_Frontend::is_button_visible() || apply_filters( 'yith_ppwc_load_frontend_scripts', false ) ) ) {
			$this->enqueue_styles();
			$this->enqueue_scripts();
		}
	}

	/**
	 * Remove version from script url
	 *
	 * @param string $src Script URL.
	 * @param string $handle The script handle.
	 *
	 * @return bool|string
	 * @since  1.0.0
	 */
	public function sdk_remove_ver_css_js( $src, $handle ) {
		if ( in_array( $handle, array(
				'yith-ppwc-sdk',
				'yith-ppwc-login'
			) ) && strpos( $src, 'ver=' ) ) { //phpcs:ignore
			$src = remove_query_arg( 'ver', $src );
		}

		return $src;
	}

	/**
	 * Add attribute to script tags.
	 *
	 * @param string $tag Script URL.
	 * @param string $handle The script handle.
	 *
	 * @return string
	 * @since  1.0.0
	 */
	public function add_attribute_to_script_tag( $tag, $handle ) {

		if ( 'yith-ppwc-sdk' !== $handle || ! yith_ppwc_is_custom_credit_card_enabled() ) {
			return $tag;
		}

		try {
			$login                = YITH_PayPal_Controller::load( 'login' );
			$client_token_request = $login->get_client_token();
			$tag                  = str_replace( '<script', '<script data-client-token="' . $client_token_request['client_token'] . '"', $tag );

		} catch ( Exception $e ) {
			YITH_PayPal_Logger::log( 'Error triggered during the request of client token for custom credit card. ' . $e->getMessage() );
		}

		return $tag;
	}

	/**
	 * Filters the HTML script tag of an enqueued script adding the BN code
	 *
	 * @param string $tag The `<script>` tag for the enqueued script.
	 * @param string $handle The script's registered handle.
	 * @param string $src The script's source URL.
	 *
	 * @since 2.9.0
	 *
	 */
	public function add_bn_code_on_sdk( $tag, $handle, $src ) {
		$bn_code = YITH_PayPal::get_instance()->get_gateway()->get_bn_code();
		if ( 'yith-ppwc-sdk' === $handle ) {
			$tag = str_replace( ' id=', " data-partner-attribution-id='{$bn_code}' id=", $tag );
		} else {
			if ( stripos( $src, 'paypal.com/sdk' ) !== false ) {
				if ( stripos( $tag, 'partner-attribution-id' ) === false || stripos( $tag, 'YITH' ) || stripos( $tag, 'NEWFOLD' ) ) {
					$tag = str_replace( ' src=', " data-partner-attribution-id='{$bn_code}' src=", $tag );
				}
			}
		}

		return $tag;
	}
}

new YITH_PayPal_Scripts();
