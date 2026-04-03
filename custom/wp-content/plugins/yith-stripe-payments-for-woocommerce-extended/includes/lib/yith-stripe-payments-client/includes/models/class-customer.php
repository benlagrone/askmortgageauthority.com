<?php
/**
 * Customer model
 * Representation of Customer instance and operations that can be performed over it on remote server
 *
 * @author  YITH
 * @package YITH\StripeClient\Models
 * @version 1.0.0
 */

namespace YITH\StripeClient\Models;

use YITH\StripeClient\Client;
use YITH\StripeClient\Models\Abstracts\Model;
use YITH\StripeClient\Traits\Object_Read;
use YITH\StripeClient\Traits\Object_Create;
use YITH\StripeClient\Traits\Object_Update;

defined( 'YITH_STRIPE_CLIENT_DIR' ) || exit;

if ( ! class_exists( 'YITH\StripeClient\Models\Customer' ) ) {
	/**
	 * Representation of account instance
	 *
	 * @since 1.0.0
	 * @property string      $cus_id             Unique identifier of the customer on Stripe.
	 * @property string      $user_id            Unique identifier of the User ID on WP.
	 * @property string      $env                Environment where customer was created (live|test).
	 * @property null|string $email              An email address associated with the customer.
	 */
	class Customer extends Model {

		use Object_Read, Object_Create, Object_Update;

		/**
		 * Endpoint related to this object on remote server
		 *
		 * @var string
		 */
		protected static $endpoint = ':env/:brand/customer';

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
				self::$data_structure = array(
					'id'             => array(
						'label'    => __( 'Stripe customer id', 'yith-stripe-client' ),
						'type'     => 'text',
						'required' => false,
						'default'  => null,
					),
					'user_id'        => array(
						'label'    => __( 'User id', 'yith-stripe-client' ),
						'type'     => 'text',
						'required' => false,
						'default'  => null,
					),
					'env'            => array(
						'label'    => __( 'Customer environment', 'yith-stripe-client' ),
						'type'     => 'text',
						'required' => false,
						'default'  => Client::get_env(),
					),
					'email'          => array(
						'label'    => __( 'Customer email', 'yith-stripe-client' ),
						'type'     => 'email',
						'required' => false,
						'default'  => null,
					),
					'name'           => array(
						'label'    => __( 'Customer name', 'yith-stripe-client' ),
						'type'     => 'text',
						'required' => false,
						'default'  => null,
					),
					'description'    => array(
						'label'    => __( 'Description', 'yith-stripe-client' ),
						'type'     => 'text',
						'required' => false,
						'default'  => null,
					),
					'payment_method' => array(
						'label'    => __( 'Payment method', 'yith-stripe-client' ),
						'type'     => 'text',
						'required' => false,
						'default'  => null,
					),
					'phone'          => array(
						'label'    => __( 'Phone', 'yith-stripe-client' ),
						'type'     => 'text',
						'required' => false,
						'default'  => null,
					),
					'address'        => array(
						'label'    => __( 'Address', 'yith-stripe-client' ),
						'type'     => 'hash',
						'required' => false,
						'default'  => null,
					),
					'metadata'       => array(
						'label'    => __( 'Metadata', 'yith-stripe-client' ),
						'type'     => 'hash',
						'required' => false,
						'default'  => null,
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
		 * @return Model
		 * @throws \Exception When raw data passed do not match Model structure.
		 */
		protected static function get( $raw = array() ) {
			return parent::get( $raw );
		}
	}
}
