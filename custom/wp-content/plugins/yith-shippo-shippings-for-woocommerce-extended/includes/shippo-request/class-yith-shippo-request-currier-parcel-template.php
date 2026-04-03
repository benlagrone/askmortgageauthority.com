<?php
/**
 * Class to manage the requests API with Shippo Service
 *
 * @class   YITH_Shippo_Request
 * @package YITH/Shippo/Request
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class YITH_Shippo_Request_Currier_Parcel_Template
 */
class YITH_Shippo_Request_Currier_Parcel_Template {

	use YITH_Shippo_Trait_Singleton;

	/**
	 * Parcel template list
	 *
	 * @var array
	 */
	protected $parcel_templates;

	/**
	 * Return partcel templates
	 *
	 * @return array
	 */
	public function get_parcel_templates() {
		if ( empty( $this->parcel_templates ) ) {
			$transient_name         = 'yith_shippo_parcel_templates';
			$this->parcel_templates = get_transient( $transient_name );
			if ( false === $this->parcel_templates || ( empty( $this->parcel_templates ) && yith_shippo()->request->validate_token() ) ) {
				$this->parcel_templates = $this->request_templates();
				set_transient( $transient_name, $this->parcel_templates, DAY_IN_SECONDS );
			}
		}

		return $this->parcel_templates;
	}

	/**
	 * Return the currier parcel template
	 *
	 * @return array
	 */
	public function request_templates() {
		$page      = 1;
		$templates = array();
		try {
			if ( yith_shippo()->request->validate_token() ) {
				do {
					$parcel_templates = YITH_Shippo_API_Resource_Currier_Parcel_Template::all( array( 'page' => $page ) );
					if ( $parcel_templates->results ) {
						foreach ( $parcel_templates->results as $template ) {
							$templates[] = $template->__toArray();
						}
					}

					$page ++;

				} while ( isset( $parcel_templates->next ) && null !== $parcel_templates->next );
			}
		}catch ( Exception $e ){
			$this->logger->log( 'Error on return parcel template :' . $e->getMessage() );
		}

		return $templates;
	}

	/**
	 * Get parcel templates formatted
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_formatted_parcel_templates() {
		$formatted_templates    = array();
		$this->parcel_templates = $this->get_converted_parcel_templates();
		if ( $this->parcel_templates ) {
			foreach ( $this->parcel_templates as $template ) {
				$formatted_templates[ $template['token'] ] = $template['carrier'] . ' ' . $template['name'];
			}
			asort( $formatted_templates );
		}

		$formatted_templates = array_merge( array( 'parcel' => __( 'Parcel', 'yith-shippo-shippings-for-woocommerce' ) ), $formatted_templates );

		return $formatted_templates;
	}


	/**
	 * Get converted parcel templates
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_converted_parcel_templates() {
		$converted_templates = array();
		if ( $this->get_parcel_templates() ) {
			$converted_templates = yith_shippo_convert_parcel_templates( $this->get_parcel_templates() );
		}
		return $converted_templates;
	}
}


