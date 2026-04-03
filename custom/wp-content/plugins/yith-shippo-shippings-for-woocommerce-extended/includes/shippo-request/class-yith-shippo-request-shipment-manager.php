<?php
/**
 * Class to manage the requests API with Shippo Service
 *
 * @class   YITH_Shippo_Request
 * @package YITH/Shippo/Request
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class YITH_Shippo_Request_Shipment_Manager
 */
class YITH_Shippo_Request_Shipment_Manager {

	use YITH_Shippo_Trait_Singleton, YITH_Shippo_Trait_Cached_Request;

	/**
	 * Create a shipment object for specific parcels
	 *
	 * @param array $global_args The arg array.
	 *
	 * @since 1.0.0
	 */
	public function create_shipment( $global_args ) {
		$shipment_args = $this->prepare_shipment_args( $global_args );
		$shipment_obj  = $this->get_transient( 'shipment', $shipment_args );

		if ( false === $shipment_obj ) {
			$shipment_cached_args = $this->get_cached_args( $shipment_args );
			$shipment_obj         = array(
				'id'    => '',
				'rates' => array(),
				'error' => '',
			);
			$error                = new WP_Error();

			$this->logger->shipments( 'Require a shipment ' . print_r( $shipment_cached_args, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			try {
				$shipment = Shippo_Shipment::create( $shipment_cached_args );
				$this->logger->shipments( 'Call for shipments ' . print_r( $shipment, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
				if ( $shipment->messages ) {
					$error_messages = '';
					foreach ( $shipment->messages as $message ) {
						$message = json_decode( $message, true );
						if ( $message ) {
							$error_messages .= isset( $message['source'], $message['text'] ) ? $message['text'] : implode( ' ', $message );
						} else {
							$error_messages .= $message;
						}
					}
					$this->logger->shipments( 'Shipment error:  ' . $error_messages );
					$error->add( 'shipment_error', $error_messages );

				}
			} catch ( Shippo_InvalidRequestError $e ) {
				$this->logger->shipments( 'Error on create shipment : ' . $e->getMessage() );

				$messages       = $e->getJsonBody();
				$error_messages = '';
				if ( isset( $messages['customs_declaration'] ) ) {
					foreach ( $messages['customs_declaration'] as $declaration ) {
						foreach ( $declaration['items'] as $items ) {
							foreach ( $items as $item ) {

								$error_messages .= implode( ',', $item ) . '<br>';

							}
						}
					}
				} else {
					$error_messages = $e->getMessage();
				}
				$error->add( 'shipment_error', $error_messages );

				return array(
					'error' => $error,
				);
			}
			$service_list = yith_shippo_get_services_list();
			$parcel       = isset( $shipment->parcels ) ? current( $shipment->parcels ) : array();

			if ( isset( $parcel['object_id'] ) && ( isset( $parcel['object_state'] ) && 'valid' === strtolower( $parcel['object_state'] ) ) ) {
				$this->set_transient( 'parcel', $parcel['object_id'], $shipment_args['parcels'] );
			} else {
				$this->delete_transient( 'parcel', $shipment_args['parcels'] );
			}

			$customs_declaration = isset( $shipment->customs_declaration ) ? $shipment->customs_declaration : false;
			$is_international    = false;
			if ( $customs_declaration && 'valid' === strtolower( $customs_declaration['object_state'] ) && isset( $customs_declaration['object_id'] ) ) {
				$this->set_transient( 'customs_declaration', $customs_declaration['object_id'], 30 * MINUTE_IN_SECONDS, $shipment_args['customs_declaration'] );
				$is_international = true;
			}
			if ( isset( $global_args['confirmed'] ) ) {
				$confirmed = $global_args['confirmed'];
			} else {
				$default_opt = get_option( 'yith_shippo_options_to_request_shipping_label' );
				$confirmed   = 'no' === $default_opt;
			}
			if ( $shipment->rates ) {
				$rates = array();
				foreach ( $shipment->rates as $rate ) {
					$new_rate       = array();
					$new_rate['id'] = $rate['object_id'];
					if ( isset( $service_list[ $rate['servicelevel']['token'] ] ) ) {
						$new_rate['label'] = $service_list[ $rate['servicelevel']['token'] ];
					} else {
						$new_rate['label'] = $rate['provider'] . ' ' . $rate['servicelevel']['name'];
					}
					$new_rate['service']          = $rate['servicelevel']['token'];
					$new_rate['is_international'] = $is_international;
					$new_rate['confirmed']        = $confirmed;
					if ( isset( $rate['currency_local'] ) && $rate['currency_local'] === $shipment_args['currency'] ) {
						$new_rate['cost'] = $rate['amount_local'];
					} else {
						$new_rate['cost'] = $rate['amount'];
					}
					$new_rate['carrier'] = $rate['provider'];

					$days = 0;
					if ( ! empty( $rate['days'] ) ) {
						$days = $rate['days'];
					} else {
						if ( ! empty( $rate['estimated_days'] ) ) {
							$days = $rate['estimated_days'];
						}
					}
					$new_rate['delivery_time_description'] = '';
					$new_rate['delivery_days']             = $days;

					if ( ! empty( $days ) ) {
						/* translators: %s is the amount of days */
						$new_rate['delivery_time_description'] = sprintf( _n( 'Estimated delivery in %s day', 'Estimated delivery in %s days', $days, 'yith-shippo-shippings-for-woocommerce' ), $days );
					} else {
						if ( ! empty( $rate['duration_terms'] ) ) {
							$new_rate['delivery_time_description'] = $rate['duration_terms'];
						}
					}
					$new_rate['service_img_url'] = '';
					if ( ! empty( $rate['provider_image_200'] ) ) {
						$new_rate['service_img_url'] = $rate['provider_image_200'];
					} elseif ( ! empty( $rate['provider_image_75'] ) ) {
						$new_rate['service_img_url'] = $rate['provider_image_75'];
					}
					$rates[ $new_rate['service'] ] = $new_rate;
				}

				$shipment_obj['rates'] = $rates;

			}
			$shipment_obj['id']    = $shipment->object_id;
			$shipment_obj['error'] = $error;
			if ( ! empty( $shipment_obj['rates'] ) ) {
				$this->set_transient( 'shipment', $shipment_obj, 30 * MINUTE_IN_SECONDS, $shipment_args );
			}
		} else {
			$this->logger->shipments( 'Shipment found from the cache :  use it ' );
			$this->logger->shipments( print_r( $shipment_obj, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		}

		return $shipment_obj;
	}

	/**
	 * Return the shipment args
	 *
	 * @param array $args The args to build.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function prepare_shipment_args( $args ) {
		$this->logger->shipments( 'Detected zone ' . $args['zone_id'] );
		$shipment_args = array(
			'address_from' => yith_shippo_get_owner_site_address( $args['zone_id'] ),
			'async'        => false,
			'currency'     => isset( $args['currency'] ) ? $args['currency'] : get_woocommerce_currency(),
		);
		$this->logger->shipments( 'Found this owner address ' . print_r( $shipment_args['address_from'], true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$the_order_id = isset( $args['order_id'] ) ? $args['order_id'] : false;
		$default_opts = get_option( 'yith_shippo_default_postage_options', yith_shippo_get_default_postage_options() );

		$shipment_args['address_to'] = yith_shippo_get_customer_address( $the_order_id );

		if ( 'yes' === $args['signature'] ) {
			$shipment_args['extra']['signature_confirmation'] = 'STANDARD';
		}

		if ( 'yes' === $args['insurance'] && ! empty( $args['amount'] ) ) {
			$shipment_args['extra']['insurance'] = array(
				'amount'   => $args['amount'],
				'currency' => get_woocommerce_currency(),
			);
		}

		if ( isset( $args['is_return'] ) && 'yes' === $args['is_return'] ) {
			$shipment_args['extra']['is_return'] = true;
		}

		if ( $the_order_id ) {
			$shipment_args['extra']['reference_1'] = $the_order_id;
			$shipment_args['metadata']             = sprintf( 'Order #%s', $the_order_id );
		}
		if ( isset( $args['order_number'] ) ) {
			$shipment_args['extra']['reference_2'] = $args['order_number'];
		}

		if ( 'no' === get_option( 'yith-shippo-validate-shipping-adress', 'yes' ) ) {
			$shipment_args['extra']['bypass_address_validation'] = true;
		}
		// If is a international shipping add the customs declarations.
		if ( $shipment_args['address_from']['country'] !== $shipment_args['address_to']['country'] ) {
			$custom_info = array(
				'certify'             => true,
				'non_delivery_option' => 'RETURN',
			);

			if ( ! empty( $shipment_args['address_from']['name'] ) ) {
				$certify_signer = $shipment_args['address_from']['name'];
			} elseif ( ! empty( $shipment_args['address_from']['company'] ) ) {
				$certify_signer = $shipment_args['address_from']['company'];
			} else {
				$certify_signer = 'Shipper';
			}
			$custom_info['certify_signer'] = $certify_signer;

			if ( $the_order_id ) {
				$custom_info['invoice'] = $the_order_id;
			}

			if ( ! empty( $args['content'] ) ) {
				$custom_info['contents_type'] = strtoupper( $args['content'] );
			} else {
				$custom_info['contents_type'] = strtoupper( $default_opts['content'] );
			}

			if ( ! empty( $args['description'] ) ) {
				$custom_info['contents_explanation'] = $args['description'];
			} else {
				$custom_info['contents_explanation'] = $default_opts['shipment_description'];
			}

			$args['default_tariff_number']        = $default_opts['tariff_number'];
			$args['default_country_origin']       = $default_opts['country_origin'];
			$args['currency']                     = $shipment_args['currency'];
			$custom_info['items']                 = $this->build_custom_items( $args );
			$shipment_args['customs_declaration'] = $custom_info;
		}

		$shipment_args['parcels'] = $this->prepare_parcel_args( $args );

		return $shipment_args;
	}

	/**
	 * Add the items in the custom declarations
	 *
	 * @param array $args The args.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	protected function build_custom_items( $args ) {
		$items_in_parcel = isset( $args['filled'] ) ? $args['filled'] : array();
		$custom_items    = array();
		foreach ( $items_in_parcel as $item ) {
			if ( empty( $item['product_name'] ) || empty( $item['quantity'] ) || empty( $item['amount'] || empty( $item['weight'] ) ) ) {
				continue;
			}
			$net_weight  = round( $item['quantity'] * $item['weight'], 2 );
			$single_item = array();

			$single_item['description']    = esc_html( $item['product_name'] );
			$single_item['quantity']       = $item['quantity'];
			$single_item['value_amount']   = round( $item['quantity'] * $item['amount'], 2 );
			$single_item['net_weight']     = $net_weight > 0 ? $net_weight : 0.01;
			$single_item['value_currency'] = $args['currency'];
			$single_item['origin_country'] = ! empty( $item['country_origin'] ) ? $item['country_origin'] : $args['default_country_origin'];
			$single_item['tariff_number']  = ! empty( $item['tariff_number'] ) ? $item['tariff_number'] : $args['default_tariff_number'];
			$single_item['mass_unit']      = 'lbs' === $args['mass_unit'] ? 'lb' : $args['mass_unit'];

			$custom_items[] = $single_item;
		}

		return $custom_items;
	}

	/**
	 * Return the cached shipment args
	 *
	 * @param array $shipment_args The shipment args.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	protected function get_cached_args( $shipment_args ) {
		$shipment_args['parcels'] = $this->get_parcel_cached( $shipment_args['parcels'] );
		$stored_address_from      = yith_shippo()->request->address_validation->get_cached_owner_address( $shipment_args['address_from'] );
		$stored_address_to        = yith_shippo()->request->address_validation->get_cached_customer_address( $shipment_args['address_to'] );

		// If is a international shipping add the customs declarations.
		if ( $shipment_args['address_from']['country'] !== $shipment_args['address_to']['country'] ) {
			$shipment_args['customs_declaration'] = $this->get_customs_declaration_cached( $shipment_args['customs_declaration'] );

		}
		if ( ! empty( $stored_address_from ) ) {
			$shipment_args['address_from'] = $stored_address_from;
		}
		if ( ! empty( $stored_address_to ) ) {
			$shipment_args['address_to'] = $stored_address_to;
		}

		return $shipment_args;
	}

	/**
	 * Return the rates
	 *
	 * @param array $parcels The parcels.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_rates( $parcels ) {
		$ship = $this->create_shipment( $parcels );

		return isset( $ship['rates'] ) ? $ship['rates'] : array();
	}

	/**
	 * Get the right parcel args
	 *
	 * @param array $parcel The parcel.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	protected function prepare_parcel_args( $parcel ) {

		$new_parcel = array(
			'length'        => $parcel['length'],
			'width'         => $parcel['width'],
			'height'        => $parcel['height'],
			'distance_unit' => isset( $parcel['distance_unit'] ) ? $parcel['distance_unit'] : get_option( 'woocommerce_dimension_unit' ),
			'mass_unit'     => isset( $parcel['mass_unit'] ) ? $parcel['mass_unit'] : get_option( 'woocommerce_weight_unit' ),
			'weight'        => $parcel['weight'],
		);

		return $new_parcel;
	}

	/**
	 * Get the parcels array
	 *
	 * @param array $parcel The parcel.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_parcel_cached( $parcel ) {
		$value = $this->get_transient( 'parcel', $parcel );

		if ( false !== $value && '' !== $value ) {
			$new_parcel = array(
				$value,
			);
		} else {
			$new_parcel = array( $parcel );
		}

		return $new_parcel;
	}

	/**
	 * Get the customs declaration in cache
	 *
	 * @param array $customs_declaration The args.
	 *
	 * @return string|array
	 * @since 1.0.0
	 */
	public function get_customs_declaration_cached( $customs_declaration ) {
		$value = $this->get_transient( 'customs_declaration', $customs_declaration );
		if ( empty( $value ) ) {
			$value = $customs_declaration;
		}

		return $value;
	}
}
