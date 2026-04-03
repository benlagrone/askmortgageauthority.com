<?php
/**
 * Trait that implements factory design pattern on a class
 *
 * @author  YITH
 * @package YITH\StripePayments\Traits
 * @version 2.0.0
 */

namespace YITH\StripePayments\Traits;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! trait_exists( 'YITH\StripePayments\Traits\Factory' ) ) {
	/**
	 * This class implements factory management on the object that uses it
	 * It will define static method ::get_instance( $id ), that has to be called to retrieve specific instance of the class
	 *
	 * Classes that uses this trait <b>should also define <code>__construct</code> as protected</b>,
	 * and make it accept one parameter (id of the object to create).
	 *
	 * @since 1.0.0
	 */
	trait Factory {
		/**
		 * List of available instances of the class
		 *
		 * @var $this[]
		 * @since 1.0.0
		 */
		protected static $instances = array();

		/**
		 * Returns single instance of the class
		 *
		 * @param mixed $id Unique identifier of the instance to retrieve.
		 * @return $this|\WP_Error
		 * @since 1.0.2
		 */
		public static function get_instance( $id ) {
			if ( ! isset( self::$instances[ $id ] ) ) {
				try {
					self::$instances[ $id ] = new static( $id );
				} catch ( \Exception $e ) {
					return new \WP_Error( 'factory_error', $e->getMessage() );
				}
			}

			return self::$instances[ $id ];
		}
	}
}
