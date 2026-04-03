<?php
/**
 * This file contain the shipping rates options
 *
 * @package YITH\Shippo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * APPLY_FILTERS:  yith_shippo_shipping_rates_options
 *
 * This filter allow to add, remove or change the shipping rates options.
 *
 * @param array $shipping_rates_options
 *
 * @retrun array
 */
return apply_filters(
	'yith_shippo_shipping_rates_options',
	array(
		'shipping-rates' => array(
			'shipping-rates-validate-products'           => array(
				'name'             => __( 'Validate products weight and dimensions', 'yith-shippo-shippings-for-woocommerce' ),
				'type'             => 'yith-field',
				'yith-type'        => 'html',
				'yith-display-row' => true,
				'html'             => sprintf(
					'<span class="yith-plugin-fw__button--primary yith-plugin-fw__button--with-icon yith-shippo-validate-products-weights-dimension"><i class="yith-icon yith-icon-update"></i>%s</span>',
					esc_html__( 'Validate products', 'yith-shippo-shippings-for-woocommerce' )
				),
				// translators: Description of 'Validate products weight and dimensions' option. Placeholders are html tags.
				'desc'             => sprintf( __( 'Products must have weight and dimensions set for live shipping rates to work. %s Click on the button to check if all your products are properly configured.', 'yith-shippo-shippings-for-woocommerce' ), '<br>' ),
			),
			'shipping-rates-zones'                       => array(
				'id'        => 'yith-shippo-enable-rates-in-zone',
				'type'      => 'yith-field',
				'yith-type' => 'onoff',
				'name'      => __( 'Manage shipping rates according to shipping zones', 'yith-shippo-shippings-for-woocommerce' ),
				'desc'      => sprintf( /* translators: the placeholder are html tag */
					__(
						'Enable to set for which destinations the plugin\'s shipping rates will be displayed.%1$sNote:%2$s if you enable this option, you need to associate the plugin features in your Shipping Zones or no shipping rates will be displayed.%3$sRead more about %4$s how to use Shipping Zones with Shippo >%5$s.',
						'yith-shippo-shippings-for-woocommerce'
					),
					'<br/><strong>',
					'</strong>',
					'<br/>',
					'<a href="https://www.bluehost.com/help/article/yith-shippo-shippings-for-woocommerce#manage-rates" target="_blank">',
					'</a>'
				),
				'default'   => 'off',
			),
			'shipping-rates-cart-checkout'               => array(
				'id'        => 'yith-shippo-show-live-shipping-rates-cart-checkout',
				'name'      => __( 'Show live shipping rates on Cart and Checkout pages', 'yith-shippo-shippings-for-woocommerce' ),
				'type'      => 'yith-field',
				'yith-type' => 'onoff',
				'desc'      => __( 'Enable to show the live shipping rates on the Cart and Checkout pages.', 'yith-shippo-shippings-for-woocommerce' ),
				'default'   => 'yes',
			),
			'shipping-rates-calculate-shipping-rates-in' => array(
				'id'        => 'yith-shippo-calculate-shipping-rates-in',
				'name'      => __( 'Calculate shipping rates on', 'yith-shippo-shippings-for-woocommerce' ),
				'type'      => 'yith-field',
				'yith-type' => 'select',
				'options'   => array(
					'cart_checkout' => __( 'Cart and Checkout', 'yith-shippo-shippings-for-woocommerce' ),
					'checkout'      => __( 'Checkout', 'yith-shippo-shippings-for-woocommerce' ),
				),
				'class'     => 'wc-enhanced-select',
				'desc'      => __( 'Choose on which pages to quote shipping rates.', 'yith-shippo-shippings-for-woocommerce' ),
				'default'   => 'cart_checkout',
				'deps'      => array(
					'id'    => 'yith-shippo-show-live-shipping-rates-cart-checkout',
					'value' => 'yes',
					'type'  => 'fadeIn',
				),
			),
			'shipping-rates-require-insurance'           => array(
				'id'        => 'yith-shippo-require-insurance',
				'name'      => __( 'Require insurance', 'yith-shippo-shippings-for-woocommerce' ),
				'type'      => 'yith-field',
				'yith-type' => 'onoff',
				'desc'      => __( 'Enable to show only shipping methods with an insurance included.', 'yith-shippo-shippings-for-woocommerce' ),
				'default'   => 'no',
				'deps'      => array(
					'id'    => 'yith-shippo-show-live-shipping-rates-cart-checkout',
					'value' => 'yes',
					'type'  => 'fadeIn',
				),
			),
			'shipping-rates-require-signature'           => array(
				'id'        => 'yith-shippo-require-signature',
				'name'      => __( 'Require signature', 'yith-shippo-shippings-for-woocommerce' ),
				'type'      => 'yith-field',
				'yith-type' => 'onoff',
				'desc'      => __( 'Enable to show only shipping methods that support signature service.', 'yith-shippo-shippings-for-woocommerce' ),
				'default'   => 'no',
				'deps'      => array(
					'id'    => 'yith-shippo-show-live-shipping-rates-cart-checkout',
					'value' => 'yes',
					'type'  => 'fadeIn',
				),
			),
			'shipping-rates-limit-rate'                  => array(
				'id'        => 'yith-shippo-enable-limit-rate',
				'name'      => __( 'Limit rates to show', 'yith-shippo-shippings-for-woocommerce' ),
				'type'      => 'yith-field',
				'yith-type' => 'onoff',
				'desc'      => __( 'Enable to limit the number of rates to display on the Cart and Checkout pages.', 'yith-shippo-shippings-for-woocommerce' ),
				'default'   => 'no',
				'deps'      => array(
					'id'    => 'yith-shippo-show-live-shipping-rates-cart-checkout',
					'value' => 'yes',
					'type'  => 'fadeIn',
				),
			),
			'shipping-rates-limit'                       => array(
				'id'        => 'yith-shippo-limit',
				'name'      => __( 'Max rates to show', 'yith-shippo-shippings-for-woocommerce' ),
				'type'      => 'yith-field',
				'yith-type' => 'number',
				'desc'      => __( 'Set the maximum number of shipping rates to show.', 'yith-shippo-shippings-for-woocommerce' ),
				'default'   => '3',
				'min'       => 0,
				'step'      => 1,
				'deps'      => array(
					'id'    => 'yith-shippo-enable-limit-rate',
					'value' => 'yes',
					'type'  => 'fadeIn',
				),
			),
			'shipping-rates-limit-cost'                  => array(
				'id'        => 'yith-shippo-enable-limit-cost',
				'name'      => __( 'Filter rates according to their cost', 'yith-shippo-shippings-for-woocommerce' ),
				'type'      => 'yith-field',
				'yith-type' => 'onoff',
				'desc'      => __( 'Enable to show/hide rates based on their cost.', 'yith-shippo-shippings-for-woocommerce' ),
				'default'   => 'no',
				'deps'      => array(
					'id'    => 'yith-shippo-show-live-shipping-rates-cart-checkout',
					'value' => 'yes',
					'type'  => 'fadeIn',
				),
			),
			'shipping-rates-min-max-cost'                => array(
				'id'        => 'yith-shippo-min-max-cost',
				'type'      => 'yith-field',
				'yith-type' => 'inline-fields',
				'name'      => __( 'Show rates with a', 'yith-shippo-shippings-for-woocommerce' ),
				'fields'    => array(
					'min_html' => array(
						'type' => 'html',
						'html' => sprintf( /* translators: %s is the currency symbol */
							__(
								'minimum cost of %s',
								'yith-shippo-shippings-for-woocommerce'
							),
							get_woocommerce_currency_symbol()
						),
					),
					'min'      => array(
						'type' => 'number',
						'min'  => 0,
						'step' => 0.01,
					),
					'max_html' => array(
						'type' => 'html',
						'html' => sprintf( /* translators: %s is the currency symbol */
							__(
								'and a maximum cost of %s',
								'yith-shippo-shippings-for-woocommerce'
							),
							get_woocommerce_currency_symbol()
						),
					),
					'max'      => array(
						'type' => 'number',
						'min'  => 0,
						'step' => 0.01,
					),
				),
				'deps'      => array(
					'id'    => 'yith-shippo-enable-limit-cost',
					'value' => 'yes',
					'type'  => 'fadeIn',
				),
				'desc'      => __( 'Set the minimum and the maximum cost of rates you want to show. Leave empty if you want to set only one value.', 'yith-shippo-shippings-for-woocommerce' ),
			),
			'shipping-rates-show-delivery-time'          => array(
				'id'        => 'yith-shippo-show-delivery-time',
				'name'      => __( 'Show delivery time', 'yith-shippo-shippings-for-woocommerce' ),
				'type'      => 'yith-field',
				'yith-type' => 'onoff',
				'desc'      => __( 'Enable to show the estimated delivery time in the shipping method section.', 'yith-shippo-shippings-for-woocommerce' ),
				'default'   => 'no',
				'deps'      => array(
					'id'    => 'yith-shippo-show-live-shipping-rates-cart-checkout',
					'value' => 'yes',
					'type'  => 'fadeIn',
				),
			),
		),
	)
);
