<?php
/**
 * Integration model class
 *
 * @author  YITH
 * @package YITH\StripePayments\Integrations
 * @version 1.0.0
 */

namespace YITH\StripePayments\Integrations\Plugins\Abstracts;

use YITH\StripePayments\Cache_Helper;
use YITH\StripePayments\Integrations\Integration as Integration;
use YITH\StripePayments\Traits\Class_Scoped_Singleton;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Integrations\Plugins\Abstracts\NewFoldPluginIntegration' ) ) {
	abstract class NewFoldPluginIntegration extends Integration {
		use Class_Scoped_Singleton;

		/**
		 * @var string
		 */
		protected $plugin_id = '';

		/**
		 * NewFold plugin option for completed Stripe onboarding.
		 *
		 * @const string
		 */
		const ONBOARDING_COMPLETED_OPTION = 'nfd-ecommerce-captive-flow-stripe';

		/**
		 * Integration init
		 */
		protected function init() {
			if ( $this->plugin_id ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ), 15 );
				add_filter( 'yith_stripe_payments_init_onboarding', array( $this, 'maybe_add_plugin_panel_checks' ) );
				add_filter( 'yith_stripe_payments_enqueue_admin_scripts', array( $this, 'maybe_add_plugin_panel_checks' ) );

				$trigger_actions = array(
					'yith_stripe_payments_updated_environment',
					'yith_stripe_payments_account_updated',
					'yith_stripe_payments_invalidate_cache',
					'yith_stripe_payments_account_deleted',
				);

				foreach ( $trigger_actions as $action ) {
					add_action( $action, array( $this, 'maybe_update_onboarding_status' ) );
				}
			}
		}

		/**
		 * Check if all the Stripe Account details are submitted and update the onboarding status option according to it
		 */
		public function maybe_update_onboarding_status() {
			$option = get_option( Cache_Helper::get_site_key( 'connection_status' ), array() );
			if ( $option[ 'details_submitted' ] ?? false ) {
				update_option( self::ONBOARDING_COMPLETED_OPTION, 'true' );
			} else {
				delete_option( self::ONBOARDING_COMPLETED_OPTION );
			}
		}

		/**
		 * Enqueue script
		 */
		public function enqueue() {
			if ( $this->is_plugin_panel() ) {
				wp_enqueue_script( 'yith-stripe-payments-onboarding' );
			}
		}

		/**
		 * Add checks for plugin panel if needed
		 *
		 * @param bool $not_needed Whether the checks are needed.
		 *
		 * @return bool
		 */
		public function maybe_add_plugin_panel_checks( $not_needed ) {
			return $not_needed || $this->is_plugin_panel();
		}

		/**
		 * Whether is plugin panel or not
		 *
		 * @return bool
		 */
		public function is_plugin_panel( $default = false ) {
			$is_plugin_panel = $default;

			if ( did_action( 'current_screen' ) ) {
				$screen          = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
				$screen_id       = $screen ? $screen->id : false;
				$is_plugin_panel = "toplevel_page_{$this->plugin_id}" === $screen_id;
			} else {
				$is_plugin_panel = isset( $_GET[ 'page' ] ) && $this->plugin_id === $_GET[ 'page' ];
			}

			return ! ! apply_filters( "yith_stripe_payments_is_{$this->plugin_id}_panel", $is_plugin_panel );
		}
	}
}
