<?php
/**
 * Charge events controller class
 *
 * @author  YITH
 * @package YITH\StripePayments\RestApi\Controllers
 * @version 1.0.0
 */

namespace YITH\StripePayments\RestApi\Controllers;

use YITH\StripePayments\Account;
use YITH\StripePayments\Cache_Helper;

if ( ! class_exists( 'YITH\StripePayments\RestApi\Controllers\Account_Controller' ) ) {
	/**
	 * Stripe `account` events controller
	 *
	 * @extends Event_Controller
	 */
	class Account_Controller extends Event_Controller {

		/**
		 * The Stripe event type
		 *
		 * @var string
		 */
		protected $event = 'account';

		/**
		 * Handle `account.updated` event
		 *
		 * @throws \Exception When request can't be processed for whatever reason.
		 */
		protected function updated() {
			$account_class   = Account::get_instance();
			$current_account = $account_class->get_account_id();
			$target_account  = isset( $this->params['account'] ) ? $this->params['account'] : '';

			if ( $target_account !== $current_account ) {
				// translators: 1. Received account id. 2. Registered account id.
				throw new \Exception( sprintf( __( 'Received account id (%1$s) does not match registered one (%2$s).', 'yith-woocommerce-stripe-payments' ), $target_account, $current_account ) );
			}

			Cache_Helper::invalidate_cache();
			$account_class->refresh();

			$this->add_response_action(
				array(
					'acc_id' => $account_class->get_account_id(),
					'action' => 'refresh',
				)
			);
		}

		/**
		 * Check if the webhook request is valid
		 *
		 * @return bool
		 */
		protected function is_valid() {
			return true; // Always valid for the Account.
		}
	}
}
