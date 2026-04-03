<?php
/**
 * Shipping tracking options
 *
 * @package YITH\Shippo\Options
 */

$options = array(
	'shipping-tracking' => array(
		'enable_autotracking'           => array(
			'name'      => esc_html_x( 'Enable Auto Tracking', 'label of admin option on shipping tracking tab', 'yith-shippo-shippings-for-woocommerce' ),
			'desc'      => esc_html_x( 'Enable to automatically track shipping updates from Shippo and send notices to your customers about the current status of their shipments.', 'description of admin option on shipping tracking', 'yith-shippo-shippings-for-woocommerce' ),
			'id'        => 'yith_shippo_enable_autotracking',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'yes',
		),
		'webhook_address'               => array(
			'name'      => esc_html_x( 'Configure webhooks', 'label of admin option on shipping tracking tab', 'yith-shippo-shippings-for-woocommerce' ),
			'desc'      => sprintf( // translators: placeholders are html tags.
				esc_html_x(
					'To enable the Auto Tracking feature, copy this link and paste it into your %1$sShippo Account%2$s on Settings > API > Webhooks.',
					'label of admin option on shipping tracking, placeholders are html tags',
					'yith-shippo-shippings-for-woocommerce'
				),
				'<a href="https://apps.goshippo.com/login">',
				'</a>'
			),
			'id'        => 'yith_shippo_webhook_address',
			'type'      => 'yith-field',
			'yith-type' => 'copy-to-clipboard',
			'readonly'  => true,
			'default'   => YITH_Shippo_Webhook::get_webhook_url(),
			'deps'      => array(
				'id'    => 'yith_shippo_enable_autotracking',
				'value' => 'yes',
			),
		),
		'customer_notify'               => array(
			'name'                 => esc_html_x( 'Tracking status updates to notify customers', 'label of admin option on shipping tracking tab', 'yith-shippo-shippings-for-woocommerce' ),
			'desc'                 => esc_html_x( 'Send an email with a tracking status update to customers when shipment status changes to one of the selected values.', 'label of admin option on shipping tracking', 'yith-shippo-shippings-for-woocommerce' ),
			'id'                   => 'yith_shippo_customer_notify',
			'type'                 => 'yith-field',
			'yith-type'            => 'select-buttons',
			'multiple'             => true,
			'options'              => array(
				'PRE_TRANSIT' => esc_html_x( 'Shipping label created', 'option content on shipping tracking tab', 'yith-shippo-shippings-for-woocommerce' ),
				'TRANSIT'     => esc_html_x( 'In transit', 'option content on shipping tracking tab', 'yith-shippo-shippings-for-woocommerce' ),
				'DELIVERED'   => esc_html_x( 'Delivered', 'option content on shipping tracking tab', 'yith-shippo-shippings-for-woocommerce' ),
				'RETURNED'    => esc_html_x( 'Returned to Sender', 'option content on shipping tracking tab', 'yith-shippo-shippings-for-woocommerce' ),
				'FAILURE'     => esc_html_x( 'Exception', 'option content on shipping tracking tab', 'yith-shippo-shippings-for-woocommerce' ),
				'UNKNOWN'     => esc_html_x( 'Notice', 'option content on shipping tracking tab', 'yith-shippo-shippings-for-woocommerce' ),
			),
			'add_all_button_label' => esc_html_x( 'Select all', 'label of a button to select all the options', 'yith-shippo-shippings-for-woocommerce' ),
		),
		'complete_order'                => array(
			'name'                 => esc_html_x( 'Complete orders with this tracking status', 'label of admin option on shipping tracking tab', 'yith-shippo-shippings-for-woocommerce' ),
			'desc'                 => esc_html_x( 'Automatically set the order as “Completed” when shipment status changes to one of the selected values.', 'label of admin option on shipping tracking', 'yith-shippo-shippings-for-woocommerce' ),
			'id'                   => 'yith_shippo_complete_order',
			'type'                 => 'yith-field',
			'yith-type'            => 'select-buttons',
			'multiple'             => true,
			'options'              => array(
				'PRE_TRANSIT' => esc_html_x( 'Shipping label created', 'option content on shipping tracking tab', 'yith-shippo-shippings-for-woocommerce' ),
				'TRANSIT'     => esc_html_x( 'In transit', 'option content on shipping tracking tab', 'yith-shippo-shippings-for-woocommerce' ),
				'DELIVERED'   => esc_html_x( 'Delivered', 'option content on shipping tracking tab', 'yith-shippo-shippings-for-woocommerce' ),
				'RETURNED'    => esc_html_x( 'Returned to Sender', 'option content on shipping tracking tab', 'yith-shippo-shippings-for-woocommerce' ),
				'FAILURE'     => esc_html_x( 'Exception', 'option content on shipping tracking tab', 'yith-shippo-shippings-for-woocommerce' ),
				'UNKNOWN'     => esc_html_x( 'Notice', 'option content on shipping tracking tab', 'yith-shippo-shippings-for-woocommerce' ),
			),
			'add_all_button_label' => esc_html_x( 'Select all', 'label of a button to select all the options', 'yith-shippo-shippings-for-woocommerce' ),
		),
	),
);

return $options;
