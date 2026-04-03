<?php
/**
 * Autoloader class
 *
 * @class   YITH_Shippo_Autoloader
 * @package YITH/Shippo
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_Shippo_Autoloader' ) ) {
	/**
	 * The autoloader class
	 *
	 * @class      YITH_Shippo_Autoloader
	 * @since      1.0.0
	 * @package
	 */
	class YITH_Shippo_Autoloader {

		/**
		 * Constructor
		 */
		public function __construct() {
			if ( function_exists( '__autoload' ) ) {
				spl_autoload_register( '__autoload' );
			}

			spl_autoload_register( array( $this, 'autoload' ) );
		}

		/**
		 * Get mapped file. Array of class => file to use on autoload.
		 *
		 * @return array
		 * @since  1.0.0
		 */
		protected function get_mapped_files() {
			/**
			 * APPLY_FILTERS: yith_shippo_autoload_mapped_files
			 *
			 * The filter allow to add remove the class to autoload.
			 *
			 * @param array $mapped_files The mapped files array.
			 *
			 * @return array
			 */
			return apply_filters( 'yith_shippo_autoload_mapped_files', array() );
		}

		/**
		 * Autoload callback
		 *
		 * @param string $class The class to load.
		 *
		 * @since  1.0.0
		 */
		public function autoload( $class ) {

			$class = strtolower( $class );
			$class = str_replace( '_', '-', $class );
			if ( false === strpos( $class, 'yith-shippo' ) ) {
				return; // Pass over.
			}

			$base_path = YITH_SHIPPO_DIR . 'includes/';
			// Check first for mapped files.
			$mapped = $this->get_mapped_files();
			if ( isset( $mapped[ $class ] ) ) {
				$file = $base_path . $mapped[ $class ];
			} else {
				if ( false !== strpos( $class, 'trait' ) ) {
					$file = $base_path . 'traits/trait-' . $class . '.php';

				} elseif ( false !== strpos( $class, 'interface' ) ) {
					$base_path .= 'interfaces/';

					$file = $base_path . 'interface-' . $class . '.php';
				} else {
					if ( false !== strpos( $class, 'api-resource' ) ) {
						$base_path .= 'shippo-request/shippo-api-resources/';
					}
					if ( false !== strpos( $class, 'shippo-request' ) ) {
						$base_path .= 'shippo-request/';
					} elseif ( false !== strpos( $class, 'data-store' ) ) {
						$base_path .= 'data-stores/';
					} elseif ( false !== strpos( $class, 'list-table' ) ) {
						$base_path .= 'admin/list-tables/';
					} elseif ( false !== strpos( $class, 'abstract' ) ) {
						$base_path .= 'abstracts/';
					}

					$file = $base_path . 'class-' . $class . '.php';

				}
			}

			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	}
}

new YITH_Shippo_Autoloader();
