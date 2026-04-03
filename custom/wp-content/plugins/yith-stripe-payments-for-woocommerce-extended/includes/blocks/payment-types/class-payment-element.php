<?php
/**
 * Main gateway class of the plugin
 * Implements Payment Element feature from Stripe as a WooCommerce Payment Gateway
 *
 * @author  YITH
 * @package YITH\StripePayments\Classes\Blocks\PaymentTypes
 * @version 2.0.0
 */

namespace YITH\StripePayments\Blocks\PaymentTypes;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use YITH\StripePayments\Gateways;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Blocks\PaymentTypes\Payment_Element' ) ) {
	/**
	 * Main gateway class of the plugin
	 *
	 * @since 1.0.0
	 */
	class Payment_Element extends AbstractPaymentMethodType {
		/**
		 * Instance of the gateway
		 *
		 * @var YITH\StripePayments\Gateway\Payment_Element
		 */
		private $gateway;

		/**
		 * When called invokes any initialization/setup for the integration.
		 */
		public function initialize() {
			$this->gateway = Gateways::get_instance()->get_gateway( 'element' );
			$this->name    = $this->gateway->get_title();
		}

		/**
		 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
		 *
		 * @return boolean
		 */
		public function is_active() {
			return $this->gateway->is_enabled();
		}

		/**
		 * Returns an array of script handles to enqueue for this payment method in
		 * the frontend context
		 *
		 * @return string[]
		 */
		public function get_payment_method_script_handles() {
			$this->gateway->register_scripts();

			return array(
				'stripe',
				'yith-stripe-payments-element-block',
			);
		}

		/**
		 * Returns an array of script handles to enqueue in the admin context.
		 *
		 * @return string[]
		 */
		public function get_payment_method_script_handles_for_admin() {
			return $this->get_payment_method_script_handles();
		}
	}
}
