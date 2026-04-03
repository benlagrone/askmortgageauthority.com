<?php
/**
 * Singleton class trait.
 *
 * @package YITH\Shippo\Traits
 */

/**
 * Singleton trait.
 */
trait YITH_Shippo_Trait_Singleton {
	/**
	 * The single instance of the class.
	 *
	 * @var self
	 */
	protected static $instance = null;


	/**
	 * The logger
	 *
	 * @var YITH_Shippo_Logger
	 */
	protected $logger;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function __construct() {
		$this->logger = YITH_Shippo_Logger::get_instance();
	}

	/**
	 * Get class instance.
	 *
	 * @return self
	 */
	final public static function get_instance() {
		return ! is_null( static::$instance ) ? static::$instance : static::$instance = new static();
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {
	}

	/**
	 * Prevent un-serializing.
	 */
	public function __wakeup() {
		_doing_it_wrong( get_called_class(), 'Unserializing instances of this class is forbidden.', YITH_SHIPPO_VERSION ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
