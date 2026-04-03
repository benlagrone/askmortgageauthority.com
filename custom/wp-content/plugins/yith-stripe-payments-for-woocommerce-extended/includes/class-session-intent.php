<?php
/**
 * Session Intent
 *
 * Contains method that allow for easy access of the unique intent stored into customer session.
 * This intent is registered as soon as customer visit checkout and loads Stripe.js widget, and it will be used by
 * subsequent checkout.
 * Any change to order amount or currency will trigger update of the intent, while any change to the account or plugin
 * configuration will force the generation of a new intent
 *
 * @author  YITH
 * @package YITH\StripePayments\Classes
 * @version 1.0.0
 */

namespace YITH\StripePayments;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

use YITH\StripePayments\Models\Intent;
use YITH\StripePayments\Traits\Factory;

if ( ! class_exists( 'YITH\StripePayments\Session_Intent' ) ) {
	/**
	 * Singleton class that allows to create, access or updated intent registered in customer session.
	 *
	 * @since 1.0.0
	 */
	class Session_Intent {

		use Factory;

		/**
		 * Stores key where intent details are stored into session
		 *
		 * @var string
		 */
		protected $session_key;

		/**
		 * Slug of the gateway
		 *
		 * @var string
		 */
		protected $gateway_slug = '';

		/**
		 * Details about current intent.
		 *
		 * @var array
		 */
		protected $details = array();

		/**
		 * Hash generated for current intent
		 *
		 * @var string
		 */
		protected $hash = '';

		/**
		 * Init session intent
		 *
		 * @param string $gateway_slug Factory pattern allows to create and manage different intents per different gateways.
		 */
		protected function __construct( $gateway_slug ) {
			$this->gateway_slug = $gateway_slug;
			$this->details      = WC()->session->get( $this->get_session_key(), array() );

			$this->maybe_update();
		}

		/**
		 * Returns Intent Id from details cache
		 * Usually this returns a "pi_..." ID, but it may occasionally happen that returns false (on intent creation failure)
		 *
		 * @return string|bool Intent id, or false on failure.
		 */
		public function get_id() {
			return isset( $this->details['id'] ) ? $this->details['id'] : false;
		}

		/**
		 * Returns Intent Client secret from details cache
		 * Usually this returns a "pi_..._secret_..." ID, but it may occasionally happen that returns false (on intent creation failure)
		 *
		 * @return string|bool Intent Client secret, or false on failure.
		 */
		public function get_secret() {
			return isset( $this->details['secret'] ) ? $this->details['secret'] : false;
		}

		/**
		 * Returns hash for current intent details; if no has has been set yet, calculates it.
		 *
		 * @return string Intent hash.
		 */
		public function get_hash() {
			if ( ! $this->hash && $this->details ) {
				$this->hash = $this->calculate_hash();
			}

			return $this->hash;
		}

		/**
		 * Clears cache for current intent.
		 */
		public function clear() {
			$this->details = array();
			$this->hash    = false;

			WC()->session->set( $this->get_session_key(), false );
		}

		/**
		 * Calculate hash for current intent details
		 *
		 * @return string Current intent hash.
		 */
		protected function calculate_hash() {
			$components = array_diff_key(
				$this->details,
				array_flip( array( 'id', 'secret' ) )
			);

			return md5( implode( '|', array_filter( $components ) ) );
		}

		/**
		 * Update intent when necessary (intent does not exist, or currency/amount do not match those coming from checkout)
		 *
		 * @return array Array of details for the intent (fresh ones if update has been processed)
		 */
		protected function maybe_update() {
			if ( $this->get_id() && $this->get_hash() === $this->get_checkout_hash() ) {
				return $this->details;
			}

			return $this->update();
		}

		/**
		 * Updates the intent with fresh data
		 */
		private function update() {
			$checkout_details = yith_stripe_payments_get_checkout_details();

			// create a new intent, or update existing one with new amount and currency.
			$gateway = Gateways::get_instance()->get_gateway( $this->gateway_slug );
			$intent  = ( $gateway && method_exists( $gateway, 'create_intent' ) ) ? $gateway->create_intent(
				$checkout_details['amount'],
				$checkout_details['currency'],
				array(
					'intent' => $this->get_id(),
				)
			) : false;

			// if we have a valid intent, update details and session.
			if ( $intent && ! is_wp_error( $intent ) ) {
				// update details with fresh intent info.
				$this->details = array_merge(
					$checkout_details,
					array(
						'id'     => $intent->id,
						'secret' => $intent->client_secret,
					)
				);

				// save details in session.
				WC()->session->set( $this->get_session_key(), $this->details );
			}

			// reset hash so that it can be calculated again.
			$this->hash = false;

			return $this->details;
		}

		/**
		 * Returns hash for current checkout details (amount/currency)
		 *
		 * @return string Checkout hash.
		 */
		protected function get_checkout_hash() {
			$checkout_details = yith_stripe_payments_get_checkout_details();

			return md5( implode( '|', array_filter( $checkout_details ) ) );
		}

		/**
		 * Static method that returns key where checkout intent is stored
		 * Key  is versioned, so that gateway can easily invalidate it
		 *
		 * @return string Session key.
		 */
		protected function get_session_key() {
			if ( ! $this->session_key ) {
				$this->session_key = Cache_Helper::get_versioned_key( "checkout_intent_{$this->gateway_slug}" );
			}

			return $this->session_key;
		}
	}
}
