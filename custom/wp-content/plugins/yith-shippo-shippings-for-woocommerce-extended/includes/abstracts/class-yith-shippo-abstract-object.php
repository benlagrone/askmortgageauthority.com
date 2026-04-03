<?php
/**
 * Generic object class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Shippo\Abstracts
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_Shippo_Abstract_Object' ) ) {

	/**
	 * Shippo generic object
	 *
	 * @since 1.0.0
	 */
	abstract class YITH_Shippo_Abstract_Object extends WC_Data implements ArrayAccess {
		/**
		 * Relation existing between object properties and array offsets
		 *
		 * @var array
		 */
		protected $offset_to_prop_map = array();

		/**
		 * Returns an array representation of this object
		 * It is an alias of ->get_data,
		 *
		 * @return array Formatted array representing current item.
		 */
		public function to_array() {
			/**
			 * APPLY_FILTERS: yith_shippo_object_to_array
			 *
			 * This filter allow to manage the object data as array.
			 *
			 * @param array $object the object data.
			 *
			 * @return array
			 */
			return apply_filters( 'yith_shippo_object_to_array', array_merge( array( 'id' => $this->get_id() ), $this->data ), $this->get_id(), $this );
		}


		/* === ARRAY ACCESS === */

		/**
		 * Checks whether or not an offset exists.
		 *
		 * @param string $offset Offset to check.
		 *
		 * @return bool Whether or not the offset exists.
		 */
		#[\ReturnTypeWillChange]
		public function offsetExists( $offset ) {
			if ( isset( $this->offset_to_prop_map[ $offset ] ) ) {
				$offset = $this->offset_to_prop_map[ $offset ];
			}

			if ( ! method_exists( $this, "get_{$offset}" ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Retrieves value for a specific offset
		 *
		 * @param string $offset Offset to retrieve.
		 *
		 * @return mixed Retrieved value.
		 */
		#[\ReturnTypeWillChange]
		public function offsetGet( $offset ) {
			if ( isset( $this->offset_to_prop_map[ $offset ] ) ) {
				$offset = $this->offset_to_prop_map[ $offset ];
			}

			if ( ! method_exists( $this, "get_{$offset}" ) ) {
				return null;
			}

			return $this->{"get_{$offset}"}( 'view' );
		}

		/**
		 * Sets value for a specific offset
		 *
		 * @param string $offset Offset to check.
		 * @param mixed  $value Value to set for the offset.
		 */
		#[\ReturnTypeWillChange]
		public function offsetSet( $offset, $value ) {
			if ( isset( $this->offset_to_prop_map[ $offset ] ) ) {
				$offset = $this->offset_to_prop_map[ $offset ];
			}

			if ( ! method_exists( $this, "set_{$offset}" ) ) {
				return;
			}

			$this->{"set_{$offset}"}( $value );
		}

		/**
		 * Does nothing. Just required by interface.
		 *
		 * @param string $offset Offset to check.
		 */
		#[\ReturnTypeWillChange]
		public function offsetUnset( $offset ) {
			// just do nothing; should never be called.
		}

		/**
		 * Prefix for action and filter hooks on data.
		 *
		 * @return string
		 * @since  1.0.0
		 */
		protected function get_hook_prefix() {
			return 'yith_shippo_' . $this->object_type . '_get_';
		}
	}
}
