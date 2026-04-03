<?php
/**
 * Account connection class
 *
 * THis class offers methods to connect plugin to YITH Stripe Server  and keep a valid connection.
 *
 * @author  YITH
 * @package YITH\StripePayments\Classes
 * @version 1.0.0
 */

namespace YITH\StripePayments;

use YITH\StripeClient\Models\Account as StripeAccount;
use YITH\StripeClient\Models\Token as StripeToken;

use YITH\StripePayments\Traits\Singleton;
use YITH\StripePayments\Traits\Api_Client_Access;

use YITH\StripePayments\Admin\Onboarding as Onboarding;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Account' ) ) {
	/**
	 * Stripe Payments Account
	 * Represents connection status with the server, and offers methods to connect and re-auth when needed
	 *
	 * @since 1.0.0
	 */
	class Account {

		use Singleton, Api_Client_Access;

		/**
		 * Array describing current status of the plugin connection against remote server.
		 *
		 * @var array
		 */
		protected $connection_status;

		/**
		 * Token used to authenticate current instance against remote server
		 *
		 * @var string
		 */
		protected $connection_token;

		/**
		 * Hooks prefix.
		 *
		 * @var string
		 */
		protected $prefix = 'yith_stripe_payments_account_';

		/**
		 * Constructor method
		 */
		protected function __construct() {
			add_action( 'yith_stripe_payments_updated_environment', array( $this, 'clear_local_cache' ) );
		}

		/**
		 * Checks if current account is connected
		 *
		 * @return bool Connection status.
		 */
		public function is_connected() {
			return apply_filters( 'yith_stripe_payments_account_is_connected', ! ! $this->get_connection_status(), $this );
		}

		/**
		 * Returns true when the charges are enabled for current account.
		 *
		 * @return bool Whether charges are enabled for currently connected account
		 */
		public function are_charges_enabled() {
			$connection_status = $this->get_connection_status();

			if ( ! $connection_status ) {
				return false;
			}

			return apply_filters( 'yith_stripe_payments_account_are_charges_enabled', $connection_status[ 'charges_enabled' ] );
		}

		/**
		 * Returns an array representing the connection status
		 *
		 * @return array|bool Array representing connection status, or false on failure.
		 */
		public function get_connection_status() {
			if ( ! $this->connection_status ) {
				$connection_status = get_option( Cache_Helper::get_site_key( 'connection_status' ) );

				if ( $connection_status ) {
					// remove expired onboarding link, if any.
					if ( isset( $connection_status[ 'onboard_exp' ] ) && time() > $connection_status[ 'onboard_exp' ] ) {
						unset( $connection_status[ 'onboard_link' ], $connection_status[ 'onboard_exp' ] );
					}

					// merge stored data with default structure.
					$connection_status = array_merge(
						array(
							'acct_id'                => '',
							'secret'                 => '',
							'onboard_link'           => '',
							'onboard_exp'            => 0,
							'details_submitted'      => false,
							'charges_enabled'        => false,
							'domain_enabled'         => false,
							'payment_method_statuses' => [],
						),
						$connection_status
					);
				}

				$this->connection_status = $connection_status;
			}

			return apply_filters( 'yith_stripe_payments_account_connection_status', $this->connection_status, $this );
		}

		/**
		 * Returns a descriptive label for current account status
		 *
		 * @return string Current connection status, in a descriptive form.
		 */
		public function get_connection_status_label() {
			$status = $this->get_connection_status();

			if ( ! $status ) {
				$label = _x( 'Disconnected', 'Onboarding status', 'yith-stripe-payments-for-woocommerce' );
			} elseif ( ! $status[ 'charges_enabled' ] ) {
				$label = _x( 'Connected, waiting for account to be completed', 'Onboarding status', 'yith-stripe-payments-for-woocommerce' );
			} else {
				$label = _x( 'Connected', 'Onboarding status', 'yith-stripe-payments-for-woocommerce' );
			}

			return $label;
		}

		/**
		 * Retrieves connection token
		 *
		 * @return string|bool Connection status, or false if no connection was established
		 */
		public function get_token() {
			if ( ! $this->is_connected() ) {
				return false;
			}

			if ( ! $this->connection_token ) {
				$connection_token = get_transient( Cache_Helper::get_site_key( 'connection_token' ) );

				if ( $connection_token ) {
					$this->connection_token = $connection_token;
				}
			}

			if ( ! $this->connection_token ) {
				$this->connection_token = $this->reauth();
			}

			return $this->connection_token;
		}

		/**
		 * Retrieves account id for current site
		 *
		 * @return string|bool Account id, or false if no connection was established
		 */
		public function get_account_id() {
			$connection_status = $this->get_connection_status();

			if ( ! $connection_status ) {
				return false;
			}

			return $connection_status[ 'acct_id' ];

		}

		/**
		 * Returns the account secret for current site
		 *
		 * @return string|bool Account secret (in the form of UUID), or false if no connection was established
		 */
		public function get_account_secret() {
			$connection_status = $this->get_connection_status();

			if ( ! $connection_status ) {
				return false;
			}

			return $connection_status[ 'secret' ];
		}

		/**
		 * Get instance (site url) that uniquely identifies current installation
		 *
		 * @return string|bool Instance url, or false on failure.
		 */
		public function get_url() {
			$site_url   = get_site_url();
			$parsed_url = wp_parse_url( $site_url );

			// returns false on invalid url.
			if ( ! $parsed_url ) {
				return false;
			}

			// add scheme if missing.
			if ( ! isset( $parsed_url[ 'scheme' ] ) ) {
				$scheme   = is_ssl() ? 'https://' : 'http://';
				$site_url = "{$scheme}{$site_url}";
			}

			return apply_filters( 'yith_stripe_payments_account_site_url', $site_url, $this );
		}

		/**
		 * Get url where this account will receive endpoints from Stripe Server
		 *
		 * @return string|bool Instance url, or false on failure.
		 */
		public function get_webhook_url() {
			// TODO: replace webhook url with the one that comes from Webhooks controller.
			$webhook_url = rest_url( 'yith/stripe-payments/webhooks' );

			return apply_filters( 'yith_stripe_payments_account_webhook_url', $webhook_url, $this );
		}

		/**
		 * Runs connection process, to register site on remote server, and associate a stripe account with it.
		 *
		 * @return array|bool New connection status, or false on failure.
		 */
		public function connect() {
			$url = $this->get_url();

			if ( ! $url ) {
				return false;
			}

			// retrieve onboarding return URL and webhooks endpoint.
			$return_url  = Onboarding::get_return_url();
			$webhook_url = $this->get_webhook_url();

			$account = self::get_api()->create_account(
				$url,
				$return_url,
				$webhook_url
			);

			if ( is_wp_error( $account ) ) {
				return false;
			}

			return $this->update( $account );
		}

		/**
		 * Refreshes status flags for current account
		 */
		public function refresh() {
			$connection_status = $this->get_connection_status();

			if ( ! $connection_status || ! $connection_status[ 'acct_id' ] ) {
				return false;
			}

			$account = self::get_api()->get_account(
				$connection_status[ 'acct_id' ]
			);

			if ( is_wp_error( $account ) ) {
				if ( in_array( $account->get_error_code(), $this->get_not_found_error_codes(), true ) ) {
					$this->delete();
				}

				return false;
			}

			return $this->update( $account );
		}

		/**
		 * Revoke connection with the server
		 */
		public function revoke() {
			$connection_status = $this->get_connection_status();

			if ( ! $connection_status || ! $connection_status[ 'acct_id' ] ) {
				return false;
			}

			self::get_api()->delete_account(
				$connection_status[ 'acct_id' ]
			);

			return $this->delete();
		}

		/**
		 * Retrieves an updated JWT used for connection to the server
		 *
		 * @return string|bool New connection token, or false on failure.
		 */
		public function reauth() {
			$url = $this->get_url();

			if ( ! $url ) {
				return false;
			}

			$token = self::get_api()->create_token( $url );

			if ( is_wp_error( $token ) ) {
				if ( in_array( $token->get_error_code(), $this->get_not_found_error_codes(), true ) ) {
					$this->delete();
				}

				return false;
			}

			return $this->update_token( $token );
		}

		/**
		 * Updates account details with fresh data retrieve from API
		 *
		 * @param StripeAccount $account Client account object.
		 *
		 * @return array|bool New connection status, created from the account, or false.
		 */
		protected function update( $account ) {
			if ( is_wp_error( $account ) ) {
				return false;
			}

			// updates connection status.
			$connection_status = array(
				'acct_id'                 => $account->acct_id,
				'secret'                  => $account->secret,
				'details_submitted'       => $account->details_submitted,
				'charges_enabled'         => $account->charges_enabled,
				'onboard_link'            => $account->onboard_link,
				'onboard_exp'             => $account->onboard_exp,
				'domain_enabled'          => $account->pmd_enabled,
				'payment_method_statuses' => $account->pmd_statuses,
			);

			if ( update_option( Cache_Helper::get_site_key( 'connection_status' ), $connection_status ) ) {
				do_action( $this->prefix . 'updated', $this, $connection_status );
			}

			// update connection token.
			if ( $account->token ) {
				$this->update_token( $account->token );
			}

			return $connection_status;
		}

		/**
		 * Updates account details with fresh data retrieve from API
		 *
		 * @param StripeToken $token Account connection token.
		 *
		 * @return string|bool New connection token, created from the passed object, or false.
		 */
		protected function update_token( $token ) {
			if ( is_wp_error( $token ) ) {
				return false;
			}

			set_transient( Cache_Helper::get_site_key( 'connection_token' ), $token->auth, $token->expires_in );

			return $token->auth;
		}

		/**
		 * Clears locally stored values, to force system retrieve them again for next usages.
		 */
		public function clear_local_cache() {
			$this->connection_status = false;
			$this->connection_token  = false;
		}

		/**
		 * Delete currently stored account info and returns status of the operation
		 *
		 * @return bool Status of the operation.
		 */
		protected function delete() {
			$deleted = delete_option( Cache_Helper::get_site_key( 'connection_status' ) ) && delete_transient( Cache_Helper::get_site_key( 'connection_token' ) );

			$deleted && do_action( $this->prefix . 'deleted', $this );

			return $deleted;
		}

		protected function get_not_found_error_codes() {
			return array( 'account_not_found', 'account_by_site_url_not_found' );
		}
	}
}
