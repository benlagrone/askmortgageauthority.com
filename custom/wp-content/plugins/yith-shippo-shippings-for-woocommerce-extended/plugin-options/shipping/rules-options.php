<?php
/**
 * This file contain the shipping rules options
 *
 * @package YITH\Shippo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * APPLY_FILTERS:  yith_shippo_shipping_rules_options
 *
 * This filter allow to add,remove or change the shipping rules options.
 *
 * @param array $shipping_rule_options
 *
 * @retrun array
 */

$url           = admin_url( 'admin.php' );
$url           = add_query_arg(
	array(
		'action'  => 'new_shipping_rule',
		'page'    => 'yith_shippo_shipping_for_woocommerce',
		'tab'     => 'shipping',
		'sub_tab' => 'shipping-rules',
	),
	$url
);
$count         = yith_shippo_get_shipping_rules( array( 'fields' => 'count' ) );
$no_item_class = 0 === $count ? 'no_item_found' : '';

return apply_filters(
	'yith_shippo_shipping_rules_options',
	array(
		'shipping-rules' => array(
			'shipping_rules_section_start' => array(
				'type' => 'title',
				'name' => '',
				'desc' => '',
			),
			'shipping-rules-list'          => array(
				'id'                   => 'yith_shippo_shipping_rule_table',
				'type'                 => 'yith-field',
				'class'                => 'yith-plugin-ui--boxed-wp-list-style ' . $no_item_class,
				'yith-type'            => 'list-table',
				'list_table_class'     => 'YITH_Shippo_Shipping_Rule_List_Table',
				'list_table_class_dir' => YITH_SHIPPO_DIR . 'admin/list-tables/class-yith-shippo-shipping-rule-list-table.php',
				'add_new_button'       => __( 'Add rule', 'yith-shippo-shippings-for-woocommerce' ),
				'add_new_url'          => $url,
			),
			'shipping_rules_section_end'   => array(
				'type' => 'sectionend',
			),
		),
	)
);
