<?php
/**
 * General tab options
 *
 * @package YITH\Shippo\Options
 */

defined( 'ABSPATH' ) || exit;

$general_options = array(
	'general' => array(
		'environment'                  => array(
			'id'        => 'yith_shippo_environment',
			'name'      => __( 'Environment', 'yith-shippo-shippings-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'live'    => __( 'Live', 'yith-shippo-shippings-for-woocommerce' ),
				'sandbox' => __( 'Sandbox', 'yith-shippo-shippings-for-woocommerce' ),
			),
			'default'   => 'live',
			'desc'      => __( 'Choose if you want to create and pay real shippings or test without paying postage.', 'yith-shippo-shippings-for-woocommerce' ),
		),
		'connect_button'               => array(
			'id'        => 'yith-shippo-onboarding-button',
			'name'      => __( 'Connect account', 'yith-shippo-shippings-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'custom',
			'desc'      => __( 'Click on the button to connect your Shippo account.', 'yith-shippo-shippings-for-woocommerce' ),
			'action'    => 'yith_shippo_show_onboarding',
			'deps'      => array(
				'id'    => 'yith_shippo_environment',
				'value' => 'live',
				'type'  => 'hide',
			),
		),
		'sandbox_token'                => array(
			'name'      => esc_html_x( 'Shippo Sandbox / Test API Token', 'label of admin option', 'yith-shippo-shippings-for-woocommerce' ),
			// translators: placeholders are html tags.
			'desc'      => sprintf( esc_html_x( 'Enter the Shippo Test API Token. You can find it in your %1$sShippo account%2$s on Settings -> Integrations -> API page.', 'description of admin option, placeholders are html tags', 'yith-shippo-shippings-for-woocommerce' ), '<a href="https://apps.goshippo.com/login" target="_blank">', '</a>' ),
			'id'        => 'yith_shippo_sandbox_token',
			'type'      => 'yith-field',
			'yith-type' => 'text',
			'deps'      => array(
				'id'    => 'yith_shippo_environment',
				'value' => 'sandbox',
				'type'  => 'hide',
			),
		),
	),
);

/**
 * APPLY_FILTERS: yith_shippo_panel_general_options
 *
 * This filter allow to manage the general options panel.
 *
 * @param array $general_options List of options.
 *
 * @return array
 */
return apply_filters( 'yith_shippo_panel_general_options', $general_options );
