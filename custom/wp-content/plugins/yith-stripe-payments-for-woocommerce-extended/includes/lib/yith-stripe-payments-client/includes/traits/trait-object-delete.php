<?php
/**
 * Trait that adds method to delete an object on remote server
 *
 * @author  YITH
 * @package YITH\StripeClient\Traits
 * @version 2.0.0
 */

namespace YITH\StripeClient\Traits;

use YITH\StripeClient\Client;
use YITH\StripeClient\Models\Abstracts\Model;

defined( 'YITH_STRIPE_CLIENT_DIR' ) || exit;

if ( ! trait_exists( 'YITH\StripeClient\Traits\Object_Delete' ) ) {
	/**
	 * This class implements method to delete an object on remote server
	 *
	 * @since 1.0.0
	 */
	trait Object_Delete {

		/**
		 * Reads an object from the remote server, by calling endpoint on remote server with GET method
		 *
		 * @param string $id Unique id of the object to retrieve.
		 * @return bool Status of the operation.
		 * @throws \Exception When something goes wrong with the call to the server.
		 */
		public static function delete( $id ) {
			Client::call( 'DELETE', self::get_endpoint( $id ), array() );

			return true;
		}

	}
}
