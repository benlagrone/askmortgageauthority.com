<?php
/**
 * Library generic Exception
 *
 * Extends default PHP exception to add a couple of additional info coming from the server or the processor.
 *
 * @author  YITH
 * @package YITH\StripeClient\Exceptions
 * @version 1.0.0
 */

namespace YITH\StripeClient\Exceptions\Abstracts;

defined( 'YITH_STRIPE_CLIENT_DIR' ) || exit;

if ( ! class_exists( 'YITH\StripeClient\Exceptions\Exception' ) ) {
	/**
	 * Used to describe an error occurred in this application
	 *
	 * @since 1.0.0
	 */
	abstract class Exception extends \Exception {
		/**
		 * Synthetic code describing the error
		 *
		 * @var string
		 */
		protected $synthetic = '';

		/**
		 * An array containing details about the error occurred.
		 *
		 * @var array
		 */
		protected $details;

		/**
		 * Constructor method
		 *
		 * @param string $message   Error message.
		 * @param int    $code      Exception code.
		 * @param string $synthetic Synthetic error code.
		 * @param array  $details   Array containing further details about the error.
		 */
		public function __construct( $message, $code, $synthetic = '', $details = false ) {
			$this->synthetic = $synthetic;
			$this->details   = $details;

			parent::__construct( $message, $code );
		}

		/**
		 * Returns synthetic type of the message
		 *
		 * @return string Synthetic error code.
		 */
		public function getType() {
			return $this->synthetic;
		}

		/**
		 * Returns details about this specific error
		 *
		 * @return array Complete array of details.
		 */
		public function getDetails() {
			return $this->details;
		}

	}
}
