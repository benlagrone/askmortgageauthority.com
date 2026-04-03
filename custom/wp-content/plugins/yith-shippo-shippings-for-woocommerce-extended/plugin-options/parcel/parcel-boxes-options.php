<?php
/**
 * Parcel boxes options
 *
 * @package YITH\Shippo\Options
 */

defined( 'ABSPATH' ) || exit;

$parcel_boxes_options = array(
	'parcel-parcel-boxes' => array(
		'section_parcel_boxes_settings'     => array(
			'name' => esc_html_x( 'Parcel Boxes', 'title of tab section', 'yith-shippo-shippings-for-woocommerce' ),
			'type' => 'title',
			'id'   => 'yith-shippo_parcel_boxes_settings',
			'desc' =>  nl2br( esc_html_x( "Create a table with the boxes that are used to ship your items.\n Specify max weight that boxes can carry, outer dimensions, weight, and inner padding of each box to get a more accurate shipping rates quote.", 'Admin tab description', 'yith-shippo-shippings-for-woocommerce' ) ),
		),
		'parcel_list'                       => array(
			'name'             => '',
			'desc'             => '',
			'id'               => 'yith_shippo_parcel_list',
			'type'             => 'yith-field',
			'yith-type'        => 'parcel-list',
			'yith-display-row' => false,
		),
		'section_end_parcel_boxes_settings' => array(
			'type' => 'sectionend',
			'id'   => 'yith-shippo_parcel_boxes_settings_ends',
		),
		'section_extra_settings'            => array(
			'name' => esc_html_x( 'Extra Options', 'title of tab section', 'yith-shippo-shippings-for-woocommerce' ),
			'type' => 'title',
			'id'   => 'yith-shippo_extra_settings',
		),
		'combine_boxes'                     => array(
			'name'      => esc_html_x( 'Combine boxes in a single package', 'label of admin option', 'yith-shippo-shippings-for-woocommerce' ),
			'desc'      => esc_html_x(
				'Enable to ship the content of an order as a single box, which can help to save on shipping fees.
The plugin will use the total dimensions and weights to send a single shipping quote request.',
				'description of admin option',
				'yith-shippo-shippings-for-woocommerce'
			),
			'id'        => 'yith_shippo_combine_boxes',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'yes',
		),
		'cube_dimensions'                   => array(
			'name'      => esc_html_x( 'Use cube dimensions', 'label of admin option', 'yith-shippo-shippings-for-woocommerce' ),
			'desc'      => esc_html_x(
				'Enable to use only cube boxes for your shippings. Regular sizes boxes are usually less expensive to ship.',
				'description of admin option',
				'yith-shippo-shippings-for-woocommerce'
			),
			'id'        => 'yith_shippo_cube_dimensions',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'no',
			'deps'      => array(
				'id'    => 'yith_shippo_combine_boxes',
				'value' => 'yes',
			),
		),
		'shipping_label_format'             => array(
			'name'      => esc_html_x( 'Shipping Label format', 'label of admin option', 'yith-shippo-shippings-for-woocommerce' ),
			'desc'      => esc_html_x( 'Choose the default format and size for the shipping labels.', 'description of admin option', 'yith-shippo-shippings-for-woocommerce' ),
			'id'        => 'yith_shippo_shipping_label_format',
			'type'      => 'yith-field',
			'yith-type' => 'select',
			'default'   => 'PDF',
			'options'   => yith_shippo_get_shipping_label_format(),
			'class'     => 'wc-enhanced-select',
		),
		'section_end_extra_settings'        => array(
			'type' => 'sectionend',
			'id'   => 'yith-shippo_extra_settings_ends',
		),
	),
);

/**
 * APPLY_FILTERS: yith_shippo_panel_parcel_boxes_options
 *
 * This filter allow to manage the options on the "Parcel Boxes" tab.
 *
 * @param array $parcel_boxes_options List of options.
 *
 * @return array
 */
return apply_filters( 'yith_shippo_panel_parcel_boxes_options', $parcel_boxes_options );
