<?php
/**
 * Cache Helper
 *
 * Offers a couple of useful methods that allow to save cached data,
 * and invalidate it when it is needed or when something changes in the environment.
 *
 * @author  YITH
 * @package YITH\StripePayments\Classes
 * @version 1.0.0
 */

namespace YITH\StripePayments;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

use YITH\StripePayments\Traits\Environment_Access;

if ( ! class_exists( 'YITH\StripePayments\Cache_Helper' ) ) {
	/**
	 * Offers static methods that implement cache utilities
	 *
	 * @since 1.0.0
	 */
	class Cache_Helper {

		use Environment_Access;

		/**
		 * String containing current cache version.
		 *
		 * @var string.
		 */
		protected static $cache_version;

		/**
		 * Returns current stored cache version, or generate a new one when none existing
		 *
		 * @return string Current cache version.
		 */
		public static function get_cache_version() {
			if ( empty( self::$cache_version ) ) {
				$cache_version = get_option( 'yith_stripe_payments_cache_version', false );

				if ( ! $cache_version ) {
					$cache_version = self::invalidate_cache();
				}

				self::$cache_version = $cache_version;
			}

			return self::$cache_version;
		}

		/**
		 * Returns a key that is hashed using data about current cache version
		 *
		 * @param string $key Key to hash.
		 *
		 * @return string Hashed key.
		 */
		public static function get_versioned_key( $key ) {
			$version    = self::get_cache_version();
			$account    = Account::get_instance();
			$acct_id    = $account->get_account_id();
			$components = array_filter( compact( 'acct_id', 'version' ) );

			return self::get_cache_key( $key, $components );
		}

		/**
		 * Returns a key that is hashed using data about current account
		 *
		 * @param string $key Key to hash.
		 *
		 * @return string Hashed key.
		 */
		public static function get_account_key( $key ) {
			$account    = Account::get_instance();
			$acct_id    = $account->get_account_id();
			$components = array_filter( compact( 'acct_id' ) );

			return self::get_cache_key( $key, $components );
		}

		/**
		 * Returns a key that is hashed using data about current site
		 *
		 * @param string $key Key to hash.
		 *
		 * @return string Hashed key.
		 */
		public static function get_site_key( $key ) {
			$account    = Account::get_instance();
			$site_url   = $account->get_url();
			$components = array_filter( compact( 'site_url' ) );

			return self::get_cache_key( $key, $components );
		}

		/**
		 * Invalidate current cache (existing data will remain, but be ignored from now on).
		 */
		public static function invalidate_cache() {
			$cache_version = time();

			if ( update_option( 'yith_stripe_payments_cache_version', $cache_version ) ) {
				do_action( 'yith_stripe_payments_invalidate_cache' );
			}

			return $cache_version;
		}

		/**
		 * Generates hashed key, using key components passed as second argument
		 *
		 * @param string $key        Key to hash.
		 * @param array  $components Components to use to generate hash.
		 *
		 * @return string Hashed key.
		 */
		protected static function get_cache_key( $key, $components ) {
			$env   = self::get_env();
			$brand = yith_stripe_payments_get_brand();
			$hash  = md5( implode( '|', array_merge( $components, array( $env, $brand ) ) ) );

			return "yith_stripe_payments_{$env}_{$key}_{$hash}";
		}
	}
}
