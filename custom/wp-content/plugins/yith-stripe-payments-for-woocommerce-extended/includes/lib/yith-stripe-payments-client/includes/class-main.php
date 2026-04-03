<?php
/**
 * Main library class
 *
 * @author  YITH
 * @package YITH\StripeClient
 * @version 1.0.0
 */

namespace YITH\StripeClient;

use YITH\StripeClient\RestApi\Main as RestApiHandler;

defined( 'YITH_STRIPE_CLIENT_DIR' ) || exit;

if ( ! class_exists( 'YITH\StripeClient\Main' ) ) {
	/**
	 * Stripe Payments API main class
	 *
	 * @since 1.0.0
	 */
	class Main {
		/**
		 * Single instance of this class
		 *
		 * @var Main
		 */
		protected static $instance;

		/**
		 * Rest API handler.
		 *
		 * @var RestApiHandler.
		 */
		protected $api_handler;

		/**
		 * Constructor method.
		 */
		public function __construct() {
			// init Rest API.
			$this->api_handler = new RestApiHandler();
		}

		/**
		 * Returns API handler
		 *
		 * @return RestApiHandler
		 */
		public function get_api() {
			return $this->api_handler;
		}

		/**
		 * Returns unique instance of this class
		 *
		 * @return Main
		 */
		public static function instance() {
			if ( ! self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}
}
