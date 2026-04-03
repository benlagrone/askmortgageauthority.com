<?php
/**
 * Trait that adds method to create an object against remote server
 *
 * @author  YITH
 * @package YITH\StripeClient\Traits
 * @version 2.0.0
 */

namespace YITH\StripeClient\Traits;

use YITH\StripeClient\Client;
use YITH\StripeClient\Models\Abstracts\Model;

defined( 'YITH_STRIPE_CLIENT_DIR' ) || exit;

if ( ! trait_exists( 'YITH\StripeClient\Traits\Object_Create' ) ) {
	/**
	 * This class implements method to create an object on remote server
	 *
	 * @since 1.0.0
	 */
	trait Object_Create {

		/**
		 * Creates an object on the remote server, by calling endpoint on remote server with POST method
		 *
		 * @param array $data Data used to create the object on remote server.
		 * @return $this Model object.
		 * @throws \Exception When something goes wrong with the call to the server.
		 */
		public static function create( $data ) {
			return self::get( Client::call( 'POST', self::get_endpoint(), self::get_data( $data ) ) );
		}

	}
}
