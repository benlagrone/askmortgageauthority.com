<?php
/**
 * HTTP requests processor - Guzzle
 *
 * @author  YITH
 * @package YITH\StripeClient\Processors
 * @version 1.0.0
 */

namespace YITH\StripeClient\Processors;

use YITH\StripeClient\Interfaces\Processor;
use GuzzleHttp\Client as Guzzle_Client;
use GuzzleHttp\Exception\GuzzleException as Guzzle_Exception;

defined( 'YITH_STRIPE_CLIENT_DIR' ) || exit;

if ( ! class_exists( 'YITH\StripeClient\Processors\Guzzle' ) ) {
	/**
	 * Stripe Payments API class
	 *
	 * @since 1.0.0
	 */
	class Guzzle implements Processor {

		/**
		 * Url of the remote server.
		 *
		 * @var string
		 */
		protected $url;

		/**
		 * Unique instance of Guzzle client
		 *
		 * @var Guzzle_Client
		 */
		protected $client;

		/**
		 * Initialize connection with the remote server
		 *
		 * @param string $url Base url of the remote server.
		 *
		 * @throws \Exception When an error occurs with processor initialization.
		 */
		public function maybe_connect( $url ) {
			if ( ! class_exists( 'Guzzle_Client' ) ) {
				throw new \Exception( 'Missing guzzle extension' );
			}

			$url = esc_url( $url );

			if ( ! $url ) {
				throw new \Exception( 'Submitted invalid url for the remote server' );
			}

			$this->url    = $url;
			$this->client = new Guzzle_Client(
				array(
					'base_uri' => $url,
				)
			);
		}

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
		public function call( $method, $endpoint, $args = array() ) {
			// generates basic connection params.
			$method = strtoupper( $method );

			// build arguments array.
			$args = array_merge(
				array(
					'timeout'            => 30,
					'reject_unsafe_urls' => true,
					'blocking'           => true,
					'sslverify'          => true,
					'attempts'           => 0,
				),
				$args,
				array(
					'method' => $method,
				)
			);

			try {
				$response = $this->client->request( $method, $endpoint, $args );

				// format response according to WP Standards.
				return array(
					'headers'  => $response->getHeaders(),
					'response' => array(
						'code'    => $response->getStatusCode(),
						'message' => $response->getReasonPhrase(),
					),
					'body'     => @json_decode( (string) $response->getBody() ),
				);
			} catch ( Guzzle_Exception $e ) {
				return new \WP_Error( $e->getResponse()->getStatusCode(), $e->getResponse()->getReasonPhrase() );
			}
		}
	}
}
