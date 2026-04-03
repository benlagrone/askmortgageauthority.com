<?php
/**
 * Integrations handler class
 *
 * @author  YITH
 * @package YITH\StripePayments\Classes
 * @version 1.0.0
 */

namespace YITH\StripePayments\Integrations;

use YITH\StripePayments\Traits\Singleton;
use YITH\StripePayments\Integrations\Integration as Integration;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Integrations\Handler' ) ) {
	class Handler {
		use Singleton;

		/**
		 * Integrations list.
		 *
		 * @var array
		 */
		protected $integrations_list;

		private function __construct() {
			add_action( 'plugins_loaded', array( $this, 'load_integrations' ), 15 );
		}

		/**
		 * Get the integrations list.
		 *
		 * @return array
		 */
		public function get_integrations_list() {
			if ( is_null( $this->integrations_list ) ) {
				$this->integrations_list = require_once __DIR__ . '/integrations-list.php';
			}

			return $this->integrations_list;
		}

		public function load_integrations() {
			foreach ( $this->get_integrations_list() as $key => $integration_data ) {
				$classname                 = 'YITH\\StripePayments\\Integrations\\Plugins\\' . str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $key ) ) );
				$integration_data[ 'key' ] = $key;

				if ( $this->has_integration( $key ) && class_exists( $classname ) && method_exists( $classname, 'get_instance' ) ) {
					/**
					 * The integration.
					 *
					 * @var Integration $integration The integration class.
					 */
					$integration = $classname::get_instance();
					$integration->set_data( $integration_data );
					$integration->init_once();
				}
			}
		}

		/**
		 * Check if the integration has to be active
		 *
		 * @param string $key The integration key
		 *
		 * @return bool
		 */
		public function has_integration( $key ) {
			$integration_list = $this->get_integrations_list();
			$has              = false;

			if ( array_key_exists( $key, $integration_list ) ) {
				$integration = $integration_list[ $key ];

				if ( isset( $integration[ 'constant' ] ) && defined( $integration[ 'constant' ] ) && constant( $integration[ 'constant' ] ) ) {
					$installed_version = $integration[ 'installed_version' ] ?? false;
					$min_version       = $integration[ 'min_version' ] ?? false;

					$has = $min_version && $installed_version && defined( $installed_version ) && version_compare( constant( $installed_version ), $min_version, $integration[ 'version_compare' ] ?? '>=' );
				}
			}

			return $has;
		}
	}
}
