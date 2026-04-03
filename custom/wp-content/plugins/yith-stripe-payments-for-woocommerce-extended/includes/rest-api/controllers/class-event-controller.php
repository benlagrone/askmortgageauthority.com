<?php
/**
 * Stripe Events controller class
 */

namespace YITH\StripePayments\RestApi\Controllers;

use YITH\StripePayments\Gateways\Abstracts\Gateway;

if ( ! class_exists( 'YITH\StripePayments\RestApi\Controllers\Event_Controller' ) ) {
	/**
	 * Stripe Events controller
	 */
	abstract class Event_Controller {

		/**
		 * The Stripe event type
		 *
		 * @var string
		 */
		protected $event = '';

		/**
		 * The request parameters
		 *
		 * @var array
		 */
		protected $params = array();

		/**
		 * The Stripe event object
		 *
		 * @var array
		 */
		protected $event_data = array();

		/**
		 * The request response data
		 *
		 * @var array
		 */
		protected $response = array();

		/**
		 * Handle the Stripe Event.
		 *
		 * @param string $event  The stripe event.
		 * @param array  $params The request params.
		 *
		 * @return array
		 */
		public function handle_event( $event, $params ) {
			$method = str_replace( array( $this->event . '.', '.' ), array( '', '_' ), $event );
			$this->populate( $params );

			if ( ! $this->is_valid() ) {
				throw new \Exception( "The request provided data didn't pass the validation" );
			}

			if ( ! is_callable( array( $this, $method ) ) ) {
				throw new \Exception( 'The requested event ' . $event . ' is not handled by webhooks' );
			}

			$this->{$method}();

			if ( empty( $this->response['message'] ) ) {
				$this->response['message'] = 'Webhook processed correctly';
			}

			return $this->response;
		}

		/**
		 * Return the event data
		 *
		 * @return array
		 */
		public function get_event_object_data() {
			return ! empty( $this->params['data']['object'] ) ? $this->params['data']['object'] : array();
		}

		/**
		 * Populate the object property using the request params
		 *
		 * @param array $params The request params.
		 *
		 * @return void
		 */
		protected function populate( $params ) {
			$this->params     = $params;
			$this->event_data = $this->get_event_object_data();
		}

		/**
		 * Add an action to the response
		 *
		 * @param array $action_data The action data.
		 *
		 * @return void
		 */
		protected function add_response_action( $action_data ) {
			if ( ! isset( $this->response['actions'] ) || ! is_array( $this->response['actions'] ) ) {
				$this->response['actions'] = array();
			}
			$this->response['actions'][] = $action_data;
		}

		/**
		 * Conditionals
		 */

		/**
		 * Check if the request is valid
		 *
		 * @return bool
		 */
		protected function is_valid() {
			$is_valid = false;
			if ( isset( $this->event_data['metadata']['instance'] ) ) {
				$is_valid = $this->event_data['metadata']['instance'] === Gateway::get_instance();
			}

			return $is_valid;
		}

		/**
		 * Check if the request was made in livemode
		 *
		 * @return bool
		 */
		protected function is_livemode() {
			return isset( $this->params['livemode'] ) ?? false;
		}
	}
}
