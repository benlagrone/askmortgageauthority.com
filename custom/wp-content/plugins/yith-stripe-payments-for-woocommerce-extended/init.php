<?php
/**
 * Plugin Name: YITH Stripe Payments for WooCommerce
 * Description: <code><strong>YITH Stripe Payments for WooCommerce</strong></code> allows your users to pay with credit cards thanks to the integration with Stripe, a powerful and flexible payment gateway. You will be able to get payments with credit cards while assuring your users of the reliability of an international partner. <a href="https://yithemes.com/" target="_blank">Get more plugins for your e-commerce on <strong>YITH</strong></a>.
 * Version: 1.4.0
 * Author: YITH
 * Author URI: https://yithemes.com/
 * Text Domain: yith-stripe-payments-for-woocommerce
 * Domain Path: /languages
 * WC requires at least: 9.6
 * WC tested up to: 9.8
 *
 * @author  YITH
 * @package YITH\StripePayments
 * @version 1.4.0
 */

defined( 'ABSPATH' ) || exit;

defined( 'YITH_STRIPE_PAYMENTS' ) || define( 'YITH_STRIPE_PAYMENTS', true );
defined( 'YITH_STRIPE_PAYMENTS_VERSION' ) || define( 'YITH_STRIPE_PAYMENTS_VERSION', '1.4.0' );
defined( 'YITH_STRIPE_PAYMENTS_FILE' ) || define( 'YITH_STRIPE_PAYMENTS_FILE', __FILE__ );
defined( 'YITH_STRIPE_PAYMENTS_URL' ) || define( 'YITH_STRIPE_PAYMENTS_URL', plugin_dir_url( __FILE__ ) );
defined( 'YITH_STRIPE_PAYMENTS_DIR' ) || define( 'YITH_STRIPE_PAYMENTS_DIR', plugin_dir_path( __FILE__ ) );
defined( 'YITH_STRIPE_PAYMENTS_INC' ) || define( 'YITH_STRIPE_PAYMENTS_INC', YITH_STRIPE_PAYMENTS_DIR . 'includes/' );
defined( 'YITH_STRIPE_PAYMENTS_LANG' ) || define( 'YITH_STRIPE_PAYMENTS_LANG', YITH_STRIPE_PAYMENTS_DIR . 'languages/' );
defined( 'YITH_STRIPE_PAYMENTS_INIT' ) || define( 'YITH_STRIPE_PAYMENTS_INIT', plugin_basename( __FILE__ ) );
defined( 'YITH_STRIPE_PAYMENTS_SLUG' ) || define( 'YITH_STRIPE_PAYMENTS_SLUG', 'yith-stripe-payments-for-woocommerce' );
defined( 'YITH_STRIPE_PAYMENTS_VIEWS' ) || define( 'YITH_STRIPE_PAYMENTS_VIEWS', YITH_STRIPE_PAYMENTS_DIR . 'views/' );
defined( 'YITH_STRIPE_PAYMENTS_TEMPLATES' ) || define( 'YITH_STRIPE_PAYMENTS_TEMPLATES', YITH_STRIPE_PAYMENTS_DIR . 'templates/' );

if ( ! function_exists( 'yith_stripe_payments_constructor' ) ) {
	/**
	 * Bootstraps plugin
	 *
	 * @return YITH\StripePayments\Main
	 */
	function yith_stripe_payments_constructor() {
		if ( function_exists( 'yith_plugin_fw_load_plugin_textdomain' ) ) {
			yith_plugin_fw_load_plugin_textdomain( 'yith-stripe-payments-for-woocommerce', basename( dirname( __FILE__ ) ) . '/languages' );
		}

		require_once YITH_STRIPE_PAYMENTS_INC . 'class-main.php';

		return YITH\StripePayments\Main::get_instance();
	}
}

if ( ! function_exists( 'yith_stripe_payments_install' ) ) {
	/**
	 * Performs pre-flight basic tests, and then bootstrap plugin
	 *
	 * @return void
	 */
	function yith_stripe_payments_install() {
		if ( ! function_exists( 'WC' ) ) {
			add_action( 'admin_notices', 'yith_stripe_payments_show_woocommerce_admin_notice' );
		} else {
			/**
			 * DO_ACTION: yith_stripe_payments_init
			 *
			 * Allows the plugin initialization.
			 */
			do_action( 'yith_stripe_payments_init' );
		}
	}
}

if ( ! function_exists( 'yith_stripe_payments_show_woocommerce_admin_notice' ) ) {
	/**
	 * Show admin notice when WooCommerce is not installed.
	 *
	 * @return void.
	 */
	function yith_stripe_payments_show_woocommerce_admin_notice() {
		?>
		<div class="error">
			<p>
				<?php
				// translators: 1. Plugin name.
				echo esc_html( sprintf( __( '%s is enabled but not effective. It requires WooCommerce in order to work.', 'yith-stripe-payments-for-woocommerce' ), 'YITH Stripe Payments for WooCommerce' ) );
				?>
			</p>
		</div>
		<?php
	}
}

if ( ! function_exists( 'yith_stripe_payments_maybe_load_plugin_fw' ) ) {
	/**
	 * Check plugin framework version.
	 *
	 * @return void.
	 */
	function yith_stripe_payments_maybe_load_plugin_fw() {
		if ( file_exists( plugin_dir_path( __FILE__ ) . 'plugin-fw/init.php' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'plugin-fw/init.php';
		}

		// activation hook.
		if ( ! function_exists( 'yith_plugin_registration_hook' ) ) {
			require_once 'plugin-fw/yit-plugin-registration-hook.php';
		}

		register_activation_hook( __FILE__, 'yith_plugin_registration_hook' );
	}
}

// load plugin-fw.
yith_stripe_payments_maybe_load_plugin_fw();

// let's start the game.
add_action( 'plugins_loaded', 'yith_stripe_payments_install', 11 );
add_action( 'yith_stripe_payments_init', 'yith_stripe_payments_constructor' );

// Auto update via Hiive CDN
require_once 'hiive-autoupdate.php';
