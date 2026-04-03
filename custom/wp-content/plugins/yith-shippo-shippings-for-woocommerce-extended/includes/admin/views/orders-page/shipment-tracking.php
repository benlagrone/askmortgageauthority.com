<?php
/**
 * This file shows the shipment tracking details in the orders page.
 *
 * @package YITH\Shippo\Views
 * @author YITH <plugins@yithemes.com>
 * @version 1.0.0
 *
 * @var array $order_id The order id.
 * @var YITH_Shippo_Order_Shipping $order_shipping The current rate.
 */

defined( 'ABSPATH' ) || exit;

$transactions = '';
if ( $order_shipping instanceof YITH_Shippo_Order_Shipping ) {
	$transactions = array_filter( $order_shipping->get_transactions() );
}

if ( empty( $transactions ) ) : ?>

<div class="yith-shippo-shipment-tracking-not-shipped">
	<div>
		<?php // translators: string printed in the shipment tracking column of the orders page. ?>
		<span class="not-shipped"><?php esc_html_e( 'Not shipped yet', 'yith-shippo-shippings-for-woocommerce' ); ?></span>
	</div>
	<div>
		<?php // translators: string printed in the shipment tracking column of the orders page. ?>
		<a href="<?php echo esc_url( get_edit_post_link( $order_id ) ); ?>" target="_blank"><?php esc_html_e( 'Create your shipment', 'yith-shippo-shippings-for-woocommerce' ); ?> ></a>
	</div>
</div>
	<?php
else :
	$count              = 1;
	$count_transactions = count( $transactions );
	foreach ( $transactions as $transaction_key => $transaction ) :
		$tracking = $order_shipping->get_tracking( $transaction_key );
		$rate     = $order_shipping->get_rate( $transaction_key );
		if ( $tracking && $rate ) :
			$tracking_info   = maybe_unserialize( $tracking->get_info() );
			$tracking_status = $tracking_info['tracking_status']['status_details'] ?? '';
			?>
			<div class="yith-shippo-shipment-tracking-container">
					<?php if ( $count_transactions > 1 ) : ?>
						<?php // translators: %1$s is the index of shippings. %2$s is the total of shippings. String shown on the orders page, when there are 2 or more shippings paid. ?>
					<div class="yith-shippo-shipping-count"><?php echo esc_html( sprintf( __( 'Shipping %1$s of %2$s', 'yith-shippo-shippings-for-woocommerce' ), $count, $count_transactions ) ); ?></div>
					<?php endif; ?>
				<div class="yith-shippo-shipment-service">
					<?php // translators: string printed in the shipment tracking column of the orders page. ?>
					<span class="yith-shippo-shipment-service-label"><?php echo esc_html_x( 'Service', '[ADMIN] Service of the shipment', 'yith-shippo-shippings-for-woocommerce' ); ?></span>
					<br>
					<span><?php echo esc_html( yith_shippo_camel_case( $rate['rate']['label'] ) ); ?></span>
					<div>
						<?php // translators: string printed in the shipment tracking column of the orders page. ?>
						<a href="<?php echo esc_url( $transaction['tracking_url_provider'] ); ?>" target="_blank"><?php esc_html_e( 'Track shipment', 'yith-shippo-shippings-for-woocommerce' ); ?> ></a>
					</div>
				</div>
				<div class="yith-shippo-shipment-status">
					<?php // translators: string printed in the shipment tracking column of the orders page. ?>
					<span class="yith-shippo-shipment-status-label"><?php echo esc_html_x( 'Status', '[ADMIN] Status of the shipment', 'yith-shippo-shippings-for-woocommerce' ); ?></span>
					<br>
					<span><?php echo esc_html( $tracking_status ); ?></span>
					<div>
						<?php // translators: string printed in the shipment tracking column of the orders page. ?>
						<a href="<?php echo esc_url( $transaction['label_url'] ); ?>" target="_blank"><?php echo esc_html_x( 'View label', '[ADMIN] Label to identify the boxes', 'yith-shippo-shippings-for-woocommerce' ); ?> ></a>
					</div>
				</div>
			</div>
			<?php
		endif;
		$count++;
	endforeach;
	?>
		<?php
endif;

?>
