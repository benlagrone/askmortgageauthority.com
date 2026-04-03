<?php
/**
 * HTML Template Email Shipment tracking
 *
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @version 1.0.0
 * @package YITH/Shippo/Templates
 *
 * @var WC_Order $order Order.
 * @var array    $email_heading Email heading.
 * @var string   $email Email object.
 * @var string   $email_description Email description.
 * @var string   $email_title Email title.
 * @var array    $tracking Tracking information.
 */

do_action( 'woocommerce_email_header', $email_heading, $email );
?>
<p><?php echo wp_kses_post( nl2br( $email_description ) ); ?>

<div class="table-wrapper">
	<?php
	wc_get_template(
		'emails/shipment-table.php',
		array(
			'order'    => $order,
			'tracking' => $tracking,
		),
		'',
		YITH_SHIPPO_TEMPLATE_PATH . '/'
	);
	?>
	<?php
	wc_get_template(
		'emails/shipment-products.php',
		array(
			'order'    => $order,
			'tracking' => $tracking,
		),
		'',
		YITH_SHIPPO_TEMPLATE_PATH . '/'
	);
	?>
</div>

<?php

do_action( 'woocommerce_email_footer', $email );
