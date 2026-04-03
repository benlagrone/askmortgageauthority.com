<?php
/**
 * This template show the confirmed rate
 *
 * @package YITH\Shippo\Views\OrderMetabox
 *
 * @var string                     $rate_key The rate key.
 * @var array                      $rate The rate.
 * @var int                        $index The index.
 * @var string                     $currency The order currency.
 * @var YITH_Shippo_Order_Shipping $order_shipping The order shipping.
 */

defined( 'ABSPATH' ) || exit;
$product_in_parcel = YITH_Shippo_Parcels::get_product_info_in_parcel( $order_shipping->get_parcel_in_rate( $rate ) );
$product_info      = array();
foreach ( $product_in_parcel as $prod_in_parc ) {
	$product_info[] = $prod_in_parc['qty'] . 'x ' . $prod_in_parc['name'];
}
$product_info = implode( ' | ', $product_info );

?>
<div id="yith-shippo-rate-confirmed-view-<?php echo esc_attr( $rate_key ); ?>" class="yith-shippo-rate-confirmed-view">
	<div class="yith-shippo-service-img">
		<?php
		$file = yith_shippo_get_carrier_image_src( $rate['rate']['carrier'] );
		if ( '' === $file ) {
			yith_shippo_save_carrier_image( $rate['rate']['carrier'], $rate['rate']['service_img_url'] );
			$file = yith_shippo_get_carrier_image_src( $rate['rate']['carrier'] );
		}
		if ( preg_match( '/(jpg|jpeg|png|gif|ico|svg)$/', $file ) ) :
			?>
			<img src="<?php echo esc_url( $file ); ?>" style="max-width:200px; max-height:200px;"/>
		<?php endif; ?>
	</div>
	<div class="yith-shippo-rate-confirmed--service-info">
				<span class="yith-shippo-rate-confirmed-service-label">
					<span class="yith-shippo-service-label-first"><?php echo esc_html( $rate['rate']['label'] ); ?></span>
					<span class="yith-shippo-service-label-sep">-</span>
					<span class="yith-shippo-service-label-price"><strong><?php echo wp_kses_post( wc_price( $rate['rate']['cost'], array( 'currency' => $currency ) ) ); ?></strong></span>
				</span>
		<div class="yith-shippo-service-days <?php echo empty( $rate['rate']['delivery_time_description'] ) ? 'hide' : ''; ?>">
			<small>
				<?php
				/* translators: %s is the amount of days */
				echo esc_html( $rate['rate']['delivery_time_description'] );
				?>
			</small>
		</div>
	</div>
	<div class="yith-shippo-rate-confirmed--products">
		<div class="yith-shippo-rate-confirmed-products-label">
			<?php echo esc_html__( 'Products in this shipping:', 'yith-shippo-shippings-for-woocoomerce' ); ?>
		</div>
		<div class="yith-shippo-rate-confirmed-product-list">
			<?php
			echo wp_kses_post( $product_info );
			?>
		</div>
	</div>
	<div class="yith-shippo-rate-confirmed-actions">
		<span class="yith-plugin-fw__action-button">
			<a href="#" class="yith-plugin-fw__action-button__link yith-plugin-fw__tips" rel="nofollow" data-tip="<?php esc_attr_e( 'Pay shipment', 'yith-shippo-shippings-for-woocommerce' ); ?>"><i class="yith-plugin-fw__action-button__icon yith-icon yith-icon-credit-card yith-shippo-pay-shipping"></i></a>
		</span>
		<span class="yith-plugin-fw__action-button">
		<a href="#" rel="nofollow" data-tip="<?php esc_attr_e( 'Delete', 'yith-shippo-shippings-for-woocommerce' ); ?>" class="yith-plugin-fw__action-button__link yith-plugin-fw__tips"><i class="yith-plugin-fw__action-button__icon yith-icon-trash yith-shippo-delete-shipping""></i></a>
		</span>
	</div>
</div>
