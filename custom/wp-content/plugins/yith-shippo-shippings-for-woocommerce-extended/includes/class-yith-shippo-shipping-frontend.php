<?php
/**
 * This class manage the frontend features
 *
 * @package YITH\Shippo
 * @author YITH <plugins@yithemes.com>
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The class
 */
class YITH_Shippo_Shipping_Frontend {

	/**
	 * The logger
	 *
	 * @var YITH_Shippo_Logger
	 */
	protected $logger;

	/**
	 * The construct
	 */
	public function __construct() {

		$this->logger = YITH_Shippo_Logger::get_instance();
		// My account page.
		add_action( 'woocommerce_order_details_after_order_table_items', array( $this, 'add_shipping_details' ) );
		add_filter( 'woocommerce_order_shipping_method', array( $this, 'hide_admin_shipping_method' ), 10, 2 );

		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_address_on_checkout' ), 10, 2 );
		add_filter( 'woocommerce_billing_fields', array( $this, 'maybe_company_field_is_required' ) );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'maybe_company_field_is_required' ) );

		// I need to invalid the package cache in checkout page.
		add_filter( 'woocommerce_cart_shipping_packages', array( $this, 'force_calculate_packages' ), 99 );

	}

	/**
	 * Invalid the rate cache
	 *
	 * @param array $packages The packages.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function force_calculate_packages( $packages ) {
		$invalidate_cache = WC()->session->get( 'yith_shippo_cache_invalidated', false );
		if ( is_checkout() ) {
			if ( ! $invalidate_cache ) {
				foreach ( $packages as &$package ) {
					$package['rate_cache'] = wp_rand();
				}
			}
			WC()->session->set( 'yith_shippo_cache_invalidated', true );
		} else {
			WC()->session->set( 'yith_shippo_cache_invalidated', false );
		}

		return $packages;
	}

	/**
	 * Validate the address before creating the order
	 *
	 * @param array    $data An array of posted data.
	 * @param WP_Error $errors Validation errors.
	 *
	 * @return void
	 */
	public function validate_address_on_checkout( $data, $errors ) {

		if ( 'yes' === get_option( 'yith-shippo-validate-shipping-adress', 'yes' ) && yith_shippo()->request->validate_token() ) {
			$address    = yith_shippo_get_customer_address();
			$validation = yith_shippo()->request->address_validation->validate_customer_address( $address );
			$is_valid   = $validation['is_valid'] ?? false;
			if ( ! $is_valid ) {
				// translators: Error shown in the checkout page when the address validation fails.
				$default_message = __( 'There is an error in address validation.', 'yith-shippo-shippings-for-woocommerce' );
				$error_message   = ! empty( $validation['message'] ) ? $validation['message'] : '';
				$this->logger->address( 'Address validation error messages: ' . print_r( $error_message, 1 ) );  //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

				if ( ! empty( $error_message ) ) {
					$error_message = '<b>' . $default_message . '</b> <span class="yith-shippo-validation-address-message">(' . $error_message . ')</span>';
					$errors->add( 'validation', $error_message );
				}
			}
		}
	}


	/**
	 * Check if company field should be required.
	 *
	 * @param array $fields The billing fields.
	 *
	 * @return array
	 */
	public function maybe_company_field_is_required( $fields ) {
		if ( 'yes' === get_option( 'yith-shippo-require-company-name', 'no' ) ) {
			if ( isset( $fields['billing_company']['required'] ) ) {
				$fields['billing_company']['required'] = true;
			}
			if ( isset( $fields['shipping_company']['required'] ) ) {
				$fields['shipping_company']['required'] = true;
			}
		}

		return $fields;
	}

	/**
	 * Add shipping details table on my account page.
	 *
	 * @param object $order The order object.
	 *
	 * @since 1.0.0
	 */
	public function add_shipping_details( $order ) {
		$order_shipping = yith_shippo_get_order_shipping( $order->get_id() );
		wc_get_template(
			'my-account/shipping-table.php',
			array(
				'order'          => $order,
				'order_shipping' => $order_shipping,
			),
			'',
			YITH_SHIPPO_TEMPLATE_PATH . '/'
		);

	}

	/**
	 * If the order has a shippo shipping created by admin, hide it
	 *
	 * @param string   $shipping_name The shipping name list.
	 * @param WC_Order $order The order.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function hide_admin_shipping_method( $shipping_name, $order ) {
		$order_shipping = yith_shippo_get_order_shipping( $order );
		if ( $order_shipping instanceof YITH_Shippo_Order_Shipping && 'admin' === $order_shipping->get_created_via() ) {
			$names = array();
			foreach ( $order->get_shipping_methods() as $shipping_method ) {

				if ( $order_shipping->get_id() !== $shipping_method->get_id() ) {
					$names[] = $shipping_method->get_name();
				}
			}
			$shipping_name = implode( ', ', $names );
		}

		return $shipping_name;
	}

}
