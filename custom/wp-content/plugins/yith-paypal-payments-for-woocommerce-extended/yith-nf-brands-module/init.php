<?php
/**
 * YITH NewFold Brands Partner library
 *
 * @package YITH\NewFoldPBrandsModule
 * @author  YITH <plugins@yithemes.com>
 * @version 1.0.1
 */

defined( 'YITH_NFBM' ) || define( 'YITH_NFBM', true );
defined( 'YITH_NFBM_VERSION' ) || define( 'YITH_NFBM_VERSION', '1.0.1' );
defined( 'YITH_NFBM_PATH' ) || define( 'YITH_NFBM_PATH', plugin_dir_path( __FILE__ ) );
defined( 'YITH_NFBM_INCLUDES' ) || define( 'YITH_NFBM_INCLUDES', YITH_NFBM_PATH . 'includes/' );

if ( ! function_exists( 'yith_nfbm_init' ) ) {
	/**
	 * Init module
	 */
	function yith_nfbm_init() {
		require_once YITH_NFBM_INCLUDES . 'functions.php';
	}
}

yith_nfbm_init();
