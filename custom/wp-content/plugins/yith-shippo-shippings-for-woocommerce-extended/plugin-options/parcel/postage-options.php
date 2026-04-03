<?php
/**
 * Postage options
 *
 * @package YITH\Shippo\Options
 */

defined( 'ABSPATH' ) || exit;


$desc = sprintf(// translators: the placeholders are html tags.
	_x(
		'Enter the default info to use in customs declarations as a description of the contents of your shipments. %1$sNote%2$s: you can override this default info for each order.',
		'yith-shippo-shippings-for-woocommerce'
	),
	'<br><strong>',
	'</strong>'
);

$desc2 = sprintf(// translators: the placeholders are html tags.
	_x(
		'%1$sTariff number%2$s: it is required by some carriers for international shipments. You can find your tariff number %3$shere%4$s.',
		'description of tariff field in the parcel postage tab.',
		'yith-shippo-shippings-for-woocommerce'
	),
	'<br><strong>',
	'</strong>',
	'<a href="https://hts.usitc.gov" target="_blank">',
	'</a>'
);

$parcel_postage_options = array(
	'parcel-postage' => array(
		'section_postage_settings'           => array(
			'name' => ' ',
			'type' => 'title',
			'desc' => esc_html_x( 'Set the default info about the content of the shipments', 'label of admin option on parcel postage tab', 'yith-shippo-shippings-for-woocommerce' ),
			'id'   => 'yith-shippo_postage_settings',
		),
		'default_postage_options'            => array(
			'name' => ' ',
			'desc'      => $desc . $desc2,
			'id'        => 'yith_shippo_default_postage_options',
			'type'      => 'yith-field',
			'yith-type' => 'postage-options',
		),
		'section_end_postage_settings'       => array(
			'type' => 'sectionend',
			'id'   => 'yith-shippo_postage_settings_ends',
		),
		'section_postage_order_settings'     => array(
			'name' => esc_html_x( 'Postage options for single order', 'title of section tab postage options', 'yith-shippo-shippings-for-woocommerce' ),
			'type' => 'title',
			'id'   => 'yith-shippo_postage_order_settings',
		),
		'options_to_request_shipping_label'  => array(
			'name'      => esc_html_x( 'Require a shipment creation', 'label of admin option tab postage options', 'yith-shippo-shippings-for-woocommerce' ),
			'desc'      => esc_html_x( 'Set if the admin needs to create the shipment before purchasing the postage and the shipping label.', 'description of admin option tab postage options', 'yith-shippo-shippings-for-woocommerce' ),
			'id'        => 'yith_shippo_options_to_request_shipping_label',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'yes',
		),
		'section_end_postage_order_settings' => array(
			'type' => 'sectionend',
			'id'   => 'yith-shippo_postage_order_settings_ends',
		),
	),
);

/**
 * APPLY_FILTERS: yith_shippo_panel_postage_options
 *
 * This filter allow to manage the options on the "Postage options" tab.
 *
 * @param array $postage_options List of options.
 *
 * @return array
 */
return apply_filters( 'yith_shippo_panel_postage_options', $parcel_postage_options );
