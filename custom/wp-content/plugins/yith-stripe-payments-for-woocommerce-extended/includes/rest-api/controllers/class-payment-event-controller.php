<?php
/**
 * Stripe Payment Events controller class
 */

namespace YITH\StripePayments\RestApi\Controllers;

use YITH\StripePayments\Gateways;
use YITH\StripePayments\Gateways\Abstracts\Gateway;
use YITH\StripePayments\Gateways\Payment_Element;

if ( ! class_exists( 'YITH\StripePayments\RestApi\Controllers\Payment_Event_Controller' ) ) {
	/**
	 * Stripe Events controller
	 */
	abstract class Payment_Event_Controller extends Event_Controller {

		/**
		 * The Order related with the payment
		 *
		 * @var false|\WC_Order|\WC_Order_Refund
		 */
		protected $order = false;

		/**
		 * Handle the Stripe Event.
		 *
		 * @param string $event  The stripe event.
		 * @param array  $params The request params.
		 *
		 * @return array
		 */
		public function handle_event( $event, $params ) {
			parent::handle_event( $event, $params );

			if ( $this->order ) {
				$this->order->save();
			}

			return $this->response;
		}

		/**
		 * Populate the object property using the request params
		 *
		 * @param array $params The request params.
		 *
		 * @return void
		 */
		protected function populate( $params ) {
			parent::populate( $params );

			if ( isset( $this->event_data['metadata']['order_id'] ) ) {
				$this->order = wc_get_order( absint( $this->event_data['metadata']['order_id'] ) );
			}
		}

		/**
		 * Update order status.
		 *
		 * @param string $status The status.
		 * @param string $note   The note.
		 *
		 * @return bool
		 */
		public function update_order_status( $status, $note = '' ) {
			$event_url = $this->get_stripe_event_url();

			$from_status       = $this->order->get_status();
			$wc_order_statuses = wc_get_order_statuses();
			$note              = ! ! $note ? $note : sprintf(
			// translators: %1$s is the order status, %1$s is the HTML link opening tag while %3$s is the closing one.
				__( 'Order status set to %1$s due to a %2$sStripe event%3$s', 'yith-stripe-payments-for-woocommerce' ),
				$wc_order_statuses[ $status ] ?? $status,
				$this->get_stripe_event_url_opening_tag(),
				$this->get_stripe_event_url_closing_tag()
			);
			$update_status     = $this->order->update_status( $status, $note );

			$this->add_response_action(
				array(
					'order_id' => $this->order->get_id(),
					'from'     => $from_status,
					'to'       => $this->order->get_status(),
				)
			);

			return $update_status;
		}

		/**
		 * Update order meta.
		 *
		 * @param string $key   The meta key.
		 * @param mixed  $value The meta value.
		 *
		 * @return bool
		 */
		public function update_order_meta( $key, $value ) {
			$gateways = Gateways::get_instance();

			return $gateways->set_order_meta( $this->order, $key, $value );
		}

		/**
		 * Get order meta.
		 *
		 * @param string $key The meta key.
		 *
		 * @return bool
		 */
		public function get_order_meta( $key ) {
			$gateways = Gateways::get_instance();

			return $gateways->get_order_meta( $this->order, $key );
		}

		/**
		 * Order payment complete.
		 *
		 * @param string $note The note.
		 *
		 * @return bool
		 */
		public function order_payment_complete( $note = '' ) {
			$from_status      = $this->order->get_status();
			$payment_complete = $this->order->payment_complete();

			if ( $payment_complete ) {
				$wc_order_statuses = wc_get_order_statuses();
				$status            = $this->order->get_status();
				$note              = ! ! $note ? $note : sprintf(
				// translators: %1$s is the order status, %1$s is the HTML link opening tag while %3$s is the closing one.
					__( 'Order status set to %1$s due to a %2$sStripe event%3$s', 'yith-stripe-payments-for-woocommerce' ),
					$wc_order_statuses[ 'wc-' . str_replace( 'wc-', '', $status ) ] ?? $status,
					$this->get_stripe_event_url_opening_tag(),
					$this->get_stripe_event_url_closing_tag()
				);
				$this->order->add_order_note( $note );
			}

			$this->add_response_action(
				array(
					'order_id'         => $this->order->get_id(),
					'from'             => $from_status,
					'to'               => $this->order->get_status(),
					'payment_complete' => $payment_complete,
				)
			);

			return $payment_complete;
		}

		/**
		 * Get stripe payment URL.
		 *
		 * @return string
		 */
		protected function get_stripe_event_url() {
			$url = '';
			if ( isset( $this->event_data['id'] ) ) {
				$url = 'https://dashboard.stripe.com/' . ( $this->is_livemode() ? '' : 'test/' ) . 'payments/' . $this->event_data['id'];
			}

			return $url;
		}

		/**
		 * Get stripe payment URL.
		 *
		 * @return string
		 */
		protected function get_stripe_event_url_opening_tag() {
			$event_url = $this->get_stripe_event_url();

			return $event_url ? '<a href="' . $event_url . '" target="_blank">' : '';
		}

		/**
		 * Get stripe payment URL.
		 *
		 * @return string
		 */
		protected function get_stripe_event_url_closing_tag() {
			$event_url = $this->get_stripe_event_url();

			return $event_url ? '</a>' : '';
		}

		/**
		 * Conditionals
		 */

		/**
		 * Check if the request is valid
		 *
		 * @return bool
		 */
		protected function is_valid() {
			$is_valid = parent::is_valid() && $this->order;
			if ( $is_valid ) {
				$gateway  = Gateways::get_instance()->get_gateway( $this->order->get_payment_method() );
				$is_valid = in_array( $gateway->id, Gateways::get_instance()->get_slugs() );
			}

			return $is_valid;
		}
	}
}
