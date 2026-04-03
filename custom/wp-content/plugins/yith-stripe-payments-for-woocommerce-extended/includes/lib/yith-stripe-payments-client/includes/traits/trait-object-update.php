<?php
/**
 * Trait that adds method to update an object against remote server
 *
 * @author  YITH
 * @package YITH\StripeClient\Traits
 * @version 2.0.0
 */

namespace YITH\StripeClient\Traits;

use YITH\StripeClient\Client;
use YITH\StripeClient\Models\Abstracts\Model;

defined( 'YITH_STRIPE_CLIENT_DIR' ) || exit;

if ( ! trait_exists( 'YITH\StripeClient\Traits\Object_Update' ) ) {
	/**
	 * This class implements method to update an object on remote server
	 *
	 * @since 1.0.0
	 */
	trait Object_Update {

		/**
		 * Updates an existing object on the remote server, by calling endpoint on remote server with PUT method
		 *
		 * @param string $id   Unique id of the object to update.
		 * @param array  $data Data used to update the object on remote server.
		 * @return $this Model object.
		 * @throws \Exception When something goes wrong with the call to the server.
		 */
		public static function update( $id, $data ) {
			return self::get( Client::call( 'PUT', self::get_endpoint( $id ), self::get_data( $data ) ) );
		}

	}
}
