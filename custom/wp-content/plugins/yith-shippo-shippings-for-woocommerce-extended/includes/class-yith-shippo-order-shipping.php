<?php
/**
 * This class manage the Order item Shipping
 *
 * @package YITH\Shippo
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_Shippo_Abstract_Object', false ) ) {
	include_once YITH_SHIPPO_INC . '/abstracts/class-yith-shippo-abstract-object.php';
}

/**
 * The class
 */
class YITH_Shippo_Order_Shipping extends WC_Order_Item_Shipping {

	/**
	 * Order Data array. This is the core order data exposed in APIs.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $extra_data = array(
		'method_title' => '',
		'method_id'    => '',
		'instance_id'  => '',
		'total'        => 0,
		'total_tax'    => 0,
		'taxes'        => array(
			'total' => array(),
		),
		'rates'        => array(),
		'transactions' => array(),
		'trackings'    => array(),
		'created_via'  => '',
	);

	/**
	 * Constructor.
	 *
	 * @param int|object|array $item ID to load from the DB, or WC_Order_Item object.
	 */
	public function __construct( $item = 0 ) {
		parent::__construct( $item );

		if ( $item instanceof WC_Order_Item ) {
			$this->set_id( $item->get_id() );
		} elseif ( is_numeric( $item ) && $item > 0 ) {
			$this->set_id( $item );
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = WC_Data_Store::load( 'shippo_order_shipping' );
		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}

	}

	/**
	 * Set transactions.
	 *
	 * @param array $value Value to set.
	 *
	 * @since 1.0.0
	 */
	public function set_transactions( $value ) {
		$this->set_prop( 'transactions', array_filter( (array) $value ) );
	}

	/**
	 * Set rates.
	 *
	 * @param array $rates Value to set.
	 *
	 * @since 1.0.0
	 */
	public function set_rates( $rates ) {
		$this->set_prop( 'rates', $rates );
	}

	/**
	 * Set rate.
	 *
	 * @param string $key Key of rate.
	 * @param array  $rate Value to set.
	 *
	 * @since 1.0.0
	 */
	public function set_rate( $key, $rate ) {
		$rates         = $this->get_rates();
		$rates[ $key ] = $rate;
		$this->set_rates( $rates );
	}

	/**
	 * Set the confirmed status
	 *
	 * @param string $key Rate key.
	 * @param bool   $confirmed Is rate confirmed or not.
	 *
	 * @author YITH <plugins@yithemes.com>
	 * @since 1.0.0
	 */
	public function set_rate_confirmed( $key, $confirmed ) {
		$rate = $this->get_rate( $key );
		if ( $rate ) {
			$rate['rate']['confirmed'] = $confirmed;
			$this->set_rate( $key, $rate );
		}
	}

	/**
	 * Set single transaction.
	 *
	 * @param string $key Key of transactions.
	 * @param array  $transaction Value to set.
	 *
	 * @since 1.0.0
	 */
	public function set_transaction( $key, $transaction ) {
		$transactions         = (array) $this->get_transactions();
		$transactions[ $key ] = $transaction;

		$this->set_transactions( $transactions );
	}


	/**
	 * Set created_via.
	 *
	 * @param bool $value Value to set.
	 *
	 * @since 1.0.0
	 */
	public function set_created_via( $value ) {
		$this->set_prop( 'created_via', $value );
	}

	/**
	 * Get order transactions.
	 *
	 * @param string $context View or edit context.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_rates( $context = 'view' ) {
		return $this->get_prop( 'rates', $context );
	}

	/**
	 * Get order single rate.
	 *
	 * @param string $key Key of rate.
	 * @param string $context View or edit context.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_rate( $key, $context = 'view' ) {
		$rates = $this->get_rates( $context );

		return $rates[ $key ] ?? array();
	}

	/**
	 * Get order transactions.
	 *
	 * @param string $context View or edit context.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_transactions( $context = 'view' ) {
		return (array) $this->get_prop( 'transactions', $context );
	}


	/**
	 * Get single transaction.
	 *
	 * @param string $key Key of transaction.
	 * @param string $context View or edit context.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_transaction( $key, $context = 'view' ) {
		$transaction = $this->get_transactions( $context );

		return $transaction[ $key ] ?? array();
	}


	/**
	 * Return the tracking
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_trackings() {
		return yith_shippo_get_trackings(
			array(
				'order_id' => $this->get_order_id(),
			)
		);
	}

	/**
	 * Return the tracking object
	 *
	 * @param string $key Row key to get the transaction.
	 *
	 * @return YITH_Shippo_Tracking|bool
	 * @since 1.0.0
	 */
	public function get_tracking( $key ) {
		$trackings = $this->get_trackings();
		if ( $trackings ) {
			foreach ( $trackings as $tracking ) {
				if ( $tracking->get_rate_key() === $key ) {
					return $tracking;
				}
			}
		}

		return false;
	}

	/**
	 * Get order transactions.
	 *
	 * @param string $context View or edit context.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function get_created_via( $context = 'view' ) {
		return $this->get_prop( 'created_via', $context );
	}

	/**
	 * Is this rate is confirmed
	 *
	 * @param string $rate_key The rate key to check.
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public function is_rate_confirmed( $rate_key ) {
		$rate = $this->get_rate( $rate_key );

		return isset( $rate['rate']['confirmed'] ) && yith_plugin_fw_is_true( $rate['rate']['confirmed'] );
	}

	/**
	 * Check if a rate has been paid or not
	 *
	 * @param string $rate_key The rate key.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function is_rate_paid( $rate_key ) {
		$transaction = $this->get_transaction( $rate_key );

		return ! empty( $transaction );
	}

	/**
	 * Check if a rate is international
	 *
	 * @param string $rate_key The rate key.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function is_rate_international( $rate_key ) {
		$rate = $this->get_rate( $rate_key );

		return isset( $rate['rate']['is_international'] ) && yith_plugin_fw_is_true( $rate['rate']['is_international'] );
	}

	/**
	 * Get the parcel for this rate
	 *
	 * @param array $rate The specific rate.
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public function get_parcel_in_rate( $rate ) {
		return isset( $rate['parcel'] ) ? $rate['parcel'] : array();
	}

	/**
	 * Remove rate
	 *
	 * @param string $key Key to remove.
	 *
	 * @since 1.0.0
	 */
	public function remove_rate( $key ) {
		$rates = $this->get_rates();
		unset( $rates[ $key ] );
		$this->set_rates( $rates );
	}

	/**
	 * Remove transaction
	 *
	 * @param string $key Key to remove.
	 *
	 * @since 1.0.0
	 */
	public function remove_transaction( $key ) {
		$transactions = $this->get_transactions();
		unset( $transactions[ $key ] );
		$this->set_transactions( $transactions );
	}

	/**
	 * Remove tracking
	 *
	 * @param string $key Key to remove.
	 *
	 * @since 1.0.0
	 */
	public function remove_tracking( $key ) {
		$trackings = yith_shippo_get_trackings(
			array(
				'order_id' => $this->get_order_id(),
				'rate_key' => $key,
			)
		);

		if ( $trackings ) {
			foreach ( $trackings as $tracking ) {
				$tracking->delete();
			}
		}
	}


	/**
	 * Remove key
	 *
	 * @param string $key Key to remove.
	 *
	 * @since 1.0.0
	 */
	public function remove_key( $key ) {
		$this->remove_rate( $key );
		$this->remove_transaction( $key );
		$this->remove_tracking( $key );
	}

	/**
	 * Pay the shipping rate
	 *
	 * @param string $key Key of rate.
	 *
	 * @return void|WP_Error
	 */
	public function pay_shipping( $key ) {
		$rate = $this->get_rate( $key );
		if ( $rate && isset( $rate['rate']['id'] ) && $this->is_rate_confirmed( $key ) ) {
			$meta       = array(
				'wc_order' => $this->get_order_id(),
				'rate_key' => $key,
			);
			$label_rate = yith_shippo()->request->get_transaction()->create_label_rate( $rate['rate']['id'], http_build_query( $meta ) );

			if ( ! is_wp_error( $label_rate ) ) {
				$transaction = array(
					'object_id'              => $label_rate['object_id'],
					'test'                   => $label_rate['test'],
					'rate'                   => $label_rate['rate'],
					'tracking_number'        => $label_rate['tracking_number'],
					'tracking_status'        => $label_rate['tracking_status'],
					'eta'                    => $label_rate['eta'],
					'tracking_url_provider'  => $label_rate['tracking_url_provider'],
					'label_url'              => $label_rate['label_url'],
					'commercial_invoice_url' => $label_rate['commercial_invoice_url'],
					'metadata'               => $label_rate['metadata'],
					'parcel'                 => $label_rate['parcel'],
					'qr_code_url'            => $label_rate['qr_code_url'],
				);

				if ( $transaction['test'] ) {
					$carrier         = 'shippo';
					$tracking_number = 'SHIPPO_' . $transaction['tracking_status'];
				} else {
					$carrier         = $rate['rate']['carrier'];
					$tracking_number = $transaction['tracking_number'];
				}

				$transaction['carrier']            = $carrier;

				$transaction['webhook_registered'] = yith_shippo()->request->get_tracking()->register_webhook( $carrier, $tracking_number, $transaction['metadata'] );

				$local_label_url = $this->save_label_url( $transaction );

				if ( ! empty( $local_label_url ) ) {
					$transaction['local_label'] = $local_label_url;
				}

				$tracking_obj = new YITH_Shippo_Tracking();
				$tracking_obj->set_tracking_number( $tracking_number );
				$tracking_obj->set_order_id( $this->get_order_id() );
				$tracking_obj->set_carrier( $carrier );
				$tracking_obj->set_rate_key( $key );
				$tracking_obj->set_status( $transaction['tracking_status'] );
				$tracking_obj->set_info( array( 'test' => $transaction['test'] ) );
				$tracking_obj->save();
				$transaction['tracking_id'] = $tracking_obj->get_id();
				$this->set_transaction( $key, $transaction );
				$this->save();
				$tracking_obj->check_for_updates();

				/**
				 * DO_ACTION:yith_shippo_paid_shipping
				 *
				 * This hook is triggered after that the shipping as paid and the transaction has been created.
				 *
				 * @param array                      $transaction Transaction created.
				 * @param int                        $order_id Order id.
				 * @param string                     $key Current key of rate.
				 * @param YITH_Shippo_Order_Shipping $this Current object.
				 */
				do_action( 'yith_shippo_paid_shipping', $transaction, $this->get_order_id(), $key, $this );

				return $tracking_number;
			} else {
				return $label_rate;
			}
		}
	}


	/**
	 * Save Label URL in the uploads folder.
	 *
	 * @param array $transaction Transaction array.
	 *
	 * @return string
	 */
	public function save_label_url( $transaction ) {
		$label_to_save = '';
		if ( $transaction && isset( $transaction['object_id'], $transaction['label_url'] ) ) {

			$object_id = $transaction['object_id'];
			$label_url = $transaction['label_url'];

			if ( ! empty( $label_url ) && ! empty( $object_id ) ) {

				$folder_name = 'labels';
				$year        = gmdate( 'Y' );
				$month       = gmdate( 'm' );
				$folder_path = YITH_SHIPPO_DOCUMENT_SAVE_DIR . $folder_name . '/' . $year . '/' . $month . '/';
				if ( ! file_exists( $folder_path ) ) {
					mkdir( $folder_path, 0755, true );
				}

				$file_type     = yith_shippo_get_label_type( $label_url );
				$label_to_save = $year . '/' . $month . '/' . $object_id . '.' . $file_type;

				$local_label_url = $folder_path . $object_id . '.' . $file_type;

				if ( file_exists( $local_label_url ) ) {
					unlink( $local_label_url );
				}

				$content = wp_remote_get( $label_url );
				file_put_contents( $local_label_url, $content['body'] ); // phpcs:ignore
			}
		}

		return $label_to_save;
	}

}


