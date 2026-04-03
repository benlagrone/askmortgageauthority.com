<?php
/**
 * Parcel tab configuration
 *
 * @package YITH\Shippo\Options
 */

defined( 'ABSPATH' ) || exit;


$parcel_tab = array(
	'parcel' => array(
		'parcel-options' => array(
			'type'     => 'multi_tab',
			'sub-tabs' => array(
				'parcel-parcel-adjustment' => array(
					'title' => esc_html_x( 'Parcel Adjustments', 'Admin tab title', 'yith-shippo-shippings-for-woocommerce' ),
				),
				'parcel-parcel-boxes'      => array(
					'title' => esc_html_x( 'Parcel Boxes', 'Admin tab title', 'yith-shippo-shippings-for-woocommerce' ),
				),
				'parcel-postage'           => array(
					'title' => esc_html_x( 'Postage Options', 'Admin tab title', 'yith-shippo-shippings-for-woocommerce' ),
				),
			),
		),
	),
);

/**
 * APPLY_FILTERS: yith_shippo_panel_parcel_tab
 *
 * This filter allow to manage the parcel packing subtabs.
 *
 * @param   array  $parcel_tab  List of subtabs.
 *
 * @return array
 */
return apply_filters( 'yith_shippo_panel_parcel_tab', $parcel_tab );
