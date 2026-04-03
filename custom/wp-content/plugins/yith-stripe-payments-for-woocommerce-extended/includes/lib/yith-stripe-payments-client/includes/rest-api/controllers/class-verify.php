<?php
/**
 * Rest API - Verify Endpoint controller
 *
 * @author  YITH
 * @package YITH\StripeClient
 * @version 1.0.0
 */

namespace YITH\StripeClient\RestApi\Controllers;

use YITH\StripeClient\Client as Client;
use YITH\StripeClient\RestApi\Main as Server;
use YITH\StripeClient\RestApi\Controllers\Abstracts\Controller as Controller;

defined( 'YITH_STRIPE_CLIENT_DIR' ) || exit;

if ( ! class_exists( 'YITH\StripeClient\RestApi\Controllers\Verify' ) ) {
	/**
	 * Defines and initialize all REST endpoints available within current library
	 *
	 * @since 1.0.0
	 */
	class Verify extends Controller {
		/**
		 * Base path for this endpoint
		 *
		 * @var string
		 */
		public static $rest_path = '/verify';

		/**
		 * Register REST routes for current controller
		 */
		public function register_rest_routes() {
			register_rest_route(
				Server::$rest_base,
				self::$rest_path,
				array(
					array(
						'methods'             => \WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'verify' ),
						'permission_callback' => '__return_true',
						'args'                => array(
							'signature' => array(
								'description'       => __( 'Unique ID of the request to validate', 'yith-stripe-client' ),
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_key',
								'validate_callback' => 'rest_validate_request_arg',
							),
						),
					),
				)
			);
		}

		/**
		 * Verify request signature
		 *
		 * @param \WP_REST_Request $request Full details about the request.
		 * @return \WP_Error|\WP_REST_Response
		 */
		public function verify( $request ) {
			// retrieve last valid signature key.
			$signature = Client::get_last_signature();

			// retrieve request parameters.
			$request_parameters = $request->get_params();

			return rest_ensure_response(
				array(
					'verified' => $signature && isset( $request_parameters['signature'] ) && $signature === $request_parameters['signature'],
					'request'  => $request_parameters,
				)
			);
		}
	}
}
