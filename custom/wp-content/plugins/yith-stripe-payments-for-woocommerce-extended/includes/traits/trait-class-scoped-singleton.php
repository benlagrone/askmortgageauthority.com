<?php
/**
 * Trait that implements singleton design pattern on a class that may be extended
 * and the extensions have to be their own instances.
 *
 * @author  YITH
 * @package YITH\StripePayments\Traits
 * @version 1.0.5
 */

namespace YITH\StripePayments\Traits;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! trait_exists( 'YITH\StripePayments\Traits\Class_Scoped_Singleton' ) ) {
	/**
	 * This class implements singleton management on the object that uses it
	 * It will define static method ::get_instance(), that has to be called to retrieve single instance of the class
	 *
	 * Classes that uses this trait <b>should also define <code>__construct</code> as protected</b>
	 *
	 * @since 1.0.0
	 */
	trait Class_Scoped_Singleton {
		/**
		 * The instances' collector.
		 *
		 * @var $this
		 * @since 1.0.0
		 */
		protected static $instances = array();

		/**
		 * Get class instance.
		 *
		 * @return self
		 */
		final public static function get_instance() {
			self::$instances[ static::class ] = self::$instances[ static::class ] ?? new static();

			return self::$instances[ static::class ];
		}
	}
}
