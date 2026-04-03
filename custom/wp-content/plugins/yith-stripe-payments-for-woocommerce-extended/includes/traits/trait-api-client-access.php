<?php
/**
 * Trait that adds methods for easy access of the API Client from any class
 *
 * @author  YITH
 * @package YITH\StripePayments\Traits
 * @version 2.0.0
 */

namespace YITH\StripePayments\Traits;

use YITH\StripePayments\Api_Client;
use YITH\StripeClient\Client as StripeClient;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! trait_exists( 'YITH\StripePayments\Traits\Api_Client_Access' ) ) {
	/**
	 * This class implements methods and properties to give extender easy access to the unique instance of the gateway
	 *
	 * Every class that uses this trait will be able to access gateway property through ::get_gateway() method
	 * Additionally, this trait allows for easy check of the environment through ::get_env(), ::is_live() and ::is_test() methods.
	 *
	 * @since 1.0.0
	 */
	trait Api_Client_Access {
		/**
		 * Single instance of the class
		 *
		 * @var Api_Client
		 */
		protected static $client = null;

		/**
		 * Returns single instance of the class
		 *
		 * @return Api_Client
		 */
		public static function get_api() {
			if ( is_null( self::$client ) ) {
				static::$client = Api_Client::get_instance();
			}

			return static::$client;
		}

		/**
		 * Wrapper for YITH\StripeClient\Client::call
		 *
		 * @param string $method   HTTP method.
		 * @param string $endpoint Endpoint to call.
		 * @param array  $payload  Array of parameters to send with request.
		 * @param array  $args     Additional arguments for the HTTP call.
		 *
		 * @return array|bool Status of the operation as a bool; if connection succeeded and server answered sent an answer,
		 *                    a json_decode version of the body will be returned
		 * @throws \Exception When an error occurs with remove request on the server.
		 */
		public static function call( $method, $endpoint, $payload = array(), $args = array() ) {
			return StripeClient::call( $method, $endpoint, $payload, $args );
		}
	}
}
