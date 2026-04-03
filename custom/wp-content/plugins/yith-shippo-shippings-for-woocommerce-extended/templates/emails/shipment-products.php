<?php
/**
 * HTML Template Email Shipment tracking products list
 *
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @version 1.0.0
 * @package YITH/Shippo/Templates
 *
 * @var WC_Order $order Order.
 * @var array    $tracking
 */

$order_shipping = $tracking['order_shipping'];
$key            = $tracking['key'];
$rate           = $order_shipping->get_rate( $key );

$product_in_parcel = YITH_Shippo_Parcels::get_product_info_in_parcel( $order_shipping->get_parcel_in_rate( $rate ) );
$product_name      = '';
$product_qty       = '';
$thumbnail         = '';
$image_size        = array( 70, 70 );


do_action( 'yith_shippo_before_shipment_products', $order );
?>

<span class="shipment-items-title"><?php esc_html_e( 'Items in this shipment:' ); ?></span>

<?php
foreach ( $product_in_parcel as $product_id => $prod_in_parc ) {
	$product      = wc_get_product( $product_id );
	$thumbnail    = $product->get_image( $image_size );
	$product_name = $prod_in_parc['name'];
	$product_qty  = $prod_in_parc['qty'];
	$price        = $prod_in_parc['value'];
	?>
	<table class="shipment-products-table">
		<tr class="product-row">
			<td class="product-thumbnail">
				<?php echo wp_kses_post( $thumbnail ); ?>
			</td>
			<td class="product-info">
				<?php echo esc_html( $product_qty ) . 'x ' . esc_html( $product_name ) . '<br>'; ?>
				<?php echo esc_html( get_woocommerce_currency_symbol() ) . ' ' . esc_html( $price ); ?>
			</td>
		</tr>
	</table>
	<?php
}
?>

<?php
do_action( 'yith_shippo_after_shipment_products', $order );

