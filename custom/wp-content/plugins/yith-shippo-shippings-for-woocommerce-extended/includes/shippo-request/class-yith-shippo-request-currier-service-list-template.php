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
 * Class YITH_Shippo_Request_Currier_Service_List_Template
 */
class YITH_Shippo_Request_Currier_Service_List_Template {

	use YITH_Shippo_Trait_Singleton;


	/**
	 * Currier service template list
	 *
	 * @var array
	 */
	protected $service_templates;

	/**
	 * Return partcel templates
	 *
	 * @return array
	 */
	public function get_currier_service_templates() {
		if ( empty( $this->service_templates ) ) {
			$transient_name          = 'yith_shippo_currier_service_list_templates';
			$this->service_templates = get_transient( $transient_name );
			if ( false === $this->service_templates || ( empty( $this->service_templates ) && yith_shippo()->request->validate_token() ) ) {
				$this->service_templates = $this->request_templates();
				set_transient( $transient_name, $this->service_templates, DAY_IN_SECONDS );
			}
		}

		return $this->service_templates;
	}

	/**
	 * Return the currier service template
	 *
	 * @return array
	 */
	public function request_templates() {
		$page      = 1;
		$templates = array();
		try {
			if ( yith_shippo()->request->validate_token() ) {
				do {
					$parcel_templates = Shippo_CarrierAccount::all(
						array(
							'service_levels' => true,
							'page'           => $page,
						)
					);
					if ( $parcel_templates->results ) {
						foreach ( $parcel_templates->results as $template ) {
							$templates[] = $template->__toArray();
						}
					}
					$page ++;
				} while ( null !== $parcel_templates->next );
			}
		} catch ( Exception $e ){
			$this->logger->log( 'Error on return carrier template :' . $e->getMessage() );
		}


		return $templates;
	}
}
