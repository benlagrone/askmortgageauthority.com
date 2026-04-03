<?php
/**
 * HTML Template Email Shipment tracking table
 *
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @version 1.0.0
 * @package YITH/Shippo/Templates
 *
 * @var WC_Order $order Order.
 * @var array    $tracking Tracking information.
 */

do_action( 'yith_shippo_before_shipment_table', $order );

if ( ! empty( $tracking ) ) {
	$old_status       = $tracking['old_status'];
	$current_status   = $tracking['current_status'];
	$tracking_updated = $tracking['tracking_updated'];
	$key              = $tracking['key'];
	$tracking_obj     = $tracking['tracking'];
	$order_shipping   = $tracking['order_shipping'];
	$transaction      = $order_shipping->get_transaction( $key );
	$tracking_info    = $tracking_obj->get_info();
	$tracking_eta     = '';
	if ( ! empty( $tracking_info['eta'] ) ) {
		$tracking_eta = date_i18n( wc_date_format(), strtotime( $tracking_info['eta'] ) );
	}
	$history = $tracking_info['tracking_history'];
	?>
	<div class="yith-shippo-shipping-status-container">
		<div class="yith-shippo-shipping-status-box">
			<table class="progressbar icons">
				<tr>
					<td>
						<div class="status-listed <?php echo in_array( $current_status, array( 'TRANSIT', 'DELIVERED', 'FAILURE' ), true ) ? 'done' : ''; ?>">
							<div class="status-step">
								<div class="inner-circle active"><img src="<?php echo esc_html( YITH_SHIPPO_ASSETS_URL ); ?>/images/shipping-icons/waiting-pickup-white.png" alt="Waiting image" class="waiting"></div>
							</div>
							<div class="status-vl"></div>
						</div>
					</td>
					<td class="status-label">
						<span class="waiting"><?php echo esc_html_x( 'Waiting for pickup', '', 'yith-shippo-shippings-for-woocommerce' ); ?></span><br>
						<span class="status-date">
							<?php
							foreach ( $history as $item ) {
								if ( in_array( $item['status'], array( 'PRE-TRANSIT', 'UNKNOWN' ), true ) ) {
									$status_date = date_i18n( wc_date_format(), strtotime( $item['status_date'] ) );
									echo esc_html( $status_date );
								}
							}
							?>
						</span>
					</td>
				</tr>
				<tr>
					<td class="<?php echo in_array( $current_status, array( 'TRANSIT', 'FAILURE' ), true ) ? 'current' : ''; ?>">
						<div class="status-listed <?php echo in_array( $current_status, array( 'DELIVERED', 'FAILURE' ), true ) ? 'done' : ''; ?>">
							<div class="status-step <?php echo 'TRANSIT' === $current_status ? 'current' : ''; ?>">
								<div class="inner-circle <?php echo in_array( $current_status, array( 'PRE-TRANSIT', 'UNKNOWN', 'TRANSIT', 'DELIVERED', 'FAILURE' ), true ) ? 'active' : ''; ?>">
									<?php if ( in_array( $current_status, array( 'PRE-TRANSIT', 'UNKNOWN', 'TRANSIT', 'DELIVERED', 'FAILURE' ), true ) ) { ?>
										<img src="<?php echo esc_html( YITH_SHIPPO_ASSETS_URL ); ?>/images/shipping-icons/intransit-white.png" alt="Transit image" class="transit">
										<?php
									} else {
										?>
										<img src="<?php echo esc_html( YITH_SHIPPO_ASSETS_URL ); ?>/images/shipping-icons/intransit-grey.png" alt="Transit image" class="transit">
										<?php
									}
									?>
								</div>
							</div>
							<div class="status-vl <?php echo 'FAILURE' === $current_status ? 'exist-next' : ''; ?>"></div>
						</div>
					</td>
					<td class="status-label">
						<span class="transit"><?php echo esc_html_x( 'In transit', '[Email] status of tracking', 'yith-shippo-shippings-for-woocommerce' ); ?></span><br>
						<span class="status-date">
							<?php
							foreach ( $history as $item ) {
								if ( 'TRANSIT' === $item['status'] ) {
									$status_date = date_i18n( wc_date_format(), strtotime( $item['status_date'] ) );
									echo esc_html( $status_date );
								}
							}
							?>
						</span>
					</td>
				</tr>
				<?php
				if ( 'FAILURE' === $current_status ) {
					?>
					<tr>
						<td class="current">
							<div class="status-listed">
								<div class="status-step current failure">
									<div class="inner-circle <?php echo in_array( $current_status, array( 'FAILURE' ), true ) ? 'failure active' : ''; ?>">
										<img src="<?php echo esc_html( YITH_SHIPPO_ASSETS_URL ); ?>/images/shipping-icons/warning.png" alt="Failure image" class="warning">
									</div>
								</div>
								<div class="status-vl"></div>
							</div>
						</td>
						<td class="status-label">
							<span class="transit"><?php echo esc_html_x( 'Failure', '', 'yith-shippo-shippings-for-woocommerce' ); ?></span><br>
							<span class="status-date">
							<?php
							foreach ( $history as $item ) {
								if ( 'FAILURE' === $item['status'] ) {
									$status_date = date_i18n( wc_date_format(), strtotime( $item['status_date'] ) );
									echo esc_html( $status_date );
								}
							}
							?>
						</span>
						</td>
					</tr>
					<?php
				}
				?>
				<tr>
					<td <?php echo 'DELIVERED' === $current_status ? 'current delivered' : ''; ?>>
						<div class="status-listed">
							<div class="status-step <?php echo 'DELIVERED' === $current_status ? 'current delivered' : ''; ?>">
								<div class="inner-circle">
									<?php
									if ( 'DELIVERED' === $current_status ) {
										?>
										<img src="<?php echo esc_html( YITH_SHIPPO_ASSETS_URL ); ?>/images/shipping-icons/delivered-white.png" alt="Delivered image" class="delivered">
										<?php
									} else {
										?>
										<img src="<?php echo esc_html( YITH_SHIPPO_ASSETS_URL ); ?>/images/shipping-icons/delivered-grey.png" alt="Delivered image" class="delivered">
										<?php
									}
									?>
								</div>
							</div>
						</div>
					</td>
					<td class="status-label">
						<span class="delivered"><?php echo esc_html_x( 'Delivered', '', 'yith-shippo-shippings-for-woocommerce' ); ?></span><br>
						<span class="status-date">
							<?php
							foreach ( $history as $item ) {
								if ( 'TRANSIT' === $item['status'] ) {
									$status_date = date_i18n( wc_date_format(), strtotime( $item['status_date'] ) );
									echo esc_html( $status_date );
								}
							}
							?>
						</span>
					</td>
				</tr>
			</table>
		</div>
	</div>
	<div class="yith-shippo-shipping-info-container">
		<div class="yith-shippo-shipping-info">
			<p class="estimated-date"><span><?php echo esc_html_x( 'Estimated delivery date', '[Email] Estimated date to delivery', 'yith-shippo-shippings-for-woocommerce' ) . ':</span> ' . esc_html( $tracking_eta ); ?></p>
			<?php
			if ( $transaction ) {
				if ( isset( $transaction['tracking_number'] ) ) {
					?>
					<p class="tracking-code"><span><?php echo esc_html_x( 'Tracking code', '[Email] Tracking code of shipping', 'yith-shippo-shippings-for-woocommerce' ) . ':</span> ' . esc_html( $transaction['tracking_number'] ); ?></p>
					<?php
				}
				if ( isset( $transaction['tracking_url_provider'] ) ) {
					?>
					<a href="<?php echo esc_url( $transaction['tracking_url_provider'] ); ?>" target="_blank" class="btn shippo-button"><?php echo esc_html_x( 'Track shipment', '[Email] Label for the link to the page that shows the tracking info.', 'yith-shippo-shippings-for-woocommerce' ); ?></a>
					<?php
				}
			}
			?>
		</div>
	</div>
	<?php
}

do_action( 'yith_shippo_after_shipment_table', $order );
