<?php
/**
 * General settings
 *
 * @author  YITH
 * @package YITH\StripePayments\Options
 * @version 1.0.0
 */

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

use \YITH\StripePayments\Admin\Main as Admin;
use \YITH\StripePayments\Gateways;

$env_option_name    = Admin::get_env_option_name();
$gateway            = Gateways::get_instance()->get_gateway();
$gateway_option_key = $gateway ? $gateway->get_option_key() : '';
$test_environment   = defined( 'WP_ENV' ) && 'development' === \WP_ENV;

/**
 * APPLY_FILTERS: yith_stripe_payments_general_settings
 *
 * Filters the options available in the General settings tab.
 *
 * @param array $options Array with options
 *
 * @return array
 */
return apply_filters(
	'yith_stripe_payments_general_settings',
	array(
		'general' => array(

			'general-options' => array(
				'title' => _x( 'General', '[ADMIN] General settings page', 'yith-stripe-payments-for-woocommerce' ),
				'type'  => 'title',
				'id'    => 'yith_stripe_payments_general',
				'desc'  => '',
			),

			'general-options-enable' => array(
				'id'        => 'yith_stripe_payments_enabled',
				'name'      => __( 'Enable/Disable', 'yith-stripe-payments-for-woocommerce' ),
				'type'      => 'yith-field',
				'yith-type' => 'onoff',
				'desc'      => __( 'Use Stripe payments on this site.', 'yith-stripe-payments-for-woocommerce' ),
				'default'   => 'yes',
			),

			'general-options-environment' => array(
				'id'        => $env_option_name,
				'name'      => __( 'Environment', 'yith-stripe-payments-for-woocommerce' ),
				'type'      => 'yith-field',
				'yith-type' => 'radio',
				'options'   => array(
					'live' => _x( 'Live', '[ADMIN] Gateway environment options', 'yith-stripe-payments-for-woocommerce' ),
					'test' => _x( 'Sandbox', '[ADMIN] Gateway environment options', 'yith-stripe-payments-for-woocommerce' ),
				),
				'class'     => $test_environment ? 'disabled' : '',
				'desc'      => (
					__( 'Choose if you want to use live transactions or test transactions for your site. Sandbox mode will allows you to test your integration by making simulated purchases.', 'yith-stripe-payments-for-woocommerce' ) .
					( $test_environment ? '<br/>' . __( '<b>Note</b>: this option is forced to Test by default as plugin is currently executed on a staging installation.', 'yith-stripe-payments-for-woocommerce' ) : '' )
				),
				'default' => $test_environment ? 'test' : 'live',
			),

			'general-options-onboarding' => array(
				'type' => 'yith_stripe_payments_onboarding',
			),

			'general-options-end' => array(
				'type' => 'sectionend',
				'id'   => 'yith_stripe_payments_general',
			),

			'payment-options' => array(
				'title' => _x( 'Payment', '[ADMIN] General settings page', 'yith-stripe-payments-for-woocommerce' ),
				'type'  => 'title',
				'id'    => 'yith_stripe_payments_details',
				'desc'  => '',
			),

			'payment-options-capture-method' => array(
				'id'        => "{$gateway_option_key}[capture]",
				'name'      => __( 'Capture method', 'yith-stripe-payments-for-woocommerce' ),
				'type'      => 'yith-field',
				'yith-type' => 'radio',
				'options'   => array(
					'automatic' => _x( 'Capture immediately', '[ADMIN] Gateway environment options', 'yith-stripe-payments-for-woocommerce' ),
					'manual'    => _x( 'Authorize only (capture later)', '[ADMIN] Gateway environment options', 'yith-stripe-payments-for-woocommerce' ),
				),
				'desc'      => __( 'Select your preference for payment capture; automatic capture upon issuance or manual capture later through the Stripe Dashboard.', 'yith-stripe-payments-for-woocommerce' ),
				'default'   => 'automatic',
			),

			'payment-options-end' => array(
				'type' => 'sectionend',
				'id'   => 'yith_stripe_payments_details',
			),
		),
	)
);
