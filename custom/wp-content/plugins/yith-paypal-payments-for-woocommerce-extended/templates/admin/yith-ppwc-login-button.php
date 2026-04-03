<?php
/**
 * Login PayPal button template
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH PayPal Payments for WooCommerce
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;
// check if merchant is currently logged in.
$merchant = YITH_PayPal_Merchant::get_merchant();

if ( ! $merchant->is_valid() ) { // needs login.
	// then get the login url.
	$login_url = YITH_PayPal::get_instance()->get_gateway()->get_login_url();
} else {
	// Logout url action.
	$logout_url = add_query_arg(
		array(
			'page'   => YITH_PayPal_Admin::get_redirect_page(),
			'action' => 'logout_merchant',
			'nonce'  => wp_create_nonce( 'logout_merchant' ),
		),
		admin_url( 'admin.php' )
	);
	// Refresh url action.
	$refresh_url = add_query_arg(
		array(
			'page'   => YITH_PayPal_Admin::PANEL_PAGE,
			'action' => 'refresh_merchant',
			'nonce'  => wp_create_nonce( 'refresh_merchant' ),
		),
		admin_url( 'admin.php' )
	);
}
?>


		<div class="yith_ppwc_login_button_wrapper">

			<div
					class="onboarding-status">
				<?php
				// translators:Admin option, 1 and 2 the placeholder are tags, 2 is the current status of login.
				printf( esc_html_x( 'Onboarding status: %1$s%2$s%3$s', 'Admin option, 1 and 2 the placeholder are tags, 2 is the current status of login', 'yith-paypal-payments-for-woocommerce' ), '<span class="' . esc_attr( $merchant->is_active() ) . '">', esc_html( yith_ppwc_status_label( $merchant->is_active() ) ), '</span>' );
				?>
			</div>
			<?php if ( ! $merchant->is_valid() ) : ?>
				<div class="yith-ppwc-button-wrapper">
					<a href="<?php echo esc_url( $login_url ); ?>" target="PPFrame"
							data-paypal-onboard-complete="onboardedCallback" data-paypal-button="PPLtBlue" data-yith-paypal-onboard-button="true">
						<?php esc_html_e( 'Connect with PayPal', 'yith-paypal-payments-for-woocommerce' ); ?>
					</a>
				</div>

				<span class="description">
				<?php
				// translators:the placeholder is a html tag.
				printf( wp_kses_post( _x( 'Click on the button to connect your PayPal business account.%1$sIf issues occur during the onboarding with regard to connectiong your account, please contact PayPal support to determine the account eligibility issues.', 'the placeholder is a html tag', 'yith-paypal-payments-for-woocommerce' ) ), '<br/>' );
				?>
				</span>
				<?php
			else :
				$permission_granted = $merchant->get( 'permissions_granted' );
				$consent_status     = $merchant->get( 'consent_status' );
				?>

				<div class="onboarding-status-info">

					<?php if ( $merchant->get( 'merchant_id' ) ) : ?>
						<div class="info">
							<span class="info-label"><?php echo esc_html_x( 'PayPal ID', 'admin login PayPal info', 'yith-paypal-payments-for-woocommerce' ); ?></span>
							<span class="info-value"><?php echo esc_html( $merchant->get( 'merchant_id' ) ); ?></span>
						</div>
					<?php endif; ?>
				</div>
				<?php if ( ! $merchant->are_payments_receivable() || ! $merchant->is_primary_email_confirmed() ) : ?>
				<div class="onboarding-status-note">
					<p>
						<strong><?php echo esc_html_x( 'Notice:', 'admin login PayPal note label', 'yith-paypal-payments-for-woocommerce' ); ?></strong>
					</p>
					<p><?php echo esc_html_x( 'To start to accept payments, login to PayPal and finish signing up . ', 'admin login PayPal note', 'yith-paypal-payments-for-woocommerce' ); ?></p>
				</div>
			<?php endif; ?>
				<div class="onboarding-action-buttons">
					<a href="<?php echo esc_url( $logout_url ); ?>" class="button button-red logout">
						<?php echo esc_html_x( 'Revoke', 'admin login PayPal button label', 'yith-paypal-payments-for-woocommerce' ); ?>
					</a>
				</div>

			<?php endif; ?>
		</div>
