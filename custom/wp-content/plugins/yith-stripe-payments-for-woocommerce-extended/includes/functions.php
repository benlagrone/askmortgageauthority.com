<?php
/**
 * Utility functions
 *
 * @author  YITH
 * @package YITH\StripePayments\Functions
 * @version 1.0.0
 */

use YITH\StripePayments\Api_Client;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! function_exists( 'yith_stripe_payments_merge_recursive' ) ) {
	/**
	 * Merges a set of arrays into one single result
	 *
	 * Merge works as follows:
	 * 1. First array of the set is considered the destination; all other arrays will be merged into it.
	 * 2. Each other array (source) will be cycled, and system will:
	 *   - Add any source key that is missing from origin
	 *   - Override origin value with the source one, if both of them are scalar
	 *   - Override origin value with result or the recursive call to this function that takes origin and source values, both casted to array.
	 *
	 * @param array ...$arrays Associative arrays to merge together.
	 *
	 * @return array|bool Merge results, or false on failure.
	 */
	function yith_stripe_payments_merge_recursive( ...$arrays ) {
		if ( count( $arrays ) < 1 ) {
			return false;
		}

		if ( count( $arrays ) === 0 ) {
			return $arrays[ 0 ];
		}

		$origin = array_shift( $arrays );

		foreach ( $arrays as $to_merge ) {
			foreach ( $to_merge as $to_merge_key => $to_merge_value ) {
				if ( empty( $origin[ $to_merge_key ] ) ) {
					$origin[ $to_merge_key ] = $to_merge_value;
				} elseif ( is_scalar( $origin[ $to_merge_key ] ) && is_scalar( $to_merge_value ) ) {
					$origin[ $to_merge_key ] = $to_merge_value;
				} else {
					$origin[ $to_merge_key ] = yith_stripe_payments_merge_recursive( (array) $origin[ $to_merge_key ], (array) $to_merge_value );
				}
			}
		}

		return $origin;
	}
}

if ( ! function_exists( 'yith_stripe_payments_get_brand' ) ) {
	/**
	 * Get current brand
	 * Tries to retrieve it from the YITH Newfold Brand Module.
	 * If it cannot be retrieved from there, it falls back to the saved option.
	 *
	 * @return string
	 */
	function yith_stripe_payments_get_brand() {
		$brand_option_name = 'yith_stripe_payments_brand_' . md5( \YITH\StripePayments\Cache_Helper::get_cache_version() );
		$registered_brands = array_keys( Api_Client::get_registered_brands() );

		$brand = function_exists( 'yith_nfbm_get_brand' ) ? yith_nfbm_get_brand() : false;

		if ( ! $brand ) {
			$brand = get_option( $brand_option_name, false );
		}

		if ( ! in_array( $brand, $registered_brands, true ) ) {
			$brand = false;
		}

		update_option( $brand_option_name, $brand );

		return $brand;
	}
}

if ( ! function_exists( 'yith_stripe_payments_get_checkout_details' ) ) {
	/**
	 * Returns an array containing details about current checkout
	 *
	 * @return array An array containing checkout details. It will be formatted as follows:
	 *               [
	 *                  'amount'   => 123.45,
	 *                  'currency' => 'EUR',
	 *               ].
	 */
	function yith_stripe_payments_get_checkout_details() {
		$cart    = WC()->cart;
		$session = WC()->session;

		// retrieves currency and total for current order (or cart).
		$order_id = (int) $session->get( 'order_awaiting_payment' );
		$order    = $order_id ? wc_get_order( $order_id ) : false;
		$currency = $order ? $order->get_currency() : get_woocommerce_currency();
		$amount   = $order ? (float) $order->get_total( 'edit' ) : (float) $cart->get_total( 'edit' );

		return compact( 'currency', 'amount' );
	}
}
