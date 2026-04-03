<?php
/**
 * Integrations list
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Booking
 */

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

return array(
	'bluehost' => array(
		'constant'          => 'BLUEHOST_PLUGIN_VERSION',
		'installed_version' => 'BLUEHOST_PLUGIN_VERSION',
		'min_version'       => '3.0.11',
	),
	'hostgator' => array(
		'constant'          => 'HOSTGATOR_PLUGIN_VERSION',
		'installed_version' => 'HOSTGATOR_PLUGIN_VERSION',
		'min_version'       => '2.6.1',
	),
);
