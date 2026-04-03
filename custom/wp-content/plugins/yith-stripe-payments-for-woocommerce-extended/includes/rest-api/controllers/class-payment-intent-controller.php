<?php
/**
 * Payment Intent events controller class
 */

namespace YITH\StripePayments\RestApi\Controllers;

use YITH\StripePayments\Amount;
use YITH\StripePayments\Gateways\Payment_Element;
use YITH\StripePayments\RestApi\Controllers\Payment_Event_Controller;

if ( ! class_exists( 'YITH\StripePayments\RestApi\Controllers\Payment_Intent_Controller' ) ) {
	/**
	 * Stripe `payment_intent` events controller
	 *
	 * @extends Payment_Event_Controller
	 */
	class Payment_Intent_Controller extends Payment_Event_Controller {

		protected $event = 'payment_intent';

		public function handle_event( $event, $params ) {
			parent::handle_event( $event, $params );

			if ( $this->is_valid() ) {
				$this->maybe_update_captured_amount();
			}

			return $this->response;
		}

		/**
		 * Handle `payment_intent.succeeded` event
		 */
		protected function succeeded() {
			if ( isset( $this->event_data['amount_received'], $this->event_data['currency'] ) ) {
				if ( $this->event_data['amount_received'] === $this->event_data['amount'] && Amount::decode( $this->event_data['amount'], $this->event_data['currency'] ) === floatval( $this->order->get_total() ) ) {
					$this->order_payment_complete();
				}
			}
		}

		/**
		 * Handle `payment_intent.succeeded` event
		 */
		protected function created() {
			$this->succeeded();
		}

		/**
		 * Handle `payment_intent.payment_failed` event
		 */
		protected function payment_failed() {
			$this->update_order_status( 'failed' );
		}

		/**
		 * Handle `payment_intent.canceled` event
		 */
		protected function canceled() {
			$this->update_order_status( 'canceled' );
		}

		/**
		 * Handle `payment_intent.requires_action` event
		 */
		protected function requires_action() {
			$this->update_order_status(
				'on-hold',
				sprintf(
					__( 'Order status set to on-hold due to the %sStripe Payments%s that requires an action', 'yith-stripe-payments-for-woocommerce' ),
					$this->get_stripe_event_url_opening_tag(),
					$this->get_stripe_event_url_closing_tag()
				)
			);
		}

		/**
		 * Check if is needed update the captured amount in the payment related order
		 */
		protected function maybe_update_captured_amount() {
			if ( isset( $this->event_data['charges']['object'], $this->event_data['charges']['data'] ) && 'list' === $this->event_data['charges']['object'] && is_array( $this->event_data['charges']['data'] ) ) {
				$captured_amount = 0;
				foreach ( $this->event_data['charges']['data'] as $charge ) {
					if ( ! empty( $charge['captured'] ) && isset( $charge['amount'], $charge['currency'] ) ) {
						$captured_amount += Amount::decode( $charge['amount'], $charge['currency'] );
					}
				}

				$captured_amount && $this->update_order_meta( 'captured', $captured_amount );
			}
		}
	}
}
