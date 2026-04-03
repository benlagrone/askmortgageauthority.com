<?php
/**
 * Class to manage the AJAX procedures
 *
 * @class   YITH_Shippo_Ajax
 * @package YITH/Shippo
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;

/**
 * The class
 */
class YITH_Shippo_Ajax {

	use YITH_Shippo_Trait_Singleton;

	/**
	 * Construct
	 */
	private function __construct() {
		$this->logger = YITH_Shippo_Logger::get_instance();

		$ajax_actions = array(
			'validate_products',
			'toggle_rule_status',
			'validate_sender_info',
			'delete_sender_info',
			'set_default_sender_info',
			'find_service',
			'update_rate',
			'create_shipping',
			'delete_shipping',
			'create_shipment',
			'pay_shipping',
			'update_tracking',
			'save_parcel',
			'enable_parcel',
			'delete_parcel',
			'disconnect_shippo',
		);

		foreach ( $ajax_actions as $ajax_action ) {
			add_action( 'wp_ajax_yith_shippo_' . $ajax_action, array( $this, $ajax_action ) );
			add_action( 'wp_ajax_nopriv_yith_shippo_' . $ajax_action, array( $this, $ajax_action ) );
		}
	}

	/**
	 * Validate products weights and dimension.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function validate_products() {
		$response =
			array(
				'success' => false,
			);
		if ( isset( $_POST['security'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'yith_shippo_validator' ) ) {
			$offset       = isset( $_POST['offset'] ) ? sanitize_text_field( wp_unslash( $_POST['offset'] ) ) : 0;
			$limit        = isset( $_POST['limit'] ) ? sanitize_text_field( wp_unslash( $_POST['limit'] ) ) : 0;
			$results      = yith_shippo_check_products_dimensions( $offset, $limit );
			$all_products = $results['total_products'];

			$wrong_products = '';
			foreach ( $results['products'] as $product ) {
				$product = wc_get_product( $product['ID'] );
				$args    = array(
					'product' => $product,
				);
				ob_start();
				yith_shippo_get_view( '/shipping/wrong-product.php', $args );
				$wrong_products .= ob_get_clean();
			}
			if ( ! empty( $wrong_products ) ) {
				$response['wrongProducts'] = $wrong_products;
				$response['countProducts'] = $all_products;
			}
			$response['success'] = true;

		} else {
			// translators: error message if products validation fails.
			$response['error'] = __( 'There was an error while validating the products.', 'yith-shippo-shippings-for-woocommerce' );
		}

		wp_send_json( $response );
	}

	/**
	 * Toggle the rule status
	 *
	 * @since  1.0.0
	 */
	public function toggle_rule_status() {
		check_ajax_referer( 'yith_shippo_toggle_rule', 'security' );
		$response =
			array(
				'success' => false,
			);
		if ( isset( $_POST['rule_id'], $_POST['rule_status'] ) ) {
			$rule_id     = intval( wp_unslash( $_POST['rule_id'] ) );
			$rule_status = wc_string_to_bool( wp_unslash( $_POST['rule_status'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$rule        = yith_shippo_get_shipping_rule( $rule_id );
			if ( $rule ) {
				$rule->set_enabled( $rule_status );
				$rule->save();
				$response['success'] = true;
			}
		}
		wp_send_json( $response );
	}


	/**
	 * Find all services by parcel
	 *
	 * @since  1.0.0
	 */
	public function find_service() {
		check_ajax_referer( 'yith_shippo_find_service', 'security' );
		if ( isset( $_POST['rate'] ) ) {
			$rate         = wp_unslash( $_POST['rate'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			$parcel       = $rate['parcel'] ?? false;
			$extra_option = $rate['extra_option'] ?? array();
			$order_id     = sanitize_text_field( wp_unslash( $rate['order_id'] ) );
			$order        = wc_get_order( $order_id );

			$args = array(
				'products_in_shipping' => $rate['products_in_shipping'],
				'order_id'             => $order_id,
				'parcel'               => $parcel,
				'package'              => $rate['package'],
			);

			$parcel_found = YITH_Shippo_Parcels::get_instance()->calculate_items_in_parcel( $args );
			$parcel       = $parcel_found;
			if ( is_array( $parcel ) ) {
				$parcel['signature']   = in_array( 'signature', $extra_option, true ) ? 'yes' : 'no';
				$parcel['insurance']   = in_array( 'insurance', $extra_option, true ) ? 'yes' : 'no';
				$parcel['is_return']   = in_array( 'return_label', $extra_option, true ) ? 'yes' : 'no';
				$parcel['content']     = sanitize_text_field( wp_unslash( $rate['content'] ) );
				$parcel['description'] = sanitize_text_field( wp_unslash( $rate['description'] ) );

				$args = array(
					'order_id'     => $order_id,
					'order_number' => $order->get_order_number(),
					'confirmed'    => false,
					'zone_id'      => yith_shippo_get_shipping_zone_id_by_order( $order ),
				);
				$args = $args + $parcel;

				$shipment = yith_shippo()->request->shipment_manager->create_shipment( $args );
				$this->logger->shipments( 'The shipment is  ' . print_r( $shipment, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
				if ( isset( $shipment['rates'] ) && count( $shipment['rates'] ) > 0 ) {

					$rates = $shipment['rates'];
					usort( $rates, 'yith_shippo_sort_rates' );
					$rates['rates']        = $rates;
					$rates['parcel']       = $parcel;
					$rates['rate_key']     = $rate['rate_key'];
					$rates['currency']     = $order->get_currency();
					$rates['old_selected'] = $rate['service_id'] ?? false;
					ob_start();
					yith_shippo_get_view( '/custom-fields/types/service-popup.php', $rates );
					$template = ob_get_clean();
					wp_send_json_success( $template );
				} else {
					wp_send_json_error( $shipment['error'] );
				}
			} else {
				wp_send_json_error( new WP_Error( 'parcel_error', $parcel ) );
			}
		}
	}

	/**
	 * Update the rate
	 *
	 * @throws WC_Data_Exception The exception.
	 * @since  1.0.0
	 */
	public function update_rate() {
		check_ajax_referer( 'yith_shippo_update_rate', 'security' );

		$update = false;
		if ( isset( $_POST['rate_key'], $_POST['rate'], $_POST['order_id'], $_POST['parcel'] ) ) {
			$order_id           = sanitize_text_field( wp_unslash( $_POST['order_id'] ) );
			$rate_key           = sanitize_text_field( wp_unslash( $_POST['rate_key'] ) );
			$order_shipping     = yith_shippo_get_order_shipping( $order_id );
			$old_rate           = $order_shipping->get_rate( $rate_key );
			$rate               = wp_unslash( $_POST['rate'] );  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$rate['confirmed']  = false;
			$old_rate['rate']   = $rate;
			$old_rate['parcel'] = wp_unslash( $_POST['parcel'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( $order_shipping ) {
				$order_shipping->set_rate( $rate_key, $old_rate );
				$order_shipping->save();
				$update = true;
			}
		}
		wp_send_json(
			array(
				'updated' => $update,
			)
		);
	}

	/**
	 * Create a new shippo shipping in the order
	 *
	 * @since  1.0.0
	 */
	public function create_shipping() {
		check_ajax_referer( 'yith_shippo_create_shipping', 'security' );
		if ( isset( $_POST['order_id'] ) ) {
			$order_id       = sanitize_text_field( wp_unslash( $_POST['order_id'] ) );
			$order          = wc_get_order( $order_id );
			$order_shipping = yith_shippo_get_order_shipping( $order_id );
			$zone_id        = false;
			if ( yith_shippo_support_shipping_zones() ) {
				$zone_id = yith_shippo_get_shipping_zone_id_by_order( $order );
			}
			$owner_address    = yith_shippo_get_owner_site_address( $zone_id );
			$customer_address = yith_shippo_get_customer_address( $order_id );
			$is_international = $owner_address['country'] !== $customer_address['country'];

			if ( $order_shipping instanceof YITH_Shippo_Order_Shipping ) {
				$rates      = $order_shipping->get_rates();
				$rate_index = 0;
				while ( isset( $rates[ 'rate_' . $rate_index ] ) ) {
					$rate_index ++;
				}
				$new_rate_key       = 'rate_' . $rate_index;
				$new_rate           = array();
				$new_rate['rate']   = array(
					'carrier'                   => '',
					'service'                   => '',
					'label'                     => '',
					'service_img_url'           => '',
					'cost'                      => 0,
					'delivery_time_description' => '',
					'is_international'          => $is_international,
					'confirmed'                 => false,
				);
				$new_rate['parcel'] = array();
				$order_shipping->set_rate( $new_rate_key, $new_rate );
				$order_shipping->save();

			} else {
				$postage_options       = get_option( 'yith_shippo_default_postage_options', yith_shippo_get_default_postage_options() );
				$international_service = $postage_options['international_service'];
				$domestic_service      = $postage_options['domestic_service'];

				/* Create a new shipping item */
				$shipping_item = new YITH_Shippo_Order_Shipping();
				$shipping_item->set_props(
					array(
						'method_title' => 'YITH Shippo Shippings',
						'method_id'    => 'yith_shippo',
						'instance_id'  => 0,
						'total'        => 0,
					)
				);

				$metadata = array(
					'created_via'      => 'admin',
					'rates'            => array(),
					'shipping_service' => '',
				);
				$zone_id  = false;
				if ( yith_shippo_support_shipping_zones() ) {
					$zone_id = yith_shippo_get_shipping_zone_id_by_order( $order );
				}

				$parcels = YITH_Shippo_Parcels::get_instance()->calculate_parcels_on_order( $order, $zone_id );
				$args    = array(
					'order_id'  => $order_id,
					'confirmed' => false,
					'zone_id'   => $zone_id,
				);
				foreach ( $parcels as $parcel ) {
					$global_args = $args + $parcel;
					$shipment    = yith_shippo()->request->shipment_manager->create_shipment( $global_args );
					if ( isset( $shipment['rates'] ) && count( $shipment['rates'] ) > 0 ) {
						$rates = $shipment['rates'];

						if ( isset( $rates[ $international_service ] ) && $rates[ $international_service ]['is_international'] ) {
							$rate = $rates[ $international_service ];
						} elseif ( isset( $rates[ $domestic_service ] ) && ! $rates[ $domestic_service ]['is_international'] ) {
							$rate = $rates[ $domestic_service ];
						} else {
							usort( $rates, 'yith_shippo_sort_rates' );
							$rate = current( $rates );
						}
						$metadata['rates'][ 'rate_' . count( $metadata['rates'] ) ] = array(
							'rate'   => $rate,
							'parcel' => $parcel,
						);

						$shipping_item->set_props( $metadata );

						$order->add_item( $shipping_item );
						$order->save();
						$order_shipping = $shipping_item;
					} else {
						wp_send_json_error( $shipment['error'] );
					}
				}
			}
			ob_start();
			$args = array(
				'order'          => $order,
				'order_shipping' => $order_shipping,
				'extra_args'     => YITH_Shippo_Meta_Box_Shipping_Order::get_product_info( $order ),
			);
			yith_shippo_get_view( '/order-metabox/shipping-meta-box.php', $args );
			$template = ob_get_clean();
			wp_send_json_success( $template );
		}
	}

	/**
	 * Delete a shipping
	 *
	 * @since  1.0.0
	 */
	public function delete_shipping() {
		check_ajax_referer( 'yith_shippo_delete_shipping', 'security' );
		$deleted = false;
		if ( isset( $_POST['order_id'], $_POST['rate_key'] ) ) {
			$order_id       = sanitize_text_field( wp_unslash( $_POST['order_id'] ) );
			$rate_key       = sanitize_text_field( wp_unslash( $_POST['rate_key'] ) );
			$order_shipping = yith_shippo_get_order_shipping( $order_id );
			if ( $order_shipping instanceof YITH_Shippo_Order_Shipping ) {
				$order_shipping->remove_key( $rate_key );
				$order_shipping->save();
				$deleted = true;
			}
		}
		wp_send_json(
			array(
				'deleted' => $deleted,
			)
		);
	}

	/**
	 * Confirm the shipping to create the shipment
	 *
	 * @since  1.0.0
	 */
	public function create_shipment() {
		check_ajax_referer( 'yith_shippo_create_shipment', 'security' );

		if ( isset( $_POST['order_id'], $_POST['rate_key'] ) ) {
			$order_id         = sanitize_text_field( wp_unslash( $_POST['order_id'] ) );
			$rate_key         = sanitize_text_field( wp_unslash( $_POST['rate_key'] ) );
			$order_shipping   = yith_shippo_get_order_shipping( $order_id );
			$customer_address = yith_shippo_get_customer_address( $order_id );
			$result           = yith_shippo()->request->address_validation->validate_customer_address( $customer_address );

			if ( $result['is_valid'] && $order_shipping instanceof YITH_Shippo_Order_Shipping ) {
				$order_shipping->set_rate_confirmed( $rate_key, true );
				$order_shipping->save();
				$order = wc_get_order( $order_id );
				$args  = array(
					'rate_key'       => $rate_key,
					'rate'           => $order_shipping->get_rate( $rate_key ),
					'order_shipping' => $order_shipping,
					'currency'       => $order->get_currency(),
				);
				ob_start();
				yith_shippo_get_view( '/order-metabox/html-single-rate-confirmed-view.php', $args );
				$template = ob_get_clean();
				wp_send_json_success( $template );
			} else {
				wp_send_json_error( new WP_Error( 'address_validation', $result['message'] ) );
			}
		}
		wp_send_json_error( new WP_Error( 'address_validation', _x( 'An error occurred while processing the shipment confirmation.', 'Error displayed if the confirm of the shipment is not completed', 'yith-shippo-shippings-for-woocommerce' ) ) );
	}

	/**
	 * Pay the shipping
	 *
	 * @since  1.0.0
	 */
	public function pay_shipping() {
		check_ajax_referer( 'yith_shippo_pay_shipping', 'security' );

		if ( isset( $_POST['order_id'], $_POST['rate_key'] ) ) {
			$order_id       = sanitize_text_field( wp_unslash( $_POST['order_id'] ) );
			$rate_key       = sanitize_text_field( wp_unslash( $_POST['rate_key'] ) );
			$order_shipping = yith_shippo_get_order_shipping( $order_id );
			if ( $order_shipping instanceof YITH_Shippo_Order_Shipping ) {
				$paid = $order_shipping->pay_shipping( $rate_key );
				if ( is_wp_error( $paid ) ) {
					wp_send_json_error( $paid );
				} else {
					$args = array(
						'rate_key'       => $rate_key,
						'rate'           => $order_shipping->get_rate( $rate_key ),
						'order_shipping' => $order_shipping,
					);
					ob_start();
					yith_shippo_get_view( '/order-metabox/html-single-tracking-view.php', $args );
					$template = ob_get_clean();
					wp_send_json_success( $template );
				}
			}
		}
		wp_send_json_error( new WP_Error( 'pay_shipping_error', _x( 'An error occurred while processing the shipping payment.', 'Error displayed if the payment of shipping is not completed', 'yith-shippo-shippings-for-woocommerce' ) ) );
	}

	/**
	 * Update tracking
	 *
	 * @since  1.0.0
	 */
	public function update_tracking() {
		check_ajax_referer( 'yith_shippo_update_tracking', 'security' );
		if ( isset( $_POST['order_id'], $_POST['rate_key'] ) ) {
			$order_id       = sanitize_text_field( wp_unslash( $_POST['order_id'] ) );
			$order_shipping = yith_shippo_get_order_shipping( $order_id );
			$rate_key       = sanitize_text_field( wp_unslash( $_POST['rate_key'] ) );
			$tracking       = $order_shipping->get_tracking( $rate_key );
			$tracking->check_for_updates();
			$args = array(
				'rate_key'       => $rate_key,
				'rate'           => $order_shipping->get_rate( $rate_key ),
				'order_shipping' => $order_shipping,
			);
			ob_start();
			yith_shippo_get_view( '/order-metabox/html-single-tracking-view.php', $args );
			$template = ob_get_clean();
			wp_send_json_success( $template );
		}

	}

	/**
	 * |--------------------------------------------------------------------------
	 * | Parcel methods
	 * |--------------------------------------------------------------------------
	 * |
	 * | Functions for manage the parcel information. The info for each parcel are stored inside the database in a specific table.
	 */

	/**
	 * Enable or disable a parcel
	 *
	 * @since  1.0.0
	 */
	public function enable_parcel() {
		check_ajax_referer( 'yith_shippo_enable_parcel', 'security' );
		if ( isset( $_POST['id'], $_POST['enabled'] ) ) {
			$parcel_id = sanitize_text_field( wp_unslash( $_POST['id'] ) );
			$enabled   = sanitize_text_field( wp_unslash( $_POST['enabled'] ) );
			$parcel    = new YITH_Shippo_Parcel_Box( $parcel_id );
			$parcel->set_enabled( 'true' === $enabled );
			$parcel->save();
			wp_send_json_success();
		}

		wp_send_json_error( 'There was an error during saving the parcel' );
	}

	/**
	 * Delete a parcel
	 *
	 * @since  1.0.0
	 */
	public function delete_parcel() {
		check_ajax_referer( 'yith_shippo_delete_parcel', 'security' );

		if ( isset( $_POST['id'] ) ) {
			$parcel_id = sanitize_text_field( wp_unslash( $_POST['id'] ) );
			$parcel    = new YITH_Shippo_Parcel_Box( $parcel_id );
			$parcel->delete();
			ob_start();
			yith_shippo_get_view( '/custom-fields/types/parcel-list.php' );
			$template = ob_get_clean();
			wp_send_json_success( $template );
		}

		wp_send_json_error( 'There was an error to delete the parcel' );
	}

	/**
	 * Add or edit a parcel
	 *
	 * @since  1.0.0
	 */
	public function save_parcel() {
		check_ajax_referer( 'yith_shippo_save_parcel', 'security' );
		$posted = $_POST;
		if ( isset( $posted['id'] ) ) {
			$parcel_id = sanitize_text_field( wp_unslash( $posted['id'] ) );
			try {
				$new_parcel = false !== strpos( $parcel_id, 'shippo' ) ? new YITH_Shippo_Parcel_Box() : new YITH_Shippo_Parcel_Box( $parcel_id );
				if ( isset( $posted['yith_shippo_parcel'][ $parcel_id ] ) ) {
					$parcel                   = $posted['yith_shippo_parcel'][ $parcel_id ]; //phpcs:ignore
					$parcel['distance_unit']  = get_option( 'woocommerce_dimension_unit' );
					$parcel['weight_unit']    = get_option( 'woocommerce_weight_unit' );
					$parcel['shipping_zones'] = $parcel['shipping_zones'] ?? array();

					$new_parcel->set_name( $parcel['name'] );
					$new_parcel->set_shipping_zones( $parcel['shipping_zones'] );
					$new_parcel->set_type( $parcel['type'] );

					if ( 'parcel' !== $parcel['type'] ) {
						$shippo_parcel_boxes = yith_shippo()->request->get_currier_parcel_template()->get_parcel_templates();
						$key                 = array_search( $parcel['type'], array_column( $shippo_parcel_boxes, 'token' ), true );
						if ( isset( $shippo_parcel_boxes[ $key ] ) ) {
							$shippo_box              = $shippo_parcel_boxes[ $key ];
							$parcel['width']         = $shippo_box['width'];
							$parcel['height']        = $shippo_box['height'];
							$parcel['length']        = $shippo_box['length'];
							$parcel['distance_unit'] = $shippo_box['distance_unit'];
						}
					}
					$new_parcel->set_weight( $parcel['weight'] );
					$new_parcel->set_width( $parcel['width'] );
					$new_parcel->set_length( $parcel['length'] );
					$new_parcel->set_height( $parcel['height'] );
					$new_parcel->set_inner_padding( $parcel['inner_padding'] );
					$new_parcel->set_max_weight( $parcel['max_weight'] );
					$new_parcel->set_weight_unit( $parcel['weight_unit'] );
					$new_parcel->set_distance_unit( $parcel['distance_unit'] );

					$new_parcel->save();
				}

				ob_start();
				yith_shippo_get_view( '/custom-fields/types/parcel-list.php' );
				$template = ob_get_clean();
				wp_send_json_success( $template );

			} catch ( Exception $e ) {
				$this->logger->shipments( 'Shippo Parcels error during new parcel creation ' . $e->getMessage() );
				wp_send_json_error( 'there was an error adding a parcel' );
			}
		}
		wp_send_json_error( 'there was an error during adding a parcel' );

	}


	/**
	 * |--------------------------------------------------------------------------
	 * | Sender info methods
	 * |--------------------------------------------------------------------------
	 * |
	 * | Functions for manage the sender information. The main information is stored inside the 'yith_shippo_sender_info' option.
	 * | If shipping zones are enabled the option contains an array of sender information with assigned one or more shipping zones.
	 * | A default sender info is mandatory and it will used if the current shipping zone doesn't match with any sender information.
	 */

	/**
	 * Validate sender info from Ajax call, and save the sender info if it is valid.
	 *
	 * @since  1.0.0
	 */
	public function validate_sender_info() {
		check_ajax_referer( 'yith_shippo_sender_info', 'security' );
		$posted = $_POST;
		if ( ! yith_shippo()->request->validate_token() ) {
			wp_send_json_error( __( 'YITH Shippo is not connected to your account.', 'yith-shippo-shippings-for-woocommerce' ) );
		}
		if ( isset( $posted['yith-shippo-sender-info-key'], $posted['yith-shippo-sender-info'] ) ) {
			$main_option = (array) get_option( 'yith-shippo-sender-info' );
			$key         = sanitize_text_field( wp_unslash( $posted['yith-shippo-sender-info-key'] ) );
			$sender_info = $posted['yith-shippo-sender-info'];

			if ( isset( $sender_info[ $key ] ) ) {
				$sender = $sender_info[ $key ];

				if ( isset( $sender['use_wc_address'] ) ) {
					$wc_address = yith_shippo_get_woocommerce_store_address_fields();
					$sender     = array_merge( $sender, $wc_address );
				}

				$validate_address            = $sender;
				$validate_address['street1'] = sanitize_text_field( wp_unslash( $sender['address_1'] ) );
				$validate_address['street2'] = sanitize_text_field( wp_unslash( $sender['address_2'] ) );
				$validate_address['zip']     = sanitize_text_field( wp_unslash( $sender['zip_code'] ) );

				unset( $validate_address['address_1'], $validate_address['address_2'], $validate_address['zip_code'] );
				$state_country               = sanitize_text_field( wp_unslash( $validate_address['country_state'] ) );
				$state_country               = explode( ':', $state_country );
				$validate_address['country'] = 2 === count( $state_country ) ? $state_country[0] : $state_country[0];
				$validate_address['state']   = 2 === count( $state_country ) ? $state_country[1] : '';

				$result = yith_shippo()->request->address_validation->validate_owner_address( $validate_address );

				if ( $result['is_valid'] ) {
					$enabled_shipping_zone = yith_shippo_support_shipping_zones();
					$sender['object_id']   = $result['object_id'];
					$sender['default']     = $enabled_shipping_zone ? ( $sender['default'] ?? 0 ) : 1;

					if ( $sender['default'] ) {
						foreach ( $main_option as $item_key => $item ) {
							$main_option[ $item_key ]['default'] = 0;
						}
					}

					$main_option[ $key ] = $sender;

					update_option( 'yith-shippo-sender-info', $main_option );

					if ( $enabled_shipping_zone ) {
						ob_start();
						yith_shippo_get_view( '/custom-fields/types/sender-info.php' );
						$data = ob_get_clean();
					} else {
						$data = __( 'Sender info updated', 'yith-shippo-shippings-for-woocommerce' );
					}

					wp_send_json_success( $data );
				} else {
					wp_send_json_error( __( 'Address validation error:', 'yith-shippo-shippings-for-woocommerce' ) . ' ' . $result['message'] );
				}
			}
		}

		wp_send_json_error( __( 'There was an error while validating the sender info.', 'yith-shippo-shippings-for-woocommerce' ) );
	}


	/**
	 * Delete sender info
	 *
	 * @since  1.0.0
	 */
	public function delete_sender_info() {
		check_ajax_referer( 'yith_shippo_delete_sender_info', 'security' );

		if ( isset( $_POST['id'] ) ) {
			$id          = sanitize_text_field( wp_unslash( $_POST['id'] ) );
			$main_option = (array) get_option( 'yith-shippo-sender-info' );
			$sender_info = $main_option[ $id ] ?? false;
			if ( $sender_info && isset( $sender_info['default'] ) && ! $sender_info['default'] ) {
				unset( $main_option[ $id ] );
				update_option( 'yith-shippo-sender-info', $main_option );
				ob_start();
				yith_shippo_get_view( '/custom-fields/types/sender-info.php' );
				$template = ob_get_clean();
				wp_send_json_success( $template );
			}
		}

		wp_send_json_error( 'There was an error to delete the sender information' );
	}

	/**
	 * Set default sender info
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function set_default_sender_info() {
		check_ajax_referer( 'yith_shippo_set_default_sender_info', 'security' );

		if ( isset( $_POST['id'] ) ) {
			$id          = sanitize_text_field( wp_unslash( $_POST['id'] ) );
			$main_option = (array) get_option( 'yith-shippo-sender-info' );
			if ( isset( $main_option[ $id ] ) ) {
				foreach ( $main_option as $item_key => $item ) {
					$main_option[ $item_key ]['default'] = $item_key === $id;
				}
				update_option( 'yith-shippo-sender-info', $main_option );

				ob_start();
				yith_shippo_get_view( '/custom-fields/types/sender-info.php' );
				$template = ob_get_clean();
				wp_send_json_success( $template );
			}
		}

		wp_send_json_error( 'There was an error to delete the sender information' );
	}
}
