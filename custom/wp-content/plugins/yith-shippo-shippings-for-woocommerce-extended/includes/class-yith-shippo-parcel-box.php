<?php
/**
 * This class manage the object parcel boxes
 *
 * @package YITH\Shippo
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_Shippo_Abstract_Object', false ) ) {
	include_once YITH_SHIPPO_INC . '/abstracts/class-yith-shippo-abstract-object.php';
}

/**
 * The class
 */
class YITH_Shippo_Parcel_Box extends YITH_Shippo_Abstract_Object {
	/**
	 * Stores meta in cache for future reads.
	 *
	 * A group must be set to to enable caching.
	 *
	 * @var string
	 */
	protected $cache_group = 'shippo_parcel_boxes';

	/**
	 * The construct
	 *
	 * @param YITH_Shippo_Parcel_Box|int $parcel_box The parcel box.
	 *
	 * @throws Exception Throws an Exception.
	 */
	public function __construct( $parcel_box = 0 ) {
		$this->data = array(
			'name'           => '',
			'enabled'        => 1,
			'type'           => 'parcel',
			'weight'         => '',
			'width'          => '',
			'height'         => '',
			'length'         => '',
			'inner_padding'  => '',
			'max_weight'     => '',
			'distance_unit'  => get_option( 'woocommerce_dimension_unit' ),
			'weight_unit'    => get_option( 'woocommerce_weight_unit' ),
			'shipping_zones' => '',
		);

		$this->object_type = 'parcel_box';
		parent::__construct( $parcel_box );

		if ( is_numeric( $parcel_box ) && $parcel_box > 0 ) {
			$this->set_id( $parcel_box );
		} elseif ( $parcel_box instanceof self ) {
			$this->set_id( $parcel_box->get_id() );
		} else {
			$this->set_object_read();
		}

		$this->data_store = WC_Data_Store::load( 'shippo_parcel_box' );

		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/* === GETTERS === */

	/**
	 * Return name property for current parcel box
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return string Name property.
	 */
	public function get_name( $context = 'view' ) {
		return $this->get_prop( 'name', $context );
	}

	/**
	 * Return enabled property for current parcel box
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return int Enabled property.
	 */
	public function get_enabled( $context = 'view' ) {
		return (int) $this->get_prop( 'enabled', $context );
	}

	/**
	 * Return type property for current parcel box
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return string Type property.
	 */
	public function get_type( $context = 'view' ) {
		return $this->get_prop( 'type', $context );
	}

	/**
	 * Return weight property for current parcel box
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return string weight property.
	 */
	public function get_weight( $context = 'view' ) {
		return $this->get_prop( 'weight', $context );
	}

	/**
	 * Return width property for current parcel box
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return string Width property.
	 */
	public function get_width( $context = 'view' ) {
		return $this->get_prop( 'width', $context );
	}

	/**
	 * Return height property for current parcel box
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return string Height property.
	 */
	public function get_height( $context = 'view' ) {
		return $this->get_prop( 'height', $context );
	}

	/**
	 * Return length property for current parcel box
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return string Length property.
	 */
	public function get_length( $context = 'view' ) {
		return $this->get_prop( 'length', $context );
	}

	/**
	 * Return inner padding property for current parcel box
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return string Inner padding property.
	 */
	public function get_inner_padding( $context = 'view' ) {
		return $this->get_prop( 'inner_padding', $context );
	}

	/**
	 * Return maximum weight property for current parcel box
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return string Maximum weight property.
	 */
	public function get_max_weight( $context = 'view' ) {
		return $this->get_prop( 'max_weight', $context );
	}


	/**
	 * Return distance unit property for current parcel box
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return string Distance unit property.
	 */
	public function get_distance_unit( $context = 'view' ) {
		return $this->get_prop( 'distance_unit', $context );
	}


	/**
	 * Return weight unit property for current parcel box
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return string Weight unit property.
	 */
	public function get_weight_unit( $context = 'view' ) {
		return $this->get_prop( 'weight_unit', $context );
	}


	/**
	 * Return shipping zones property for current parcel box
	 *
	 * @param string $context Context of the operation.
	 *
	 * @return array Shipping zone property.
	 */
	public function get_shipping_zones( $context = 'view' ) {
		return (array) maybe_unserialize( $this->get_prop( 'shipping_zones', $context ) );
	}
	/* === SETTERS === */

	/**
	 * Set parcel box's name
	 *
	 * @param string $name Name for parcel box.
	 */
	public function set_name( $name ) {
		$this->set_prop( 'name', $name );
	}


	/**
	 * Set parcel box's enabled
	 *
	 * @param bool|int $enabled Status of parcel box.
	 */
	public function set_enabled( $enabled ) {
		$this->set_prop( 'enabled', intval( $enabled ) );
	}

	/**
	 * Set parcel box's type
	 *
	 * @param string $type Type of parcel box.
	 */
	public function set_type( $type ) {
		$this->set_prop( 'type', $type );
	}

	/**
	 * Set parcel box's weight
	 *
	 * @param string $weight Weight of parcel.
	 */
	public function set_weight( $weight ) {
		$this->set_prop( 'weight', $weight );
	}

	/**
	 * Set parcel box's width
	 *
	 * @param string $width Width of parcel.
	 */
	public function set_width( $width ) {
		$this->set_prop( 'width', $width );
	}

	/**
	 * Set parcel box's height
	 *
	 * @param string $height Height of parcel.
	 */
	public function set_height( $height ) {
		$this->set_prop( 'height', $height );
	}

	/**
	 * Set parcel box's length
	 *
	 * @param string $length Length of parcel.
	 */
	public function set_length( $length ) {
		$this->set_prop( 'length', $length );
	}

	/**
	 * Set parcel box's inner padding
	 *
	 * @param string $inner_padding Inner padding of parcel.
	 */
	public function set_inner_padding( $inner_padding ) {
		$this->set_prop( 'inner_padding', $inner_padding );
	}

	/**
	 * Set parcel box's max weight
	 *
	 * @param string $max_weight Max weight of parcel.
	 */
	public function set_max_weight( $max_weight ) {
		$this->set_prop( 'max_weight', $max_weight );
	}

	/**
	 * Set parcel box's distance unit
	 *
	 * @param string $distance_unit Distance unit of parcel.
	 */
	public function set_distance_unit( $distance_unit ) {
		$this->set_prop( 'distance_unit', $distance_unit );
	}

	/**
	 * Set parcel box's weight_unit
	 *
	 * @param string $weight_unit Weight unit of parcel.
	 */
	public function set_weight_unit( $weight_unit ) {
		$this->set_prop( 'weight_unit', $weight_unit );
	}

	/**
	 * Set parcel box's shipping zone
	 *
	 * @param array $shipping_zone Shipping zone.
	 */
	public function set_shipping_zones( $shipping_zone ) {
		$this->set_prop( 'shipping_zones', $shipping_zone );
	}

}
