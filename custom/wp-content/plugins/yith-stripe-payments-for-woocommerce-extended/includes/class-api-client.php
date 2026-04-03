<?php
/**
 * API Client class
 *
 * THis class offers methods to make use of YITH Stripe Payments Client library
 * It stands as unique point access to the library; may something change there, only this class will need to change.
 * Rest of the application will be left intact
 *
 * @author  YITH
 * @package YITH\StripePayments\Classes
 * @version 1.0.0
 */

namespace YITH\StripePayments;

use YITH\StripePayments\Traits\Logger;
use YITH\StripePayments\Traits\Singleton;
use YITH\StripePayments\Traits\Environment_Access;

use YITH\StripeClient\Client as StripeClient;
use YITH\StripeClient\Models as StripeObjects;
use YITH\StripeClient\Exceptions\Abstracts\Exception as StripeException;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Api_Client' ) ) {
	/**
	 * API Client
	 * Offers unique point of failure to talk with API client library
	 *
	 * @since 1.0.0
	 */
	class Api_Client {

		/**
		 * Store the last API error
		 *
		 * @var null|\WP_Error
		 */
		protected static $last_error = null;

		use Singleton, Logger, Environment_Access;

		/**
		 * Performs init operation on the client
		 */
		protected function __construct() {
			$plugin_descriptor = 'YITH\StripePayments\\' . YITH_STRIPE_PAYMENTS_VERSION;
			$client_descriptor = StripeClient::get_user_agent();

			StripeClient::set( 'environment', $this->get_env() );
			StripeClient::set( 'brand', yith_stripe_payments_get_brand() );
			StripeClient::set( 'user-agent', "$plugin_descriptor $client_descriptor" );

			add_action( 'yith_stripe_payments_updated_environment', array( $this, 'update_client_env' ) );
		}

		/**
		 * Set authentication token before an authenticated request
		 */
		protected function set_auth() {
			$token = Account::get_instance()->get_token();
			$token && StripeClient::set( 'auth', $token );

			return $token;
		}

		/**
		 * Receives an Exception object and build a WP_Error starting from that
		 *
		 * @param \Exception $e Exception object.
		 *
		 * @return \WP_Error Error object.
		 */
		protected function format_error( $e ) {
			if ( $e instanceof StripeException ) {
				self::$last_error = new \WP_Error( $e->getType(), $e->getMessage(), $e->getDetails() );
			} else {
				self::$last_error = new \WP_Error( $e->getCode(), $e->getMessage() );
			}

			return self::$last_error;
		}

		/**
		 * Log any request performed
		 */
		protected static function log_request() {
			$level = StripeClient::get_last_error() ? 'error' : 'info';
			$message = StripeClient::get_message_log();
			$message && self::log( $message, $level );
		}

		/**
		 * Update Stripe Client
		 */
		public function update_client_env() {
			StripeClient::set( 'environment', $this->get_env() );
		}

		/**
		 * Reads and returns public key from the server
		 *
		 * @return string|bool
		 */
		public function get_public_key() {
			$public_key = get_transient( Cache_Helper::get_site_key( 'public_key' ) );

			if ( $public_key ) {
				return $public_key;
			}

			try {
				$public_key = StripeObjects\Public_Key::read();
			} catch ( \Exception $e ) {
				$this->format_error( $e );
				$public_key = false;
			} finally {
				self::log_request();
			}

			set_transient( Cache_Helper::get_site_key( 'public_key' ), $public_key, WEEK_IN_SECONDS );

			return $public_key;
		}

		/**
		 * Creates an account over on Stripe
		 *
		 * @param string $site_url    Url for current site, to be used to generate account.
		 * @param string $return_url  Url where owner will be redirected after completing onboarding.
		 * @param string $webhook_url Url where application expect to receive webhooks for this account.
		 *
		 * @return StripeObjects\Account|\WP_Error Decoded result from the server
		 */
		public function create_account( $site_url, $return_url, $webhook_url = false ) {
			try {
				return StripeObjects\Account::create(
					array(
						'site_url'    => $site_url,
						'return_url'  => $return_url,
						'webhook_url' => $webhook_url,
					)
				);
			} catch ( \Exception $e ) {
				return $this->format_error( $e );
			} finally {
				self::log_request();
			}
		}

		/**
		 * Creates an account over on Stripe
		 *
		 * @param string $acct_id Unique account id retrieved after connecting site to the server.
		 *
		 * @return StripeObjects\Account|\WP_Error Decoded result from the server
		 */
		public function get_account( $acct_id ) {
			if ( ! $this->set_auth() ) {
				return self::get_last_error();
			}

			try {
				return StripeObjects\Account::read( $acct_id );
			} catch ( \Exception $e ) {
				return $this->format_error( $e );
			} finally {
				self::log_request();
			}
		}

		/**
		 * Delete an account over on Stripe
		 *
		 * @param string $acct_id Unique account id retrieved after connecting site to the server.
		 *
		 * @return bool|\WP_Error Status of the operation
		 */
		public function delete_account( $acct_id ) {
			if ( ! $this->set_auth() ) {
				return self::get_last_error();
			}

			try {
				return StripeObjects\Account::delete( $acct_id );
			} catch ( \Exception $e ) {
				return $this->format_error( $e );
			} finally {
				self::log_request();
			}
		}

		/**
		 * Creates an authorization token over on Stripe
		 *
		 * @param string $site_url Url for current site, to be used to identify requester.
		 *
		 * @return StripeObjects\Token|\WP_Error Decoded result from the server
		 */
		public function create_token( $site_url ) {
			try {
				return StripeObjects\Token::create(
					array(
						'site_url' => $site_url,
					)
				);
			} catch ( \Exception $e ) {
				return $this->format_error( $e );
			} finally {
				self::log_request();
			}
		}

		/**
		 * Creates a customer over on Stripe
		 *
		 * @param \WP_User $user The user id.
		 * @param array    $data Additional data.
		 *
		 * @return StripeObjects\Customer|\WP_Error Decoded result from the server
		 */
		public function create_customer( $user, $data = array() ) {
			if ( ! $this->set_auth() ) {
				return self::get_last_error();
			}

			$data = array_merge(
				$data,
				array(
					'user_id' => $user->ID,
					'name'    => $user->display_name,
					'email'   => $user->user_email,
				)
			);

			try {
				return StripeObjects\Customer::create( $data );
			} catch ( \Exception $e ) {
				return $this->format_error( $e );
			} finally {
				self::log_request();
			}
		}

		/**
		 * Get a customer from Stripe
		 *
		 * @param string $cus_id The Stripe customer id.
		 *
		 * @return StripeObjects\Customer|\WP_Error Decoded result from the server
		 */
		public function get_customer( $cus_id ) {
			if ( ! $this->set_auth() ) {
				return self::get_last_error();
			}

			try {
				return StripeObjects\Customer::read( $cus_id );
			} catch ( \Exception $e ) {
				return $this->format_error( $e );
			} finally {
				self::log_request();
			}
		}

		/**
		 * Update a customer in Stripe
		 *
		 * @param string $cus_id The Stripe customer id.
		 * @param array  $data   The customer data to update.
		 *
		 * @return StripeObjects\Customer|\WP_Error Decoded result from the server
		 */
		public function update_customer( $cus_id, $data ) {
			if ( ! $this->set_auth() ) {
				return self::get_last_error();
			}

			try {
				return StripeObjects\Customer::update( $cus_id, $data );
			} catch ( \Exception $e ) {
				return $this->format_error( $e );
			} finally {
				self::log_request();
			}
		}

		/**
		 * Creates an intent over on Stripe
		 *
		 * @param string $intent_id Id of the intent to retrieve.
		 *
		 * @return StripeObjects\Intent|\WP_Error Decoded result from the server
		 */
		public function get_intent( $intent_id ) {
			if ( ! $this->set_auth() ) {
				return self::get_last_error();
			}

			try {
				return StripeObjects\Intent::read( $intent_id );
			} catch ( \Exception $e ) {
				return $this->format_error( $e );
			} finally {
				self::log_request();
			}
		}

		/**
		 * Create an intent object on Stripe
		 *
		 * @param array $args Array of arguments for the intent creation ({@see \YITH\StripeClient\Models\Intent}).
		 *
		 * @return StripeObjects\Intent|\WP_Error Decoded result from the server
		 */
		public function create_intent( $args = array() ) {
			if ( ! $this->set_auth() ) {
				return self::get_last_error();
			}

			try {
				return StripeObjects\Intent::create( $args );
			} catch ( \Exception $e ) {
				return $this->format_error( $e );
			} finally {
				self::log_request();
			}
		}

		/**
		 * Updates an existing intent on Stripe
		 *
		 * @param string $intent_id Id of the intent to update.
		 * @param array  $args      Array of arguments for the intent creation ({@see \YITH\StripeClient\Models\Intent}).
		 *
		 * @return StripeObjects\Intent|\WP_Error Decoded result from the server
		 */
		public function update_intent( $intent_id, $args = array() ) {
			if ( ! $this->set_auth() ) {
				return self::get_last_error();
			}

			try {
				return StripeObjects\Intent::update( $intent_id, $args );
			} catch ( \Exception $e ) {
				return $this->format_error( $e );
			} finally {
				self::log_request();
			}
		}

		/**
		 * Confirms an existing intent on Stripe
		 *
		 * @param string $intent_id Id of the intent to update.
		 * @param array  $args      Array of arguments for the intent confirmation ({@see \YITH\StripeClient\Models\Intent::confirm()}).
		 *
		 * @return StripeObjects\Intent|\WP_Error Decoded result from the server
		 */
		public function confirm_intent( $intent_id, $args = array() ) {
			if ( ! $this->set_auth() ) {
				return self::get_last_error();
			}

			try {
				return StripeObjects\Intent::confirm( $intent_id, $args );
			} catch ( \Exception $e ) {
				return $this->format_error( $e );
			} finally {
				self::log_request();
			}
		}

		/**
		 * Capture an existing intent on Stripe
		 *
		 * @param string $intent_id Id of the intent to update.
		 *
		 * @return StripeObjects\Intent|\WP_Error Decoded result from the server
		 */
		public function capture_intent( $intent_id ) {
			if ( ! $this->set_auth() ) {
				return self::get_last_error();
			}

			try {
				return StripeObjects\Intent::capture( $intent_id );
			} catch ( \Exception $e ) {
				return $this->format_error( $e );
			} finally {
				self::log_request();
			}
		}

		/**
		 * Get registered brands
		 *
		 * @return array
		 */
		public static function get_registered_brands() {
			$transient_key = 'yith_stripe_payments_registered_brands_' . str_replace( array( '.', '-' ), '_', YITH_STRIPE_PAYMENTS_VERSION );
			$brands        = get_transient( $transient_key );

			if ( ! empty( $brands ) ) {
				return $brands;
			}

			try {
				$brands = StripeClient::call( 'get', 'brands' );
			} catch ( \Exception $e ) {
				$brands = [];
			} finally {
				self::log_request();
			}

			set_transient( $transient_key, $brands, WEEK_IN_SECONDS );

			return $brands;
		}

		/**
		 * Return the last API error
		 *
		 * @return null|\WP_Error
		 */
		public static function get_last_error() {
			return self::$last_error;
		}
	}
}
