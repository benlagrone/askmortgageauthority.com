<?php
/**
 * Gateway class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH PayPal Payments for WooCommerce
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class YITH_PayPal_Gateway
 */
class YITH_PayPal_Gateway extends WC_Payment_Gateway_CC {

	/**
	 * The partner ID
	 *
	 * @var string
	 */
	const PARTNER_ID = '4W7PT248NATQQ';
	const PARTNER_ID_LIVE = 'FVENL8FXSNQ8U';

	/**
	 * The partner Client ID
	 *
	 * @var string
	 */
	const PARTNER_CLIENT_ID = 'AQzsRhvy0N0zjOuxvJaWVleXCsqOujcbEP5aJE_wp46yO_oqE2oUcs-GmKiUBtdXoQu-jJYdOOLWUiUR';
	const PARTNER_CLIENT_ID_LIVE = 'AVVVyplPWwBMpvvRtiTf4qi0QuocVBTeaEYSE8gDgqACLFfj3kZ-Zo5W1gbM1jXLQ3hAeBw16gMm3Vzl';


	/**
	 * Gateway environment
	 *
	 * @var string
	 */
	protected $environment = '';

	/**
	 * Constructor for the gateway.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Setup general properties.
		$this->setup_properties();
		// Load the settings.
		$this->init_settings();

		$this->supports = array(
			'products',
			'refunds',
		);

		// Get settings.
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->environment = $this->get_option( 'environment', 'production' );

		if ( is_admin() ) {
			$get_args = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
			if ( isset( $get_args['page'] ) && 'wc-orders' === $get_args['page'] && isset( $get_args['id'] ) ) {
				$order_id = sanitize_key( $get_args['id'] );
				$order    = wc_get_order( $order_id );
				if ( $order instanceof WC_Order ) {
					$order_funding = $order->get_meta( '_yith_ppwc_funding_source' );
					if ( YITH_PayPal_Google_Pay::get_funding_source() === $order_funding ) {
						$this->title = YITH_PayPal_Google_Pay::get_funding_source_label();
					}
					if ( YITH_PayPal_Apple_Pay::get_funding_source() === $order_funding ) {
						$this->title = YITH_PayPal_Apple_Pay::get_funding_source_label();
					}
				}
			}
		}

		if ( 'capture' !== $this->get_intent() ) {
			// for authorized payment.
			add_action( 'woocommerce_order_status_payment-auth_to_processing', array(
				$this,
				'request_authorized_payment'
			) );
			add_action( 'woocommerce_order_status_payment-auth_to_completed', array(
				$this,
				'request_authorized_payment'
			) );
			add_action( 'woocommerce_order_status_cancelled', array( $this, 'maybe_void_authorized_payment' ) );
		}

		add_filter( 'woocommerce_gateway_title', array( $this, 'filter_gateway_title' ), 10, 2 );
	}

	/**
	 * Setup general properties for the gateway.
	 *
	 * @since 1.0.0
	 */
	protected function setup_properties() {
		$this->id                 = YITH_Paypal::GATEWAY_ID;
		$this->icon               = '';
		$this->method_title       = __( 'PayPal Payments', 'yith-paypal-payments-for-woocommerce' );
		$this->method_description = __( 'Allow customers to pay with their paypal account, no credit card information needed.', 'yith-paypal-payments-for-woocommerce' );
		$this->has_fields         = false;
	}

	/**
	 * Get option key for this gateway.
	 *
	 * @since 1.0.0
	 */
	public function get_option_key() {
		return 'yith_ppwc_gateway_options';
	}

	/**
	 * Init settings for gateways.
	 *
	 * @since 1.0.0
	 */
	public function init_settings() {
		parent::init_settings();
		$this->enabled = ( ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'] ) || empty( $this->settings ) ? 'yes' : 'no';
	}

	/**
	 * Show payment fields on checkout page
	 *
	 * @since 1.0.0
	 */
	public function payment_fields() {
		$description = $this->get_description();

		if ( $description ) {
			echo wpautop( wptexturize( $description ) ); // @codingStandardsIgnoreLine.
		}

		if ( $this->supports( 'tokenization' ) && is_checkout() ) {
			$this->tokenization_script();
			$this->saved_payment_methods();
			$this->form();
			$this->save_payment_method_checkbox();
		} else {
			$this->form();
		}
	}

	/**
	 * Init settings for gateways.
	 *
	 * @return boolean
	 * @since 1.0.0
	 */
	public function is_enabled() {
		return 'yes' === $this->enabled;
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * @return bool
	 */
	public function is_available() {
		$is_available = $this->is_enabled();
		$merchant     = YITH_PayPal_Merchant::get_merchant();

		if ( ! $merchant->is_valid() ) {
			$is_available = false;
		}

		return $is_available;
	}

	/**
	 * Check if fast checkout is enabled
	 *
	 * @return boolean
	 * @since 1.0.0
	 */
	public function is_fast_checkout_enabled() {
		return 'yes' === $this->get_option( 'fast_checkout', 'no' );
	}


	/**
	 * Get the gateway environment
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function get_environment() {
		return $this->environment;
	}

	/**
	 * Get the gateway partner ID
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function get_partner_id() {
		return 'production' === $this->environment ? self::PARTNER_ID_LIVE : self::PARTNER_ID;
	}

	/**
	 * Get the gateway partner client ID
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function get_partner_client_id() {
		return 'production' === $this->environment ? self::PARTNER_CLIENT_ID_LIVE : self::PARTNER_CLIENT_ID;
	}


	/**
	 * Get the BN code to trace orders
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function get_bn_code() {
		return 'Yith_PCP';
	}

	/**
	 * Get the intent
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function get_intent() {
		return 'capture' === $this->get_option( 'intent', 'capture' ) ? 'capture' : 'authorize';
	}

	/**
	 * Get the gateway base url
	 *
	 * @param string $environment Force the environment.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function get_base_url( $environment = '' ) {
		$environment = ! empty( $environment ) ? $environment : $this->get_environment();

		return 'sandbox' === $environment ? 'https://www.sandbox.paypal.com' : 'https://www.paypal.com';
	}

	/**
	 * Get the API gateway url
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function get_api_url() {
		return 'production' === $this->get_environment() ? 'https://api.paypal.com' : 'https://api-m.sandbox.paypal.com';
	}

	/**
	 * Get the invoice id prefix
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function get_prefix() {
		return $this->get_option( 'prefix' );
	}

	/**
	 * Get the SDK url
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function get_sdk_url() {

		// Add needed data like merchant ID and options style.
		$merchant = YITH_PayPal_Merchant::get_merchant();

		$cc_enabled = yith_ppwc_is_custom_credit_card_enabled();
		$components = apply_filters( 'yith_ppwc_sdk_components', array( 'hosted-fields', 'buttons', 'card-fields' ) );
		$args       = array(
			'components'  => ( is_checkout() && $cc_enabled ) ? implode( ',', $components ) : 'buttons',
			'client-id'   => $merchant->get_client_id(),
			'merchant-id' => $merchant->get( 'merchant_id' ),
			'currency'    => get_woocommerce_currency(),
			'intent'      => $this->get_intent(),
			'commit'      => is_checkout() ? 'true' : 'false',
			'locale'      => get_locale(), // to get PayPal button text based on buyer locale instead of site locale.
			'debug'       => 'production' !== $this->environment ? 'true' : 'false',
		);

		if ( 'production' !== $this->environment ) {
			$test_buyer_country = 'US';

			if ( is_user_logged_in() && WC()->customer ) {
				$customer_country   = WC()->customer->get_billing_country();
				$test_buyer_country = $customer_country ? $customer_country : $test_buyer_country;
			}

			$args['buyer-country'] = $test_buyer_country;
		}

		$enabled_funding_sources = yith_ppwc_get_enabled_funding( false );

		if ( $enabled_funding_sources ) {
			if ( $cc_enabled && is_checkout() && ! in_array( 'card', $enabled_funding_sources, true ) ) {
				$enabled_funding_sources[] = 'card';
			}
			$args['enable-funding'] = implode( ',', $enabled_funding_sources );
		}

		/**
		 * APPLY_FILTERS:yith_ppwc_sdk_parameters
		 *
		 * This filter is used for adding extra arguments for the SDK parameters
		 *
		 * @return array
		 */
		return add_query_arg( apply_filters( 'yith_ppwc_sdk_parameters', $args ), 'https://www.paypal.com/sdk/js' );
	}


	/**
	 * Get the login url
	 *
	 * @param string $environment Force the environment.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function get_login_url( $environment = '' ) {
		$environment = ! empty( $environment ) ? $environment : $this->get_environment();

		$url = $this->get_login_url_from_cache( $environment );
		if ( ! $url ) {
			$endpoint = "https://atuzppv5znglbafpzsytcmrkhy0hbqkh.lambda-url.us-east-1.on.aws/";

			$args     = array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( array(
					'returnURL'   => esc_url( admin_url( 'admin.php?page='
					                                     . YITH_PayPal_Admin::get_redirect_login_page() ) ),
					'sellerNonce' => yith_ppwc_get_nonce(),
					'environment' => $environment,
					'clientId'    => $this->get_partner_client_id(),
					'products'    => YITH_PayPal::get_instance()->get_product_type(),
				) )
			);
			$response = wp_safe_remote_request( $endpoint, $args );

			if ( isset( $response['response']['code'] ) && 200 !== $response['response']['code'] ) {
				YITH_PayPal_Logger::log( 'Error: Unable to process the PayPal API Request.' . print_r( $response, true ) ); // phpcs:ignore

				return '';
			} else {
				$body = json_decode( $response['body'], true );
				$url  = $body['login_url'];
				$this->save_login_url_to_cache( $environment, $url );
			}

		}

		return $url ? $url . '&displayMode=minibrowser' : '';

	}


	/**
	 * Get the login url from cache
	 *
	 * @param string $environment The environment
	 *
	 * @return false|string
	 */
	public function get_login_url_from_cache( $environment ) {
		$product        = strtolower( YITH_PayPal::get_instance()->get_product_type() );
		$user_id        = get_current_user_id();
		$transient_name = 'yith_ppwc_login_url_' . $environment . '_' . $product . '_' . $user_id;
		$value          = get_transient( $transient_name );

		if ( ! is_array( $value ) ) {
			return false;
		}

		if (
			! ( $value['user_id'] ?? false )
			|| ! ( $value['url'] ?? false )
			|| ! ( $value['hash_check'] ?? false )
			|| ! ( $value['go_back_page'] ?? false )
		) {
			return false;
		}

		if ( $value['user_id'] !== $user_id ) {
			return false;
		}

		if ( $value['hash_check'] !== wp_hash( '' ) ) {
			return false;
		}

		if ( $value['go_back_page'] !== YITH_PayPal_Admin::get_redirect_login_page() ) {
			return false;
		}

		return $value['url'];
	}

	/**
	 * Store the url in cache
	 *
	 * @param string $environment The environment.
	 * @param string $url The url.
	 *
	 * @return void
	 */
	public function save_login_url_to_cache( $environment, $url ) {
		$product        = strtolower( YITH_PayPal::get_instance()->get_product_type() );
		$user_id        = get_current_user_id();
		$transient_name = 'yith_ppwc_login_url_' . $environment . '_' . $product . '_' . $user_id;
		$value          = array(
			'url'          => $url,
			'user_id'      => $user_id,
			'hash_check'   => wp_hash( '' ),
			'go_back_page' => YITH_PayPal_Admin::get_redirect_login_page(),
		);
		set_transient( $transient_name, $value, MONTH_IN_SECONDS );
	}

	/**
	 * Add the button on checkout page
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function form() {
	}

	/**
	 * Pay the order.
	 *
	 * @param int $order_id Order id.
	 *
	 * @return array
	 * @throws Exception .
	 */
	public function process_payment( $order_id ) {

		if ( ! isset( WC()->session ) ) {
			throw new Exception( 'Error: An error occurred during the payment.' );
		}

		if ( ! WC()->session->get( 'paypal_order_id', false ) ) {
			throw new Exception( 'Error: Missing paypal order id.' );
		}

		$order           = wc_get_order( $order_id );
		$paypal_order_id = WC()->session->get( 'paypal_order_id' );

		$order->update_meta_data( '_paypal_order', $paypal_order_id );

		if ( $order->get_total() > 0 ) {
			$transaction = YITH_PayPal_Controller::load( 'transaction' );
			try {
				if ( $transaction->update_order( $order_id, $paypal_order_id ) ) {
					if ( 'authorize' === $this->get_intent() ) {
						$this->authorize_order( $order, $paypal_order_id );
					} else {
						$payment_info = $transaction->pay_order( $paypal_order_id );
						if ( 'COMPLETED' === $payment_info['status'] ) {
							$transaction_id = $payment_info['transaction_id'];

							isset( $payment_info['yith_ppwc_paypal_address'] ) && $order->update_meta_data( '_yith_ppwc_paypal_address', $payment_info['yith_ppwc_paypal_address'] );
							isset( $payment_info['yith_ppwc_payer_id'] ) && $order->update_meta_data( '_yith_ppwc_payer_id', $payment_info['yith_ppwc_payer_id'] );
							isset( $payment_info['yith_ppwc_invoice_id'] ) && $order->update_meta_data( '_yith_ppwc_invoice_id', $payment_info['yith_ppwc_invoice_id'] );
							$order->payment_complete( $transaction_id );
						}
					}
				}
			} catch ( Exception $e ) {

				wc_add_notice( $e->getMessage(), 'error' );
				$redirect = WC()->session->get( 'checkout_as_confirm_page' ) ? wc_get_cart_url() : wc_get_checkout_url();

				// Return to checkout redirect.
				return array(
					'result'   => 'success',
					'redirect' => $redirect,
				);
			}
		} else {
			$order->payment_complete();
		}

		/* this is for Google Pay and Apple Pay */
		$funding_source = WC()->session->get( 'yith_ppwc_funding_source' );
		if ( ! empty( $funding_source ) ) {
			$order->add_meta_data( '_yith_ppwc_funding_source', $funding_source );
		}

		/* change only the payment title to show in checkout for GooglePay and ApplePay */
		if ( YITH_PayPal_Google_Pay::get_funding_source() === $funding_source ) {
			$order->set_payment_method_title( YITH_PayPal_Google_Pay::get_funding_source_label() );
		}
		if ( YITH_PayPal_Apple_Pay::get_funding_source() === $funding_source ) {
			$order->set_payment_method_title( YITH_PayPal_Apple_Pay::get_funding_source_label() );
		}

		$order->save();
		// Remove cart.
		WC()->cart->empty_cart();

		// Return thankyou redirect.
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Process the refund.
	 *
	 * @param WC_Order $order Order.
	 * @param string   $paypal_order_id PayPal order id.
	 *
	 * @throws Exception Throws Exception.
	 */
	protected function authorize_order( $order, $paypal_order_id ) {

		$transaction = YITH_PayPal_Controller::load( 'transaction' );

		$order_details = $transaction->maybe_request_authorize_code( $paypal_order_id );

		if ( empty( $order_details['purchase_units'][0]['payments']['authorizations'] ) ) {
			throw new Exception( 'No authorizations have been created for this order.' );
		}

		$authorize_info = $order_details['purchase_units'][0]['payments']['authorizations'][0];

		$order_note = esc_html_x( 'PayPal Payment authorized. Change the order status to Processing or Completed to capture funds or take a partial payment.', 'Order note message', 'yith-paypal-payments-for-woocommerce' );

		if ( ! empty( $authorize_info['expiration_time'] ) ) {
			$date = new WC_DateTime( $authorize_info['expiration_time'] );
			// translators: the placeholder is a date.
			$order_note .= sprintf( esc_html_x( ' Expiration date %s.', 'Order note, the placeholder is the expiration date of the authorized payment', 'yith-paypal-payments-for-woocommerce' ), $date->date_i18n( wc_date_format() ) );
		}

		$order->has_status( 'payment-auth' ) ? $order->add_order_note( $order_note ) : $order->update_status( 'payment-auth', $order_note );

		$order->update_meta_data( '_captured', 'no' );
		$order->update_meta_data( '_yith_ppwc_paypal_authorize_info', $authorize_info );
		$order->update_meta_data( '_yith_ppwc_paypal_authorize_info_', maybe_serialize( $authorize_info ) );

		! empty( $order_details['payer']['email_address'] ) && $order->update_meta_data( '_yith_ppwc_paypal_address', $order_details['payer']['email_address'] );
		! empty( $order_details['payer']['payer_id'] ) && $order->update_meta_data( '_yith_ppwc_payer_id', $order_details['payer']['payer_id'] );
		! empty( $order_details['purchase_units'][0]['invoice_id'] ) && $order->update_meta_data( '_yith_ppwc_invoice_id', $order_details['purchase_units'][0]['invoice_id'] );
	}

	/**
	 * Charge an authorized payment.
	 *
	 * @param int $order_id Order id.
	 *
	 * @return void|WP_Error
	 * @throws Exception Exception message.
	 */
	public function request_authorized_payment( $order_id ) {
		$order          = wc_get_order( $order_id );
		$captured       = $order->get_meta( '_captured' );
		$payment_method = $order->get_payment_method();
		$invoice_id     = $order->get_meta( '_yith_ppwc_invoice_id' );
		$invoice_id     = empty( $invoice_id ) ? $this->get_prefix() . $order_id : $invoice_id;

		if ( ! $captured || 'no' !== $captured || ! yith_ppwc_check_gateway( $payment_method ) ) {
			return;
		}

		// If there are partials, continue with that.
		$partials = YITH_PayPal_Order_Helper::get_partials( $order_id );
		if ( ! empty( $partials ) ) {
			YITH_PayPal_Order_Helper::create_partial_payment(
				array(
					'amount'   => YITH_PayPal_Order_Helper::get_remaining_order_amount( $order ),
					'order_id' => $order_id,
				)
			);

			return;
		}

		try {

			$auth_info = $order->get_meta( '_yith_ppwc_paypal_authorize_info' );

			if ( empty( $auth_info ) ) {
				throw new Exception( esc_html_x( 'Authorize information is missing.', 'Error message', 'yith-paypal-payments-for-woocommerce' ) );
			}

			$transaction    = YITH_PayPal_Controller::load( 'transaction' );
			$authorize_id   = $auth_info['id'];
			$transaction_id = $transaction->capture_authorization( $authorize_id, $invoice_id );

			if ( $order->payment_complete( $transaction_id ) ) {
				// translators: the placeholder is the transaction id.
				$order->add_order_note( sprintf( esc_html_x( 'PayPal payment approved (ID: %s)', 'Order note. Placeholder id of transaction', 'yith-paypal-payments-for-woocommerce' ), $transaction_id ) );
				$order->update_meta_data( '_captured', 'final' );
				$order->update_meta_data( '_transaction_id', $transaction_id );
				$order->save_meta_data();
			} else {
				throw new Exception( __( 'Something went wrong with the payment', 'yith-paypal-payments-for-woocommerce' ) );
			}
		} catch ( Exception $e ) {
			// translators: the placeholder is an error message.
			$message = sprintf( esc_html_x( 'There was an error while processing the payment: %s.', 'Error message. Placeholder is the error detail', 'yith-paypal-payments-for-woocommerce' ), esc_html( $e->get_message() ) );

			$order->add_order_note( $message );

			return new WP_Error( 'yith_ppwc_error', $message );
		}
	}

	/**
	 * Charge an authorized partial payment.
	 *
	 * @param int $partial_id Order id.
	 *
	 * @return void|WP_Error
	 * @throws Exception Exception message.
	 */
	public function request_authorized_partial_payment( $partial_id ) {

		$partial = wc_get_order( $partial_id );
		if ( ! $partial || 'shop_order_partial' !== $partial->get_type() ) {
			return;
		}

		$order          = wc_get_order( $partial->get_parent_id() );
		$payment_method = $order ? $order->get_payment_method() : '';

		try {
			if ( 'no' !== $partial->get_meta( '_captured' ) || ! yith_ppwc_check_gateway( $payment_method ) ) {
				throw new Exception( esc_html_x( 'Invalid request.', 'Error message', 'yith-paypal-payments-for-woocommerce' ) );
			}

			$auth_info = $order ? $order->get_meta( '_yith_ppwc_paypal_authorize_info' ) : false;

			if ( empty( $auth_info ) || empty( $auth_info['id'] ) ) {
				throw new Exception( esc_html_x( 'Authorize information is missing.', 'Error message', 'yith-paypal-payments-for-woocommerce' ) );
			}

			$invoice_id = $partial->get_meta( '_yith_ppwc_invoice_id' );
			$invoice_id = empty( $invoice_id ) ? $this->get_prefix() . $order->get_id() : $invoice_id;

			$final_payment = abs( YITH_PayPal_Order_Helper::get_remaining_order_amount( $order ) ) < 0.01;

			$transaction    = YITH_PayPal_Controller::load( 'transaction' );
			$transaction_id = $transaction->capture_authorization(
				$auth_info['id'],
				$invoice_id,
				yith_ppwc_format_amount( $partial->get_amount(), $partial->get_currency() ),
				$final_payment
			);

			if ( $transaction_id ) {
				$this->save_transaction_id_on_partial_payment( $transaction_id, $order, $partial, $final_payment );
			} else {
				throw new Exception( __( 'Something went wrong with the payment', 'yith-paypal-payments-for-woocommerce' ) );
			}
		} catch ( Exception $e ) {
			// translators: the placeholder is an error message.
			$message = sprintf( esc_html_x( 'There was an error while processing the partial payment: %s.', 'Error message. Placeholder is the error detail', 'yith-paypal-payments-for-woocommerce' ), esc_html( $e->get_message() ) );

			$order->add_order_note( $message );

			return new WP_Error( 'yith_ppwc_error', $message );
		}
	}


	/**
	 * Register partial payment inside the order.
	 *
	 * @param string                    $transaction_id Transaction id.
	 * @param WC_Order                  $order Order.
	 * @param YITH_PayPal_Order_Partial $partial Partial order.
	 * @param bool                      $final_payment If true the payment is completed.
	 */
	public function save_transaction_id_on_partial_payment( $transaction_id, $order, $partial, $final_payment ) {
		// translators: the placeholder is the transaction id.
		$order->add_order_note( sprintf( esc_html_x( 'PayPal partial payment approved (ID: %s)', 'Order note. Placeholder id of transaction', 'yith-paypal-payments-for-woocommerce' ), $transaction_id ) );

		$partial->update_meta_data( '_captured', 'true' );
		$partial->set_transaction_id( $transaction_id );
		if ( ! $partial->get_date_paid( 'edit' ) ) {
			$partial->set_date_paid( time() );
		}

		$partial->save();

		if ( $final_payment ) {
			$order->add_order_note( sprintf( esc_html_x( 'Order PayPal partial payment finalized. No more partial payments allowed.', 'Order note.', 'yith-paypal-payments-for-woocommerce' ) ) );
			$order->update_meta_data( '_captured', 'final' );
			$order->payment_complete();
		}
	}


	/**
	 * Check if the order has an authorized payment and do the void
	 *
	 * @param int    $order_id Order id.
	 * @param string $auth_code The auth code.
	 *
	 * @throws Exception Throws Exception.
	 */
	public function maybe_void_authorized_payment( $order_id, $auth_code = '' ) {

		$order          = wc_get_order( $order_id );
		$captured       = $order->get_meta( '_captured' );
		$payment_method = $order->get_payment_method();
		$invoice_id     = $order->get_meta( '_yith_ppwc_invoice_id' );
		$invoice_id     = empty( $invoice_id ) ? $this->get_prefix() . $order_id : $invoice_id;

		if ( ! $captured || 'final' === $captured || ! yith_ppwc_check_gateway( $payment_method ) ) {
			return;
		}

		try {

			$auth_info = $order->get_meta( '_yith_ppwc_paypal_authorize_info' );

			if ( empty( $auth_info ) ) {
				throw new Exception( esc_html_x( 'Authorize information is missing.', 'Error message', 'yith-paypal-payments-for-woocommerce' ) );
			}

			$transaction  = YITH_PayPal_Controller::load( 'transaction' );
			$authorize_id = $auth_info['id'];
			if ( ! empty( $auth_code ) && $authorize_id !== $auth_code ) {
				$order->add_order_note( esc_html_x( 'A Payment authorization void was requested for this order from PayPal, but the authorization code does not match the registered code.', 'Order note.', 'yith-paypal-payments-for-woocommerce' ) );

				return;
			}

			empty( $auth_code ) && $transaction->void_authorization( $authorize_id, $invoice_id );

			$who = empty( $auth_code ) ? esc_html_x( 'Administrator', 'A piece of order note, to specify who made the action', 'yith-paypal-payments-for-woocommerce' ) : esc_html_x( 'PayPal', 'A piece of order note, to specify who made the action', 'yith-paypal-payments-for-woocommerce' );
			// translators: The placeholder is a string previously declared.
			$order->add_order_note( sprintf( esc_html_x( 'PayPal payment is voided by %s', 'Order note. Placeholder who requested the void', 'yith-paypal-payments-for-woocommerce' ), $who ) );
			$order->update_meta_data( '_captured', 'voided' );
			$order->save_meta_data();

		} catch ( Exception $e ) {
			// translators: The placeholder is the error returned by PayPal.
			return new WP_Error( 'yith_ppwc_error', sprintf( esc_html_x( 'Sorry, there was an error while processing the payment: %s.', 'Error message. Placeholder is the error detail', 'yith-paypal-payments-for-woocommerce' ), esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Get the transaction URL.
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return string
	 */
	public function get_transaction_url( $order ) {

		$this->view_transaction_url = $this->get_base_url() . '/cgi-bin/webscr?cmd=_view-a-trans&id=%s';

		return parent::get_transaction_url( $order );
	}

	/**
	 * Process the refund.
	 *
	 * @param int        $order_id Order id.
	 * @param null|float $amount Amount.
	 * @param string     $reason Reason of Refund.
	 * @param string     $transaction_id The payment transaction ID. User for partial payments.
	 *
	 * @return bool|WP_Error
	 * @throws Exception Throws Exception.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '', $transaction_id = '' ) {

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_Error( 'yith_ppwc_refund_error', __( 'No valid order to refund.' ) );
		}

		if ( empty( $transaction_id ) ) {
			$transaction_id = $order->get_transaction_id();
		}

		if ( ! $transaction_id ) {
			return new WP_Error( 'yith_ppwc_refund_error', __( 'No transaction ID registered for this order.', 'yith-paypal-payments-for-woocommerce' ) );
		}

		$order_currency      = $order->get_currency();
		$payer_id            = $order->get_meta( '_yith_ppwc_payer_id' );
		$invoice_id          = $order->get_meta( '_yith_ppwc_invoice_id' );
		$refund_tracking_ids = (array) $order->get_meta( '_yith_ppwc_refund_tracking_ids' );

		try {
			$transaction = YITH_PayPal_Controller::load( 'transaction' );
			// ask refund to transaction.
			$refund_id = $transaction->refund_order( $amount, $order_currency, $reason, $transaction_id, $payer_id, $invoice_id );
			array_push( $refund_tracking_ids, $refund_id );
			$order->update_meta_data( '_yith_ppwc_refund_tracking_ids', $refund_tracking_ids );
			$order->save_meta_data();

		} catch ( Exception $e ) {
			return new WP_Error( 'yith_ppwc_refund_error', $e->getMessage() );
		}

		// translators: Placeholder is the refund id.
		$note = is_null( $amount ) ? sprintf( esc_html_x( 'Fully Refund ID: %2$s', 'Order note message', 'yith-paypal-payments-for-woocommerce' ), $amount, $refund_id ) : sprintf( esc_html_x( 'Refunded %1$s - Refund ID: %2$s', 'Order note message', 'yith-paypal-payments-for-woocommerce' ),
			wc_price( $amount, $order_currency ),
			$refund_id
		);
		$note .= $reason ? esc_html_x( ' - Reason: ', 'Part of an order note message', 'yith-paypal-payments-for-woocommerce' ) . $reason : '';

		$order->add_order_note( $note );

		return true;
	}

	/**
	 * Return the title of the gateway
	 *
	 * @return string
	 */
	public function get_method_title() {
		/**
		 * APPLY_FILTERS: yith_ppwc_method_title_icons
		 *
		 * List the icons to add in payment method title.
		 *
		 * @param array $icons list of the icons.
		 */
		$icons_to_show = apply_filters( 'yith_ppwc_method_title_icons', array(
			'paypal',
			'venmo',
			'giropay',
			'ideal'
		) );

		$cc_images = '<span class="cc-images">';
		foreach ( $icons_to_show as $icon ) {
			$url       = YITH_PAYPAL_PAYMENTS_URL . 'assets/images/cc/' . $icon . '.svg';
			$cc_images .= '<img src="' . $url . '" alt="' . $icon . '"/>';
		}
		$cc_images .= '<span>';

		return '<span class="pp-title">' . __( 'PayPal - ', 'yith-paypal-payments-for-woocommerce' ) . $cc_images . '<span>';
	}

	/**
	 * Filter the payment method title on setting to remove the custom title
	 *
	 * @param string $title Custom title of gateway.
	 * @param string $gateway_id id of the gateway.
	 *
	 * @return string
	 */
	public function filter_gateway_title( $title, $gateway_id ) {
		if ( $this->id === $gateway_id && isset( $_GET['page'], $_GET['tab'] ) && 'wc-settings' === $_GET['page'] && 'checkout' === $_GET['tab'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $this->get_method_title();
		}

		return $title;
	}
}
