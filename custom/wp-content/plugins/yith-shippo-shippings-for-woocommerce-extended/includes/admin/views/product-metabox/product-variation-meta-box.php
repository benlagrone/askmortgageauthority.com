<?php
/**
 * This template add in the product variation the shipping fields
 *
 * @package YITH\Shippo\Views\ProductMetabox
 * @author YITH <plugins@yithemes.com>
 * @version 1.0.0
 *
 * @var int     $loop The loop index.
 * @var array   $variation_data The variation data.
 * @var WP_Post $variation Post data.
 */

defined( 'ABSPATH' ) || exit;

$variation_object = wc_get_product( $variation->ID );
$product_object   = wc_get_product( $variation_object->get_parent_id() );

$tariff_number  = $variation_object->get_meta( '_yith_shippo_variation_tariff_number' );
$country_origin = $variation_object->get_meta( '_yith_shippo_variation_country_origin' );
$countries      = array( '' => __( 'Same origin as parent', 'yith-shippo-shippings-for-woocommerce' ) ) + WC()->countries->get_countries();
$desc_tariff    = sprintf(// translators: the placeholders are html tags.
	_x(
		'Set the tariff number for the international shipments. You can find your tariff number %1$shere%2$s. Leave empty to use the default value.',
		'yith-shippo-shippings-for-woocommerce'
	),
	'<a href="https://hts.usitc.gov" target="_blank">',
	'</a><br/>'
);
?>
<div class="yith-shippo-variation-fields yith-plugin-ui">
	<p class="form-field form-row shipment_tariff hide_if_variation_virtual form-row-first">
		<label for="_yith_shippo_variation_tariff_number_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Tariff number', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
		<input type="text" id="_yith_shippo_variation_tariff_number_<?php echo esc_attr( $loop ); ?>" name="_yith_shippo_variation_tariff_number[<?php echo esc_attr( $loop ); ?>]" placeholder="<?php echo esc_attr( $product_object->get_meta( '_yith_shippo_tariff_number' ) ); ?>" value="<?php echo esc_attr( $tariff_number ); ?>">
		<span class="description" style="clear: both;padding-left: 5px;padding-top: 10px;">
			<?php echo wp_kses_post( $desc_tariff ); ?>
		</span>
	</p>
	<p class="form-field form-row hide_if_variation_virtual form-row-last">
		<label for="_yith_shippo_variation_country_origin_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Country of origin', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
		<?php
		$args = array(
			'type'              => 'select',
			'id'                => '_yith_shippo_variation_country_origin_' . $loop,
			'name'              => '_yith_shippo_variation_country_origin[' . $loop . ']',
			'options'           => $countries,
			'class'             => 'wc-enhanced-select',
			'value'             => $country_origin,
			'custom_attributes' => array(
				'style' => 'width:100%',
			),
		);

		yith_plugin_fw_get_field( $args, true, false );
		?>
		<span class="description" style="clear: both;padding-left: 5px;padding-top: 10px;">
			<?php esc_html_e( 'Set the country of origin for this product. This is required for international shipments.', 'yith-shippo-shippings-for-woocommerce' ); ?>
		</span>
	</p>
</div>
