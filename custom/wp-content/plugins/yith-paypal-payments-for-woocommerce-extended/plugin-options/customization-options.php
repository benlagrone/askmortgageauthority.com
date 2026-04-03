<?php
/**
 * The plugin general options array
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH PayPal Payments for WooCommerce
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$global_settings = array(
	array(
		'title' => esc_html_x( 'Global Options', 'Title of setting tab.', 'yith-paypal-payments-for-woocommerce' ),
		'type'  => 'title',
		'desc'  => '',
		'id'    => 'yith_ppwc_customization_extra_options',
	),
	array(
		'id'           => 'yith_ppwc_button_size',
		'title'        => esc_html_x( 'Buttons container width', 'Admin title option', 'yith-paypal-payments-for-woocommerce' ),
		'desc'         => sprintf( esc_html_x( 'Use this value to edit the button size.', 'Admin option', 'yith-paypal-payments-for-woocommerce' ), '<strong>', '</strong>' ),
		'type'         => 'yith-field',
		'yith-type'    => 'dimensions',
		'allow_linked' => false,
		'dimensions'   => array(
			'width' => esc_html_x( 'Width', 'Admin option', 'yith-paypal-payments-for-woocommerce' ),
		),
		'default'      => array(
			'dimensions' => array(
				'width' => 100,
			),
			'unit'       => 'percentage',
		),
	),

	array(
		'id'        => 'yith_ppwc_button_on',
		'title'     => esc_html_x( 'Show buttons on', 'Admin title option', 'yith-paypal-payments-for-woocommerce' ),
		'desc'      => esc_html_x( 'Choose where to show the payment buttons.', 'Admin description option', 'yith-paypal-payments-for-woocommerce' ),
		'type'      => 'yith-field',
		'yith-type' => 'checkbox-array',
		'default'   => array( 'cart', 'checkout' ),
		'options'   => array(
			// translators:placeholders are html tags.
			'cart'     => esc_html_x( 'Cart page', 'Admin option', 'yith-paypal-payments-for-woocommerce' ),
			// translators:placeholders are html tags.
			'checkout' => esc_html_x( 'Checkout', 'Admin option', 'yith-paypal-payments-for-woocommerce' ),
			// translators:placeholders are html tags.
			'product'  => esc_html_x( 'Single product pages', 'Admin option', 'yith-paypal-payments-for-woocommerce' ),
		),
	),

	array(
		'id'        => 'yith_ppwc_gateway_options[fast_checkout]',
		'title'     => esc_html_x( 'Fast checkout', 'Admin title option', 'yith-paypal-payments-for-woocommerce' ),
		'desc'      => esc_html_x( 'In case the payment button is displayed on the product page or in the cart and you enable this option, customers will be able to pay without leaving the product or cart page, and so will skip the checkout page.', 'Admin option', 'yith-paypal-payments-for-woocommerce' ),
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
		'default'   => 'no',
	),

	array(
		'type' => 'sectionend',
		'id'   => 'yith_ppwc_customization_extra_options',
	),
);

$paypal_settings = array(

		array(
			'title' => esc_html_x( 'Paypal Button', 'Title of setting tab.', 'yith-paypal-payments-for-woocommerce' ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'yith_ppwc_button_options',
		),

		array(
			'id'        => 'yith_ppwc_gateway_options[title]',
			'title'     => esc_html_x( 'Title', 'Admin panel option title', 'yith-paypal-payments-for-woocommerce' ),
			'desc'      => esc_html_x( 'Enter a title to identify this payment method during checkout.', 'Admin panel option description', 'yith-paypal-payments-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'text',
			'default'   => esc_html_x( 'Pay with PayPal', 'Default value for PayPal Payment title.', 'yith-paypal-payments-for-woocommerce' ),
		),

		array(
			'id'        => 'yith_ppwc_gateway_options[description]',
			'title'     => esc_html_x( 'Description', 'Admin panel option title', 'yith-paypal-payments-for-woocommerce' ),
			'desc'      => esc_html_x( 'Enter an optional description for this payment method.', 'Admin panel option description', 'yith-paypal-payments-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'text',
			'default'   => esc_html_x( 'Pay safe with PayPal', 'Default value for PayPal Payment description.', 'yith-paypal-payments-for-woocommerce' ),
		),

		array(
			'id'        => 'yith_ppwc_button_shape',
			'title'     => esc_html_x( 'Button shape', 'Admin title option', 'yith-paypal-payments-for-woocommerce' ),
			// translators:placeholders are html tags.
			'desc'      => sprintf( esc_html_x( 'Choose the PayPal button shape style. The recommended shape is %1$srectangular%2$s.', 'Admin option, the placeholder are tags', 'yith-paypal-payments-for-woocommerce' ), '<strong>', '</strong>' ),
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'default'   => 'rect',
			'options'   => array(
				'rect' => esc_html_x( 'Rectangular', 'Admin option', 'yith-paypal-payments-for-woocommerce' ),
				'pill' => esc_html_x( 'Pill', 'Admin option', 'yith-paypal-payments-for-woocommerce' ),
			),
		),

		array(
			'id'        => 'yith_ppwc_button_color',
			'title'     => esc_html_x( 'Button color', 'Admin title option', 'yith-paypal-payments-for-woocommerce' ),
			// translators:placeholders are html tags.
			'desc'      => sprintf( esc_html_x( 'Choose the PayPal button color. The recommended color is %1$sgold%2$s.', 'Admin option, the placeholder are tags', 'yith-paypal-payments-for-woocommerce' ), '<strong>', '</strong>' ),
			'type'      => 'yith-field',
			'yith-type' => 'select-images',
			'default'   => 'gold-rect',
			'options'   => array(
				'gold-rect'   => array(
					'label' => esc_html_x( 'Gold', 'Option: Button color', 'yith-paypal-payments-for-woocommerce' ),
					'image' => YITH_PAYPAL_PAYMENTS_URL . 'assets/images/gold.jpeg',
				),
				'blue-rect'   => array(
					'label' => esc_html_x( 'Blue', 'Option: Button color', 'yith-paypal-payments-for-woocommerce' ),
					'image' => YITH_PAYPAL_PAYMENTS_URL . 'assets/images/blue.jpeg',
				),
				'silver-rect' => array(
					'label' => esc_html_x( 'Silver', 'Option: Button color', 'yith-paypal-payments-for-woocommerce' ),
					'image' => YITH_PAYPAL_PAYMENTS_URL . 'assets/images/silver.jpeg',
				),
				'white-rect'  => array(
					'label' => esc_html_x( 'White', 'Option: Button color', 'yith-paypal-payments-for-woocommerce' ),
					'image' => YITH_PAYPAL_PAYMENTS_URL . 'assets/images/white.jpeg',
				),
				'black-rect'  => array(
					'label' => esc_html_x( 'Black', 'Option: Button color', 'yith-paypal-payments-for-woocommerce' ),
					'image' => YITH_PAYPAL_PAYMENTS_URL . 'assets/images/black.jpeg',
				),
				'gold-pill'   => array(
					'label' => esc_html_x( 'Gold', 'Option: Button color', 'yith-paypal-payments-for-woocommerce' ),
					'image' => YITH_PAYPAL_PAYMENTS_URL . 'assets/images/gold_pill.jpeg',
				),
				'blue-pill'   => array(
					'label' => esc_html_x( 'Blue', 'Option: Button color', 'yith-paypal-payments-for-woocommerce' ),
					'image' => YITH_PAYPAL_PAYMENTS_URL . 'assets/images/blue_pill.jpeg',
				),
				'silver-pill' => array(
					'label' => esc_html_x( 'Silver', 'Option: Button color', 'yith-paypal-payments-for-woocommerce' ),
					'image' => YITH_PAYPAL_PAYMENTS_URL . 'assets/images/silver_pill.jpeg',
				),
				'white-pill'  => array(
					'label' => esc_html_x( 'White', 'Option: Button color', 'yith-paypal-payments-for-woocommerce' ),
					'image' => YITH_PAYPAL_PAYMENTS_URL . 'assets/images/white_pill.jpeg',
				),
				'black-pill'  => array(
					'label' => esc_html_x( 'Black', 'Option: Button color', 'yith-paypal-payments-for-woocommerce' ),
					'image' => YITH_PAYPAL_PAYMENTS_URL . 'assets/images/black_pill.jpeg',
				),
			),

		),

		array(
			'type' => 'sectionend',
			'id'   => 'yith_ppwc_end_button_options',
		),
);

$allsettings = array(
	'customization' => array_merge( $global_settings, $paypal_settings ),
);

return apply_filters(
	'yith_ppwc_customization_settings',
	$allsettings
);
