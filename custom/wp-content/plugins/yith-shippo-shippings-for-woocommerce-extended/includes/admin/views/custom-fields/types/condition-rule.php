<?php
/**
 * This is a custom field, to manage the service condition rules
 *
 * @package YITH\Shippo\Views\Panel
 * @author YITH <plugins@yithemes.com>
 * @version 1.0.0
 *
 * @var array $field The field array.
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="<?php echo esc_attr( $field['id'] ); ?>" class="yith-plugin-fw-field-wrapper yith-plugin-fw-condition-rule-field-wrapper">
	<div id="yith-shippo-primary-condition-wrapper">
		<?php
		$condition_field = array(
			'type'   => 'inline-fields',
			'id'     => 'yith_shippo_primary_condition',
			'name'   => $field['name'],
			'fields' => array(
				'condition_type' => array(
					'type'    => 'select',
					'options' => array(
						'show' => __( 'Show', 'yith-shippo-shippings-for-woocommerce' ),
						'hide' => __( 'Hide', 'yith-shippo-shippings-for-woocommerce' ),
					),
					'default' => $field['condition_type'],
					'class'   => 'yith-shippo-condition-type',
				),
				'html'           => array(
					'type' => 'html',
					'html' => '<div class="condition-desc">' . esc_html__( 'Only if', 'yith-shippo-shippings-for-woocommerce' ) . '</div>',
				),
				'condition_mode' => array(
					'type'    => 'select',
					'options' => array(
						'and' => __( 'All these rules', 'yith-shippo-shippings-for-woocommerce' ),
						'or'  => __( 'Any of these rules', 'yith-shippo-shippings-for-woocommerce' ),
					),
					'default' => $field['condition_mode'],
				),
				'html2'          => array(
					'type' => 'html',
					'html' => '<div class="condition-desc">' . esc_html__( 'match:', 'yith-shippo-shippings-for-woocommerce' ) . '</div>',
				),
			),
		);
		yith_plugin_fw_get_field(
			$condition_field,
			true
		);
		?>
		<div id="yith-shippo-subconditions-wrapper">
			<?php
			$i = 0;
			?>
			<div class="yith-shippo-subcondition-list">
				<?php
				$conditions = isset( $field['conditions'] ) ? $field['conditions'] : array();
				foreach ( $conditions as $condition ) :
					?>
					<div class="yith-shippo-subcondition-row" data-index="<?php echo esc_attr( $i ); ?>">
						<?php
						$subcond_field = array(
							'type'   => 'inline-fields',
							'id'     => 'yith-shippo-subcondition-' . $i,
							'name'   => $field['name'] . '[conditions][condition_subtypes][' . $i . ']',
							'fields' => array(
								'subtype_type'       => array(
									'type'    => 'select',
									'options' => array(
										'product_ids' => __( 'Product', 'yith-shippo-shippings-for-woocommerce' ),
										'product_categories' => __( 'Product category', 'yith-shippo-shippings-for-woocommerce' ),
									),
									'default' => $condition['subtype_type'],
									'class'   => 'yith-shippo-subcondition-type',
								),
								'is_label'           => array(
									'type' => 'html',
									'html' => '<div class="yith_shippo_shipping_is_label">' . esc_html__( 'is', 'yith-shippo-shippings-for-woocommerce' ) . '</div>',
								),
								'product_ids'        => array(
									'type'     => 'ajax-products',
									'multiple' => true,
									'data'     => array(
										'action'   => 'woocommerce_json_search_products_and_variations',
										'security' => wp_create_nonce( 'search-products' ),
									),
									'default'  => isset( $condition['product_ids'] ) ? $condition['product_ids'] : array(),
								),
								'product_categories' => array(
									'type'     => 'ajax-terms',
									'data'     => array(
										'taxonomy'    => 'product_cat',
										'placeholder' => __( 'Search for a category...', 'yith-shippo-shippings-for-woocommerce' ),
									),
									'multiple' => true,
									'default'  => isset( $condition['product_categories'] ) ? $condition['product_categories'] : array(),
								),
								'remove_html'        => array(
									'type' => 'html',
									'html' => '<span class="yith-shippo-remove-rule yith-icon yith-icon-trash"></span>',
								),
							),
						);
						yith_plugin_fw_get_field(
							$subcond_field,
							true
						);
						?>
					</div>
					<?php
					$i ++;
				endforeach;
				?>
			</div>
		</div>
		<div class="yith-shippo-shipping-new-condition">
			<a href="#" id="yith_shippo_new_condition" class="yith_shippo_new_condition"><?php esc_html_e( '+ Add condition', 'yith-shippo-shippings-for-woocommerce' ); ?></a>
		</div>
	</div>
</div>

<script type="text/template" id="tmpl-yith-shppo-new-condition-row">
	<div class="yith-shippo-subcondition-row" data-index="{{{data.index}}}">
		<?php
		$subcond_field = array(
			'type'   => 'inline-fields',
			'id'     => 'yith-shippo-subcondition-{{{data.index}}}',
			'name'   => $field['name'] . '[conditions][condition_subtypes][{{{data.index}}}]',
			'fields' => array(
				'subtype_type'       => array(
					'type'    => 'select',
					'options' => array(
						'product_ids'        => __( 'Product', 'yith-shippo-shippings-for-woocommerce' ),
						'product_categories' => __( 'Product category', 'yith-shippo-shippings-for-woocommerce' ),
					),
					'default' => 'product_ids',
					'class'   => 'yith-shippo-subcondition-type',
				),
				'is_label'           => array(
					'type' => 'html',
					'html' => '<div class="yith_shippo_shipping_is_label">' . esc_html__( 'is', 'yith-shippo-shippings-for-woocommerce' ) . '</div>',
				),
				'product_ids'        => array(
					'type'     => 'ajax-products',
					'multiple' => true,
					'data'     => array(
						'action'   => 'woocommerce_json_search_products_and_variations',
						'security' => wp_create_nonce( 'search-products' ),
					),
				),
				'product_categories' => array(
					'type'     => 'ajax-terms',
					'data'     => array(
						'taxonomy'    => 'product_cat',
						'placeholder' => __( 'Search for a category...', 'yith-shippo-shippings-for-woocommerce' ),
					),
					'multiple' => true,
				),
				'remove_html'        => array(
					'type' => 'html',
					'html' => '<span class="yith-shippo-remove-rule yith-icon yith-icon-trash"></span>',
				),
			),
		);
		yith_plugin_fw_get_field(
			$subcond_field,
			true
		);
		?>
	</div>
</script>
