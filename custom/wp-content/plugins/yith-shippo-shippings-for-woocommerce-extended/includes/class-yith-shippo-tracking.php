<?php
/**
 * This class manage the object tracking
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
class YITH_Shippo_Tracking extends YITH_Shippo_Abstract_Object {
	/**
	 * Stores meta in cache for future reads.
	 *
	 * A group must be set to to enable caching.
	 *
	 * @var string
	 */
	protected $cache_group = 'shippo_tracking';

	/**
	 * The construct
	 *
	 * @param YITH_Shippo_Tracking|int $tracking The tracking.
	 *
	 * @throws Exception Throws an Exception.
	 */
	public function __construct( $tracking = 0 ) {
		$this->data = array(
			'tracking_number' => '',
			'order_id'        => 0,
			'rate_key'        => '',
			'status'          => '',
			'status_date'     => '',
			'carrier'         => '',
			'info'            => array(),
		);

		$this->object_type = 'tracking';
		parent::__construct( $tracking );

		if ( is_numeric( $tracking ) && $tracking > 0 ) {
			$this->set_id( $tracking );
		} elseif ( $tracking instanceof self ) {
			$this->set_id( $tracking->get_id() );
		} else {
			$this->set_object_read();
		}

		$this->data_store = WC_Data_Store::load( 'shippo_tracking' );

		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/* === GETTERS === */
	/**
	 * Return the shippo tracking number test
	 *
	 * @param string $status Current Status.
	 *
	 * @return string
	 */
	public static function get_test_tracking_number( $status ) {
		$tracking_numbers = array(
			'PRE_TRANSIT' => 'SHIPPO_TRANSIT',
			'TRANSIT'     => 'SHIPPO_FAILURE',
			'DELIVERED'   => 'SHIPPO_DELIVERED',
			'UNKNOWN'     => 'SHIPPO_TRANSIT',
			'FAILURE'     => 'SHIPPO_DELIVERED',
		);

		return $tracking_numbers[ $status ] ?? 'SHIPPO_FAILURE';
	}

	/**
	 * Return tracking code property for current tracking
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return string Tracking code property.
	 */
	public function get_tracking_number( $context = 'view' ) {
		return $this->get_prop( 'tracking_number', $context );
	}

	/**
	 * Return the order id of tracking
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return int Order id property.
	 */
	public function get_order_id( $context = 'view' ) {
		return (int) $this->get_prop( 'order_id', $context );
	}

	/**
	 * Return rate key property for current tracking
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return string Rate key property.
	 */
	public function get_rate_key( $context = 'view' ) {
		return $this->get_prop( 'rate_key', $context );
	}

	/**
	 * Return status property for current tracking
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return string status property.
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
	}

	/**
	 * Return carrier property for current tracking
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return string carrier property.
	 */
	public function get_carrier( $context = 'view' ) {
		return $this->get_prop( 'carrier', $context );
	}

	/**
	 * Return status date property for current tracking
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return string status date property.
	 */
	public function get_status_date( $context = 'view' ) {
		return $this->get_prop( 'status_date', $context );
	}

	/**
	 * Return info property for current tracking
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return array info property.
	 */
	public function get_info( $context = 'view' ) {
		return maybe_unserialize( $this->get_prop( 'info', $context ) );
	}


	/* === SETTERS === */

	/**
	 * Set tracking code
	 *
	 * @param string $tracking_number Tracking identification code.
	 */
	public function set_tracking_number( $tracking_number ) {
		$this->set_prop( 'tracking_number', $tracking_number );
	}

	/**
	 * Set order_id
	 *
	 * @param int $order_id Status of tracking.
	 */
	public function set_order_id( $order_id ) {
		$this->set_prop( 'order_id', (int) $order_id );
	}

	/**
	 * Set transaction rate_key
	 *
	 * @param string $rate_key Rate key of transaction.
	 */
	public function set_rate_key( $rate_key ) {
		$this->set_prop( 'rate_key', $rate_key );
	}

	/**
	 * Set transaction carrier
	 *
	 * @param string $carrier Carrier.
	 */
	public function set_carrier( $carrier ) {
		$this->set_prop( 'carrier', $carrier );
	}

	/**
	 * Set transaction status
	 *
	 * @param string $status Status.
	 */
	public function set_status( $status ) {
		$this->set_prop( 'status', $status );
	}


	/**
	 * Set transaction status date
	 *
	 * @param string $status_date Status.
	 */
	public function set_status_date( $status_date ) {
		$this->set_prop( 'status_date', $status_date );
	}

	/**
	 * Set transaction info
	 *
	 * @param array $info Tracking info.
	 */
	public function set_info( $info ) {
		$this->set_prop( 'info', $info );
	}


	/**
	 * Check for updates
	 *
	 * @since 1.0.0
	 */
	public function check_for_updates() {
		$is_test         = $this->is_test();
		$carrier         = $is_test ? 'shippo' : $this->get_carrier();
		$tracking_number = $is_test ? self::get_test_tracking_number( $this->get_status() ) : $this->get_tracking_number();

		$tracking_updated = yith_shippo()->request->get_tracking()->get_status( $carrier, $tracking_number );
		if ( $tracking_updated ) {
			$this->update_tracking_status( $tracking_updated );
		}
	}

	/**
	 * Return if the tracking is a test
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function is_test() {
		$info = $this->get_info();

		return $info['test'] ?? 0;
	}

	/**
	 * Update tracking status
	 *
	 * @param array $tracking_updated Tracking to update.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function update_tracking_status( $tracking_updated ) {
		$new_status_date = $tracking_updated['tracking_status']['status_date'];
		$new_status      = $tracking_updated['tracking_status'] ['status'];
		$old_status      = $this->get_status();
		$old_info        = $this->get_info();
		if ( $this->is_test() || strtotime( $new_status_date ) > strtotime( $this->get_status_date() ) ) {
			$this->set_status_date( gmdate( 'Y-m-d H:i:s', strtotime( $new_status_date ) ) );
			$this->set_info( $tracking_updated );
			$this->set_status( $new_status );
			$this->save();
			if ( $old_status !== $tracking_updated['tracking_status'] ['status'] ) {
				/**
				 * DO_ACTION:yith_shippo_tracking_status_updated
				 *
				 * This hook is triggered after that the tracking status changes
				 *
				 * @param string                     $old_status Old status.
				 * @param string                     $new_status New status.
				 * @param array                      $tracking_updated Updated tracking info.
				 * @param string                     $key Current key of rate.
				 * @param YITH_Shippo_Order_Shipping $this Current object.
				 */
				do_action( 'yith_shippo_tracking_status_updated', $old_status, $new_status, $tracking_updated, $this->get_rate_key(), $this );
			}

			if (
				isset( $old_info['tracking_status'] ['substatus'], $tracking_updated['tracking_status'] ['substatus'] ) &&
				$old_info['tracking_status'] ['substatus'] !== $tracking_updated['tracking_status'] ['substatus'] &&
				in_array( $tracking_updated['tracking_status'] ['substatus'], yith_shippo_get_substatus_with_action_required_list(), true )
			) {
				/**
				 * DO_ACTION:yith_shippo_tracking_status_action_request
				 *
				 * This hook is triggered after that the tracking status changes and an action is requested by the merchant
				 *
				 * @param string                     $old_status Old status.
				 * @param string                     $new_status New status.
				 * @param array                      $tracking_updated Updated tracking info.
				 * @param string                     $key Current key of rate.
				 * @param YITH_Shippo_Order_Shipping $this Current object.
				 */
				do_action( 'yith_shippo_tracking_status_action_request', $old_status, $new_status, $tracking_updated, $this->get_rate_key(), $this );
			}
		}
	}

}
