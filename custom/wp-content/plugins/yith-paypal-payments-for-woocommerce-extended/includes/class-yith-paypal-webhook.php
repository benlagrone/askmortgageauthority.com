<?php
/**
 * Webhook class
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH PayPal Payments for WooCommerce
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class YITH_PayPal_Webhook
 */
class YITH_PayPal_Webhook {

	/**
	 * Single instance of the class
	 *
	 * @since 1.0.0
	 * @var class YITH_PayPal_Webhook
	 */
	protected static $instance;

	/**
	 * Page name
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $pagename;

	/**
	 * Returns single instance of the class
	 *
	 * @since 1.0.0
	 * @return YITH_PayPal_Webhook
	 */
	public static function get_webhook() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Wrapper for YITH_PayPal_Logger::log with webhook context
	 *
	 * @since 1.3.2
	 * @param string $message The message to log.
	 * @param string $type The message type.
	 */
	public static function log( $message, $type = 'error' ) {
		YITH_PayPal_Logger::log( $message, $type, 'webhooks' );
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->get_webhook_url();
		/**
		 * APPLY_FILTERS:yith_ppcc_webhook_pagename
		 *
		 * Filter the page name for the webook by default, the page name is set to "yith_ppwc"
		 *
		 * @return string
		 */
		$this->pagename = apply_filters( 'yith_ppcc_webhook_pagename', 'yith_ppwc' );

		add_action( 'woocommerce_api_' . $this->pagename, array( $this, 'handle_webhooks' ) );
	}

	/**
	 * Handle the webhook
	 *
	 * @since 1.0.0
	 */
	public function handle_webhooks() {

		$headers     = getallheaders();
		$body        = @file_get_contents( 'php://input' ); //phpcs:ignore
		$environment = YITH_PayPal::get_instance()->get_gateway()->get_environment();

		if ( $body ) {

			$body   = json_decode( $body, true );
			$webook = YITH_PayPal_Controller::load( 'webhook' );
			try {
				if ( 'production' === $environment && ! $webook->verify_webhook_signature( $headers, $body ) ) {
					// print_r used on log.
					self::log( 'Webhook failed signature ' . print_r( $body, 1 ) ); //phpcs:ignore
					return;
				}

				$event_type = $body['event_type'];
				$resource   = $body['resource'];

				self::log(  "Event $event_type received " . print_r( $body, true ), 'info' ); //phpcs:ignore

				switch ( $event_type ) {
					case 'PAYMENT.AUTHORIZATION.CREATED':
						$this->handle_capture( $resource, 'register_authorization' );
						break;
					case 'PAYMENT.AUTHORIZATION.VOIDED':
						$this->handle_capture( $resource, 'void_authorization' );
						break;
					case 'PAYMENT.CAPTURE.COMPLETED':
						$this->handle_capture( $resource, 'process_payment_complete' );
						break;

					case 'PAYMENT.CAPTURE.REFUNDED':
						$this->handle_capture( $resource, 'process_refund' );
						break;
					case 'PAYMENT.CAPTURE.REVERSED':
						$this->handle_capture( $resource, 'process_reversed' );
						break;
					case 'PAYMENT.CAPTURE.DENIED':
						$this->handle_capture( $resource, 'process_failed_payment' );
						break;
					case 'PAYMENT.CAPTURE.PENDING':
						$this->handle_capture( $resource, 'process_pending_payment' );
						break;
					case 'CHECKOUT.ORDER.COMPLETED':
					case 'CHECKOUT.ORDER.APPROVED':
						return;

					case 'CUSTOMER.DISPUTE.CREATED':
						$this->handle_dispute( $resource, 'process_open_dispute' );
						break;
					case 'CUSTOMER.DISPUTE.RESOLVED':
						$this->handle_dispute( $resource, 'process_resolved_dispute' );
						break;
					default:
						self::log( 'Event type not found: ' . $event_type );
				}
			} catch ( Exception $e ) {
				// APPEND REQUEST DATA TO LOG.
				self::log( 'Error processing event: ' . $e->getMessage() );
			}
		}

	}


	/**
	 * Handle authorization
	 *
	 * @since 1.0.0
	 * @param array $posted Webhook content.
	 */
	public function handle_authorization( $posted ) {
		$event_type = $posted['event_type'];
	}


	/**
	 * Handle capture
	 *
	 * @since 1.0.0
	 * @param array  $resource Webhook content.
	 * @param string $callback Method to call after checked the webhook resource.
	 */
	public function handle_capture( $resource, $callback ) {

		if ( empty( $resource['invoice_id'] ) ) {
			self::log( 'The invoice id is not set inside the capture webhook resource.' );

			return;
		}

		$order_id = str_replace( YITH_PayPal::get_instance()->get_gateway()->get_prefix(), '', $resource['invoice_id'] );
		$order    = wc_get_order( $order_id );
		if ( ! $order ) {
			self::log( 'The order with id ' . $order_id . ' is not found.' );

			return;
		}

		$this->$callback( $order, $resource );

	}

	/**
	 * Handle dispute
	 *
	 * @since 1.3.2
	 * @param array  $resource Webhook content.
	 * @param string $callback Method to call after checked the webhook resource.
	 */
	public function handle_dispute( $resource, $callback ) {
		// retrieve order from resource.
		$disputed_transactions = isset( $resource['disputed_transactions'] ) ? (array) $resource['disputed_transactions'] : false;

		if ( ! $disputed_transactions ) {
			self::log( 'Cannot find any disputed transaction inside webhook resource.' );

			return;
		}

		foreach ( $disputed_transactions as $transaction ) {
			$transaction_id = isset( $transaction['seller_transaction_id'] ) ? $transaction['seller_transaction_id'] : '';

			if ( empty( $transaction['invoice_number'] ) ) {
				self::log( "The invoice number is not set inside the disputed transaction $transaction_id." );

				continue;
			}

			$order_id = str_replace( YITH_PayPal::get_instance()->get_gateway()->get_prefix(), '', $transaction['invoice_number'] );
			$order    = wc_get_order( $order_id );

			if ( ! $order ) {
				self::log( 'The order with id ' . $order_id . ' is not found.' );

				continue;
			}

			if ( $transaction_id && $transaction_id !== $order->get_transaction_id() ) {
				self::log( "The order transaction id {$order->get_transaction_id()} doesn't match dispute transaction $transaction_id." );

				continue;
			}

			$this->$callback( $order, $resource );
		}
	}


	/**
	 * Complete the order.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order Order to complete.
	 * @param array    $resource Webhook request.
	 * @throws Exception Throws Exception.
	 */
	private function process_payment_complete( $order, $resource ) {

		if ( $order->has_status( wc_get_is_paid_statuses() ) ) {
			self::log( 'Aborting the order ' . $order->get_id() . ' the order is already completed.' );

			return;
		}

		$this->validate_currency( $order, $resource['amount']['currency_code'] );
		$this->save_paypal_meta_data( $order, $resource );

		if ( 'completed' === strtolower( $resource['status'] ) ) {

			if ( $order->has_status( 'cancelled' ) ) {
				self::log( 'Payment for cancelled order ' . $order->get_id() . ' received', 'warning' );
			}

			YITH_PayPal_Order_Helper::register_payment_from_webhook( $order, $resource );
		}
	}

	/**
	 * Process the refund of the order.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order Order to refund.
	 * @param array    $resource Webhook request.
	 * @throws Exception Throws Exception.
	 */
	private function process_refund( $order, $resource ) {

		// check the refund status.
		if ( 'completed' !== strtolower( $resource['status'] ) ) {
			self::log( 'The refund process for the order ' . $order->get_id() . ' is not completed.' );

			return;
		}

		$amount              = $resource['amount']['value'];
		$currency            = $resource['amount']['currency_code'];
		$refund_tracking_ids = (array) $order->get_meta( '_yith_ppwc_refund_tracking_ids' );

		if ( in_array( $resource['id'], $refund_tracking_ids ) ) { //phpcs:ignore
			self::log( 'The refund process for the order ' . $order->get_id() . ' with reference ' . $resource['id'] . ' is already registered.', 'info' );

			return;
		}

		// handle full refunds.
		if ( $order->get_total() <= $amount ) {
			/* translators: %s: Placeholder is the number of transaction. */
			$order->update_status( 'refunded', sprintf( esc_html_x( 'Refunded this order via PayPal. Reference ID: %s.', 'Order note when an order is refunded: Placeholder is the number of transaction', 'yith-paypal-payments-for-woocommerce' ), $resource['id'] ) );
		} else {
			// refund partial order.
			$order_refund = wc_create_refund(
				array(
					'amount'   => $amount,
					/* translators: %s: Placeholder is the number of transaction. */
					'reason'   => sprintf( esc_html_x( 'Refunded via PayPal. Reference ID: %s.', 'Order note when an order is refunded: Placeholder is the number of transaction', 'yith-paypal-payments-for-woocommerce' ), $resource['id'] ),
					'order_id' => $order->get_id(),
				)
			);

			if ( $order_refund ) {
				$order_refund->set_refunded_by( 'PayPal' );
				$order_refund->save();
			}

			// translators: 1. Reference id, 2. Refund Amount.
			$order->add_order_note( sprintf( esc_html_x( 'Refunded partially via PayPal. Reference ID: %1$s. Amount: %2$s.', 'Order note when an order is refunded: Placeholder is the number of transaction', 'yith-paypal-payments-for-woocommerce' ), $resource['id'], wc_price( $amount, $currency ) ) );
		}

		$order->update_meta_data( '_yith_ppwc_refund_tracking_ids', $resource['id'] );
		$order->save_meta_data();
	}

	/**
	 * Process reversed order.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order Order to reverse.
	 * @param array    $resource Webhook request.
	 */
	private function process_reversed( $order, $resource ) {
		/* translators: %s: Reference id of this reversed action. */
		$order->update_status( 'on-hold', sprintf( esc_html_x( 'Reversed via PayPal. Reference ID: %s.', 'Order note, %s is the reference id.', 'yith-paypal-payments-for-woocommerce' ), wc_clean( $resource['id'] ) ) );
	}

	/**
	 * Process failed payments when a payment is denied.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order Order to register failed payment.
	 * @param array    $resource Webhook request.
	 */
	private function process_failed_payment( $order, $resource ) {
		/* translators: %s: Reference id of this denied action. */
		if ( ! $order->has_status( 'failed' ) ) {
			/* translators: %s: Reference id. */
			$order->update_status( 'failed', sprintf( esc_html_x( 'Denied payment via PayPal. Reference ID: %s.', 'Order note, %s is the reference id.', 'yith-paypal-payments-for-woocommerce' ), wc_clean( $resource['id'] ) ) );
		}
	}

	/**
	 * Process pending payments when a payment is denied.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order Order to set in pending.
	 * @param array    $resource Webhook request.
	 */
	private function process_pending_payment( $order, $resource ) {

		if ( ! $order->has_status( 'pending' ) ) {
			/* translators: %s: Reference id of this denied action. */
			$order->update_status( 'pending', sprintf( esc_html_x( 'Pending payment via PayPal. Reference ID: %s.', 'Order note, %s is the reference id.', 'yith-paypal-payments-for-woocommerce' ), wc_clean( $resource['id'] ) ) );
		}
	}

	/**
	 * Register Authorization code inside an order if is necessary.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order Order where register the authorization.
	 * @param array    $resource Webhook request.
	 * @throws Exception Throws Exception.
	 */
	private function register_authorization( $order, $resource ) {
		if ( 'VOIDED' === $resource['status'] ) {
			return;
		}

		$order_authorize_info = $order->get_meta( '_yith_ppwc_paypal_authorize_info' );

		$is_registered = ! empty( $order_authorize_info ) && $order_authorize_info['id'] === $resource['id'];

		if ( ! $is_registered ) {
			$order->update_meta_data( '_yith_ppwc_paypal_authorize_info', $resource );
		}

	}

	/**
	 * Void the authorization of an order.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order Order.
	 * @param array    $resource Webhook request.
	 * @throws Exception Throws Exception.
	 */
	private function void_authorization( $order, $resource ) {
		YITH_PayPal::get_instance()->get_gateway()->maybe_void_authorized_payment( $order->get_id(), $resource['id'] );
		/**
		 * APPLY_FILTERS: yith_ppwc_order_status_after_void_authorization
		 *
		 * Filters the order status after voiding the authorization.
		 *
		 * @param WC_Order $order Order.
		 * @param array    $resource Webhook request.
		 *
		 * @return string
		 */
		$new_status = apply_filters( 'yith_ppwc_order_status_after_void_authorization', 'cancelled', $order, $resource );
		$order->update_status( $new_status );
	}

	/**
	 * Set order on-hold with specific message
	 *
	 * @since 1.3.2
	 * @param WC_Order $order Order.
	 * @param array    $resource Webhook request.
	 * @throws Exception Throws Exception.
	 */
	private function process_open_dispute( $order, $resource ) {
		$case_number = isset( $resource['dispute_id'] ) ? sanitize_text_field( wp_unslash( $resource['dispute_id'] ) ) : '';
		$message     = _x( 'Reversed via PayPal.', 'Order note', 'yith-paypal-payments-for-woocommerce' );

		if ( $order->has_status( 'on-hold' ) ) {
			self::log( "Order {$order->get_id()} is already On hold after reversal." );
			return;
		}

		if ( $case_number ) {
			$case_link = sprintf( 'https://www.sandbox.paypal.com/resolutioncenter/view/%s/inquiry', $case_number );

			// translators: 1. Url to dispute management on PayPal dashboard. 2. Case number.
			$message .= ' ' . sprintf( _x( 'New dispute opened: case <a href="%1$s">%2$s</a>', 'Order note', 'yith-paypal-payments-for-woocommerce' ), $case_link, $case_number );
		}

		$order->update_status( 'on-hold', $message );
	}

	/**
	 * If dispute is resolved in seller favor, return order to completed; otherwise does nothing.
	 *
	 * @since 1.3.2
	 * @param WC_Order $order Order.
	 * @param array    $resource Webhook request.
	 * @throws Exception Throws Exception.
	 */
	private function process_resolved_dispute( $order, $resource ) {
		$case_number    = isset( $resource['dispute_id'] ) ? sanitize_text_field( wp_unslash( $resource['dispute_id'] ) ) : '';
		$transaction_id = isset( $resource['seller_transaction_id'] ) ? sanitize_text_field( wp_unslash( $resource['seller_transaction_id'] ) ) : '';
		$outcome        = isset( $resource['dispute_outcome'], $resource['dispute_outcome']['outcome_code'] ) ? sanitize_text_field( wp_unslash( $resource['dispute_outcome']['outcome_code'] ) ) : false;

		switch ( $outcome ) {
			case 'RESOLVED_SELLER_FAVOUR':
			case 'CANCELED_BY_BUYER':
				// translators: 1. Case number.
				$order->add_order_note( sprintf( _x( 'Dispute %s resolved in seller\'s favor', 'Order note', 'yith-paypal-payments-for-woocommerce' ), $case_number ) );
				$order->payment_complete( $transaction_id );

				self::log( "Dispute {$case_number} resolved in seller favor.", 'info' );
				return;
			case 'RESOLVED_BUYER_FAVOUR':
				// log and do nothing.
				self::log( "Dispute {$case_number} resolved in buyer favor.", 'info' );
				return;
			default:
				// log and do nothing.
				self::log( "Dispute {$case_number} resolved with {$outcome} outcome.", 'info' );
				return;
		}
	}

	/**
	 * Check currency from Webhook matches the order.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order Order object.
	 * @param string   $currency Currency code.
	 */
	protected function validate_currency( $order, $currency ) {
		if ( $order->get_currency() !== $currency ) {
			self::log( 'Payment error: Currencies do not match (sent "' . $order->get_currency() . '" | returned "' . $currency . '")' );

			/* translators: %s: currency code. */
			$order->update_status( 'on-hold', sprintf( esc_html_x( 'Validation error: PayPal currencies do not match (code %s).', 'Order note, the placeholder is the currency code', 'yith-paypal-payments-for-woocommerce' ), $currency ) );
			exit;
		}
	}

	/**
	 * Check payment amount from Webhook matches the order.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order Order object.
	 * @param int      $amount Amount to validate.
	 */
	protected function validate_amount( $order, $amount ) {
		if ( number_format( $order->get_total(), 2, '.', '' ) !== number_format( $amount, 2, '.', '' ) ) {
			self::log( 'Payment error: Amounts do not match (gross ' . $amount . ')' );

			/* translators: %s: Amount. */
			$order->update_status( 'on-hold', sprintf( esc_html_x( 'Validation error: PayPal amounts do not match (gross %s).', 'Order note, the placeholder is the amount of the order.', 'yith-paypal-payments-for-woocommerce' ), $amount ) );
			exit;
		}
	}

	/**
	 * Save PayPal metadata inside the order.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order Order object.
	 * @param integer  $resource Webhook content.
	 * @throws Exception Throws Exception.
	 */
	protected function save_paypal_meta_data( $order, $resource ) {

		$order->update_meta_data( '_yith_ppwc_invoice_id', $resource['invoice_id'] );

		if ( ! empty( $resource['links'] ) ) {
			$up_relation_key = array_search( 'up', array_column( $resource['links'], 'rel' ), true );
			if ( false !== $up_relation_key ) {
				$link = array_filter( explode( '/', $resource['links'][ $up_relation_key ]['href'] ) );
				if ( ! in_array( 'authorizations', $link ) ) { //phpcs:ignore
					$paypal_order_id = end( $link );
					$transaction     = YITH_PayPal_Controller::load( 'transaction' );
					try {
						$order_details = $transaction->get_order_details( $paypal_order_id );
						! empty( $order_details['payer']['email_address'] ) && $order->update_meta_data( '_yith_ppwc_paypal_address', $order_details['payer']['email_address'] );
						! empty( $order_details['payer']['payer_id'] ) && $order->update_meta_data( '_yith_ppwc_payer_id', $order_details['payer']['payer_id'] );
					} catch ( Exception $e ) {
						self::log( 'There was an issue during the PayPal order details request and some payer info are not saved. ' . $paypal_order_id );
					}
				}
			}
		}

		$order->save_meta_data();
	}

	/**
	 * Get webhook URL
	 *
	 * @since 1.0.0
	 */
	public function get_webhook_url() {
		/**
		 * APPLY_FILTERS:yith_ppcc_webhook_url
		 *
		 * Filters the WooCommerce webhook URL
		 *
		 * @return string
		 */
		return apply_filters( 'yith_ppcc_webhook_url', get_site_url( null, '', 'https' ) . '/?wc-api=' . $this->pagename );
	}
}