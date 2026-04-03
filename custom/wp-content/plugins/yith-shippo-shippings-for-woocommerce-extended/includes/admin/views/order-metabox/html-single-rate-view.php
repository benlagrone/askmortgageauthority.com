<?php
/**
 * This template show the single shipping view
 *
 * @package YITH\Shippo\Views\OrderMetabox
 *
 * @var string                     $field_name The field name.
 * @var string|int                 $index Is the current index.
 * @var string                     $rate_key Is the current index.
 * @var YITH_Shippo_Order_Shipping $order_shipping Is the current shipping array.
 * @var int                        $order_item_total The total quantity item in the order.
 * @var array                      $products_in_order All product id in the order.
 * @var array                      $owner_parcels_info the parcel.
 * @var array                      $rate The rate.
 * @var array                      $owner_parcels The owner parcel.
 * @var array                      $package_types The package type.
 * @var string                     $currency The order currency.
 */

defined( 'ABSPATH' ) || exit;

$parcel               = $order_shipping->get_parcel_in_rate( $rate );
$total_item_in_parcel = YITH_Shippo_Parcels::count_item_in_parcel( $parcel );
$parcel_id            = YITH_Shippo_Parcels::get_parcel_id( $parcel );
$parcel_type          = YITH_Shippo_Parcels::get_parcel_type( $parcel );

if ( $parcel_id && array_key_exists( $parcel_id, $owner_parcels ) ) {
	$parcel_selected = $parcel_id;
	$parcel['tare']  = $owner_parcels_info[ $parcel_selected ]['weight'];
} elseif ( ! empty( $parcel ) ) {
	$parcel_name = $parcel['name'] ?? _x( 'Single product', 'Is the name of parcel if no package are found', 'yith-shippo-shippings-for-woocommerce' );
	if ( 'combined_box' === $parcel_type ) {
		$owner_parcels = array( $parcel['id'] => $parcel_name . ' - Combined Box' ) + $owner_parcels;
	} else {
		$owner_parcels = array( $parcel['id'] => $parcel_name . ' - ' . $package_types[ $parcel_type ] ) + $owner_parcels;
	}
	$parcel_selected                     = $parcel['id'];
	$parcel['tare']                      = '';
	$owner_parcels_info[ $parcel['id'] ] = $parcel;
} else {
	$parcel_selected = current( array_keys( $owner_parcels ) );
	$parcel          = $owner_parcels_info[ $parcel_selected ];
	$parcel['tare']  = $owner_parcels_info[ $parcel_selected ]['weight'];
}


$product_in_parcel = YITH_Shippo_Parcels::get_product_info_in_parcel( $parcel );
$parcel_extra_info = array();
if ( isset( $parcel['insurance'] ) && 'yes' === $parcel['insurance'] ) {
	$parcel_extra_info[] = 'insurance';
}

if ( isset( $parcel['signature'] ) && 'yes' === $parcel['signature'] ) {
	$parcel_extra_info[] = 'signature';
}

if ( isset( $parcel['is_return'] ) && 'yes' === $parcel['is_return'] ) {
	$parcel_extra_info[] = 'return_label';
}

$parcel_content = array();
if ( isset( $parcel['content'] ) ) {
	$parcel_content['content'] = $parcel['content'];
}
if ( isset( $parcel['description'] ) ) {
	$parcel_content['description'] = $parcel['description'];
}

$postage_opt        = get_option( 'yith_shippo_default_postage_options', yith_shippo_get_default_postage_options() );
$show_tariff_number = false;

if ( ! empty( $product_in_parcel ) ) {
	$show_only_all = $total_item_in_parcel === $order_item_total;
} else {
	$show_only_all     = true;
	$product_in_parcel = $products_in_order;
}

if ( $order_shipping->is_rate_international( $rate_key ) ) {
	$show_only_all      = false;
	$show_tariff_number = true;
}

?>

<div id="yith-shippo-single-rate-view-<?php echo esc_attr( $index ); ?>" class="yith-shippo-single-rate-view">
	<div class="yith-shippo-shipping-form-field yith-shippo-product-info">
		<?php
		yith_plugin_fw_get_field(
			array(
				'id'                 => 'yith-shippo-product-' . $index,
				'type'               => 'products-shipping',
				'name'               => $field_name . '[' . $index . ']',
				'products_in_parcel' => $product_in_parcel,
				'products_in_order'  => $products_in_order,
				'show_all_products'  => $show_only_all,
				'show_tariff_number' => $show_tariff_number,
				'currency'           => $currency,
				'index'              => $index,
			),
			true
		);
		?>
	</div>
	<div class="yith-shippo-shipping-form-field yith-shippo-parcel-info" data-parcels="<?php echo yith_shippo_get_json( $owner_parcels_info ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
		<?php
		yith_plugin_fw_get_field(
			array(
				'id'     => 'yith-shippo-parcel-' . $index,
				'type'   => 'inline-fields',
				'name'   => $field_name . '[' . $index . ']',
				'fields' => array(
					'package' => array(
						'type'    => 'select',
						'class'   => 'yith-shippo-package',
						'options' => $owner_parcels,
						'default' => $parcel_selected,
						'label'   => _x( 'Package type:', 'Label for the option package typs inside each shipping', 'yith-shippo-shippings-for-woocommerce' ),
					),
					'parcel'  => array(
						'type'    => 'parcel-dimension',
						'default' => $parcel,
					),
				),
			),
			true
		);
		?>
	</div>
	<div class="yith-shippo-shipping-form-field yith-shippo-extra-content">
		<?php
		yith_plugin_fw_get_field(
			array(
				'type'   => 'inline-fields',
				'id'     => 'yith-shippo-extra-content-' . $index,
				'name'   => $field_name . '[' . $index . ']',
				'fields' => array(
					'content'     => array(
						'type'    => 'select',
						'options' => yith_shippo_get_postage_content(),
						'default' => isset( $parcel_content['content'] ) ? $parcel_content['content'] : $postage_opt['content'],
						'label'   => _x( 'Content:', 'Label for the option content inside each shipping', 'yith-shippo-shippings-for-woocommerce' ),
					),
					'description' => array(
						'type'    => 'text',
						'default' => isset( $parcel_content['description'] ) ? $parcel_content['description'] : $postage_opt['shipment_description'],
						'label'   => _x( 'Description:', 'Label for the option content inside each shipping', 'yith-shippo-shippings-for-woocommerce' ),
					),
				),
			),
			true
		);
		?>
	</div>
	<div class="yith-shippo-shipping-form-field yith-shippo-extra-option">
		<?php

		yith_plugin_fw_get_field(
			array(
				'type'    => 'checkbox-array',
				'id'      => 'yith-shippo-extra-option-' . $index,
				'name'    => $field_name . '[' . $index . '][extra_option]',
				'options' => array(
					'return_label' => _x( 'Return label', 'Label for the option return label inside each shipping', 'yith-shippo-shippings-for-woocommerce' ),
					'insurance'    => _x( 'Include insurance', 'Label for the option include insurance inside each shipping', 'yith-shippo-shippings-for-woocommerce' ),
					'signature'    => _x( 'Signature required', 'Label for the option signature required inside each shipping', 'yith-shippo-shippings-for-woocommerce' ),
				),
				'value'   => $parcel_extra_info,
			),
			true
		);
		?>
	</div>
	<div class="yith-shippo-shipping-form-field yith-shippo-choose-service-wrap">
		<?php
		yith_plugin_fw_get_field(
			array(
				'type'         => 'choose-service',
				'id'           => 'yith-shippo-service-' . $index,
				'name'         => $field_name . '[' . $index . ']',
				'carrier_info' => array(
					'name'            => $rate['rate']['carrier'],
					'service_id'      => $rate['rate']['service'],
					'service_label'   => $rate['rate']['label'],
					'service_img_url' => $rate['rate']['service_img_url'],
					'cost'            => $rate['rate']['cost'],
					'days'            => $rate['rate']['delivery_time_description'],
				),
				'currency'     => $currency,
			),
			true,
			false
		);
		?>
	</div>
	<div class="yith-shippo-shipping-form-field yith-shippo-actions">
		<input type="hidden" id="<?php echo esc_attr( $field_name ); ?>[<?php echo esc_attr( $index ); ?>][rate_id]" class="yith_shippo_rate_key" value="<?php echo esc_attr( $rate_key ); ?>">
		<?php
		if ( ! $order_shipping->is_rate_confirmed( $rate_key ) ) :
			?>
			<div class="yith-plugin-fw__button--primary yith-plugin-fw__button--xl yith-shippo-create-shipment">
				<?php
				esc_html_e( 'Create shipment', 'yith-shippo-shippings-for-woocommerce' );
				?>
			</div>
			<?php
		endif;
		?>
	</div>
	<a href="#" rel="nofollow" class="yith-shippo-delete-shipping">
		<?php esc_html_e( 'Delete shipment', 'yith-shippo-shippings-for-woocommerce' ); ?>
	</a>
</div>
