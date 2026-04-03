<?php
/**
 * This template show the parcel box info
 *
 * @package YITH\Shippo\Views\CustomFields
 *
 * @var array  $shippo_parcels
 * @var array  $parcel
 * @var string $dimension_unit
 * @var string $weight_unit
 * @var string $shipping_zones
 */

$curr_id  = isset( $is_placeholder ) && $is_placeholder ? '{{data.id}}' : $parcel['ID'];
$disabled = 'parcel' !== $parcel['type'] ? 'disabled="disabled"' : '';

$fields = array(
	'name'           => array(
		'type'    => 'text',
		'id'      => 'yith_shippo_parcel-' . $curr_id . '-name',
		'name'    => 'yith_shippo_parcel[' . $curr_id . '][name]',
		'value'   => '{{data.name}}',
		'class'   => 'yith-shippo-name',
		'default' => '',
	),
	'type'           => array(
		'type'    => 'select',
		'id'      => 'yith_shippo_parcel-' . $curr_id . '-type',
		'name'    => 'yith_shippo_parcel[' . $curr_id . '][type]',
		'value'   => '{{data.type}}',
		'options' => $shippo_parcels,
		'class'   => 'wc-enhanced-select',
		'default' => 'parcel',
	),
	'shipping-zones' => array(
		'type'     => 'shipping-zones',
		'id'       => 'yith_shippo_parcel-' . $curr_id . '-shipping-zones',
		'name'     => 'yith_shippo_parcel[' . $curr_id . '][shipping_zones]',
		'value'    => $parcel['shipping_zones'] ?? array(),
		'multiple' => true,
	),
	'width'          => array(
		'type'              => 'text',
		'id'                => 'yith_shippo_parcel-' . $curr_id . '-width',
		'name'              => 'yith_shippo_parcel[' . $curr_id . '][width]',
		'value'             => '{{data.width}}',
		'custom_attributes' => $disabled,
		'class'             => 'yith-shippo-number',
		'default'           => '',
	),
	'length'         => array(
		'type'              => 'text',
		'id'                => 'yith_shippo_parcel-' . $curr_id . '-length',
		'name'              => 'yith_shippo_parcel[' . $curr_id . '][length]',
		'value'             => '{{data.length}}',
		'custom_attributes' => $disabled,
		'class'             => 'yith-shippo-number',
		'default'           => '',
	),
	'height'         => array(
		'type'              => 'text',
		'id'                => 'yith_shippo_parcel-' . $curr_id . '-height',
		'name'              => 'yith_shippo_parcel[' . $curr_id . '][height]',
		'value'             => '{{data.height}}',
		'custom_attributes' => $disabled,
		'class'             => 'yith-shippo-number',
		'default'           => '',
	),
	'weight'         => array(
		'type'    => 'text',
		'id'      => 'yith_shippo_parcel-' . $curr_id . '-weight',
		'name'    => 'yith_shippo_parcel[' . $curr_id . '][weight]',
		'value'   => '{{data.weight}}',
		'class'   => 'yith-shippo-number',
		'default' => '',
	),
	'inner-padding'  => array(
		'type'    => 'text',
		'id'      => 'yith_shippo_parcel-' . $curr_id . '-inner-padding',
		'name'    => 'yith_shippo_parcel[' . $curr_id . '][inner_padding]',
		'value'   => '{{data.inner_padding}}',
		'class'   => 'yith-shippo-number',
		'default' => '',
	),
	'max-weight'     => array(
		'type'    => 'text',
		'id'      => 'yith_shippo_parcel-' . $curr_id . '-max-weight',
		'name'    => 'yith_shippo_parcel[' . $curr_id . '][max_weight]',
		'value'   => '{{data.max_weight}}',
		'class'   => 'yith-shippo-number',
		'default' => '',
	),
);

?>
<div id="yith-shippo-add-parcel-modal">
	<form method="post" id="yith-shippo-add-parcel">
		<div class="form-row form-row-inline required">
			<label for="name">
				<?php echo esc_html_x( 'Parcel name', '[ADMIN] add parcel modal', 'yith-shippo-shippings-for-woocommerce' ); ?>
			</label>
			<?php yith_plugin_fw_get_field( $fields['name'], true, false ); ?>
		</div>
		<div class="form-row form-row-inline parcel-type">
			<label for="type">
				<?php echo esc_html_x( 'Type', '[ADMIN] add parcel modal', 'yith-shippo-shippings-for-woocommerce' ); ?>
			</label>
			<?php yith_plugin_fw_get_field( $fields['type'], true, false ); ?>
		</div>
		<?php if ( yith_shippo_support_shipping_zones() ) : ?>
			<div class="form-row form-row-inline">
				<label for="shipping-zone">
					<?php echo esc_html_x( 'Shipping zones', '[ADMIN] add parcel modal', 'yith-shippo-shippings-for-woocommerce' ); ?>
				</label>
				<?php yith_plugin_fw_get_field( $fields['shipping-zones'], true, false ); ?>
			</div>
		<?php endif; ?>
		<div class="form-row form-row-inline">
			<label for="length">
				<?php echo esc_html_x( 'Length', '[ADMIN] add parcel modal', 'yith-shippo-shippings-for-woocommerce' ); ?>
			</label>
			<div><?php yith_plugin_fw_get_field( $fields['length'], true, false ); ?><?php echo esc_html( $dimension_unit ); ?></div>

		</div>
		<div class="form-row form-row-inline">
			<label for="width">
				<?php echo esc_html_x( 'Width', '[ADMIN] add parcel modal', 'yith-shippo-shippings-for-woocommerce' ); ?>
			</label>
			<div><?php yith_plugin_fw_get_field( $fields['width'], true, false ); ?><?php echo esc_html( $dimension_unit ); ?></div>
		</div>
		<div class="form-row form-row-inline">
			<label for="height">
				<?php echo esc_html_x( 'Height', '[ADMIN] add parcel modal', 'yith-shippo-shippings-for-woocommerce' ); ?>
			</label>
			<div><?php yith_plugin_fw_get_field( $fields['height'], true, false ); ?><?php echo esc_html( $dimension_unit ); ?></div>
		</div>
		<div class="form-row form-row-inline">
			<label for="weight">
				<?php echo esc_html_x( 'Weight', '[ADMIN] add parcel modal', 'yith-shippo-shippings-for-woocommerce' ); ?>
			</label>
			<div><?php yith_plugin_fw_get_field( $fields['weight'], true, false ); ?><?php echo esc_html( $weight_unit ); ?></div>
		</div>
		<div class="form-row form-row-inline">
			<label for="inner-padding">
				<?php echo esc_html_x( 'Inner padding', '[ADMIN] add parcel modal', 'yith-shippo-shippings-for-woocommerce' ); ?>
			</label>
			<div> <?php yith_plugin_fw_get_field( $fields['inner-padding'], true, false ); ?><?php echo esc_html( $dimension_unit ); ?></div>
		</div>
		<div class="form-row form-row-inline">
			<label for="max-weight">
				<?php echo esc_html_x( 'Max weight', '[ADMIN] add parcel modal', 'yith-shippo-shippings-for-woocommerce' ); ?>
			</label>
			<div><?php yith_plugin_fw_get_field( $fields['max-weight'], true, false ); ?><?php echo esc_html( $weight_unit ); ?></div>
		</div>
		<div class="form-row form-row-wide submit">
			<button class="submit button-primary">
				<# if ( data.edit ) { #>
				<?php echo esc_html_x( 'Save box', '[ADMIN] add parcel modal', 'yith-shippo-shippings-for-woocommerce' ); ?>
				<# } else { #>
				<?php echo esc_html_x( 'Add box', '[ADMIN] add parcel modal', 'yith-shippo-shippings-for-woocommerce' ); ?>
				<# } #>
			</button>

			<input type="hidden" name="enabled" value="{{data.enabled}}" data-value="{{data.enabled}}"/>
		</div>
		<input type="hidden" id="parcel-id" name="id" value="{{data.id}}"/>
	</form>
</div>
