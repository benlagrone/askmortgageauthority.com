<?php
/**
 * Handler class that performs correct action when required (it searches for known action nonce in the REQUEST).
 *
 * @author  YITH
 * @package YITH\StripePayments\Classes
 * @version 1.0.0
 */

namespace YITH\StripePayments\RestApi;

use YITH\StripePayments\Traits\Singleton;

if ( ! class_exists( 'YITH\StripePayments\RestApi\Listener' ) ) {
	/**
	 * Registers available REST API controllers
	 */
	class Listener {

		use Singleton;

		/**
		 * REST API namespaces and endpoints.
		 *
		 * @var array
		 */
		protected $controllers = array();

		/**
		 * YITH\StripePayments\RestApi\Listener Class constructor
		 */
		protected function __construct() {
			add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
		}

		/**
		 * Get the namespace that needs to be registered
		 *
		 * @return array[]
		 */
		protected function get_rest_namespaces() {
			return apply_filters(
				'yith_stripe_payments_rest_namespaces',
				array(
					'stripe-payments' => $this->get_stripe_payments_controllers(),
				)
			);
		}

		/**
		 * Instance the controller classes that will register the Rest Routes
		 */
		public function register_rest_route() {
			foreach ( $this->get_rest_namespaces() as $namespace => $controllers ) {
				foreach ( $controllers as $controller_name => $controller_class ) {
					if ( class_exists( $controller_class ) ) {
						$this->controllers[ $namespace ][ $controller_name ] = new $controller_class();
						$this->controllers[ $namespace ][ $controller_name ]->register_routes();
					}
				}
			}
		}

		/**
		 * List of controllers in the stripe-payments controllers.
		 *
		 * @return array
		 */
		protected function get_stripe_payments_controllers() {
			return apply_filters(
				'yith_stripe_payments_rest_controllers',
				array(
					'webhooks' => 'YITH\StripePayments\RestApi\Controllers\Webhooks_Controller',
				)
			);
		}

	}
}
