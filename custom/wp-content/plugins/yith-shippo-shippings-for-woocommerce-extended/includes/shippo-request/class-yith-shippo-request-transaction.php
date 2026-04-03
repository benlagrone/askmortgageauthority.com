<?php
/**
 * Class to manage the requests API to requests the transactions
 *
 * @class   YITH_Shippo_Request
 * @package YITH/Shippo/Request
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class YITH_Shippo_Request_Transaction
 */
class YITH_Shippo_Request_Transaction {

	use YITH_Shippo_Trait_Singleton;

	/**
	 * Create the label rate
	 *
	 * @param   string $rate      Rate.
	 * @param   string $metadata  Add metadata to the request.
	 *
	 * @return WP_Error|array
	 */
	public function create_label_rate( $rate, $metadata = '' ) {
		$label_format   = get_option( 'yith_shippo_shipping_label_format', 'PDF' );
		$error_messages = '';
		try {
			$args = array(
				'rate'            => $rate,
				'label_file_type' => $label_format,
				'async'           => false,
				'metadata'        => $metadata,
			);
			$this->logger->shipments( 'Call for transaction  ' . print_r( $args, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			$transaction = Shippo_Transaction::create( $args );

			if ( $transaction->messages ) {
				foreach ( $transaction->messages as $message ) {
					$message = json_decode( $message, true );
					if ( $message ) {
						$error_messages .= isset( $message['source'], $message['text'] ) ? $message['text'] : implode( ' ', $message );
					} else {
						$error_messages .= $message;
					}
				}
				$this->logger->shipments( 'Transaction error:  ' . $error_messages );
			}

			if ( 'SUCCESS' === $transaction->status ) {
				return $transaction->__toArray();
			} else {
				return new WP_Error( 'transaction_error', $error_messages );
			}
		} catch ( Exception $e ) {
			$this->logger->shipments( 'Error on create transaction : ' . $e->getMessage() );
			return new WP_Error( 'transaction_error', 'Error on create transaction : ' . $e->getMessage() );
		}

	}
}
