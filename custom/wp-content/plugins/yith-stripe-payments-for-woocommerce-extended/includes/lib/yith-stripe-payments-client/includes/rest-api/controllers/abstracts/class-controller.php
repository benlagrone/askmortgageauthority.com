<?php
/**
 * Abstract controller
 * Base controller extended by specific objects
 *
 * @author  YITH
 * @package YITH\StripeClient\Controllers
 * @version 1.0.0
 */

namespace YITH\StripeClient\RestApi\Controllers\Abstracts;

use YITH\StripeClient\RestApi\Main as Server;

defined( 'YITH_STRIPE_CLIENT_DIR' ) || exit;

if ( ! class_exists( 'YITH\StripeClient\Controllers\Controller' ) ) {
	/**
	 * Representation of account instance
	 *
	 * @since 1.0.0
	 */
	abstract class Controller {

		/**
		 * Base path for this endpoint
		 *
		 * @var string
		 */
		public static $rest_path;

		/**
		 * Register REST routes for current controller
		 * {@see register_rest_route()}.
		 */
		abstract public function register_rest_routes();

		/**
		 * Returns url to current endpoint, completed with an optional trailing path
		 *
		 * @param string $path Additional path to append to the endpoint.
		 * @return string Url to endpoint controller by current class.
		 */
		public function get_rest_url( $path = '' ) {
			$parts = array_filter(
				array(
					trim( static::$rest_path, '/' ),
					$path,
				)
			);

			return Server::get_rest_url( implode( '/', $parts ) );
		}
	}
}
