<?php
/**
 * Shippo Functions
 *
 * @package YITH\Shippo\Functions
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'yith_shippo_get_shipping_rule' ) ) {

	/**
	 * Get the shipping rule object
	 *
	 * @param   YITH_Shippo_Shipping_Rule|int  $the_shipping_rule  The shipping rule to get.
	 *
	 * @return bool|YITH_Shippo_Shipping_Rule
	 * @since  1.0.0
	 */
	function yith_shippo_get_shipping_rule( $the_shipping_rule = 0 ) {
		try {
			return new YITH_Shippo_Shipping_Rule( $the_shipping_rule );
		}
		catch ( Exception $e ) {
			return false;
		}
	}
}

if ( ! function_exists( 'yith_shippo_get_shipping_rules' ) ) {
	/**
	 * Get all shipping rules by specific args
	 *
	 * @param   array  $args  The query args.
	 *
	 * @return array|int|bool|YITH_Shippo_Shipping_Rule[]
	 * @throws Exception The exception.
	 * @since  1.0.0
	 */
	function yith_shippo_get_shipping_rules( $args ) {
		try {
			return WC_Data_Store::load( 'shippo_shipping_rule' )->query( $args );
		}
		catch ( Exception $e ) {
			return false;
		}
	}
}

if ( ! function_exists( 'yith_shippo_get_shipping_label_format' ) ) {
	/**
	 * Return the list of all shippin label format supported
	 *
	 * @return array
	 */
	function yith_shippo_get_shipping_label_format() {
		$label_format = array(
			'PNG'              => 'PNG',
			'PNG_2.3x7.5'      => 'PNG_2.3x7.5',
			'PDF'              => 'PDF',
			'PDF_SINGLE_8X11'  => 'PDF_SINGLE_8X11',
			'PDF_W_PSLIP_8x11' => 'PDF_W_PSLIP_8x11',
			'PDF_2.3x7.5'      => 'PDF_2.3x7.5',
			'PDF_4x6'          => 'PDF_4x6',
			'PDF_4x8'          => 'PDF_4x8',
			'PDF_A4'           => 'PDF_A4',
			'PDF_A6'           => 'PDF_A6',
			'ZPLII'            => 'ZPLII',
		);

		return $label_format;
	}
}

if ( ! function_exists( 'yith_shippo_camel_case' ) ) {
	/**
	 * Return the string in camel case format
	 *
	 * @param   string  $string  String to convert.
	 *
	 * @return string
	 * @since  1.0.0
	 */
	function yith_shippo_camel_case( $string ) {
		$split = explode( '_', $string );
		$split = array_map( 'ucfirst', $split );
		$split = implode( '_', $split );

		return $split;
	}
}

if ( ! function_exists( 'yith_shippo_get_view' ) ) {
	/**
	 * Get the view
	 *
	 * @param   string  $file_name  Name of the file to get in views.
	 * @param   array   $args       Arguments.
	 */
	function yith_shippo_get_view( $file_name, $args = array() ) {
		$file_path = YITH_SHIPPO_VIEWS_PATH . $file_name;

		if ( file_exists( $file_path ) ) {
			extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			include $file_path;
		}
	}
}

if ( ! function_exists( 'yith_shippo_convert_from_to' ) ) {
	/**
	 * Convert the amount to a specific unit dimentsion.
	 *
	 * @param   string  $value  Value to convert.
	 * @param   string  $from   Dimension unit of current value.
	 * @param   string  $to     Dimension unit to covert.
	 *
	 * @return string
	 */
	function yith_shippo_convert_from_to( $value, $from, $to ) {
		if ( $from === $to ) {
			return $value;
		}

		$conversion_table = array(
			'm'   => array(
				'mm' => 1000,
				'cm' => 100,
				'in' => 39.370,
				'yd' => 1.09361,
			),
			'cm'  => array(
				'mm' => 10,
				'm'  => 0.01,
				'in' => 0.393701,
				'yd' => 0.0109361,
			),
			'mm'  => array(
				'cm' => 0.1,
				'm'  => 0.001,
				'in' => 0.0393701,
				'yd' => 0.00109361,
			),
			'in'  => array(
				'mm' => 25.4,
				'cm' => 2.54,
				'm'  => 0.0254,
				'yd' => 0.0277778,
			),
			'yd'  => array(
				'mm' => 914.4,
				'cm' => 91.44,
				'm'  => 0.9144,
				'in' => 36,
			),
			'lbs' => array(
				'kg' => 0.453592,
				'g'  => 453.59200,
				'oz' => 16,
			),
			'kg'  => array(
				'lbs' => 2.20462,
				'g'   => 1000,
				'oz'  => 35.274,
			),
			'g'   => array(
				'kg'  => 0.001,
				'lbs' => 0.00220462,
				'oz'  => 0.035274,
			),
			'oz'  => array(
				'kg'  => 0.0283495,
				'g'   => 28.3495,
				'lbs' => 0.0625,
			),
		);

		if ( isset( $conversion_table[ $from ], $conversion_table[ $from ][ $to ] ) ) {
			$value = (float) $value * $conversion_table[ $from ][ $to ];
			$value = wc_format_decimal( $value, 2 );
		}

		return (string) $value;
	}
}

if ( ! function_exists( 'yith_shippo_get_parcel_box' ) ) {
	/**
	 * Get the parcel box object
	 *
	 * @param   YITH_Shippo_Parcel_Box|int  $parcel_box  The parcel box to get.
	 *
	 * @return bool|YITH_Shippo_Parcel_Box
	 * @since  1.0.0
	 */
	function yith_shippo_get_parcel_box( $parcel_box = 0 ) {
		try {
			return new YITH_Shippo_Parcel_Box( $parcel_box );
		}
		catch ( Exception $e ) {
			return false;
		}
	}
}

if ( ! function_exists( 'yith_shippo_check_products_dimensions' ) ) {
	/**
	 * Get the products with wrong weight and dimensions
	 *
	 * @param   int  $offset  The offset args.
	 * @param   int  $limit   The limit args.
	 *
	 * @return array
	 */
	function yith_shippo_check_products_dimensions( $offset = 0, $limit = 0 ) {
		global $wpdb;
		$post_type   = "'product', 'product_variation'";
		$post_status = 'publish';
		// phpcs:disable
		$query = $wpdb->prepare(
			"SELECT SQL_CALC_FOUND_ROWS *, posts.ID FROM {$wpdb->posts} AS posts
			LEFT JOIN {$wpdb->postmeta} AS postmeta_virtual ON posts.ID = postmeta_virtual.post_id
			LEFT JOIN {$wpdb->postmeta} AS postmeta_weight ON posts.ID = postmeta_weight.post_id AND postmeta_weight.meta_key = '_weight'
			LEFT JOIN {$wpdb->postmeta} AS postmeta_length ON posts.ID = postmeta_length.post_id AND postmeta_length.meta_key = '_length'
			LEFT JOIN {$wpdb->postmeta} AS postmeta_width ON posts.ID = postmeta_width.post_id AND postmeta_width.meta_key = '_width'
			LEFT JOIN {$wpdb->postmeta} AS postmeta_height ON posts.ID = postmeta_height.post_id AND postmeta_height.meta_key = '_height'
			WHERE (
				posts.post_type IN ({$post_type}) AND posts.post_status = %s AND postmeta_virtual.meta_key = '_virtual' AND postmeta_virtual.meta_value = 'no'
				AND
				( postmeta_weight.meta_key IS NULL OR postmeta_weight.meta_value <= 0
				OR postmeta_length.meta_key IS NULL OR postmeta_length.meta_value <= 0
				OR postmeta_width.meta_key IS NULL OR postmeta_width.meta_value <= 0
				OR postmeta_height.meta_key IS NULL OR postmeta_height.meta_value <= 0
				)
				)
				ORDER BY posts.post_title
				LIMIT %d OFFSET %d
				",
			$post_status,
			$limit,
			$offset
		);
		// phpcs:enable
		$result['products']       = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$result['total_products'] = $wpdb->get_var( 'SELECT FOUND_ROWS()' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared

		return $result;

	}
}

if ( ! function_exists( 'yith_shippo_get_postage_content' ) ) {
	/**
	 * Return the list of the content fot postage
	 *
	 * @return array
	 * @since 1.0.0
	 */
	function yith_shippo_get_postage_content() {
		$postage_content = array(
			'merchandise'           => esc_html_x( 'Merchandise', 'type of postage content', 'yith-shippo-shippings-for-woocommerce' ),
			'documents'             => esc_html_x( 'Documents', 'type of postage content', 'yith-shippo-shippings-for-woocommerce' ),
			'gift'                  => esc_html_x( 'Gift', 'type of postage content', 'yith-shippo-shippings-for-woocommerce' ),
			'return_merchandise'    => esc_html_x( 'Returned goods', 'type of postage content', 'yith-shippo-shippings-for-woocommerce' ),
			'humanitarian_donation' => esc_html_x( 'Humanitarian donation', 'type of postage content', 'yith-shippo-shippings-for-woocommerce' ),
			'other'                 => esc_html_x( 'Other', 'type of postage content', 'yith-shippo-shippings-for-woocommerce' ),
		);

		/**
		 * APPLY_FILTERS: yith_shippo_postage_content
		 *
		 * This filter allow to add, remove or change the postage content.
		 *
		 * @param   array  $postage_content  List of contents
		 *
		 * @return array
		 */
		return apply_filters( 'yith_shippo_postage_content', $postage_content );
	}
}

if ( ! function_exists( 'yith_shippo_get_default_parcel_dimension' ) ) {
	/**
	 * Return the default parcel dimension coverted based on the units set on the store
	 *
	 * @return array
	 * @since 1.0.0
	 */
	function yith_shippo_get_default_parcel_dimension() {

		$weight_unit    = get_option( 'woocommerce_weight_unit' );
		$dimension_unit = get_option( 'woocommerce_dimension_unit' );

		$default_parcel_dimensions = array(
			'weight'         => yith_shippo_convert_from_to( '0.1', 'kg', $weight_unit ),
			'length'         => yith_shippo_convert_from_to( '20', 'cm', $dimension_unit ),
			'height'         => yith_shippo_convert_from_to( '2', 'cm', $dimension_unit ),
			'width'          => yith_shippo_convert_from_to( '20', 'cm', $dimension_unit ),
			'dimension_unit' => $dimension_unit,
			'weight_unit'    => $weight_unit,
		);

		return $default_parcel_dimensions;
	}
}

if ( ! function_exists( 'yith_shippo_get_woocommerce_store_address_fields' ) ) {
	/**
	 * Get WooCommerce Store Address Fields
	 *
	 * @return array
	 */
	function yith_shippo_get_woocommerce_store_address_fields() {
		$countries            = WC()->countries;
		$country_code         = $countries->get_base_country();
		$state_code           = $countries->get_base_state();
		$country_code         .= empty( $state_code ) ? '' : ':' . $state_code;
		$store_address_fields = array(
			'address_1'     => $countries->get_base_address(),
			'address_2'     => $countries->get_base_address_2(),
			'country_state' => $country_code,
			'city'          => $countries->get_base_city(),
			'zip_code'      => $countries->get_base_postcode(),
		);

		return $store_address_fields;
	}
}

if ( ! function_exists( 'yith_shippo_get_customer_address' ) ) {
	/**
	 * Get customer address from Checkout process
	 *
	 * @param   bool|int  $the_order_id  The order id.
	 *
	 * @return array
	 */
	function yith_shippo_get_customer_address( $the_order_id = false ) {
		$address = array();

		if ( $the_order_id ) {
			$order              = wc_get_order( $the_order_id );
			$phone              = $order->get_shipping_phone();
			$address['name']    = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
			$address['company'] = $order->get_shipping_company();
			$address['street1'] = $order->get_shipping_address_1();
			$address['city']    = $order->get_shipping_city();
			$address['state']   = $order->get_shipping_state();
			$address['zip']     = $order->get_shipping_postcode();
			$address['country'] = $order->get_shipping_country();
			$address['phone']   = empty( $phone ) ? $order->get_billing_phone() : $phone;
			$address['email']   = $order->get_billing_email();
		} else {
			$posted_data = isset( $_POST['post_data'] ) ? wp_unslash( $_POST['post_data'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( ! empty( $posted_data ) ) {
				parse_str( $posted_data, $posted_data );
				$ship_to_different_address = ! empty( $posted_data['ship_to_different_address'] ) && ! wc_ship_to_billing_address_only();
				$field_to_check            = $ship_to_different_address ? 'shipping_' : 'billing_';
				$args_to_check             = array(
					'first_name' => 'name',
					'last_name'  => 'name',
					'company'    => 'company',
					'address_1'  => 'street1',
					'city'       => 'city',
					'state'      => 'state',
					'postcode'   => 'zip',
					'country'    => 'country',
					'phone'      => 'phone',
				);
				foreach ( $args_to_check as $wc_field => $ship_field ) {
					$key = $field_to_check . $wc_field;
					if ( ! empty( $posted_data[ $key ] ) ) {
						if ( ! isset( $address[ $ship_field ] ) ) {
							$address[ $ship_field ] = sanitize_text_field( wp_unslash( $posted_data[ $key ] ) );
						} else {
							$address[ $ship_field ] .= ' ' . sanitize_text_field( wp_unslash( $posted_data[ $key ] ) );
						}
					}
				}
				$address['email'] = isset( $posted_data['billing_email'] ) ? sanitize_text_field( wp_unslash( $posted_data['billing_email'] ) ) : '';
			} else {
				if ( isset( WC()->customer ) ) {
					$customer           = WC()->customer;
					$phone              = $customer->get_shipping_phone();
					$address['name']    = $customer->get_shipping_first_name() . ' ' . $customer->get_shipping_last_name();
					$address['company'] = $customer->get_shipping_company();
					$address['street1'] = $customer->get_shipping_address_1();
					$address['city']    = $customer->get_shipping_city();
					$address['state']   = $customer->get_shipping_state();
					$address['zip']     = $customer->get_shipping_postcode();
					$address['country'] = $customer->get_shipping_country();
					$address['phone']   = empty( $phone ) ? $customer->get_billing_phone() : $phone;
					$address['email']   = $customer->get_billing_email();
				}
			}
		}
		$address['is_residential'] = empty( $address['company'] );

		return $address;
	}
}

if ( ! function_exists( 'yith_shippo_get_owner_site_address' ) ) {
	/**
	 * Get the address of the owner
	 *
	 * @param   int|bool  $zone_id  The zone id.
	 *
	 * @return array
	 *
	 * @since  1.0.0
	 */
	function yith_shippo_get_owner_site_address( $zone_id = false ) {
		$default_senders = yith_shippo_get_default_sender_info();
		$sender_info     = get_option( 'yith-shippo-sender-info', $default_senders );

		$default_info = false;
		$address      = array(
			'address_1'     => '',
			'address_2'     => '',
			'zip_code'      => '',
			'country_state' => '',
			'country'       => '',
			'state'         => '',
		);
		// Find the default.
		foreach ( $sender_info as $single_sender ) {
			if ( $single_sender['default'] ) {
				$default_info = $single_sender;
				$address      = $default_info;
				break;
			}
		}
		if ( false !== $zone_id && yith_shippo_support_shipping_zones() ) {
			foreach ( $sender_info as $single_sender ) {
				if ( ! $single_sender['default'] && in_array( $zone_id, array_map( 'intval', $single_sender['shipping_zones'] ), true ) ) {
					$address = $single_sender;
					break;
				}
			}
		}

		$address            = array_map( 'wp_unslash', (array) $address );
		$address['street1'] = sanitize_text_field( $address['address_1'] );
		$address['street2'] = sanitize_text_field( $address['address_2'] );
		$address['zip']     = sanitize_text_field( $address['zip_code'] );

		unset( $address['address_1'], $address['address_2'], $address['zip_code'] );
		$state_country      = sanitize_text_field( $address['country_state'] );
		$state_country      = explode( ':', $state_country );
		$address['country'] = 2 === count( $state_country ) ? $state_country[0] : $state_country[0];
		$address['state']   = 2 === count( $state_country ) ? $state_country[1] : '';

		return $address;
	}
}

if ( ! function_exists( 'yith_shippo_get_service_options' ) ) {
	/**
	 * Return the currier service option for shipping rules
	 *
	 * @return array
	 * @since  1.0.0
	 */
	function yith_shippo_get_service_options() {
		$currier_list = yith_shippo_get_services_list();

		return array_merge(
			array(
				'all' => __( 'All services', 'yith-shippo-shippings-for-woocommerce' ),
			),
			$currier_list
		);
	}
}

if ( ! function_exists( 'yith_shippo_get_services_list' ) ) {
	/**
	 * Return the currier service as array list ordered
	 *
	 * @return array
	 * @since  1.0.0
	 */
	function yith_shippo_get_services_list() {
		$options      = array();
		$currier_list = yith_shippo()->request->currier_service_list_template->get_currier_service_templates();
		foreach ( $currier_list as $currier ) {
			$services = $currier['service_levels'] ?? array();

			foreach ( $services as $service ) {
				$options[ $service['token'] ] = $currier['carrier_name'] . ' ' . $service['name'];
			}
		}
		asort( $options );

		return $options;
	}
}

if ( ! function_exists( 'yith_shippo_get_order_shipping' ) ) {
	/**
	 * Return the order shipping from order
	 *
	 * @param   WC_Order|int  $order  Order.
	 *
	 * @return YITH_Shippo_Order_Shipping|bool
	 *
	 * @since  1.0.0
	 */
	function yith_shippo_get_order_shipping( $order ) {

		$order          = is_numeric( $order ) ? wc_get_order( $order ) : $order;
		$order_shipping = false;
		if ( ! $order instanceof WC_Order ) {
			return $order_shipping;
		}

		$shipping_items = $order->get_items( 'shipping' );
		foreach ( $shipping_items as $shipping ) {
			if ( $shipping->get_method_id() === 'yith_shippo' ) {
				$order_shipping = new YITH_Shippo_Order_Shipping( $shipping->get_id() );
				break;
			}
		}

		return $order_shipping;
	}
}

if ( ! function_exists( 'yith_shippo_save_carrier_image' ) ) {
	/**
	 * Store the carrier image if not exist
	 *
	 * @param   string  $carrier_name  The carrier name.
	 * @param   string  $image_url     The image url.
	 *
	 * @since  1.0.0
	 */
	function yith_shippo_save_carrier_image( $carrier_name, $image_url ) {
		$folder_name      = 'carriers';
		$folder_path      = YITH_SHIPPO_DOCUMENT_SAVE_DIR . $folder_name . '/';
		$carrier_name     = preg_replace( '/\s+/', '_', $carrier_name );
		$image_local_path = $folder_path . $carrier_name . '.png';
		if ( file_exists( $image_local_path ) ) {
			unlink( $image_local_path );
		}

		$image = wp_remote_get( $image_url );
		if ( ! is_wp_error( $image ) ) {
			file_put_contents( $image_local_path, $image['body'] ); // phpcs:ignore
		}
	}
}

if ( ! function_exists( 'yith_shippo_get_carrier_image_path' ) ) {
	/**
	 * Return the carrier image path if exist.
	 *
	 * @param   string  $carrier_name  The carrier name.
	 *
	 * @return bool|string
	 * @since  1.0.0
	 */
	function yith_shippo_get_carrier_image_path( $carrier_name ) {
		$folder_name      = 'carriers';
		$folder_path      = YITH_SHIPPO_DOCUMENT_SAVE_DIR . $folder_name . '/';
		$carrier_name     = preg_replace( '/\s+/', '_', $carrier_name );
		$image_local_path = $folder_path . $carrier_name . '.png';
		if ( file_exists( $image_local_path ) ) {
			return $image_local_path;
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'yith_shippo_get_carrier_image_src' ) ) {
	/**
	 * Return the carrier image src if exist.
	 *
	 * @param   string  $carrier_name  The carrier name.
	 *
	 * @return string
	 * @since  1.0.0
	 */
	function yith_shippo_get_carrier_image_src( $carrier_name ) {

		$image_path = yith_shippo_get_carrier_image_path( $carrier_name );
		$image_url  = '';
		if ( $image_path ) {
			$uploads   = wp_get_upload_dir();
			$image_url = str_replace( $uploads['basedir'], $uploads['baseurl'], $image_path );
		}

		return $image_url;
	}
}

if ( ! function_exists( 'yith_shippo_get_label_type' ) ) {
	/**
	 * Return the label type.
	 *
	 * @param   string  $label_url  The label URL.
	 *
	 * @return string
	 * @since  1.0.0
	 */
	function yith_shippo_get_label_type( $label_url ) {
		$file_type = 'pdf';

		stream_context_set_default(
			array(
				'ssl' => array(
					'verify_peer'      => false,
					'verify_peer_name' => false,
				),
			)
		);

		$content_type = get_headers( $label_url, 1 )['Content-Type'];

		if ( 'image/png' === $content_type ) {
			$file_type = 'png';
		} elseif ( 'application/octet-stream' === $content_type ) { // ZPLII content.
			$file_type = 'zplii';
		}

		return $file_type;
	}
}

if ( ! function_exists( 'yith_shippo_sort_rates' ) ) {
	/**
	 * Sort the rates by cost
	 *
	 * @param   array  $rate_a  The first rate array.
	 * @param   array  $rate_b  The second rate array.
	 *
	 * @return int
	 * @since  1.0.0
	 */
	function yith_shippo_sort_rates( $rate_a, $rate_b ) {

		if ( $rate_a['cost'] <= $rate_b['cost'] ) {
			return - 1;
		} else {
			return 1;
		}
	}
}

if ( ! function_exists( 'yith_shippo_get_json' ) ) {
	/**
	 * Get an array and return a json
	 *
	 * @param   array  $data  The data.
	 *
	 * @return string
	 *
	 * @since  1.0.0
	 */
	function yith_shippo_get_json( $data ) {
		$data_json = wp_json_encode( $data );
		$data_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $data_json ) : _wp_specialchars( $data_json, ENT_QUOTES, 'UTF-8', true );

		return $data_attr;
	}
}

if ( ! function_exists( 'yith_shippo_get_order_info_by_tracking_number' ) ) {
	/**
	 * Return the order info and the rate key from tracking number
	 *
	 * @param   string  $tracking_number  Tracking number.
	 *
	 * @return null|array
	 */
	function yith_shippo_get_order_info_by_tracking_number( $tracking_number ) {
		global $wpdb;
		$query      = $wpdb->prepare( "SELECT post_id as order_id, meta_key as rate_key FROM {$wpdb->postmeta} WHERE meta_value=%s and meta_key LIKE %s ", $tracking_number, '_tracking_%' );
		$order_info = $wpdb->get_row( $query, ARRAY_A ); //phpcs:ignore

		if ( isset( $result['rate_key'] ) ) {
			$order_info['rate_key'] = str_replace( '_tracking_', '', $order_info['rate_key'] );
		}

		return $order_info;
	}
}

if ( ! function_exists( 'yith_shippo_get_default_postage_options' ) ) {
	/**
	 * Return the default information about the postage_options
	 *
	 * @return array
	 */
	function yith_shippo_get_default_postage_options() {
		$default = array(
			'shipment_description'  => __( 'Merchandise', 'yith-shippo-shippings-for-woocommerce' ),
			'content'               => 'merchandise',
			'country_origin'        => WC()->countries->get_base_country(),
			'domestic_service'      => '',
			'international_service' => '',
			'tariff_number'         => '',
		);

		return $default;
	}
}

if ( ! function_exists( 'yith_shippo_get_trackings' ) ) {
	/**
	 * Return the tracking object from tracking number
	 *
	 * @param   array  $args  Arguments of query.
	 *
	 * @return YITH_Shippo_Tracking[]
	 *
	 * @throws Exception Throws Exception.
	 * @since  1.0.0
	 */
	function yith_shippo_get_trackings( $args ) {
		$trackings    = array();
		$data_store   = WC_Data_Store::load( 'shippo_tracking' );
		$tracking_ids = $data_store->get_trackings( $args );
		if ( $tracking_ids ) {
			foreach ( $tracking_ids as $tracking_id ) {
				$trackings[] = new YITH_Shippo_Tracking( $tracking_id );
			}
		}

		return $trackings;
	}
}

if ( ! function_exists( 'yith_shippo_get_substatus_with_action_required_list' ) ) {
	/**
	 * Return the list os substatus where is requested an action
	 *
	 * @return array
	 *
	 * @since  1.0.0
	 */
	function yith_shippo_get_substatus_with_action_required_list() {
		$substatus = array(
			'address_issue',
			'contact_carrier',
			'delivery_attempted',
			'location_inaccessible',
			'notice_left',
			'package_damaged',
			'package_held',
			'pickup_available',
			'reschedule_delivery',
			'package_unclaimed',
			'package_undeliverable',
			'package_lost',
		);

		/**
		 * APPLY_FILTERS:
		 *
		 * This filter allow to add, remove or change the substatus that require an action.
		 *
		 * @param   array  $substatus  List of substatus.
		 *
		 * @return array
		 */
		return apply_filters( 'yith_shippo_substatus_with_action_required', $substatus );
	}
}


if ( ! function_exists( 'yith_shippo_get_shipping_zones ' ) ) {
	/**
	 * Return the shipping zone as options.
	 *
	 * @return array
	 */
	function yith_shippo_get_shipping_zones() {

		$options = array();
		$zones   = array();

		$data_store = WC_Data_Store::load( 'shipping-zone' );
		$raw_zones  = $data_store->get_zones();
		foreach ( $raw_zones as $raw_zone ) {
			$zones[] = new WC_Shipping_Zone( $raw_zone );
		}
		$zones[] = new WC_Shipping_Zone( 0 );

		foreach ( $zones as $zone ) {
			if ( 0 === $zone->get_id() ) {
				$options[0] = _x( 'Others', 'Generic shipping zones', 'yith-shippo-shippings-for-woocommerce' );
			} else {
				$options[ $zone->get_id() ] = $zone->get_zone_name();
			}
		}
		$yih_shippo_shipping_zones = $options;

		return $options;
	}
}

if ( ! function_exists( 'yith_shippo_get_product_tariff_number' ) ) {
	/**
	 * Get the product tariff number
	 *
	 * @param   WC_Product  $product  The product.
	 *
	 * @since 1.0.0
	 */
	function yith_shippo_get_product_tariff_number( $product ) {
		$tariff = '';

		if ( $product ) {
			// if is a variation check the variation meta, if empty check the parent meta , otherwise get the default.
			if ( 'variation' === $product->get_type() ) {
				$tariff = $product->get_meta( '_yith_shippo_variation_tariff_number' );
				if ( empty( $tariff ) ) {
					$parent_product = wc_get_product( $product->get_parent_id() );
					$tariff         = $parent_product->get_meta( '_yith_shippo_tariff_number' );
				}
			} else {
				$tariff = $product->get_meta( '_yith_shippo_tariff_number' );
			}
		}
		if ( empty( $tariff ) ) {
			$postage_options = get_option( 'yith_shippo_default_postage_options', yith_shippo_get_default_postage_options() );
			$tariff          = $postage_options['tariff_number'];
		}

		return $tariff;
	}
}

if ( ! function_exists( 'yith_shippo_get_product_country_origin' ) ) {
	/**
	 * Get the product country
	 *
	 * @param   WC_Product  $product  The product.
	 *
	 * @since 1.0.0
	 */
	function yith_shippo_get_product_country_origin( $product ) {
		$country = '';

		if ( $product ) {
			// if is a variation check the variation meta, if empty check the parent meta , otherwise get the default.
			if ( 'variation' === $product->get_type() ) {
				$country = $product->get_meta( '_yith_shippo_variation_country_origin' );
				if ( empty( $country ) ) {
					$parent_product = wc_get_product( $product->get_parent_id() );
					$country        = $parent_product->get_meta( '_yith_shippo_country_origin' );
				}
			} else {
				$country = $product->get_meta( '_yith_shippo_country_origin' );
			}
		}
		if ( empty( $country ) ) {
			$postage_options = get_option( 'yith_shippo_default_postage_options', yith_shippo_get_default_postage_options() );
			$country         = $postage_options['country_origin'];
		}

		return $country;
	}
}

if ( ! function_exists( 'yith_shippo_support_shipping_zones' ) ) {
	/**
	 * Check if the plugin support the live rate on zones
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	function yith_shippo_support_shipping_zones() {

		if ( defined( 'DOING_YITH_BH_ONBOARDING' ) || isset( $_REQUEST['onboarding'] ) ) {
			return false;
		}
		$opt = get_option( 'yith-shippo-enable-rates-in-zone', 'off' );

		return yith_plugin_fw_is_true( $opt );
	}
}

if ( ! function_exists( 'yith_shippo_get_shipping_zone_id_by_package' ) ) {
	/**
	 * Return the current zone id
	 *
	 * @param   array  $package  The package.
	 *
	 * @return int
	 * @since 1.0.0
	 */
	function yith_shippo_get_shipping_zone_id_by_package( $package ) {
		$shipping_zone = WC_Shipping_Zones::get_zone_matching_package( $package );

		return $shipping_zone->get_id();
	}
}

if ( ! function_exists( 'yith_shippo_get_shipping_zone_id_by_order' ) ) {
	/**
	 * Get zone id by the order informations
	 *
	 * @param   WC_Order  $the_order  The order.
	 *
	 * @return int
	 * @since 1.0.0
	 */
	function yith_shippo_get_shipping_zone_id_by_order( $the_order ) {
		$package = array(
			'destination' => array(
				'country'  => $the_order->get_shipping_country(),
				'state'    => $the_order->get_shipping_state(),
				'postcode' => $the_order->get_shipping_postcode(),
			),
		);

		return yith_shippo_get_shipping_zone_id_by_package( $package );
	}
}

if ( ! function_exists( 'yith_shippo_get_default_sender_info' ) ) {
	/**
	 * Get the default option for sender info
	 *
	 * @return array
	 * @since  1.0.0
	 */
	function yith_shippo_get_default_sender_info() {

		$wc_address_info = yith_shippo_get_woocommerce_store_address_fields();

		$wc_contact_info = array(
			'name'           => get_bloginfo( 'blogname' ),
			'company'        => get_bloginfo( 'blogname' ),
			'email'          => get_option( 'admin_email' ),
			'phone'          => '',
			'use_wc_address' => 1,
			'default'        => 0,
		);

		return array( array_merge( $wc_contact_info, $wc_address_info ) );
	}
}

if ( ! function_exists( 'yith_shippo_convert_parcel_templates' ) ) {
	/**
	 * Convert the measurement of template parcel in the current dimension unit
	 *
	 * @param   array  $templates  Parcel templates.
	 *
	 * @return array
	 * @since  1.0.0
	 */
	function yith_shippo_convert_parcel_templates( $templates ) {
		$wc_dimension_unit   = get_option( 'woocommerce_dimension_unit' );
		$converted_templates = array();

		if ( $templates ) {
			foreach ( $templates as $template ) {
				if ( $template['distance_unit'] !== $wc_dimension_unit ) {
					$template['height']        = yith_shippo_convert_from_to( $template['height'], $template['distance_unit'], $wc_dimension_unit );
					$template['width']         = yith_shippo_convert_from_to( $template['width'], $template['distance_unit'], $wc_dimension_unit );
					$template['length']        = yith_shippo_convert_from_to( $template['length'], $template['distance_unit'], $wc_dimension_unit );
					$template['distance_unit'] = $wc_dimension_unit;
				}

				array_push( $converted_templates, $template );
			}

			return $converted_templates;
		}

		return $converted_templates;
	}
}
if ( ! function_exists( 'yith_shippo_is_request_for_url' ) ) {
	/**
	 * Check if a request is for a specific url
	 *
	 * @param   string  $url  The url to check.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	function yith_shippo_is_request_for_url( $url ) {
		$result = false;
		$url    = rtrim( $url, '/' );

		if ( strpos( $_SERVER['REQUEST_URI'], $url )  !== false ) {
			$result = true;
		} else if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			if ( isset( $_SERVER['HTTP_REFERER'] ) && rtrim( preg_replace( '/\?.*/', '', sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) ), '/' ) === $url ) {
				$result = true;
			}
		} else {
			$requested_url = sprintf(
				'%s://%s%s',
				( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ? 'https' : 'http' ),
				isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '',
				isset( $_SERVER['REQUEST_URI'] ) ? preg_replace( '/\?.*/', '', sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : ''
			);

			$requested_url = rtrim( $requested_url, '/' );

			if ( $url === $requested_url ) {
				$result = true;
			}
		}

		return $result;
	}
}
