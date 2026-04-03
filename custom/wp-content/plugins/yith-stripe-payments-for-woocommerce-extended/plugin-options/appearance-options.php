<?php
/**
 * PaymentElement appearance settings
 *
 * @author  YITH
 * @package YITH\StripePayments\Options
 * @version 1.0.0
 */

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

use \YITH\StripePayments\Gateways;

$gateway            = Gateways::get_instance()->get_gateway();
$gateway_option_key = $gateway ? $gateway->get_option_key() : '';

/**
 * APPLY_FILTERS: yith_stripe_payments_appearance_settings
 *
 * Filters the options available in the Appearance settings tab.
 *
 * @param array $options Array with options
 *
 * @return array
 */
return apply_filters(
	'yith_stripe_payments_appearance_settings',
	array(
		'appearance' => array(

			'customization-options' => array(
				'title' => _x( 'Customization', '[ADMIN] General settings page', 'yith-stripe-payments-for-woocommerce' ),
				'type'  => 'title',
				'id'    => 'yith_stripe_payments_customization',
				'desc'  => '',
			),

			'customization-options-title' => array(
				'id'        => "{$gateway_option_key}[title]",
				'name'      => __( 'Gateway title', 'yith-stripe-payments-for-woocommerce' ),
				'type'      => 'yith-field',
				'yith-type' => 'text',
				'desc'      => __( 'Add a title for this payment gateway that the user will see during checkout.', 'yith-stripe-payments-for-woocommerce' ),
				'default'   => _x( 'Stripe Payments', '[ADMIN] Default gateway title', 'yith-stripe-payments-for-woocommerce' ),
			),

			'customization-options-description' => array(
				'id'        => "{$gateway_option_key}[description]",
				'name'      => __( 'Gateway description', 'yith-stripe-payments-for-woocommerce' ),
				'type'      => 'yith-field',
				'yith-type' => 'text',
				'desc'      => __( "Add an optional description for this payment gateway that the user will see in checkout. Leave this field blank if you don't want to show a description.", 'yith-stripe-payments-for-woocommerce' ),
				'default'   => _x( 'Use your Credit Card, or choose one of our selected partners to process your payment', '[ADMIN] Default gateway description', 'yith-stripe-payments-for-woocommerce' ),
			),

			'customization-options-end' => array(
				'type' => 'sectionend',
				'id'   => 'yith_stripe_payments_customization',
			),

			'layout-options' => array(
				'title' => _x( 'Layout', '[ADMIN] Element settings page', 'yith-stripe-payments-for-woocommerce' ),
				'type'  => 'title',
				'id'    => 'yith_stripe_payments_element',
				'desc'  => '',
			),

			'layout' => array(
				'id'        => "{$gateway_option_key}[layout]",
				'title'     => _x( 'Layout', '[ADMIN] Element settings page', 'yith-stripe-payments-for-woocommerce' ),
				'type'      => 'yith-field',
				'yith-type' => 'radio',
				'options'   => array(
					'tabs'      => _x( 'Tabs', '[ADMIN] Element layout options', 'yith-stripe-payments-for-woocommerce' ),
					'accordion' => _x( 'Accordion', '[ADMIN] Element layout options', 'yith-stripe-payments-for-woocommerce' ),
				),
				'desc'      => __( 'Select the layout for the Stripe Payment Element to use on the checkout page.', 'yith-stripe-payments-for-woocommerce' ),
				'default'   => 'tabs',
			),

			'accordion-collapsed' => array(
				'id'        => "{$gateway_option_key}[accordion_collapsed]",
				'title'     => _x( 'Collapsed by default', '[ADMIN] Element settings page', 'yith-stripe-payments-for-woocommerce' ),
				'type'      => 'yith-field',
				'yith-type' => 'onoff',
				'desc'      => __( 'Choose if you want your accordion elements collapsed by default.', 'yith-stripe-payments-for-woocommerce' ),
				'default'   => 'yes',
				'deps'      => array(
					'id'        => "{$gateway_option_key}\[layout\]",
					'target-id' => "{$gateway_option_key}\[accordion_collapsed\]",
					'value'     => 'accordion',
					'type'      => 'hide',
				),
			),

			'accordion-radios' => array(
				'id'        => "{$gateway_option_key}[accordion_radios]",
				'title'     => _x( 'Show radios', '[ADMIN] Element settings page', 'yith-stripe-payments-for-woocommerce' ),
				'type'      => 'yith-field',
				'yith-type' => 'onoff',
				'desc'      => __( 'Add radio buttons next to the logo of each payment method to visually indicate the current selection.', 'yith-stripe-payments-for-woocommerce' ),
				'default'   => 'no',
				'deps'      => array(
					'id'        => "{$gateway_option_key}\[layout\]",
					'target-id' => "{$gateway_option_key}\[accordion_radios\]",
					'value'     => 'accordion',
					'type'      => 'hide',
				),
			),

			'accordion-spaced' => array(
				'id'        => "{$gateway_option_key}[accordion_spaced]",
				'title'     => _x( 'Spaced accordion', '[ADMIN] Element settings page', 'yith-stripe-payments-for-woocommerce' ),
				'type'      => 'yith-field',
				'yith-type' => 'onoff',
				'desc'      => __( 'Add space in between each payment method within the accordion.', 'yith-stripe-payments-for-woocommerce' ),
				'default'   => 'no',
				'deps'      => array(
					'id'        => "{$gateway_option_key}\[layout\]",
					'target-id' => "{$gateway_option_key}\[accordion_spaced\]",
					'value'     => 'accordion',
					'type'      => 'hide',
				),
			),

			'layout-options-end' => array(
				'type' => 'sectionend',
				'id'   => 'yith_stripe_payments_element',
			),

			'appearance-options' => array(
				'title' => _x( 'Appearance', '[ADMIN] Element settings page', 'yith-stripe-payments-for-woocommerce' ),
				'type'  => 'title',
				'id'    => 'yith_stripe_payments_element',
				'desc'  => '',
			),

			'theme' => array(
				'id'        => "{$gateway_option_key}[theme]",
				'title'     => _x( 'Theme', '[ADMIN] Element settings page', 'yith-stripe-payments-for-woocommerce' ),
				'type'      => 'yith-field',
				'yith-type' => 'select-images',
				'options'   => array(
					'stripe'  => array(
						'label' => _x( 'Stripe', '[ADMIN] Element settings page', 'yith-stripe-payments-for-woocommerce' ),
						'image' => YITH_STRIPE_PAYMENTS_URL . 'assets/images/themes/stripe.png',
					),
					'night'   => array(
						'label' => _x( 'Night', '[ADMIN] Element settings page', 'yith-stripe-payments-for-woocommerce' ),
						'image' => YITH_STRIPE_PAYMENTS_URL . 'assets/images/themes/night.png',
					),
					'flat'    => array(
						'label' => _x( 'Flat', '[ADMIN] Element settings page', 'yith-stripe-payments-for-woocommerce' ),
						'image' => YITH_STRIPE_PAYMENTS_URL . 'assets/images/themes/flat.png',
					),
				),
				'desc'      => __( 'Select a theme to apply to the Stripe Payment Element.', 'yith-stripe-payments-for-woocommerce' ),
				'default'   => 'stripe',
			),

			'colors' => array(
				'id'           => "{$gateway_option_key}[colors]",
				'title'        => _x( 'Colors', '[ADMIN] Element settings page', 'yith-stripe-payments-for-woocommerce' ),
				'type'         => 'yith-field',
				'yith-type'    => 'multi-colorpicker',
				'colorpickers' => array(
					array(
						'id'      => 'primary',
						'name'    => _x( 'Primary', '[ADMIN] Element settings page', 'yith-stripe-payments-for-woocommerce' ),
						'default' => '#0570de',
					),
					array(
						'id'      => 'background',
						'name'    => _x( 'Background', '[ADMIN] Element settings page', 'yith-stripe-payments-for-woocommerce' ),
						'default' => '#ffffff',
					),
					array(
						'id'      => 'text',
						'name'    => _x( 'Text', '[ADMIN] Element settings page', 'yith-stripe-payments-for-woocommerce' ),
						'default' => '#30313d',
					),
				),
				'desc'      => __( 'Customize the colors of the Stripe Payment Element for a personalized look.', 'yith-stripe-payments-for-woocommerce' ),
			),

			'appearance-options-end' => array(
				'type' => 'sectionend',
				'id'   => 'yith_stripe_payments_element',
			),

		),
	)
);
