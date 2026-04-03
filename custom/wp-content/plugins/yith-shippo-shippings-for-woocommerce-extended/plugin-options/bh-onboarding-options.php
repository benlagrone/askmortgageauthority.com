<?php
/**
 * Options to configure the content of the BH Onboarding
 *
 * @package YITH\Shippo\Options
 */

defined( 'ABSPATH' ) || exit;

$onboarding_tabs = array(
	'slug'               => YITH_SHIPPO_SLUG,
	'enqueue_script'     => array( 'yith-shippo-sender-info', 'yith-shippo-admin' ),
	'enqueue_style'      => array( 'yith-shippo-admin' ),
	'logo'               => YITH_SHIPPO_ASSETS_URL . '/images/bh-onboarding/shippo-whole-logo.svg',
	'plugin-description' => __( 'The best multi-carrier shipping software for e-commerce businesses.', 'yith-shippo-shippings-for-woocommerce' ),
	'claim'              => __( 'Connect your store to Shippo', 'yith-shippo-shippings-for-woocommerce' ),
	'tabs'               => array(
		'main'        => array(
			'title'            => __( 'I already have an account on Shippo', 'yith-shippo-shippings-for-woocommerce' ),
			// translators:Placeholders are HTML tags.
			'description'      => sprintf( __( '%1$sIf you already have an account on Shippo%2$s, enter the info below to connect your store.', 'yith-shippo-shippings-for-woocommerce' ), '<strong>', '</strong>' ),
			'options'          => array(
				'environment'          => array(
					'id'        => 'yith_shippo_environment',
					'title'     => __( 'Environment', 'yith-shippo-shippings-for-woocommerce' ),
					'type'      => 'yith-field',
					'yith-type' => 'radio',
					'options'   => array(
						'live'    => __( 'Live', 'yith-shippo-shippings-for-woocommerce' ),
						'sandbox' => __( 'Sandbox', 'yith-shippo-shippings-for-woocommerce' ),
					),
					'default'   => 'live',
					'desc'      => __( 'Select to create and pay real shippings or to test without the need to pay the postage', 'yith-shippo-shippings-for-woocommerce' ),
				),
				'connect_button'       => array(
					'id'        => 'yith-shippo-onboarding-button',
					'title'     => __( 'Connect account', 'yith-shippo-shippings-for-woocommerce' ),
					'type'      => 'yith-field',
					'yith-type' => 'custom',
					'desc'      => __( 'Click on the button to connect your Shippo account', 'yith-shippo-shippings-for-woocommerce' ),
					'action'    => 'yith_shippo_show_onboarding',
					'deps'      => array(
						'id'    => 'yith_shippo_environment',
						'value' => 'live',
						'type'  => 'hide',
					),
				),
				'sandbox_token'        => array(
					'title'     => esc_html_x( 'Shippo Sandbox / Test API Token', 'label of admin option', 'yith-shippo-shippings-for-woocommerce' ),
					// translators: placeholders are html tags.
					'desc'      => sprintf(
						esc_html_x( 'Enter the Shippo Test API Token. You can find it in your %1$sShippo account%2$s on Settings -> Integrations -> API page.', 'description of admin option, placeholders are html tags', 'yith-shippo-shippings-for-woocommerce' ),
						'<a href="https://apps.goshippo.com/login">',
						'</a>'
					),
					'id'        => 'yith_shippo_sandbox_token',
					'type'      => 'yith-field',
					'yith-type' => 'text',
					'deps'      => array(
						'id'    => 'yith_shippo_environment',
						'value' => 'sandbox',
						'type'  => 'hide',
					),
				),
				'shipping-sender-info' => array(
					'name'             => '',
					'desc'             => '',
					'id'               => 'yith-shippo-sender-info',
					'type'             => 'yith-field',
					'yith-type'        => 'sender-info',
					'yith-display-row' => false,
				),
			),
			'show_save_button' => true,
		),
		'shippo-info' => array(
			'title'            => __( 'Create an account', 'yith-shippo-shippings-for-woocommerce' ),
			'description'      => __( 'Create a free account on Shippo and start shipping with the best delivery options in the business.', 'yith-shippo-shippings-for-woocommerce' ),
			'options'          => array(
				'text' => array(
					'id'               => 'yith_shippo_text',
					'title'            => '',
					'type'             => 'yith-field',
					'yith-type'        => 'bh-onboarding-create-an-account-shippo',
					'yith-display-row' => false,
				),
			),
			'show_save_button' => false,
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
return apply_filters( 'yith_shippo_bh_onboarding_tab', $onboarding_tabs );
