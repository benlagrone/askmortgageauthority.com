<?php
/**
 * Parcel adjustment options
 *
 * @package YITH\Shippo\Options
 */

defined( 'ABSPATH' ) || exit;

$weight_unit                    = get_option( 'woocommerce_weight_unit' );
$dimension_unit                 = get_option( 'woocommerce_dimension_unit' );
$default_parcel_box_dimentsions = yith_shippo_get_default_parcel_dimension();

$parcel_adjustment_options = array(
	'parcel-parcel-adjustment' => array(

		'saved_unit_dimension'                   => array(
			'name'      => '',
			'type'      => 'yith-field',
			'yith-type' => 'hidden',
			'id'        => 'yith_shippo_saved_unit_dimension',
			'default'   => $dimension_unit,
		),

		'saved_unit_weight'                      => array(
			'name'      => '',
			'type'      => 'yith-field',
			'yith-type' => 'hidden',
			'id'        => 'yith_shippo_saved_unit_weight',
			'default'   => $weight_unit,
		),


		'min_parcel_dimension'                   => array(
			'name'      => esc_html_x( 'Set the minimum weight and dimensions of the parcel', 'label of admin option', 'yith-shippo-shippings-for-woocommerce' ),
			// translators: placeholders are html tags.
			'desc'      => sprintf( esc_html_x( 'Set adjustments to the dimensions and weight of a parcel before the plugin requests shipping rates for it.%1$sNote%2$s: the plugin will use these values to quote shipping rates even when products don’t have any dimensions set or are too small.', 'label of admin option. Placeholder are html tags', 'yith-shippo-shippings-for-woocommerce' ), '<br><strong>', '</strong>' ),
			'id'        => 'yith_shippo_min_parcel_dimension',
			'type'      => 'yith-field',
			'yith-type' => 'text-array',
			'fields'    => array(
				// translators:placeholder is the current length dimension set on the store.
				'min-length' => sprintf( esc_html_x( 'Min length (%s)', 'label of option; placeholder is the current length dimension set on the store', 'yith-shippo-shippings-for-woocommerce' ), $dimension_unit ),
				// translators:placeholder is the current width dimension set on the store.
				'min-width'  => sprintf( esc_html_x( 'Min width (%s)', 'label of option; placeholder is the current width dimension set on the store.', 'yith-shippo-shippings-for-woocommerce' ), $dimension_unit ),
				// translators:placeholder is the current heigth dimension set on the store.
				'min-height' => sprintf( esc_html_x( 'Min height (%s)', 'label of option; placeholder is the current heigth dimension set on the store', 'yith-shippo-shippings-for-woocommerce' ), $dimension_unit ),
				// translators:placeholder is the current weigth dimension set on the store.
				'min-weight' => sprintf( esc_html_x( 'Min weight (%s)', 'label of option; placeholder is the current weigth dimension set on the store', 'yith-shippo-shippings-for-woocommerce' ), $weight_unit ),
			),

			'default'   => array(
				'min-length' => $default_parcel_box_dimentsions['length'],
				'min-width'  => $default_parcel_box_dimentsions['width'],
				'min-height' => $default_parcel_box_dimentsions['height'],
				'min-weight' => $default_parcel_box_dimentsions['weight'],
			),
			'size'      => '200',
			'inline'    => true,
		),

		'enable_additional_weight'               => array(
			'name'      => esc_html_x( 'Set an additional weight', 'label of admin option', 'yith-shippo-shippings-for-woocommerce' ),
			'desc'      => esc_html_x( 'Enable to increase the weight of the parcel (useful if you typically include promotional materials, gadgets, etc.)', 'description of admin option', 'yith-shippo-shippings-for-woocommerce' ),
			'id'        => 'yith_shippo_enable_additional_weight',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'yes',
		),

		'additional_weight'                      => array(
			'name'      => esc_html_x( 'Add to weight', 'label of admin option', 'yith-shippo-shippings-for-woocommerce' ),
			'desc'      => esc_html_x( 'Set the weight to sum by default to parcels weight.', 'description of admin option', 'yith-shippo-shippings-for-woocommerce' ),
			'id'        => 'yith_shippo_additional_weight',
			'type'      => 'yith-field',
			'yith-type' => 'inline-fields',
			'fields'    => array(
				'type'   => array(

					'type'    => 'select',
					'options' => array(
						'fixed'      => esc_html_x( 'A fixed amount of', '', 'yith-shippo-shippings-for-woocommerce' ),
						'percentage' => esc_html_x( 'A percentage amount of', '', 'yith-shippo-shippings-for-woocommerce' ),
					),
					'default' => 'fixed',
				),
				'amount' => array(
					'type' => 'text',
				),
				'html'   => array(
					'type' => 'html',
					'html' => '<div class="fixed">' . $weight_unit . '</div><div class="percentage">%</div>',
				),
			),
			'deps'      => array(
				'id'    => 'yith_shippo_enable_additional_weight',
				'value' => 'yes',
				'type'  => 'hide',
			),
		),
	),
);

/**
 * APPLY_FILTERS: yith_shippo_panel_parcel_adjustment_options
 *
 * This filter allow to manage the options on the "Parcel Adjustment" tab.
 *
 * @param   array  $parcel_adjustment_options  List of options.
 *
 * @return array
 */
return apply_filters( 'yith_shippo_panel_parcel_adjustment_options', $parcel_adjustment_options );
