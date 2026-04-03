<?php
/**
 * Interface for RESTful processors
 *
 * @author  YITH
 * @package YITH\StripeClient
 * @version 1.0.0
 */

namespace YITH\StripeClient\Interfaces;

if ( ! Interface_exists( 'Processor' ) ) {
	/**
	 * Stripe Payments API class
	 *
	 * @since 1.0.0
	 */
	interface Processor {

		/**
		 * Initialize connection with the remote server
		 *
		 * @param string $url Base url of the remote server.
		 *
		 * @throws \Exception When an error occurs with processor initialization.
		 */
		public function maybe_connect( $url );

		/**
		 * Calls an endpoint on the remote server
		 *
		 * @param string $method   HTTP method to use for the call.
		 * @param string $endpoint Endpoint to call on the server.
		 * @param array  $args     Optional array of arguments for the call. An example is listed below:
		 * [
		 *    'timeout'            => 30,
		 *    'reject_unsafe_urls' => true,
		 *    'blocking'           => true,
		 *    'sslverify'          => true,
		 *    'attempts'           => 0,
		 *    'headers'            => []
		 *    'body'               => ''
		 * ].
		 */
		public function call( $method, $endpoint, $args = array() );
	}
}
