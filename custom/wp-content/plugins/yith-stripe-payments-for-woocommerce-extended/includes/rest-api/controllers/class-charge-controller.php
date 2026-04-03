<?php
/**
 * Charge events controller class
 */

namespace YITH\StripePayments\RestApi\Controllers;

use YITH\StripePayments\RestApi\Controllers\Payment_Event_Controller;
use YITH\StripePayments\Amount;

if ( ! class_exists( 'YITH\StripePayments\RestApi\Controllers\Charge_Controller' ) ) {
	/**
	 * Stripe `charge` events controller
	 *
	 * @extends Payment_Event_Controller
	 */
	class Charge_Controller extends Payment_Event_Controller {

		/**
		 * The Stripe event type
		 *
		 * @var string
		 */
		protected $event = 'charge';

		/**
		 * Handle `charge.succeeded` event
		 */
		protected function succeeded() {
			if ( ! empty( $this->event_data['amount_refunded'] ) ) {
				$this->refunded();
			} elseif ( isset( $this->event_data['amount_captured'], $this->event_data['currency'] ) ) {
				$amount_captured = Amount::decode( $this->event_data['amount_captured'], $this->event_data['currency'] );
				if ( floatval( $this->order->get_total() ) === $amount_captured ) {
					$this->order_payment_complete();
				}

				if ( false !== $amount_captured ) {
					$this->update_order_meta( 'captured', $amount_captured );
				}
			}
		}

		/**
		 * Handle `charge.captured` event
		 */
		protected function captured() {
			$this->succeeded();
		}

		/**
		 * Handle `charge.dispute.created` event
		 */
		protected function dispute_created() {
			if ( isset( $this->event_data['charge'] ) ) {
				$this->update_order_meta( 'status_on_dispute_created', $this->order->get_status() );
				$this->update_order_status(
					'on-hold',
					sprintf(
						__( 'Order status set to on-hold due to a %sStripe Dispute%s', 'yith-stripe-payments-for-woocommerce' ),
						$this->get_stripe_event_url_opening_tag(),
						$this->get_stripe_event_url_closing_tag()
					)
				);
			}
		}

		/**
		 * Handle `charge.dispute.closed` event
		 */
		protected function dispute_closed() {
			if ( isset( $this->event_data['status'] ) ) {
				$status = 'on-hold';
				switch ( $this->event_data['status'] ) {
					case 'won':
						$status = $this->get_order_meta( 'status_on_dispute_created' );
						break;
					case 'lost':
						$status = 'cancelled';
						break;
					case 'charge_refunded':
						$status = 'refunded';
						break;
				}

				$wc_order_statuses = wc_get_order_statuses();
				$this->update_order_status(
					$status,
					sprintf(
					// translators: %1$s is the new order status, %2$s is the opening tag of thr link while %3$s is the closing one.
						__( 'Order status set to %1$s due from losing the %2$sStripe Dispute%3$s', 'yith-stripe-payments-for-woocommerce' ),
						$wc_order_statuses[ $status ] ?? $status,
						$this->get_stripe_event_url_opening_tag(),
						$this->get_stripe_event_url_closing_tag()
					)
				);
			}
		}

		/**
		 * Handle `charge.refunded` event
		 */
		protected function refunded() {
			if ( isset( $this->event_data['amount_captured'], $this->event_data['currency'] ) ) {
				$amount_captured = Amount::decode( $this->event_data['amount_captured'], $this->event_data['currency'] );

				if ( false !== $amount_captured ) {
					$this->update_order_meta( 'captured', $amount_captured );
				}

				$already_refunded = $this->order->get_total_refunded();
				$refunded_amount  = Amount::decode( $this->event_data['amount_refunded'], $this->event_data['currency'] );
				$to_refund        = $already_refunded ? $refunded_amount - $already_refunded : $refunded_amount;

				if ( $refunded_amount === floatval( $this->order->get_total() ) ) {
					$this->update_order_status(
						'refunded',
						sprintf(
							__( 'Order status set to refunded due to a %sStripe event%s', 'yith-stripe-payments-for-woocommerce' ),
							$this->get_stripe_event_url_opening_tag(),
							$this->get_stripe_event_url_closing_tag()
						)
					);
				} else {
					wc_create_refund(
						array(
							'amount'   => $to_refund,
							'reason'   => $this->event_data['description'],
							'order_id' => $this->order->get_id(),
						)
					);
				}
			}
		}

		/**
		 * Handle `charge.failed` event
		 */
		protected function failed() {
			$this->update_order_status( 'failed' );
		}

		/**
		 * Handle `charge.expired` event
		 */
		protected function expired() {
			$this->update_order_status(
				'failed',
				sprintf(
					__( 'Order status set to failed due to an %suncaptured charge that as expired%s', 'yith-stripe-payments-for-woocommerce' ),
					$this->get_stripe_event_url_opening_tag(),
					$this->get_stripe_event_url_closing_tag()
				)
			);
		}
	}
}
