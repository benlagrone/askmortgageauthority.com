<?php
/**
 * Customers handler class
 *
 * THis class handle the synchronization between the Customers information between WP and Stripe.
 *
 * @author  YITH
 * @package YITH\StripePayments\Classes
 * @version 1.0.0
 */

namespace YITH\StripePayments;

use YITH\StripePayments\Models\Customer as Customer;
use YITH\StripePayments\Traits\Singleton;

defined( 'YITH_STRIPE_PAYMENTS' ) || exit;

if ( ! class_exists( 'YITH\StripePayments\Customers' ) ) {
	/**
	 * Stripe Payments Account
	 * Represents connection status with the server, and offers methods to connect and re-auth when needed
	 *
	 * @since 1.0.0
	 */
	class Customers {

		use Singleton;

		const CUSTOMER_DATA_FINGERPRINT_OPTION = 'yith_stripe_payments_customer_data_fingerprint';

		/**
		 * YITH\StripePayments\Customers constructor
		 */
		protected function __construct() {
			add_action( 'profile_update', array( $this, 'handle_user_update' ), 10 );
		}

		public function handle_user_update( $user_id ) {
			$customer = new \WC_Customer( $user_id );

			if ( $customer->get_id() ) {
				$customer_data = array(
					'name'    => $customer->get_first_name() . ' ' . $customer->get_last_name(),
					'email'   => $customer->get_email(),
					'phone'   => $customer->get_billing_phone(),
					'address' => array(
						'line1'       => $customer->get_billing_address(),
						'line2'       => $customer->get_billing_address_2(),
						'city'        => $customer->get_billing_city(),
						'country'     => $customer->get_billing_country(),
						'postal_code' => $customer->get_billing_postcode(),
						'state'       => $customer->get_billing_state(),
					),
				);

				$data_fingerprint     = md5( wp_json_encode( $customer_data ) );
				$customer_fingerprint = $customer->get_meta( self::CUSTOMER_DATA_FINGERPRINT_OPTION );

				if ( $data_fingerprint === $customer_fingerprint ) {
					return;
				}

				$stripe_customer = Customer::get( $customer->get_id() );

				if ( ! is_wp_error( $stripe_customer ) ) {
					$stripe_customer->update( $customer_data );
					update_user_meta( $customer->get_id(), self::CUSTOMER_DATA_FINGERPRINT_OPTION, $data_fingerprint );
				}
			}
		}
	}
}
