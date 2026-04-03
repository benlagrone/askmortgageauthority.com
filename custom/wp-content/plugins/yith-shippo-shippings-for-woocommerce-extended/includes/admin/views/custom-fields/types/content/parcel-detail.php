<?php
/**
 * The file show the parcel details
 *
 * @package YITH\Shippo\Views\CustomFields
 *
 * @var array  $shippo_parcels
 * @var array  $parcel
 * @var string $dimension_unit
 * @var string $weight_unit
 * @var string $shipping_zones
 */

$curr_id                  = isset( $is_placeholder ) && $is_placeholder ? '{{data.id}}' : $parcel['ID'];
$disabled                 = 'parcel' !== $parcel['type'] ? 'disabled="disabled"' : '';
$parcel['height']         = yith_shippo_convert_from_to( $parcel['height'], $parcel['distance_unit'], $dimension_unit );
$parcel['width']          = yith_shippo_convert_from_to( $parcel['width'], $parcel['distance_unit'], $dimension_unit );
$parcel['length']         = yith_shippo_convert_from_to( $parcel['length'], $parcel['distance_unit'], $dimension_unit );
$weight                   = empty( $parcel['weight'] ) ? ' - ' : sprintf( '%s %s', $parcel['weight'], $weight_unit );
$inner_padding            = empty( $parcel['inner_padding'] ) ? ' - ' : sprintf( '%s %s', $parcel['inner_padding'], $dimension_unit );
$max_weight               = empty( $parcel['max_weight'] ) ? ' - ' : sprintf( '%s %s', $parcel['max_weight'], $weight_unit );
$shipping_zone            = (array) ( isset( $parcel['shipping_zones'] ) ? maybe_unserialize( $parcel['shipping_zones'] ) : array() );
$parcel['shipping_zones'] = $shipping_zone;

$actions = array(
	'edit'   => array(
		'type'  => 'action-button',
		'title' => _x( 'Edit', 'Tip to edit the parcel', 'yith-shippo-shippings-for-woocommerce' ),
		'icon'  => 'edit',
		'url'   => '',
		'class' => 'action__edit',
	),
	'delete' => array(
		'type'   => 'action-button',
		'title'  => _x( 'Delete', 'Tip to delete the parcel', 'yith-shippo-shippings-for-woocommerce' ),
		'icon'   => 'trash',
		'url'    => '',
		'action' => 'delete',
		'class'  => 'action__trash',
	),
);

$fields = array(
	'checkbox' => array(
		'type'  => 'checkbox',
		'id'    => 'yith-shippo-bulk-' . $curr_id,
		'name'  => 'yith-shippo-bulk[' . $curr_id . ']',
		'class' => 'yith-shippo-bulk-single-action',
	),
	'enabled'  => array(
		'type'    => 'onoff',
		'id'      => 'yith-shippo-parcel-' . $curr_id . '-enabled',
		'name'    => 'yith_shippo_parcel[' . $curr_id . '][enabled]',
		'value'   => $parcel['enabled'],
		'default' => 'yes',
	),
);

?>
<tr class="yith-shippo-parcel" data-id="<?php echo esc_attr( $curr_id ); ?>" data-item="<?php echo esc_attr( wp_json_encode( $parcel ) ); ?>">
	<td class="check-bulk">
		<?php yith_plugin_fw_get_field( $fields['checkbox'], true, false ); ?>
		<input type="hidden" name="<?php echo 'yith_shippo_parcel[' . esc_attr( $curr_id ) . '][id]'; ?>" value="<?php echo esc_attr( $curr_id ); ?>">
	</td>
	<td class="name">
		<?php echo esc_html( $parcel['name'] ); ?>
	</td>
	<td class="parcel-type">
		<?php echo esc_html( $shippo_parcels[ $parcel['type'] ] ?? __( 'Parcel', 'yith-shippo-shippings-for-woocommerce' ) ); ?>
	</td>
	<td class="measurements">
		<div class="flex-wrapper">
			<?php echo esc_html( sprintf( '(%s x %s x %s) %s', $parcel['length'], $parcel['width'], $parcel['height'], $dimension_unit ) ); ?>
		</div>
	</td>

	<td class="weight">
		<div class="flex-wrapper">
			<?php echo esc_html( $weight ); ?>
		</div>
	</td>
	<td class="inner-padding">
		<div class="flex-wrapper">
			<?php echo esc_html( $inner_padding ); ?>
		</div>
	</td>
	<td class="max-weight">
		<div class="flex-wrapper">
			<?php echo esc_html( $max_weight ); ?>
		</div>
	</td>
	<?php if ( yith_shippo_support_shipping_zones() ) : ?>
		<td class="shipping-zone">
			<?php
			$zones = array();
			foreach ( $shipping_zone as $zone_id ) :
				$zone_id = $zone_id ?? 0;

				if ( isset( $shipping_zones[ $zone_id ] ) ) :
					$zones[] = $shipping_zones[ $zone_id ];
				endif;
			endforeach;
			?>
			<small><?php echo esc_html( implode( ', ', $zones ) ); ?></small>
		</td>
	<?php endif; ?>
	<td class="active"><?php yith_plugin_fw_get_field( $fields['enabled'], true, false ); ?></td>
	<td class="actions">
		<?php yith_plugin_fw_get_action_buttons( $actions, true ); ?>
	</td>
</tr>
