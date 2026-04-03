<?php
/**
 * Common functions that will be executed both on Frontend and Backend
 *
 * @author  YITH
 * @package YITH\StripePayments
 * @version 1.0.0
 */

namespace YITH\StripePayments;

use YITH\StripePayments\Traits\Environment_Access;
use YITH\StripePayments\Traits\Singleton;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Common' ) ) {
	/**
	 * Common class
	 * Functions to execute both in backend and frontend
	 *
	 * @since 1.0.0
	 */
	class Common {

		use Singleton, Environment_Access;

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		protected function __construct() {
			add_filter( 'pre_option_' . self::get_env_option_name(), array( $this, 'force_test_env' ) );
		}

		/**
		 * Forces to use test environment when {@see \WP_ENV} is defined and set to development
		 *
		 * @return string|bool Returns 'test' if {@see \WP_ENV} === 'development', false otherwise.
		 */
		public function force_test_env() {
			if ( defined( 'WP_ENV' ) && 'development' === \WP_ENV ) {
				return 'test';
			}

			return false;
		}
	}
}
