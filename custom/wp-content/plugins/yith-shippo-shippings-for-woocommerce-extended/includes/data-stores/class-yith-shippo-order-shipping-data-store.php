<?php
/**
 * Order shipping data store
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Shippo\Classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_Shippo_Order_Shipping_Data_Store' ) ) {
	/**
	 * This class implements CRUD methods for Order Shipping
	 *
	 * @since 1.0.0
	 */
	class YITH_Shippo_Order_Shipping_Data_Store extends WC_Order_Item_Shipping_Data_Store {

		/**
		 * Data stored in meta keys.
		 *
		 * @since 3.0.0
		 * @var array
		 */
		protected $internal_meta_keys = array( 'method_id', 'instance_id', 'cost', 'total_tax', 'taxes', 'rates', 'transactions', 'created_via' );

		/**
		 * Read/populate data properties specific to this order item.
		 *
		 * @param WC_Order_Item_Shipping $item Item to read to.
		 *
		 * @throws Exception If invalid shipping order item.
		 * @since 1.0.0
		 */
		public function read( &$item ) {
			parent::read( $item );
			$id    = $item->get_id();
			$props = array(
				'method_id'    => get_metadata( 'order_item', $id, 'method_id', true ),
				'instance_id'  => get_metadata( 'order_item', $id, 'instance_id', true ),
				'total'        => get_metadata( 'order_item', $id, 'cost', true ),
				'taxes'        => get_metadata( 'order_item', $id, 'taxes', true ),
				'rates'        => get_metadata( 'order_item', $id, 'rates', true ),
				'transactions' => get_metadata( 'order_item', $id, 'transactions', true ),
				'created_via'  => get_metadata( 'order_item', $id, 'created_via', true ),
			);

			$item->set_props(
				$props
			);

			// BW compat.
			if ( '' === $item->get_instance_id() && strstr( $item->get_method_id(), ':' ) ) {
				$legacy_method_id = explode( ':', $item->get_method_id() );
				$item->set_method_id( $legacy_method_id[0] );
				$item->set_instance_id( $legacy_method_id[1] );
			}

			$item->set_object_read( true );
		}

		/**
		 * Saves an item's data to the database / item meta.
		 * Ran after both create and update, so $id will be set.
		 *
		 * @param WC_Order_Item_Shipping $item Item to save.
		 *
		 * @since 1.0.0
		 */
		public function save_item_data( &$item ) {
			$id = $item->get_id();

			$meta_key_to_props = array(
				'method_id'    => 'method_id',
				'instance_id'  => 'instance_id',
				'cost'         => 'total',
				'total_tax'    => 'total_tax',
				'taxes'        => 'taxes',
				'rates'        => 'rates',
				'transactions' => 'transactions',
				'created_via'  => 'created_via',
			);
			$props_to_update   = $this->get_props_to_update( $item, $meta_key_to_props, 'order_item' );

			foreach ( $props_to_update as $meta_key => $prop ) {
				update_metadata( 'order_item', $id, $meta_key, $item->{"get_$prop"}( 'edit' ) );
			}
		}
	}
}
