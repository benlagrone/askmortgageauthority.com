<?php
/**
 * This field manage a select to choose WooCommerce shipping zone
 *
 * @package YITH\Shippo\CustomFields
 * @author YITH <plugins@yithemes.com>
 * @version 1.0.0
 *
 * @var array $field The field
 */

defined( 'ABSPATH' ) || exit;
yith_plugin_fw_enqueue_enhanced_select();

list ( $field_id, $class, $data, $name, $style, $value ) = yith_plugin_fw_extract( $field, 'id', 'class', 'data', 'name', 'style', 'value' );

$options       = yith_shippo_get_shipping_zones();
$field['type'] = 'checkbox-array';

$options       = yith_shippo()->get_shipping_zones();
$field['type'] = 'checkbox-array';

$field['options'] = $options;
$field['value']   = array_map( 'intval', (array) $value );

?>
<div id="<?php echo esc_attr( $field_id ); ?>" class="yith-shippo-shipping-zones" <?php yith_plugin_fw_html_data_to_string( $data, true ); ?>>
	<div class="yith-shipping-zone-list">
		<?php foreach ( $options as $key => $label ) : ?>
			<?php
			$checkbox_id = sanitize_key( $field_id . '-' . $key );
			?>
			<div class="yith-plugin-fw-checkbox-array__row">
				<input type="checkbox" id="<?php echo esc_attr( $checkbox_id ); ?>" name="<?php echo esc_attr( $name ); ?>[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $field['value'], true ) ); ?> />
				<span><?php echo wp_kses_post( $label ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>
	<div class="yith-shipping-zone-select-all">
		<?php
		$checkbox = array(
			'id'          => $field_id . '--checkbox',
			'type'        => 'checkbox',
			'value'       => count( $options ) === count( $field['value'] ),
			'desc-inline' => __( 'Select all', 'yith-shippo-shippings-for-woocommerce' ),
		);
		yith_plugin_fw_get_field( $checkbox, true, true );
		?>
	</div>
</div>
