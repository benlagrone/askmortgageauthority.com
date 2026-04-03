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

$transaction = $order_shipping->get_transaction( $rate_key );
$tracking    = $order_shipping->get_tracking( $rate_key );
if ( empty( $tracking ) || empty( $transaction ) ) {
	return;
}
$tracking_status = $tracking->get_status();
$tracking_info   = $tracking->get_info();
$history         = $tracking_info['tracking_history'] ?? false;
?>
<div class="yith-shippo-tracking-info">
	<div class="yith-shippo-tracking--service-info">
		<?php
		$file = yith_shippo_get_carrier_image_src( $rate['rate']['carrier'] );
		if ( '' === $file ) {
			yith_shippo_save_carrier_image( $rate['rate']['carrier'], $rate['rate']['service_img_url'] );
			$file = yith_shippo_get_carrier_image_src( $rate['rate']['carrier'] );
		}
		if ( preg_match( '/(jpg|jpeg|png|gif|ico|svg)$/', $file ) ) :
			?>
			<img class="yith-shippo-tracking--service-image" src="<?php echo esc_url( $file ); ?>" style="max-width:200px; max-height:200px;"/>
		<?php endif; ?>
		<div class="yith-shippo-tracking--service-info-details">
				<span class="yith-shippo-tracking--service-info-service-label">
					<span class="yith-shippo-service-label-first"><?php echo esc_html( $rate['rate']['label'] ); ?></span>
				</span>
			<small class="yith-shippo-service-days <?php echo empty( $rate['rate']['delivery_days'] ) ? 'hide' : ''; ?>">
				<?php
				/* translators: %s is the amount of days */
				echo esc_html( __( 'Estimated delivery in', 'yith-shippo-shippings-for-woocommerce' ) ) . ' ' . esc_html( sprintf( _n( '%s day', '%s days', $rate['rate']['delivery_days'], 'yith-shippo-shippings-for-woocommerce' ), $rate['rate']['delivery_days'] ) );
				?>
			</small>
		</div>
	</div>
	<div class="yith-shippo-tracking--status-info yith-shippo-tracking-block">
		<div class="yith-shippo-tracking-block-label"><?php echo esc_html_x( 'Shippo status:', 'label for the tracking status', 'yith-shippo-shippings-for-woocommerce' ); ?></div>
		<small class="yith-shippo-tracking-block-content"><?php echo esc_html( $tracking_status ); ?></small>
	</div>
	<div class="yith-shippo-tracking--status-info yith-shippo-tracking-block">
		<div class="yith-shippo-tracking-block-label"><?php echo esc_html_x( 'Tracking:', 'label for the carrier link to check the tracking information', 'yith-shippo-shippings-for-woocommerce' ); ?></div>
		<small class="yith-shippo-tracking-block-content">
			<?php if ( ! empty( $transaction['tracking_url_provider'] ) ) : ?>
				<a href="<?php echo esc_url( $transaction['tracking_url_provider'] ); ?>" target="_blank"><?php echo esc_html_x( 'Track your shipping', 'name of url of carrier link to check the tracking information' ); ?><?php echo esc_html( ' >' ); ?></a>
				<?php
			else :
				echo esc_html( $tracking->get_tracking_number() );
			endif;
			?>
		</small>
	</div>
	<div class="yith-shippo-tracking--status-info yith-shippo-tracking-block">
		<div class="yith-shippo-tracking-block-label"><?php echo esc_html_x( 'Label:', 'label for the link to download the shipping label', 'yith-shippo-shippings-for-woocommerce' ); ?></div>
		<small class="yith-shippo-tracking-block-content">
			<a href="<?php echo esc_url( $transaction['label_url'] ); ?>" target="_blank">
				<?php
				echo esc_html_x(
					'Open label',
					'[ADMIN] Label to identify the boxes',
					'yith-shippo-shippings-for-woocommerce'
				);
				?>
			</a>
		</small>
	</div>
	<div class="yith-shippo-tracking--check-for-updates">
		<a href="#" data-tracking-number="<?php echo esc_attr( $tracking->get_tracking_number() ); ?>"><i class="yith-icon yith-icon-update"></i> <?php esc_html_e( 'Check for updates', 'yith-shippo-shippings-for-woocommerce' ); ?></a>
	</div>
</div>
<div class="yith-shippo-shipping-section-box--title">
	<h3><?php echo esc_html_x( 'Status', 'title of tracking status on order details', 'yith-shippo-shippings-for-woocommerce' ); ?></h3>
</div>
<?php
if ( isset( $tracking_info['tracking_status']['substatus'] ) && in_array( $tracking_info['tracking_status']['substatus'], yith_shippo_get_substatus_with_action_required_list(), true ) ) {
	$tracking_status = 'FAILURE';
};
?>
<div class="yith-shippo-shipping-status-box">
	<ul class="progressbar icons">
		<li class="<?php echo in_array( $tracking_status, array( 'TRANSIT', 'DELIVERED', 'FAILURE' ), true ) ? 'done' : ''; ?>">
			<div class="inner-box">
				<div class="status-step">
					<div class="inner-circle active"><span class="shippo-icon shippo-icon-waiting"></span></div>
				</div>
				<span class="waiting inner-box-description"><?php echo esc_html_x( 'Waiting for pickup', '', 'yith-shippo-shippings-for-woocommerce' ); ?></span>
			</div>
		</li>
		<li class="centered <?php echo in_array( $tracking_status, array( 'DELIVERED', 'FAILURE' ), true ) ? 'done' : ''; ?>">
			<div class="inner-box">
				<div class="status-step <?php echo 'TRANSIT' === $tracking_status ? 'current' : ''; ?>">
					<div class="inner-circle <?php echo in_array( $tracking_status, array( 'PRE-TRANSIT', 'UNKNOWN', 'TRANSIT', 'FAILURE', 'DELIVERED' ), true ) ? 'active' : ''; ?>">
						<span class="shippo-icon shippo-icon-transit"></span>
					</div>
				</div>
				<span class="transit inner-box-description"><?php echo esc_html_x( 'In transit', '', 'yith-shippo-shippings-for-woocommerce' ); ?></span>
			</div>
		</li>
		<?php if ( 'FAILURE' === $tracking_status ) : ?>
			<li class="centered failure">
				<div class="inner-box">
					<div class="status-step current">
						<div class="inner-circle">
							<span class="shippo-icon shippo-icon-error"></span>
						</div>
					</div>
					<span class="issue inner-box-description"><?php echo esc_html_x( 'Delivery issue', 'label to describe that was an issue during the shipping', 'yith-shippo-shippings-for-woocommerce' ); ?></span>
				</div>
			</li>
		<?php endif; ?>
		<li class="<?php echo 'DELIVERED' === $tracking_status ? 'done' : ''; ?>">
			<div class="inner-box">
				<div class="status-step <?php echo 'DELIVERED' === $tracking_status ? 'current delivered' : ''; ?>">
					<div class="inner-circle"><span <?php echo $tracking->get_status() === 'DELIVERED' ? 'class="active"' : ''; ?>><span class="shippo-icon shippo-icon-delivered"></span></span></div>
				</div>
				<span class="delivered inner-box-description"><?php echo esc_html_x( 'Delivered', '', 'yith-shippo-shippings-for-woocommerce' ); ?></span>
			</div>
		</li>
	</ul>
</div>
<?php if ( ! empty( $history ) ) : ?>
	<div class="yith-shippo-shipping-tracking-history">
		<a class="yith-shippo-tracking-history-view-detail" href="#"><?php echo esc_html_x( 'View all details', 'link name to open or close the history list', 'yith-shippo-shippings-for-woocommerce' ); ?><i class="yith-icon yith-icon-arrow-down"></i> </a>
		<div class="yith-shippo-tracking-history-list">
			<ul>
				<?php
				foreach ( $history as $history_item ) :
					$date = sprintf( '%1$s - %2$s', date_i18n( wc_date_format(), strtotime( $history_item['status_date'] ) ), date_i18n( wc_time_format(), strtotime( $history_item['status_date'] ) ) );

					$detail       = $history_item['status_details'];
					$location     = ! empty( $history_item['location'] ) ? implode( ', ', $history_item['location'] ) : '';
					$status_class = strtolower( $history_item['status'] );
					?>
					<li class="<?php echo esc_attr( $status_class ); ?>">
						<span class="date"><?php echo esc_html( $date ); ?></span>
						<span class="description"><?php echo esc_html( $detail ); ?></span>
						<?php if ( ! empty( $location ) ) : ?>
							<span class="location"><?php echo esc_html( $location ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
<?php endif; ?>
