<?php
/**
 * Customer events controller class
 */

namespace YITH\StripePayments\RestApi\Controllers;

use YITH\StripePayments\Models\Customer;
use YITH\StripePayments\RestApi\Controllers\Event_Controller;

if ( ! class_exists( 'YITH\StripePayments\RestApi\Controllers\Customer_Controller' ) ) {
	/**
	 * Stripe `customer` events controller
	 *
	 * @extends Event_Controller
	 */
	class Customer_Controller extends Event_Controller {

		/**
		 * The Stripe event type
		 *
		 * @var string
		 */
		protected $event = 'customer';

		/**
		 * The Stripe event type
		 *
		 * @var \WP_User
		 */
		protected $user = false;

		/**
		 * Handle `customer.deleted` event
		 *
		 * @return void
		 */
		protected function deleted() {
			if ( isset( $this->event_data['id'] ) ) {
				$deleted = delete_user_meta( $this->user->ID, Customer::get_user_meta_key(), $this->event_data['id'] );
				$this->add_response_action(
					array(
						'user_id'      => $this->user->ID,
						'cus_id'       => $this->event_data['id'],
						'meta_deleted' => $deleted,
					)
				);
			}
		}

		/**
		 * Populate the object properties using the request parameters
		 *
		 * @param array $params The request parameters.
		 */
		protected function populate( $params ) {
			parent::populate( $params );

			if ( isset( $this->event_data['metadata']['user_id'] ) ) {
				$this->user = get_user_by( 'id', absint( $this->event_data['metadata']['user_id'] ) );
			}
		}

		/**
		 * Check if the request is valid
		 *
		 * @return bool
		 */
		protected function is_valid() {
			return parent::is_valid() && $this->user;
		}

	}
}
