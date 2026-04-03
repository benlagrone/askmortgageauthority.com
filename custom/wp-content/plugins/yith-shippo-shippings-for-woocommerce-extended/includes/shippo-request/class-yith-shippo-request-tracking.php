<?php
/**
 * Class to manage the requests API to requests the tracking information
 *
 * @class   YITH_Shippo_Request
 * @package YITH/Shippo/Request
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class YITH_Shippo_Request_Tracking
 */
class YITH_Shippo_Request_Tracking {

	use YITH_Shippo_Trait_Singleton;

	/**
	 * Return the status of the shipping
	 *
	 * @param string $carrier Carrier.
	 * @param string $tracking Tracking number.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function get_status( $carrier, $tracking ) {
		$this->logger->tracking( 'Checking the status of this tracking number  ' . $tracking . ' from this carrier ' . $carrier );
		$status_params = array(
			'id'      => $tracking,
			'carrier' => $carrier,
		);

		$result = Shippo_Track::get_status( $status_params );
		if ( $result instanceof Shippo_Object ) {
			$this->logger->tracking( 'Tracking results:  ' . print_r( $result, 1 ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			$tracking = yith_shippo()->request->format_object( $result );
			return $tracking;
		} else {
			$this->logger->tracking( 'Get tracking status error:  ' . $result );
			return false;
		}
	}

	/**
	 * Register the webhook for a specific tracking
	 *
	 * @param string $carrier Carrier.
	 * @param string $tracking Tracking code.
	 * @param string $metadata Metadata.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function register_webhook( $carrier, $tracking, $metadata ) {
		try {
			$args = array(
				'carrier'         => $carrier,
				'tracking_number' => $tracking,
				'metadata'        => $metadata,
			);

			$this->logger->tracking( 'Register webhook for  ' . print_r( $args, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

			$result = Shippo_Track::create( $args );
			if ( $result instanceof Shippo_Object ) {
				return true;
			} else {
				$this->logger->tracking( 'Register webhook error:  ' . $result );
				return false;
			}
		} catch ( Exception $e ) {
			$this->logger->tracking( 'Register webhook error  ' . $e->getMessage() );
			return false;
		}
	}
}
