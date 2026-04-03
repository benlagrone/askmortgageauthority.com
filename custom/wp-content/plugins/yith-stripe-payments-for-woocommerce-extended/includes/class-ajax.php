<?php
/**
 * Frontend-only AJAX handlers
 *
 * @author  YITH
 * @package YITH\StripePayments\Classes
 * @version 1.0.0
 */

namespace YITH\StripePayments;

use YITH\StripePayments\Models\Intent;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Ajax' ) ) {
	/**
	 * Handles all AJAX calls coming from frontend requests
	 *
	 * @since 1.0.0
	 */
	class Ajax {

		/**
		 * List of supported handlers
		 *
		 * @var string[]
		 */
		protected static $handlers = array(
			'get_checkout_details',
		);

		/**
		 * Init defined AJAX handlers
		 */
		public static function init() {
			$handlers = self::$handlers;

			foreach ( $handlers as $handler ) {
				add_action( "wp_ajax_yith_stripe_payments_$handler", self::class . '::process' );
				add_action( "wp_ajax_nopriv_yith_stripe_payments_$handler", self::class . '::process' );
			}
		}

		/**
		 * Single AJAX handler for the plugin
		 * Performs basic checks over the call, then uses current action to execute proper handler in this class
		 */
		public static function process() {
			$current_action = current_action();
			$handler        = str_replace( array( 'wp_ajax_yith_stripe_payments_', 'wp_ajax_nopriv_yith_stripe_payments_' ), '', $current_action );

			// checks for supported handler.
			if ( ! in_array( $handler, self::$handlers, true ) ) {
				wp_die();
			}

			// checks for correct nonce.
			check_admin_referer( $handler, 'security' );

			// checks that method exists.
			if ( ! method_exists( self::class, $handler ) ) {
				wp_die();
			}

			// runs proper handler.
			call_user_func( self::class . '::' . $handler );
		}

		/**
		 * Starts onboarding process if needed.
		 */
		protected static function get_checkout_details() {
			$order_id = (int) WC()->session->get( 'order_awaiting_payment' );
			$order    = $order_id ? wc_get_order( $order_id ) : false;
			$cart     = WC()->cart;

			$total    = $order ? (float) $order->get_total( 'edit' ) : (float) $cart->get_total( 'edit' );
			$currency = $order ? $order->get_currency() : '';

			// retrieves details about current checkout.
			$checkout_details = array(
				'total'    => Amount::format( $total ),
				'currency' => Amount::get_formatted_currency( $currency ),
			);

			// retrieves selected gateway from request.
			$gateway_slug = isset( $_REQUEST['gateway'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['gateway'] ) ) : false;
			$gateway      = $gateway_slug ? Gateways::get_instance()->get_gateway( $gateway_slug ) : false;

			// adds gateway specific details.
			if ( $gateway ) {
				$checkout_details = array_merge( $checkout_details, $gateway->get_checkout_details() );
			}

			wp_send_json( $checkout_details );
		}
	}
}
