<?php
/**
 * Sender info options
 *
 * @package YITH\Shippo\Options
 */

defined( 'ABSPATH' ) || exit;

$options = array(
	'shipping-sender-info' => array(
		'shipping-sender-info'             => array(
			'name'             => '',
			'desc'             => '',
			'id'               => 'yith_shippo_sender-info',
			'type'             => 'yith-field',
			'yith-type'        => 'sender-info',
			'yith-display-row' => false,
		),
	),
);

/**
 * APPLY_FILTERS: yith_shippo_sender_info_options
 *
 * This filter allow to manage the options on the "Sender info" tab.
 *
 * @param array $options List of options.
 *
 * @return array
 */
return apply_filters( 'yith_shippo_sender_info_options', $options );
