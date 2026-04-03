<?php
/**
 * General gateway implementation
 * Offers a couple of common methods that should be implemented by any gateway of this plugin.
 *
 * @author  YITH
 * @package YITH\StripePayments\Classes\Gateways\Abstracts
 * @version 2.0.0
 */

namespace YITH\StripePayments\Gateways\Abstracts;

use YITH\StripePayments\Account;
use YITH\StripePayments\Amount;
use YITH\StripePayments\Cache_Helper;
use YITH\StripePayments\Gateways;
use YITH\StripePayments\Traits\Api_Client_Access;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Gateways\Abstracts\Gateway' ) ) {
	/**
	 * Main gateway class of the plugin
	 *
	 * @since 1.0.0
	 */
	abstract class Gateway extends \WC_Payment_Gateway_CC {

		use Api_Client_Access;

		/**
		 * Contains unique slug for the gateway.
		 *
		 * @var string
		 */
		public static $slug;

		/**
		 * Constructor method
		 */
		public function __construct() {
			$this->plugin_id = 'yith_stripe_payments_';
			$this->id        = static::$slug;
		}

		/**
		 * Init settings for gateways.
		 */
		public function init_settings() {
			parent::init_settings();

			$this->enabled = $this->is_enabled() ? 'yes' : 'no';
		}

		/**
		 * Returns true if method is enabled.
		 *
		 * @return bool Whether current gateway is enabled.
		 */
		public function is_enabled() {
			$slug    = static::$slug;
			$enabled = Gateways::get_instance()->are_enabled();

			return apply_filters( "yith_stripe_payments_{$slug}_enabled", $enabled, $this );
		}

		/**
		 * Returns current environment
		 *
		 * @return string
		 */
		public function get_env() {
			$slug = static::$slug;
			$env  = Gateways::get_instance()->get_env();

			return apply_filters( "yith_stripe_payments_{$slug}_environment", $env, $this );
		}

		/**
		 * Returns hashed meta key to use to save payment details as order/order_item meta.
		 *
		 * @param string $meta Meta to save.
		 * @return string Hashed meta key.
		 */
		public function get_meta_key( $meta ) {
			return Cache_Helper::get_account_key( "{$this->id}_{$meta}" );
		}

		/**
		 * Return whether or not this gateway still requires setup to function.
		 *
		 * When this gateway is toggled on via AJAX, if this returns true a
		 * redirect will occur to the settings page instead.
		 *
		 * @since 3.4.0
		 * @return bool
		 */
		public function needs_setup() {
			return ! Account::get_instance()->is_connected();
		}

		/**
		 * Check if the gateway is available for use.
		 *
		 * @return bool
		 */
		public function is_available() {
			if ( is_checkout() && ! $this->is_valid_for_checkout() ) {
				return false;
			}

			return parent::is_available() && Account::get_instance()->are_charges_enabled() && ( 'test' === $this->get_env() || is_ssl() ) && yith_stripe_payments_get_brand();
		}

		/**
		 *  Check if the gateway is valid for current checkout
		 *
		 * @return bool
		 */
		public function is_valid_for_checkout() {
			$checkout_details = yith_stripe_payments_get_checkout_details();

			return $checkout_details['amount'] && Amount::is_valid( $checkout_details['amount'], $checkout_details['currency'] ?? '' );
		}

		/**
		 * Returns gateway-specific details that needs to be returned to AJAX call that updates checkout.
		 *
		 * @return array Array of gateway-specific details
		 */
		public function get_checkout_details() {
			return array();
		}

		/**
		 * This method allows callers to force an arbitrary payment for a customer, depending on the configuration array passed
		 *
		 * @param int   $order_id Order id to pay.
		 * @param array $args     Array of arguments for the operation.
		 */
		abstract public function pay( $order_id, $args = array() );

		/**
		 * Returns unique identifier of current instance (by default site url)
		 *
		 * @return string Unique store identifier.
		 */
		public static function get_instance() {
			return apply_filters( 'yith_stripe_payments_gateway_instance', Account::get_instance()->get_url() );
		}
	}
}
