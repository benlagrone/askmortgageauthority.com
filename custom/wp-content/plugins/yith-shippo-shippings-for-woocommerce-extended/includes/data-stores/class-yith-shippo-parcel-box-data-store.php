<?php
/**
 * Parcel boxex data store
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Shippo\Classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_Shippo_Parcel_Box_Data_Store' ) ) {
	/**
	 * This class implements CRUD methods for Parcel box
	 *
	 * @since 1.0.0
	 */
	class YITH_Shippo_Parcel_Box_Data_Store implements YITH_Shippo_Object_Data_Store_Interface {

		use YITH_Shippo_Trait_DB_Object, YITH_Shippo_Trait_Cacheable;


		/**
		 * Constructor method
		 */
		public function __construct() {
			global $wpdb;
			$this->table       = $wpdb->yith_shippo_shipping_parcel_boxes;
			$this->cache_group = 'shippo_parcel_boxes';
		}


		/**
		 * Method to create a new record of a WC_Data based object.
		 *
		 * @param YITH_Shippo_Parcel_Box $parcel_box Data object.
		 */
		public function create( &$parcel_box ) {
			global $wpdb;
			$data = array(
				'name'           => $parcel_box->get_name( 'edit' ),
				'enabled'        => $parcel_box->get_enabled( 'edit' ),
				'type'           => $parcel_box->get_type( 'edit' ),
				'weight'         => $parcel_box->get_weight( 'edit' ),
				'width'          => $parcel_box->get_width( 'edit' ),
				'height'         => $parcel_box->get_height( 'edit' ),
				'length'         => $parcel_box->get_length( 'edit' ),
				'inner_padding'  => $parcel_box->get_inner_padding( 'edit' ),
				'max_weight'     => $parcel_box->get_max_weight( 'edit' ),
				'distance_unit'  => $parcel_box->get_distance_unit( 'edit' ),
				'weight_unit'    => $parcel_box->get_weight_unit( 'edit' ),
				'shipping_zones' => maybe_serialize( $parcel_box->get_shipping_zones( 'edit' ) ),
			);

			$wpdb->insert( $this->table, $data ); // WPCS: DB call ok.

			$parcel_box_id = $wpdb->insert_id;
			$parcel_box->set_id( $parcel_box_id );
			$parcel_box->apply_changes();

			$this->cache_delete( $parcel_box->get_id() );
			$this->invalidate_versioned_cache();
			/**
			 * DO_ACTION:yith_shippo_new_parcel_box
			 *
			 * This action is triggered after the creation of a new parcel box.
			 *
			 * @param int                    $parcel_box_id New parcel box id.
			 * @param YITH_Shippo_Parcel_Box $parcel_box New parcel box object.
			 */
			do_action( 'yith_shippo_new_parcel_box', $parcel_box_id, $parcel_box );
		}

		/**
		 * Read a parcel box from the database.
		 *
		 * @param YITH_Shippo_Parcel_Box $parcel_box Data object instance.
		 *
		 * @throws Exception When parcel box is invalid.
		 * @since  1.0.0
		 */
		public function read( &$parcel_box ) {
			global $wpdb;

			$data = $this->cache_get( $parcel_box->get_id() );

			if ( false === $data ) {
				$data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->yith_shippo_shipping_parcel_boxes} WHERE id = %d LIMIT 1;", $parcel_box->get_id() ), ARRAY_A ); // WPCS: cache ok, DB call ok.
				$this->cache_set( $parcel_box->get_id(), $data );
			}

			if ( is_array( $data ) ) {
				$parcel_box->set_props(
					array(
						'id'             => $data['ID'],
						'name'           => $data['name'],
						'type'           => $data['type'],
						'length'         => $data['length'],
						'width'          => $data['width'],
						'height'         => $data['height'],
						'weight'         => $data['weight'],
						'max_weight'     => $data['max_weight'],
						'inner_padding'  => $data['inner_padding'],
						'distance_unit'  => $data['distance_unit'],
						'weight_unit'    => $data['weight_unit'],
						'shipping_zones' => isset( $data['shipping_zones'] ) ?? array(),
					)
				);
				$parcel_box->set_object_read( true );
				/**
				 * DO_ACTION:yith_shippo_parcel_box_loaded
				 *
				 * This action is triggered after the loading of a parcel box.
				 *
				 * @param YITH_Shippo_Parcel_Box $parcel_box New parcel box object.
				 */
				do_action( 'yith_shippo_parcel_box_loaded', $parcel_box );
			} else {
				throw new Exception( _x( 'Invalid parcel box.', 'error notice', 'yith-shippo-shippings-for-woocommerce' ) );
			}
		}

		/**
		 * Updates a record in the database.
		 *
		 * @param YITH_Shippo_Parcel_Box $parcel_box Data object instance.
		 */
		public function update( &$parcel_box ) {
			global $wpdb;

			$data = array(
				'name'           => $parcel_box->get_name( 'edit' ),
				'enabled'        => $parcel_box->get_enabled( 'edit' ),
				'type'           => $parcel_box->get_type( 'edit' ),
				'weight'         => $parcel_box->get_weight( 'edit' ),
				'width'          => $parcel_box->get_width( 'edit' ),
				'height'         => $parcel_box->get_height( 'edit' ),
				'length'         => $parcel_box->get_length( 'edit' ),
				'inner_padding'  => $parcel_box->get_inner_padding( 'edit' ),
				'max_weight'     => $parcel_box->get_max_weight( 'edit' ),
				'distance_unit'  => $parcel_box->get_distance_unit( 'edit' ),
				'weight_unit'    => $parcel_box->get_weight_unit( 'edit' ),
				'shipping_zones' => maybe_serialize( $parcel_box->get_shipping_zones( 'edit' ) ),
			);

			$wpdb->update( $wpdb->yith_shippo_shipping_parcel_boxes, $data, array( 'id' => $parcel_box->get_id() ) ); // phpcs:ignore
			$parcel_box->apply_changes();

			$this->cache_delete( $parcel_box->get_id() );
			$this->invalidate_versioned_cache();

			/**
			 * DO_ACTION:yith_shippo_parcel_box_updated
			 *
			 * This action is triggered when a parcel box is updated.
			 *
			 * @param int $parcel_box_id Parcel box id.
			 */
			do_action( 'yith_shippo_parcel_box_updated', $parcel_box->get_id() );

		}

		/**
		 * Deletes a record from the database.
		 *
		 * @param YITH_Shippo_Parcel_Box $parcel_box Data object instance.
		 *
		 * @return bool result
		 */
		public function delete( &$parcel_box ) {
			global $wpdb;
			$res = $wpdb->delete(
				$wpdb->yith_shippo_shipping_parcel_boxes,
				array(
					'id' => $parcel_box->get_id(),
				),
				array( '%d' )
			); // WPCS: cache ok, DB call ok.

			$this->cache_delete( $parcel_box->get_id() );
			$this->invalidate_versioned_cache();

			/**
			 * DO_ACTION:yith_shippo_parcel_box_deleted
			 *
			 * This action is triggered when a parcel box is deleted.
			 *
			 * @param int                    $parcel_box_id Parcel box id.
			 * @param YITH_Shippo_Parcel_Box $parcel_box New parcel box object.
			 */
			do_action( 'yith_shippo_parcel_box_deleted', $parcel_box->get_id(), $parcel_box );

			return $res;
		}


		/**
		 * Return the list of parcel boxes
		 *
		 * @return array An array of objects containing parcel boxes.
		 * @since 1.0.0
		 */
		public function get_parcel_boxes() {
			$parcel_boxes = $this->get_parcel_boxes_as_array();

			if ( ! $parcel_boxes ) {
				return array();
			}

			$ids          = array_map( 'intval', wp_list_pluck( $parcel_boxes, 'ID' ) );
			$parcel_boxes = array_filter( array_map( 'yith_shippo_get_parcel_box', $ids ) );

			return $parcel_boxes;
		}

		/**
		 * Return the list of parcel boxes as assiociative array.
		 *
		 * @param array $args The args.
		 *
		 * @return array An array of objects containing parcel boxes.
		 *
		 * @since 1.0.0
		 */
		public function get_parcel_boxes_as_array( $args = array() ) {
			global $wpdb;

			$where = '';
			if ( isset( $args['enabled'] ) ) {
				$where .= ' parcel.enabled=' . $args['enabled'];
			}

			$where        = empty( $where ) ? '' : ' WHERE 1=1 AND' . $where;
			$parcel_boxes = $this->cache_get( $this->get_versioned_cache_key( 'query', $args ) );

			// if no data found in cache, query database.
			if ( false === $parcel_boxes ) {
				$parcel_boxes = $wpdb->get_results( "SELECT * FROM {$wpdb->yith_shippo_shipping_parcel_boxes} as parcel " . $where, ARRAY_A ); //phpcs:ignore

				if ( isset( $args['shipping_zone'] ) && $args['shipping_zone'] ) {
					foreach ( $parcel_boxes as $key => $box ) {
						$ship_zones = array_map( 'intval', maybe_unserialize( $box['shipping_zones'] ) );
						if ( ! in_array( $args['shipping_zone'], $ship_zones, true ) ) {
							unset( $parcel_boxes[ $key ] );
						}
					}
				}
				$this->cache_set( $this->get_versioned_cache_key( 'query', $args ), $parcel_boxes );
			}

			return $parcel_boxes;
		}
	}
}
