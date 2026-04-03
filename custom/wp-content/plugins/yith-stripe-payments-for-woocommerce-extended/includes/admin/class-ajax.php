<?php
/**
 * Admin-only AJAX handlers
 *
 * @author  YITH
 * @package YITH\StripePayments\Classes
 * @version 1.0.0
 */

namespace YITH\StripePayments\Admin;

use YITH\StripePayments\Account;
use YITH\StripePayments\Admin\Main as Admin;
use YITH\StripePayments\Api_Client;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Admin\Ajax' ) ) {
	/**
	 * Handles all AJAX calls from backend requests.
	 *
	 * @since 1.0.0
	 */
	class Ajax {

		/**
		 * List of supported handlers
		 *
		 * @var string[]
		 */
		protected static $handlers = array(
			// onboarding related handlers.
			'process_onboarding',
			'refresh_connection_status',
			'revoke_connection',
		);

		/**
		 * Init defined AJAX handlers
		 */
		public static function init() {
			foreach ( self::$handlers as $handler ) {
				add_action( "wp_ajax_yith_stripe_payments_$handler", self::class . '::process' );
			}
		}

		/**
		 * Single AJAX handler for the plugin
		 * Performs basic checks over the call, then uses current action to execute proper handler in this class
		 */
		public static function process() {
			$current_action = current_action();
			$handler        = str_replace( 'wp_ajax_yith_stripe_payments_', '', $current_action );

			// checks for supported handler.
			if ( ! in_array( $handler, self::$handlers, true ) ) {
				wp_die();
			}

			// checks for correct nonce.
			check_admin_referer( $handler, 'security' );

			// checks that method exists.
			if ( ! method_exists( self::class, $handler ) ) {
				wp_die();
			}

			// runs proper handler.
			call_user_func( self::class . '::' . $handler );
		}

		/**
		 * Starts onboarding process if needed.
		 */
		protected static function process_onboarding() {
			// retrieve posted env.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$env = isset( $_REQUEST['env'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['env'] ) ) : false;

			// if env was posted, and it differs from current env, first of all save it as current.
			if ( $env && Admin::get_env() !== $env ) {
				Admin::set_env( $env );
			}

			$res = Onboarding::get_instance()->process();

			if ( ! $res ) {
				$error = Api_Client::get_last_error();
				Notices::add_notice_from_wp_error( $error );

				wp_send_json_error( $error );
			}

			wp_send_json_success( $res );
		}

		/**
		 * Refreshes status of the connection
		 */
		protected static function refresh_connection_status() {
			// retrieve posted env.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$env = isset( $_REQUEST['env'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['env'] ) ) : false;

			// if env was posted, and it differs from current env, first of all save it as current.
			if ( $env && Admin::get_env() !== $env ) {
				Admin::set_env( $env );
			}

			$res = Account::get_instance()->refresh();

			if ( ! $res ) {
				$error = Api_Client::get_last_error();
				Notices::add_notice_from_wp_error( $error );

				wp_send_json_error( $error );
			}

			wp_send_json_success( $res );
		}

		/**
		 * Revoke connection and deletes account (when possible)
		 */
		protected static function revoke_connection() {
			// retrieve posted env.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$env = isset( $_REQUEST['env'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['env'] ) ) : false;

			// if env was posted, and it differs from current env, first of all save it as current.
			if ( $env && Admin::get_env() !== $env ) {
				Admin::set_env( $env );
			}

			$res = Account::get_instance()->revoke();

			if ( ! $res ) {
				$error = Api_Client::get_last_error();
				Notices::add_notice_from_wp_error( $error );

				wp_send_json_error( $error );
			}

			wp_send_json_success( $res );
		}
	}
}
