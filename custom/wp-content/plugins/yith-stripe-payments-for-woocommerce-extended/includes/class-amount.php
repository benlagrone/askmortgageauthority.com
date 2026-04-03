<?php
/**
 * Amount class
 * Offers methods to format, decode, validate amounts basing on currency
 *
 * @author  YITH
 * @package YITH\StripePayments
 * @version 1.0.0
 */

namespace YITH\StripePayments;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Amount' ) ) {
	/**
	 * Amounts class
	 * Offers utilities to format and validate payment amounts.
	 *
	 * @since 1.0.0
	 */
	class Amount {

		/**
		 * Format amount to use it in API calls
		 *
		 * @param float  $amount   Amount to format.
		 * @param string $currency Currency to check; optionally can be left empty to use store currency.
		 * @return float Formatted value, or false if invalid.
		 */
		public static function format( float $amount, string $currency = '' ) {
			$currency = self::get_formatted_currency( $currency );

			if ( ! self::is_valid( $amount, $currency ) ) {
				return false;
			}

			$decimals = self::get_currency_decimals( $currency );

			return ceil( $amount * pow( 10, $decimals ) );
		}

		/**
		 * Decode amount coming from API results.
		 *
		 * @param float  $amount   Amount to format.
		 * @param string $currency Currency to check; optionally can be left empty to use store currency.
		 * @return float Decoded value, or false if invalid.
		 */
		public static function decode( float $amount, string $currency = '' ) {
			$currency = self::get_formatted_currency( $currency );

			if ( ! self::is_supported_currency( $currency ) ) {
				return false;
			}

			$decimals = self::get_currency_decimals( $currency );

			return round( $amount / pow( 10, $decimals ), $decimals );
		}

		/**
		 * Checks if an amount is valid to be processed through Stripe
		 *
		 * @param float  $amount   Amount to test.
		 * @param string $currency Currency to check; optionally can be left empty to use store currency.
		 *
		 * @return bool Whether amount is valid or not.
		 */
		public static function is_valid( float $amount, string $currency = '' ) {
			$currency = self::get_formatted_currency( $currency );

			return self::is_supported_currency( $currency ) && $amount >= self::get_currency_min( $currency ) && $amount <= self::get_currency_max( $currency );
		}

		/**
		 * Returns true when given currency is supported by Stripe
		 *
		 * @param string $currency Currency to check; optionally can be left empty to use store currency.
		 * @return bool Whether currency is supported or not
		 */
		public static function is_supported_currency( $currency = '' ) {
			// short-circuit to avoid this check at all.
			if ( ! apply_filters( 'yith_stripe_payments_enforce_currency_check', true ) ) {
				return true;
			}

			$currency = self::get_formatted_currency( $currency );

			return in_array( $currency, self::get_supported_currencies(), true );
		}

		/**
		 * Returns min amount supported by a given currency
		 *
		 * @param string $currency Currency to check; optionally can be left empty to use store currency.
		 * @return float Min amount supported by the currency.
		 */
		protected static function get_currency_min( string $currency = '' ) {
			// short-circuit to avoid this check at all.
			if ( ! apply_filters( 'yith_stripe_payments_enforce_min_charge_amount', true ) ) {
				return 0;
			}

			$currency = self::get_formatted_currency( $currency );

			// we can only assume store currency is the settlement one; if this isn't the case use the provided filter.
			$settlement_currency = apply_filters( 'yith_stripe_payments_settlement_currency', $currency );

			switch ( $settlement_currency ) {
				case 'usd':
					$min = 0.50;
					break;
				case 'aed':
					$min = 2.00;
					break;
				case 'aud':
					$min = 0.50;
					break;
				case 'bgn':
					$min = 1.00;
					break;
				case 'brl':
					$min = 0.50;
					break;
				case 'cad':
					$min = 0.50;
					break;
				case 'chf':
					$min = 0.50;
					break;
				case 'czk':
					$min = 15.00;
					break;
				case 'dkk':
					$min = 2.50;
					break;
				case 'eur':
					$min = 0.50;
					break;
				case 'gbp':
					$min = 0.30;
					break;
				case 'hkd':
					$min = 4.00;
					break;
				case 'hrk':
					$min = 0.50;
					break;
				case 'huf':
					$min = 175.00;
					break;
				case 'inr':
					$min = 0.50;
					break;
				case 'jpy':
					$min = 50;
					break;
				case 'mxn':
					$min = 10;
					break;
				case 'myr':
					$min = 2;
					break;
				case 'nok':
					$min = 3.00;
					break;
				case 'nzd':
					$min = 0.50;
					break;
				case 'pln':
					$min = 2.00;
					break;
				case 'ron':
					$min = 2.00;
					break;
				case 'sek':
					$min = 3.00;
					break;
				case 'sgd':
					$min = 0.50;
					break;
				case 'thb':
					$min = 10;
					break;
				default:
					$min = 0;
			}

			return $min;
		}

		/**
		 * Returns max amount supported by a given currency
		 *
		 * @param string $currency Currency to check; optionally can be left empty to use store currency.
		 * @return float Max amount supported by the currency.
		 */
		protected static function get_currency_max( string $currency = '' ) {
			// short-circuit to avoid this check at all.
			if ( ! apply_filters( 'yith_stripe_payments_enforce_max_amount', true ) ) {
				return PHP_FLOAT_MAX;
			}

			$currency = self::get_formatted_currency( $currency );

			if ( 'IDR' === $currency ) {
				return 9999999999.99;
			}

			return 999999.99;
		}

		/**
		 * Returns store currency
		 *
		 * @return string Store currency code.
		 */
		public static function get_store_currency() {
			return strtolower( get_woocommerce_currency() );
		}

		/**
		 * Returns formatted currency
		 *
		 * @param string $currency Currency to format; optionally can be left empty to use store currency.
		 * @return string Formatted currency.
		 */
		public static function get_formatted_currency( $currency = '' ) {
			$currency = $currency ? $currency : self::get_store_currency();

			return strtolower( $currency );
		}

		/**
		 * Returns number of decimals supported by given currency
		 *
		 * @param string $currency Currency to check; optionally can be left empty to use store currency.
		 * @return int Number of decimals supported.
		 */
		protected static function get_currency_decimals( string $currency = '' ) {
			$currency = $currency ? $currency : self::get_store_currency();

			// most currencies support 2 decimal digits; here we list notable exceptions.
			switch ( $currency ) {
				case 'bif':
				case 'clp':
				case 'djf':
				case 'gnf':
				case 'jpy':
				case 'kmf':
				case 'krw':
				case 'mga':
				case 'pyg':
				case 'rwf':
				case 'ugx':
				case 'vnd':
				case 'vuv':
				case 'xaf':
				case 'xof':
				case 'xpf':
					$decimals = 0;
					break;
				case 'bhd':
				case 'jod':
				case 'kwd':
				case 'omr':
				case 'tnd':
					$decimals = 3;
					break;
				default:
					$decimals = 2;
			}

			return $decimals;
		}

		/**
		 * Returns a list of supported currencies for this plugin
		 *
		 * @return string[] List of currency codes.
		 */
		protected static function get_supported_currencies() {
			return apply_filters(
				'yith_stripe_payments_supported_currencies',
				array(
					'usd',
					'aed',
					'all',
					'amd',
					'ang',
					'aud',
					'awg',
					'azn',
					'bam',
					'bbd',
					'bdt',
					'bgn',
					'bif',
					'bmd',
					'bnd',
					'bsd',
					'bwp',
					'byn',
					'bzd',
					'cad',
					'cdf',
					'chf',
					'cny',
					'czk',
					'dkk',
					'dop',
					'dzd',
					'egp',
					'etb',
					'eur',
					'fjd',
					'gbp',
					'gel',
					'gip',
					'gmd',
					'gyd',
					'hkd',
					'htg',
					'huf',
					'idr',
					'ils',
					'inr',
					'isk',
					'jmd',
					'jpy',
					'kes',
					'kgs',
					'khr',
					'kmf',
					'krw',
					'kyd',
					'kzt',
					'lbp',
					'lkr',
					'lrd',
					'lsl',
					'mad',
					'mdl',
					'mga',
					'mkd',
					'mmk',
					'mnt',
					'mop',
					'mro',
					'mvr',
					'mwk',
					'mxn',
					'myr',
					'mzn',
					'nad',
					'ngn',
					'nok',
					'npr',
					'nzd',
					'pgk',
					'php',
					'pkr',
					'pln',
					'qar',
					'ron',
					'rsd',
					'rub',
					'rwf',
					'sar',
					'sbd',
					'scr',
					'sek',
					'sgd',
					'sle',
					'sll',
					'sos',
					'szl',
					'thb',
					'tjs',
					'top',
					'try',
					'ttd',
					'twd',
					'tzs',
					'uah',
					'ugx',
					'uzs',
					'vnd',
					'vuv',
					'wst',
					'xaf',
					'xcd',
					'yer',
					'zar',
					'zmw',
				)
			);
		}
	}
}
