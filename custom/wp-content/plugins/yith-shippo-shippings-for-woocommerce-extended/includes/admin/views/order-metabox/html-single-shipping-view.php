<?php
/**
 * This template show the single shipping view
 *
 * @package YITH\Shippo\Views\OrderMetabox
 *
 * @var string|int                 $index Is the current index.
 * @var string                     $rate_key Is the current index.
 * @var YITH_Shippo_Order_Shipping $order_shipping Is the current shipping array.
 * @var array                      $rate The current rate.
 * @var array                      $products_in_order All product id in the order.
 * @var array                      $owner_parcels All parcel stored in db.
 * @var array                      $owner_parcels_info All parcels info stored in db.
 * @var array                      $package_types All package type.
 * @var string                     $currency The order currency.
 * @var int                        $order_item_total The total quantity item in the order.
 */

defined( 'ABSPATH' ) || exit;

$field_name = 'yith-shippo-shipping-metabox';

?>
<div class="yith-shippo-shipping-section-box yith-shippo-shipping-section-box--closed" data-rate-key="<?php echo esc_attr( $rate_key ); ?>">
	<div class="yith-shippo-shipping-section-box--title">
		<h3>
			<?php
			/* translators: %s is the current index */
			echo esc_html( sprintf( _x( 'Shipping %s', 'Placeholder is the current index of the shipping', 'yith-shippo-shippings-for-woocommerce' ), $index + 1 ) );
			?>
		</h3>
		<span class="yith-shippo-shipping-section-box--toggle"><span class="dashicons dashicons-arrow-up-alt2"></span></span>
	</div>
	<div class="yith-shippo-shipping-section-box--content">
		<?php
		if ( ! $order_shipping->is_rate_confirmed( $rate_key ) ) :
			$args = array(
				'field_name'         => $field_name,
				'rate_key'           => $rate_key,
				'rate'               => $rate,
				'index'              => $index,
				'owner_parcels'      => $owner_parcels,
				'owner_parcels_info' => $owner_parcels_info,
				'package_types'      => $package_types,
				'currency'           => $currency,
				'order_shipping'     => $order_shipping,
				'order_item_total'   => $order_item_total,
				'products_in_order'  => $products_in_order,
			);
			yith_shippo_get_view( '/order-metabox/html-single-rate-view.php', $args );
			?>
			<?php
		elseif ( $order_shipping->is_rate_confirmed( $rate_key ) && ! $order_shipping->is_rate_paid( $rate_key ) ) :
			$args = array(
				'rate_key'       => $rate_key,
				'rate'           => $rate,
				'index'          => $index,
				'currency'       => $currency,
				'order_shipping' => $order_shipping,
			);
			yith_shippo_get_view( '/order-metabox/html-single-rate-confirmed-view.php', $args );
		else :
			$args = array(
				'rate_key'       => $rate_key,
				'rate'           => $rate,
				'order_shipping' => $order_shipping,
			);
			yith_shippo_get_view( '/order-metabox/html-single-tracking-view.php', $args );
		endif;
		?>
	</div>
</div>
