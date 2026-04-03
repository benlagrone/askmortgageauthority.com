<?php
/**
 * This is the template to see/modify the selected service
 *
 * @package YITH\Shippo\CustomFields
 * @author YITH <plugins@yithemes.com>
 * @version 1.0.0
 *
 * @var array $field The field
 */

defined( 'ABSPATH' ) || exit;

list( $field_id, $name, $carrier_info, $currency ) = yith_plugin_fw_extract( $field, 'id', 'name', 'carrier_info', 'currency' );

$service_id      = isset( $carrier_info['service_id'] ) ? $carrier_info['service_id'] : '';
$service_name    = isset( $carrier_info['name'] ) ? $carrier_info['name'] : '';
$service_img_url = isset( $carrier_info['service_img_url'] ) ? $carrier_info['service_img_url'] : '';
$service_label   = isset( $carrier_info['service_label'] ) ? $carrier_info['service_label'] : '';
$service_cost    = isset( $carrier_info['cost'] ) ? $carrier_info['cost'] : '';
$service_days    = isset( $carrier_info['days'] ) ? $carrier_info['days'] : '';

?>
<div id="<?php echo esc_attr( $field_id ); ?>" class="yith-plugin-fw-field-wrapper yith-plugin-fw-choose-service-field-wrapper">
	<div class="yith-shippo-service-content <?php echo empty( $service_id ) ? 'hide' : ''; ?>">
		<div class="yith-shippo-service-image">
			<?php
			if ( ! empty( $service_img_url ) && ! empty( $service_name ) ) {
				$file = yith_shippo_get_carrier_image_src( $service_name );
				if ( '' === $file ) {
					yith_shippo_save_carrier_image( $service_name, $service_img_url );
					$file = yith_shippo_get_carrier_image_src( $service_name );
				}
				if ( preg_match( '/(jpg|jpeg|png|gif|ico|svg)$/', $file ) ) :
					?>
					<img src="<?php echo esc_url( $file ); ?>" style="max-width:200px; max-height:200px;"/>
					<?php
				endif;
			}
			?>
		</div>
		<div class="yith-shippo-service-info">
				<span class="yith-shippo-service-label">
					<span class="yith-shippo-service-label-first"><?php echo esc_html( $service_label ); ?></span>
					<span class="yith-shippo-service-label-sep">-</span>
					<span class="yith-shippo-service-label-price"><strong><?php echo wp_kses_post( wc_price( $service_cost, array( 'currency' => $currency ) ) ); ?></strong></span>
				</span>
			<div class="yith-shippo-service-days <?php echo empty( $service_days ) ? 'hide' : ''; ?>">
				<small>
					<?php
					/* translators: %s is the amount of days */
					echo esc_html( $service_days );
					?>
				</small>
			</div>
		</div>
		<a href="#" rel="nofollow" class="yith-shippo-edit-service"><?php esc_html_e( 'Edit >', 'yith-shippo-shippings-for-woocommerce' ); ?></a>
	</div>
	<div class="yith-shippo-service-empty-state-wrapper <?php echo ! empty( $service_id ) ? 'hide' : ''; ?>">
		<div class="yith-shippo-no-service-message">
			<?php esc_html_e( 'No service selected', 'yith-shippo-shippings-for-woocommerce' ); ?>
		</div>
		<div class="yith-plugin-fw__button--primary yith-plugin-fw__button--xl yith-shippo-choose-service">
			<?php
			esc_html_e( 'Choose service', 'yith-shippo-shippings-for-woocommerce' );
			?>
		</div>
	</div>
	<input type="hidden" name="<?php echo esc_attr( $name ); ?>[service_id]" value="<?php echo esc_attr( $service_id ); ?>" class="yith-shippo-service-id">
</div>
