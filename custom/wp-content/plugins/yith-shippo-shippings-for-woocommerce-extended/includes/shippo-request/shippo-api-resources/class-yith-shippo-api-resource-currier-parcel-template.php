<?php
/**
 * Class to get the carrier parcel templates
 *
 * A carrier parcel template represents a package used for shipping that has preset dimensions defined by a carrier.
 *
 * @class   YITH_Shippo_API_Resource_Currier_Parcel_Template
 * @package YITH/Shippo/API/Resources
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class YITH_Shippo_API_Resource_Currier_Parcel_Template
 */
class YITH_Shippo_API_Resource_Currier_Parcel_Template extends Shippo_ApiResource {

	/**
	 * Class URL
	 *
	 * @param string $class Ignored.
	 *
	 * @return string
	 */
	public static function classUrl( $class ) {
		return '/parcel-templates';
	}

	/**
	 * Get all carrier parcel template
	 *
	 * @param array|null $params List of params.
	 * @param string     $api_key API key.
	 *
	 * @return Shippo_All Get all the carrier parcel templates.
	 */
	public static function all( $params = null, $api_key = null ) {
		$class = get_class();
		return self::_scopedAll( $class, $params, $api_key );
	}

}
