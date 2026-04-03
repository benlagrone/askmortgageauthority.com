<?php
/**
 * This file show the sender form template
 *
 * @package YITH\Shippo\Views\CustomFields
 *
 * @var array  $key
 * @var array  $single_sender
 * @var bool  $is_placeholder
 */
$key            = $is_placeholder ? '{{data.id}}' : $key;
$name           = $is_placeholder ? '{{data.name}}' : $single_sender['name'];
$company        = $is_placeholder ? '{{data.company}}' : $single_sender['company'];
$email          = $is_placeholder ? '{{data.email}}' : $single_sender['email'];
$phone          = $is_placeholder ? '{{data.phone}}' : $single_sender['phone'];
$use_wc_address = $is_placeholder ? '{{data.use_wc_address}}' : ( $single_sender['use_wc_address'] ?? 0 );
$address_1      = $is_placeholder ? '{{data.address_1}}' : $single_sender['address_1'];
$address_2      = $is_placeholder ? '{{data.address_2}}' : $single_sender['address_2'];
$city           = $is_placeholder ? '{{data.city}}' : $single_sender['city'];
$zip_code       = $is_placeholder ? '{{data.zip_code}}' : $single_sender['zip_code'];
$country_state  = $is_placeholder ? '{{data.country_state}}' : $single_sender['country_state'];
$shipping_zones = $is_placeholder ? '{{data.shipping_zones}}' : ( $single_sender['shipping_zones'] ?? array() );
$default        = $is_placeholder ? '{{data.default}}' : $single_sender['default'];

?>
<?php if ( $is_placeholder ) : ?>
<div id="yith-shippo-sender-info-modal">
	<form method="post" id="yith-shippo-add-sender">
		<div class="yith-shippo-address-validation-error"></div>
		<?php endif; ?>
		<div class="yith-plugin-fw-field-wrapper yith-plugin-fw-text-array-field-wrapper yith-shippo-sender-wrap">
				<div id="yith-shippo-sender-info" class="yith-shippo-sender-info-address yith-plugin-fw-text-array-inline">
					<div class="yith-single-text" >
						<label for="yith-shippo-sender-info-name"><?php echo esc_html_x( 'Name', 'ADMIN form label', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
						<?php
						yith_plugin_fw_get_field(
							array(
								'id'    => 'yith-shippo-sender-info-name',
								'name'  => 'yith-shippo-sender-info[' . $key . '][name]',
								'class' => 'yith-shippo-sender-name yith-shippo-validate',
								'type'  => 'text',
								'value' => $name,
							),
							true,
							false
						);
						?>
						<input type="hidden" id="default" name="yith-shippo-sender-info[<?php echo esc_attr( $key ); ?>][default]" value="<?php echo esc_attr( $default ); ?>"/>
						<input type="hidden" name='yith-shippo-sender-info-key' value="<?php echo esc_attr( $key ); ?>" />
					</div>
					<div class="yith-single-text" >
						<label for="yith-shippo-sender-info-company"><?php echo esc_html_x( 'Company', 'ADMIN form label', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
						<?php
						yith_plugin_fw_get_field(
							array(
								'id'    => 'yith-shippo-sender-info-company',
								'name'  => 'yith-shippo-sender-info[' . $key . '][company]',
								'class' => 'yith-shippo-sender-company',
								'type'  => 'text',
								'value' => $company,
							),
							true,
							false
						);
						?>
					</div>
					<div class="yith-single-text" >
						<label for="yith-shippo-sender-info-email"><?php echo esc_html_x( 'Email', 'ADMIN form label', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
						<?php
						yith_plugin_fw_get_field(
							array(
								'id'    => 'yith-shippo-sender-info-email',
								'name'  => 'yith-shippo-sender-info[' . $key . '][email]',
								'class' => 'yith-shippo-sender-email yith-shippo-validate yith-shippo-validate-email',
								'type'  => 'text',
								'value' => $email,
							),
							true,
							false
						);
						?>
					</div>
					<div class="yith-single-text" >
						<label for="yith-shippo-sender-info-phone"><?php echo esc_html_x( 'Phone', 'ADMIN form label', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
						<?php
						yith_plugin_fw_get_field(
							array(
								'id'    => 'yith-shippo-sender-info-phone',
								'name'  => 'yith-shippo-sender-info[' . $key . '][phone]',
								'class' => 'yith-shippo-sender-phone',
								'type'  => 'text',
								'value' => $phone,
							),
							true,
							false
						);
						?>
					</div>
				</div>
				<div class="inner-row yith-shippo-sender-checkbox">
					<?php
					yith_plugin_fw_get_field(
						array(
							'id'    => 'yith-shippo-sender-use_wc_address',
							'name'  => 'yith-shippo-sender-info[' . $key . '][use_wc_address]',
							'class' => 'yith-shippo-sender-use_wc_address',
							'type'  => 'checkbox',
							'value' => $use_wc_address,
						),
						true,
						false
					);
					?>
					<span><?php esc_html_e( 'Use the Store Address saved in WooCommerce > Settings', 'yith-shippo-shippings-for-woocommerce' ); ?></span>
				</div>
				<div class="yith-plugin-fw-text-array-inline yith-shippo-sender-info-address">
					<div class="yith-single-text" >
						<label for="yith-shippo-sender-info-address_1"><?php echo esc_html_x( 'Address line 1', 'ADMIN form label', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
						<?php
						yith_plugin_fw_get_field(
							array(
								'id'    => 'yith-shippo-sender-info-address_1',
								'name'  => 'yith-shippo-sender-info[' . $key . '][address_1]',
								'class' => 'yith-shippo-sender-address_1 yith-shippo-validate',
								'type'  => 'text',
								'value' => $address_1,
							),
							true,
							false
						);
						?>
					</div>
					<div class="yith-single-text" >
						<label for="yith-shippo-sender-info-address_2"><?php echo esc_html_x( 'Address line 2', 'ADMIN form label', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
						<?php
						yith_plugin_fw_get_field(
							array(
								'id'    => 'yith-shippo-sender-info-address_2',
								'name'  => 'yith-shippo-sender-info[' . $key . '][address_2]',
								'class' => 'yith-shippo-sender-address_2',
								'type'  => 'text',
								'value' => $address_2,
							),
							true,
							false
						);
						?>
					</div>
					<div class="yith-single-text" >
						<label for="yith-shippo-sender-info-city"><?php echo esc_html_x( 'City', 'ADMIN form label', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
						<?php
						yith_plugin_fw_get_field(
							array(
								'id'    => 'yith-shippo-sender-info-city',
								'name'  => 'yith-shippo-sender-info[' . $key . '][city]',
								'class' => 'yith-shippo-sender-city yith-shippo-validate',
								'type'  => 'text',
								'value' => $city,
							),
							true,
							false
						);
						?>
					</div>
					<div class="yith-single-text" >
						<label for="yith-shippo-sender-info-zip_code"><?php echo esc_html_x( 'ZIP/Postal Code', 'ADMIN form label', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
						<?php
						yith_plugin_fw_get_field(
							array(
								'id'    => 'yith-shippo-sender-info-zip_code',
								'name'  => 'yith-shippo-sender-info[' . $key . '][zip_code]',
								'class' => 'yith-shippo-sender-zip_code yith-shippo-validate',
								'type'  => 'text',
								'value' => $zip_code,
							),
							true,
							false
						);
						?>
					</div>
					</div>
			<div class="yith-plugin-fw-text-array-inline yith-shippo-sender-info-address yith-shippo-sender-info-address-last ">
					<div class="yith-single-text yith-single-text-wide" >
						<label for="yith-shippo-sender-info-country_state"><?php echo esc_html_x( 'Choose a country', 'ADMIN form label', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
						<?php
						yith_plugin_fw_get_field(
							array(
								'id'    => 'yith-shippo-sender-info-country_state',
								'name'  => 'yith-shippo-sender-info[' . $key . '][country_state]',
								'class' => 'yith-shippo-sender-country_state',
								'type'  => 'country-select',
								'value' =>  $country_state,
							),
							true,
							false
						);
						?>
					</div>

				</div>

				<?php if ( yith_shippo_support_shipping_zones() ) : ?>
				<div class="yith-plugin-fw-text-array-inline yith-shippo-sender-info-address yith-shippo-sender-info-address-last ">
					<div class="yith-single-text" >
						<label for="yith-shippo-sender-info-shipping_zones"><?php echo esc_html_x( 'Shipping zones', '[ADMIN] add parcel modal', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
						<?php
						yith_plugin_fw_get_field(
							array(
								'id'    => 'yith-shippo-sender-info-shipping_zones',
								'name'  => 'yith-shippo-sender-info[' . $key . '][shipping_zones]',
								'class' => 'yith-shippo-sender-shipping_zones',
								'type'  => 'shipping-zones',
								'value' => $shipping_zones,
							),
							true,
							false
						);
						?>
					</div>

				</div>
				<?php endif; ?>

		</div>
<?php if ( $is_placeholder ) : ?>
			<div class="form-row form-row-wide submit">
				<button class="submit button-primary" id="yith-shippo-save-sender">
					<# if ( data.edit ) { #>
					<?php echo esc_html_x( 'Save', '[ADMIN] save sender', 'yith-shippo-shippings-for-woocommerce' ); ?>
					<# } else { #>
					<?php echo esc_html_x( 'Add sender', '[ADMIN] add sender', 'yith-shippo-shippings-for-woocommerce' ); ?>
					<# } #>
				</button>
			</div>
			<input type="hidden" id="parcel-id" name="id" value="{{data.id}}"/>

		</div>
	</form>
</div>
<?php endif; ?>
