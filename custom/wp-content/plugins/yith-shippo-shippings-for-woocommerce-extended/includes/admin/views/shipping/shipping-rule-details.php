<?php
/**
 * This file manage the shipping rule details
 *
 * @package YITH\Shippo\Views
 * @author YITH <plugins@yithemes.com>
 * @version 1.0.0
 *
 * @var YITH_Shippo_Shipping_Rule $shipping_rule The rule.
 */

defined( 'ABSPATH' ) || exit;

$wp_list_url = add_query_arg(
	array(
		'page'    => 'yith_shippo_shipping_for_woocommerce',
		'tab'     => 'shipping',
		'sub_tab' => 'shipping-rules',
	),
	admin_url( 'admin.php' )
);

$currier_service_list = yith_shippo_get_service_options();
$heading_line_label   = _x( 'Add new rule', '[ADMIN] title to add a new shipping rule', 'yith-shippo-shippings-for-woocommerce' );

if ( isset( $_GET['shipping_rule_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$heading_line_label = _x( 'Edit rule', '[ADMIN] title to edit a new shipping rule', 'yith-shippo-shippings-for-woocommerce' );
}
?>
<div id="yith_shippo_shipping_rule_detail" class="yith-plugin-fw yit-admin-panel-container">
	<div class="yith-plugin-ui">
		<form method="post" id="plugin-fw-wc" autocomplete="off">
			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row">
						<div class="yith-plugin-fw__back-to-wp-list__wrapper">
							<a class="yith-plugin-fw__back-to-wp-list" href="<?php echo esc_url( $wp_list_url ); ?>">
								<?php echo esc_html_x( 'Back to rules list', '[ADMIN] Shipping rule details panel', 'yith-shippo-shippings-for-woocommerce' ); ?>
							</a>
						</div>
					</th>
					<td></td>
				</tr>
				<tr>
					<th scope="row">
						<h1 class="wp-heading-inline"><?php echo esc_html( $heading_line_label ); ?></h1>
					</th>
					<td></td>
				</tr>
				<tr class="yith-plugin-fw-panel-wc-row text yith-plugin-fw--required">
					<th scope="row" class="titledesc">
						<label for="yith_shippo_rule_name"><?php esc_html_e( 'Rule name', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
					</th>
					<td class="forminp forminp-text">
						<?php
						yith_plugin_fw_get_field(
							array(
								'type'  => 'text',
								'id'    => 'yith_shippo_rule_name',
								'name'  => 'yith_shippo_rule[name]',
								'value' => $shipping_rule->get_name(),
							),
							true
						);
						?>
						<span class="description"><?php esc_html_e( 'Enter a name to indentify this rule.', 'yith-shippo-shippings-for-woocommerce' ); ?></span>
					</td>
				</tr>
				<?php
				if ( yith_shippo_support_shipping_zones() ) :
					?>
					<tr class="yith-plugin-fw-panel-wc-row shipping-zones shipping-zones yith-plugin-fw--required">
						<th scope="row" class="titledesc">
							<label for="yith_shippo_rule_shipping_zone"><?php esc_html_e( 'Apply rule to zones:', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
						</th>
						<td class="forminp forminp-shipping-zones">
							<?php
							yith_plugin_fw_get_field(
								array(
									'type'     => 'shipping-zones',
									'id'       => 'yith_shippo_rule_shipping_zone',
									'name'     => 'yith_shippo_rule[shipping_zones]',
									'value'    => $shipping_rule->get_shipping_zones(),
									'multiple' => true,
								),
								true
							);
							?>
							<span class="description"><?php esc_html_e( 'Choose which shipping zone to apply this rule to.', 'yith-shippo-shippings-for-woocommerce' ); ?></span>
						</td>
					</tr>
					<?php
				endif;
				?>
				<tr class="yith-plugin-fw-panel-wc-row select">
					<th scope="row" class="titledesc">
						<label for="yith_shippo_rule_service"><?php esc_html_e( 'Apply rule to service:', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
					</th>
					<td class="forminp forminp-select">
						<?php
						yith_plugin_fw_get_field(
							array(
								'type'    => 'select',
								'id'      => 'yith_shippo_rule_service',
								'name'    => 'yith_shippo_rule[shipping_service]',
								'value'   => $shipping_rule->get_shipping_service(),
								'class'   => 'select wc-enhanced-select',
								'options' => $currier_service_list,
							),
							true
						);
						?>
						<span class="description">
							<?php
							// translators: Placeholders are html tags.
							echo wp_kses_post( sprintf( _x( 'Choose which shipping service to apply the rule to.%1$sNote:%2$s you can create only one rule for each shipping service.', 'Placeholders are html tags', 'yith-shippo-shippings-for-woocommerce' ), '<br><strong>', '</strong>' ) );
							?>
						</span>
					</td>
				</tr>
				<tr class="yith-plugin-fw-panel-wc-row onoff">
					<th scope="row" class="titledesc">
						<label for="yith_shippo_rule_set_conditions"><?php esc_html_e( 'Set conditions', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
					</th>
					<td class="forminp forminp-onoff">
						<?php
						yith_plugin_fw_get_field(
							array(
								'type'  => 'onoff',
								'id'    => 'yith_shippo_rule_set_conditions',
								'name'  => 'yith_shippo_rule[condition_enabled]',
								'value' => $shipping_rule->is_condition_enabled() ? 'yes' : 'no',
							),
							true
						);
						?>
						<span class="description"><?php esc_html_e( 'Enable to set in which conditions to show or hide the selected service.', 'yith-shippo-shippings-for-woocommerce' ); ?></span>
					</td>
				</tr>
				<tr class="yith-plugin-fw-panel-wc-row rule-condition yith-plugin-fw--required" data-dep-target="yith_shippo_rule_conditions" data-dep-id="yith_shippo_rule_set_conditions" data-dep-value="yes" data-dep-type="hide">
					<th scope="row" class="titledesc">
						<label for="yith_shippo_rule_set_conditions"><?php esc_html_e( 'Conditions', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
					</th>
					<td class="forminp forminp-rule-condition">
						<?php
						yith_plugin_fw_get_field(
							array(
								'type'           => 'condition-rule',
								'id'             => 'yith_shippo_rule_conditions',
								'name'           => 'yith_shippo_rule',
								'conditions'     => $shipping_rule->get_conditions_array(),
								'condition_type' => $shipping_rule->get_condition_type(),
								'condition_mode' => $shipping_rule->get_condition_mode(),
							),
							true
						);
						?>
					</td>
				</tr>
				<tr class="yith-plugin-fw-panel-wc-row onoff">
					<th scope="row" class="titledesc">
						<label for="yith_shippo_rule_fee_enabled"><?php esc_html_e( 'Add fee to shipping rates', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
					</th>
					<td class="forminp forminp-onoff">
						<?php
						yith_plugin_fw_get_field(
							array(
								'type'  => 'onoff',
								'id'    => 'yith_shippo_rule_fee_enabled',
								'name'  => 'yith_shippo_rule[fee_enabled]',
								'value' => $shipping_rule->is_fee_enabled() ? 'yes' : 'no',
							),
							true
						);
						?>
						<span class="description">
							<?php
							// translators: Placeholders are html tags.
							echo wp_kses_post( sprintf( _x( 'Enable to add an extra fee to the cost of this shipping service.%1$sNote:%2$s this option will override the default fee option in the %3$sShipping Rates%4$s tab.', 'yith-shippo-shippings-for-woocommerce' ), '<br><strong>', '</strong>', '<i>', '</i>' ) );
							?>
						</span>
					</td>
				</tr>
				<tr class="yith-plugin-fw-panel-wc-row fee-charge inline-fields yith-plugin-fw--required" data-dep-target="yith_shippo_rule_fee" data-dep-id="yith_shippo_rule_fee_enabled" data-dep-value="yes" data-dep-type="hide">
					<th scope="row" class="titledesc">
						<label for="yith_shippo_rule_fee"><?php esc_html_e( 'Fee to charge', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
					</th>
					<td class="forminp forminp-inline-fields">
						<?php
						yith_plugin_fw_get_field(
							array(
								'type'   => 'inline-fields',
								'id'     => 'yith_shippo_rule_fee',
								'name'   => 'yith_shippo_rule[fee]',
								'fields' => array(
									'fee_type'  => array(
										'type'    => 'select',
										'options' => array(
											'fixed'      => __( 'A fixed amount of ', 'yith-shippo-shippings-for-woocommerce' ),
											'percentage' => __( 'A percentage amount of', 'yith-shippo-shippings-for-woocommerce' ),
										),
										'default' => $shipping_rule->get_fee_type(),
									),
									'fee_value' => array(
										'type'    => 'text',
										'default' => $shipping_rule->get_fee_value(),
									),
									'html'      => array(
										'type' => 'html',
										'html' => '<div class="fixed">' . get_woocommerce_currency_symbol() . '</div><div class="percentage">%</div>',
									),
								),
							),
							true
						);
						?>
						<span class="description"><?php esc_html_e( 'Set the fee charge.', 'yith-shippo-shippings-for-woocommerce' ); ?></span>
					</td>
				</tr>

				<tr class="yith-plugin-fw-panel-wc-row onoff">
					<th scope="row" class="titledesc">
						<label for="yith_shippo_rule_label_enabled"><?php esc_html_e( 'Customize label', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
					</th>
					<td class="forminp forminp-onoff">
						<?php
						yith_plugin_fw_get_field(
							array(
								'type'  => 'onoff',
								'id'    => 'yith_shippo_rule_label_enabled',
								'name'  => 'yith_shippo_rule[label_enabled]',
								'value' => $shipping_rule->is_label_enabled() ? 'yes' : 'no',
							),
							true
						);
						?>
						<span class="description"><?php esc_html_e( 'Enable to set a custom label to show for this service.', 'yith-shippo-shippings-for-woocommerce' ); ?></span>
					</td>
				</tr>
				<tr class="yith-plugin-fw-panel-wc-row text yith-plugin-fw--required" data-dep-target="yith_shippo_rule_custom_label" data-dep-id="yith_shippo_rule_label_enabled" data-dep-value="yes" data-dep-type="hide">
					<th scope="row" class="titledesc">
						<label for="yith_shippo_rule_custom_label"><?php esc_html_e( 'Custom service label', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
					</th>
					<td class="forminp forminp-onoff">
						<?php
						yith_plugin_fw_get_field(
							array(
								'type'  => 'text',
								'id'    => 'yith_shippo_rule_custom_label',
								'name'  => 'yith_shippo_rule[custom_label]',
								'value' => $shipping_rule->get_custom_label(),
							),
							true
						);
						?>
						<span class="description"><?php esc_html_e( 'Set a custom label for this service.', 'yith-shippo-shippings-for-woocommerce' ); ?></span>
					</td>
				</tr>
				</tbody>
			</table>
			<p class="submit">
				<?php wp_nonce_field( 'yith_shippo_save_rule', 'yith_shippo_save_rule_nonce' ); ?>
				<input type="hidden" name="yith_shippo_rule_id" value="<?php echo esc_attr( $shipping_rule->get_id() ); ?>">
				<input class="button-primary" id="main-save-button" type="submit" value="<?php esc_html_e( 'Save rule', 'yith-shippo-shippings-for-woocommerce' ); ?>"/>
			</p>
		</form>
	</div>
</div>
