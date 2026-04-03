<?php
/**
 * Rest API main class
 *
 * @author  YITH
 * @package YITH\StripeClient
 * @version 1.0.0
 */

namespace YITH\StripeClient\RestApi;

use YITH\StripeClient\RestApi\Controllers\Abstracts\Controller as Controller;

defined( 'YITH_STRIPE_CLIENT_DIR' ) || exit;

if ( ! class_exists( 'YITH\StripeClient\RestApi\Main' ) ) {
	/**
	 * Defines and initialize all REST endpoints available within current library
	 *
	 * @since 1.0.0
	 */
	class Main {

		/**
		 * Base path for the REST API defined by this library
		 *
		 * @var string
		 */
		public static $rest_base = 'yith/stripe-client';

		/**
		 * Array of available application routes
		 *
		 * @var string[]
		 */
		protected static $available_routes = array(
			'verify',
		);

		/**
		 * Array of controller instances.
		 *
		 * @var Controller[]
		 */
		protected $controllers = array();

		/**
		 * Constructor method
		 */
		public function __construct() {
			add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );

			$this->init_controllers();
		}

		/**
		 * Init controllers objects for easy aaccess further down the line.
		 */
		public function init_controllers() {
			foreach ( self::$available_routes as $controller ) {
				$controller_class  = __NAMESPACE__ . '\Controllers\\' . ucfirst( $controller );
				$controller_object = new $controller_class();

				$this->controllers[ $controller ] = $controller_object;
			}
		}

		/**
		 * Register rest routes defined by available controllers
		 */
		public function register_rest_routes() {
			foreach ( $this->controllers as $controller ) {
				$controller->register_rest_routes();
			}
		}

		/**
		 * Returns unique instance of endpoint controller
		 *
		 * @param string $controller Controller to find.
		 * @return Controller Controller object.
		 */
		public function get_controller( $controller ) {
			if ( ! isset( $this->controllers[ $controller ] ) ) {
				return null;
			}

			return $this->controllers[ $controller ];
		}

		/**
		 * Returns url for an endpoint in current API
		 *
		 * @param string $endpoint Endpoint to use to generate url.
		 * @return string Generated url
		 */
		public static function get_rest_url( $endpoint ) {
			$parts = array_filter(
				array(
					trim( self::$rest_base, '/' ),
					$endpoint,
				)
			);

			return get_rest_url( null, implode( '/', $parts ) );
		}
	}
}
