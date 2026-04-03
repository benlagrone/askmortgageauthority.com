<?php
/**
 * Intent handling class
 *
 * @author  YITH
 * @package YITH\StripePayments\Models
 * @version 1.0.0
 */

namespace YITH\StripePayments\Models;

use WpOrg\Requests\Exception;
use YITH\StripePayments\Amount;
use YITH\StripePayments\Models\Abstracts\Model;
use YITH\StripeClient\Models\Intent as StripeIntent;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Models\Intent' ) ) {
	/**
	 * Handles intent objects, offering unified way to retrieve, update and confirm them
	 * It also offers a static part that works as a factory and cache for class instances
	 *
	 * @since 1.0.0
	 */
	class Intent extends Model {

		/**
		 * Object representing Intent on the library
		 *
		 * @var StripeIntent
		 */
		protected $model;

		/**
		 * Array of internal properties, used to have base model to perform actions
		 *
		 * @var array
		 */
		protected $data = array(
			'amount'                    => 0,
			'currency'                  => '',
			'customer'                  => '',
			'description'               => '',
			'payment_method'            => '',
			'intent'                    => '',
			'off_session'               => false,
			'receipt_email'             => '',
			'automatic_payment_methods' => false,
			'payment_method_types'      => array(),
			'capture_method'            => 'automatic',
			'setup_future_usage'        => '',
			'metadata'                  => array(),
		);

		/**
		 * Retrieves intent by creating it or updating an existing object
		 *
		 * @param float  $amount   Amount of the intent.
		 * @param string $currency Currency for the intent.
		 * @param array  $args     Array of arguments for the operation. Formatted as follows:<br>
		 *                        * 'customer'           => 'cus_***********', // customer ID on Stripe. When not passed, tries to use order customer (if any)<br>
		 *                        * 'payment_method'     => 'pm_**********',   // payment method ID on Stripe. When not passed, tries to use order method (if any)<br>
		 *                        * 'intent'             => 'pi_***********',  // payment intent to pay. When passed, all other parameters are optional. When missing, searches for it in the order, and eventually updates it. Last option is to create a new one<br>
		 *                        * 'description'        => ''                 // payment intent description
		 *                        * 'off_session'        => true,              // to set intent as off session on Stripe<br>
		 *                        * 'receipt_email'      => ''                 // Email where to send receipt email<br>
		 *                        * 'capture_method'     => ''                 // Capture method (one between automatic|automatic_async|manual)<br>
		 *                        * 'setup_future_usage' => ''                 // Whether to set method for future usage (one between on_session|off_session)<br>
		 *                        * 'metadata'           => []                 // Array of metadata for the intent.
		 * @return Intent|\WP_error Instance of current class with intent details, or WP_Error on error.
		 */
		public static function get( $amount, $currency, $args = array() ) {
			// search for intent to use, if any, and remove it from arguments array.
			$intent = isset( $args['intent'] ) ? $args['intent'] : false;
			unset( $args['intent'] );

			try {
				// validates amount before submitting it for API processing.
				if ( $amount && ! Amount::is_valid( $amount, $currency ) ) {
					throw new Exception( __( 'Amount doesn\'t match requirements for being processed by Stripe.', 'yith-stripe-payments-for-woocommerce' ), 'incorrect_amount' );
				}

				// update existing intent, or create new one.
				if ( ! $intent ) {
					$intent_args = yith_stripe_payments_merge_recursive(
						array(
							'amount'                    => Amount::format( $amount ),
							'currency'                  => Amount::get_formatted_currency( $currency ),
							'confirm'                   => false,
							'automatic_payment_methods' => array(
								'enabled' => true,
							),
						),
						array_filter( $args )
					);

					$_intent = new self( $intent_args );
				} else {
					$intent_args = yith_stripe_payments_merge_recursive(
						array(
							'amount'   => Amount::format( $amount ),
							'currency' => Amount::get_formatted_currency( $currency ),
						),
						array_filter( $args )
					);

					$_intent = new self( $intent );
					$_intent->update( $intent_args );
				}
			} catch ( \Exception $e ) {
				$_intent = new \WP_Error( 'intent_creation_error', $e->getMessage() );
			}

			// if an error occurred during creation/update process, end here.
			if ( is_wp_error( $_intent ) ) {
				return $_intent;
			}

			// create instance of this class and save it in local cache.
			self::$instances[ $_intent->id ] = $_intent;

			// return object instance.
			return $_intent;
		}

		/*
		 |--------------------------------------------------------------------------
		 | CURD
		 |--------------------------------------------------------------------------
		 */

		/**
		 * Create a new object on remote server, and returns instance.
		 *
		 * @param array $args Arguments to use for update call.
		 * @return Model|\WP_Error Created instance.
		 */
		public function create( $args = array() ) {
			$intent = self::get_api()->create_intent(
				yith_stripe_payments_merge_recursive(
					$this->data,
					$args
				)
			);

			if ( is_wp_error( $intent ) ) {
				return $intent;
			}

			$this->id    = $intent->id;
			$this->model = $intent;

			return $this;
		}

		/**
		 * Reads object from database when needed, or when explicitly requested
		 *
		 * @param bool $force Whether to force re-read.
		 * @return Intent|\WP_Error Current instance (for concatenation).
		 */
		public function read( $force = false ) {
			if ( is_null( $this->model ) || $force ) {
				$intent = $this->get_api()->get_intent( $this->id );

				if ( is_wp_error( $intent ) ) {
					return $intent;
				}

				$this->model = $intent;
			}

			return $this;
		}

		/**
		 * Updates current intent
		 *
		 * @param array $args Arguments to use for update call.
		 * @return Intent|\WP_Error Current instance (for concatenation).
		 */
		public function update( $args = array() ) {
			if ( $this->id ) {
				$intent = $this->get_api()->update_intent(
					$this->id,
					yith_stripe_payments_merge_recursive(
						$this->data,
						$args
					)
				);

				if ( is_wp_error( $intent ) ) {
					return $intent;
				}

				$this->model = $intent;
			}

			return $this;
		}

		/**
		 * Deletes an object on remote server
		 *
		 * @throws \Exception Method is not implemented; do not use it.
		 */
		public function delete(): bool {
			throw new \Exception( 'Delete method is not implemented for this object' );
		}

		/**
		 * Confirms current intent
		 *
		 * @param array $args Arguments to use for confirm call.
		 * @return Intent|\WP_Error Current instance (for concatenation).
		 */
		public function confirm( $args = array() ) {
			if ( 'requires_confirmation' === $this->status ) {
				$intent = $this->get_api()->confirm_intent( $this->id, $args );

				if ( is_wp_error( $intent ) ) {
					return $intent;
				}

				$this->model = $intent;
			}

			return $this;
		}
	}
}
