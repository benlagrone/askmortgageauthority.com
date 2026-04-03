<?php
/**
 * This template manage the onboarding button
 *
 * @package YITH\Shippo\Views\Panel
 * @author YITH <plugins@yithemes.com>
 * @version 1.0.0
 *
 * @var array $field The field array.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$token = get_option( 'yith_shippo_live_token', '' );
?>
<div id="<?php echo esc_attr( $field['id'] ); ?>">
	<?php
	if ( empty( $token ) ) :
		?>
		<span class="yith-plugin-fw__button--primary yith-plugin-fw__button--with-icon yith-shippo-button yith-shippo-connect-account">
		<i class="yith-icon yith-shippo-connect-icon"></i>
		<?php esc_html_e( 'Connect to Shippo', 'yith-shippo-shippings-for-woocommerce' ); ?>
	</span>
		<?php
	else :
		?>
		<div class="yith-shippo-connected-row">
			<div class="yith-shippo-connected-button">
				<span class="yith-plugin-fw__button--secondary yith-plugin-fw__button--with-icon yith-shippo-button yith-shippo-connected-account">
				<i class="yith-icon yith-shippo-connected-icon"></i>
				<?php esc_html_e( 'Account connected', 'yith-shippo-shippings-for-woocommerce' ); ?>
				</span>
				<span class="yith-plugin-fw__button--secondary yith-shippo-button yith-shippo-disconnect-account">
					<?php esc_html_e( 'Disconnect', 'yith-shippo-shippings-for-woocommerce' ); ?>
				</span>
                <input type="hidden" name="yith_shippo_disconnect">
			</div>
		</div>
		<?php
	endif;
	?>
</div>
