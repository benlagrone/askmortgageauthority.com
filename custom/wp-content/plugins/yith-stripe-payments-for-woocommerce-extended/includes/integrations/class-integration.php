<?php
/**
 * Integration model class
 *
 * @author  YITH
 * @package YITH\StripePayments\Classes
 * @version 1.0.0
 */

namespace YITH\StripePayments\Integrations;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Integrations\Integration' ) ) {
	abstract class Integration {
		/**
		 * Initialized flag.
		 *
		 * @var bool
		 */
		protected $initialized = false;

		/**
		 * Initialization
		 */
		public function init_once() {
			if ( ! $this->initialized ) {
				$this->init();
				$this->initialized = true;
			}
		}

		/**
		 * Initialization
		 */
		protected function init() {

		}

		/**
		 * Plugin data.
		 *
		 * @var array
		 */
		protected $data = array(
			'key'               => '',
			'constant'          => '',
			'installed_version' => '',
			'min_version'       => '',
			'version_compare'   => '>=',
		);

		/**
		 * Set the integration data.
		 *
		 * @param array $integration_data The integration data.
		 */
		public function set_data( array $integration_data ) {
			foreach ( $this->data as $key => $value ) {
				if ( isset( $integration_data[ $key ] ) ) {
					$this->data[ $key ] = $integration_data[ $key ];
				}
			}
		}

		/**
		 * Get property
		 *
		 * @param string $prop The property.
		 *
		 * @return mixed|null
		 */
		public function get_prop( $prop ) {
			return array_key_exists( $prop, $this->data ) ? $this->data[ $prop ] : null;
		}

		/**
		 * Get the key.
		 *
		 * @return string
		 */
		public function get_key() {
			return $this->get_prop( 'key' );
		}

		/**
		 * Get the installed_version.
		 *
		 * @return string
		 */
		public function get_installed_version() {
			return $this->get_prop( 'installed_version' );
		}

		/**
		 * Get the min_version.
		 *
		 * @return string
		 */
		public function get_min_version() {
			return $this->get_prop( 'min_version' );
		}

		/**
		 * Get the version_compare.
		 *
		 * @return string
		 */
		public function get_version_compare() {
			return $this->get_prop( 'version_compare' );
		}

		/**
		 * Get the constant.
		 *
		 * @return string
		 */
		public function get_constant() {
			return $this->get_prop( 'constant' );
		}
	}
}
