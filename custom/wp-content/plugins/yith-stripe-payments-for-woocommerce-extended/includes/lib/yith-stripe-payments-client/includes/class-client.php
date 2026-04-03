<?php
/**
 * API client
 *
 * @author  YITH
 * @package YITH\StripeClient
 * @version 1.0.0
 */

namespace YITH\StripeClient;

use Ramsey\Uuid\Uuid;
use YITH\StripeClient\Processors\Curl;
use YITH\StripeClient\Processors\Guzzle;
use YITH\StripeClient\Exceptions\Api_Exception;
use YITH\StripeClient\Exceptions\Processor_Exception;

defined( 'YITH_STRIPE_CLIENT_DIR' ) || exit;

if ( ! class_exists( 'YITH\StripeClient\Client' ) ) {
	/**
	 * Stripe Payments API client
	 *
	 * @since 1.0.0
	 */
	class Client {

		/**
		 * Request signature TTL
		 *
		 * @const int
		 */
		const SIGNATURE_TTL = 10 * MINUTE_IN_SECONDS;

		/**
		 * API url where to contact external service for live applications
		 *
		 * @var string
		 */
		protected static $production_api_url = 'https://payments.yithemes.com/api';

		/**
		 * API url where to contact external service for test applications
		 *
		 * @var string
		 */
		protected static $staging_api_url = 'https://staging-payments.yithemes.com/api';

		/**
		 * Default max timeout
		 *
		 * @var int
		 */
		protected static int $timeout = 30;

		/**
		 * Environment for requests processed
		 *
		 * @var array
		 */
		protected static array $options = array();

		/**
		 * Processor used to perform RESTful API calls
		 * It could be either WP_HTTP (external) or Guzzle (included)
		 *
		 * @var Curl|Guzzle
		 */
		protected static $processor;

		/**
		 * String that registers last message from the processor.
		 *
		 * @var string
		 */
		protected static $log = '';

		/**
		 * Last error object (it is reset for every new call).
		 *
		 * @var \Exception
		 */
		protected static $last_error;

		/**
		 * Init client with default options; can also be passed a set ot options to be merged with defaults
		 *
		 * @param array $options Options to use for client initialization.
		 */
		public static function maybe_init( $options = array() ) {
			if ( ! empty( self::$options ) ) {
				return;
			}

			self::$options = array_merge(
				array(
					'auth'        => false,
					'user-agent'  => 'YITH\StripeClient\\' . YITH_STRIPE_CLIENT_VERSION,
					'environment' => defined( 'WP_ENV' ) && 'development' === \WP_ENV ? 'test' : 'live',
					'brand'       => false,
				),
				$options
			);
		}

		/**
		 * Returns value for a specific option of the client
		 *
		 * @param string $prop Option to retrieve.
		 *
		 * @return mixed Option value.
		 */
		public static function get( $prop ) {
			// init options if needed.
			self::maybe_init();

			if ( ! isset( self::$options[ $prop ] ) ) {
				return false;
			}

			return self::$options[ $prop ];
		}

		/**
		 * Allow users to set options for connection to the server.
		 *
		 * @param string $prop  Option to set.
		 * @param mixed  $value Value of the option.
		 */
		public static function set( $prop, $value ) {
			// init options if needed.
			self::maybe_init();

			if ( ! isset( self::$options[ $prop ] ) ) {
				return;
			}

			self::$options[ $prop ] = $value;
		}

		/**
		 * Returns current running environment
		 *
		 * @return string Test or Live, depending on the value set for the client's options.
		 */
		public static function get_env() {
			return apply_filters( 'yith_stripe_client_env', self::get( 'environment' ) );
		}

		/**
		 * Returns current brand ID.
		 *
		 * @return string Brand ID.
		 */
		public static function get_brand() {
			return apply_filters( 'yith_stripe_client_brand', self::get( 'brand' ) );
		}

		/**
		 * Returns user agent applied to all requests of the client
		 *
		 * @return string User agent currently set in client's options.
		 */
		public static function get_user_agent() {
			return apply_filters( 'yith_stripe_client_user_agent', self::get( 'user-agent' ) );
		}

		/**
		 * Returns API url where to contact external service
		 *
		 * @var string
		 */
		public static function get_api_url() {
			$test = defined( 'YITH_STRIPE_CLIENT_DEBUG' ) && true === \YITH_STRIPE_CLIENT_DEBUG;
			$url  = $test ? self::$staging_api_url : self::$production_api_url;

			return apply_filters( 'yith_stripe_client_api_url', $url );
		}

		/**
		 * Retrieves instance of the requests processor
		 *
		 * @return Curl|Guzzle
		 * @throws \Exception When something fails during initial processor creation.
		 */
		public static function get_processor() {
			$processor_class = class_exists( 'WP_Http' ) ? 'curl' : 'guzzle';
			$processor_class = ucfirst( $processor_class );
			$processor_class = apply_filters( 'yith_stripe_client_processor_class', "YITH\StripeClient\Processors\\$processor_class" );
			$processor       = new $processor_class();

			self::$processor = apply_filters( 'yith_stripe_client_processor', $processor );
			self::$processor->maybe_connect( self::get_api_url() );

			return self::$processor;
		}

		/**
		 * Returns last valid signature key created by this library
		 *
		 * @return string|bool Last signature key, if any was produced within last 10 minutes; false otherwise.
		 */
		public static function get_last_signature() {
			$last_signature = get_transient( 'yith_stripe_client_last_request_signature' );

			if ( ! $last_signature ) {
				return false;
			}

			return $last_signature;
		}

		/**
		 * Returns messages from the Processor
		 * It may contain success answers, or error messages.
		 *
		 * @return string
		 */
		public static function get_message_log() {
			return self::$log;
		}

		/**
		 * Returns last error object, or null if last request didn't produce any.
		 *
		 * @return \Exception
		 */
		public static function get_last_error() {
			return self::$last_error;
		}

		/**
		 * Format endpoint replacing its placeholders
		 *
		 * @param string $endpoint The endpoint to format.
		 *
		 * @return string
		 */
		public static function format_endpoint( $endpoint ) {
			$placeholders = array_filter( self::get_endpoint_placeholders() );

			return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $endpoint );
		}

		/**
		 * Check if the endpoint has any unsolved placeholders
		 *
		 * @param string $endpoint The endpoint to check.
		 *
		 * @return bool
		 */
		public static function has_unsolved_placeholders( $endpoint ) {
			return ! ! preg_match( '/' . implode( '|', array_keys( self::get_endpoint_placeholders() ) ) . '/', $endpoint );
		}

		/**
		 * Execute HTTP request to API server
		 *
		 * @param string $method   HTTP method.
		 * @param string $endpoint Endpoint to call.
		 * @param array  $payload  Array of parameters to send with request.
		 * @param array  $args     Additional arguments for the HTTP call.
		 *
		 * @return array|bool Status of the operation as a bool; if connection succeeded and server answered sent an answer,
		 *                    a json_decode version of the body will be returned
		 * @throws \Exception When external API fails.
		 */
		public static function call( $method, $endpoint, $payload = array(), $args = array() ) {
			try {
				return self::process_request( $method, $endpoint, $payload, $args );
			} catch ( \Exception $e ) {
				self::process_error( $e, $method, $endpoint, $payload );
			}
		}

		/**
		 * Adds header to the request, to send unique signature ID with each call.
		 *
		 * @param array $args Array of arguments for the request.
		 *
		 * @return array Filtered array of arguments.
		 */
		protected static function add_signature_header( $args ) {
			if ( ! isset( $args[ 'headers' ] ) ) {
				$args[ 'headers' ] = array();
			}

			// generates unique request signature.
			$signature = Uuid::uuid4()->toString();

			// sets signature inside request headers.
			$args[ 'headers' ][ 'X-Request-Signature' ] = $signature;

			// register signature in a transient to be later verified.
			set_transient( 'yith_stripe_client_last_request_signature', $signature, self::SIGNATURE_TTL );

			return $args;
		}

		/**
		 * Adds header to the request, to send unique signature ID with each call.
		 *
		 * @param array $args Array of arguments for the request.
		 *
		 * @return array Filtered array of arguments.
		 */
		protected static function maybe_add_auth_header( $args ) {
			if ( ! isset( $args[ 'headers' ] ) ) {
				$args[ 'headers' ] = array();
			}

			// generates unique request signature.
			$auth = self::get( 'auth' );

			if ( ! $auth ) {
				return $args;
			}

			// sets signature inside request headers.
			$args[ 'headers' ][ 'Authorization' ] = "Bearer $auth";

			return $args;
		}

		/**
		 * Process request with specified parameters
		 * Throws an exception in case of unexpected answer
		 *
		 * @param string $method   HTTP method.
		 * @param string $endpoint Endpoint to call.
		 * @param array  $payload  Array of parameters to send with request.
		 * @param array  $args     Additional arguments for the HTTP call.
		 *
		 * @return array|bool Status of the operation as a bool; if connection succeeded and server answered sent an answer,
		 *                    a json_decode version of the body will be returned
		 *
		 * @throws \Exception When call returns unexpected status.
		 */
		protected static function process_request( $method, $endpoint, $payload, $args ) {
			if ( self::has_unsolved_placeholders( $endpoint ) ) {
				throw new \Exception( 'It\'s not possible to perform a call on an endpoint with an unsolved placeholder' );
			}

			$method = strtoupper( $method );
			$body   = 'GET' === $method ? $payload : http_build_query( $payload );

			$args = array_merge(
				array(
					'timeout'            => self::$timeout,
					'reject_unsafe_urls' => true,
					'blocking'           => true,
					'sslverify'          => true,
					'attempts'           => 0,
					'user-agent'         => self::get( 'user-agent' ),
				),
				$args,
				array(
					'method' => $method,
					'body'   => $body,
				)
			);

			$args = self::add_signature_header( $args );
			$args = self::maybe_add_auth_header( $args );

			return self::process_answer( self::get_processor()->call( $method, $endpoint, $args ), $method, $endpoint, $payload );
		}

		/**
		 * Process answer from the server, and schedule retries when needed
		 *
		 * @param \WP_Error|array $response Value returned from {@see wp_remote_request}.
		 * @param string          $method   HTTP method.
		 * @param string          $endpoint Endpoint to call.
		 * @param array           $payload  Array of parameters to send with request.
		 *
		 * @return array|bool Status of the operation as a bool; if connection succeeded and server answered sent an answer,
		 *                    a json_decode version of the body will be returned
		 *
		 * @throws \Exception When call returns unexpected status.
		 */
		protected static function process_answer( $response, $method, $endpoint, $payload ) {
			$message = "{$method} /{$endpoint}";

			// reset last error message.
			self::$last_error = null;

			if ( is_wp_error( $response ) ) {
				// connection failed.
				$message      .= " {$response->get_error_message()}";
				$return_value = new Processor_Exception( $response->get_error_message() );
			} else {
				// server returned a status code.
				$body   = isset( $response[ 'body' ] ) ? json_decode( $response[ 'body' ], true ) : true;
				$status = isset( $response[ 'response' ] ) ? $response[ 'response' ][ 'code' ] : false;

				switch ( $status ) {
					case 200: // Found.
						$return_value = $body;
						break;
					default: // Unrecognized Status.
						$message = isset( $body[ 'message' ] ) ? sanitize_text_field( wp_unslash( $body[ 'message' ] ) ) : print_r( $body, 1 );
						$code    = isset( $body[ 'code' ] ) ? $body[ 'code' ] : 'unknown';
						$details = array(
							'path'     => $endpoint,
							'method'   => $method,
							'payload'  => $payload,
							'response' => $response,
							'severity' => isset( $body[ 'severity' ] ) ? $body[ 'severity' ] : '',
							'errors'   => isset( $body[ 'errors' ] ) ? $body[ 'errors' ] : array(),
							'data'     => isset( $body[ 'data' ] ) ? $body[ 'data' ] : array(),
						);

						$return_value = new Api_Exception( $message, $status, $code, $details );
				}

				$message .= " {$status}\n";
			}

			if ( ! empty( $payload ) ) {
				$message .= print_r( $payload, 1 );
				$message .= "\n";
			}

			if ( ! empty( $body ) ) {
				$message .= print_r( $body, 1 );
				$message .= "\n";
			}

			// log operation.
			self::$log = $message;

			// if we generated an exception, throw it.
			if ( $return_value instanceof \Exception ) {
				self::$last_error = $return_value;
				throw $return_value;
			}

			return $return_value;
		}

		/**
		 * Handle error conditions (stores error log and throws exception)
		 *
		 * @param \Exception $error    Error object.
		 * @param string     $method   HTTP method.
		 * @param string     $endpoint Endpoint to call.
		 * @param array      $payload  Array of parameters to send with request.
		 *
		 * @throws \Exception To allow invoker to process error handling.
		 */
		protected static function process_error( $error, $method, $endpoint, $payload ) {
			$error_message = $error->getMessage();
			$error_code    = $error->getCode();

			// log operation.
			$message = "{$method} /{$endpoint} {$error_code}\n{$error_message}\n";
			$message .= print_r( $payload, 1 );

			self::$log = $message;

			throw $error;
		}

		protected static function get_endpoint_placeholders() {
			return array(
				':brand' => self::get_brand(),
				':env'   => self::get_env(),
			);
		}
	}
}
