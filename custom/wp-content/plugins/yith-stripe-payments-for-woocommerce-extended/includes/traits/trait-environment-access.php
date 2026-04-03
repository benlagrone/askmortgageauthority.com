<?php
/**
 * Trait that adds methods for an easy access to the current environment status
 *
 * @author  YITH
 * @package YITH\StripePayments\Traits
 * @version 2.0.0
 */

namespace YITH\StripePayments\Traits;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! trait_exists( 'YITH\StripePayments\Traits\Environment_Access' ) ) {
	/**
	 * This class implements methods that allow extenders to easily check environment status of the application
	 *
	 * Every class that uses this trait will be able to check of the environment through ::get_env(), ::is_live() and ::is_test() methods.
	 *
	 * @since 1.0.0
	 */
	trait Environment_Access {
		/**
		 * Name of the option where current environment is stored
		 *
		 * @var string
		 */
		private static $env_option_name = 'yith_stripe_payments_environment';

		/**
		 * Returns name of the option used to store plugin environment
		 *
		 * @return string
		 */
		public static function get_env_option_name() {
			return self::$env_option_name;
		}

		/**
		 * Returns current environment
		 *
		 * @return string|bool Environment
		 */
		public static function get_env() {
			return get_option( self::$env_option_name, 'live' );
		}

		/**
		 * Updates current environment to a new value
		 *
		 * @param string $env Environment to set (live|test).
		 */
		public static function set_env( $env ) {
			if ( ! in_array( $env, array( 'live', 'test' ), true ) ) {
				return;
			}

			$updated = update_option( self::$env_option_name, $env );
			$updated && do_action( 'yith_stripe_payments_updated_environment', $env );
		}

		/**
		 * Checks if gateway is currently in test mode
		 *
		 * @return bool Whether gateway is currently in test mode
		 */
		public static function is_test() {
			return 'test' === self::get_env();
		}

		/**
		 * Checks if gateway is currently in live mode
		 *
		 * @return bool Whether gateway is currently in live mode
		 */
		public static function is_live() {
			return 'live' === self::get_env();
		}
	}
}
