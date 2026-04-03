<?php
/**
 * This template show the list of services
 *
 * @package YITH\Shippo\CustomFields
 * @author YITH <plugins@yithemes.com>
 * @version 1.0.0
 *
 * @var array  $rates The rates list.
 * @var array  $parcel The parcel.
 * @var string $rate_key The original rate key.
 * @var string $old_selected The original rate selected.
 * @var string $currency The order currency.
 */

defined( 'ABSPATH' ) || exit;

$parcel_attr = yith_shippo_get_json( $parcel );

?>
<div class="yith-shippo-service-popup">
	<div class="yith-shippo-service-popup-list" data-parcel="<?php echo $parcel_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
		<?php
		foreach ( $rates as $key => $rate ) :
			$rate_attr = yith_shippo_get_json( $rate );

			$rate_checked = $old_selected === $rate['service'] ? 'service-checked' : '';
			?>
			<div class="yith-shippo-service-popup-row <?php echo esc_attr( $rate_checked ); ?>" data-rate-key="<?php echo esc_attr( $rate_key ); ?>" data-rate=" <?php echo $rate_attr;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" data-service-id="<?php echo esc_attr( $rate['service'] ); ?>">
				<div class="yith-shippo-service-image-popup">
					<?php
					$service_id = $rate['service'];
					$file       = yith_shippo_get_carrier_image_src( $rate['carrier'] );
					if ( '' === $file ) {
						yith_shippo_save_carrier_image( $rate['carrier'], $rate['service_img_url'] );
						$file = yith_shippo_get_carrier_image_src( $rate['carrier']['name'] );
					}
					if ( preg_match( '/(jpg|jpeg|png|gif|ico|svg)$/', $file ) ) :
						?>
						<img src="<?php echo esc_url( $file ); ?>" style="max-width:200px; max-height:200px;"/>
					<?php endif; ?>
				</div>
				<div class="yith-shippo-service-popup--name">
					<?php echo esc_html( $rate['label'] ); ?>
				</div>
				<div class="yith-shippo-service-popup--extra">
					<div class="yith-shippo-service-popup--price">
						<?php echo wp_kses_post( wc_price( $rate['cost'], array( 'currency' => $currency ) ) ); ?>
					</div>
					<?php
					if ( ! empty( $rate['delivery_time_description'] ) ) :
						?>
						<div class="yith-shippo-service-popup--days">
							<small>
								<?php echo esc_html( $rate['delivery_time_description'] ); ?>
							</small>
						</div>
						<?php
					endif;
					?>
				</div>
				<i class="yith-icon yith-icon-check"></i>
			</div>
			<?php
		endforeach;
		?>
	</div>
</div>
