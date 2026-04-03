<?php
/**
 * Token model
 * Representation of Token instance and operations that can be performed over it on remote server
 *
 * @author  YITH
 * @package YITH\StripeClient\Models
 * @version 1.0.0
 */

namespace YITH\StripeClient\Models;

use YITH\StripeClient\Main;
use YITH\StripeClient\Models\Abstracts\Model;
use YITH\StripeClient\Traits\Object_Create;

defined( 'YITH_STRIPE_CLIENT_DIR' ) || exit;

if ( ! class_exists( 'YITH\StripeClient\Models\Token' ) ) {
	/**
	 * Representation of token instance
	 *
	 * @since 1.0.0
	 * @property string $auth        JWT token string.
	 * @property int    $exp         Expiration time for current token.
	 * @property int    $expires_in  Token lifetime (in seconds).
	 * @property string $verify_url  URL used for calls verification.
	 */
	class Token extends Model {

		use Object_Create;

		/**
		 * Endpoint related to this object on remote server
		 *
		 * @var string
		 */
		protected static $endpoint = ':env/:brand/token';

		/**
		 * Data structure {@see Model::$data_structure}
		 *
		 * @var array
		 */
		protected static $data_structure;

		/**
		 * Returns data structure describing current object
		 *
		 * @return array Data structure ({@see self::$data_structure} for more info).
		 */
		public static function get_data_structure() {
			if ( ! self::$data_structure ) {
				$rest_server = Main::instance()->get_api();

				self::$data_structure = array(
					'auth'      => array(
						'label'    => __( 'Authentication token', 'yith-stripe-client' ),
						'type'     => 'text',
						'required' => false,
						'default'  => null,
					),
					'exp'        => array(
						'label'    => __( 'Token expiration', 'yith-stripe-client' ),
						'type'     => 'number',
						'required' => false,
						'default'  => null,
					),
					'expires_in' => array(
						'label'    => __( 'Token lifetime', 'yith-stripe-client' ),
						'type'     => 'number',
						'required' => false,
						'default'  => null,
					),
					'site_url'          => array(
						'label'      => __( 'Site url', 'yith-stripe-client' ),
						'type'       => 'text',
						'required'   => false,
						'default'    => null,
						'validation' => 'url',
					),
					'verify_url' => array(
						'label'      => __( 'Verification url', 'yith-stripe-client' ),
						'type'       => 'text',
						'required'   => true,
						'default'    => $rest_server->get_controller( 'verify' )->get_rest_url(),
						'validation' => 'url',
					),
				);
			}

			return self::$data_structure;
		}
	}
}
