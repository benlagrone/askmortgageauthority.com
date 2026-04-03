<?php
/**
 * Public key methods
 * This static-only class offer an easy way to retrieve Stripe Public Key from the server
 * Anyway, it can't be instantiated to generate an object as the key is just a string and doesn't require a proper model.
 *
 * @author  YITH
 * @package YITH\StripeClient\Models
 * @version 1.0.0
 */

namespace YITH\StripeClient\Models;

use YITH\StripeClient\Models\Abstracts\Model;
use YITH\StripeClient\Traits\Object_Read;

defined( 'YITH_STRIPE_CLIENT_DIR' ) || exit;

if ( ! class_exists( 'YITH\StripeClient\Models\Public_Key' ) ) {
	/**
	 * Representation of token instance
	 *
	 * @since 1.0.0
	 * @property string $auth        JWT token string.
	 * @property int    $exp         Expiration time for current token.
	 * @property int    $expires_in  Token lifetime (in seconds).
	 * @property string $verify_url  URL used for calls verification.
	 */
	class Public_Key extends Model {

		use Object_Read;

		/**
		 * Endpoint related to this object on remote server
		 *
		 * @var string
		 */
		protected static $endpoint = ':env/:brand/public-key';

		/**
		 * Makes class non-instantiatable from outside.
		 */
		private function __construct() {
			// Do nothing on purpose; class cannot be instantiated.
		}

		/**
		 * Returns sanitized public key.
		 *
		 * @param array $raw Raw public key from the API.
		 * @return string
		 */
		protected static function get( $raw = false ) {
			return sanitize_text_field( wp_unslash( $raw ) );
		}


	}
}
