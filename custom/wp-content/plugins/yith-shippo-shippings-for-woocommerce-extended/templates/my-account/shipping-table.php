<?php
/**
 * HTML Shipping template for the orders on My Account page.
 *
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @version 1.0.0
 * @package YITH/Shippo/Templates
 *
 * @var YITH_Shippo_Order_Shipping $order_shipping Order Shipping Object.
 */

defined( 'ABSPATH' ) || exit;

if ( $order_shipping instanceof YITH_Shippo_Order_Shipping ) :
	$transactions = array_filter( $order_shipping->get_transactions() );
	if ( $transactions ) : ?>
		<div class="yith-shippo-shipping-info-container">
		<h3><?php echo esc_html__( 'Shipping info', 'yith-shippo-shippings-for-woocommerce' ); ?></h3>
		<?php
		foreach ( $transactions as $transaction_key => $transaction ) :
			$tracking = $order_shipping->get_tracking( $transaction_key );
			if ( $tracking ) :
				$tracking_info = maybe_unserialize( $tracking->get_info() );
				$tracking_eta  = '';
				if ( ! empty( $tracking_info['eta'] ) ) {
					$tracking_eta = sprintf( '%1$s - %2$s', date_i18n( wc_date_format(), strtotime( $tracking_info['eta'] ) ), date_i18n( wc_time_format(), strtotime( $tracking_info['eta'] ) ) );
				}
				?>
					<div class="yith-shippo-shipping-container">
						<div class="yith-shippo-shipping-details">
							<span>
								<?php echo esc_html__( 'Order picked up by', 'yith-shippo-shippings-for-woocommerce' ) . ' <b>' . esc_html( yith_shippo_camel_case( $tracking_info['carrier'] ) ) . '</b>.'; ?>
							</span>
							<br>
							<span>
								<?php echo esc_html__( 'Estimated delivery date', 'yith-shippo-shippings-for-woocommerce' ) . ': <b>' . esc_html( $tracking_eta ) . '</b>.'; ?>
							</span>
							<br>
							<span>
								<?php echo esc_html__( 'Status', 'yith-shippo-shippings-for-woocommerce' ) . ': <b>' . esc_html( $tracking->get_status() ) . ' - ' . esc_html( $tracking_info['tracking_status']['status_details'] ) . '</b>'; ?>
							</span>
							<br>
							<span>
								<?php echo esc_html__( 'Tracking code', 'yith-shippo-shippings-for-woocommerce' ) . ': <b>' . esc_html( $transaction['tracking_number'] ) . '</b>'; ?>
							</span>
						</div>
						<p>
							<a href="<?php echo esc_url( $transaction['tracking_url_provider'] ); ?>" class="yith-shippo-shipping-live-track" target="_blank"><?php echo esc_html__( 'Track your order', 'yith-shippo-shippings-for-woocommerce' ); ?></a>
						</p>
					</div>
				</div>
				<?php
			endif;
		endforeach;
	endif;
endif;
