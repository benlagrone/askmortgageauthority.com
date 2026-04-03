<?php
/**
 * Plain Shipment tracking template Email
 *
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @version 1.0.0
 * @package YITH/Shippo/Templates
 *
 * @var WC_Order $order Order.
 * @var array    $email_heading Email header
 * @var string   $email Email object.
 * @var string   $email_description Email description.
 * @var string   $email_title Title.
 * @var array    $tracking Traking information.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


echo wp_kses_post( nl2br( $email_description ) . "\n\n" );

// Include table .
wc_get_template(
	'emails/plain/shipment-table.php',
	array(
		'order'    => $order,
		'tracking' => $tracking,
	),
	'',
	YITH_SHIPPO_TEMPLATE_PATH . '/'
);
