<?php
/**
 * Apple Pay registration class
 *
 * This class handle the Apple Pay registration to allow merchants to
 * Use Apple Pay through the Stripe services
 *
 * @author  YITH
 * @package YITH\StripePayments\Classes
 * @version 1.0.5
 */

namespace YITH\StripePayments;

use YITH\StripePayments\Traits\Logger;
use YITH\StripePayments\Traits\Singleton;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Apple_Pay_Registration' ) ) {
	class Apple_Pay_Registration {
		use Singleton;
		use Logger;

		const DOMAIN_ASSOCIATION_FILE_NAME = 'apple-developer-merchantid-domain-association';
		const DOMAIN_ASSOCIATION_FILE_DIR  = '.well-known';
		const UP_TO_DATE_TRANSIENT_NAME    = 'apple_pay_association_file_is_up_to_date';

		protected function __construct() {
			add_action( 'init', array( $this, 'verify_domain_on_update' ), 5 );
			add_action( 'init', array( $this, 'add_domain_association_rewrite_rule' ), 5 );
			add_filter( 'query_vars', array( $this, 'whitelist_domain_association_query_param' ), 10 );
			add_action( 'parse_request', array( $this, 'parse_domain_association_request' ), 10 );
		}

		/**
		 * Verify domain upon plugin update only in case the domain association file has changed.
		 */
		public function verify_domain_on_update() {
			// TODO: maybe specialize the enabled checks for Stripe Elements gateway
			if ( Gateways::get_instance()->are_enabled() && ! $this->is_hosted_domain_association_file_up_to_date() ) {
				$this->verify_domain_if_configured();
			}
		}

		/**
		 * Process the Apple Pay domain verification if proper settings are configured.
		 */
		public function verify_domain_if_configured() {
			if ( ! Gateways::get_instance()->are_enabled() ) {
				return;
			}

			$this->update_domain_association_file();
		}

		/**
		 * Verifies if hosted domain association file is up-to-date
		 * with the file from the plugin directory.
		 *
		 * @return bool Whether file is up-to-date or not.
		 */
		private function is_hosted_domain_association_file_up_to_date() {
			$transient_key = Cache_Helper::get_site_key( self::UP_TO_DATE_TRANSIENT_NAME );
			$is_up_to_date = get_transient( $transient_key );

			if ( ! $is_up_to_date ) {
				$fullpath = $this->get_domain_association_file_location( 'ABSPATH' );
				if ( ! file_exists( $fullpath ) ) {
					return false;
				}

				// Contents of domain association file from plugin dir.
				$new_contents = @file_get_contents( $this->get_domain_association_file_location() );

				// Get file contents from local path and remote URL and check if either of which matches.
				$local_contents  = @file_get_contents( $fullpath );
				$url             = $this->get_domain_association_file_location( 'site_url' );
				$response        = @wp_remote_get( $url );
				$remote_contents = @wp_remote_retrieve_body( $response );

				$is_up_to_date = $local_contents === $new_contents || $remote_contents === $new_contents;
				set_transient( $transient_key, $is_up_to_date, 3 * DAY_IN_SECONDS );
			}

			return $is_up_to_date;
		}

		/**
		 * Copies and overwrites domain association file.
		 *
		 * @return true|\WP_Error True if success, WP_Error otherwise.
		 */
		private function copy_and_overwrite_domain_association_file() {
			$well_known_dir = $this->get_domain_association_file_location( 'ABSPATH', false );

			if ( ! is_dir( $well_known_dir ) && ! @mkdir( $well_known_dir, 0755 ) && ! is_dir( $well_known_dir ) ) {
				return new \WP_Error( 'domain_association_file_mkdir_failed', __( 'Unable to create domain association folder to domain root.', 'yith-stripe-payments-for-woocommerce' ) );
			}

			if ( ! @copy( $this->get_domain_association_file_location(), $this->get_domain_association_file_location( 'ABSPATH' ) ) ) {
				return new \WP_Error( 'domain_association_file_copy_failed', __( 'Unable to copy domain association file to domain root.', 'yith-stripe-payments-for-woocommerce' ) );
			}

			return true;
		}

		/**
		 * Updates the Apple Pay domain association file.
		 * Reports failure only if file isn't already being served properly.
		 */
		public function update_domain_association_file() {
			if ( $this->is_hosted_domain_association_file_up_to_date() ) {
				return;
			}

			$error = $this->copy_and_overwrite_domain_association_file();

			if ( is_wp_error( $error ) ) {
				$url = $this->get_domain_association_file_location( 'site_url' );
				self::log(
					'Error: ' . $error->get_error_message() . ' ' .
					/* translators: expected domain association file URL */
					sprintf( __( 'To enable Apple Pay, domain association file must be hosted at %s.', 'yith-stripe-payments-for-woocommerce' ), $url )
				);
			} else {
				self::log( __( 'Domain association file updated.', 'yith-stripe-payments-for-woocommerce' ) );
				delete_transient( Cache_Helper::get_site_key( self::UP_TO_DATE_TRANSIENT_NAME ) );
			}
		}

		/**
		 * Adds a rewrite rule for serving the domain association file from the proper location.
		 */
		public function add_domain_association_rewrite_rule() {
			// Check if rewrite rule has been included, flushing them if not
			$rewrite_rules = get_option( 'rewrite_rules' );

			$regex    = '^\\' . self::DOMAIN_ASSOCIATION_FILE_DIR . '\/' . self::DOMAIN_ASSOCIATION_FILE_NAME . '$';
			$redirect = 'index.php?' . self::DOMAIN_ASSOCIATION_FILE_NAME . '=1';

			if ( ! is_array( $rewrite_rules ) || ! array_key_exists( $regex, $rewrite_rules ) ) {
				add_rewrite_rule( $regex, $redirect, 'top' );
				flush_rewrite_rules();
			}
		}

		/**
		 * Add Domain association query param to the list of publicly allowed query variables.
		 *
		 * @param array $query_vars The query vars.
		 *
		 * @return array
		 */
		public function whitelist_domain_association_query_param( $query_vars ) {
			$query_vars[] = self::DOMAIN_ASSOCIATION_FILE_NAME;

			return $query_vars;
		}

		/**
		 * Serve domain association file when proper query param is provided.
		 *
		 * @param object $wp WordPress environment object.
		 */
		public function parse_domain_association_request( $wp ) {
			if (
				! isset( $wp->query_vars[ self::DOMAIN_ASSOCIATION_FILE_NAME ] ) ||
				'1' !== $wp->query_vars[ self::DOMAIN_ASSOCIATION_FILE_NAME ]
			) {
				return;
			}
			$path = $this->get_domain_association_file_location();
			header( 'Content-Type: text/plain;charset=utf-8' );
			echo esc_html( @file_get_contents( $path ) );
			exit;
		}

		/**
		 * Return the path to the Domain Association File
		 *
		 * @param string $from         Where you want to get the path from, the local plugin, ABSPATH or site_url
		 * @param bool   $include_file Whether you want to include the file in the path.
		 *
		 * @return string
		 */
		public function get_domain_association_file_location( $from = 'plugin', $include_file = true ) {
			$base = YITH_STRIPE_PAYMENTS_DIR;

			if ( 'ABSPATH' === $from ) {
				$base = untrailingslashit( ABSPATH ) . '/' . self::DOMAIN_ASSOCIATION_FILE_DIR;
			}

			if ( 'site_url' === $from ) {
				$base = untrailingslashit( site_url() ) . '/' . self::DOMAIN_ASSOCIATION_FILE_DIR;
			}

			$fullpath = untrailingslashit( $base ) . '/' . self::DOMAIN_ASSOCIATION_FILE_NAME;

			return $include_file ? $fullpath : $base;
		}
	}
}
