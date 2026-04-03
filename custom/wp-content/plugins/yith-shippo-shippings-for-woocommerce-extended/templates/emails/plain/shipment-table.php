<?php
/**
 * Plain Shipment tracking template Email
 *
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @version 1.0.0
 * @package YITH/Shippo/Templates
 *
 * @var WC_Order $order Order.
 * @var array    $tracking Traking information.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

echo "****************************************************\n\n";

if ( ! empty( $tracking ) ) {
	$old_status       = $tracking['old_status'];
	$current_status   = $tracking['current_status'];
	$tracking_updated = $tracking['tracking_updated'];
	$key              = $tracking['key'];
	$tracking_obj     = $tracking['tracking'];
	$order_shipping   = $tracking['order_shipping'];
	$transaction      = $order_shipping->get_transaction( $key );
	$tracking_info    = $tracking_obj->get_info();
	$tracking_eta     = '';

	if ( ! empty( $tracking_info['eta'] ) ) {
		$tracking_eta = date_i18n( wc_date_format(), strtotime( $tracking_info['eta'] ) );
	}
	$history = $tracking_info['tracking_history'];

	echo "\n";
	echo esc_html_x( 'Tracking code', '', 'yith-shippo-shippings-for-woocommerce' ) . ': ' . esc_html( $transaction['tracking_number'] . ' | ' );
	echo "\n";
	echo esc_html_x( 'Current status', '', 'yith-shippo-shippings-for-woocommerce' ) . ': ' . esc_html( $current_status . ' | ' );
	echo "\n";
	echo esc_html_x( 'Estimated delivery date', '', 'yith-shippo-shippings-for-woocommerce' ) . ': ' . esc_html( $tracking_eta . ' | ' );
	echo "\n";
	echo esc_html_x( 'Track shipment', '', 'yith-shippo-shippings-for-woocommerce' ) . ': ' . esc_attr( $transaction['tracking_url_provider'] );
	echo "\n";

}

echo "****************************************************\n\n";
