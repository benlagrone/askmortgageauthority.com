<?php
/**
 * Class to manage the requests API of Address Validation with Shippo Service
 *
 * @class   YITH_Shippo_Request
 * @package YITH/Shippo/Request
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class YITH_Shippo_Request_Address_Validation
 */
class YITH_Shippo_Request_Address_Validation {

	use YITH_Shippo_Trait_Singleton, YITH_Shippo_Trait_Cached_Request;

	/**
	 * Validate an address
	 *
	 * @param array $address The address of the customer before doing the checkout.
	 *
	 * @return array
	 */
	public function validate_owner_address( $address ) {

		$result = array(
			'is_valid'  => false,
			'message'   => '',
			'object_id' => '',
		);

		$stored_address = $this->get_transient( 'address_validated', $address );
		$this->logger->address( 'Try to validate the address' );
		if (true ) {
			$address_args             = $address;
			$address_args['validate'] = true;
			try {
				$valid = Shippo_Address::create( $address_args );

				if ( empty( $valid->validation_results ) || $valid->validation_results->is_valid ) {
					$result['is_valid'] = true;
					if ( $valid->object_id ) {
						$result['object_id'] = $valid->object_id;

						$this->set_transient( 'address_object_id', $valid->object_id, MONTH_IN_SECONDS, $address );
						$this->set_transient( 'address_validated', $address, MONTH_IN_SECONDS, $address );
					}
				} else {
					$result['message'] = $valid->validation_results->messages[0]->text;
				}
			} catch ( Shippo_InvalidRequestError $e ) {
				$messages          = $e->getJsonBody();
				$result['message'] = '';
				foreach ( $messages as $item ) {
					$result['message'] .= implode( ',', $item ) . '<br>';
				}
				$this->logger->address( 'Address validation error messages: ' . print_r( $e->getMessage(), 1 ) );  //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			}
		} else {
			$result['object_id'] = $this->get_transient( 'address_object_id', $address );
			$result['is_valid']  = true;
		}

		return $result;
	}

	/**
	 * Validate the customer address
	 *
	 * @param array $address The address.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function validate_customer_address( $address ) {

			$result = array(
				'is_valid'  => true,
				'message'   => '',
				'object_id' => '',
			);
			if ( yith_shippo()->request->validate_token() ) {
				$stored_address_id = $this->get_transient( 'customer_address_object_id', $address );
				$this->logger->address( 'Check for customer address validation ' . print_r( $address, true ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
				if ( false === $stored_address_id ) {
					$address_args             = $address;
					$address_args['validate'] = true;
					$this->logger->address( 'Try to validate the address' );
					try {
						$valid = Shippo_Address::create( $address_args );
						if ( $valid->validation_results ) {
							$result['is_valid'] = $valid->validation_results->is_valid;
							if ( ! $valid->validation_results->is_valid ) {
								$result['message'] = $valid->validation_results->messages[0]->text;
								$this->logger->address( 'Address validation error messages: ' . $result['message'] );
							}
						}
						if ( $result['is_valid'] && $valid->object_id ) {
							$result['object_id'] = $valid->object_id;
							$this->logger->address( 'The address is valid: store ' . $valid->object_id . ' in the cache' );
							$this->set_transient( 'customer_address_object_id', $valid->object_id, MONTH_IN_SECONDS, $address );
						}
					} catch ( Shippo_InvalidRequestError $e ) {
						$messages          = $e->getJsonBody();
						$result['message'] = '';
						foreach ( $messages as $item ) {
							$result['message'] .= implode( ',', $item ) . '<br>';
						}
						$this->logger->address( 'Address validation error messages: ' . print_r( $e->getMessage(), 1 ) );  //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
					}
				} else {
					$result['object_id'] = $stored_address_id;
					$this->logger->address( 'A valid address found: ' . $stored_address_id . ' use it' );
				}
			}

			return $result;
	}

	/**
	 * Get the cached ( if exsist ) customer address
	 *
	 * @param array $address The address.
	 *
	 * @return string|array
	 * @since 1.0.0
	 */
	public function get_cached_customer_address( $address ) {
		$stored_address = $this->get_transient( 'customer_address_object_id', $address );
		if ( false === $stored_address ) {
			$stored_address = $address;
		}

		return $stored_address;

	}

	/**
	 * Get the cached ( if exsist ) owner address
	 *
	 * @param array $address The address.
	 *
	 * @return string|array
	 * @since 1.0.0
	 */
	public function get_cached_owner_address( $address ) {
		$stored_address = $this->get_transient( 'address_object_id', $address );
		if ( false === $stored_address ) {
			$stored_address = $address;
		}

		return $stored_address;
	}

	/**
	 * Validate an existing address
	 *
	 * @param string $id The id of the existing address.
	 *
	 * @return Shippo_Validate
	 */
	public function validate_existing_address( $id ) {

		$valid = Shippo_Address::validate( $id );

		return $valid;
	}

}
