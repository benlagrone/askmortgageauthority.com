<?php
/**
 * On-boarding button
 *
 * @author YITH
 * @package YITH\StripePayments\Admin\Views\
 * @version 1.0.0
 *
 * @var array  $connection         Array representing connections status
 * @var array  $additional_classes Array of additional classes for the button
 */

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;
?>

<button class="yith-stripe-payments__onboarding__button <?php echo esc_attr( implode( ' ', $additional_classes ) ); ?>" role="button">
	<?php echo esc_html( _x( 'Connect with Stripe', '[Admin] Onboarding button', 'yith-stripe-payments-for-woocommerce' ) ); ?>
</button>
