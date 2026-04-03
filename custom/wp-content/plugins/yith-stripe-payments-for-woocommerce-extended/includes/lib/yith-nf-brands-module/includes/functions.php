<?php
/**
 * Functions
 *
 * @package YITH\NewFoldPBrandsModule\Functions
 * @author  YITH <plugins@yithemes.com>
 * @since   1.0.0
 */

use function NewfoldLabs\WP\ModuleLoader\container;

if ( ! function_exists( 'yith_nfbm_get_container' ) ) {
	/**
	 * Get the NewFold Module Container
	 *
	 * @return false|NewfoldLabs\WP\ModuleLoader\Container
	 */
	function yith_nfbm_get_container() {
		static $container = false;

		if ( ! $container ) {
			$container = function_exists( 'NewfoldLabs\WP\ModuleLoader\container' ) ? container() : false;
		}

		return $container;
	}
}

if ( ! function_exists( 'yith_nfbm_get_container_attribute' ) ) {
	/**
	 * Get the Container attribute
	 *
	 * @throws Exception If the attribute is not found
	 * @return mixed
	 */
	function yith_nfbm_get_container_attribute( $attribute ) {
		$container = yith_nfbm_get_container();

		if ( ! is_callable( array( $container, 'get' ) ) ) {
			return false;
		}

		return $container->get( $attribute );
	}
}

if ( ! function_exists( 'yith_nfbm_get_container_plugin' ) ) {
	/**
	 * Get the Container Plugin
	 *
	 * @return NewfoldLabs\WP\ModuleLoader\Plugin|false
	 */
	function yith_nfbm_get_container_plugin() {
		$container = yith_nfbm_get_container();
		$plugin    = $container && is_callable( array( $container, 'plugin' ) ) ? $container->plugin() : false;

		return ! ! $plugin ? $plugin : false;
	}
}

if ( ! function_exists( 'yith_nfbm_get_container_plugin_attribute' ) ) {
	/**
	 * Get the Container Plugin attribute
	 *
	 * @return mixed
	 */
	function yith_nfbm_get_container_plugin_attribute( $attribute ) {
		$container_plugin = yith_nfbm_get_container_plugin();

		if ( ! is_callable( array( $container_plugin, 'get' ) ) ) {
			return false;
		}

		return $container_plugin->get( $attribute );
	}
}

if ( ! function_exists( 'yith_nfbm_get_brand' ) ) {
	/**
	 * Get the partner Brand
	 *
	 * @param mixed $default The default value returned if the brand as an empty value.
	 *
	 * @return string|false|mixed
	 */
	function yith_nfbm_get_brand( $default = false ) {
		try {
			$brand = yith_nfbm_get_container_attribute( 'marketplace_brand' );
		} catch ( \Exception $e ) {
			$brand = yith_nfbm_get_container_plugin_attribute( 'brand' );
		}

		return ! ! $brand ? $brand : $default;
	}
}
