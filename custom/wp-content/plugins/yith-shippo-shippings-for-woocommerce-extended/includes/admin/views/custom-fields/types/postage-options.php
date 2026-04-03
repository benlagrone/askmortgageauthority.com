<?php
/**
 * Template file to Default postage options custom field
 *
 * @package YITH\Shippo\Views\Panel
 *
 * @var array $field The field array.
 */

defined( 'ABSPATH' ) || exit;
$default = yith_shippo_get_default_postage_options();

$services = array_merge( array( '' => esc_html_x( 'Select a service', 'Placeholder to invite the admin to select a shippo service', 'yith-shippo-shippings-for-woocommerce' ) ), yith_shippo_get_services_list() );

$values                 = get_option( $field['id'] );
$postage_options_fields = array(
	'shipment_description'  => array(
		'label' => __( 'Shipment description', 'yith-shippo-shippings-for-woocommerce' ),
		'type'  => 'text',
		'id'    => $field['id'] . '-shipment_description',
		'name'  => $field['id'] . '[shipment_description]',
		'value' => isset( $values['shipment_description'] ) ? $values['shipment_description'] : $default['shipment_description'],
	),
	'content'               => array(
		'label'   => __( 'Content', 'yith-shippo-shippings-for-woocommerce' ),
		'type'    => 'select',
		'id'      => $field['id'] . '-content',
		'name'    => $field['id'] . '[content]',
		'options' => yith_shippo_get_postage_content(),
		'class'   => 'wc-enhanced-select',
		'value'   => isset( $values['content'] ) ? $values['content'] : $default['content'],
	),
	'country-origin'        => array(
		'label'   => __( 'Country of origin', 'yith-shippo-shippings-for-woocommerce' ),
		'type'    => 'select',
		'id'      => $field['id'] . '-country-origin',
		'name'    => $field['id'] . '[country_origin]',
		'options' => WC()->countries->get_countries(),
		'class'   => 'wc-enhanced-select',
		'value'   => isset( $values['country_origin'] ) ? $values['country_origin'] : $default['country_origin'],
	),
	'domestic-service'      => array(
		'label'   => __( 'Domestic shipping service', 'yith-shippo-shippings-for-woocommerce' ),
		'type'    => 'select',
		'id'      => $field['id'] . '-domestic-service',
		'name'    => $field['id'] . '[domestic_service]',
		'options' => $services,
		'class'   => 'wc-enhanced-select',
		'value'   => isset( $values['domestic_service'] ) ? $values['domestic_service'] : '',
	),
	'international-service' => array(
		'label'   => __( 'International shipping service', 'yith-shippo-shippings-for-woocommerce' ),
		'type'    => 'select',
		'id'      => $field['id'] . '-international-service',
		'name'    => $field['id'] . '[international_service]',
		'class'   => 'wc-enhanced-select',
		'options' => $services,
		'value'   => isset( $values['international_service'] ) ? $values['international_service'] : '',
	),
	'tariff-number'         => array(
		'label' => __( 'Tariff number', 'yith-shippo-shippings-for-woocommerce' ),
		'type'  => 'text',
		'id'    => $field['id'] . '-tariff-number',
		'name'  => $field['id'] . '[tariff_number]',
		'value' => isset( $values['tariff_number'] ) ? $values['tariff_number'] : '',
	),
);

?>
<div id="<?php echo esc_attr( $field['id'] ); ?>" class="yith-plugin-fw-field-wrapper yith-plugin-fw-condition-rule-field-wrapper">

	<div class="parcel-boxes-section">
		<div class="parcel-boxes-elements">
			<label for="<?php echo esc_attr( $postage_options_fields['shipment_description']['id'] ); ?>"><?php echo esc_html( $postage_options_fields['shipment_description']['label'] ); ?></label>
			<?php yith_plugin_fw_get_field( $postage_options_fields['shipment_description'], true, false ); ?></div>
		<div class="parcel-boxes-elements">
			<label for="<?php echo esc_attr( $postage_options_fields['content']['id'] ); ?>"><?php echo esc_html( $postage_options_fields['content']['label'] ); ?></label>
			<?php yith_plugin_fw_get_field( $postage_options_fields['content'], true, false ); ?></div>
	</div>
	<div class="parcel-boxes-section">
		<div class="parcel-boxes-elements">
			<label for="<?php echo esc_attr( $postage_options_fields['country-origin']['id'] ); ?>"><?php echo esc_html( $postage_options_fields['country-origin']['label'] ); ?></label>
			<?php yith_plugin_fw_get_field( $postage_options_fields['country-origin'], true, false ); ?></div>
		<div class="parcel-boxes-elements">
			<label for="<?php echo esc_attr( $postage_options_fields['domestic-service']['id'] ); ?>"><?php echo esc_html( $postage_options_fields['domestic-service']['label'] ); ?></label>
			<?php yith_plugin_fw_get_field( $postage_options_fields['domestic-service'], true, false ); ?>
		</div>
	</div>
	<div class="parcel-boxes-section">
		<div class="parcel-boxes-elements">
			<label for="<?php echo esc_attr( $postage_options_fields['international-service']['id'] ); ?>"><?php echo esc_html( $postage_options_fields['international-service']['label'] ); ?></label>
			<?php yith_plugin_fw_get_field( $postage_options_fields['international-service'], true, false ); ?>
		</div>
		<div class="parcel-boxes-elements">
			<label for="<?php echo esc_attr( $postage_options_fields['tariff-number']['id'] ); ?>"><?php echo esc_html( $postage_options_fields['tariff-number']['label'] ); ?></label>
			<?php yith_plugin_fw_get_field( $postage_options_fields['tariff-number'], true, false ); ?>
		</div>
	</div>
</div>
