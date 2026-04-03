<?php
/**
 * Thank you page - Next actions
 *
 * @author  YITH
 * @package YITH\StripePayments\Templates
 * @version 1.0.0
 *
 * @var $next_action array
 * @var $button_classes
 */

?>
<section class="yith-stripe-payments__order-next-actions">
	<h2 class="yith-stripe-payments__order-next-actions__title">
		<?php echo esc_html_x( 'Next steps', 'Thank you page next actions', 'yith-stripe-payments-for-woocommerce' ); ?>
	</h2>

	<?php echo wp_kses_post( wpautop( wptexturize( $next_action['message'] ) ) ); ?>

	<?php if ( $next_action['url'] ) : ?>
		<a rel="nofollow" target="_blank" href="<?php echo esc_url( $next_action['url'] ); ?>" class="button alt <?php esc_attr( $button_classes ); ?>">
			<?php echo esc_html_x( 'Confirm your purchase', 'Thank you page next actions', 'yith-stripe-payments-for-woocommerce' ); ?>
		</a>
	<?php endif; ?>
</section>
