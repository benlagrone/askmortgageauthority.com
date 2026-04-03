<?php
/**
 * Api Exception
 *
 * @author  YITH
 * @package YITH\StripeClient\Exceptions
 * @version 1.0.0
 */

namespace YITH\StripeClient\Exceptions;

use YITH\StripeClient\Exceptions\Abstracts\Exception;

defined( 'YITH_STRIPE_CLIENT_DIR' ) || exit;

if ( ! class_exists( 'YITH\StripeClient\Exceptions\Api_Exception' ) ) {
	/**
	 * Used to describe an error occurred in this application
	 *
	 * @since 1.0.0
	 */
	class Api_Exception extends Exception {
		/**
		 * Constructor method
		 *
		 * @param string $message   Error message.
		 * @param string $http_code API call return HTTP status.
		 * @param string $synthetic Synthetic error code.
		 * @param array  $details   Array containing further details about the error.
		 */
		public function __construct( $message, $http_code, $synthetic = '', $details = false ) {
			$details = wp_parse_args(
				$details,
				array(
					'path'     => '',
					'method'   => '',
					'payload'  => false,
					'response' => false,
					'severity' => false,
					'errors'   => array(),
					'data'     => array(),
				)
			);

			parent::__construct( $message, (int) $http_code, $synthetic, $details );
		}

		/**
		 * Returns endpoint path that triggered this error
		 *
		 * @return string Endpoint path called.
		 */
		public function getPath() {
			if ( ! isset( $this->details['path'] ) ) {
				return false;
			}

			return $this->details['path'];
		}

		/**
		 * Returns method used for the API call.
		 *
		 * @return string HTTP Method.
		 */
		public function getMethod() {
			if ( ! isset( $this->details['method'] ) ) {
				return false;
			}

			return $this->details['method'];
		}

		/**
		 * Returns request payload.
		 *
		 * @return mixed Request payload.
		 */
		public function getPayload() {
			if ( ! isset( $this->details['payload'] ) ) {
				return false;
			}

			return $this->details['payload'];
		}

		/**
		 * Returns request response.
		 *
		 * @return mixed Request response.
		 */
		public function getResponse() {
			if ( ! isset( $this->details['response'] ) ) {
				return false;
			}

			return $this->details['response'];
		}

		/**
		 * Returns list of errors that caused the overall API exception.
		 *
		 * @return mixed Errors occurred during request.
		 */
		public function getErrors() {
			if ( ! isset( $this->details['errors'] ) ) {
				return false;
			}

			return $this->details['errors'];
		}
	}
}
