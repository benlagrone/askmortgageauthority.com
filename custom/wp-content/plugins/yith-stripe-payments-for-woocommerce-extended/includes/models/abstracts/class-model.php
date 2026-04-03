<?php
/**
 * Model class
 *
 * Offers a general description of the model of an Object, matching an item on Stripe remote server
 * Methods allow to retrieve, save and update the item on remote server, while keeping a temporary cache of the instances
 * already retrieved from server.
 *
 * @author  YITH
 * @package YITH\StripePayments\Models
 * @version 1.0.0
 */

namespace YITH\StripePayments\Models\Abstracts;

use YITH\StripePayments\Traits\Factory;
use YITH\StripePayments\Traits\Api_Client_Access;
use YITH\StripeClient\Models\Abstracts\Model as StripeModel;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Models\Abstracts\Model' ) ) {
	/**
	 * General description of the model of a Stripe object
	 * Extenders must implement specific methods to interact with APIs.
	 *
	 * @since 1.0.0
	 */
	abstract class Model {
		use Factory, Api_Client_Access;

		/**
		 * Unique ID of the intent on Stripe
		 *
		 * @var string
		 */
		protected $id;

		/**
		 * Object representing Intent on the library
		 *
		 * @var StripeModel
		 */
		protected $model;

		/**
		 * Array of internal properties, used to have base model to perform actions
		 *
		 * @var array
		 */
		protected $data = array();

		/**
		 * Constructor method
		 *
		 * @param string|array|StripeModel $data Data used to initialize object.
		 * @throws \Exception When object can't be retrieved from Stripe.
		 */
		protected function __construct( $data ) {
			if ( is_string( $data ) ) {
				$this->id = $data;
			} elseif ( $data instanceof StripeModel ) {
				$this->id    = $data->id;
				$this->model = $data;
			} else if ( is_array( $data ) ) {
				$this->set_props( $data );
			}

			if ( ! $this->id && $this->data ) {
				$this->create();
			}

			if ( ! $this->id ) {
				throw new \Exception( __( 'Couldn\'t instantiate model', 'yith-stripe-payments-for-woocommerce' ) );
			}
		}

		/*
		 |--------------------------------------------------------------------------
		 | Getters
		 |--------------------------------------------------------------------------
		 */

		/**
		 * Magic getter
		 *
		 * @param string $key Key to retrieve.
		 * @return mixed Property value, or null on failure.
		 */
		public function __get( $key ) {
			$this->read();

			if ( ! $this->model || ! isset( $this->model->{ $key } ) ) {
				return null;
			}

			return $this->model->{ $key };
		}

		/**
		 * Magic isset
		 *
		 * @param string $key Key to search.
		 * @return bool Whether property exists or not.
		 */
		public function __isset( $key ) {
			$this->read();

			return isset( $this->{ $key } ) || ( $this->model && isset( $this->model->{ $key } ) );
		}

		/**
		 * Returns instance of the Stripe object for current model
		 *
		 * @return StripeModel
		 */
		public function get_model() {
			$this->read();

			return $this->model;
		}

		/**
		 * Returns value of a specific prop for the object.
		 *
		 * @param string $prop    The prop name.
		 * @param mixed  $context The context.
		 *
		 * @return mixed|string
		 */
		protected function get_prop( $prop, $context = '' ) {
			$value = null;

			if ( array_key_exists( $prop, $this->data ) ) {
				$value = $this->data[ $prop ];

				if ( 'view' === $context ) {
					$value = apply_filters( 'yith_stripe_payments_model_' . $prop, $value, $this );
				}
			}

			return $value;
		}

		/*
		 |--------------------------------------------------------------------------
		 | Setters
		 |--------------------------------------------------------------------------
		 */

		/**
		 * Set properties
		 *
		 * @param array[] $props The properties.
		 *
		 * @return void
		 */
		protected function set_props( $props ) {
			if ( is_array( $props ) ) {
				foreach ( $props as $prop => $value ) {
					$this->set_prop( $prop, $value );
				}
			}
		}

		/**
		 * Set property
		 *
		 * @param string $prop  The property name.
		 * @param mixed  $value The property value.
		 *
		 * @return void
		 */
		protected function set_prop( $prop, $value ) {
			if ( 'user' === $prop && $value instanceof \WP_User ) {
				$this->user = $value;
			} else if ( array_key_exists( $prop, $this->data ) ) {
				$this->data[ $prop ] = $value;
			}
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
		 * @return Model|\WP_Error Created instance.
		 */
		abstract public function create( $args = array() );

		/**
		 * Reads object from remote server when needed, or when explicitly requested
		 *
		 * @param bool $force Whether to force re-read.
		 * @return Model|\WP_Error Current instance (for concatenation).
		 */
		abstract public function read( $force = false );

		/**
		 * Updates current intent
		 *
		 * @param array $args Arguments to use for update call.
		 * @return Model|\WP_Error Current instance (for concatenation).
		 */
		abstract public function update( $args = array() );

		/**
		 * Deletes an object on remote server
		 *
		 * @return bool Status of the operation
		 */
		abstract public function delete();
	}
}
