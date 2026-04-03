<?php
/**
 * Account model
 * Representation of Account instance and operations that can be performed over it on remote server
 *
 * @author  YITH
 * @package YITH\StripeClient\Models
 * @version 1.0.0
 */

namespace YITH\StripeClient\Models;

use YITH\StripeClient\Main;
use YITH\StripeClient\Client;
use YITH\StripeClient\Models\Abstracts\Model;
use YITH\StripeClient\Traits\Object_Read;
use YITH\StripeClient\Traits\Object_Create;
use YITH\StripeClient\Traits\Object_Delete;

defined( 'YITH_STRIPE_CLIENT_DIR' ) || exit;

if ( ! class_exists( 'YITH\StripeClient\Models\Account' ) ) {
	/**
	 * Representation of account instance
	 *
	 * @since 1.0.0
	 * @property string      $site_url                   URL registered for current account.
	 * @property string      $verify_url                 URL used for calls verification.
	 * @property string      $return_url                 URL where customer is redirected after onboarding process.
	 * @property string      $webhook_url                URL where account wants to receive webhooks.
	 * @property string      $acct_id                    Unique identifier of the account on Stripe.
	 * @property string      $secret                     Secret shared with the server and used to validate incoming webhooks.
	 * @property string      $env                        Environment where account was created (live|test).
	 * @property null|string $country                    The account's country.
	 * @property null|string $email                      An email address associated with the account. You can treat this as metadata: it is not used for authentication or messaging account holders.
	 * @property string      $onboard_link               Url to onboarding page.
	 * @property int         $onboard_exp                Expiration time for onboarding url.
	 * @property bool        $pmd_enabled                Whether the Payment Method Domain it's enabled.
	 * @property array       $pmd_statuses               The Payment Methods Domain statuses.
	 * @property bool        $charges_enabled            Whether the account can create live charges.
	 * @property bool        $details_submitted          Whether account details have been submitted. Standard accounts cannot receive payouts before this is true.
	 * @property Token       $token                      Object describing instance of temporary authentication token.
	 */
	class Account extends Model {

		use Object_Read, Object_Create, Object_Delete;

		/**
		 * Endpoint related to this object on remote server
		 *
		 * @var string
		 */
		protected static $endpoint = ':env/:brand/account';

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
					'site_url'          => array(
						'label'      => __( 'Site url', 'yith-stripe-client' ),
						'type'       => 'text',
						'required'   => true,
						'default'    => null,
						'validation' => 'url',
					),
					'acct_id'           => array(
						'label'    => __( 'Stripe account id', 'yith-stripe-client' ),
						'type'     => 'text',
						'required' => false,
						'default'  => null,
					),
					'country'           => array(
						'label'      => __( 'Site country', 'yith-stripe-client' ),
						'type'       => 'text',
						'required'   => false,
						'default'    => null,
						'validation' => 'country',
					),
					'env'               => array(
						'label'    => __( 'Account environment', 'yith-stripe-client' ),
						'type'     => 'text',
						'required' => false,
						'default'  => Client::get_env(),
					),
					'secret'            => array(
						'label'    => __( 'Account secret', 'yith-stripe-client' ),
						'type'     => 'text',
						'required' => false,
						'default'  => '',
					),
					'email'             => array(
						'label'    => __( 'Admin email', 'yith-stripe-client' ),
						'type'     => 'email',
						'required' => false,
						'default'  => null,
					),
					'onboard_link'      => array(
						'label'      => __( 'Onboarding link', 'yith-stripe-client' ),
						'type'       => 'text',
						'required'   => false,
						'default'    => null,
						'validation' => 'url',
					),
					'onboard_exp'       => array(
						'label'    => __( 'Onboarding link expiration', 'yith-stripe-client' ),
						'type'     => 'number',
						'required' => false,
						'default'  => null,
					),
					'pmd_enabled'       => array(
						'label'    => __( 'Domain enabled', 'yith-stripe-client' ),
						'type'     => 'bool',
						'required' => false,
						'default'  => false,
					),
					'pmd_statuses'      => array(
						'label'    => __( 'Payment Method statuses for Domain', 'yith-stripe-client' ),
						'type'     => 'hash',
						'required' => false,
						'default'  => false,
					),
					'charges_enabled'   => array(
						'label'    => __( '"Charges enabled" flag', 'yith-stripe-client' ),
						'type'     => 'bool',
						'required' => false,
						'default'  => false,
					),
					'details_submitted' => array(
						'label'    => __( '"Details submitted" flag', 'yith-stripe-client' ),
						'type'     => 'bool',
						'required' => false,
						'default'  => false,
					),
					'verify_url'        => array(
						'label'      => __( 'Verification url', 'yith-stripe-client' ),
						'type'       => 'text',
						'required'   => true,
						'default'    => $rest_server->get_controller( 'verify' )->get_rest_url(),
						'validation' => 'url',
					),
					'webhook_url'       => array(
						'label'      => __( 'Webhook url', 'yith-stripe-client' ),
						'type'       => 'text',
						'required'   => false,
						'default'    => '',
						'validation' => 'url',
					),
					'return_url'        => array(
						'label'      => __( 'Return url', 'yith-stripe-client' ),
						'type'       => 'text',
						'required'   => false,
						'default'    => null,
						'validation' => 'url',
					),
					'token'             => array(
						'auth' => array(
							'label'    => __( 'Authentication token', 'yith-stripe-client' ),
							'type'     => 'text',
							'required' => false,
							'default'  => null,
						),
						'exp'  => array(
							'label'    => __( 'Token expiration', 'yith-stripe-client' ),
							'type'     => 'number',
							'required' => false,
							'default'  => null,
						),
					),
				);
			}

			return self::$data_structure;
		}

		/**
		 * Returns an instance of current Model, populating it with data from $raw array passed
		 *
		 * @param array $raw Optional array of data used to populate model object.
		 *
		 * @return $this
		 * @throws \Exception When raw data passed do not match Model structure.
		 */
		protected static function get( $raw = array() ) {
			// build basic instance.
			$instance = parent::get( $raw );

			// add decoded token if available.
			$instance->token = self::get_token( $raw );

			return $instance;
		}

		/**
		 * Returns token object, populating it with data from $raw array passed
		 *
		 * @param array $raw Optional array of data used to populate model object.
		 *
		 * @return Model
		 * @throws \Exception When raw data passed do not match Model structure.
		 */
		protected static function get_token( $raw ) {
			if ( ! isset( $raw[ 'token' ] ) ) {
				return null;
			}

			try {
				return new Token( $raw[ 'token' ] );
			} catch ( \Exception $e ) {
				return null;
			}
		}
	}
}
