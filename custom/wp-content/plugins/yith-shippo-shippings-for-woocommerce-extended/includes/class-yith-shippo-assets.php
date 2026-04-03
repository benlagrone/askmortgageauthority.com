<?php
/**
 * Class to manage the scripts and styles
 *
 * @class   YITH_Shippo_Assets
 * @package YITH/Shippo
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;

/**
 * Assets Class
 *
 * @class   YITH_Shippo_Assets
 * @package YITH/Shippo
 * @since   1.0.0
 */
class YITH_Shippo_Assets {
	/**
	 * Contains an array of script handles registered by YITH Shippo.
	 *
	 * @var array
	 */
	private static $scripts = array();

	/**
	 * Contains an array of style handles registered by YITH Shippo.
	 *
	 * @var array
	 */
	private static $styles = array();

	/**
	 * Contains an array of script handles localized by YITH Shippo.
	 *
	 * @var array
	 */
	private static $yith_shippo_localize_scripts = array();

	/**
	 * YITH_Shippo_Assets constructor.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_admin_scripts' ), 11 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'load_frontend_scripts' ), 11 );
	}

	/**
	 * Register admin scripts
	 */
	private static function register_admin_scripts() {
		$admin_scripts = array(
			'yith-shippo-admin'          => array(
				'src'     => yit_load_js_file( YITH_SHIPPO_ASSETS_URL . '/js/admin/admin.js' ),
				'deps'    => array( 'jquery' ),
				'version' => YITH_SHIPPO_VERSION,
			),
			'yith-shippo-order-meta-box' => array(
				'src'     => yit_load_js_file( YITH_SHIPPO_ASSETS_URL . '/js/admin/order-meta-box.js' ),
				'deps'    => array( 'jquery', 'serializejson' ),
				'version' => YITH_SHIPPO_VERSION,
			),
			'yith-shippo-parcel'         => array(
				'src'     => yit_load_js_file( YITH_SHIPPO_ASSETS_URL . '/js/admin/parcel.js' ),
				'deps'    => array( 'jquery' ),
				'version' => YITH_SHIPPO_VERSION,
			),
			'yith-shippo-sender-info'    => array(
				'src'     => yit_load_js_file( YITH_SHIPPO_ASSETS_URL . '/js/admin/sender-info.js' ),
				'deps'    => array( 'jquery', 'yith-plugin-fw-wp-pages', 'jquery-blockui' ),
				'version' => YITH_SHIPPO_VERSION,
			),
			'yith-shippo-shipping-rates' => array(
				'src'     => yit_load_js_file( YITH_SHIPPO_ASSETS_URL . '/js/admin/shipping-rates.js' ),
				'deps'    => array( 'jquery', 'yith-plugin-fw-wp-pages' ),
				'version' => YITH_SHIPPO_VERSION,
			),
			'yith-shippo-shipping-rules' => array(
				'src'     => yit_load_js_file( YITH_SHIPPO_ASSETS_URL . '/js/admin/shipping-rules.js' ),
				'deps'    => array( 'jquery' ),
				'version' => YITH_SHIPPO_VERSION,
			),
		);

		foreach ( $admin_scripts as $handle => $admin_script ) {
			self::register_script( $handle, $admin_script['src'], $admin_script['deps'], $admin_script['version'] );
		}
	}

	/**
	 * Register the admin styles
	 *
	 * @since 1.0.0
	 */
	private static function register_admin_styles() {
		$admin_styles = array(
			'yith-shippo-admin' => array(
				'src'     => YITH_SHIPPO_ASSETS_URL . '/css/admin.css',
				'deps'    => array(),
				'version' => YITH_SHIPPO_VERSION,
				'has_rtl' => false,
			),
		);

		foreach ( $admin_styles as $handle => $admin_style ) {
			self::register_style( $handle, $admin_style['src'], $admin_style['deps'], $admin_style['version'], 'all', $admin_style['has_rtl'] );
		}
	}

	/**
	 * Register the frontend styles
	 *
	 * @since 1.0.0
	 */
	private static function register_frontend_styles() {
		$frontend_styles = array(
			'yith-shippo-frontend' => array(
				'src'     => YITH_SHIPPO_ASSETS_URL . '/css/frontend.css',
				'deps'    => array(),
				'version' => YITH_SHIPPO_VERSION,
				'has_rtl' => false,
			),
		);
		foreach ( $frontend_styles as $handle => $frontend_style ) {
			self::register_style( $handle, $frontend_style['src'], $frontend_style['deps'], $frontend_style['version'], 'all', $frontend_style['has_rtl'] );
		}
	}

	/**
	 * Register a style for use.
	 *
	 * @param   string    $handle   Name of the stylesheet. Should be unique.
	 * @param   string    $path     Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
	 * @param   string[]  $deps     An array of registered stylesheet handles this stylesheet depends on.
	 * @param   string    $version  String specifying stylesheet version number, if it has one, which is added to the URL as a query string for cache busting purposes. If version is set to false, a version number is automatically added equal to current installed WordPress version. If set to null, no version is added.
	 * @param   string    $media    The media for which this stylesheet has been defined. Accepts media types like 'all', 'print' and 'screen', or media queries like '(orientation: portrait)' and '(max-width: 640px)'.
	 * @param   boolean   $has_rtl  If has RTL version to load too.
	 *
	 * @since  1.0.0
	 * @uses   wp_register_style()
	 */
	private static function register_style( $handle, $path, $deps = array(), $version = YITH_SHIPPO_VERSION, $media = 'all', $has_rtl = false ) {
		self::$styles[] = $handle;
		wp_register_style( $handle, $path, $deps, $version, $media );

		if ( $has_rtl ) {
			wp_style_add_data( $handle, 'rtl', 'replace' );
		}
	}

	/**
	 * Register and enqueue a styles for use.
	 *
	 * @param   string    $handle   Name of the stylesheet. Should be unique.
	 * @param   string    $path     Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
	 * @param   string[]  $deps     An array of registered stylesheet handles this stylesheet depends on.
	 * @param   string    $version  String specifying stylesheet version number, if it has one, which is added to the URL as a query string for cache busting purposes. If version is set to false, a version number is automatically added equal to current installed WordPress version. If set to null, no version is added.
	 * @param   string    $media    The media for which this stylesheet has been defined. Accepts media types like 'all', 'print' and 'screen', or media queries like '(orientation: portrait)' and '(max-width: 640px)'.
	 * @param   boolean   $has_rtl  If has RTL version to load too.
	 *
	 * @uses   wp_enqueue_style()
	 */
	private static function enqueue_style( $handle, $path = '', $deps = array(), $version = YITH_SHIPPO_VERSION, $media = 'all', $has_rtl = false ) {
		if ( ! in_array( $handle, self::$styles, true ) && $path ) {
			self::register_style( $handle, $path, $deps, $version, $media, $has_rtl );
		}
		wp_enqueue_style( $handle );
	}

	/**
	 * Register a script for use.
	 *
	 * @param   string    $handle     Name of the script. Should be unique.
	 * @param   string    $path       Full URL of the script, or path of the script relative to the WordPress root directory.
	 * @param   string[]  $deps       An array of registered script handles this script depends on.
	 * @param   string    $version    String specifying script version number, if it has one, which is added to the URL as a query string for cache busting purposes. If version is set to false, a version number is automatically added equal to current installed WordPress version. If set to null, no version is added.
	 * @param   boolean   $in_footer  Whether to enqueue the script before </body> instead of in the <head>. Default 'false'.
	 *
	 * @since  1.0.0
	 * @uses   wp_register_script()
	 */
	private static function register_script( $handle, $path, $deps = array( 'jquery' ), $version = YITH_SHIPPO_VERSION, $in_footer = true ) {
		self::$scripts[] = $handle;
		wp_register_script( $handle, $path, $deps, $version, $in_footer );
	}

	/**
	 * Register and enqueue a script for use.
	 *
	 * @param   string    $handle     Name of the script. Should be unique.
	 * @param   string    $path       Full URL of the script, or path of the script relative to the WordPress root directory.
	 * @param   string[]  $deps       An array of registered script handles this script depends on.
	 * @param   string    $version    String specifying script version number, if it has one, which is added to the URL as a query string for cache busting purposes. If version is set to false, a version number is automatically added equal to current installed WordPress version. If set to null, no version is added.
	 * @param   boolean   $in_footer  Whether to enqueue the script before </body> instead of in the <head>. Default 'false'.
	 *
	 * @uses   wp_enqueue_script()
	 */
	private static function enqueue_script( $handle, $path = '', $deps = array( 'jquery' ), $version = YITH_SHIPPO_VERSION, $in_footer = true ) {
		if ( ! in_array( $handle, self::$scripts, true ) && $path ) {
			self::register_script( $handle, $path, $deps, $version, $in_footer );
		}
		wp_enqueue_script( $handle );
	}

	/**
	 * Localize a WC script once.
	 *
	 * @since 2.3.0 this needs less wp_script_is() calls due to https://core.trac.wordpress.org/ticket/28404 being added in WP 4.0.
	 *
	 * @param   string  $handle  Script handle the data will be attached to.
	 */
	public static function localize_script( $handle ) {
		if ( ! in_array( $handle, self::$yith_shippo_localize_scripts, true ) ) {
			$data = self::get_script_data( $handle );

			if ( ! $data ) {
				return;
			}

			$name                                 = str_replace( '-', '_', $handle ) . '_params';
			self::$yith_shippo_localize_scripts[] = $handle;
			/**
			 * APPLY_FILTERS: yith_shippo_$handle_params
			 *
			 * The filter allow to add ,remove the data in the script.
			 *
			 * @param   array  $data  The script data.
			 *
			 * @return array
			 */
			wp_localize_script( $handle, $name, apply_filters( $name, $data ) );
		}
	}

	/**
	 * Return data for script handles.
	 *
	 * @param   string  $handle  Script handle the data will be attached to.
	 *
	 * @return array|bool
	 * @since 1.0.0
	 */
	private static function get_script_data( $handle ) {
		switch ( $handle ) {
			case 'yith-shippo-parcel':
				$params = array(
					'ajaxurl'                     => admin_url( 'admin-ajax.php' ),
					'actions'                     => array(
						'saveParcel'   => 'yith_shippo_save_parcel',
						'enableParcel' => 'yith_shippo_enable_parcel',
						'deleteParcel' => 'yith_shippo_delete_parcel',
					),
					'security'                    => array(
						'saveParcelNonce'   => wp_create_nonce( 'yith_shippo_save_parcel' ),
						'enableParcelNonce' => wp_create_nonce( 'yith_shippo_enable_parcel' ),
						'deleteParcelNonce' => wp_create_nonce( 'yith_shippo_delete_parcel' ),
					),
					'add_new_parcel_title'        => esc_html_x( 'Add box', 'title of modal to add a new parcel box', 'yith-shippo-shippings-for-woocommerce' ),
					'edit_parcel_title'           => esc_html_x( 'Edit box', 'title of modal to edit a parcel box', 'yith-shippo-shippings-for-woocommerce' ),
					'delete_confirmation_title'   => esc_html_x( 'Delete confirmation', 'title of modal to confirm deleting parcel boxes', 'yith-shippo-shippings-for-woocommerce' ),
					'delete_confirmation_message' => esc_html_x( 'Are you sure you want to delete the parcel box?', 'message of modal to confirm deleting parcel boxes', 'yith-shippo-shippings-for-woocommerce' ),
				);
				break;
			case 'yith-shippo-shipping-rates':
				$params = array(
					'ajaxurl'  => admin_url( 'admin-ajax.php' ),
					'actions'  => array(
						'validateProducts' => 'yith_shippo_validate_products',
					),
					'security' => wp_create_nonce( 'yith_shippo_validator' ),
				);
				break;
			case 'yith-shippo-sender-info':
				$params = array(
					'ajaxurl'                     => admin_url( 'admin-ajax.php' ),
					'actions'                     => array(
						'validateSenderInfo'   => 'yith_shippo_validate_sender_info',
						'deleteSenderInfo'     => 'yith_shippo_delete_sender_info',
						'setDefaultSenderInfo' => 'yith_shippo_set_default_sender_info',
					),
					'security'                    => array(
						'addSenderInfoNonce'        => wp_create_nonce( 'yith_shippo_sender_info' ),
						'deleteSenderInfoNonce'     => wp_create_nonce( 'yith_shippo_delete_sender_info' ),
						'setDefaultSenderInfoNonce' => wp_create_nonce( 'yith_shippo_set_default_sender_info' ),
					),
					'woocommerce_shop_base_info'  => yith_shippo_get_woocommerce_store_address_fields(),
					'modal_title'                 => _x( 'Sender Info', 'title of modal to add or update a sender info', 'yith-shippo-shippings-for-woocommerce' ),
					'emptyField'                  => esc_html__( 'This field is required.', 'yith-shippo-shippings-for-woocommerce' ),
					'invalidEmailField'           => esc_html__( 'Invalid email address.', 'yith-shippo-shippings-for-woocommerce' ),
					'delete_confirmation_title'   => esc_html_x( 'Delete confirmation', 'title of modal to confirm deleting sender info', 'yith-shippo-shippings-for-woocommerce' ),
					'delete_confirmation_message' => esc_html_x( 'Are you sure you want to delete the sender info?', 'message of modal to confirm deleting sender info', 'yith-shippo-shippings-for-woocommerce' ),
				);
				break;
			case 'yith-shippo-shipping-rules':
				$params = array(
					'ajaxurl'  => admin_url( 'admin-ajax.php' ),
					'actions'  => array(
						'toggleRuleStatus' => 'yith_shippo_toggle_rule_status',
					),
					'security' => wp_create_nonce( 'yith_shippo_toggle_rule' ),
				);
				break;
			case 'yith-shippo-order-meta-box':
				$params = array(
					'ajaxurl'              => admin_url( 'admin-ajax.php' ),
					'actions'              => array(
						'findService'    => 'yith_shippo_find_service',
						'updateRate'     => 'yith_shippo_update_rate',
						'createShipping' => 'yith_shippo_create_shipping',
						'deleteShipping' => 'yith_shippo_delete_shipping',
						'createShipment' => 'yith_shippo_create_shipment',
						'payShipping'    => 'yith_shippo_pay_shipping',
						'updateTracking' => 'yith_shippo_update_tracking',
					),
					'nonces'               => array(
						'findService'    => wp_create_nonce( 'yith_shippo_find_service' ),
						'createShipping' => wp_create_nonce( 'yith_shippo_create_shipping' ),
						'payShipping'    => wp_create_nonce( 'yith_shippo_pay_shipping' ),
						'updateRate'     => wp_create_nonce( 'yith_shippo_update_rate' ),
						'deleteShipping' => wp_create_nonce( 'yith_shippo_delete_shipping' ),
						'createShipment' => wp_create_nonce( 'yith_shippo_create_shipment' ),
						'updateTracking' => wp_create_nonce( 'yith_shippo_update_tracking' ),
					),
					'servicePopUp'         => array(
						'title'       => esc_html__( 'Shipping services', 'yith-shippo-shippings-for-woocommerce' ),
						'title_error' => esc_html__( 'Shipping services: no rates found', 'yith-shippo-shippings-for-woocommerce' ),
					),
					'confirmDelete'        => array(
						'title'         => esc_html__( 'Confirm delete shipment', 'yith-shippo-shippings-for-woocommerce' ),
						'message'       => esc_html__( 'Are you sure you want to delete this shipping?', 'yith-shippo-shippings-for-woocommerce' ),
						'confirmButton' => esc_html__( 'Delete', 'yith-shippo-shippings-for-woocommerce' ),
					),
					'confirmShipment'      => array(
						'title'         => esc_html__( 'Confirm create shipment', 'yith-shippo-shippings-for-woocommerce' ),
						'message'       => esc_html__( 'Are you sure you want to create this shipment? This information cannot be edited later on.', 'yith-shippo-shippings-for-woocommerce' ),
						'confirmButton' => esc_html__( 'Create', 'yith-shippo-shippings-for-woocommerce' ),
						'title_error'   => esc_html__( 'Error creating the shipment', 'yith-shippo-shippings-for-woocommerce' ),
					),
					'errorconfirmShipment' => array(
						'title'   => esc_html__( 'The shipment can\'t be created', 'yith-shippo-shippings-for-woocommerce' ),
						'message' => esc_html__( 'You need to select a service first.', 'yith-shippo-shippings-for-woocommerce' ),
					),
					'confirmPay'           => array(
						'title'         => esc_html__( 'Confirm payment', 'yith-shippo-shippings-for-woocommerce' ),
						'message'       => esc_html__( 'Are you sure you want to pay for this shipment?', 'yith-shippo-shippings-for-woocommerce' ),
						'confirmButton' => esc_html__( 'Pay', 'yith-shippo-shippings-for-woocommerce' ),
						'title_error'   => esc_html__( 'The payment can\'t be processed', 'yith-shippo-shippings-for-woocommerce' ),
					),
					'errorButtonLabel'     => esc_html_x( 'Ok', 'Is the label of the button to close the error popup', 'yith-shippo-shippings-for-woocommerce' ),
				);
				break;
			case 'yith-shippo-admin':
				$bridge_url     = 'https://ubajxfae45mqetrplratazzlxa0rcswq.lambda-url.us-east-1.on.aws/shippo-oauth-request/';
				$token          = get_option( 'yith_shippo_live_token', '' );
				$admin_url      = admin_url( 'admin.php' );
				$admin_url_args = array(
					'action'     => 'yith_shippo_onboarding',
					'onboarding' => 'yes',
					'page'       => 'yith_shippo_shipping_for_woocommerce',
				);
				$admin_url      = rawurlencode( add_query_arg( $admin_url_args, $admin_url ) );
				$args           = array(
					'return_url' => $admin_url,
				);
				$shippo_url     = esc_url( add_query_arg( $args, $bridge_url ) );
				$params         = array(
					'shippo_url'       => $shippo_url,
					'live_connected'   => ! empty( $token ),
					'disconnect_popup' => array(
						'title'   => __( 'Confirm disconnect?', 'yith-shippo-shippings-for-woocommerce' ),
						'message' => __( 'Are you sure you want to disconnect from Shippo?', 'yith-shippo-shippings-for-woocommerce' ),
						'button'  => __( 'Disconnect!', 'yith-shippo-shippings-for-woocommerce' ),
					),
					'ajaxurl'          => admin_url( 'admin-ajax.php' ),
				);
				break;
			default:
				$params = false;
		}

		/**
		 * APPLY_FILTERS: yith_shippo_get_scripts_data
		 *
		 * This filter allow to add, remove or change the param of a specific script.
		 *
		 * @param   array   $params  The script params.
		 * @param   string  $handle  The script handle.
		 *
		 * @return array
		 */
		return apply_filters( 'yith_shippo_get_scripts_data', $params, $handle );
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @param   string  $hook  The current admin page.
	 */
	public static function load_admin_scripts( $hook ) {
		self::register_admin_scripts();
		self::register_admin_styles();
		global $current_screen;
		$current_screen_base = $current_screen->base ?? false;
		$current_post_type   = $current_screen->post_type ?? false;
		if ( 'yith-plugins_page_yith_shippo_shipping_for_woocommerce' === $hook ) {
			$tab     = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$sub_tab = isset( $_GET['sub_tab'] ) ? sanitize_text_field( wp_unslash( $_GET['sub_tab'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			self::enqueue_style( 'yith-shippo-admin' );
			self::enqueue_script( 'yith-shippo-admin' );

			if ( 'parcel' === $tab ) {
				self::enqueue_script( 'yith-shippo-parcel' );
			} elseif ( 'shipping' === $tab ) {
				if ( 'shipping-sender-info' === $sub_tab ) {
					self::enqueue_script( 'yith-shippo-sender-info' );
				} elseif ( 'shipping-rules' === $sub_tab ) {
					self::enqueue_script( 'yith-shippo-shipping-rules' );
				} else {
					self::enqueue_script( 'yith-shippo-shipping-rates' );
				}
			}
		} elseif ( strpos( $hook, 'toplevel_page_' ) !== false || $current_post_type === 'product' ) {
			self::enqueue_script( 'yith-shippo-admin' );
		} else {
			$order_id = isset( $_GET['post'] ) ? sanitize_text_field( wp_unslash( $_GET['post'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification

			if ( ( $order_id && 'shop_order' === get_post_type( $order_id ) ) || ( $current_post_type && 'shop_order' === $current_post_type ) || 'woocommerce_page_wc-orders' === $current_screen_base ) {
				self::enqueue_style( 'yith-shippo-admin' );
				self::enqueue_script( 'yith-shippo-admin' );
				wp_enqueue_script( 'yit-plugin-metaboxes' );
				wp_enqueue_style( 'yith-plugin-fw-fields' );
				wp_enqueue_script( 'yit-metabox' );
				self::enqueue_script( 'yith-shippo-order-meta-box' );
			}

			$product_id        = isset( $_GET['post'] ) ? sanitize_text_field( wp_unslash( $_GET['post'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification
			$current_post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification
			if ( ( $product_id && 'product' === get_post_type( $product_id ) ) || ( $current_post_type && 'product' === $current_post_type ) || 'woocommerce_page_wc-orders' === $current_screen_base ) {
				wp_enqueue_style( 'yit-plugin-metaboxes' );
				wp_enqueue_style( 'yith-plugin-fw-fields' );
				wp_enqueue_script( 'yit-metabox' );
				self::enqueue_style( 'yith-shippo-admin' );
				self::enqueue_script( 'yith-shippo-admin' );
			}
		}

		self::localize_printed_scripts();
	}

	/**
	 * Localize scripts only when enqueued.
	 */
	public static function localize_printed_scripts() {
		foreach ( self::$scripts as $handle ) {
			self::localize_script( $handle );
		}
	}

	/**
	 * Enqueue frontend scripts
	 */
	public static function load_frontend_scripts() {
		self::register_frontend_styles();
		if ( is_account_page() ) {
			self::enqueue_style( 'yith-shippo-frontend' );
		}
	}

}
