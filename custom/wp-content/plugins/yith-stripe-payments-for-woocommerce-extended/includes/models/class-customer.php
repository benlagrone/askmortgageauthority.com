<?php
/**
 * Customer class
 *
 * THis class offers methods to connect plugin to YITH Stripe Server.
 *
 * @author  YITH
 * @package YITH\StripePayments\Models
 * @version 1.0.0
 */

namespace YITH\StripePayments\Models;

use YITH\StripePayments\Cache_Helper;
use YITH\StripePayments\Gateways\Abstracts\Gateway;
use YITH\StripePayments\Models\Abstracts\Model;
use YITH\StripeClient\Models\Customer as StripeCustomer;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Models\Customer' ) ) {
	/**
	 * Stripe Payments Customer
	 *
	 * @since 1.0.0
	 */
	class Customer extends Model {

		/**
		 * The user object
		 *
		 * @var \WP_User
		 */
		protected $user = null;

		/**
		 * The Stripe customer object
		 *
		 * @var StripeCustomer
		 */
		protected $model;

		/**
		 * Default data used for product initialization.
		 *
		 * @var array
		 */
		protected $data = array(
			'user_id'  => 0,
			'email'    => '',
			'name'     => '',
			'address'  => array(),
			'metadata' => array(),
		);

		/**
		 * Property changes.
		 *
		 * @var array
		 */
		protected $changes = array();

		/**
		 * Customer constructor
		 *
		 * @param string|int|array|\WP_User|StripeCustomer $data Data used to initialize object.
		 *
		 * @throws \Exception When the data provided are not valid to instance a Customer.
		 */
		protected function __construct( $data ) {
			if ( is_string( $data ) ) {
				$this->id = $data;
			} elseif ( is_numeric( $data ) ) {
				$this->data['user_id'] = $data;
			} elseif ( $data instanceof \WP_User ) {
				$this->data['user_id'] = $data->ID;
			} elseif ( is_array( $data ) ) {
				$this->set_props( $data );
			} elseif ( $data instanceof StripeCustomer ) {
				$this->id    = $data->id;
				$this->model = $data;
			}

			$this->maybe_init_user();

			if ( ! $this->id && $this->user ) {
				$this->create();
			}

			if ( ! $this->id ) {
				throw new \Exception( __( 'The data provided to instance the Customer are not valid.', 'yith-stripe-client' ) );
			}
		}

		/**
		 * Try to set the $user property using the user id
		 *
		 * @return void
		 */
		protected function maybe_init_user() {
			$user_id = $this->data['user_id'];
			if ( $user_id ) {
				$this->user = get_user_by( 'id', $user_id );
				$this->id   = get_user_meta( $user_id, self::get_user_meta_key(), true );
			}
			if ( $this->user ) {
				$this->data['name']  = "{$this->user->first_name} {$this->user->last_name}";
				$this->data['email'] = $this->user->user_email;
			}
		}

		/*
		|--------------------------------------------------------------------------
		| Getters
		|--------------------------------------------------------------------------
		 */

		/**
		 * Retrieves a customer starting from user data
		 *
		 * @param int|\WP_User $user_data Details about WP user that will be attached to Stripe customer.
		 *
		 * @return Customer|\WP_Error
		 * @throws \Exception When the data provided are not valid to instance a Customer.
		 */
		public static function get( $user_data ) {
			try {
				$customer = new self( $user_data );

				// create instance of this class and save it in local cache.
				self::$instances[ $customer->id ] = $customer;
			} catch ( \Exception $e ) {
				$customer = new \WP_Error( 'customer_creation_error', $e->getMessage() );
			}

			// return object instance.
			return $customer;
		}

		/**
		 * Returns customer id for current object
		 *
		 * @return string
		 */
		public function get_customer_id() {
			return $this->id;
		}

		/**
		 * Get the user meta key
		 *
		 * @return string
		 */
		public static function get_user_meta_key() {
			return Cache_Helper::get_account_key( 'customer_id' );
		}

		/*
		|--------------------------------------------------------------------------
		| Conditionals
		|--------------------------------------------------------------------------
		 */

		/**
		 * Check if the current Customer has a valid ID
		 *
		 * @return bool
		 */
		public function has_valid_customer_id() {
			return is_string( $this->id ) && 0 === strpos( $this->id, 'cus_' );
		}

		/*
		|--------------------------------------------------------------------------
		| CURD
		|--------------------------------------------------------------------------
		 */

		/**
		 * Create a new object on remote server, and returns instance.
		 *
		 * @param array $args Arguments to use for update call.
		 *
		 * @return Model|\WP_Error Created instance.
		 */
		public function create( $args = array() ) {
			$customer_data                         = $this->data;
			$customer_data['metadata']['instance'] = Gateway::get_instance();

			if ( isset( $customer_data['user_id'] ) ) {
				$customer_data['metadata']['user_id'] = $customer_data['user_id'];
				unset( $customer_data['user_id'] );
			}

			$customer = self::get_api()->create_customer(
				$this->user,
				apply_filters(
					'yith_stripe_payments_create_customer_extra_data',
					yith_stripe_payments_merge_recursive(
						$customer_data,
						$args
					),
					$this
				)
			);

			if ( is_wp_error( $customer ) ) {
				return $customer;
			}

			$this->id    = $customer->id;
			$this->model = $customer;

			update_user_meta( $this->user->ID, Cache_Helper::get_account_key( 'customer_id' ), $customer->id );

			return $this;
		}

		/**
		 * Reads object from remote server when needed, or when explicitly requested
		 *
		 * @param bool $force Whether to force re-read.
		 *
		 * @return Model|\WP_Error Current instance (for concatenation).
		 */
		public function read( $force = false ) {
			if ( is_null( $this->model ) || $force ) {
				$customer = $this->get_api()->get_customer( $this->id );

				if ( is_wp_error( $customer ) ) {
					return $customer;
				}

				$this->model = $customer;
			}

			return $this;
		}

		/**
		 * Updates current customer
		 *
		 * @param array $args Arguments to use for update call.
		 *
		 * @return Customer|\WP_Error Current instance (for concatenation).
		 */
		public function update( $args = array() ) {
			if ( $this->has_valid_customer_id() ) {
				$customer = self::get_api()->update_customer(
					$this->id,
					apply_filters(
						'yith_stripe_payments_update_customer_extra_data',
						yith_stripe_payments_merge_recursive(
							$this->data,
							$args
						),
						$this
					)
				);

				if ( is_wp_error( $customer ) ) {
					return $customer;
				} else {
					$this->model = $customer;
				}
			}

			return $this;
		}

		/**
		 * Deletes an object on remote server
		 *
		 * @throws \Exception Method is not implemented; do not use it.
		 */
		public function delete() {
			throw new \Exception( 'Delete method is not implemented for this object' );
		}
	}
}
