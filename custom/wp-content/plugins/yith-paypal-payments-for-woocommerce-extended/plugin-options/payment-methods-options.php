<?php
/**
 * The plugin general options array
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH PayPal Payments for WooCommerce
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$pp_methods = array(
	array(
		'title' => esc_html_x( 'Payment methods', 'Title of setting tab', 'yith-paypal-payments-for-woocommerce' ),
		'type'  => 'title',
		'desc'  => '',
		'id'    => 'yith_ppwc_payment_methods_options',
	),

	array(
		'id'        => 'yith_ppwc_button_funding_sources',
		'title'     => esc_html_x( 'Additional payments buttons for PayPal transactions', 'Admin title option', 'yith-paypal-payments-for-woocommerce' ),
		'desc'      => sprintf(
			esc_html_x(
				'Select the additional payments buttons that will be available in the PayPal wallet by default.%sWhether these are eligible and visible to the user, this will depend on a variety of factors including customer location.',
				'Admin option. Placeholder is an html tag.',
				'yith-paypal-payments-for-woocommerce'
			),
			'<br>'
		),
		'type'      => 'yith-field',
		'yith-type' => 'checkbox-array',
		'default'   => array( 'card' ),
		'options'   => yith_ppwc_funding_sources_list(),
	),

	array(
		'type' => 'sectionend',
		'id'   => 'yith_ppwc_end_payment_methods_options',
	),
);



$cc_cart = array(
	array(
		'title' => esc_html_x( 'Custom Credit Card Options', 'Admin option section title', 'yith-paypal-payments-for-woocommerce' ),
		'type'  => 'title',
		'desc'  => '',
		'id'    => 'yith_ppwc_credit_card_options',
	),
	array(
		'id'        => 'yith_ppwc_cc_gateway_options[enabled]',
		'title'     => esc_html_x( 'Enable credit card payment', 'Admin title option', 'yith-paypal-payments-for-woocommerce' ),
		'desc'      => esc_html_x( 'Enable the custom "white label" credit card option in your checkout as a payment option.', 'Admin description option', 'yith-paypal-payments-for-woocommerce' ),
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'default'   => 'yes',
	),
	array(
		'id'                => 'yith_ppwc_cc_gateway_options[title]',
		'title'             => esc_html_x( 'Title', 'Admin option title of payment method', 'yith-paypal-payments-for-woocommerce' ),
		'desc'              => esc_html_x( 'Enter a title to identify this payment method during checkout.', 'Admin option description', 'yith-paypal-payments-for-woocommerce' ),
		'type'              => 'yith-field',
		'yith-type'         => 'text',
		'default'           => esc_html_x( 'Pay with a Credit or Debit Card', 'Default value of a the payment method title', 'yith-paypal-payments-for-woocommerce' ),
		'custom_attributes' => array(
			'data-deps'       => 'yith_ppwc_cc_gateway_options[enabled]',
			'data-deps_value' => 'yes',
		),
	),
	array(
		'id'                => 'yith_ppwc_cc_gateway_options[description]',
		'title'             => esc_html_x( 'Description', 'Admin title option of payment method description', 'yith-paypal-payments-for-woocommerce' ),
		'desc'              => esc_html_x( 'Enter an optional description for this payment method.', 'Admin description option of payment method description.', 'yith-paypal-payments-for-woocommerce' ),
		'type'              => 'yith-field',
		'yith-type'         => 'text',
		'default'           => esc_html_x( 'Pay safe with a credit or debit card', 'Default value of a the payment method description', 'yith-paypal-payments-for-woocommerce' ),
		'custom_attributes' => array(
			'data-deps'       => 'yith_ppwc_cc_gateway_options[enabled]',
			'data-deps_value' => 'yes',
		),
	),
	array(
		'id'                => 'yith_ppwc_cc_gateway_options[3d_secure_setting]',
		'title'             => esc_html_x( 'Enable 3D Secure', 'Admin title option', 'yith-paypal-payments-for-woocommerce' ),
		'desc'              => esc_html_x(
			'3D Secure enables you to authenticate card holders through card issuers. It reduces the likelihood of fraud when you use supported cards and improves transaction performance. A successful 3D Secure authentication can shift liability for chargebacks due to fraud from you to the card issuer.',
			'Admin description option',
			'yith-paypal-payments-for-woocommerce'
		),
		'type'              => 'yith-field',
		'yith-type'         => 'onoff',
		'default'           => 'yes',
		'custom_attributes' => array(
			'data-deps'       => 'yith_ppwc_cc_gateway_options[enabled]',
			'data-deps_value' => 'yes',
		),
	),
	array(
		'id'                => 'yith_ppwc_cc_gateway_options[3d_secure_liability_shift]',
		'title'             => esc_html_x( '3D Secure liability shift', 'Admin title option', 'yith-paypal-payments-for-woocommerce' ),
		'desc'              => esc_html_x(
			'3D Secure authentication is performed only if the card is enrolled for the service. In scenarios where the 3D Secure authentication hasn\'t been successful, you have the option to complete the payment at your own risk, meaning that you, as the merchant, will be liable in case of a chargeback.',
			'Admin description option',
			'yith-paypal-payments-for-woocommerce'
		),
		'type'              => 'yith-field',
		'yith-type'         => 'checkbox-array',
		'default'           => array( 'possible', 'unknown' ),
		'options'           => array(
			'possible' => sprintf( esc_html_x( 'Liability might shift to the card issuer.', 'Admin option', 'yith-paypal-payments-for-woocommerce' ) ),
			'no'       => sprintf( esc_html_x( 'Liability lies with the merchant.', 'Admin option', 'yith-paypal-payments-for-woocommerce' ) ),
			'unknown'  => sprintf( esc_html_x( 'The authentication system is not available.', 'Admin option', 'yith-paypal-payments-for-woocommerce' ) ),
		),
		'custom_attributes' => array(
			'data-deps'       => 'yith_ppwc_cc_gateway_options[enabled],yith_ppwc_cc_gateway_options\\[3d_secure_setting\\]',
			'data-deps_value' => 'yes,yes',
		),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'yith_ppwc_end_credit_card',
	),
);

$remove_cc_settings = apply_filters( 'yith_paypal_payments_remove_cc_settings', false );
return array(
	'payment-methods' => apply_filters(
		'yith_ppwc_payment_methods_settings',
		$remove_cc_settings ? $pp_methods : array_merge( $pp_methods, $cc_cart )
	),
);
