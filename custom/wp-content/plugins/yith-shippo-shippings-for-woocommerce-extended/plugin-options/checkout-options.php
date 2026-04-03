<?php
/**
 * Extra tab options
 *
 * @package YITH\Shippo\Options
 */

defined( 'ABSPATH' ) || exit;

$extra_options = array(
	'checkout' => array(
		'checkout-validate-shipping-address' => array(
			'id'        => 'yith-shippo-validate-shipping-adress',
			'name'      => __( 'Validate shipping address', 'yith-shippo-shippings-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'desc'      => __( 'Enable to validate shipping address before allowing users to proceed with the order.', 'yith-shippo-shippings-for-woocommerce' ),
			'default'   => 'yes',
		),
		'checkout-require-company-name'      => array(
			'id'        => 'yith-shippo-require-company-name',
			'name'      => __( 'Require company name', 'yith-shippo-shippings-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'desc'      => __( 'Enable to make company name mandatory for users during the checkout process.', 'yith-shippo-shippings-for-woocommerce' ),
			'default'   => 'no',
		),
	),
);

/**
 * APPLY_FILTERS: yith_shippo_panel_extra_options
 *
 * This filter allow to manage the extra options panel.
 *
 * @param   array  $extra_options  List of extra options.
 *
 * @return array
 */
return apply_filters( 'yith_shippo_panel_extra_options', $extra_options );
