<?php
/**
 * Trait that offers a convenient and quick access to a logger instance and a log method
 *
 * @author  YITH
 * @package YITH\StripePayments\Traits
 * @version 2.0.0
 */

namespace YITH\StripePayments\Traits;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! trait_exists( 'YITH\StripePayments\Traits\Logger' ) ) {
	/**
	 * This class implements an easy way to access WooCommerce's unique WC_Logger instance
	 *
	 * It should be used exclusively to log operations regarding the plugin in the main plugin's log file
	 * Anyway, it also offers a quick way to override source when needed.
	 *
	 * @since 1.0.0
	 */
	trait Logger {
		/**
		 * Logger instance
		 *
		 * @var \WC_Logger
		 */
		protected static $logger;

		/**
		 * Returns log source
		 *
		 * Override this method and change source to make extender class write on a separate log file
		 *
		 * @return string Log source
		 */
		public static function get_log_source() {
			return 'yith-stripe-payments-for-woocommerce';
		}

		/**
		 * Log messages to plugin's log file
		 *
		 * @param string $message Message to log.
		 * @param string $level   One of the following:
		 *     'emergency': System is unusable.
		 *     'alert': Action must be taken immediately.
		 *     'critical': Critical conditions.
		 *     'error': Error conditions.
		 *     'warning': Warning conditions.
		 *     'notice': Normal but significant condition.
		 *     'info': Informational messages.
		 *     'debug': Debug-level messages.
		 */
		public static function log( $message, $level = 'info' ) {
			if ( ! self::$logger ) {
				self::$logger = wc_get_logger();
			}

			if ( ! self::$logger ) {
				return;
			}

			self::$logger->log(
				$level,
				$message,
				array(
					'source' => self::get_log_source(),
				)
			);
		}
	}
}
