<?php
/**
 * This template add in the product the shipping fields
 *
 * @package YITH\Shippo\Views\ProductMetabox
 * @author YITH <plugins@yithemes.com>
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

global $product_object;

$tariff_number  = $product_object->get_meta( '_yith_shippo_tariff_number' );
$country_origin = $product_object->get_meta( '_yith_shippo_country_origin' );
$desc_tariff    = sprintf(// translators: the placeholders are html tags.
	_x(
		'Set the tariff number for the international shipments. You can find your tariff number %1$shere%2$s. Leave empty to use the default value.',
		'yith-shippo-shippings-for-woocommerce'
	),
	'<a href="https://hts.usitc.gov" target="_blank">',
	'</a><br/>'
);

$default_opts = get_option( 'yith_shippo_default_postage_options', yith_shippo_get_default_postage_options() );

if ( empty( $country_origin ) ) {
	$country_origin = $default_opts['country_origin'];
}
?>
<div class="options_group yith-plugin-ui">
	<p class="form-field _tariff_number">
		<label for="_yith_shippo_tariff_number"><?php esc_html_e( 'Tariff number', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
		<input type="text" id="_yith_shippo_tariff_number" name="_yith_shippo_tariff_number" value="<?php echo esc_attr( $tariff_number ); ?>" placeholder="<?php echo esc_attr( $default_opts['tariff_number'] ); ?>">
		<span class="description" style="clear: both;padding-left: 5px;padding-top: 10px;">
			<?php echo wp_kses_post( $desc_tariff ); ?>
		</span>
	</p>
	<p class="form-field _country_origin">
		<label for="_yith_shippo_country_origin"><?php esc_html_e( 'Country of origin', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
		<?php
		$args = array(
			'type'              => 'select',
			'id'                => '_yith_shippo_country_origin',
			'name'              => '_yith_shippo_country_origin',
			'options'           => WC()->countries->get_countries(),
			'class'             => 'wc-enhanced-select',
			'value'             => $country_origin,
			'custom_attributes' => array(
				'style' => 'width:50%',
			),
		);

		yith_plugin_fw_get_field( $args, true, false );
		?>
		<span class="description" style="clear: both;padding-left: 5px;padding-top: 10px;">
			<?php esc_html_e( 'Set the country of origin for this product. This is required for international shipments.', 'yith-shippo-shippings-for-woocommerce' ); ?>
		</span>
	</p>
</div>
