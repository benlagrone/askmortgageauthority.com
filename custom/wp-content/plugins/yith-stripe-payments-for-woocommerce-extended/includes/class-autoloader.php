<?php
/**
 * Autoloader.
 *
 * @package YITH\StripePayments\Classes
 * @version 1.0.0
 */

namespace YITH\StripePayments;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Autoloader' ) ) {
	/**
	 * Autoloader class.
	 */
	class Autoloader {

		/**
		 * Path to the includes directory.
		 *
		 * @var string
		 */
		private $include_path = '';

		/**
		 * The Constructor.
		 */
		public function __construct() {
			if ( function_exists( '__autoload' ) ) {
				spl_autoload_register( '__autoload' );
			}

			spl_autoload_register( array( $this, 'autoload' ) );

			$this->include_path = YITH_STRIPE_PAYMENTS_INC;
		}

		/**
		 * Returns path components for current class
		 *
		 * @param string $class Class to retrieve.
		 * @return array Array of path components for the class to retrieve, based on namespace provided.
		 */
		private function get_path_components( $class ) {
			$components = explode( '\\', str_replace( __NAMESPACE__, '', $class ) );
			$components = array_filter(
				array_map(
					function( $component ) {
						$component = preg_replace( '/(?<!^)[A-Z]/', '-$0', $component );
						$component = str_replace( '_', '', $component );

						return strtolower( $component );
					},
					$components
				)
			);

			$basename     = array_pop( $components );
			$components[] = $this->get_base_file( $basename, $components );

			return $components;
		}

		/**
		 * Returns name of the file containing searched class
		 *
		 * @param string $base       Basename of the class.
		 * @param array  $components Array of path components as calculated by {@see Autoloader::get_path_components()}.
		 *
		 * @return string Formatted file name.
		 */
		private function get_base_file( $base, $components ) {
			if ( in_array( 'interfaces', $components, true ) ) {
				$filename = 'interface-' . $base . '.php';
			} elseif ( in_array( 'traits', $components, true ) ) {
				$filename = 'trait-' . $base . '.php';
			}

			if ( empty( $filename ) ) {
				$filename = 'class-' . $base . '.php';
			}

			return $filename;
		}

		/**
		 * Take a class name and turn it into a file name.
		 *
		 * @param string $class Class name.
		 *
		 * @return string
		 */
		private function get_file_from_class( $class ) {
			$components = $this->get_path_components( $class );
			$path       = implode( DIRECTORY_SEPARATOR, $components );

			return $this->include_path . $path;
		}

		/**
		 * Include a class file.
		 *
		 * @param string $path File path.
		 *
		 * @return bool Successful or not.
		 */
		private function load_file( $path ) {
			if ( $path && is_readable( $path ) ) {
				include_once $path;

				return true;
			}

			return false;
		}

		/**
		 * Auto-load plugins' classes on demand to reduce memory consumption.
		 *
		 * @param string $class Class name.
		 */
		public function autoload( $class ) {
			if ( 0 !== strpos( $class, __NAMESPACE__ ) ) {
				return;
			}

			$this->load_file( $this->get_file_from_class( $class ) );
		}
	}
}

new Autoloader();
