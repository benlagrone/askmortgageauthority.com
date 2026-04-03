<?php
/**
 * Webhooks controller class
 *
 * @author  YITH
 * @package YITH\StripePayments\RestApi\Controllers
 * @version 1.0.0
 */

namespace YITH\StripePayments\RestApi\Controllers;

use YITH\StripePayments\Traits\Logger;
use YITH\StripePayments\Account;

if ( ! class_exists( 'YITH\StripePayments\RestApi\Controllers\Webhooks_Controller' ) ) {
	/**
	 * Webhooks Controller Class
	 * This class is responsible to handle all calls to /wp-json/yith/stripe-payments/webhooks endpoint.
	 * Stripe payments server will direct there all webhook requests coming from stripe for this account.
	 */
	class Webhooks_Controller extends Rest_Controller {

		use Logger;

		/**
		 * Number of seconds an event can be considered valid, from generation.
		 *
		 * @const int
		 */
		const EVENT_TTL = 5 * MINUTE_IN_SECONDS;

		/**
		 * Route base.
		 *
		 * @var string
		 */
		protected $rest_base = 'webhooks';

		/**
		 * Handled events
		 *
		 * @var string[]
		 */
		protected static $events = array(
			'account.updated',
			'charge.captured',
			'charge.dispute.created',
			'charge.dispute.closed',
			'charge.expired',
			'charge.failed',
			'charge.refunded',
			'charge.succeeded',
			'charge.updated',
			'customer.deleted',
			'payment_intent.canceled',
			'payment_intent.created',
			'payment_intent.payment_failed',
			'payment_intent.requires_action',
			'payment_intent.succeeded',
		);

		/**
		 * Handle the endpoint request.
		 *
		 * @param \WP_REST_Request $request REST request instance.
		 *
		 * @return \WP_REST_Response
		 */
		public function handle_request( $request ) {
			$response = parent::handle_request( $request );

			// if response has status other than 200, stop here.
			if ( 200 !== $response->get_status() ) {
				$this->log_request();

				return $response;
			}

			$params = $request->get_params();

			if ( $params ) {
				$event = $params['type'] ?? '';

				try {
					if ( ! in_array( $event, self::$events, true ) ) {
						throw new \Exception( 'Not found `' . $event . '` Stripe event in allowed events list', 200 );
					}

					$controller_class = 'YITH\StripePayments\RestApi\Controllers\\' . ucwords( substr( $event, 0, strpos( $event, '.' ) ), '_' ) . '_Controller';
					if ( ! class_exists( $controller_class ) ) {
						throw new \Exception( 'No controller found for the `' . $event . '` Stripe event.', 200 );
					}

					$controller = new $controller_class();
					$response->set_data( $controller->handle_event( $event, $params ) );
				} catch ( \Exception $e ) {
					$code = $e->getCode();
					$response->set_status( 0 === $code ? 200 : $code );
					$response->set_data( array( 'message' => $e->getMessage() ) );
				}
			}

			$this->log_request();

			return $response;
		}

		/**
		 * Get the expected schema
		 *
		 * @return array[]
		 */
		public function get_schema() {
			return array(
				'id'          => array(
					'type'              => 'string',
					'description'       => __( 'Unique stripe event ID', 'yith-stripe-payments-for-woocommerce' ),
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => array( __CLASS__, 'validate_event_id' ),
				),
				'object'      => array(
					'type'              => 'string',
					'description'       => __( 'The webhook object type', 'yith-stripe-payments-for-woocommerce' ),
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'api_version' => array(
					'type'              => 'string',
					'description'       => __( 'Stripe API Version', 'yith-stripe-payments-for-woocommerce' ),
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'created'     => array(
					'type'              => 'integer',
					'description'       => __( 'Timestamp', 'yith-stripe-payments-for-woocommerce' ),
					'sanitize_callback' => 'absint',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'data'        => array(
					'type'              => 'object',
					'description'       => __( 'The Stripe object related to the event', 'yith-stripe-payments-for-woocommerce' ),
					'validate_callback' => 'rest_validate_request_arg',
				),
				'livemode'    => array(
					'type'              => 'boolean',
					'description'       => __( 'Whatever the event has been generated in live mode (true) or test (false)', 'yith-stripe-payments-for-woocommerce' ),
					'validate_callback' => 'rest_validate_request_arg',
				),
				'type'        => array(
					'type'              => 'string',
					'description'       => __( 'The stripe event type', 'yith-stripe-payments-for-woocommerce' ),
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => array( __CLASS__, 'validate_event_type' ),
				),
			);
		}

		/**
		 * Verify the source authentication
		 *
		 * @param \WP_REST_Request $request Request object to verify.
		 *
		 * @return bool
		 */
		protected function verify( $request ) {
			$account = Account::get_instance();

			if ( ! $account->is_connected() ) {
				return false;
			}

			// retrieve relevant headers from the request.
			$secret    = $account->get_account_secret();
			$signature = $request->get_header( 'signature' );
			$timestamp = $request->get_header( 'time' );

			if ( ! $signature || ! $timestamp || ! $secret ) {
				return false;
			}

			// generates expected signature using request payload and account secret.
			$payload   = $request->get_body();
			$reference = base64_encode( hash_hmac( 'sha256', "$timestamp.$payload", $secret, true ) );

			// if reference do not match signature, immediately return.
			if ( $signature !== $reference ) {
				return false;
			}

			// finally, checks if timestamp is within expected threshold.
			return $timestamp > ( time() - self::EVENT_TTL );
		}

		/*
		|--------------------------------------------------------------------------
		| LOGGER METHODS
		|--------------------------------------------------------------------------
		 */

		/**
		 * Generates Log entry for current request/response, and registers it for future reference
		 */
		public function log_request() {
			$params = $this->request->get_params();
			$data   = $this->response->get_data();
			$type   = isset( $params['type'] ) ? $params['type'] : 'unknown';

			$formatted_request  = print_r( $params, 1 );
			$formatted_response = print_r( $data, 1 );
			$response_status    = $this->response->get_status();

			$entry = "$type $response_status\n $formatted_request\n $formatted_response";

			self::log( print_r( $entry, 1 ), 'info' );
		}

		/**
		 * Returns log source
		 *
		 * Override this method and change source to make extender class write on a separate log file
		 *
		 * @return string Log source
		 */
		public static function get_log_source() {
			return 'yith-stripe-payments-for-woocommerce-webhooks';
		}

		/*
		|--------------------------------------------------------------------------
		| SCHEMA VALIDATION METHODS
		|--------------------------------------------------------------------------
		 */

		/**
		 * Check if the event ID has a valid format
		 *
		 * @param string $id The event id.
		 *
		 * @return bool
		 */
		public static function validate_event_id( $id ) {
			return is_string( $id ) && strpos( $id, 'evt_' ) === 0 && strlen( $id ) === 28;
		}

		/**
		 * Check if the event ID has a valid format
		 *
		 * @param string $type The event type.
		 *
		 * @return bool
		 */
		public static function validate_event_type( $type ) {
			return is_string( $type ) && in_array( $type, self::$events );
		}
	}
}
