<?php
/**
 * Template file to Sender info custom field
 *
 * @package YITH\Shippo\Views\Panel
 */

defined( 'ABSPATH' ) || exit;
$default_senders = yith_shippo_get_default_sender_info();
$sender_info     = get_option( 'yith-shippo-sender-info' );

if ( empty( $sender_info ) || ! is_array( $sender_info ) ) {
	$default_senders[0]['default'] = 1;
	$sender_info                   = $default_senders;
}

foreach ( $sender_info as $key => $info ) {
	if ( isset( $info['use_wc_address'] ) && $info['use_wc_address'] ) {
		$sender_info[ $key ] = wp_parse_args($info, $default_senders[0]);
	}
}
$shipping_zones = yith_shippo()->get_shipping_zones();
if ( yith_shippo_support_shipping_zones() ) : ?>
	<div class="yith-shippo-sender-info yith-plugin-ui--boxed-wp-list-style" data-senders="<?php echo esc_attr( wp_json_encode( $sender_info ) ); ?>">
		<?php wp_nonce_field( 'yith_shippo_edit_sender_info', '_yith_shippo_nonce' ); ?>
		<table id="sender-info" class="wp-list-table fixed table-view-list">
			<thead>
			<tr>
				<th id="name" class="colum-title"><?php echo esc_html_x( 'Sender', 'Name of sender', 'yith-shippo-shippings-for-woocommerce' ); ?></th>
				<th id="shipping-zone" class="colum-title"><?php echo esc_html_x( 'Shipping Zone', 'list of shipping zone where the sender is activated', 'yith-shippo-shippings-for-woocommerce' ); ?></th>
				<th id="actions" class="colum-title"></th>
			</tr>
			</thead>
			<tbody>
			<?php
			if ( $sender_info ) {
				foreach ( $sender_info as $key => $sender ) {
					yith_shippo_get_view(
						'/custom-fields/types/content/sender-detail.php',
						array_merge(
							array(
								'sender'         => $sender,
								'shipping_zones' => $shipping_zones,
								'key'            => $key,
							)
						)
					);
				}
			}

			?>
			</tbody>
		</table>
		<div class="yith-shippo-add-sender-wrapper">
			<button class="yith-shippo-add-sender button-primary"><?php echo esc_html_x( '+ Add sender', 'Add sender button label', 'yith-shippo-shippings-for-woocommerce' ); ?></button>
		</div>

	</div>
	<?php
else :
	$key = false;
	// get the default sender or the first occurrence.
	foreach ( $sender_info as $index => $single_sender ) {
		if ( $single_sender['default'] ) {
			$default_sender = $single_sender;
			$key            = $index;
			break;
		}
	}

	if ( ! $key ) {
		foreach ( $sender_info as $index => $single_sender ) {
				$default_sender = $single_sender;
				$key            = $index;
				break;
		}
	}

	$template_options = array_merge(
		array(
			'single_sender'  => $default_sender,
			'is_placeholder' => false,
			'key'            => $key,
		)
	);

	?>
	<p class="single-sender-info description">
		<?php
		printf(
			esc_html_x(
				'Set the default address of the place from where parcels are going to be shipped. It will be printed in shipping labels.',
				'description of "Sender info" section',
				'yith-shippo-shippings-for-woocommerce'
			),
			'<br>'
		);
		?>
	</p>
	<div class="yith-shippo-address-validation-error"></div>
	<div class="single-sender-info-wrapper" >
		<div scope="row" class="titledesc">
			<label for="yith-shippo-sender-info"><?php echo esc_html_x( 'Set sender info', 'ADMIN sender info label', 'yith-shippo-shippings-for-woocommerce' ); ?></label>
		</div>
		<div class="forminp forminp-text-array">
			<?php yith_shippo_get_view( '/custom-fields/types/content/sender-form.php', $template_options ); ?>
		</div>
	</div>
<?php endif; ?>


<script type="text/html" id="tmpl-yith-shippo-sender">
	<?php
	$template_options = array_merge(
		array(
			'single_sender'  => $default_senders[0],
			'is_placeholder' => true,
		)
	);
	yith_shippo_get_view( '/custom-fields/types/content/sender-form.php', $template_options );
	?>
</script>
