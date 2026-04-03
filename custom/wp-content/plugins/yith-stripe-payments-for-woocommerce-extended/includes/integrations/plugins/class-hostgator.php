<?php
/**
 * Integration model class
 *
 * @author  YITH
 * @package YITH\StripePayments\Integrations
 * @version 1.0.0
 */

namespace YITH\StripePayments\Integrations\Plugins;

use YITH\StripePayments\Integrations\Plugins\Abstracts\NewFoldPluginIntegration;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Integrations\Plugins\HostGator' ) ) {
	class HostGator extends NewFoldPluginIntegration {
		protected $plugin_id = 'hostgator';
	}
}
