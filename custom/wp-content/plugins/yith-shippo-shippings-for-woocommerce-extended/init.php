<?php
/**
 * Plugin Name: YITH Shippo Shippings for WooCommerce Extended
 * Plugin URI: https://yithemes.com/themes/plugins/yith-shippo-shippings-for-woocommerce/
 * Description: <code><strong>YITH Shippo Shippings for WooCommerce</strong></code> adds Shippo live rates into WooCommerce shipping methods. <a href ="https://yithemes.com">Get more plugins for your e-commerce shop on <strong>YITH</strong></a>.
 * Version: 1.33.0
 * Author: YITH
 * Author URI: https://yithemes.com/
 * Text Domain: yith-shippo-shippings-for-woocommerce
 * Domain Path: /languages/
 * WC requires at least: 9.6
 * WC tested up to: 9.8
 *
 * Requires Plugins: woocommerce
 *
 * @package YITH/Shippo
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

! defined( 'YITH_SHIPPO_DIR' ) && define( 'YITH_SHIPPO_DIR', plugin_dir_path( __FILE__ ) );

// Plugin Framework Loader.
if ( file_exists( plugin_dir_path( __FILE__ ) . 'plugin-fw/init.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'plugin-fw/init.php';
}


// Define constants ________________________________________.

$wp_upload_dir = wp_upload_dir();

! defined( 'YITH_SHIPPO_VERSION' ) && define( 'YITH_SHIPPO_VERSION', '1.33.0');
! defined( 'YITH_SHIPPO_DB_VERSION' ) && define( 'YITH_SHIPPO_DB_VERSION', '1.0.7' );
! defined( 'YITH_SHIPPO_INIT' ) && define( 'YITH_SHIPPO_INIT', plugin_basename( __FILE__ ) );
! defined( 'YITH_SHIPPO_DIR' ) && define( 'YITH_SHIPPO_DIR', plugin_dir_path( __FILE__ ) );
! defined( 'YITH_SHIPPO_FILE' ) && define( 'YITH_SHIPPO_FILE', __FILE__ );
! defined( 'YITH_SHIPPO_URL' ) && define( 'YITH_SHIPPO_URL', plugins_url( '/', __FILE__ ) );
! defined( 'YITH_SHIPPO_ASSETS_URL' ) && define( 'YITH_SHIPPO_ASSETS_URL', YITH_SHIPPO_URL . 'assets' );
! defined( 'YITH_SHIPPO_TEMPLATE_PATH' ) && define( 'YITH_SHIPPO_TEMPLATE_PATH', YITH_SHIPPO_DIR . 'templates' );
! defined( 'YITH_SHIPPO_INC' ) && define( 'YITH_SHIPPO_INC', YITH_SHIPPO_DIR . '/includes/' );
! defined( 'YITH_SHIPPO_VIEWS_PATH' ) && define( 'YITH_SHIPPO_VIEWS_PATH', YITH_SHIPPO_INC . 'admin/views' );
! defined( 'YITH_SHIPPO_SLUG' ) && define( 'YITH_SHIPPO_SLUG', 'yith-shippo-shippings-for-woocommerce' );
! defined( 'YITH_SHIPPO_SECRET_KEY' ) && define( 'YITH_SHIPPO_SECRET_KEY', '' );
! defined( 'YITH_SHIPPO_CLIENT_ID' ) && define( 'YITH_SHIPPO_CLIENT_ID', '89b375d8d52d4eb8a5b918c1adf4055d' );
! defined( 'YITH_SHIPPO_CLIENT_SECRET' ) && define( 'YITH_SHIPPO_CLIENT_SECRET', '-PpyX9jG-u4w9dPub4TcKvK4NfVkLuTMY8eCTSmZMIY' );
! defined( 'YITH_SHIPPO_EXTENDED' ) && define( 'YITH_SHIPPO_EXTENDED', '1' );
! defined( 'YITH_SHIPPO_DOCUMENT_SAVE_DIR' ) && define( 'YITH_SHIPPO_DOCUMENT_SAVE_DIR', $wp_upload_dir['basedir'] . '/yith-shippo/' );

if ( ! function_exists( 'yith_shippo_install_woocommerce_admin_notice_premium' ) ) {
	/**
	 * Print a notice if WooCommerce is not installed.
	 */
	function yith_shippo_install_woocommerce_admin_notice_premium() {
		?>
		<div class="error">
			<p><?php esc_html_e( 'YITH Shippo Shippings for WooCommerce is enabled but not effective. It requires WooCommerce in order to work.', 'yith-shippo-shippings-for-woocommerce' ); ?></p>
		</div>
		<?php
	}
}

if ( ! function_exists( 'yith_shippo_min_php_version_error' ) ) {
	/**
	 * Print a notice if PHP version is < 7.4.
	 */
	function yith_shippo_min_php_version_error() {
		?>
		<div class="error">
			<p>
				<?php
				printf( // translators: %s is the current PHP version.
					esc_html__(
						'YITH Shippo Shippings for WooCommerce is enabled but not effective. It requires at least PHP 7.4, you are using %s version.',
						'yith-shippo-shippings-for-woocommerce'
					),
					PHP_VERSION
				);
				?>
			</p>
		</div>
		<?php
	}
}

if ( ! function_exists( 'yith_shippo_premium_install' ) ) {
	/**
	 * Check WC installation.
	 */
	function yith_shippo_premium_install() {
		if ( ! function_exists( 'WC' ) ) {
			add_action( 'admin_notices', 'yith_shippo_install_woocommerce_admin_notice_premium' );
		} elseif ( ! ( PHP_VERSION_ID >= 70400 ) ) {
			add_action( 'admin_notices', 'yith_shippo_min_php_version_error' );
		} else {
			do_action( 'yith_shippo_init' );
			require_once 'includes/class-yith-shippo-install.php';
			YITH_Shippo_Install::init();
		}
	}
}
add_action( 'plugins_loaded', 'yith_shippo_premium_install', 11 );


// Require plugin autoload.
if ( ! class_exists( 'YITH_Shippo_Autoloader' ) ) {
	require_once YITH_SHIPPO_INC . 'class-yith-shippo-autoloader.php';
}

if ( ! function_exists( 'yith_shippo' ) ) {
	/**
	 * Unique access to instance of YITH_Shippo class
	 *
	 * @return YITH_Shippo|YITH_Shippo_Extended
	 * @since 1.0.0
	 */
	function yith_shippo() { // phpcs:ignore

		if ( defined( 'YITH_SHIPPO_EXTENDED' ) && file_exists( YITH_SHIPPO_INC . 'class-yith-shippo-extended.php' ) ) {
			return YITH_Shippo_Extended::get_instance();
		}

		return YITH_Shippo::get_instance();
	}
}

if ( ! function_exists( 'yith_shippo_premium_constructor' ) ) {
	/**
	 * Start the game.
	 */
	function yith_shippo_premium_constructor() {

		if ( function_exists( 'yith_plugin_fw_load_plugin_textdomain' ) ) {
			yith_plugin_fw_load_plugin_textdomain(  'yith-shippo-shippings-for-woocommerce',  false, dirname( plugin_basename( __FILE__ ) ) . '/languages/'  );
		}else{
			load_plugin_textdomain( 'yith-shippo-shippings-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		include_once YITH_SHIPPO_INC . 'yith-shippo-functions.php';
		yith_shippo();
	}
}
add_action( 'yith_shippo_init', 'yith_shippo_premium_constructor' );

add_action('init','shippo_reset_onboarding');
function shippo_reset_onboarding(){
	if( isset($_GET['shippo_onboarding'])){
		delete_option('nfd-ecommerce-captive-flow-shippo');
	}
	if( isset($_GET['pp_onboarding'])){
		delete_option('nfd-ecommerce-captive-flow-paypal');
	}
}

if ( file_exists( YITH_SHIPPO_DIR . 'hiive-autoupdate.php' ) ) {
	include YITH_SHIPPO_DIR . 'hiive-autoupdate.php';
}