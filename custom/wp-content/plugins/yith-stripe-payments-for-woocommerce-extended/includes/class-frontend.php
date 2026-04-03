<?php
/**
 * Main class
 *
 * @author  YITH
 * @package YITH\StripePayments
 * @version 1.0.0
 */

namespace YITH\StripePayments;

use YITH\StripePayments\Traits\Singleton;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Frontend' ) ) {
	/**
	 * Stripe Payments frontend class
	 * Init the frontend management of the plugin
	 *
	 * @since 1.0.0
	 */
	class Frontend {
		use Singleton;

		/**
		 * Constructor method; init frontend handling
		 */
		protected function __construct() {
			// enqueue general assets.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );

			// init admin actions.
			add_action( 'init', array( $this, 'init_forms' ) );

			// add Next Action box in thank you page.
			add_action( 'woocommerce_thankyou', array( $this, 'thankyou_next_action' ), 5, 2 );

			// add Next Action button in My Account page.
			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'myaccount_next_action' ), 10, 2 );
		}

		/**
		 * Enqueue assets global to the entire frontend
		 */
		public function enqueue() {
			if ( ! is_checkout() && ! is_checkout_pay_page() && ! is_add_payment_method_page() && ! has_block( 'woocommerce/checkout' ) ) {
				return;
			}

			// localize global variable to be used in all frontend scripts.
			wp_localize_script(
				'jquery',
				'yithStripePayments',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'labels'   => array(),
					'nonces'   => array(
						'get_checkout_details' => wp_create_nonce( 'get_checkout_details' ),
					),
				)
			);
		}

		/**
		 * Init frontend forms handling for this plugin
		 */
		public function init_forms() {
			Form_Handler::init();
		}

		/**
		 * Print templates to show next order action
		 * Order must be on-hold, processed with one of plugin's gateway, and including "next_action" meta
		 *
		 * @param int $order_id Order id.
		 */
		public function thankyou_next_action( $order_id ) {
			$order = wc_get_order( $order_id );

			if ( ! $order || ! $order->has_status( 'on-hold' ) ) {
				return;
			}

			$next_action    = Gateways::get_instance()->get_order_meta( $order, 'next_action' );
			$button_classes = wc_wp_theme_get_element_class_name( 'button' );

			if ( ! $next_action ) {
				return;
			}

			wc_get_template( 'checkout/thankyou-next-actions.php', compact( 'next_action', 'button_classes' ), 'yith-stripe-payments/', YITH_STRIPE_PAYMENTS_TEMPLATES );
		}

		/**
		 * Adds custom action to confirm payment in My Account -> Orders table
		 *
		 * @param array     $actions Array of available actions for the order.
		 * @param \WC_Order $order   Current order object.
		 *
		 * @return array Filtered list of available actions for the order.
		 */
		public function myaccount_next_action( $actions, $order ) {
			if ( ! $order || ! $order->has_status( 'on-hold' ) ) {
				return $actions;
			}

			$next_action = Gateways::get_instance()->get_order_meta( $order, 'next_action' );

			if ( ! $next_action || empty( $next_action[ 'url' ] ) ) {
				return $actions;
			}

			$actions[ 'confirm' ] = array(
				'url'  => $next_action[ 'url' ],
				'name' => _x( 'Confirm payment', 'My Account order action', 'yith-stripe-payments-for-woocommerce' ),
			);

			return $actions;
		}
	}
}
