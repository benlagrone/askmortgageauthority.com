<?php
/**
 * This template show the status of onboarding procedure
 *
 * @package YITH\Shippo\Views
 *
 * @var array $result The result.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$class_error = ! empty( $_GET['error'] ) ? 'with_error' : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
$connected   = empty( $result['error'] );
$error       = $result['error_description'] ?? '';
?>
<link rel="stylesheet" href="<?php echo esc_attr( YIT_CORE_PLUGIN_URL . '/assets/css/yith-icon.css' ); ?>" media="all"><?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
<link rel="stylesheet" href="<?php echo esc_attr( YIT_CORE_PLUGIN_URL . '/assets/css/yith-plugin-ui.css' ); ?>" media="all"><?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
<style>

    body {
        margin: 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }

    #yith_shippo_onboarding_status {
        background-image: url(<?php echo esc_attr( YITH_SHIPPO_ASSETS_URL . '/images/onboarding-status/success-shippo-bg.svg' ); ?>);
        background-repeat: no-repeat;
        background-size: cover;
        width: 100%;
        height: 100%;
        margin-top: -5px;
    }

    #yith_shippo_onboarding_status.with_error {
        background-image: url(<?php echo esc_attr( YITH_SHIPPO_ASSETS_URL . '/images/onboarding-status/error-shippo-bg.svg' ); ?>);
    }

    #yith-plugin-fw-float-save-button, #wpwrap {
        display: none !important;
    }

    .yith_shippo_onboarding_status_ok, .yith_shippo_onboarding_status_error {
        position: absolute;
        top: 20%;
        left: 27%;
        max-width: 45%;
        font-weight: bold;
        text-align: center;
        line-height: 24px;
        background-repeat: no-repeat;
        background-size: contain;
        height: 200px;
        font-family: inherit;
    }

    .yith_shippo_onboarding_status_ok {
        background-image: url(<?php echo esc_attr( YITH_SHIPPO_ASSETS_URL . '/images/onboarding-status/shippo-icon-green.png' ); ?>);
    }

    .yith_shippo_onboarding_status_error {
        background-image: url(<?php echo esc_attr( YITH_SHIPPO_ASSETS_URL . '/images/onboarding-status/shippo-icon-red.png' ); ?>);
    }

    .yith_shippo_onboarding_status_ok:before, .yith_shippo_onboarding_status_error:before {
        content: '';
        display: block;
        margin-bottom: 15px;
        background-repeat: no-repeat;
        background-size: contain;
        width: 32px;
        height: 22px;
        margin-left: 95px;
        margin-top: 6px;
    }

    .yith_shippo_onboarding_status_ok:before {
        background-image: url(<?php echo esc_attr( YITH_SHIPPO_ASSETS_URL . '/images/onboarding-status/checkgreen.svg' ); ?>);
    }

    .yith_shippo_onboarding_status_error {
        color: #931818;
    }

    .yith_shippo_onboarding_status_error:before {
        background-image: url(<?php echo esc_attr( YITH_SHIPPO_ASSETS_URL . '/images/onboarding-status/error.svg' ); ?>);
    }

</style>
<script type="text/javascript">
  function RefreshParent() {
    if (window.opener != null && !window.opener.closed) {
      const event = new CustomEvent('yith-shippo-update-connection-button', {detail: {connected:<?php echo $connected ?>, error: '<?php echo $error ?>' }});
      window.opener.document.dispatchEvent(event);
    }
  }

  window.onbeforeunload = RefreshParent;
  setTimeout(function() {
        window.close();
      },
      3000,
  );
</script>
<div id="yith_shippo_onboarding_status" class="yith-plugin-ui <?php echo esc_attr( $class_error ); ?>">
	<?php
	if ( empty( $result['error'] ) ) : //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="yith_shippo_onboarding_status_ok">
			<?php esc_html_e( 'Successfully connected to your Shippo account!', 'yith-shippo-shippings-for-woocommerce' ); ?>
		</div>
	<?php
	else :
		$error_message = $result['error_description'];
		?>
		<div class="yith_shippo_onboarding_status_error">
			<?php echo esc_html( $error_message ); ?>
		</div>
	<?php
	endif;
	?>
</div>
