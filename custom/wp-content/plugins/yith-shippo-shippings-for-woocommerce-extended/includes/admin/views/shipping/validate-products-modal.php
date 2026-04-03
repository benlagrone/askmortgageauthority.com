<?php
/**
 * Validate Products modal
 *
 * @package YITH\Shippo\Views
 * @author YITH <plugins@yithemes.com>
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * APPLY_FILTERS: yith_shippo_product_validation_limit
 *
 * This filter allow to set limit of products on the product validation ajax process.
 *
 * @param int $limit The limit. Default: 20
 *
 * @return int
 */
$limit = apply_filters( 'yith_shippo_product_validation_limit', 20 );
?>

<div class="yith-shippo-validator-modal--overlay"></div>
<div class="yith-shippo-validator-modal">
	<div class="yith-shippo-validator-modal--title">
		<?php
		// translators: Products validation modal title.
		echo esc_html_x( 'Validate products', '[Admin] Validate products dimension modal title', 'yith-shippo-shippings-for-woocommerce' );
		?>
	</div>

	<div class="yith-shippo-success-validation">
		<div class="yith-shippo-success-validation--title">
			<?php // translators: Success title on Products validation modal. ?>
			<?php echo esc_html_x( 'Everything worked like a charm!', '[Admin] Success message in Products validation', 'yith-shippo-shippings-for-woocommerce' ); ?>
		</div>
		<div class="yith-shippo-success-validation--message">
			<?php // translators: Success message on Products validation modal. ?>
			<?php echo esc_html_x( 'All products are properly configured with weight and dimensions.', '[Admin] Success message in products validation', 'yith-shippo-shippings-for-woocommerce' ); ?>
		</div>
	</div>

	<div class="yith-shippo-failed-validation">
		<div class="yith-shippo-failed-validation--message">
			<?php
			echo wp_kses_post(
				sprintf(
				// translators: Failed message on Products validation modal. %s is the number of products.
					__( 'Warning: weight or dimensions missing for the following %s product(s). Please, fix:', 'yith-shippo-shippings-for-woocommerce' ),
					'<span class="yith-shippo-product-count"></span>'
				)
			);
			?>
		</div>
	</div>
	<div class="yith-shippo-failed-validation yith-shippo-failed-validation__list">
		<div class="yith-shippo-invalid-products-list"></div>
		<input type="hidden" class="yith-shippo-products-offset" data-offset="0" data-limit="<?php echo esc_attr( $limit ); ?>"/>
	</div>

	<div class="yith-shippo-modal-button yith-shippo-close-button">
		<?php // translators: Close button on Products validation modal. ?>
		<?php esc_html_e( 'Close', 'yith-shippo-shippings-for-woocommerce' ); ?>
	</div>

	<div class="yith-shippo-close">
		<i class="yith-icon yith-icon-close"></i>
	</div>

</div>
