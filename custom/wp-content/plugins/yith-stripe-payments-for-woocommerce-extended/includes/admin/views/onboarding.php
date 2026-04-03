<?php
/**
 * Onboarding template
 * Represents onboarding status and offers options to connect/refresh data.
 *
 * @author YITH
 * @package YITH\StripePayments\Templates\
 * @version 1.0.0
 *
 * @var bool   $connected Whether account is connected or not.
 * @var array  $connection Array representing connections status
 * @var string $status_label A string describing current status for the onboarding process
 */

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;
?>

<div class="yith-stripe-payments__onboarding">
	<p class="connection-status">
		<?php
		$status_span = sprintf( '<span class="%1$s">%2$s</span>', $connected ? 'connected' : 'disconnected', $status_label );

		// Translators: 1. Connection status span.
		echo wp_kses_post( sprintf( __( 'Status: %s', 'yith-stripe-payments-for-woocommerce' ), $status_span ) );
		?>
	</p>

	<?php do_action( 'yith_stripe_payments_before_onboarding_content', $connection ); ?>

	<div class="onboarding-content">
		<?php do_action( 'yith_stripe_payments_onboarding_content', $connection ); ?>
	</div>

	<?php do_action( 'yith_stripe_payments_after_onboarding_content', $connection ); ?>
</div>

<?php wp_enqueue_script( 'yith-stripe-payments-onboarding' ); ?>
