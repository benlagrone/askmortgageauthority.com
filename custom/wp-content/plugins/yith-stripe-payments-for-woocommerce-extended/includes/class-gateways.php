<?php
/**
 * Gateways handling class
 *
 * @author  YITH
 * @package YITH\StripePayments
 * @version 1.0.0
 */

namespace YITH\StripePayments;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use YITH\StripePayments\Traits\Singleton;
use YITH\StripePayments\Traits\Environment_Access;
use YITH\StripePayments\Gateways\Abstracts\Gateway;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Gateways' ) ) {
	/**
	 * Gateways handling class
	 * Register gateways available for this plugin, and offer quick access to their instances and to general properties
	 * valid for any of them.
	 *
	 * @since 1.0.0
	 */
	class Gateways {

		use Singleton, Environment_Access;

		/**
		 * Array of classes for the supported gateways
		 *
		 * @var array
		 */
		protected $gateway_classes = array(
			'element' => __NAMESPACE__ . '\Gateways\Payment_Element',
		);

		/**
		 * Single instance of the class
		 *
		 * @var Gateway[]
		 */
		protected $gateways = array();

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			// register plugin's gateways.
			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );

			// register plugin's block.
			add_action( 'woocommerce_blocks_payment_method_type_registration', array( $this, 'add_blocks' ) );

			// Sorts the plugin gateways first in the list.
			add_filter( 'default_option_woocommerce_gateway_order', array( $this, 'set_gateways_on_top_of_list' ), 15 );
		}

		/**
		 * Filters existing payment gateways to add plugin's ones
		 *
		 * @param array $payment_gateways List of existing payment gateways.
		 *
		 * @return array Filtered list of payment gateways.
		 */
		public function add_gateways( $payment_gateways ) {
			foreach ( $this->gateway_classes as $slug => $class_name ) {
				$payment_gateways[] = $class_name;
			}

			return $payment_gateways;
		}

		/**
		 * Register Payment Methods for Checkout block
		 *
		 * @param PaymentMethodRegistry $payment_method_registry Payment method registry.
		 */
		public function add_blocks( PaymentMethodRegistry $payment_method_registry ) {
			foreach ( $this->gateway_classes as $slug => $class_name ) {
				$class_name = str_replace( 'Gateways', 'Blocks\PaymentTypes', $class_name );

				if ( ! class_exists( $class_name ) ) {
					continue;
				}

				$payment_method_registry->register( new $class_name() );
			}
		}

		/**
		 * Returns a list of plugin's  gateway classes.
		 *
		 * @return array Array of plugin's gateway classes, indexed by slug.
		 */
		public function get_classes() {
			return $this->gateway_classes;
		}

		/**
		 * Returns a list of plugin's  gateway slugs.
		 *
		 * @return array Array of plugin's gateway slugs.
		 */
		public function get_slugs() {
			return array_keys( $this->gateway_classes );
		}

		/**
		 * Retrieves instance of a specific
		 *
		 * @param string $gateway_slug Slug of the gateway to retrieve (defaults to element slug).
		 *
		 * @return Gateway Instance of the requested gateway, or null.
		 */
		public function get_gateway( $gateway_slug = false ) {
			$slugs = $this->get_slugs();

			if ( ! $gateway_slug ) {
				$gateway_slug = current( $slugs );
			}

			if ( ! in_array( $gateway_slug, $slugs, true ) ) {
				return null;
			}

			if ( ! isset( $this->gateways[ $gateway_slug ] ) ) {
				$gateways = WC()->payment_gateways()->payment_gateways;
				$gateways = wp_list_filter( $gateways, array( 'id' => $gateway_slug ) );
				$gateway  = ! empty( $gateways ) ? array_shift( $gateways ) : null;

				$this->gateways[ $gateway_slug ] = $gateway;
			}

			return isset( $this->gateways[ $gateway_slug ] ) ? $this->gateways[ $gateway_slug ] : null;
		}

		/**
		 * Checks if gateways should be considered enabled by default
		 * Each gateway may override this preset in its own settings.
		 *
		 * @return bool Whether gateways are enabled or not.
		 */
		public function are_enabled() {
			return yith_plugin_fw_is_true( get_option( 'yith_stripe_payments_enabled', 'yes' ) );
		}

		/**
		 * Returns value for a specific meta key set by one of the plugin's gateway inside an order
		 * If order wasn't paid with one of plugin's gateway, return false
		 *
		 * @param \WC_Order $order    Order object.
		 * @param string    $meta_key Meta to read.
		 *
		 * @return mixed|false Meta value
		 */
		public function get_order_meta( $order, $meta_key ) {
			if ( ! $order instanceof \WC_Order || ! $meta_key ) {
				return false;
			}

			$gateway = $this->get_gateway( $order->get_payment_method() );

			if ( ! $gateway ) {
				return false;
			}

			return $order->get_meta( $gateway->get_meta_key( $meta_key ) );
		}

		/**
		 * Sets value for a specific meta key, using one of plugin's gateway to generate meta key
		 * If order wasn't paid with one of plugin's gateway, does nothing
		 *
		 * @param \WC_Order $order      Order object.
		 * @param string    $meta_key   Meta to read.
		 * @param mixed     $meta_value Meta value to set.
		 */
		public function set_order_meta( $order, $meta_key, $meta_value ) {
			if ( ! $order instanceof \WC_Order || ! $meta_key ) {
				return;
			}

			$gateway = $this->get_gateway( $order->get_payment_method() );

			if ( ! $gateway ) {
				return;
			}

			$order->update_meta_data( $gateway->get_meta_key( $meta_key ), $meta_value );
		}

		/**
		 * Sorts the plugin gateways first in the list.
		 *
		 * @param int[] $gateways_order The gateway order.
		 *
		 * @return int[]
		 */
		public function set_gateways_on_top_of_list( $gateways_order ) {
			foreach ( $this->gateway_classes as $id => $class ) {
				if ( ! empty( $gateways_order ) && is_array( $gateways_order ) ) {
					$gateways_order[ $id ] = min( $gateways_order ) - 1;
				}
			}

			return $gateways_order;
		}
	}
}
