<?php
/**
 * Payment Exception
 *
 * @author  YITH
 * @package YITH\StripePayments\Exceptions
 * @version 1.0.0
 */

namespace YITH\StripePayments\Exceptions;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Exceptions\Payment_Exception' ) ) {
	/**
	 * Used to describe an error occurred during payment
	 *
	 * @since 1.0.0
	 */
	class Payment_Exception extends \Exception {
		/**
		 * Synthetic error message
		 *
		 * @var string
		 */
		protected $synthetic = '';
		/**
		 * Order that attempted payment
		 *
		 * @var \WC_Order
		 */
		protected $order = null;

		/**
		 * Constructor method
		 *
		 * @param string    $message   Error message.
		 * @param string    $synthetic Synthetic error code.
		 * @param \WC_Order $order     Order object.
		 */
		public function __construct( $message, $synthetic = '', $order = false ) {
			parent::__construct( $message );

			$this->synthetic = $synthetic;
			$this->order     = $order;

			$this->maybeAddNote();
		}

		/**
		 * Get details about the error, if any
		 *
		 * @return string Error details
		 */
		public function getSynthetic() {
			return $this->synthetic;
		}

		/**
		 * Get order that triggered the error
		 *
		 * @return \WC_Order|bool Order object, if any.
		 */
		public function getOrder() {
			return $this->order;
		}

		/**
		 * Returns formatted error message.
		 * If debug mode is set, will return debug message.
		 *
		 * @return string Formatted message.
		 */
		public function getFormatted() {
			$debug     = defined( 'WP_DEBUG' ) && true === WP_DEBUG;
			$message   = $this->getMessage();
			$hints     = $this->getHints();
			$synthetic = $this->getSynthetic();
			$parts     = array(
				$message,
			);

			if ( $hints ) {
				$parts[] = $hints;
			}

			if ( $synthetic && $debug ) {
				$parts[] = sprintf( '(%s)', $synthetic );
			}

			return implode( ' ', $parts );
		}

		/**
		 * When possible, retrieve hints on what happened and suggestions on what to do next from the synthetic error code.
		 *
		 * @return string|bool Hints regarding current synthetic code.
		 */
		public function getHints() {
			switch ( $this->synthetic ) {
				case 'card_not_supported':
					return _x( 'The card does not support this type of purchase.', 'Payment errors', 'yith-stripe-payments-for-woocommerce' );
				case 'currency_not_supported':
					return _x( 'The card does not support the specified currency.', 'Payment errors', 'yith-stripe-payments-for-woocommerce' );
				case 'do_not_honor':
					return _x( 'The card was declined for an unknown reason.', 'Payment errors', 'yith-stripe-payments-for-woocommerce' );
				case 'expired_card':
					return _x( 'The card has expired.', 'Payment errors', 'yith-stripe-payments-for-woocommerce' );
				case 'incorrect_number':
					return _x( 'The card number is incorrect.', 'Payment errors', 'yith-stripe-payments-for-woocommerce' );
				case 'incorrect_cvc':
					return _x( 'The CVC number is incorrect.', 'Payment errors', 'yith-stripe-payments-for-woocommerce' );
				case 'insufficient_funds':
					return _x( 'The card has insufficient funds to complete the purchase.', 'Payment errors', 'yith-stripe-payments-for-woocommerce' );
				default:
					return false;
			}
		}

		/**
		 * If there is any order registered for curren Exception, register a note with details about error
		 */
		protected function maybeAddNote() {
			if ( ! $this->order || ! $this->synthetic ) {
				return;
			}

			// translators: specific error code occurred.
			$this->order->add_order_note( sprintf( _x( 'An error occurred while processing payment (%s).', 'Order notes', 'yith-stripe-payments-for-woocommerce' ), $this->synthetic ) );
		}

	}
}
