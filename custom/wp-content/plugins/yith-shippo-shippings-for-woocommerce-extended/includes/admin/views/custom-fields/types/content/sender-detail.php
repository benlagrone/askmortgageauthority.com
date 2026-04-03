<?php
/**
 * This file show the sender details
 *
 * @package YITH\Shippo\Views\CustomFields
 *
 * @var array  $sender
 * @var string $shipping_zones
 * @var int    $key
 */

$curr_id       = isset( $is_placeholder ) && $is_placeholder ? '{{data.id}}' : $key;
$shipping_zone = (array) ( isset( $sender['shipping_zones'] ) ? maybe_unserialize( $sender['shipping_zones'] ) : array() );

$sender['shipping_zones'] = $shipping_zone;

$actions = array(
	'edit' => array(
		'type'  => 'action-button',
		'title' => _x( 'Edit', 'Tip to edit the sender info', 'yith-shippo-shippings-for-woocommerce' ),
		'icon'  => 'edit',
		'url'   => '',
		'class' => 'action__edit',
	),
);

$is_default = isset( $sender['default'] ) && $sender['default'];
if ( ! $is_default ) {
	$actions['delete'] = array(
		'type'   => 'action-button',
		'title'  => _x( 'Delete', 'Tip to delete the sender info', 'yith-shippo-shippings-for-woocommerce' ),
		'icon'   => 'trash',
		'url'    => '',
		'action' => 'delete',
		'class'  => 'action__trash',
	);
}
$class = $is_default ? 'is-default' : '';

$sender = array_map( 'wp_unslash', $sender );

?>
<tr class="yith-shippo-sender-info <?php echo esc_attr( $class ); ?> " data-id="<?php echo esc_attr( $curr_id ); ?>" data-item="<?php echo esc_attr( wp_json_encode( $sender ) ); ?>">
	<td class="name">
		<?php echo esc_html( $sender['name'] . ' ' . $sender['company'] ); ?>
	</td>
	<td class="shipping-zone">
		<?php if ( count( $shipping_zone ) > 0 ) : ?>
			<?php
			$zones = array();
			foreach ( $shipping_zone as $zone_id ) :
				$zone_id = $zone_id ?? 0;

				if ( isset( $shipping_zones[ $zone_id ] ) ) :
					$zones[] = $shipping_zones[ $zone_id ];
				endif;
			endforeach;
			?>
			<small><?php echo esc_html( implode( ', ', $zones ) ); ?></small>

			<?php
		else :

			?>

		<?php endif; ?>
	</td>
	<td class="actions">
		<?php yith_plugin_fw_get_action_buttons( $actions, true ); ?>
		<?php
		if ( ! $is_default ) :
			printf( '<a class="set-default" data-nonce="%s" data-key="%d" href="#">%s</a>', esc_html( wp_create_nonce( 'yith-shippo-set-default-sender' ) ), esc_html( $curr_id ), esc_html_x( 'set default', 'set sender as default', 'yith-shippo-shippings-for-woocommerce' ) );
		else :
			printf( '<div class="default-badge">%s</div>', esc_html_x( 'default', 'ADMIN badge that shows the default sender info', 'yith-shippo-shippings-for-woocommerce' ) );
		endif;
		?>
	</td>
</tr>
