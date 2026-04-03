<?php
/**
 * Handle log action using WC_Logger.
 *
 * @package YITH\Shippo
 */

defined( 'ABSPATH' ) || exit;

/**
 * The class
 */
class YITH_Shippo_Logger {
	use YITH_Shippo_Trait_Singleton;

	/**
	 * WC logger instance
	 *
	 * @var WC_Logger
	 */
	public static $logger_object = null;


	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'init', array( __CLASS__, 'init' ), 30 );
	}


	/**
	 * Init the WC Logger
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public static function init() {
		if ( is_null( self::$logger_object ) && class_exists( 'WC_Logger' ) ) {
			self::$logger_object = new WC_Logger();
		}
	}

	/**
	 * Log the shipments actions
	 *
	 * @param string $message Message to log.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function shipments( $message ) {
		$this->log( $message, 'yith-shippo-shipments' );
	}

	/**
	 * Log the tracking actions and webhooks
	 *
	 * @param string $message Message to log.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function tracking( $message ) {
		$this->log( $message, 'yith-shippo-tracking' );
	}

	/**
	 * Log the address validation
	 *
	 * @param string $message Message to log.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function address( $message ) {
		$this->log( $message, 'yith-shippo-address' );
	}

	/**
	 * Log a message
	 *
	 * @param string $message The message to log.
	 * @param string $type The message type.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function log( $message, $type = 'yith-shippo' ) {
		self::init();

		if ( ! is_null( self::$logger_object ) ) {
			self::$logger_object->add(
				$type,
				$message
			);
		}
	}


}
