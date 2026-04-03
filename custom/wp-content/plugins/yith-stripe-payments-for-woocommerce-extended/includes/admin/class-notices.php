<?php
/**
 * Admin notices class
 *
 * @author  YITH
 * @package YITH\StripePayments\Admin
 * @version 1.0.0
 */

namespace YITH\StripePayments\Admin;

use YITH\StripePayments\Admin\Main as Admin;
use YITH\StripePayments\Amount;
use YITH\StripePayments\Cache_Helper;
use YITH\StripePayments\Traits\Singleton;
use YITH\StripePayments\Traits\Environment_Access;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Admin\Notices' ) ) {
	/**
	 * Stripe Payments admin class
	 * Register tha panel, and handles admin actions
	 *
	 * @since 1.0.0
	 */
	class Notices {

		use Singleton, Environment_Access;

		/**
		 * Single instance of the admin panel
		 *
		 * @var Panel
		 */
		protected static $panel;

		/**
		 * Single instance of the admin panel class
		 *
		 * @var array
		 */
		protected static $notices;

		/**
		 * The notice types
		 *
		 * @var array
		 */
		protected static $notice_types = array(
			'error',
			'warning',
			'success',
			'info',
		);

		/**
		 * Constructor method; init admin handling
		 */
		protected function __construct() {
			self::load();

			add_action( 'admin_init', array( __CLASS__, 'register_notices' ) );
			add_action( 'admin_menu', array( __CLASS__, 'maybe_add_brand_notice' ) );
			add_action( 'admin_menu', array( __CLASS__, 'maybe_add_ssl_notice' ) );
			add_action( 'admin_menu', array( __CLASS__, 'maybe_add_currency_notice' ) );
			add_action( 'shutdown', array( __CLASS__, 'save_notices' ) );
		}

		/**
		 * Add the notice
		 *
		 * @return void
		 */
		public static function register_notices() {
			foreach ( self::$notices as $notice ) {
				self::$panel->add_notice( $notice['message'], $notice['type'] );
			}
			self::clear();
		}

		/**
		 * Initialize the class properties
		 */
		public static function load() {
			self::$panel   = Panel::get_instance();
			self::$notices = get_transient( self::get_transient_key() );

			if ( ! is_array( self::$notices ) ) {
				self::$notices = array();
			}
		}

		/**
		 * Get transient key
		 *
		 * @param string $message The notice message.
		 * @param string $type    The notice type.
		 *
		 * @return void
		 */
		public static function add_notice( $message, $type = 'error' ) {
			if ( $message ) {
				self::$notices[] = compact( 'message', 'type' );
			}
		}

		/**
		 * Get transient key
		 *
		 * @param \WP_Error $error The error.
		 *
		 * @return void
		 */
		public static function add_notice_from_wp_error( $error ) {
			if ( ! is_wp_error( $error ) ) {
				return;
			}

			$error_data     = $error->get_error_data();
			$error_message  = self::get_hint( $error, $error->get_error_message() );
			$error_severity = ! empty( $error_data['severity'] ) && in_array( $error_data['severity'], self::$notice_types, true ) ? $error_data['severity'] : false;

			self::$notices[] = array(
				'message' => $error_message,
				'type'    => $error_severity ? $error_severity : current( self::$notice_types ),
			);
		}

		/**
		 * Add notice when store currency is not supported by Stripe
		 */
		public static function maybe_add_currency_notice() {
			if ( ! Amount::is_supported_currency() ) {
				$store_currency = get_woocommerce_currency();
				$stripe_doc_url = 'https://stripe.com/docs/currencies';
				self::add_notice(
					sprintf(
						// Translators: 1. Current store currency. 2. Url to Stripe DOC.
						__( 'Stripe doesn\'t currently support your store\'s currency (%1$s); you can consult a list of supported currencies at <a href="%2$s" target="_blank">this link</a>. Plugin is currently enabled, but no gateway will be available until you switch to a supported currency.', 'yith-stripe-payments-for-woocommerce' ),
						$store_currency,
						$stripe_doc_url
					),
					'warning'
				);
			}
		}

		/**
		 * Add SSL notice for live store
		 */
		public static function maybe_add_ssl_notice() {
			$env = Admin::get_env();
			$ssl = is_ssl();

			if ( 'live' === $env && ! $ssl ) {
				self::add_notice( __( 'A secure connection is required when running Stripe Payments in Live mode; plugin is currently enabled, but no gateway will be available until you secure connection to your site through a valid SSL certificate', 'yith-stripe-payments-for-woocommerce' ), 'warning' );
			}
		}

		/**
		 * Add notice for non-valid brand
		 */
		public static function maybe_add_brand_notice() {
			$brand = yith_stripe_payments_get_brand();

			if ( ! $brand ) {
				self::add_notice( __( 'A valid hosting plugin is required in order to proceed with the plugin configuration. Please contact your hosting provider for further details.', 'yith-stripe-payments-for-woocommerce' ), 'warning' );
			}
		}

		/**
		 * Delete the notices
		 *
		 * @return void
		 */
		public static function clear() {
			delete_transient( self::get_transient_key() );
			self::$notices = array();
		}

		/**
		 * Save the notices in a transient
		 *
		 * @return void
		 */
		public static function save_notices() {
			if ( ! empty( self::$notices ) ) {
				set_transient( self::get_transient_key(), self::$notices );
			}
		}

		/**
		 * Get transient key
		 *
		 * @return string
		 */
		public static function get_transient_key() {
			return Cache_Helper::get_site_key( 'notices' );
		}

		/**
		 * Get the message to display based on the code
		 *
		 * @param string|\WP_Error $error    The error.
		 * @param string           $fallback The fallback message.
		 *
		 * @return string
		 */
		public static function get_hint( $error, $fallback = '' ) {
			$message = '';
			$hints   = array(
				'connection_error'              => __( 'Unable to contact the server', 'yith-stripe-payments-for-woocommerce' ),
				// translators: %s is the specified URL.
				'account_by_site_url_not_found' => __( "Couldn't find any account for the specified URL (%s)", 'yith-stripe-payments-for-woocommerce' ),
			);

			$code = is_wp_error( $error ) ? $error->get_error_code() : $error;

			switch ( $code ) {
				case 'account_by_site_url_not_found':
					$link    = self::get_prop_from_error_data( $error, 'siteUrl' );
					$message = isset( $hints[ $code ] ) ? sprintf( $hints[ $code ], $link ) : $fallback;
					break;
				default:
					$message = array_key_exists( $code, $hints ) ? $hints[ $code ] : $fallback;
					break;
			}

			return $message;
		}

		/**
		 * Get prop from error data.
		 *
		 * @param \WP_Error $error The error.
		 * @param string    $prop  The prop name.
		 *
		 * @return mixed
		 */
		protected static function get_prop_from_error_data( $error, $prop ) {
			$value = null;

			if ( is_wp_error( $error ) ) {
				$error_data = $error->get_error_data();
				if ( isset( $error_data['data'][ $prop ] ) ) {
					$value = $error_data['data'][ $prop ];
				}
			}

			return $value;
		}
	}
}
