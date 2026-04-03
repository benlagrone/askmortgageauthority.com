<?php
/**
 * Trait that implements singleton design pattern on a class
 *
 * @author  YITH
 * @package YITH\StripePayments\Traits
 * @version 2.0.0
 */

namespace YITH\StripePayments\Traits;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! trait_exists( 'YITH\StripePayments\Traits\Singleton' ) ) {
	/**
	 * This class implements singleton management on the object that uses it
	 * It will define static method ::get_instance(), that has to be called to retrieve single instance of the class
	 *
	 * Classes that uses this trait <b>should also define <code>__construct</code> as protected</b>
	 *
	 * @since 1.0.0
	 */
	trait Singleton {
		/**
		 * Single instance of the class
		 *
		 * @var $this
		 * @since 1.0.0
		 */
		protected static $instance = null;

		/**
		 * Returns single instance of the class
		 *
		 * @return $this
		 * @since 1.0.2
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				static::$instance = new static();
			}

			return static::$instance;
		}
	}
}
