<?php
/**
 * Wrong Products template
 *
 * @package YITH\Shippo\Views
 * @author YITH <plugins@yithemes.com>
 * @version 1.0.0
 *
 * @var WC_Product $product Product.
 */

$product_id = $product instanceof WC_Product_Variation ? $product->get_parent_id() : $product->get_id();
?>

<div class="yith-shippo-invalid-product">
	<a target="_blank" href="<?php echo esc_url( get_edit_post_link( $product_id ) ); ?>">
		<div class="yith-shippo-invalid-product-thumbnail">
		<?php echo wp_kses_post( $product->get_image() ); ?>
		</div>
		<div class="yith-shippo-invalid-product-name">
		<?php echo esc_html( $product->get_name() ); ?>
		</div>
	</a>
</div>
