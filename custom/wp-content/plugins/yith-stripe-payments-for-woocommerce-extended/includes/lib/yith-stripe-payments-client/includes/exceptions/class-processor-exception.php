<?php
/**
 * Processor Exception
 *
 * @author  YITH
 * @package YITH\StripeClient\Exceptions
 * @version 1.0.0
 */

namespace YITH\StripeClient\Exceptions;

use YITH\StripeClient\Exceptions\Abstracts\Exception;

defined( 'YITH_STRIPE_CLIENT_DIR' ) || exit;

if ( ! class_exists( 'YITH\StripeClient\Exceptions\Processor_Exception' ) ) {
	/**
	 * Used to describe an error occurred in this application
	 *
	 * @since 1.0.0
	 */
	class Processor_Exception extends Exception {
		/**
		 * Constructor method
		 *
		 * @param string $message   Error message.
		 */
		public function __construct( $message ) {
			parent::__construct( $message, 0, 'connection_error' );
		}

	}
}
