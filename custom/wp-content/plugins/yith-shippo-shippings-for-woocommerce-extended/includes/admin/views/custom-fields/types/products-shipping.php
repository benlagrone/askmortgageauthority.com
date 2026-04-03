<?php
/**
 * This is the product inline field for the shipping
 *
 * @package YITH\Shippo\CustomFields
 * @author YITH <plugins@yithemes.com>
 * @version 1.0.0
 *
 * @var array $field The field.
 */

defined( 'ABSPATH' ) || exit;

list( $field_id, $name, $products_in_parcel, $products_in_order, $show_only_all, $show_tariff, $currency, $index ) = yith_plugin_fw_extract( $field, 'id', 'name', 'products_in_parcel', 'products_in_order', 'show_all_products', 'show_tariff_number', 'currency', 'index' );
$all                       = array(
	'all' => __( 'All', 'yith-shippo-shippings-for-woocommerce' ),
);
$select_options            = $all + $products_in_order;
$international_field_class = ! $show_tariff ? 'hide' : '';
$default_opt               = get_option( 'yith_shippo_default_postage_options', yith_shippo_get_default_postage_options() );
if ( $show_only_all ) {
	$products_in_parcel = array(
		'all' => array(
			'qty'   => '',
			'value' => '',
		),
	);
}

?>
<div id="<?php echo esc_attr( $field_id ); ?>" class="yith-plugin-fw-field-wrapper yith-plugin-fw-product-list-field-wrapper">
	<div class="yith-shippo-producs-shipping-list">
		<?php
		$i = 0;
		foreach ( $products_in_parcel as $product_id => $config ) :

			?>
			<div class="yith-shippo-product-shipping--row" data-subindex="<?php echo esc_attr( $i ); ?>">
				<?php
				$field_args = array(
					'type'   => 'inline-fields',
					'id'     => $field_id . '-' . $i,
					'name'   => $name . '[products_in_shipping][' . $i . ']',
					'fields' => array(
						'product'        => array(
							'type'    => 'select',
							'class'   => 'yith-shippo-product-list',
							'options' => ( $i > 0 || $show_tariff ) ? $products_in_order : $select_options,
							'default' => $product_id,
							'label'   => __( 'Products to include in this shipping:', 'yith-shippo-shippings-for-woocommerce' ),
						),
						'country_origin' => array(
							'type'    => 'select',
							'class'   => 'wc-enhanced-select yith-country-origin ' . $international_field_class,
							'default' => isset( $config['country_origin'] ) ? $config['country_origin'] : $default_opt['country_origin'],
							'options' => WC()->countries->get_countries(),
							'label'   => __( 'Country of origin', 'yith-shippo-shippings-for-woocommerce' ),
						),
						'tariff_number'  => array(
							'type'    => 'text',
							'class'   => 'yith-tariff-number ' . $international_field_class,
							'default' => isset( $config['tariff_number'] ) ? $config['tariff_number'] : $default_opt['tariff_number'],
							'label'   => __( 'Tariff number', 'yith-shippo-shippings-for-woocommerce' ),
						),
						'qty'            => array(
							'type'    => 'number',
							'class'   => 'yith-product-qty yith-shippo-small-text',
							'default' => isset( $config['qty'] ) ? $config['qty'] : 1,
							'label'   => __( 'Qty', 'yith-shippo-shippings-for-woocommerce' ),
							'min'     => 1,
							'step'    => 1,
						),
						'value'          => array(
							'type'    => 'text',
							'class'   => 'yith-product-price yith-shippo-small-text',
							'default' => isset( $config['value'] ) ? $config['value'] : '',
							'label'   => __( 'Value', 'yith-shippo-shippings-for-woocommerce' ),
						),
						'html'           => array(
							'type' => 'html',
							'html' => '<div class="fixed yith_shippo_currency">' . get_woocommerce_currency_symbol( $currency ) . '</div>',
						),
						'remove_html'    => array(
							'type' => 'html',
							'html' => '<span class="yith-shippo-remove-rule yith-icon yith-icon-trash"></span>',
						),
					),
				);
				yith_plugin_fw_get_field( $field_args, true );
				?>
			</div>
			<?php
			$i ++;
		endforeach;
		?>
	</div>
	<div class="yith-shippo-new-product">
		<a href="#" id="yith_shippo_new_product_<?php echo esc_attr( $index ); ?>" class="yith_shippo_new_product"><?php esc_html_e( '+ Add product', 'yith-shippo-shippings-for-woocommerce' ); ?></a>
	</div>
</div>

<script type="text/html" id="tmpl-yith-shippo-product-list">
	<div class="yith-shippo-product-shipping--row" data-subindex="{{{data.index}}}">
		<?php
		$default    = array_keys( $products_in_order );
		$default    = $default[0];
		$field_args = array(
			'type'   => 'inline-fields',
			'id'     => $field_id . '-{{{data.index}}}',
			'name'   => $name . '[products_in_shipping][{{{data.index}}}]',
			'fields' => array(
				'product'        => array(
					'type'    => 'select',
					'class'   => 'yith-shippo-product-list',
					'options' => $products_in_order,
					'default' => $default,
					'label'   => __( 'Products to include in this shipping:', 'yith-shippo-shippings-for-woocommerce' ),
				),
				'country_origin' => array(
					'type'    => 'select',
					'class'   => 'wc-enhanced-select yith-country-origin ' . $international_field_class,
					'default' => $default_opt['country_origin'],
					'options' => WC()->countries->get_countries(),
					'label'   => __( 'Country of origin', 'yith-shippo-shippings-for-woocommerce' ),
				),
				'tariff_number'  => array(
					'type'    => 'text',
					'class'   => 'yith-tariff-number ' . $international_field_class,
					'default' => $default_opt['tariff_number'],
					'label'   => __( 'Tariff number', 'yith-shippo-shippings-for-woocommerce' ),
				),
				'qty'            => array(
					'type'    => 'number',
					'class'   => 'yith-product-qty yith-shippo-small-text',
					'default' => '',
					'label'   => __( 'Qty', 'yith-shippo-shippings-for-woocommerce' ),
					'min'     => 1,
					'step'    => 1,
				),
				'value'          => array(
					'type'    => 'text',
					'class'   => 'yith-product-price yith-shippo-small-text',
					'default' => '',
					'label'   => __( 'Value', 'yith-shippo-shippings-for-woocommerce' ),
				),
				'html'           => array(
					'type' => 'html',
					'html' => '<div class="fixed yith_shippo_currency">' . get_woocommerce_currency_symbol( $currency ) . '</div>',
				),
				'remove_html'    => array(
					'type' => 'html',
					'html' => '<span class="yith-shippo-remove-rule yith-icon yith-icon-trash"></span>',
				),
			),
		);
		yith_plugin_fw_get_field( $field_args, true );
		?>
	</div>
</script>
