<?php
/**
 * Main gateway class of the plugin
 * Implements Payment Element feature from Stripe as a WooCommerce Payment Gateway
 *
 * @package YITH\StripePayments\Classes\Gateways
 * @author  YITH
 * @version 2.0.0
 */

namespace YITH\StripePayments\Gateways;

use YITH\StripePayments\Cache_Helper;
use YITH\StripePayments\Gateways\Abstracts\Gateway;
use YITH\StripePayments\Amount;
use YITH\StripePayments\Account;
use YITH\StripePayments\Models\Customer;
use YITH\StripePayments\Models\Intent;
use YITH\StripePayments\Exceptions\Payment_Exception;
use YITH\StripePayments\Session_Intent;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Gateways\Payment_Element' ) ) {
	/**
	 * Main gateway class of the plugin
	 *
	 * @since 1.0.0
	 */
	class Payment_Element extends Gateway {

		/**
		 * Contains unique slug for the gateway.
		 *
		 * @var string
		 */
		public static $slug = 'element';

		/**
		 * Stores the capturing type.
		 *
		 * @var string
		 */
		private $capture;

		/**
		 * Constructor method
		 */
		public function __construct() {
			parent::__construct();

			$this->has_fields         = true;
			$this->method_title       = apply_filters( 'yith_stripe_payments_element_method_title', __( 'Stripe Element', 'yith-stripe-payments-for-woocommerce' ) );
			$this->method_description = apply_filters( 'yith_stripe_payments_element_method_description', __( 'Stripe provides all the tools that you need to accept payments online around the world, including support for Apple Pay and Google Pay.', 'yith-stripe-payments-for-woocommerce' ) );
			$this->enabled            = apply_filters( 'yith_stripe_payments_element_enabled', $this->enabled );

			$this->title                = $this->get_gateway_title();
			$this->description          = $this->get_option( 'description', _x( 'Use your Credit Card, or choose one of our selected partners to process your payment', '[ADMIN] Default gateway description', 'yith-stripe-payments-for-woocommerce' ) );
			$this->capture              = $this->get_option( 'capture', 'automatic' );
			$this->view_transaction_url = 'https://dashboard.stripe.com/' . ( 'test' === $this->get_env() ? 'test/' : '' ) . 'payments/%s';

			// register plugin scripts.
			add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );

			// invalidate cache upon settings saving.
			add_action( 'update_option_' . $this->get_option_key(), array( Cache_Helper::class, 'invalidate_cache' ) );
		}

		/**
		 * Register scripts needed for proper gateway execution
		 */
		public function register_scripts() {
			static $registered = false;

			if ( $registered ) {
				return;
			}

			$registered = true;
			$suffix     = defined( 'SCRIPT_DEBUG' ) && \SCRIPT_DEBUG ? '' : '.min';

			wp_register_script( 'stripe', 'https://js.stripe.com/v3/', array(), YITH_STRIPE_PAYMENTS_VERSION, true );
			wp_register_script( 'yith-stripe-payments-element', YITH_STRIPE_PAYMENTS_URL . "assets/js/yith-stripe-payments-element.bundle{$suffix}.js", array( 'jquery', 'stripe' ), YITH_STRIPE_PAYMENTS_VERSION, true );
			wp_register_script( 'yith-stripe-payments-element-block', YITH_STRIPE_PAYMENTS_URL . "assets/js/yith-stripe-payments-element-block.bundle{$suffix}.js", array( 'jquery', 'stripe' ), YITH_STRIPE_PAYMENTS_VERSION, true );

			wp_localize_script(
				'stripe',
				'yithStripePaymentsElement',
				array(
					'slug'           => self::$slug,
					'title'          => $this->get_title(),
					'description'    => $this->get_description(),
					'is_checkout'    => is_checkout() || is_checkout_pay_page(),
					'is_add_method'  => is_add_payment_method_page(),
					'account_id'     => Account::get_instance()->get_account_id(),
					'public_key'     => self::get_api()->get_public_key(),
					'currency'       => Amount::get_store_currency(),
					'appearance'     => $this->get_appearance_options(),
					'layout'         => $this->get_layout_options(),
					'tokenization'   => $this->supports( 'tokenization' ),
					'capture_method' => $this->capture,
				)
			);
		}

		/**
		 * Returns an array with appearance options for the embed.
		 *
		 * @return array.
		 */
		public function get_appearance_options() {
			$colors     = (array) $this->get_option( 'colors' );
			$appearance = array(
				'theme'     => $this->get_option( 'theme', 'stripe' ),
				'variables' => array(
					'colorPrimary'    => $colors['primary'] ?? '#0570de',
					'colorBackground' => $colors['background'] ?? '#ffffff',
					'colorText'       => $colors['text'] ?? '#30313d',
				),
			);

			return apply_filters( 'yith_stripe_payments_element_appearance_options', $appearance );
		}

		/**
		 * Returns an array with layout options for the embed.
		 *
		 * @return array.
		 */
		public function get_layout_options() {
			$layout = array(
				'type'                 => $this->get_option( 'layout', 'tabs' ),
				'defaultCollapsed'     => wc_string_to_bool( $this->get_option( 'accordion_collapsed', 'no' ) ),
				'radios'               => wc_string_to_bool( $this->get_option( 'accordion_radios', 'no' ) ),
				'spacedAccordionItems' => wc_string_to_bool( $this->get_option( 'accordion_spaced', 'no' ) ),
			);

			return apply_filters( 'yith_stripe_payments_element_layout_options', $layout );
		}

		/**
		 * Get gateway title
		 * In admin "WC Settings > Payments" panel it will also show the payment methods icons
		 *
		 * @return string
		 */
		public function get_gateway_title() {
			$title = $this->get_option( 'title', _x( 'Stripe Payments', '[ADMIN] Default gateway title', 'yith-stripe-payments-for-woocommerce' ) );

			if ( 'wc-settings' === ( $_GET['page'] ?? '' ) && 'checkout' === ( $_GET['tab'] ?? '' ) ) {
				$title .= '&nbsp;&nbsp;' . $this->get_payment_method_icons_html();
			}

			return $title;
		}

		/**
		 * Return the HTML of the payment method icons
		 *
		 * @return string
		 */
		public function get_payment_method_icons_html() {
			$html            = '';
			$payment_methods = array(
				'visa'       => 'Visa',
				'mastercard' => 'MasterCard',
				'amex'       => 'American Express',
				'applepay'   => 'Apple Pay',
				'googlepay'  => 'Google Pay',
			);

			foreach ( $payment_methods as $payment_method => $title ) {
				$html .= '<img src="' . YITH_STRIPE_PAYMENTS_URL . '/assets/images/payment-methods/' . $payment_method . '.svg" title="' . $title . ' icon">';
			}

			return $html;
		}

		/**
		 * Get gateway option
		 *
		 * @param string $key         The option Key
		 * @param mixed  $empty_value The value to return if the option is empty
		 *
		 * @return false|mixed|string|null
		 */
		public function get_option( $key, $empty_value = null ) {
			if ( 'enabled' !== $key ) {
				return parent::get_option( $key, $empty_value );
			}

			return get_option( 'yith_stripe_payments_enabled', $empty_value );
		}

		/**
		 * update gateway option
		 *
		 * @param string $key   The option key
		 * @param mixed  $value The option value
		 *
		 * @return bool
		 */
		public function update_option( $key, $value = '' ) {
			if ( 'enabled' !== $key ) {
				return parent::update_option( $key, $value );
			}

			return update_option( 'yith_stripe_payments_enabled', $value );
		}

		/**
		 * Builds our payment fields area - including tokenization fields for logged
		 * in users, and the actual payment fields.
		 *
		 * @since 2.6.0
		 */
		public function payment_fields() {
			$description = $this->get_description();
			if ( $description ) {
				echo wpautop( wptexturize( $description ) ); // @codingStandardsIgnoreLine.
			}

			parent::payment_fields();
		}

		/**
		 * Outputs fields for entering credit card information.
		 *
		 * @since 2.6.0
		 */
		public function form() {
			?>
			<div id="yith-stripe-payments-element"></div>
			<?php
			wp_enqueue_script( 'yith-stripe-payments-element' );
		}

		/**
		 * Returns gateway-specific details that needs to be returned to AJAX call that updates checkout.
		 *
		 * @return array Array of gateway-specific details
		 */
		public function get_checkout_details() {
			if ( ! apply_filters( 'yith_stripe_payments_element_prefetch_intent', true, $this ) ) {
				return array();
			}

			return array(
				'secret' => $this->get_session_intent()->get_secret(),
			);
		}

		/**
		 * Returns confirmation url for a given intent object (it will trigger next steps on Stripe.js)
		 *
		 * @param \WC_Order $order  Order object.
		 * @param Intent    $intent Intent object.
		 *
		 * @return string Confirmation url for the given intent.
		 */
		public function get_confirm_url( $order, $intent ) {
			$hash = sprintf(
				'yith-stripe-payments-%1$s/confirm/%2$s/%3$s',
				$this->id,
				$intent->client_secret,
				rawurlencode( esc_url_raw( $this->get_verify_url( $order, $intent ) ) )
			);

			return apply_filters( 'yith_stripe_payments_element_confirm_url', "#$hash", $order, $intent, $this );
		}

		/**
		 * Returns verification url for a given order (it will call API to verify intent status and behave consequently)
		 *
		 * @param \WC_Order $order  Order object.
		 * @param Intent    $intent Intent object.
		 *
		 * @return string Verification ulr for the given intent.
		 */
		public function get_verify_url( $order, $intent ) {
			$base_url = wc_get_checkout_url();
			$url      = add_query_arg(
				array(
					'order_id'              => $order->get_id(),
					'redirect'              => parent::get_return_url( $order ),
					'element_verify_intent' => true,
				),
				$base_url
			);

			return apply_filters( 'yith_stripe_payments_element_confirm_url', $url, $order, $intent, $this );
		}

		/**
		 * Create intent for a specific amount and currency
		 *
		 * @param float  $amount   Amount of the intent.
		 * @param string $currency Currency for the intent.
		 * @param array  $args     Array of additional arguments for intent creation.
		 */
		public function create_intent( $amount, $currency, $args = array() ) {
			$intent_args = yith_stripe_payments_merge_recursive(
				array(
					'capture_method' => $this->capture,
					'metadata'       => array(
						'instance' => self::get_instance(),
					),
				),
				$this->supports( 'tokenization' ) ? array(
					'setup_future_usage' => apply_filters( 'yith_stripe_payments_element_future_usage', 'on_session', $amount, $currency, $this ),
				) : array(),
				$args,
			);

			// update existing intent, or create new one.
			return Intent::get(
				$amount,
				$currency,
				$intent_args
			);
		}

		/**
		 * Process Payment.
		 *
		 * @param int $order_id Order ID.
		 *
		 * @throws Payment_Exception When can't handle default error within payment processing.
		 * @return array
		 *
		 */
		public function process_payment( $order_id ) {
			// disable nonce verification, as nonce was already checked by WooCommerce in \WC_Checkout::process_checkout.
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$order = wc_get_order( $order_id );

			try {
				if ( ! $order ) {
					throw new Payment_Exception( _x( 'Sorry, there was an error while processing payment; please, try again.', 'Payment error', 'yith-stripe-payments-for-woocommerce' ), 'missing_order_to_pay' );
				}

				// retrieves intent, if it exists already.
				$intent = $this->get_session_intent()->get_id();

				// retrieves payment method to use in the payment.
				if ( isset( $_POST["wc-{$this->id}-payment-token"] ) ) {
					$payment_method = sanitize_text_field( wp_unslash( $_POST["wc-{$this->id}-payment-token"] ) );
				} elseif ( isset( $_POST["yith_stripe_payments_{$this->id}_payment_method"] ) ) {
					$payment_method = sanitize_text_field( wp_unslash( $_POST["yith_stripe_payments_{$this->id}_payment_method"] ) );
				} else {
					$payment_method = false;
				}

				// retrieve customer to use in the payment.
				$customer = apply_filters( 'yith_stripe_payments_element_intent_customer', false, $order );

				// process with payment intent creation.
				$intent = $this->pay( $order, compact( 'payment_method', 'customer', 'intent' ) );

				// if intent is still missing payment method, return an error.
				if ( 'requires_payment_method' === $intent->status ) {
					$order->delete_meta_data( $this->get_meta_key( 'method' ) );
					$order->save();

					throw new Payment_Exception( __( 'Couldn\'t complete payment with selected payment method; please, try again by selecting another.', 'yith-stripe-payments-for-woocommerce' ), $intent->last_payment_error['decline_code'], $order );
				}

				// if intent is canceled, it cannot be re-used; remove it from order and give customer another chance.
				if ( 'canceled' === $intent->status ) {
					$this->get_session_intent()->clear();
					$order->delete_meta_data( $this->get_meta_key( 'intent' ) );
					$order->save();

					throw new Payment_Exception( __( 'Payment session expired; please try again by entering a new payment method.', 'yith-stripe-payments-for-woocommerce' ), 'intent_canceled', $order );
				}

				// confirmation requires additional action; return to customer.
				if ( 'requires_action' === $intent->status && ! $this->handle_intent_next_actions( $order, $intent ) ) {
					return array(
						'result'   => 'success',
						'redirect' => $this->get_confirm_url( $order, $intent ),
					);
				}

				// intent is succeeded; adjust order accordingly before sending customer to thank you page.
				if ( in_array( $intent->status, array( 'succeeded', 'processing', 'requires_capture' ), true ) ) {
					$this->after_intent_success( $order, $intent );
				}

				return array(
					'result'   => 'success',
					'redirect' => parent::get_return_url( $order ),
				);
			} catch ( \Exception $e ) {
				$message = $e instanceof Payment_Exception ? $e->getFormatted() : $e->getMessage();

				// translators: 1. actual error message.
				wc_add_notice( sprintf( _x( 'Payment error - %s', 'Payment error', 'yith-stripe-payments-for-woocommerce' ), $message ), 'error' );

				return array(
					'result'   => 'fail',
					'redirect' => '',
				);
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		/**
		 * This method allows callers to force an arbitrary payment for a customer, depending on the configuration array passed
		 *
		 * @param int   $order_id Order id to pay.
		 * @param array $args     Array of arguments for the operation. Formatted as follows:<br>
		 *                        * 'amount'             => 10.00,             // float, amount to pay<br>
		 *                        * 'currency'           => 'EUR'              // currency iso code,<br>
		 *                        * 'customer'           => 'cus_***********', // customer ID on Stripe. When not passed, tries to use order customer (if any)<br>
		 *                        * 'payment_method'     => 'pm_**********',   // payment method ID on Stripe. When not passed, tries to use order method (if any)<br>
		 *                        * 'intent'             => 'pi_***********',  // payment intent to pay. When passed, all other parameters are optional. When missing, searches for it in the order, and eventually updates it. Last option is to create a new one<br>
		 *                        * 'off_session'        => true,              // to set intent as off session on Stripe<br>
		 *                        * 'note'               => ''                 // Additional note to add to order (if any) when payment is done<br>
		 *                        * 'receipt_email'      => ''                 // Email where to send receipt email<br>
		 *                        * 'capture_method'     => ''                 // Capture method (one between automatic|automatic_async|manual)<br>
		 *                        * 'setup_future_usage' => ''                 // Whether to set method for future usage (one between on_session|off_session)<br>
		 *                        * 'metadata'           => []                 // Array of metadata for the intent.
		 *
		 * @throws Payment_Exception | \Exception When an error occurs with params validation or with API calls.
		 * @return Intent
		 */
		public function pay( $order_id, $args = array() ) {
			$args = wp_parse_args(
				$args,
				array(
					'amount'             => 0,
					'currency'           => '',
					'intent'             => '',
					'customer'           => '',
					'payment_method'     => '',
					'off_session'        => false,
					'receipt_email'      => false,
					'capture_method'     => false,
					'setup_future_usage' => false,
					'metadata'           => false,
					'note'               => '',
				)
			);

			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				throw new Payment_Exception( _x( 'Sorry, there was an error while processing payment; please, try again.', 'Payment error', 'yith-stripe-payments-for-woocommerce' ), 'missing_order_to_pay' );
			}

			// extract arguments.
			list( $amount, $currency, $intent, $customer, $payment_method ) = yith_plugin_fw_extract( $args, 'amount', 'currency', 'intent', 'customer', 'payment_method' );

			// set payment details.
			$amount   = $amount ? (float) $amount : (float) $order->get_total( 'edit' );
			$currency = $currency ? $currency : $order->get_currency();

			// validates amount and currency.
			if ( ( $amount && ! Amount::is_valid( $amount ) ) || ! Amount::is_supported_currency( $currency ) ) {
				throw new Payment_Exception( _x( 'Sorry, there was an error while processing payment; please, try again.', 'Payment error', 'yith-stripe-payments-for-woocommerce' ), 'incorrect_payment_amount', $order );
			}

			// retrieve intent to use.
			$intent         = $intent ? $intent : $order->get_meta( $this->get_meta_key( 'intent' ) );
			$payment_method = $payment_method ? $payment_method : $order->get_meta( $this->get_meta_key( 'method' ) );
			$order_user_id  = $order->get_customer_id();
			$customer       = $customer ? $customer : ( $order_user_id ? Customer::get( $order_user_id )->get_customer_id() : false );

			// extract additional parameters.
			list( $off_session, $receipt_email, $capture_method, $setup_future_usage, $metadata ) = yith_plugin_fw_extract( $args, 'off_session', 'receipt_email', 'capture_method', 'setup_future_usage', 'metadata' );

			// generate array of additional intent data.
			$intent_args = yith_stripe_payments_merge_recursive(
				array(
					// translators: 1. Blog name. 2. Order id.
					'description'    => apply_filters( 'yith_stripe_payments_element_intent_description', sprintf( _x( '%1$s - Order #%2$d', '', 'yith-stripe-payments-for-woocommerce' ), get_bloginfo( 'name' ), $order->get_order_number() ), $order ),
					'receipt_email'  => apply_filters( 'yith_stripe_payments_element_intent_receipt_email', $order->get_billing_email(), $order ),
					'capture_method' => $this->capture,
					'metadata'       => apply_filters(
						'yith_stripe_payments_element_intent_metadata',
						array(
							'instance'  => self::get_instance(),
							'order_id'  => $order->get_id(),
							'order_url' => $order->get_edit_order_url(),
						),
						$order
					),
				),
				$this->supports( 'tokenization' ) ? array(
					'setup_future_usage' => apply_filters( 'yith_stripe_payments_element_future_usage', 'on_session', $amount, $currency, $this ),
				) : array(),
				array_filter( compact( 'off_session', 'receipt_email', 'capture_method', 'setup_future_usage', 'metadata' ) )
			);

			// update existing intent, or create new one.
			$_intent = $this->create_intent(
				$amount,
				$currency,
				array_merge(
					compact( 'intent', 'payment_method', 'customer' ),
					$intent_args
				)
			);

			if ( is_wp_error( $_intent ) ) {
				// translators: 1. Error message from remote server.
				throw new Payment_Exception( _x( 'Sorry, there was an error while processing payment; please, try again.', 'Payment error', 'yith-stripe-payments-for-woocommerce' ), $_intent->get_error_code(), $order );
			}

			// stores intent data in the order.
			$order->update_meta_data( $this->get_meta_key( 'intent' ), $_intent->id );
			$order->update_meta_data( $this->get_meta_key( 'method' ), isset( $_intent->payment_method ) ? $_intent->payment_method : false );
			$order->update_meta_data( $this->get_meta_key( 'customer' ), isset( $_intent->customer ) ? $_intent->customer : false );
			$order->save();

			// confirm intent if needed.
			$_intent = $this->maybe_confirm_intent( $order, $_intent );

			if ( is_wp_error( $_intent ) ) {
				// translators: 1. Error message from remote server.
				throw new Payment_Exception( _x( 'Sorry, there was an error while confirming payment; please, try again.', 'Payment error', 'yith-stripe-payments-for-woocommerce' ), $_intent->get_error_code(), $order );
			}

			// add custom order note.
			$note = ! empty( $args['note'] ) ? $args['note'] : false;

			if ( $note ) {
				$order->add_order_note( $note );
			}

			return $_intent;
		}

		/**
		 * This method allows callers to force an arbitrary payment for a customer, depending on the configuration array passed
		 *
		 * @param int   $order_id Order id to pay.
		 * @param array $args     Array of arguments for the operation. Formatted as follows:<br>
		 *                        * 'customer'           => 'cus_***********', // customer ID on Stripe. When not passed, tries to use order customer (if any)<br>
		 *                        * 'payment_method'     => 'pm_**********',   // payment method ID on Stripe. When not passed, tries to use order method (if any)<br>
		 *                        * 'intent'             => 'pi_***********',  // payment intent to pay. When passed, all other parameters are optional. When missing, searches for it in the order, and eventually updates it. Last option is to create a new one<br>
		 *                        * 'off_session'        => true,              // to set intent as off session on Stripe<br>
		 *                        * 'receipt_email'      => ''                 // Email where to send receipt email<br>
		 *                        * 'capture_method'     => ''                 // Capture method (one between automatic|automatic_async|manual)<br>
		 *                        * 'setup_future_usage' => ''                 // Whether to set method for future usage (one between on_session|off_session).
		 *
		 * @throws Payment_Exception | \Exception When an error occurs with params validation or with API calls.
		 * @return Intent|\WP_Error
		 */
		public function confirm( $order_id, $args = array() ) {
			$args = wp_parse_args(
				$args,
				array(
					'intent'             => '',
					'customer'           => '',
					'payment_method'     => '',
					'off_session'        => false,
					'receipt_email'      => false,
					'capture_method'     => false,
					'setup_future_usage' => false,
				)
			);

			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				throw new Payment_Exception( _x( 'Sorry, there was an error while processing payment; please, try again.', 'Payment error', 'yith-stripe-payments-for-woocommerce' ), 'missing_order_to_confirm' );
			}

			// extract arguments.
			list( $intent, $customer, $payment_method ) = yith_plugin_fw_extract( $args, 'intent', 'customer', 'payment_method' );

			// retrieve intent to use.
			$intent         = $intent ? $intent : $order->get_meta( $this->get_meta_key( 'intent' ) );
			$payment_method = $payment_method ? $payment_method : $order->get_meta( $this->get_meta_key( 'method' ) );
			$customer       = $customer ? $customer : $order->get_meta( $this->get_meta_key( 'customer' ) );

			if ( ! $intent ) {
				throw new Payment_Exception( _x( 'Sorry, there was an error while processing payment; please, try again.', 'Payment error', 'yith-stripe-payments-for-woocommerce' ), 'missing_intent_to_confirm', $order );
			}

			$_intent = Intent::get_instance( $intent );

			if ( is_wp_error( $_intent ) ) {
				// translators: 1. Error message from remote server.
				throw new Payment_Exception( _x( 'Sorry, there was an error while processing payment; please, try again.', 'Payment error', 'yith-stripe-payments-for-woocommerce' ), $_intent->get_error_message(), $order );
			}

			// if intent is still missing payment method, return an error.
			if ( 'requires_payment_method' === $_intent->status ) {
				$order->delete_meta_data( $this->get_meta_key( 'method' ) );
				$order->save();

				throw new Payment_Exception( __( 'Couldn\'t complete payment with selected payment method; please, try again by selecting another.', 'yith-stripe-payments-for-woocommerce' ), $_intent->last_payment_error['decline_code'] ?? '', $order );
			}

			// check for error with intent data.
			if (
				( $payment_method && $_intent->payment_method !== $payment_method ) ||
				( $customer && $_intent->customer !== $customer )
			) {
				throw new Payment_Exception( _x( 'Sorry, there was an error while processing payment; please, try again.', 'Payment error', 'yith-stripe-payments-for-woocommerce' ), 'payment_record_mismatch', $order );
			}

			// confirm intent if needed.
			$_intent = $this->maybe_confirm_intent( $order, $_intent );

			if ( 'requires_action' === $_intent->status && ! $this->handle_intent_next_actions( $order, $intent ) ) {
				throw new Payment_Exception( _x( 'Please, validate your payment method before proceeding further; in order to do this, refresh the page and proceed with checkout as usual', 'Payment error', 'yith-stripe-payments-for-woocommerce' ), 'requires_further_actions', $order );
			} elseif ( in_array( $_intent->status, array( 'succeeded', 'processing', 'requires_capture' ), true ) ) {
				$this->after_intent_success( $order, $_intent );
			} else {
				throw new Payment_Exception( _x( 'Sorry, there was an error while processing payment; please, try again.', 'Payment error', 'yith-stripe-payments-for-woocommerce' ), $_intent->last_payment_error['decline_code'] ?? '', $order );
			}

			// Return intent.
			return $_intent;
		}

		/**
		 * Returns object that manages session intent
		 *
		 * @return Session_Intent
		 */
		protected function get_session_intent() {
			return Session_Intent::get_instance( $this->id );
		}

		/**
		 * Confirm an intent when possible
		 *
		 * @param \WC_Order $order  Order whose intent needs to be confirmed.
		 * @param Intent    $intent Intent object to confirm.
		 *
		 * @throws Payment_Exception | \Exception When there is an error with intent confirmation.
		 * @return Intent|\WP_Error Confirmed intent or WP_error object if an error occurred.
		 */
		protected function maybe_confirm_intent( $order, $intent ) {
			if ( ! $intent instanceof Intent ) {
				throw new Payment_Exception( _x( 'Sorry, there was an error while processing payment; please, try again.', 'Payment error', 'yith-stripe-payments-for-woocommerce' ), 'missing_intent_to_confirm', $order );
			}

			$confirm_args = array(
				'return_url'   => $this->get_verify_url( $order, $intent ),
				'mandate_data' => array(
					'customer_acceptance' => array(
						'type'        => 'online',
						'accepted_at' => $order->get_date_created()->getTimestamp(),
						'online'      => array(
							'ip_address' => $order->get_customer_ip_address(),
							'user_agent' => $order->get_customer_user_agent(),
						),
					),
				),
			);

			return $intent->confirm( $confirm_args );
		}

		/**
		 * Performs operation required when intent is returned in requires_action status
		 *
		 * @param \WC_Order $order  Order whose intent needs to be confirmed.
		 * @param Intent    $intent Intent object to confirm.
		 *
		 * @throws Payment_Exception | \Exception When there is an error with intent confirmation.
		 */
		protected function handle_intent_next_actions( $order, $intent ) {
			if ( 'requires_action' !== $intent->status ) {
				throw new Payment_Exception( _x( 'Sorry, there was an error while processing payment; please, try again.', 'Payment error', 'yith-stripe-payments-for-woocommerce' ), 'unexpected_intent_status', $order );
			}

			$next_action  = isset( $intent->next_action ) ? $intent->next_action : array();
			$action_type  = isset( $next_action['type'] ) ? $next_action['type'] : false;
			$order_status = 'on-hold';
			$order_note   = '';

			// supported types of actions.
			switch ( $action_type ) {
				case 'use_stripe_sdk':
				case 'redirect_to_url':
					return false;
				case 'verify_with_microdeposits':
					$micro_deposit_info = isset( $next_action['verify_with_microdeposits'] ) ? $next_action['verify_with_microdeposits'] : array();
					$redirect_url       = isset( $micro_deposit_info['hosted_verification_url'] ) ? esc_url( $micro_deposit_info['hosted_verification_url'] ) : false;
					break;
				default:
					throw new Payment_Exception( _x( 'Please, validate your payment method before proceeding further; in order to do this, refresh the page and proceed with checkout as usual', 'Payment error', 'yith-stripe-payments-for-woocommerce' ), 'requires_further_actions', $order );
			}

			// if next action requires redirect format both message to customer and to admin.
			if ( $redirect_url ) {
				// translators: 1. Url to confirmation page (handled by payment method).
				$order_note       = sprintf( _x( 'Customer needs to manually confirm payment. Send customer to the following <a href="%s">url</a> for confirmation.', 'yith-stripe-payments-for-woocommerce' ), $redirect_url );
				$next_action_meta = array(
					'url'     => $redirect_url,
					'message' => apply_filters(
						'yith_stripe_payments_checkout_redirect_action',
						_x( 'Please, confirm your purchasing using the following button. You order will be automatically processed as soon as we receive confirmation of your payment. Thanks for your preference!', 'Post checkout message', 'yith-stripe-payments-for-woocommerce' ),
						$order,
						$intent
					),
				);
			}

			// save next action meta when needed.
			! empty( $next_action_meta ) && $order->update_meta_data( $this->get_meta_key( 'next_action' ), $next_action_meta );

			// update status and save order.
			$order->update_status( $order_status, $order_note );

			return true;
		}

		/**
		 * Performs operation required after intent successful confirmation.
		 *
		 * @param \WC_Order $order  Order whose intent needs to be confirmed.
		 * @param Intent    $intent Intent object to confirm.
		 *
		 * @throws Payment_Exception | \Exception When there is an error with intent confirmation.
		 */
		protected function after_intent_success( $order, $intent ) {
			if ( ! in_array( $intent->status, array( 'succeeded', 'processing', 'requires_capture' ), true ) ) {
				throw new Payment_Exception( _x( 'Sorry, there was an error while processing payment; please, try again.', 'Payment error', 'yith-stripe-payments-for-woocommerce' ), 'unexpected_intent_status', $order );
			}

			if ( 'processing' === $intent->status && $order->get_total() > 0 ) {
				// Payment processing (requires customer action).
				$order->set_transaction_id( $intent->latest_charge );
				$order->set_status( apply_filters( 'yith_stripe_payments_element_processing_order_status', 'on-hold', $order ) );
			} else {
				// Payment complete.
				$amount_captured = Amount::decode( $intent->amount - $intent->amount_capturable, $order->get_currency() );

				$order->update_meta_data( self::get_meta_key( 'captured' ), $amount_captured );
				$order->payment_complete( $intent->latest_charge );
			}

			// Add order note.
			// translators: 1. Charge id.
			$order->add_order_note( sprintf( __( 'Stripe payment approved (ID: %s)', 'yith-stripe-payments-for-woocommerce' ), $intent->latest_charge ) );

			// clears intent from session.
			$this->get_session_intent()->clear();

			// Remove cart.
			WC()->cart->empty_cart();

			$order->save();
		}
	}
}
