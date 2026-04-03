<?php
/**
 * Onboarding handler class
 *
 * @author  YITH
 * @package YITH\StripePayments\Classes
 * @version 1.0.0
 */

namespace YITH\StripePayments\Admin;

use YITH\StripePayments\Traits\Singleton;
use YITH\StripePayments\Account;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Admin\Onboarding' ) ) {
	/**
	 * Handles all actions related to onboarding on backend
	 *
	 * @since 1.0.0
	 */
	class Onboarding {

		use Singleton;

		/**
		 * Constructor method
		 */
		protected function __construct() {
			// enqueue assets.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );

			// register onboarding processing.
			add_action( 'admin_action_confirm_stripe_payments_onboarding', array( $this, 'complete' ) );

			// register onboarding template actions.
			add_action( 'woocommerce_admin_field_yith_stripe_payments_onboarding', array( $this, 'print_onboarding' ) );
			add_action( 'yith_stripe_payments_onboarding_content', array( $this, 'print_onboarding_button' ) );
			add_action( 'yith_stripe_payments_onboarding_content', array( $this, 'print_onboarding_details' ) );
		}

		/* === ACTIONS === */

		/**
		 * Returns true when we're currently visiting "Return url", used to redirect admin when exiting onboarding
		 *
		 * @return bool Whether current url matches "Return url" structure.
		 */
		public static function is_return_url() {
			global $pagenow;

			return 'admin.php' === $pagenow && isset( $_GET[ 'action' ] ) && 'confirm_stripe_payments_onboarding' === $_GET[ 'action' ];
		}

		/**
		 * Returns url visited once customer exits onboarding
		 *
		 * Reaching this url isn't a definite sign of having completed onboarding, so system will still need to check status flags.
		 *
		 * @return string Url where customer will be redirected when exiting the onboard flow.
		 */
		public static function get_return_url() {
			return add_query_arg( 'action', 'confirm_stripe_payments_onboarding', admin_url( 'admin.php' ) );
		}

		/**
		 * Process onboarding for current site
		 *
		 * Usually this method is called via AJAX handler, and it will have the effect of generating a new connection for current site
		 * whether this actually means generating a new account on the remote server for current instance, or just refreshing
		 * existing one.
		 */
		public function process() {
			// process onboarding.
			$account    = Account::get_instance();
			$connection = $account->get_connection_status();

			// if never connected, or onboarding has expired, perform fresh connection.
			if ( ! $connection || ! $connection[ 'onboard_link' ] ) {
				$connection = $account->connect();
			}

			return $connection;
		}

		/**
		 * Complete onboarding process
		 *
		 * This will first of all refresh account's status flags; then will show a greetings message,
		 * just before closing current window (onboarding popup) and refreshing parent.
		 */
		public function complete() {
			// return if url doesn't match.
			if ( ! self::is_return_url() ) {
				return;
			}

			// refreshes info we own about the account.
			Account::get_instance()->refresh();

			// prints "onboarding complete" message.
			$this->print_onboarding_complete();
		}

		/* === TEMPLATES === */

		/**
		 * Registers/Enqueues scripts specific to the onboarding process.
		 */
		public function enqueue() {
			$suffix = defined( 'SCRIPT_DEBUG' ) && \SCRIPT_DEBUG ? '' : '.min';

			wp_register_script( 'yith-stripe-payments-onboarding', YITH_STRIPE_PAYMENTS_URL . "assets/js/admin/yith-stripe-payments-onboarding.bundle{$suffix}.js", array( 'jquery', 'jquery-blockui' ), YITH_STRIPE_PAYMENTS_VERSION, true );
		}

		/**
		 * Prints button that triggers connection to Stripe
		 */
		public function print_onboarding() {
			// retrieve connection status.
			$account      = Account::get_instance();
			$connection   = $account->get_connection_status();
			$status_label = $account->get_connection_status_label();
			$connected    = ! ! $connection;

			include YITH_STRIPE_PAYMENTS_INC . 'admin/views/onboarding.php';
		}

		/**
		 * Prints connection button, when needed
		 *
		 * @param array $connection Connection status; when not passed, method will retrieve it directly from Account object.
		 */
		public function print_onboarding_button( $connection = null ) {
			if ( is_null( $connection ) ) {
				$connection = Account::get_instance()->get_connection_status();
			}

			if ( ! ! $connection ) {
				return;
			}

			$additional_classes = array();

			if ( ( Main::is_live() && ! is_ssl() ) || ! yith_stripe_payments_get_brand() ) {
				$additional_classes[] = 'disabled';
			}

			include YITH_STRIPE_PAYMENTS_INC . 'admin/views/onboarding-button.php';
		}

		/**
		 * Prints connection details, when needed
		 *
		 * @param array $connection Connection status; when not passed, method will retrieve it directly from Account object.
		 */
		public function print_onboarding_details( $connection = null ) {
			if ( is_null( $connection ) ) {
				$connection = Account::get_instance()->get_connection_status();
			}

			if ( ! $connection ) {
				return;
			}

			include YITH_STRIPE_PAYMENTS_INC . 'admin/views/onboarding-details.php';
		}

		/**
		 * Prints onboarding complete message, after Stripe redirects back the admin to the site.
		 *
		 * @param array $connection Connection status; when not passed, method will retrieve it directly from Account object.
		 */
		public function print_onboarding_complete( $connection = null ) {
			$message = _x(
				'Great news! Onboarding process is complete. You\'ll be redirected in a moment. Thank you for your patience, keep up with the great work :)',
				'[ADMIN] Shown in the onboarding popup when flow is complete',
				'yith-stripe-payments-for-woocommerce'
			);
			$script  = <<<EOJS
				<script>
					window.opener.location.reload();
					window.close();
				</script>
			EOJS;

			// non-static part of the output is already escaped, to need to escape script tag.
			// phpcs:ignore WordPress.Security.EscapeOutput
			wp_die( esc_html( $message ) . $script );
		}
	}
}
