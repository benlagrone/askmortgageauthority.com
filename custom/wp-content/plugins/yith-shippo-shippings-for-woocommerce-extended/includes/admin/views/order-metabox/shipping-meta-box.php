<?php
/**
 * This is the template for the order shippings
 *
 * @package YITH\Shippo\Views\OrderMetabox
 * @author YITH <plugins@yithemes.com>
 * @version 1.0.0
 *
 * @var WC_Order                   $order The order object.
 * @var YITH_Shippo_Order_Shipping $order_shipping The shipping object.
 * @var array                      $extra_args The extra args.
 */

defined( 'ABSPATH' ) || exit;

$rates           = $order_shipping instanceof YITH_Shippo_Order_Shipping ? $order_shipping->get_rates() : array();
$items_in_parcel = array();
foreach ( $rates as $rate_key => $rate ) {
	$items_in_parcel[ $rate_key ] = YITH_Shippo_Parcels::get_product_info_in_parcel( $rate['parcel'] );
}

$zone_id = yith_shippo_support_shipping_zones() ? yith_shippo_get_shipping_zone_id_by_order( $order ) : false;

$product_info_attr = yith_shippo_get_json( $extra_args['product_info'] );
$currier_parcel_template = yith_shippo()->request->get_currier_parcel_template();
$package_types     = $currier_parcel_template ? $currier_parcel_template->get_formatted_parcel_templates() : array() ;
$all_parcels       = YITH_Shippo_Parcels::get_instance()->get_parcel_boxes_as_array(
	array(
		'enabled'       => true,
		'shipping_zone' => $zone_id,
	)
);
$all_parcels       = yith_shippo_convert_parcel_templates( $all_parcels );

$owner_parcels      = array();
$owner_parcels_info = array();
foreach ( $all_parcels as $my_parcel ) {
    $name = isset($package_types[ $my_parcel['type'] ]) ?? '';
	$owner_parcels[ $my_parcel['ID'] ]      = $my_parcel['name'] . ' - ' . $name;
	$owner_parcels_info[ $my_parcel['ID'] ] = $my_parcel;
}
$extra_class   = '';
$order_item_id = '';
if ( $order_shipping instanceof YITH_Shippo_Order_Shipping && 'admin' === $order_shipping->get_created_via() ) {
	$extra_class   = 'yith-shippo-hide-shipping-row';
	$order_item_id = $order_shipping->get_id();
}
?>
<div id="yith-shippo-order-meta-box" class="yith-plugin-ui <?php echo esc_attr( $extra_class ); ?>" data-order_id = "<?php echo esc_attr( $order->get_id() ) ?>" data-order_item_id="<?php echo esc_attr( $order_item_id ); ?>" data-items="<?php echo yith_shippo_get_json( $items_in_parcel ); ?>" data-order_configuration="<?php echo $product_info_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
	<div id="yith-shippo-order-shipping-list">
		<?php
		if ( $order_shipping instanceof YITH_Shippo_Order_Shipping ) {
			$i        = 0;
			$view_arg = array(
				'order_shipping'     => $order_shipping,
				'products_in_order'  => $extra_args['products_in_order'],
				'order_item_total'   => $extra_args['total_items'],
				'package_types'      => $package_types,
				'owner_parcels'      => $owner_parcels,
				'owner_parcels_info' => $owner_parcels_info,
				'currency'           => $order->get_currency(),
			);
			foreach ( $rates as $rate_key => $rate ) {
				$view_arg['index']    = $i;
				$view_arg['rate']     = $rate;
				$view_arg['rate_key'] = $rate_key;
				yith_shippo_get_view( '/order-metabox/html-single-shipping-view.php', $view_arg );

				$i ++;
			}
		}
		?>
	</div>
	<div id="yith-shippo-add-new-shipping">
		<?php esc_html_e( '+ Add Shipping', 'yith-shippo-shippings-for-woocommerce' ); ?>
	</div>
</div>
