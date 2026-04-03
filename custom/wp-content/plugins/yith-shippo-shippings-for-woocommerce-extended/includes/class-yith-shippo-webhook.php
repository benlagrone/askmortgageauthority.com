<?php
/**
 * Class to manage the webhook
 *
 * @class   YITH_Shippo_Webhook
 * @package YITH/Shippo
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;

/**
 * Assets Class
 *
 * @class   YITH_Shippo_Webhook
 * @package YITH/Shippo
 * @since   1.0.0
 */
class YITH_Shippo_Webhook {


	/**
	 * Single instance of the logger
	 *
	 * @var YITH_Shippo_Logger
	 */
	protected $logger;

	/**
	 * Page name
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected static $pagename;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		self::$pagename = apply_filters( 'yith_shippo_webhook_pagename', 'yith_shippo' );
		$this->logger   = YITH_Shippo_Logger::get_instance();
		add_action( 'woocommerce_api_' . self::$pagename, array( $this, 'handle_webhooks' ) );
	}

	/**
	 * Handle webhook
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function handle_webhooks() {
		$headers = getallheaders();
		$body        = @file_get_contents( 'php://input' ); //phpcs:ignore

		if ( $body ) {

			$body = json_decode( $body, true );
			if ( isset( $body['test'] ) && 1 === $body['test'] ) {
				$this->logger->tracking( '------ Received a Webhook TEST ------' );
			}

			$this->logger->tracking( '------ HEADER ------' );
			$this->logger->tracking( 'Registered webhook headers' . print_r( $headers, 1 ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			$this->logger->tracking( '------ BODY ------' );
			$this->logger->tracking( 'Registered webhook body' . print_r( $body, 1 ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

			$event_type = $body['event'];
			$resource   = $body['data'];

			switch ( $event_type ) {
				case 'track_updated':
					if ( 'yes' === get_option( 'yith_shippo_enable_autotracking', 'yes' ) ) {
						$this->handle_capture( $resource, 'track_updated' );
					}
					break;
			}
		}
	}

	/**
	 * Handle capture
	 *
	 * @param array  $resource Webhook content.
	 * @param string $callback Method to call after checked the webhook resource.
	 *
	 * @since 1.0.0
	 */
	public function handle_capture( $resource, $callback ) {
		if ( ! isset( $resource['tracking_number'] ) ) {
			$this->logger->tracking( 'The tracking number in not present inside the webhook content' );
			return false;
		}

		$this->$callback( $resource );
	}

	/**
	 * Register the update of the tracking
	 *
	 * @param array $resource Content data of webhook.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function track_updated( $resource ) {

		$tracking_number = $resource['tracking_number'];
		$trackings       = yith_shippo_get_trackings( array( 'tracking_number' => $tracking_number ) );
		if ( $trackings ) {
			foreach ( $trackings as $tracking ) {
				$this->logger( 'Webhook updating ' . $tracking->get_tracking_number() . ' for order ' . $tracking->get_order_id() . ' rate key ' . $tracking->get_rate_key() );
				$tracking->update_tracking_status( $resource );
			}
		}
	}

	/**
	 * Get webhook URL
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_webhook_url() {
		return apply_filters( 'yith_shippo_webhook_url', get_site_url( null, '', 'https' ) . '/?wc-api=' . self::$pagename );
	}
}
