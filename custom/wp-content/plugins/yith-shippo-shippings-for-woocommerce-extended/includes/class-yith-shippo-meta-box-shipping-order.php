<?php
/**
 * This class manage the Shipping meta box in the order
 *
 * @package YITH\Shippo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * The order meta box
 */
class YITH_Shippo_Meta_Box_Shipping_Order {

	/**
	 * Show the shipping metabox
	 *
	 * @param WP_Post $post The post.
	 *
	 * @since 1.0.0
	 * @author YITH <plugins@yithemes.com>
	 */
	public static function output( $post ) {
		global $theorder;
		if( $theorder ){
			$order = $theorder;

			$args = array(
				'order'          => $order,
				'order_shipping' => yith_shippo_get_order_shipping( $order ),
				'extra_args'     => self::get_product_info( $order ),
			);

			yith_shippo_get_view( '/order-metabox/shipping-meta-box.php', $args );
		}

	}

	/**
	 * Get an array with all product in the order
	 *
	 * @param WC_Order $order The order.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public static function get_product_info( $order ) {

		$products   = array(
			'products_in_order' => array(),
			'product_info'      => array(),
			'total_items'       => 0,
		);
		$line_items = $order->get_items( 'line_item' );

		foreach ( $line_items as $item ) {

			$product = $item->get_product();
			if ( $product && $product->exists() && $product->needs_shipping() ) {
				$products['products_in_order'][ $product->get_id() ] = $product->get_name();
				$products['product_info'][ $product->get_id() ]      = array(
					'max_qty' => $item->get_quantity(),
					'cost'    => $order->get_item_subtotal( $item, false, true ),
					'weight'  => $product->get_weight(),
				);

				$products['total_items'] += $item->get_quantity();
			}
		}

		return $products;
	}
}
