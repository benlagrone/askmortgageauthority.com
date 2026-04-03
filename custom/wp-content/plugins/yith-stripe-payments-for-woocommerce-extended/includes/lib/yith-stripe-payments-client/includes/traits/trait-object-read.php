<?php
/**
 * Trait that adds method to retrieve an object from remote server
 *
 * @author  YITH
 * @package YITH\StripeClient\Traits
 * @version 2.0.0
 */

namespace YITH\StripeClient\Traits;

use YITH\StripeClient\Client;
use YITH\StripeClient\Models\Abstracts\Model;

defined( 'YITH_STRIPE_CLIENT_DIR' ) || exit;

if ( ! trait_exists( 'YITH\StripeClient\Traits\Object_Read' ) ) {
	/**
	 * This class implements method to read an object from remote server
	 *
	 * @since 1.0.0
	 */
	trait Object_Read {

		/**
		 * Reads an object from the remote server, by calling endpoint on remote server with GET method
		 *
		 * @param string $id Unique id of the object to retrieve.
		 * @return $this Model object.
		 * @throws \Exception When something goes wrong with the call to the server.
		 */
		public static function read( $id = '' ) {
			return self::get( Client::call( 'GET', self::get_endpoint( $id ), array() ) );
		}

	}
}
