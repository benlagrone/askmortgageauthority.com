<?php
/**
 * Admin Class
 *
 * @class   YITH_Shippo_Admin
 * @package YITH/Shippo
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class
 */
class YITH_Shippo_Admin {
	/**
	 * Panel Object
	 *
	 * @var YIT_Plugin_Panel_WooCommerce
	 */
	protected $panel;
	/**
	 * Panel Page
	 *
	 * @var string
	 */
	public static $panel_page = 'yith_shippo_shipping_for_woocommerce';
	/**
	 * Screen id
	 *
	 * @var string
	 */
	protected $screen_id = 'yith_shippo_shipping_for_woocommerce';

	/**
	 * Construct
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		// Panel.
		add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );
		add_filter( 'yith_plugin_fw_panel_has_help_tab', array( $this, 'has_help_tab' ), 10, 2 );

		// Plugin row action and licence.
		add_filter( 'plugin_action_links_' . plugin_basename( YITH_SHIPPO_DIR . '/' . basename( YITH_SHIPPO_FILE ) ), array( $this, 'action_links' ) );
		add_action( 'admin_init', array( $this, 'register_plugin_for_updates' ) );
		add_filter( 'yith_show_plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 3 );

		// Shipping rules manage.
		add_filter( 'yith_shippo_shipping_rules_options', array( $this, 'use_right_options' ) );
		add_action( 'yith_shippo_shipping_rule_details_panel', array( $this, 'output_shipping_rule_details' ) );
		add_action( 'admin_init', array( $this, 'save_shipping_rule' ) );
		add_action( 'admin_init', array( $this, 'process_shipping_rule_bulk_actions' ), 20 );
		add_filter( 'yith_plugin_fw_yith-plugins_page_yith_shippo_shipping_for_woocommerce_bulk_delete_confirmation_enabled', '__return_true' );
		// Validate products modal.
		add_action( 'admin_footer', array( $this, 'print_validate_products_modal' ) );
		add_action( 'admin_init', array( $this, 'save_postage_option' ), 20 );
		// Add YITH Shippo item in WooCommerce Shipping list.
		add_filter( 'woocommerce_get_sections_shipping', array( $this, 'add_yith_shippo_shipping_section' ) );
		add_action( 'admin_init', array( $this, 'redirect_to_plugin_panel' ) );
		// New column on order details with shipment details.
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_shipment_order_column' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_yith_shippo_shipment_details_column' ), 10, 2 );
		// HPOS
		$this->screen_id = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		add_filter( 'woocommerce_shop_order_list_table_columns', array( $this, 'add_shipment_order_column' ), 20 );
		add_action( 'woocommerce_' . $this->screen_id . '_list_table_custom_column', array( $this, 'render_shop_table_column' ), 10, 2 );
		// Order metabox.
		add_action( 'add_meta_boxes', array( $this, 'add_shipping_meta_box' ), 35 );
		// Product metabox.
		add_action( 'woocommerce_product_options_shipping_product_data', array( $this, 'add_custom_product_meta' ) );
		add_action( 'woocommerce_variation_options_dimensions', array( $this, 'add_custom_product_variaton_meta' ), 10, 3 );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_the_custom_product_meta' ), 10, 1 );
		add_action( 'woocommerce_admin_process_variation_object', array( $this, 'save_the_custom_product_variation_meta' ), 10, 2 );
		// Action scheduler.
		add_action( 'admin_init', array( $this, 'add_carriers_logos_action_scheduler' ) );
		add_action( 'yith_shippo_update_carriers_logo', array( $this, 'set_carriers_scheduled_per_page' ) );
		add_action( 'yith_shippo_update_carriers_logo_per_page', array( $this, 'update_carrier_logos_per_page' ) );
		add_filter( 'yith_plugin_fw_get_field_template_path', array( $this, 'get_yith_panel_custom_template' ), 10, 2 );
		add_filter( 'yith_plugin_fw_inline_fields_allowed_types', array( $this, 'enable_extra_types_in_inline_fields' ), 10, 3 );
		// Autocomple order.
		add_action( 'yith_shippo_tracking_status_updated', array( $this, 'change_order_status' ), 20, 5 );

		// Add the onboarding button in the panel.
		add_action( 'yith_shippo_show_onboarding', array( $this, 'add_onboarding_button' ), 20, 1 );
		add_action( 'admin_init', array( $this, 'show_onboarding_status' ), 0 );
		add_action( 'admin_init', array( $this, 'disconnect_from_shippo' ), 5 );

		// BH onboarding.
		add_filter( 'yith_bh_onboarding_' . YITH_SHIPPO_SLUG, array( $this, 'bh_onboarding_options' ) );
		add_action( 'nfd-ecommerce-captive-flow-shippo', array( $this, 'show_onboarding_content' ) );
		add_action( 'yith_bh_onboarding_save_option_value', array( $this, 'save_onboarding_options' ), 10, 3 );
		add_filter( 'yith_bh_onboarding_save_options_' . YITH_SHIPPO_SLUG, array( $this, 'check_sandbox_option' ), 10, 2 );

	}


	/**
	 * Show the help tab for extended version
	 *
	 * @param   bool              $show   Show the tab.
	 * @param   YIT_Plugin_Panel  $panel  Current panel.
	 *
	 * @return bool|mixed
	 */
	public function has_help_tab( $show, $panel ) {

		if ( isset( $panel->settings['plugin_slug'] ) && YITH_SHIPPO_SLUG === $panel->settings['plugin_slug'] ) {
			$show = true;
		}

		return $show;
	}

	/**
	 * Add a panel under YITH Plugins tab
	 *
	 * @return   void
	 * @since    1.0.0
	 * @use      Yit_Plugin_Panel class
	 * @see      plugin-fw/lib/yit-plugin-panel.php
	 */
	public function register_panel() {
		if ( ! empty( $this->panel ) ) {
			return;
		}
		/**
		 * APPLY_FILTERS: yith_shippo_show_admin_tabs
		 *
		 * This filter allow to add, remove or change the main tab inside the settings panel.
		 *
		 * @param   array  $admin_tabs  List of admin tabs
		 *
		 * @return array
		 */
		$admin_tabs = $this->get_admin_tabs();
		/**
		 * APPLY_FILTERS: yith_shippo_admin_panel_options
		 *
		 * This filter allow change the arguments to create the admin panel.
		 *
		 * @param   array  $arguments  List of settings.
		 *
		 * @return array
		 * @since  1.0.0
		 */
		$args = apply_filters(
			'yith_shippo_admin_panel_options',
			array(
				'create_menu_page' => true,
				'parent_slug'      => '',
				'page_title'       => 'YITH Shippo Shippings for WooCommerce',
				'menu_title'       => 'Shippo Shippings',
				'capability'       => 'manage_options',
				'parent'           => '',
				'parent_page'      => 'yith_plugin_panel',
				'page'             => self::$panel_page,
				'admin-tabs'       => $admin_tabs,
				'options-path'     => YITH_SHIPPO_DIR . 'plugin-options',
				'class'            => yith_set_wrapper_class( 'yith-plugin-fw-wp-page-wrapper' ),
				'plugin_slug'      => YITH_SHIPPO_SLUG,
				'plugin-url'       => YITH_SHIPPO_URL,
				'is_extended'      => true,
				'is_premium'       => false,
				'is_free'          => false,
				'ui_version'       => 2,
				'welcome_modals'   => array(
					'on_close' => function () {
						update_option( 'yith-shippo-plugin-welcome-modal', 'no' );
					},
					'modals'   => array(
						'welcome' => array(
							'type'        => 'welcome',
							'description' => __( 'Shippo is the best multi-carrier shipping software for e-commerce businesses. With this plugin you can connect your Shippo account to manage shippins of products sold in your shop', 'yith-shippo-shippings-for-woocommerce' ),
							'show'        => get_option( 'yith-shippo-plugin-welcome-modal', 'welcome' ) === 'welcome',
							'items'       => array(
								'documentation'  => array(),
								'create-parcels' => array(
									'title'       => __( 'Are your ready? Create your <mark>parcel boxes table</mark>', 'yith-shippo-shippings-for-woocommerce' ),
									'description' => __( 'Start from Step 1: Populate the table with the boxes that you will use to ship your items.', 'yith-shippo-shippings-for-woocommerce' ),
									'url'         => add_query_arg(
										array(
											'page'    => 'yith_shippo_shipping_for_woocommerce',
											'tab'     => 'parcel',
											'sub_tab' => 'parcel-parcel-boxes',
										),
										admin_url(
											'admin.php'
										)
									),
								),
							),
						),
					),
				),
			)
		);
		if ( ! class_exists( 'YIT_Plugin_Panel_WooCommerce' ) ) {
			require_once YITH_SHIPPO_DIR . '/plugin-fw/lib/yit-plugin-panel-wc.php';
		}
		$this->panel = new YIT_Plugin_Panel_WooCommerce( $args );
	}

	/**
	 * Register plugins for update tab
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function register_plugin_for_updates() {
		if ( ! class_exists( 'YIT_Upgrade' ) ) {
			include_once YITH_SHIPPO_DIR . 'plugin-fw/lib/yit-upgrade.php';
		}

		YIT_Upgrade()->register( YITH_SHIPPO_SLUG, YITH_SHIPPO_INIT );

	}


	/**
	 * Add custom panel fields.
	 *
	 * @param   string  $template  Template.
	 * @param   array   $field     Fields.
	 *
	 * @return string
	 * @since  1.0.0
	 */
	public function get_yith_panel_custom_template( $template, $field ) {
		$custom_option_types = array(
			'parcel-list',
			'condition-rule',
			'postage-options',
			'products-shipping',
			'parcel-dimension',
			'choose-service',
			'shipping-zones',
			'sender-info',
			'bh-onboarding-create-an-account-shippo',
		);
		$field_type          = $field['type'];
		if ( isset( $field['type'] ) && in_array( $field['type'], $custom_option_types, true ) ) {
			$template = YITH_SHIPPO_VIEWS_PATH . "/custom-fields/types/{$field_type}.php";
		}

		return $template;
	}

	/**
	 * Add extra field type in the inline field
	 *
	 * @param   array   $allowed_fields  The allowed type.
	 * @param   string  $field_name      The field name.
	 * @param   array   $field           The field.
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public function enable_extra_types_in_inline_fields( $allowed_fields, $field_name, $field ) {
		if ( strpos( $field['id'], 'yith-shippo-subcondition' ) !== false ) {
			$allowed_fields[] = 'ajax-products';
			$allowed_fields[] = 'ajax-terms';
		}
		if ( strpos( $field['id'], 'yith-shippo-parcel' ) !== false ) {
			$allowed_fields[] = 'parcel-dimension';
		}

		return $allowed_fields;
	}

	/**
	 * Action Links
	 *
	 * Add the action links to plugin admin page
	 *
	 * @param   array  $links  | links plugin array.
	 *
	 * @return   array
	 * @since    1.0
	 * @use      plugin_action_links_{$plugin_file_name}
	 */
	public function action_links( $links ) {
		return yith_add_action_links( $links, self::$panel_page, false, YITH_SHIPPO_SLUG );
	}

	/**
	 * Plugin_row_meta
	 *
	 * Add the action links to plugin admin page
	 *
	 * @param   mixed  $new_row_meta_args  new row meta args.
	 * @param   mixed  $plugin_meta        plugin meta.
	 * @param   mixed  $plugin_file        plugin file.
	 *
	 * @return   array
	 * @since    1.0
	 * @use      plugin_row_meta
	 */
	public function plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file ) {
		if ( defined( 'YITH_SHIPPO_INIT' ) && YITH_SHIPPO_INIT === $plugin_file ) {
			$new_row_meta_args['slug']        = YITH_SHIPPO_SLUG;
			$new_row_meta_args['is_extended'] = true;
			$new_row_meta_args['to_show']     = array( 'documentation', 'support' );
		}

		return $new_row_meta_args;
	}

	/**
	 * Check if show the list table or the edit page
	 *
	 * @param   array  $shipping_rule_options  The shipping rule options.
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public function use_right_options( $shipping_rule_options ) {
		$show_single_page = false;
		if ( isset( $_GET['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$action = sanitize_text_field( wp_unslash( $_GET['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( 'new_shipping_rule' === $action || 'edit' === $action && isset( $_GET['shipping_rule_id'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification
				$show_single_page = true;
			}
		}
		if ( $show_single_page ) {
			$shipping_rule_options = array(
				'shipping-rules' => array(
					'shipping_details' => array(
						'type'   => 'custom_tab',
						'action' => 'yith_shippo_shipping_rule_details_panel',
					),
				),
			);
		}

		return $shipping_rule_options;
	}

	/**
	 * Print the shipping rule panel
	 *
	 * @since  1.0.0
	 */
	public function output_shipping_rule_details() {
		$shipping_rule = false;
		if ( isset( $_GET['action'] ) && 'edit' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) && isset( $_GET['shipping_rule_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$shipping_rule_id = intval( wp_unslash( $_GET['shipping_rule_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$shipping_rule    = yith_shippo_get_shipping_rule( $shipping_rule_id );
			if ( ! $shipping_rule ) {
				// translators: 1. Shippung rule id.
				wp_die( esc_html( sprintf( _x( 'Shipping rule #%d doesn\'t exist', '[ADMIN] Shipping rule details page', 'yith-shippo-shippings-for-woocommerce' ), $shipping_rule_id ) ) );
			}
		}
		if ( isset( $_GET['action'] ) && 'new_shipping_rule' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$shipping_rule = yith_shippo_get_shipping_rule();
		}
		if ( $shipping_rule ) {
			include YITH_SHIPPO_VIEWS_PATH . '/shipping/shipping-rule-details.php';
		}
	}

	/**
	 * Update or create new shipping rule
	 *
	 * @since  1.0.0
	 */
	public function save_shipping_rule() {

		if ( isset( $_POST['yith_shippo_save_rule_nonce'], $_POST['yith_shippo_rule_id'], $_POST['yith_shippo_rule'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['yith_shippo_save_rule_nonce'] ) ), 'yith_shippo_save_rule' ) ) {

			$ship_rule_id = intval( wp_unslash( $_POST['yith_shippo_rule_id'] ) );

			$shipping_posted = $_POST['yith_shippo_rule']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$shipping_data   = array();

			foreach ( $shipping_posted as $data_key => $value ) {

				$sanitized_value = false;
				switch ( $data_key ) {
					case 'name':
					case 'shipping_service':
					case 'custom_label':
					case 'condition_type':
					case 'condition_mode':
						$sanitized_value = sanitize_text_field( wp_unslash( $value ) );
						break;
					case 'condition_enabled':
					case 'fee_enabled':
					case 'label_enabled':
						$sanitized_value = 1;
						break;
					case 'fee':
						$sanitized_value              = array();
						$sanitized_value['fee_type']  = sanitize_text_field( wp_unslash( $value['fee_type'] ) );
						$sanitized_value['fee_value'] = (float) $value['fee_value'];
						break;
					case 'conditions':
						$sanitized_value    = array();
						$condition_subtypes = $value['condition_subtypes'];
						foreach ( $condition_subtypes as $condition_subtype ) {
							$condition_meta_key = $condition_subtype['subtype_type'];
							if ( isset( $condition_subtype[ $condition_meta_key ] ) ) {
								$condition_value = array_map( 'intval', (array) $condition_subtype[ $condition_meta_key ] );
								if ( ! isset( $sanitized_value[ $condition_meta_key ] ) ) {
									$sanitized_value[ $condition_meta_key ] = $condition_value;
								} else {
									$old_val                                = $sanitized_value[ $condition_meta_key ];
									$sanitized_value[ $condition_meta_key ] = array_unique( array_merge( $old_val, $condition_value ) );
								}
							} else {
								$sanitized_value[ $condition_meta_key ] = array();
							}
						}
						break;
					case 'shipping_zones':
						$sanitized_value = array_map( 'intval', $value );
						break;
					default:
						$sanitized_value = false;
						break;
				}
				if ( ! $sanitized_value ) {
					continue;
				}
				if ( 'fee' === $data_key ) {
					$shipping_data = array_merge( $shipping_data, $sanitized_value );
				} else {
					$shipping_data[ $data_key ] = $sanitized_value;
				}
			}

			try {
				$shipping_rule = yith_shippo_get_shipping_rule( $ship_rule_id );
				$shipping_rule->disable_condition();
				$shipping_rule->disable_fee();
				$shipping_rule->disable_custom_label();
				$shipping_rule->set_props( $shipping_data );

				$shipping_rule->save();

				$return_url = admin_url( 'admin.php' );
				$args       = array(
					'page'             => 'yith_shippo_shipping_for_woocommerce',
					'tab'              => 'shipping',
					'sub_tab'          => 'shipping-rules',
					'action'           => 'edit',
					'shipping_rule_id' => $shipping_rule->get_id(),
				);
				$return_url = add_query_arg( $args, $return_url );
				wp_safe_redirect( $return_url );
				exit;
			}
			catch ( Exception $e ) {
				wp_die( esc_html( $e->getMessage() ) );
			}
		}
	}

	/**
	 * Save the postage option that is a custom field
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function save_postage_option() {

		if ( ! isset( $_REQUEST['yit_panel_wc_options_nonce'], $_REQUEST['yith_shippo_default_postage_options'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['yit_panel_wc_options_nonce'] ) ), 'yit_panel_wc_options_' . $this->panel->settings['page'] ) ) {
			return;
		}

		update_option( 'yith_shippo_default_postage_options', wc_clean( $_REQUEST['yith_shippo_default_postage_options'] ) ); //phpcs:ignore

	}

	/**
	 * Process the bulk actions
	 *
	 * @since  1.0.0
	 */
	public function process_shipping_rule_bulk_actions() {

		if ( isset( $_GET['page'], $_GET['sub_tab'], $_REQUEST['action'] ) && 'yith_shippo_shipping_for_woocommerce' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) && 'shipping-rules' === sanitize_text_field( wp_unslash( $_GET['sub_tab'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( in_array( $action, array( 'delete', 'enable', 'disable' ), true ) ) {
				$ids_to_process = array();

				if ( isset( $_REQUEST['shipping_rule_id'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$ids_to_process = array( intval( wp_unslash( $_REQUEST['shipping_rule_id'] ) ) );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				} elseif ( isset( $_REQUEST['shipping_rules'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$ids_to_process = array_map( 'intval', $_REQUEST['shipping_rules'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				}

				foreach ( $ids_to_process as $id ) {
					$ship_rule = yith_shippo_get_shipping_rule( $id );
					if ( $ship_rule ) {
						if ( 'delete' === $action ) {
							$ship_rule->delete();
						} else {
							$ship_rule->set_enabled( 'enable' === $action );
							$ship_rule->save();
						}
					}
				}

				$return_url = admin_url( 'admin.php' );
				$args       = array(
					'page'    => 'yith_shippo_shipping_for_woocommerce',
					'tab'     => 'shipping',
					'sub_tab' => 'shipping-rules',
				);
				$return_url = add_query_arg( $args, $return_url );
				wp_safe_redirect( $return_url );
				exit;
			}
		}
	}

	/**
	 * Print Products Validation Modal
	 *
	 * @since  1.0.0
	 */
	public function print_validate_products_modal() {
		if ( isset( $_REQUEST['page'], $_REQUEST['tab'], $_REQUEST['sub_tab'] ) && self::$panel_page === $_REQUEST['page'] && 'shipping' === $_REQUEST['tab'] && 'shipping-rates' === $_REQUEST['sub_tab'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			yith_shippo_get_view( '/shipping/validate-products-modal.php' );
		}
	}

	/**
	 * Add a new order column with the shipment details in the WooCommerce Orders page.
	 *
	 * @param   array  $columns  Existing columns.
	 *
	 * @return array
	 */
	public function add_shipment_order_column( $columns ) {

		// translators: Added column title on WooCommerce Orders page.
		$columns['yith_shippo_shipment_details'] = __( 'Shipment Tracking', 'yith-shippo-shippings-for-woocommerce' );

		return $columns;
	}

	/**
	 * Add shipment tracking details to the custom order column in the WooCommerce Orders page.
	 *
	 * @param   array  $column    Existing column.
	 * @param   int    $order_id  The order id.
	 */
	public function render_yith_shippo_shipment_details_column( $column, $order_id ) {
		$order = is_numeric( $order_id ) ? wc_get_order( $order_id ) : $order_id;

		if ( 'yith_shippo_shipment_details' === $column ) {
			$order_shipping = yith_shippo_get_order_shipping( $order->get_id() );
			$args           = array(
				'order_id'       => $order->get_id(),
				'order_shipping' => $order_shipping,
			);
			yith_shippo_get_view( '/orders-page/shipment-tracking.php', $args );
		}
	}

	/**
	 * Add the YITH shippo section
	 *
	 * @param   array  $sections  The shipping sections.
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public function add_yith_shippo_shipping_section( $sections ) {

		$sections['yith_shippo'] = __( 'YITH Shippo Shippings', 'yith-shippo-shippings-for-woocommerce' );

		return $sections;
	}

	/**
	 * Check if customer click on YITH Shippo section in WooCommerce->Settings->Shipping and in case redirect in our panel
	 *
	 * @since  1.0.0
	 */
	public function redirect_to_plugin_panel() {
		if ( isset( $_GET['page'], $_GET['section'] ) && 'wc-settings' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) && 'yith_shippo' === sanitize_text_field( wp_unslash( $_GET['section'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$return_url = admin_url( 'admin.php' );
			$args       = array( 'page' => 'yith_shippo_shipping_for_woocommerce' );
			$return_url = add_query_arg( $args, $return_url );
			wp_safe_redirect( $return_url );
			exit;
		}
	}

	/**
	 * Return the list of the admin tabs
	 *
	 * @return array
	 */
	protected function get_admin_tabs() {
		/**
		 * APPLY_FILTERS:
		 *
		 * This filter allow to add, remove or change the main tab inside the settings panel.
		 *
		 * @param   array  $admin_tabs  List of admin tabs
		 *
		 * @return array
		 */
		return apply_filters(
			'yith_shippo_show_admin_tabs',
			array(
				'general'  => array(
					'title' => esc_html__( 'General Options', 'yith-shippo-shippings-for-woocommerce' ),
					'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
				),
				'shipping'  => array(
					'title' => esc_html__( 'Shipping Settings', 'yith-shippo-shippings-for-woocommerce' ),
					'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>',
				),
				'parcel'  => array(
					'title' => esc_html__( 'Parcel Packing', 'yith-shippo-shippings-for-woocommerce' ),
					'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>',
				),
				'checkout'  => array(
					'title' => esc_html__( 'Checkout', 'yith-shippo-shippings-for-woocommerce' ),
					'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>',
				),
			)
		);
	}

	/**
	 * Add the order metabox
	 *
	 * @since  1.0.0
	 */
	public function add_shipping_meta_box() {
		if ( yith_shippo()->request->validate_token() ) {
			add_meta_box( 'yith-shippo-order-shippings', __( 'Shippo Shippings', 'yith-shippo-shippings-for-woocommerce' ), 'YITH_Shippo_Meta_Box_Shipping_Order::output', wc_get_page_screen_id( 'shop-order' ), 'normal', 'high' );
		}
	}

	/**
	 * Add the custom meta box in the product
	 *
	 * @since  1.0.0
	 */
	public function add_custom_product_meta() {
		yith_shippo_get_view( '/product-metabox/product-meta-box.php' );
	}

	/**
	 * Add the custom meta box in the product variation
	 *
	 * @param   int      $loop            Position in the loop.
	 * @param   array    $variation_data  Variation data.
	 * @param   WP_Post  $variation       Post data.
	 *
	 * @since  1.0.0
	 */
	public function add_custom_product_variaton_meta( $loop, $variation_data, $variation ) {
		$args = array(
			'loop'           => $loop,
			'variation_data' => $variation_data,
			'variation'      => $variation,
		);
		yith_shippo_get_view( '/product-metabox/product-variation-meta-box.php', $args );
	}

	/**
	 * Save the custom product meta
	 *
	 * @param   WC_Product  $product  The product.
	 *
	 * @since  1.0.0
	 */
	public function save_the_custom_product_meta( $product ) {
		$tariff  = isset( $_POST['_yith_shippo_tariff_number'] ) ? sanitize_text_field( wp_unslash( $_POST['_yith_shippo_tariff_number'] ) ) : '';  // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$country = isset( $_POST['_yith_shippo_country_origin'] ) ? sanitize_text_field( wp_unslash( $_POST['_yith_shippo_country_origin'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$product->update_meta_data( '_yith_shippo_tariff_number', $tariff );
		$product->update_meta_data( '_yith_shippo_country_origin', $country );
	}

	/**
	 * Save the custom product variation meta
	 *
	 * @param   WC_Product_Variation  $variation  The variation.
	 * @param   int                   $loop       The loop.
	 *
	 * @since  1.0.0
	 */
	public function save_the_custom_product_variation_meta( $variation, $loop ) {
		$tariff  = isset( $_POST['_yith_shippo_variation_tariff_number'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['_yith_shippo_variation_tariff_number'][ $loop ] ) ) : '';  // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$country = isset( $_POST['_yith_shippo_variation_country_origin'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['_yith_shippo_variation_country_origin'][ $loop ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$variation->update_meta_data( '_yith_shippo_variation_tariff_number', $tariff );
		$variation->update_meta_data( '_yith_shippo_variation_country_origin', $country );

	}

	/**
	 * Add action scheduler to update the carrier logos monthly.
	 *
	 * @since  1.0.0
	 */
	public function add_carriers_logos_action_scheduler() {
		$exists = WC()->queue()->get_next( 'yith_shippo_update_carriers_logo' );

		if ( ! $exists ) {
			WC()->queue()->schedule_recurring( strtotime( '+30 second' ), 30 * DAY_IN_SECONDS, 'yith_shippo_update_carriers_logo', array(), 'yith-shippo-carriers' );
		}
	}

	/**
	 * Set the carriers logo action schedulers per each page.
	 *
	 * @since  1.0.0
	 */
	public function set_carriers_scheduled_per_page() {
		$page = 1;

		do {
			$carriers = yith_shippo()->request->carrier_accounts->get_all_carrier_accounts( $page );
			if ( $carriers->results ) {

				$args = array(
					'page' => $page,
				);

				WC()->queue()->schedule_single( strtotime( '+30 second' ), 'yith_shippo_update_carriers_logo_per_page', $args, 'yith-shippo-carriers' );
				$page ++;
			}
		} while ( null !== $carriers->next );
	}

	/**
	 * Update the carriers per page.
	 *
	 * @param   int  $page  The current page.
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function update_carrier_logos_per_page( $page ) {

		$carriers = yith_shippo()->request->carrier_accounts->get_all_carrier_accounts( $page );

		if ( $carriers->results ) {
			foreach ( $carriers->results as $carrier ) {
				$carrier_name      = ! empty( $carrier->carrier_name ) ? $carrier->carrier_name : '';
				$carrier_name      = preg_replace( '/\s+/', '_', $carrier_name );
				$carrier_image_url = ! empty( $carrier->carrier_images ) ? $carrier->carrier_images[0] : ''; // [0] means size 200.
				if ( $carrier_image_url ) {
					yith_shippo_save_carrier_image( $carrier_name, $carrier_image_url );
				}
			}
		}
	}

	/**
	 * Change the order status to complete if all order tracking are delivered
	 *
	 * @param   string                $old_status        The old status.
	 * @param   string                $new_status        The new status.
	 * @param   array                 $tracking_updated  The tracking array.
	 * @param   string                $rate_key          The rate key.
	 * @param   YITH_Shippo_Tracking  $shippo_tracking   The tracking object.
	 *
	 * @since  1.0.0
	 */
	public function change_order_status( $old_status, $new_status, $tracking_updated, $rate_key, $shippo_tracking ) {

		$tracking_status = get_option( 'yith_shippo_complete_order', array() );

		if ( count( $tracking_status ) > 0 && in_array( $new_status, $tracking_status, true ) ) {
			$order_id = $shippo_tracking->get_order_id();
			$order    = wc_get_order( $order_id );
			/**
			 * APPLY_FILTERS: yith_shippo_order_statuses
			 *
			 * This filter allow to add additional order status where is possible try to autocomple the order.
			 *
			 * @param   array  $order_status  list of order status.
			 *
			 * @return array
			 */
			$order_status_to_check = apply_filters( 'yith_shippo_order_statuses', array( 'processing' ) );
			if ( $order && $order->has_status( $order_status_to_check ) ) {
				$trackings  = yith_shippo_get_trackings( array( 'order_id' => $order_id ) );
				$can_update = true;

				foreach ( $trackings as $tracking ) {
					if ( ! in_array( $tracking->get_status(), $tracking_status, true ) ) {
						$can_update = false;
						break;
					}
				}

				if ( $can_update ) {
					/* translators: %s is the list of tracking status */
					$transition_note = sprintf( __( 'All shippings have one of these statuses: %s.', 'yith-shippo-shippings-for-woocommerce' ), implode( ', ', $tracking_status ) );

					$order->update_status( 'completed', $transition_note );
				}
			}
		}
	}

	/**
	 * Show the onboarding button
	 *
	 * @param   array  $field  The field.
	 *
	 * @since  1.0.0
	 */
	public function add_onboarding_button( $field ) {
		yith_shippo_get_view( '/custom-fields/types/onboarding-button.php', array( 'field' => $field ) );
	}

	/**
	 * Show the onboarding status template
	 *
	 * @since  1.0.0
	 */
	public function show_onboarding_status() {

		if ( ! isset( $_GET['action'] ) || 'yith_shippo_onboarding' !== $_GET['action'] ) {
			return;
		}
		if ( ! empty( $_GET['error'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$view_args = array(
				'error'             => sanitize_text_field( wp_unslash( $_GET['error'] ) ), //phpcs:ignore WordPress.Security.NonceVerification.Recommended,
				'error_description' => ! empty( $_GET['error_description'] ) ? sanitize_text_field( wp_unslash( $_GET['error_description'] ) ) : __(
					'Error: impossible to complete the onboarding.',
					'yith-shippo-shippings-for-woocommerce'
				), //phpcs:ignore WordPress.Security.NonceVerification.Recommended,
			);
		} elseif ( ! empty( $_GET['code'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$view_args = yith_shippo()->request->retrive_oauth_code( sanitize_text_field( wp_unslash( $_GET['code'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		yith_shippo_get_view( '/onboarding/onboarding-status.php', array( 'result' => $view_args ) );
	}

	/**
	 * Handle the disconnect action
	 *
	 * @since  1.0.0
	 */
	public function disconnect_from_shippo() {
		if ( isset( $_POST['yith_shippo_disconnect'] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST['yith_shippo_disconnect'] ) ) ) { //phpcs:ignore WordPress.Security.NonceVerification
			yith_shippo()->request->disconnect_from_shippo();
		}
	}

	/**
	 * ONBOARDING METHODS
	 */

	/**
	 * Get the BH onboarding options
	 */
	public function bh_onboarding_options() {
		return include YITH_SHIPPO_DIR . 'plugin-options/bh-onboarding-options.php';
	}

	/**
	 * Add the action to show the onboarding content of Shippo
	 *
	 * @return void
	 */
	public function show_onboarding_content() {
		do_action( 'yith_bh_onboarding', YITH_SHIPPO_SLUG );
	}

	/**
	 * Filter the value of the onboarding options
	 *
	 * @param   mixed   $value  Current value.
	 * @param   array   $field  Current field.
	 * @param   string  $slug   Slug of plugin.
	 *
	 * @return mixed|void
	 */
	public function save_onboarding_options( $value, $field, $slug ) {
		if ( YITH_SHIPPO_SLUG !== $slug ) {
			return $value;
		}

		if ( isset( $field['yith-type'], $field['id'] ) && is_array( $value ) && 'sender-info' === $field['yith-type'] ) {
			$sender_info = get_option( $field['id'], array() );
			if ( isset( $_POST['yith-shippo-sender-info-key'], $sender_info[ $_POST['yith-shippo-sender-info-key'] ] ) ) { //phpcs:ignore
				$sender_info[ $_POST['yith-shippo-sender-info-key'] ] = array_pop( $value ); //phpcs:ignore

				return $sender_info;
			}
		}

		return $value;
	}

	/**
	 * Update the captive flow shippo option if the sandbox is selected and a token in added
	 *
	 * @param   boolean  $save_option  Current status of filter.
	 * @param   array    $posted       Post array.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function check_sandbox_option( $save_option, $posted ) {

		if ( isset( $posted['yith_shippo_environment'] ) && 'sandbox' === sanitize_text_field( wp_unslash( $posted['yith_shippo_environment'] ) ) ) {
			$sanbox_token = sanitize_text_field( wp_unslash( $posted['yith_shippo_sandbox_token'] ) );
			update_option( 'nfd-ecommerce-captive-flow-shippo', empty( $sanbox_token ) ? 'false' : 'true' );
		}

		return $save_option;
	}

	/**
	 * Render shop table column
	 *
	 * @since 2.15.0
	 * @param string   $column_id The column id to render.
	 * @param WC_Order $order     Order instance.
	 * @return void
	 */
	public function render_shop_table_column( $column_id, $order ) {

		if ( method_exists( $this, "render_{$column_id}_column" ) ) {
			call_user_func( array( $this, "render_{$column_id}_column" ), $column_id, $order );
		}
	}
}
