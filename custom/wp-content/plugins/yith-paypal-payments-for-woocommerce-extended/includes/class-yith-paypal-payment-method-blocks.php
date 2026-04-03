<?php
/**
 * Payment Method class to register the payment methods ofor cart and checkout blocks
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH PayPal Payments for WooCommerce
 * @version 1.0.0
 */
require_once YITH_PAYPAL_PAYMENTS_PATH . 'includes/builders/wc-blocks/src/Payments/Integrations/YITHPPWC.php';
require_once YITH_PAYPAL_PAYMENTS_PATH . 'includes/builders/wc-blocks/src/Payments/Integrations/YITHPPWC-Card.php';
require_once YITH_PAYPAL_PAYMENTS_PATH . 'includes/builders/wc-blocks/src/Payments/Integrations/YITHPPWC-Google-Pay.php';
require_once YITH_PAYPAL_PAYMENTS_PATH . 'includes/builders/wc-blocks/src/Payments/Integrations/YITHPPWC-Apple-Pay.php';

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Blocks\Package;
defined( 'ABSPATH' ) || exit;

/**
 * Class YITH_PayPal_Payment_Method_Blocks
 */
class YITH_PayPal_Payment_Method_Blocks {

	/**
	 * Loaded
	 *
	 * @var bool
	 */
	public $loaded = false;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		! $this->loaded && add_action( 'woocommerce_blocks_loaded', array( $this, 'add_extension_woocommerce_blocks_support' ) );
	}

	/**
	 * Handle the webhook
	 *
	 * @since 1.0.0
	 */
	public function add_extension_woocommerce_blocks_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {

			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( PaymentMethodRegistry $payment_method_registry ) {
					$container = Package::container();

					$container->register(
						YITHPPWC::class,
						function () {
							return new YITHPPWC();
						}
					);
					$payment_method_registry->register(
						$container->get( YITHPPWC::class )
					);

					$container->register(
						YITHPPWCC_Card::class,
						function () {
							return new YITHPPWCC_Card();
						}
					);
					$payment_method_registry->register(
						$container->get( YITHPPWCC_Card::class )
					);

					/* load GooglePay block support if enabeld */
					if ( YITH_PayPal_Google_Pay::get_instance()->is_enabled() ) {
						$container->register(
							YITHPPWC_Google_Pay::class,
							function () {
								return new YITHPPWC_Google_Pay();
							}
						);
						$payment_method_registry->register(
							$container->get( YITHPPWC_Google_Pay::class )
						);
					}
					/* load ApplePay block support if enabeld */
					if ( YITH_PayPal_Apple_Pay::get_instance()->is_enabled() && YITH_PayPal_Apple_Pay::get_instance()->is_browser_supported() ) {
						$container->register(
							YITHPPWC_Apple_Pay::class,
							function () {
								return new YITHPPWC_Apple_Pay();
							}
						);
						$payment_method_registry->register(
							$container->get( YITHPPWC_Apple_Pay::class )
						);
					}
				},
			);

			$this->loaded = true;
		}
	}
}

new YITH_PayPal_Payment_Method_Blocks();
