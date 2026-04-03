<?php
/**
 * Onboarding details
 *
 * @author YITH
 * @package YITH\StripePayments\Admin\Views\
 * @version 1.0.0
 *
 * @var array  $connection Array representing connections status
 */

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;
?>

<div class="yith-stripe-payments__onboarding__details" >
	<div class="yith-stripe-payments__onboarding__details__info account-id">
		<span class="info-label">
			<?php esc_html_e( 'Account ID', 'yith-stripe-payments-for-woocommerce' ); ?>
		</span>
		<span class="info-value">
			<?php echo esc_html( $connection['acct_id'] ); ?>
		</span>
	</div>
	<div class="yith-stripe-payments__onboarding__details__info">
		<span class="info-label">
			<?php esc_html_e( 'Details submitted', 'yith-stripe-payments-for-woocommerce' ); ?>
		</span>
		<span class="info-value">
			<?php echo esc_html( $connection['details_submitted'] ? __( 'Yes', 'yith-stripe-payments-for-woocommerce' ) : __( 'No', 'yith-stripe-payments-for-woocommerce' ) ); ?>
		</span>
	</div>
	<div class="yith-stripe-payments__onboarding__details__info">
		<span class="info-label">
			<?php esc_html_e( 'Charges enabled', 'yith-stripe-payments-for-woocommerce' ); ?>
		</span>
		<span class="info-value">
			<?php echo esc_html( $connection['charges_enabled'] ? __( 'Yes', 'yith-stripe-payments-for-woocommerce' ) : __( 'No', 'yith-stripe-payments-for-woocommerce' ) ); ?>
		</span>
	</div>
</div>

<div class="yith-stripe-payments__onboarding__actions" >
	<button class="yith-plugin-fw__button--primary yith-plugin-fw__button--with-icon yith-stripe-payments__onboarding__refresh" role="button">
		<i class="yith-icon yith-icon-reset"></i>
		<?php esc_html_e( 'Refresh info', 'yith-stripe-payments-for-woocommerce' ); ?>
	</button>
	<button class="yith-plugin-fw__button--trash yith-plugin-fw__button--secondary yith-stripe-payments__onboarding__revoke" role="button">
		<?php esc_html_e( 'Disconnect', 'yith-stripe-payments-for-woocommerce' ); ?>
	</button>

	<?php if ( ! $connection['details_submitted'] ) : ?>
		<a href="#" role="button" class="yith-stripe-payments__onboarding__continue">
			<?php esc_html_e( 'Resume Stripe connection >', 'yith-stripe-payments-for-woocommerce' ); ?>
		</a>
	<?php endif; ?>
</div>
