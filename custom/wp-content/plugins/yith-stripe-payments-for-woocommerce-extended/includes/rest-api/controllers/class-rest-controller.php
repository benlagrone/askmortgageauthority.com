<?php
/**
 * REST Controller
 *
 * @author  YITH
 * @package YITH\StripePayments\RestApi\Controllers
 * @version 1.0.0
 */

namespace YITH\StripePayments\RestApi\Controllers;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\RestApi\Controllers\Rest_Controller' ) ) {
	/**
	 * Abstract Rest Controller Class
	 */
	abstract class Rest_Controller {

		/**
		 * Endpoint namespace.
		 *
		 * @var string
		 */
		protected $namespace = 'yith/stripe-payments';

		/**
		 * Route base.
		 *
		 * @var string
		 */
		protected $rest_base = '';

		/**
		 * Used to cache computed return fields.
		 *
		 * @var null|array
		 */
		private $fields = null;

		/**
		 * Request object
		 * Used to verify if cached fields are for correct request object.
		 *
		 * @var null|\WP_REST_Request
		 */
		protected $request = null;

		/**
		 * Response object
		 * Used to verify if cached fields are for correct request object.
		 *
		 * @var null|\WP_REST_Response
		 */
		protected $response = null;

		/**
		 * Registers the routes for the objects of the controller.
		 *
		 * @since 4.7.0
		 *
		 * @see   register_rest_route()
		 */
		public function register_routes() {
			register_rest_route(
				$this->namespace,
				$this->rest_base,
				array(
					array(
						'methods'             => \WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'handle_request' ),
						'permission_callback' => '__return_true',
					),
					'schema' => array( $this, 'get_schema' ),
				),
				true
			);
		}

		/**
		 * Verify the source authentication
		 *
		 * @param \WP_REST_Request $request Request object to verify.
		 * @return bool
		 */
		protected function verify( $request ) {
			return true;
		}

		/**
		 * Verify the source authentication
		 *
		 * @return array|false
		 */
		public function get_schema() {
			return false;
		}

		/**
		 * Handle the endpoint request.
		 *
		 * @param \WP_REST_Request $request REST request instance.
		 *
		 * @return \WP_REST_Response instance if the index was found.
		 */
		public function handle_request( $request ) {
			$this->request  = rest_ensure_request( $request );
			$this->response = new \WP_REST_Response();

			if ( ! $this->verify( $this->request ) ) {
				$this->response->set_status( 403 );
				$this->response->set_data(
					array(
						// response to server, no translation required.
						'message' => 'Can\'t verify request',
						'code'    => 'signature_verification_error',
					)
				);
			}

			return $this->response;
		}
	}
}
