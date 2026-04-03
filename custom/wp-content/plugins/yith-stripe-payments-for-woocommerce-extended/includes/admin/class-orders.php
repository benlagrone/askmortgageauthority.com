<?php
/**
 * Apply changes to Orders admin view
 *
 * @author  YITH
 * @package YITH\StripePayments\Classes
 * @version 1.0.0
 */

namespace YITH\StripePayments\Admin;

use YITH\StripePayments\Amount;
use YITH\StripePayments\Gateways;
use YITH\StripePayments\Traits\Singleton;
use YITH\StripePayments\Traits\Api_Client_Access;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Admin\Orders' ) ) {

	/**
	 * Handles all actions related to onboarding on backend
	 *
	 * @since 1.0.0
	 */
	class Orders {

		use Singleton, Api_Client_Access;

		/**
		 * Constructor method
		 */
		protected function __construct() {
			// add capture label on order details.
			add_action( 'woocommerce_admin_order_totals_after_total', array( $this, 'add_captured_amount' ), 10, 1 );

			// add custom order actions.
			add_action( 'woocommerce_order_actions', array( $this, 'add_capture_action' ), 10, 2 );
			add_action( 'woocommerce_order_action_capture_payment', array( $this, 'capture' ) );

			// hide protected meta.
			add_filter( 'is_protected_meta', array( $this, 'hide_meta' ), 10, 2 );
		}

		/**
		 * Checks if an order needs capture
		 *
		 * @param \WC_order $order Order object.
		 *
		 * @return bool Whether order needs capture.
		 */
		public function needs_capture( $order ) {
			$captured = $this->get_captured_amount( $order );

			$not_captureable_order_statuses = apply_filters(
				'yith_stripe_payments_not_captureable_order_statuses',
				array(
					'failed',
					'refunded',
					'cancelled',
				)
			);

			$is_captureable = ! in_array( $order->get_status(), $not_captureable_order_statuses );

			return $is_captureable && false !== $captured && $captured < $order->get_total();
		}

		/**
		 * Captures remaining amount for the order
		 *
		 * @param \WC_order $order Order object.
		 *
		 * @return bool Whether capture was successful.
		 */
		public function capture( $order ) {
			$gateway = Gateways::get_instance()->get_gateway();

			if ( ! $gateway || $gateway::$slug !== $order->get_payment_method() ) {
				return false;
			}

			$intent_id = $order->get_meta( $gateway->get_meta_key( 'intent' ), true );

			if ( ! $intent_id ) {
				return false;
			}

			$intent = self::get_api()->capture_intent( $intent_id );

			if ( is_wp_error( $intent ) ) {
				return false;
			}

			$amount_captured = Amount::decode( $intent->amount - $intent->amount_capturable, $order->get_currency() );

			$order->update_meta_data( $gateway->get_meta_key( 'captured' ), $amount_captured );
			$order->save();

			return true;
		}

		/**
		 * Get amount captured for order paid with Stripe
		 *
		 * @param \WC_order $order Order object.
		 *
		 * @return bool|float Amount captured for the order, if applicable.
		 */
		public function get_captured_amount( $order ) {
			$gateway = Gateways::get_instance()->get_gateway();

			if ( ! $gateway || $gateway::$slug !== $order->get_payment_method() ) {
				return false;
			}

			$captured = $order->get_meta( $gateway->get_meta_key( 'captured' ), true );

			if ( false === $captured ) {
				return false;
			}

			return (float) $captured;
		}

		/**
		 * Adds a new line to order totals to show amount captured.
		 *
		 * @param int $order_id Order id.
		 */
		public function add_captured_amount( $order_id ) {
			$order   = wc_get_order( $order_id );
			$gateway = Gateways::get_instance()->get_gateway();

			if ( ! $order || ! $gateway || $gateway::$slug !== $order->get_payment_method() ) {
				return;
			}

			$captured = $this->get_captured_amount( $order );

			?>
			<tr>
				<td class="label label-highlight"><?php esc_html_e( 'Captured total', 'woocommerce' ); ?>:</td>
				<td width="1%"></td>
				<td class="total">
					<?php echo wc_price( $captured, array( 'currency' => $order->get_currency() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</td>
			</tr>
			<?php
		}

		/**
		 * Adds capture action to order edit view
		 *
		 * @param string[]  $actions List of available actions.
		 * @param \WC_Order $order   Order object.
		 *
		 * @return string[] Filtered list of actions.
		 */
		public function add_capture_action( $actions, $order ) {
			if ( ! $this->needs_capture( $order ) ) {
				return $actions;
			}

			$actions[ 'capture_payment' ] = __( 'Capture payment', 'yith-stripe-payments-for-woocommerce' );

			return $actions;
		}

		/**
		 * Make order meta created by this plugin protected, excepting when we're in debug mode.
		 *
		 * @param bool   $protected Whether meta is protected.
		 * @param string $meta_key  Meta key being checked.
		 *
		 * @return bool Filtered value of protected flag.
		 */
		public function hide_meta( $protected, $meta_key ) {
			global $post;

			$order_id = $post->ID ?? false;

			if ( ! $order_id && ! empty( $_GET[ 'id' ] ) ) {
				$order_id = absint( $_GET[ 'id' ] );
			}

			$order =wc_get_order($order_id);

			if ( ! $order ) {
				return $protected;
			}

			$debug = defined( 'WP_DEBUG' ) && WP_DEBUG;

			if ( 0 === strpos( $meta_key, 'yith_stripe_payments' ) && ! $debug ) {
				return true;
			}

			return $protected;
		}
	}
}
