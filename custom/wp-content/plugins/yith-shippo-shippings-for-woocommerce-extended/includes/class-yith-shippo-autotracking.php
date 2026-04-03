<?php
/**
 * This class manage the autotracking
 *
 * @package YITH\Shippo
 */

defined( 'ABSPATH' ) || exit;

/**
 * The class
 */
class YITH_Shippo_Autotracking {
	use YITH_Shippo_Trait_Singleton;

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->logger = YITH_Shippo_Logger::get_instance();
		add_action( 'admin_init', array( $this, 'check_autotracking' ), 10 );
		add_action( 'yith_shippo_autotracking', array( $this, 'autotracking' ), 10, 2 );
	}

	/**
	 * Check autotracking options
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function check_autotracking() {
		/**
		 * APPLY_FILTERS: yith_shippo_enable_autotracking_scheduling
		 *
		 * This filter allow disable the autotracking scheduler.
		 *
		 * @param   bool  $enable_autotracking  Status of option to set.
		 *
		 * @return bool
		 */
		if ( 'yes' === get_option( 'yith_shippo_enable_autotracking', 'yes' ) && apply_filters( 'yith_shippo_enable_autotracking_scheduling', true ) ) {
			$this->schedule_autotracking();
		} else {
			$this->remove_autotracking_scheduling();
		}
	}

	/**
	 * Autotracking action
	 *
	 * @param   int $limit  Number of items to retrieve.
	 * @param   int $offset  Offset.
	 *
	 * @return void
	 */
	public function autotracking( $limit, $offset ) {
		$trackings = yith_shippo_get_trackings(
			array(
				'offset'         => $offset,
				'limit'          => $limit,
				'status__not_in' => array( 'DELIVERED' ),
				'date_start'     => date( 'Y-m-d H:i:s', strtotime( 'first day of -2 month' ) ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			)
		);
		$items     = 0;

		if ( $trackings ) {
			$items = count( $trackings );
			foreach ( $trackings as $tracking ) {
				$this->logger->tracking( '[Action scheduler]Checking the status of this tracking id ' . $tracking->get_id() . ' order_id ' . $tracking->get_order_id() . ' rate_key ' . $tracking->get_rate_key() );
				$tracking->check_for_updates();
			}
		}

		if ( $items < $limit ) {
			$this->schedule_autotracking();
		} else {
			as_schedule_single_action(
				time(),
				'yith_shippo_autotracking',
				array(
					'limit'  => $limit,
					'offset' => $offset + $limit,
				)
			);
		}
	}

	/**
	 * Set the scheduling for autotracking
	 */
	public function schedule_autotracking() {
		$hook               = 'yith_shippo_autotracking';
		$has_hook_scheduled = as_next_scheduled_action( $hook );
		/**
		 * APPLY_FILTER: yith_shippo_autotracking_scheduling_process_number
		 *
		 * This filter allow to change the number of tracking to process in every single process.
		 *
		 * @param   int  $process_number Number of element to process.
		 *
		 * @return int
		 */
		$limit = apply_filters( 'yith_shippo_autotracking_scheduling_process_number', 20 );

		if ( empty( $has_hook_scheduled ) ) {
			as_schedule_single_action(
				time() + 30 * MINUTE_IN_SECONDS,
				$hook,
				array(
					'limit'  => $limit,
					'offset' => 0,
				)
			);
		}
	}

	/**
	 * Remove autotracking scheduling
	 */
	public function remove_autotracking_scheduling() {
		as_unschedule_all_actions( 'yith_shippo_autotracking' );
	}
}
