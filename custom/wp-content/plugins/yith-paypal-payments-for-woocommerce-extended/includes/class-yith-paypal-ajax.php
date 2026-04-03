<?php
/**
 * AJAX handler class
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH PayPal Payments for WooCommerce
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class YITH_PayPal_Ajax
 */
class YITH_PayPal_Ajax {

	/**
	 * The AJAX action key
	 *
	 * @var string
	 */
	const AJAX_ACTION = 'yith_ppwc_ajax_request';

	/**
	 * The array of accepted request
	 *
	 * @var array
	 */
	public $requests = array();

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->requests = $this->load_requests();

		// Handle WC AJAX requests.
		add_action( 'wc_ajax_' . self::AJAX_ACTION, array( $this, 'handle_ajax' ) );
		// Handle ADMIN AJAX requests.
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'handle_ajax' ) );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( $this, 'handle_ajax' ) );
	}

	/**
	 * An array of valid request
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function load_requests() {
		return array(
			'validate_checkout',
			'validate_product_cart',
			'create_order',
			'approve_order',
			'client_token',
			'partial_payment',
			'partial_payment_refund',
			'void_payment_authorization',
			'cart_info',
			'product_cart_info',
			'update_shipping_contact_applepay',
			'update_shipping_method_applepay',
			'update_payment_data',
			'maybe_clean_session',
			'validate_merchant'
		);
	}

	/**
	 * Handle AJAX request
	 *
	 * @since 1.0.0
	 * @throws Exception Throws Exception.
	 */
	public function handle_ajax() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended

		try {
			// Check if request if valid.
			$request = isset( $_REQUEST['request'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['request'] ) ) : '';

			if ( ! $request || ! in_array( $request, $this->requests, true ) || ! is_callable( array( $this, 'handle_' . $request ) ) ) {
				throw new Exception( 'Error: Invalid request!' );
			}

			$res = call_user_func( array( $this, 'handle_' . $request ) );
			if ( $res ) {
				$this->handle_ajax_request_success( $res );
			} else {
				throw new Exception( __( 'An error occurred while processing the request!', 'yith-paypal-payments-for-woocommerce' ) );
			}
		} catch ( Exception $e ) {
			$this->handle_ajax_request_failure( array(), $e->getMessage() );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Handle a partial payment request
	 *
	 * @since 1.0.0
	 * @return mixed
	 * @throws Exception Throws Exception.
	 */
	public function handle_partial_payment() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( - 1 );
		}

		$order_id       = isset( $_REQUEST['order_id'] ) ? absint( $_REQUEST['order_id'] ) : 0;
		$payment_amount = isset( $_REQUEST['payment_amount'] ) ? wc_format_decimal( wp_unslash( $_REQUEST['payment_amount'] ), wc_get_price_decimals() ) : 0; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$payed_amount   = isset( $_REQUEST['payed_amount'] ) ? wc_format_decimal( wp_unslash( $_REQUEST['payed_amount'] ), wc_get_price_decimals() ) : 0; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$order       = wc_get_order( $order_id );
		$total_payed = YITH_PayPal_Order_Helper::get_total_partial_payed( $order );
		$max_payment = YITH_PayPal_Order_Helper::get_max_partial_payment( $order );

		if ( ! $payment_amount || $max_payment < $payment_amount || 0 > $payment_amount ) {
			throw new Exception( esc_html__( 'Invalid partial payment amount', 'yith-paypal-payments-for-woocommerce' ) );
		}

		if ( wc_format_decimal( $total_payed, wc_get_price_decimals() ) !== $payed_amount ) {
			throw new Exception( esc_html__( 'Error processing partial payment. Please try again.', 'yith-paypal-payments-for-woocommerce' ) );
		}

		// Create the refund object.
		$payment = YITH_PayPal_Order_Helper::create_partial_payment(
			array(
				'amount'   => $payment_amount,
				'order_id' => $order_id,
			)
		);

		if ( is_wp_error( $payment ) ) {
			throw new Exception( $payment->get_error_message() );
		}

		return true;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Handle a partial payment refund request
	 *
	 * @since 1.0.0
	 * @return mixed
	 * @throws Exception Throws Exception.
	 */
	public function handle_partial_payment_refund() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( - 1 );
		}

		$order_id        = isset( $_REQUEST['order_id'] ) ? absint( $_REQUEST['order_id'] ) : 0;
		$refund_amount   = isset( $_REQUEST['refund_amount'] ) ? wc_format_decimal( wp_unslash( $_REQUEST['refund_amount'] ), wc_get_price_decimals() ) : 0; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$refunded_amount = isset( $_REQUEST['refunded_amount'] ) ? wc_format_decimal( wp_unslash( $_REQUEST['refunded_amount'] ), wc_get_price_decimals() ) : 0; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$refund_reason   = isset( $_REQUEST['refund_reason'] ) ? wp_unslash( $_REQUEST['refund_reason'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$partial_items   = isset( $_REQUEST['partial_items'] ) ? json_decode( wp_unslash( $_REQUEST['partial_items'] ), true ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$partial_items   = is_array( $partial_items ) ? array_filter( $partial_items ) : array();
		$api_refund      = isset( $_REQUEST['api_refund'] ) && 'true' === $_REQUEST['api_refund'];

		$order      = wc_get_order( $order_id );
		$max_refund = $order ? wc_format_decimal( YITH_PayPal_Order_Helper::get_total_partial_payed( $order ) - $order->get_total_refunded(), wc_get_price_decimals() ) : 0;

		if ( ! $refund_amount || $max_refund < $refund_amount || 0 > $refund_amount || empty( $partial_items ) ) {
			throw new Exception( esc_html__( 'Invalid refund amount', 'yith-paypal-payments-for-woocommerce' ) );
		}

		if ( wc_format_decimal( $order->get_total_refunded(), wc_get_price_decimals() ) !== $refunded_amount ) {
			throw new Exception( esc_html__( 'Error processing the refund. Please try again.', 'yith-paypal-payments-for-woocommerce' ) );
		}

		// Double check for partials amount and refund.
		foreach ( $partial_items as $partial_id => $refund_partial_amount ) {
			$partial = wc_get_order( $partial_id );
			if ( ! $partial ) {
				throw new Exception( esc_html__( 'Error processing the refund. Please try again.', 'yith-paypal-payments-for-woocommerce' ) );
			}

			if ( $partial->get_remaining_refund_amount() < $refund_partial_amount ) {
				// translators: 1 - Order id, 2 - Transaction id.
				throw new Exception( sprintf( esc_html__( 'Invalid refund amount for partial #%1$s (Transaction ID: %2$s)', 'yith-paypal-payments-for-woocommerce' ), $partial->get_id(), $partial->get_transaction_id() ) );
			}
		}

		foreach ( $partial_items as $partial_id => $refund_partial_amount ) {
			// Create the refund object.
			$refund = YITH_PayPal_Order_Helper::create_partial_payment_refund(
				array(
					'amount'         => $refund_partial_amount,
					'reason'         => $refund_reason,
					'order_id'       => $order_id,
					'partial_id'     => $partial_id,
					'refund_payment' => $api_refund,
				)
			);

			if ( is_wp_error( $refund ) ) {
				throw new Exception( $refund->get_error_message() );
			}

			// Trigger notification emails.
			if ( ( $order->get_remaining_refund_amount() - $refund_partial_amount ) > 0 ) {
				do_action( 'woocommerce_order_partially_refunded', $order->get_id(), $refund->get_id() );
			} else {
				do_action( 'woocommerce_order_fully_refunded', $order->get_id(), $refund->get_id() );

				$parent_status = apply_filters( 'woocommerce_order_fully_refunded_status', 'refunded', $order->get_id(), $refund->get_id() );
				if ( $parent_status ) {
					$order->update_status( $parent_status );
				}

				// order is fully refunded, no more refund required.
				break;
			}
		}

		return true;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Void a payment authorization
	 *
	 * @return mixed
	 * @throws Exception Throws Exception.
	 */
	public function handle_void_payment_authorization() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( - 1 );
		}

		$order_id = isset( $_REQUEST['order_id'] ) ? absint( $_REQUEST['order_id'] ) : 0;
		$order    = wc_get_order( $order_id );
		$gateway  = YITH_PayPal::get_instance()->get_gateway();
		if ( ! $order || empty( $gateway ) ) {
			throw new Exception( esc_html__( 'Error processing void authorization. Please try again.', 'yith-paypal-payments-for-woocommerce' ) );
		}

		$res = $gateway->maybe_void_authorized_payment( $order->get_id() );
		if ( is_wp_error( $res ) ) {
			throw new Exception( $res->get_error_message() );
		}

		return true;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Validate form checkout before send request to PayPal
	 *
	 * @since 1.0.0
	 * @return mixed
	 * @throws Exception Throws Exception.
	 */
	public function handle_validate_checkout() {
		try {

			// Make sure there are no WC notices.
			wc_clear_notices();

			$checkout = new YITH_PayPal_Checkout_Helper();
			$checkout->process_checkout();

			if ( wc_notice_count( 'error' ) ) {
				throw new Exception( 'Error processing checkout form' );
			}

			// Make sure there are no WC notices.
			wc_clear_notices();
			return true;

		} catch ( Exception $e ) {
			$has_error_notice = ! wc_notice_count( 'error' );
			$this->handle_ajax_request_failure(
				array(
					'messages' => wc_print_notices( true ),
				),
				$e->getMessage(),
				$has_error_notice
			);
		}

		return false;
	}

	/**
	 * Validate product cart form submit. The form is completely handled by WooCommerce, we just need to check if there are errors.
	 *
	 * @since 1.0.0
	 * @return mixed
	 * @throws Exception Throws Exception.
	 */
	public function handle_validate_product_cart() {
		try {
			if ( wc_notice_count( 'error' ) ) {
				$errors = wp_list_pluck( wc_get_notices( 'error' ), 'notice' );
				throw new Exception( 'Error processing product cart form. Error details: ' . print_r( $errors, true ) ); // phpcs:ignore
			}

			wc_clear_notices();

			return true;

		} catch ( Exception $e ) {
			$this->handle_ajax_request_failure(
				array(
					'reload' => true,
				),
				$e->getMessage(),
				0 === wc_notice_count( 'error' )
			);
		}

		return false;
	}

	/**
	 * Handle create order
	 *
	 * @since 1.0.0
	 * @return mixed
	 * @throws Exception Throws Exception.
	 */
	public function handle_create_order() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		try {

			$checkout_request = isset( $_REQUEST['checkoutRequest'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['checkoutRequest'] ) ) : '';

			if ( 'checkout' === $checkout_request ) {
				wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );
			}

			$gateway        = YITH_PayPal::get_instance()->get_gateway();
			$handler        = YITH_PayPal_Controller::load( 'transaction' );
			$flow           = isset( $_REQUEST['flow'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['flow'] ) ) : '';
			$order_id       = isset( $_REQUEST['orderID'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderID'] ) ) : '';
			$funding_source = isset( $_REQUEST['fundingSource'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['fundingSource'] ) ) : '';

			if ( ! empty( $funding_source ) ) {
				WC()->session->set( 'yith_ppwc_funding_source', $funding_source );
			}

			if ( $order_id && 'pay_order' === $checkout_request ) {
				return $handler->create_order_to_pay_order( $flow, $order_id );
			}

			if ( ! in_array( $checkout_request, array( 'checkout', 'pay_order' ), true ) && $gateway->is_fast_checkout_enabled() && ! WC()->cart->needs_shipping() && ! YITH_PayPal_Google_Pay::is_googleplay_flow() && ! YITH_PayPal_Apple_Pay::is_applepay_flow()) {
				$flow = 'ecs';
			}

			return $handler->create_order( $flow );

		} catch ( Exception $e ) {
			$this->handle_ajax_request_failure( array(), $e->getMessage(), true );
		}

		return false;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Handle approve order request
	 *
	 * @since 1.0.0
	 * @throws Exception Throws Exception.
	 */
	public function handle_approve_order() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		try {
			if ( empty( $_REQUEST['orderID'] ) ) {
				throw new Exception( 'The PayPal order ID cannot be empty' );
			}

			$checkout_request = isset( $_REQUEST['checkoutRequest'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['checkoutRequest'] ) ) : '';
			$order_id         = sanitize_text_field( wp_unslash( $_REQUEST['orderID'] ) );
			$funding_source   = isset( $_REQUEST['fundingSource'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['fundingSource'] ) ) : '';
			$flow             = isset( $_REQUEST['flow'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['flow'] ) ) : '';
			$handler          = YITH_PayPal_Controller::load( 'transaction' );

			if ( ! empty( $funding_source ) ) {
				WC()->session->set( 'yith_ppwc_funding_source', $funding_source );
			}

			if ( in_array( $checkout_request, array( 'checkout', 'pay_order' ), true ) && empty( $funding_source ) ) {
				WC()->session->set( 'checkoutRequest', $checkout_request );
				$handler->approve_order_checkout( $order_id );

				return true;
			} else {
				$handler->approve_order( $order_id );
				$gateway = YITH_PayPal::get_instance()->get_gateway();
				if ( $gateway->is_fast_checkout_enabled() && ! WC()->cart->needs_shipping() && ! in_array( $flow, array( 'googlepay', 'applepay' ), true ) ) {
					$checkout = new YITH_PayPal_Checkout_Helper();
					$checkout->process_fast_checkout();
				}
				/* Google Pay & Apple Pay Express Checkout Process */
				if ( 'googlepay' === $flow || 'applepay' === $flow ) {
					$checkout = new YITH_PayPal_Checkout_Helper();
					$checkout->process_express_checkout( $flow );
				}

				return array(
					'redirect' => wc_get_checkout_url(),
				);

				die;
			}
		} catch ( Exception $e ) {
			$this->handle_ajax_request_failure( array(), $e->getMessage(), true );
		}

		return false;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Handle approve order request on checkout page
	 *
	 * @since 1.0.0
	 * @throws Exception Throws Exception.
	 */
	public function handle_approve_order_checkout() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		try {
			if ( empty( $_REQUEST['orderID'] ) ) {
				throw new Exception( 'The PayPal order ID cannot be empty' );
			}

			$order_id = sanitize_text_field( wp_unslash( $_REQUEST['orderID'] ) );
			$handler  = YITH_PayPal_Controller::load( 'transaction' );
			$handler->approve_order_checkout( $order_id );

			// If no error process checkout.
			wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );
			WC()->checkout()->process_checkout();
		} catch ( Exception $e ) {
			$this->log_error( $e->getMessage() );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Handle client token
	 *
	 * @return void
	 */
	public function handle_client_token() {
		$login                = YITH_PayPal_Controller::load( 'login' );
		$client_token_request = $login->get_client_token();
		wp_send_json( $this->format_response_data( array( 'token' => $client_token_request ), 'success' ) );
	}

	/**
	 * Handle an AJAX request response success
	 *
	 * @since 1.0.0
	 * @param mixed  $data The data to send as json response.
	 * @param string $message The error message to log.
	 * @return void
	 */
	protected function handle_ajax_request_success( $data, $message = '' ) {
		// Send success.
		wp_send_json( $this->format_response_data( $data, 'success' ) );
	}

	/**
	 * Handle an AJAX request response error
	 *
	 * @since 1.0.0
	 * @param mixed   $data The data to send as json response.
	 * @param string  $message The error message to log.
	 * @param boolean $wc_notice If add or not a wc error notice. Useful for all WooCommerce sections.
	 * @return void
	 */
	protected function handle_ajax_request_failure( $data = array(), $message = '', $wc_notice = false ) {
		// Add generic error notice if $wc_notice is true.
		$wc_notice && wc_add_notice( __( 'An error occurred while processing the request!', 'yith-paypal-payments-for-woocommerce' ), 'error' );
		// Log the error.
		$this->log_error( $message );
		// Add message to data if error is not present.
		if ( ! isset( $data['error'] ) ) {
			$data['error'] = $message;
		}

		// Send error.
		wp_send_json( $this->format_response_data( $data, 'failure' ) );
	}

	/**
	 * Get current cart info
	 *
	 * @return bool
	 */
	protected function handle_cart_info() {
		if ( ! $this->check_nonce() ) {
			wp_send_json_error( 'Failed nonce check' );
			return false;
		}

		if ( is_callable( 'wc_maybe_define_constant' ) ) {
			wc_maybe_define_constant( 'WOOCOMMERCE_CART', true );
		}

		$funding      = isset( $_POST['funding'] ) ? sanitize_key( $_POST['funding'] ) : '';//phpcs:ignore WordPress.Security.NonceVerification.Missing
		$currency     = get_woocommerce_currency();
		$total        = (float) WC()->cart->get_total( 'numeric' );
		$total_string = $this->total_to_string( $total, $currency );

		$base_location     = wc_get_base_location();
		$shop_country_code = $base_location['country'] ?? '';

		if ( isset( WC()->cart ) && ! WC()->cart->is_empty() ) {
			if ( YITH_PayPal_Apple_Pay::get_funding_source() === $funding ) {
				$response = $this->cart_info_for_apple_pay( $currency, $shop_country_code, $total_string );
			} else {
				$response = $this->cart_info_for_google_pay( $currency, $shop_country_code, $total_string );
			}
			wp_send_json_success( $response );
			return true;
		} else {
			wp_send_json_error( 'The cart is empty' );
			return false;
		}
	}

	/**
	 *
	 * Get current cart info array response for Google Pay
	 *
	 * @param string $currency .
	 * @param string $shop_country_code .
	 * @param string $total_string .
	 *
	 * @return array
	 */
	protected function cart_info_for_google_pay( $currency, $shop_country_code, $total_string ) {

		$response = array(
			'currencyCode'     => $currency,
			'countryCode'      => $shop_country_code,
			'amount'           => WC()->cart->get_total( 'raw' ),
			'totalPrice'       => $total_string,
			'totalPriceLabel'  => __( 'Total', 'yith-paypal-payments-for-woocommerce' ),
			'totalPriceStatus' => 'FINAL',
		);

		return $response;
	}

	/**
	 * Get current cart info array response for Apple Pay
	 *
	 * @param string $currency .
	 * @param string $shop_country_code .
	 *
	 * @return array
	 */
	protected function cart_info_for_apple_pay( $currency, $shop_country_code ) {
		$response = array(
			'currencyCode' => $currency,
			'countryCode'  => $shop_country_code,
			'total'        => array(
				'label'  => __( 'Total', 'yith-paypal-payments-for-woocommerce' ),
				'type'   => 'final',
				'amount' => (float) WC()->cart->get_total( 'numeric' ),
			),
		);

		return $response;
	}

	/**
	 * Get currentc cart from product single page for Apply Pay
	 *
	 * @return array
	 */
	protected function handle_product_cart_info() {
		if ( ! $this->check_nonce() ) {
			wp_send_json_error( 'Cannot get informations' );
			return false;
		}
		if ( isset( $_POST['product_id'] ) && $this->check_nonce() ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing
			$currency          = get_woocommerce_currency();
			$base_location     = wc_get_base_location();
			$shop_country_code = $base_location['country'] ?? '';

			$product = wc_get_product( sanitize_key( $_POST['product_id'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Missing
			
			$qty = ! empty( sanitize_key( $_POST['product_qty'] ) ) ? sanitize_key( $_POST['product_qty'] ) : 1;

			$response = array(
				'currencyCode' => $currency,
				'countryCode'  => $shop_country_code,
				'total'        => array(
					'label'  => __( 'Total', 'yith-paypal-payments-for-woocommerce' ),
					'type'   => 'final',
					'amount' => $product->get_price() * (int)$qty,
				),
			);
			wp_send_json_success( $response );
			return true;
		}

		wp_send_json_error( 'No Product Informations' );
			return false;
	}

	/**
	 * Update Google Pay payment data
	 *
	 * @return mixed|bool
	 * @throws Exception Throws Exception.
	 */
	protected function handle_update_payment_data() {
		try {
			if ( ! $this->check_nonce() ) {
				return;
			}

			$data = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing

			// Validate payment data.
			if ( ! isset( $data['paymentData'] ) ) {
				throw new RuntimeException(
					__( 'No paymentData provided.', 'yith-paypal-payments-for-woocommerce' )
				);
			}

			$payment_data = str_replace( '\\', '', $data['paymentData'] );
			$payment_data = json_decode( html_entity_decode( $payment_data ), true );

			if ( ! is_array( $payment_data ) ) {
				throw new RuntimeException(
					__( 'PaymentData provided has wrong format.', 'yith-paypal-payments-for-woocommerce' )
				);
			}

			// Set context as cart.
			if ( is_callable( 'wc_maybe_define_constant' ) ) {
				wc_maybe_define_constant( 'WOOCOMMERCE_CART', true );
			}

			$this->update_addresses( $payment_data );
			$this->update_shipping_method( $payment_data );

			WC()->cart->calculate_shipping();
			WC()->cart->calculate_fees();
			WC()->cart->calculate_totals();

			$total = (float) WC()->cart->get_total( 'numeric' );

			$base_location     = wc_get_base_location();
			$shop_country_code = $base_location['country'];
			$currency_code     = get_woocommerce_currency();

			$flow = isset( $data['flow'] ) ? sanitize_text_field( wp_unslash( $data['flow'] ) ) : '';

			if ( 'googlepay' === $flow ) {
				wp_send_json_success(
					array(
						'total'            => $total,
						'total_str'        => $this->total_to_string( $total, $currency_code ),
						'currency_code'    => $currency_code,
						'country_code'     => $shop_country_code,
						'shipping_options' => $this->get_shipping_options(),
					)
				);
			}

			return true;
		} catch ( Exception $e ) {
			$this->handle_ajax_request_failure( array(), $e->getMessage(), true );
			return false;
		}
	}
	/**
	 * Update addresses.
	 *
	 * @param array  $datas The data provided by funding source.
	 * @param string $funding funding source slug.
	 * @return void
	 */
	private function update_addresses( $datas, $funding = 'googlepay' ) {
		if ( 'googlepay' === $funding && ! in_array( $datas['callbackTrigger'] ?? '', array( 'SHIPPING_ADDRESS', 'INITIALIZE' ), true ) ) {
			return;
		}

		/**
		 * The shipping methods.
		 *
		 * @var \WC_Customer|null $customer
		 */
		$customer = WC()->customer;
		if ( ! $customer ) {
			return;
		}

		$email = $customer->get_billing_email();
		// If the current user exists (logged in) we backup as we will overwrite the values so we could recover it if the payment process will be interrupted.
		if ( ! empty( $email ) ) {
			WC()->session->set( 'yith_ppwc_customer_backup', $customer );
		}

		if ( 'googlepay' === $funding ) {
			$customer->set_billing_postcode( $datas['shippingAddress']['postalCode'] ?? '' );
			$customer->set_billing_country( $datas['shippingAddress']['countryCode'] ?? '' );
			$customer->set_billing_state( $datas['shippingAddress']['administrativeArea'] ?? '' );
			$customer->set_billing_city( $datas['shippingAddress']['locality'] ?? '' );

			$customer->set_shipping_postcode( $datas['shippingAddress']['postalCode'] ?? '' );
			$customer->set_shipping_country( $datas['shippingAddress']['countryCode'] ?? '' );
			$customer->set_shipping_state( $datas['shippingAddress']['administrativeArea'] ?? '' );
			$customer->set_shipping_city( $datas['shippingAddress']['locality'] ?? '' );
		}

		if ( 'applepay' === $funding ) {
			$customer->set_billing_postcode( $datas['postalCode'] ?? '' );
			$customer->set_billing_country( $datas['countryCode'] ?? '' );
			$customer->set_billing_state( $datas['administrativeArea'] ?? '' );
			$customer->set_billing_city( $datas['locality'] ?? '' );

			$customer->set_shipping_postcode( $datas['postalCode'] ?? '' );
			$customer->set_shipping_country( $datas['countryCode'] ?? '' );
			$customer->set_shipping_state( $datas['administrativeArea'] ?? '' );
			$customer->set_shipping_city( $datas['locality'] ?? '' );
		}

		// Save the data.
		$customer->save();

		WC()->session->set( 'customer', $customer->get_data() );
	}
	/**
	 * Get shipping options for payment modals (Google Pay)
	 *
	 * @return array
	 */
	protected function get_shipping_options() {
		$shipping_options = array();
		$dummy_shipping   = array(
			'id'          => 'no_ship',
			'label'       => 'FREE',
			'description' => html_entity_decode(
				wp_strip_all_tags( wc_price( 0, array( 'currency' => get_woocommerce_currency() ) ) )
			),
		);

		$calculated_packages = WC()->shipping->calculate_shipping(
			WC()->cart->get_shipping_packages()
		);

		if ( ! isset( $calculated_packages[0] ) && ! isset( $calculated_packages[0]['rates'] ) ) {
			$shipping_options[] = $dummy_shipping;
		}

		if ( ! WC()->cart->needs_shipping() ) {
			$shipping_options[] = $dummy_shipping;
		} else {
			foreach ( $calculated_packages[0]['rates'] as $rate ) {
				$cost = (float) $rate->get_cost() + WC()->cart->get_shipping_tax();
				/**
				 * The shipping rate.
				 *
				 * @var \WC_Shipping_Rate $rate
				 */
				$shipping_options[] = array(
					'id'          => $rate->get_id(),
					'label'       => $rate->get_label(),
					'description' => html_entity_decode(
						wp_strip_all_tags(
							wc_price( (float) $cost, array( 'currency' => get_woocommerce_currency() ) )
						)
					),
				);
			}
		}

		if ( ! isset( $shipping_options[0] ) ) {
			$shipping_options[] = $dummy_shipping;
		}

		return array(
			'defaultSelectedOptionId' => $shipping_options[0]['id'],
			'shippingOptions'         => $shipping_options,
		);
	}

	/**
	 * Update shipping method.
	 *
	 * @param array $data The payment data.
	 * @param string $funding_source 
	 * @return void
	 */
	private function update_shipping_method( $data, $funding_source = 'googlepay' ) {
		$rate_id = '';

		if ( 'googlepay' === $funding_source ) {
			$rate_id             = $data['shippingOptionData']['id'];
		} else if ( 'applepay' === $funding_source ) {
			$rate_id = $data['identifier'];
		}
		
		$calculated_packages = WC()->shipping->calculate_shipping(
			WC()->cart->get_shipping_packages()
		);

		if ( $rate_id && isset( $calculated_packages[0]['rates'][ $rate_id ] ) ) {
			WC()->session->set( 'chosen_shipping_methods', array( $rate_id ) );
		}
	}

	/**
	 * Update shipping contact details
	 *
	 * @return array
	 */
	protected function handle_update_shipping_contact_applepay() {
		try {
			if ( ! $this->check_nonce() ) {
				return;
			}

			// Set context as cart.
			if ( is_callable( 'wc_maybe_define_constant' ) ) {
				wc_maybe_define_constant( 'WOOCOMMERCE_CART', true );
			}

			$response = array();
			$address = isset( $_REQUEST['simplified_contact'] ) ? wp_unslash( $_REQUEST['simplified_contact'] ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( $address ) {
				$this->update_addresses( $address, 'applepay' );
			}

			WC()->cart->calculate_shipping();
			WC()->cart->calculate_fees();
			WC()->cart->calculate_totals();

			$total = (float) WC()->cart->get_total( 'numeric' );

			$calculated_packages = WC()->shipping->calculate_shipping(
				WC()->cart->get_shipping_packages()
			);

			foreach ( $calculated_packages[0]['rates'] as $rate ) {
				$cost = (float) $rate->get_cost() + WC()->cart->get_shipping_tax();
				/**
				 * The shipping rate.
				 *
				 * @var \WC_Shipping_Rate $rate
				 */
				$shipping_options[] = array(
					'identifier' => $rate->get_id(),
					'label'      => $rate->get_label(),
					'amount'     => $cost,
					'detail'     => '',
				);
			}

			usort($shipping_options, function($a, $b) {
				if ($a['identifier'] === WC()->session->get('chosen_shipping_methods')[0]) {
					return -1;
				} else {
					return 1;
				}
				return 0;
			});

			$response = array(
				'newShippingMethods' => $shipping_options,
				'newLineItems'       => array(),
				'newTotal'           => array(
					'label'  => __( 'Total', 'yith-paypal-payments-for-woocommerce' ),
					'type'   => 'final',
					'amount' => $total,
				),
			);

			wp_send_json_success( $response );
			return true;
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
			return false;
		}
	}
	/**
	 * Update shipping method details
	 *
	 * @return array
	 */
	public function handle_update_shipping_method_applepay() {
		try {
			if ( ! $this->check_nonce() ) {
				return;
			}

			// Set context as cart.
			if ( is_callable( 'wc_maybe_define_constant' ) ) {
				wc_maybe_define_constant( 'WOOCOMMERCE_CART', true );
			}

			$response = array();
			$shipping = isset( $_REQUEST['shipping_method'] ) ? wp_unslash( $_REQUEST['shipping_method'] ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( $shipping ) { 
				$this->update_shipping_method( $shipping, 'applepay' ); 
			}

			WC()->cart->calculate_shipping();
			WC()->cart->calculate_fees();
			WC()->cart->calculate_totals();

			$total = (float) WC()->cart->get_total( 'numeric' );

			$calculated_packages = WC()->shipping->calculate_shipping(
				WC()->cart->get_shipping_packages()
			);

			foreach ( $calculated_packages[0]['rates'] as $rate ) {
				$cost = (float) $rate->get_cost() + WC()->cart->get_shipping_tax();
				/**
				 * The shipping rate.
				 *
				 * @var \WC_Shipping_Rate $rate
				 */
				$shipping_options[] = array(
					'identifier' => $rate->get_id(),
					'label'      => $rate->get_label(),
					'amount'     => $cost,
					'detail'     => '',
				);
			}

			$response = array(
				'newShippingMethods' => $shipping_options,
				'newLineItems'       => array(),
				'errors' => array(),
				'newTotal'           => array(
					'label'  => __( 'Total', 'yith-paypal-payments-for-woocommerce' ),
					'type'   => 'final',
					'amount' => $total,
				),
			);

			wp_send_json_success( $response );
			return true;

		} catch ( Exception $e ) {
			$this->handle_ajax_request_failure( array(), $e->getMessage(), true );
			return false;
		}
	}

	/**
	 * Handle maybe clean session
	 *
	 * @return bool
	 */
	public function handle_maybe_clean_session() {
		if ( ! $this->check_nonce() ) {
			return;
		}

		$flow = isset( $_REQUEST['flow'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['flow'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
		if ( in_array( $flow, array( 'googlepay', 'applepay'), true )) {
			unset( WC()->yith_ppwc_funding_source );
			$customer_backup = WC()->session->get( 'yith_ppwc_customer_backup' );
			if ( ! empty( $customer_backup ) ) {
				WC()->session->set( 'customer', $customer_backup );
				unset( WC()->session->yith_ppwc_customer_backup );
			}
			$old_cart = WC()->session->get( 'old_cart' );
			if ( ! empty( $old_cart ) ) {
				//WC()->session->set( 'cart', $old_cart );
			} else {
				//WC()->cart->empty_cart( true );
			}
		}

		wp_send_json_success();
		return true;
	}

	/**
	 * Validate Merchant for Apple Pay
	 */
	public function handle_validate_merchant() {
		if ( ! $this->check_nonce() ) {
			return;
		}

		if ( isset( $_REQUEST['validation'] ) && filter_var( $_REQUEST['validation'], FILTER_VALIDATE_BOOLEAN ) === true ) {
			YITH_PayPal_Apple_Pay::validate_merchant( true );
		} else {
			YITH_PayPal_Apple_Pay::validate_merchant( false );
		}
	}

	/**
	 * Check nonce for ajax calls
	 *
	 * @return bool
	 */
	protected function check_nonce() {
		$nonce = filter_input( INPUT_POST, 'security', FILTER_SANITIZE_SPECIAL_CHARS );
		if ( ! $nonce ) {
			return false;
		}

		return wp_verify_nonce(
			$nonce,
			'yith_ppwc_ajax_request'
		) === 1;
	}

	/**
	 * Format the response
	 *
	 * @param mixed  $data The response data.
	 * @param string $result Result.
	 * @return array
	 */
	protected function format_response_data( $data, $result = 'success' ) {
		$data = array_filter( (array) $data );

		return array_merge( array( 'result' => $result ), $data );
	}

	/**
	 * Log request error
	 *
	 * @since 1.0.0
	 * @param string $message Message text.
	 */
	protected function log_error( $message ) {
		if ( $message ) {
			YITH_PayPal_Logger::log( $message );
		}
	}

	/**
	 * Change the total from float to string for google pay
	 *
	 * @since 3.0.0
	 * @param string $total Message text.
	 * @param string $currency currency slug.
	 * @return string
	 */
	protected function total_to_string( $total, $currency ) {
		$currencies_without_decimals = array( 'HUF', 'JPY', 'TWD' );
		return in_array( $currency, $currencies_without_decimals, true ) ? (string) round( $total, 0 ) : number_format( $total, 2, '.', '' );
	}
}

new YITH_PayPal_Ajax();
