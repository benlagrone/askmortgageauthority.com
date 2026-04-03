<?php
/**
 * Handler class that performs correct action when required (it searches for known action nonce in the REQUEST).
 *
 * @author  YITH
 * @package YITH\StripePayments\Classes
 * @version 1.0.0
 */

namespace YITH\StripePayments;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Form_Handler' ) ) {
	/**
	 * Performs all required actions coming from frontend requests
	 *
	 * @since 1.0.0
	 */
	class Form_Handler {

		/**
		 * List of supported actions
		 *
		 * @var string[]
		 */
		protected static $handlers = array(
			'element_verify_intent',
		);

		/**
		 * Init defined AJAX handlers
		 */
		public static function init() {
			add_action( 'template_redirect', self::class . '::process' );
		}

		/**
		 * Retrieves action being executed, if any
		 *
		 * @return string|bool Current handler, or false if none is found.
		 */
		public static function get_current_handler() {
			$handler = false;

			foreach ( self::$handlers as $flag ) {
				if ( ! empty( $_REQUEST[ $flag ] ) ) {
					$handler = $flag;
					break;
				}
			}

			return $handler;
		}

		/**
		 * Single action handler for the plugin
		 * Searches for known nonce, validates it, and finally executes related class method to process action.
		 */
		public static function process() {
			$handler = self::get_current_handler();

			if ( ! $handler ) {
				return;
			}

			// checks that method exists.
			if ( ! method_exists( self::class, $handler ) ) {
				wp_die();
			}

			// runs proper handler.
			call_user_func( self::class . '::' . $handler );
		}

		/**
		 * Verifies intent and redirect customer to current page.
		 * If intent is verified and succeeded, redirects to thank you page. Otherwise brings back to checkout page
		 * with a specific error message
		 *
		 * @throws \Exception When something is wrong with intent verification. Should be handled internally.
		 */
		protected static function element_verify_intent() {
			$gateway = Gateways::get_instance()->get_gateway();

			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$order_id     = isset( $_REQUEST['order_id'] ) ? (int) $_REQUEST['order_id'] : false;
			$redirect_url = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : false;
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			try {
				if ( ! $gateway ) {
					throw new \Exception( _x( 'Error while initializing gateway.', 'Payment confirm error', 'yith-stipe-payments-for-woocommerce' ) );
				}

				// Retrieve the order.
				$order = wc_get_order( $order_id );

				if ( ! $order ) {
					throw new \Exception( _x( 'Missing order ID for payment confirmation.', 'Payment confirm error', 'yith-stipe-payments-for-woocommerce' ) );
				}

				// confirm intent.
				$gateway->confirm( $order_id );

				// finally redirect to thank you page.
				$redirect_url = $redirect_url ? $redirect_url : $gateway->get_return_url( $order );

				wp_safe_redirect( $redirect_url );
				exit;

			} catch ( \Exception $e ) {
				// translators: 1. Error message.
				wc_add_notice( sprintf( __( 'Payment verification error - %s', 'woocommerce-gateway-stripe' ), $e->getMessage() ), 'error' );

				$redirect_url = WC()->cart->is_empty() ? wc_get_cart_url() : wc_get_checkout_url();

				wp_safe_redirect( $redirect_url );
				exit;
			}
		}
	}
}
