<?php
/**
 * Template file to Parcel List custom field
 *
 * @package YITH\Shippo\Views\Panel
 */

defined( 'ABSPATH' ) || exit;
$currier_parcel_template = yith_shippo()->request->get_currier_parcel_template();

$shippo_parcels_options = $currier_parcel_template ? $currier_parcel_template->get_formatted_parcel_templates() : array();
$shippo_parcels         = $currier_parcel_template ? $currier_parcel_template->get_converted_parcel_templates() : array();
$parcels                = YITH_Shippo_Parcels::get_instance()->get_parcel_boxes_as_array();
$shipping_zones         = yith_shippo()->get_shipping_zones();

$general_attributes = array(
	'shippo_parcels' => $shippo_parcels_options,
	'weight_unit'    => get_option( 'woocommerce_weight_unit' ),
	'dimension_unit' => get_option( 'woocommerce_dimension_unit' ),
	'shipping_zones' => $shipping_zones,
);

$select_bulk = array(
	'type'    => 'select',
	'id'      => 'yith-shippo-parcel-bulk-action',
	'name'    => 'yith_shippo_parcel_bulk_action',
	'class'   => 'yith-shippo-parcel-bulk-action-select wc-enhanced-select',
	'options' => array(
		''                        => esc_html_x( 'Bulk actions', 'bulk action placeholder', 'yith-shippo-shippings-for-woocommerce' ),
		'activate_parcel_boxes'   => esc_html_x( 'Activate', 'bulk action to activate the parcels', 'yith-shippo-shippings-for-woocommerce' ),
		'deactivate_parcel_boxes' => esc_html_x( 'Deactivate', 'bulk action to deactivate the parcels', 'yith-shippo-shippings-for-woocommerce' ),
		'delete_parcel_boxes'     => esc_html_x( 'Delete', 'bulk action to delete the parcels', 'yith-shippo-shippings-for-woocommerce' ),
	),
);

$default = array(
	'ID'             => 'shippo-1',
	'name'           => '',
	'type'           => 'parcel',
	'width'          => '',
	'length'         => '',
	'height'         => '',
	'weight'         => '',
	'inner_padding'  => '',
	'max_weight'     => '',
	'enabled'        => 1,
	'distance_unit'  => get_option( 'woocommerce_dimension_unit' ),
	'weight_unit'    => get_option( 'woocommerce_weight_unit' ),
	'shipping_zones' => '',
);
?>

<div class="yith-shippo-parcel-list yith-plugin-ui--boxed-wp-list-style" data-parcels="<?php echo esc_attr( wp_json_encode( $shippo_parcels ) ); ?>">
	<?php wp_nonce_field( 'yith_shippo_edit_parcel_boxes', '_yith_shippo_nonce' ); ?>
	<div class="yith-shippo-bulk-actions-container tablenav top">
		<div class="yith-shippo-bulk-actions actions bulkactions">
			<?php yith_plugin_fw_get_field( $select_bulk, true, false ); ?>
			<input type="submit" class="button action" name="yith_shippo_bulk_parcels" id="yith_shippo_bulk_parcels" value="<?php esc_html_e( 'Apply', 'yith-shippo-shippings-for-woocommerce' ); ?>">
			<input type="hidden" id="yith_shippo_bulk_parcels_triggered" name="yith_shippo_bulk_parcels_triggered" value="0">
		</div>
	</div>

	<table id="parcel-list" class="wp-list-table fixed table-view-list">
		<?php if ( ! empty( $parcels ) ) : ?>
		<thead>
		<tr>
			<th class="check-bulk"><input type="checkbox" class="yith-shippo-bulk-action-check"></th>
			<th id="name" class="colum-title"><?php echo esc_html_x( 'Name', 'Name of parcel box', 'yith-shippo-shippings-for-woocommerce' ); ?></th>
			<th id="type" class="colum-title"><?php echo esc_html_x( 'Type', 'Type of parcel box', 'yith-shippo-shippings-for-woocommerce' ); ?></th>
			<th id="measurements" class="colum-title"><?php echo esc_html_x( 'Measurements (LxWxH)', 'Measurements of parcel box', 'yith-shippo-shippings-for-woocommerce' ); ?></th>
			<th id="weight" class="colum-title"><?php echo esc_html_x( 'Weight', 'Weight of parcel box', 'yith-shippo-shippings-for-woocommerce' ); ?></th>
			<th id="inner-padding" class="colum-title"><?php echo esc_html_x( 'Inner padding', 'Inner padding of parcel box', 'yith-shippo-shippings-for-woocommerce' ); ?></th>
			<th id="max-weight" class="colum-title"><?php echo esc_html_x( 'Max weight', 'Maximum weight of parcel box', 'yith-shippo-shippings-for-woocommerce' ); ?></th>
			<?php if ( yith_shippo_support_shipping_zones() ) : ?>
			<th id="shipping-zone" class="colum-title"><?php echo esc_html_x( 'Shipping Zone', 'list of shipping zone where the parcel is activated', 'yith-shippo-shippings-for-woocommerce' ); ?></th>
			<?php endif; ?>
			<th id="active" class="colum-title"><?php echo esc_html_x( 'Activate', 'Set a parcel box active', 'yith-shippo-shippings-for-woocommerce' ); ?></th>
			<th id="actions" class="colum-title"></th>
		</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $parcels as $parcel ) {
				yith_shippo_get_view(
					'/custom-fields/types/content/parcel-detail.php',
					array_merge(
						$general_attributes,
						array(
							'parcel' => $parcel,
						)
					)
				);
			}
			?>
		</tbody>
		<?php endif; ?>
	</table>

	<div class="yith-shippo-add-parcel-box">
		<?php echo esc_html_x( '+ Add box', 'Add parcel box button label', 'yith-shippo-shippings-for-woocommerce' ); ?>
	</div>
</div>

<script type="text/html" id="tmpl-yith-shippo-parcel">
	<?php
	$template_options = array_merge(
		$general_attributes,
		array(
			'parcel'         => $default,
			'is_placeholder' => true,
		)
	);
	yith_shippo_get_view( '/custom-fields/types/content/parcel-box.php', $template_options );
	?>
</script>

