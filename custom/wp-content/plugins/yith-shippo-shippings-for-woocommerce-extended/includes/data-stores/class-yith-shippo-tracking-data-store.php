<?php
/**
 * Tracking data store
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Shippo\Classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_Shippo_Tracking_Data_Store' ) ) {
	/**
	 * This class implements CRUD methods for Tracking
	 *
	 * @since 1.0.0
	 */
	class YITH_Shippo_Tracking_Data_Store implements YITH_Shippo_Object_Data_Store_Interface {

		use YITH_Shippo_Trait_DB_Object, YITH_Shippo_Trait_Cacheable;

		/**
		 * Constructor method
		 */
		public function __construct() {
			global $wpdb;
			$this->table       = $wpdb->yith_shippo_shipping_tracking;
			$this->cache_group = 'shippo_tracking';
		}


		/**
		 * Method to create a new record of a WC_Data based object.
		 *
		 * @param YITH_Shippo_Tracking $tracking Data object.
		 */
		public function create( &$tracking ) {
			global $wpdb;
			$data = array(
				'tracking_number' => $tracking->get_tracking_number( 'edit' ),
				'order_id'        => $tracking->get_order_id( 'edit' ),
				'rate_key'        => $tracking->get_rate_key( 'edit' ),
				'carrier'         => $tracking->get_carrier( 'edit' ),
				'status'          => $tracking->get_status( 'edit' ),
				'status_date'     => $tracking->get_status_date( 'edit' ),
				'info'            => maybe_serialize( $tracking->get_info( 'edit' ) ),
			);

			$wpdb->insert( $this->table, $data ); // WPCS: DB call ok.

			$tracking_id = $wpdb->insert_id;
			$tracking->set_id( $tracking_id );
			$tracking->apply_changes();

			$this->cache_delete( $tracking->get_id() );
			$this->invalidate_versioned_cache();
			/**
			 * DO_ACTION:yith_shippo_new_tracking
			 *
			 * This action is triggered after the creation of a new tracking.
			 *
			 * @param int                  $tracking_id New tracking id.
			 * @param YITH_Shippo_Tracking $tracking New tracking object.
			 */
			do_action( 'yith_shippo_new_tracking', $tracking_id, $tracking );
		}

		/**
		 * Read a tracking from the database.
		 *
		 * @param YITH_Shippo_Tracking $tracking Data object instance.
		 *
		 * @throws Exception When tracking is invalid.
		 * @since  1.0.0
		 */
		public function read( &$tracking ) {
			global $wpdb;

			$data = $this->cache_get( $tracking->get_id() );

			if ( false === $data ) {
				$query = $wpdb->prepare( "SELECT * FROM {$wpdb->yith_shippo_shipping_tracking} WHERE id = %d LIMIT 1;", $tracking->get_id() );
				$data  = $wpdb->get_row( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$this->cache_set( $tracking->get_id(), $data );
			}

			if ( is_array( $data ) ) {
				$tracking->set_props(
					array(
						'id'              => $data['id'],
						'tracking_number' => $data['tracking_number'],
						'order_id'        => $data['order_id'],
						'rate_key'        => $data['rate_key'],
						'carrier'         => $data['carrier'],
						'status'          => $data['status'],
						'status_date'     => $data['status_date'],
						'info'            => $data['info'],
					)
				);

				$tracking->set_object_read( true );
				/**
				 * DO_ACTION:yith_shippo_tracking_loaded
				 *
				 * This action is triggered after the loading of a tracking.
				 *
				 * @param YITH_Shippo_Tracking $tracking New tracking box object.
				 */
				do_action( 'yith_shippo_tracking_loaded', $tracking );
			} else {
				throw new Exception( _x( 'Invalid tracking.', 'error notice', 'yith-shippo-shippings-for-woocommerce' ) );
			}
		}

		/**
		 * Updates a record in the database.
		 *
		 * @param YITH_Shippo_Tracking $tracking Data object instance.
		 */
		public function update( &$tracking ) {
			global $wpdb;

			$data = array(
				'tracking_number' => $tracking->get_tracking_number( 'edit' ),
				'order_id'        => $tracking->get_order_id( 'edit' ),
				'carrier'         => $tracking->get_carrier( 'edit' ),
				'rate_key'        => $tracking->get_rate_key( 'edit' ),
				'status'          => $tracking->get_status( 'edit' ),
				'status_date'     => $tracking->get_status_date( 'edit' ),
				'info'            => maybe_serialize( $tracking->get_info( 'edit' ) ),
			);

			$wpdb->update( $wpdb->yith_shippo_shipping_tracking, $data, array( 'id' => $tracking->get_id() ) ); // phpcs:ignore
			$tracking->apply_changes();

			$this->cache_delete( $tracking->get_id() );
			$this->invalidate_versioned_cache();

			/**
			 * DO_ACTION:yith_shippo_tracking_updated
			 *
			 * This action is triggered when a parcel box is triggered.
			 *
			 * @param int $tracking_id Tracking id.
			 */
			do_action( 'yith_shippo_tracking_updated', $tracking->get_id() );

		}

		/**
		 * Deletes a record from the database.
		 *
		 * @param YITH_Shippo_Tracking $tracking Data object instance.
		 *
		 * @return bool result
		 */
		public function delete( &$tracking ) {
			global $wpdb;
			$res = $wpdb->delete(
				$wpdb->yith_shippo_shipping_tracking,
				array(
					'id' => $tracking->get_id(),
				),
				array( '%d' )
			); // WPCS: cache ok, DB call ok.

			$this->cache_delete( $tracking->get_id() );
			$this->invalidate_versioned_cache();

			/**
			 * DO_ACTION:yith_shippo_tracking_deleted
			 *
			 * This action is triggered when a tracking is deleted.
			 *
			 * @param int                  $tracking_id Tracking id.
			 * @param YITH_Shippo_Tracking $tracking New tracking box object.
			 */
			do_action( 'yith_shippo_tracking_deleted', $tracking->get_id(), $tracking );

			return $res;
		}


		/**
		 * Get trackings
		 *
		 * @param array $args Args.
		 *
		 * @since 1.0.0
		 */
		public function get_trackings( $args ) {
			global $wpdb;
			$where = '';
			if ( isset( $args['tracking_number'] ) ) {
				$where .= ' AND tracking.tracking_number=' . $args['tracking_number'];
			}

			if ( isset( $args['order_id'] ) ) {
				$where .= ' AND tracking.order_id=' . $args['order_id'];
			}

			if ( isset( $args['rate_key'] ) ) {
				$where .= " AND tracking.rate_key='" . $args['rate_key'] . "'";
			}

			if ( isset( $args['date_start'] ) ) {
				$where .= " AND tracking.status_date >'" . $args['date_start'] . "'";
			}

			if ( isset( $args['status__not_in'] ) ) {
				$where .= " AND tracking.status NOT IN  ( '" . implode( "', '", $args['status__not_in'] ) . "' )";
			}

			$limit = '';
			if ( isset( $args['limit'] ) && $args['limit'] > 0 ) {
				$limit = ' LIMIT ' . $args['limit'];
			}

			if ( isset( $args['offset'] ) && $args['offset'] >= 0 ) {
				$limit .= ' OFFSET ' . $args['offset'];
			}

			$where = empty( $where ) ? '' : ' WHERE 1=1 ' . $where;

			$cache_key   = 'yith_shippo_get_trackings_' . md5( wp_json_encode( $args ) );
			$cache_value = wp_cache_get( $cache_key, $this->cache_group );

			if ( $cache_value ) {
				return $cache_value;
			}
			$trackings = $wpdb->get_col( "SELECT DISTINCT tracking.id FROM {$wpdb->yith_shippo_shipping_tracking} as tracking " . $where . $limit ); //phpcs:ignore
			wp_cache_set( $cache_key, $trackings, $this->cache_group );
			// if we have an empty set from db, return empty array and skip next steps.
			if ( ! $trackings ) {
				return false;
			}

			return $trackings;
		}


	}
}
