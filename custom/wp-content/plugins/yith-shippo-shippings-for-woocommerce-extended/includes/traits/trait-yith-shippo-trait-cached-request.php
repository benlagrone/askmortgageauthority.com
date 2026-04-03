<?php
/**
 * This trait manage the cached request
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Shippo\Traits
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;


if ( ! trait_exists( 'YITH_Shippo_Trait_Cached_Request' ) ) {
	/**
	 * This class implements methods that can be used to store/retrieve shippo api object from cache
	 *
	 * @since 1.0.0
	 */
	trait YITH_Shippo_Trait_Cached_Request {

		/**
		 * The transient id
		 *
		 * @var string
		 */
		protected $transient_id = 'yith_shippo_';

		/**
		 * Set the transient
		 *
		 * @param string $transient_type The transient type.
		 * @param mixed  $transient_value The value.
		 * @param int    $transient_expire The expire.
		 * @param array  $args The args to generate the unique key.
		 *
		 * @since 1.0.0
		 */
		protected function set_transient( $transient_type, $transient_value, $transient_expire = 0, $args = array() ) {
			$transient_type = $transient_type . '_' . $this->get_transient_key( $args );
			set_transient( $this->transient_id . $transient_type, $transient_value, $transient_expire );
		}

		/**
		 * Get the transient
		 *
		 * @param string $transient_type The transient type.
		 * @param array  $args The args to generate the unique key.
		 *
		 * @return mixed
		 * @since 1.0.0
		 */
		protected function get_transient( $transient_type, $args = array() ) {
			$transient_type = $transient_type . '_' . $this->get_transient_key( $args );

			return get_transient( $this->transient_id . $transient_type );
		}

		/**
		 * Delete the transient
		 *
		 * @param string $transient_type The transient type.
		 * @param array  $args The args to generate the unique key.
		 *
		 * @return bool
		 */
		protected function delete_transient( $transient_type, $args = array() ) {
			$transient_type = $transient_type . '_' . $this->get_transient_key( $args );

			return delete_transient( $this->transient_id . $transient_type );
		}

		/**
		 * Generate a unique key
		 *
		 * @param array $args The args.
		 *
		 * @return string
		 * @since 1.0.0
		 */
		protected function get_transient_key( $args ) {
			if ( ! empty( $args ) ) {
				return md5( http_build_query( (array) $args ) );
			} else {
				return '';
			}
		}
	}
}
