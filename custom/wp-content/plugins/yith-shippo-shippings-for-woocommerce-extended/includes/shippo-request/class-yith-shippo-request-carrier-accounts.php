<?php
/**
 * Class to manage the requests API of Carrier Accounts.
 *
 * @class   YITH_Shippo_Request
 * @package YITH/Shippo/Request
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class YITH_Shippo_Request_Carrier_Accounts
 */
class YITH_Shippo_Request_Carrier_Accounts {

	use YITH_Shippo_Trait_Singleton;

	/**
	 * Get all carrier accounts
	 *
	 * @param int $page The current page.
	 *
	 * @return object
	 */
	public function get_all_carrier_accounts( $page = 1 ) {

		return Shippo_CarrierAccount::all(
			array(
				'page' => $page,
			)
		);
	}
}
