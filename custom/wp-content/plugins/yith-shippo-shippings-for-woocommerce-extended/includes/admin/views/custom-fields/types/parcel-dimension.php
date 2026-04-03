<?php
/**
 * This is the parcel dimensions field
 *
 * @package YITH\Shippo\CustomFields
 * @author YITH <plugins@yithemes.com>
 * @version 1.0.0
 *
 * @var array $field The field
 */

defined( 'ABSPATH' ) || exit;

list( $field_id, $name, $parcel ) = yith_plugin_fw_extract( $field, 'id', 'name', 'value' );

$weight_unit    = get_option( 'woocommerce_weight_unit' );
$dimension_unit = get_option( 'woocommerce_dimension_unit' );

?>
<div id="<?php echo esc_attr( $field_id ); ?>" class="yith-plugin-fw-field-wrapper yith-plugin-fw-parce-dimension-field-wrapper">
	<div class="yith-dimension-inline-fields--container">
		<label for="<?php echo esc_attr( $field_id ); ?>--dimensions"><?php esc_html_e( 'Measurements: (LxWxH)', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
		<div class="yith-dimension-inline-fields">
			<?php
			yith_plugin_fw_get_field(
				array(
					'type'  => 'text',
					'class' => 'yith-parcel-length yith-shippo-small-text',
					'value' => yith_shippo_convert_from_to( $parcel['length'], $parcel['distance_unit'], $dimension_unit ),
					'name'  => $name . '[length]',
				),
				true
			);
			yith_plugin_fw_get_field(
				array(
					'type' => 'html',
					'html' => '<span class="dimension_sep">x</span>',
				),
				true
			);
			yith_plugin_fw_get_field(
				array(
					'type'  => 'text',
					'class' => 'yith-parcel-width yith-shippo-small-text',
					'value' => yith_shippo_convert_from_to( $parcel['width'], $parcel['distance_unit'], $dimension_unit ),
					'name'  => $name . '[width]',
				),
				true
			);

			yith_plugin_fw_get_field(
				array(
					'type' => 'html',
					'html' => '<span class="dimension_sep">x</span>',
				),
				true
			);
			yith_plugin_fw_get_field(
				array(
					'type'  => 'text',
					'class' => 'yith-parcel-height yith-shippo-small-text',
					'value' => yith_shippo_convert_from_to( $parcel['height'], $parcel['distance_unit'], $dimension_unit ),
					'name'  => $name . '[height]',
				),
				true
			);
			yith_plugin_fw_get_field(
				array(
					'type' => 'html',
					'html' => '<span class="dimension_sep">' . esc_html( $dimension_unit ) . '</span>',
				),
				true
			);
			?>
		</div>
	</div>
	<div class="yith-weight-inline-fields--container">
		<label for="<?php echo esc_attr( $field_id ); ?>--tare"><?php esc_html_e( 'Tare', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
		<div class="yith-weight-inline-fields">
			<?php
			yith_plugin_fw_get_field(
				array(
					'type'  => 'text',
					'class' => 'yith-parcel-weight yith-shippo-small-text',
					'value' => $parcel['tare'],
					'name'  => $name . '[weight]',
				),
				true
			);
			yith_plugin_fw_get_field(
				array(
					'type' => 'html',
					'html' => '<span class="dimension_sep">' . esc_html( $weight_unit ) . '</span>',
				),
				true
			);
			?>
		</div>
	</div>
	<div class="yith-total-weight-inline-fields--container">
		<label for="<?php echo esc_attr( $field_id ); ?>--total-weight"><?php esc_html_e( 'Total weight', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
		<div class="yith-total-weight-inline-fields">
			<?php

			$html = '<div class="yith-shippo-total-weight">';

			$html .= '<div class="total_weight">';
			$html .= '<span class="total_weight_value">' . esc_attr( $parcel['weight'] ) . '</span>';
			$html .= '<span class="total_weight_unit">' . esc_html( $weight_unit ) . '</span>';
			$html .= '</div></div>';

			yith_plugin_fw_get_field(
				array(
					'type' => 'html',
					'html' => $html,
				),
				true
			);
			?>
		</div>
	</div>
</div>
