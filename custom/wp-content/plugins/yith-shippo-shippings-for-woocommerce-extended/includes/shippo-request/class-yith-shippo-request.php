<?php
/**
 * Class to manage the requests API with Shippo Service
 *
 * @class   YITH_Shippo_Request
 * @package YITH/Shippo/Request
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class YITH_Shippo_Request
 */
class YITH_Shippo_Request {

	/**
	 * The logger
	 *
	 * @var YITH_Shippo_Logger
	 */
	protected $logger;

	/**
	 * Single instance of the class
	 *
	 * @var YITH_Shippo_Request
	 */
	private $token;

	/**
	 * Admin message error
	 *
	 * @var string
	 */
	private $message = '';
	

	/**
	 * Constructor
	 */
	public function __construct() {
		require_once YITH_SHIPPO_DIR . 'vendor/autoload.php';
	}

	/**
	 * Return the token to connect Shippo via API
	 *
	 * @return string
	 */
	public function get_token() {
		if ( empty( $this->token ) ) {
			$this->token = $this->is_sandbox() ? get_option( 'yith_shippo_sandbox_token', '' ) : get_option( 'yith_shippo_live_token', '' );
		}

		return $this->token;
	}

	/**
	 * Check if the sanbox is enabled.
	 *
	 * @return bool
	 */
	public function is_sandbox() {
		$is_sandbox = 'sandbox' === get_option( 'yith_shippo_environment', 'live' );

		/**
		 * APPLY_FILTERS: yith_shippo_is_sandbox
		 *
		 * This filter allow to set if the sandbox environment is enabled.
		 *
		 * @param bool $is_sandbox Sandbox status.
		 *
		 * @return bool
		 */
		return apply_filters( 'yith_shippo_is_sandbox', $is_sandbox );
	}

	/**
	 * Connect the request to the specific class
	 *
	 * @param mixed $reference The reference to call.
	 *
	 * @return mixed
	 */
	public function __get( $reference ) {
		$this->connect();
		$class_name = 'YITH_Shippo_Request_' . yith_shippo_camel_case( $reference );

		return $class_name::get_instance();
	}

	/**
	 * Return the Currier parcel template object
	 *
	 * @return YITH_Shippo_Request_Currier_Parcel_Template
	 */
	public function get_currier_parcel_template() {
		return $this->__get('currier_parcel_template');
	}


	/**
	 * Return the request tracking object
	 *
	 * @return YITH_Shippo_Request_Tracking
	 */
	public function get_tracking() {
		return $this->__get( 'tracking');
	}

	/**
	 * Return the request transaction object
	 *
	 * @return YITH_Shippo_Request_Transaction
	 */
	public function get_transaction() {
		return $this->__get('transaction');
	}


	/**
	 * Connect Shippo Object
	 *
	 * Set the API key to Shippo Object that will be ready to
	 *
	 * @return void
	 */
	public function connect() {
		Shippo::setApiKey( $this->get_token() );

	}

	/**
	 * Recursive method to format all Shippo_Object elements
	 *
	 * @param Shippo_Object|array $object Object or array to format.
	 *
	 * @return array|mixed
	 * @since 1.0.0
	 */
	public function format_object( $object ) {
		$array_obj = $object;
		if ( $object instanceof Shippo_Object ) {
			$array_obj = $object->__toArray();
		}

		if ( is_array( $array_obj ) ) {
			foreach ( $array_obj as $key => $value ) {
				$array_obj[ $key ] = $this->format_object( $value );
			}
		}

		return $array_obj;
	}

	/**
	 * Print a notice if WooCommerce is not installed.
	 */
	public function show_notice() {
		if ( ! empty( $this->message ) ) :
			?>
			<div class="error">
				<p><?php printf( '%s <strong>%s</strong>.', esc_html__( 'YITH Shippo Shippings for WooCommerce is enabled but not effective.', 'yith-shippo-shippings-for-woocommerce' ), esc_html( $this->message ) ); ?></p>
			</div>
			<?php
		endif;

	}

	/**
	 * Validate the token
	 *
	 * @return bool
	 */
	public function validate_token() {
		$token = $this->get_token();
		if ( ! empty( $token ) ) {
			$transient_name = 'yith_shippo_token_' . $token;
			$is_valid       = get_transient( $transient_name );
			if ( false === $is_valid ) {
				$this->connect();
				try {
					Shippo_CarrierAccount::all();
					$is_valid = 'true';
				} catch ( Exception $e ) {
					if ( $e instanceof Shippo_AuthenticationError ) {
						$this->message = implode( ',', $e->getJsonBody() );
						if ( is_admin() ) {
							add_action( 'admin_notices', array( $this, 'show_notice' ) );
						}
					}
					$is_valid = 'false';
				}
				set_transient( $transient_name, $is_valid, HOUR_IN_SECONDS );
			}

			return (bool) $is_valid;
		} else {
			if ( is_admin() ) {
				$this->message = __( 'YITH Shippo is not connected to your account', 'yith-shippo-shippings-for-woocommerce' );
				add_action( 'admin_notices', array( $this, 'show_notice' ) );
			}
			return false;
		}

	}

	/**
	 * Try to get the oauth_code after the onboarding
	 *
	 * @param string $code The code.
	 *
	 * @return  array
	 * @since 1.0.0
	 */
	public function retrive_oauth_code( $code ) {
		$body_args    = array(
			'code'          => $code,
			'client_id'     => YITH_SHIPPO_CLIENT_ID,
			'client_secret' => YITH_SHIPPO_CLIENT_SECRET,
			'grant_type'    => 'authorization_code',
		);
		$post_args    = array(
			'body'    => $body_args,
			'timeout' => 30,
		);
		$raw_response = wp_remote_post( 'https://goshippo.com/oauth/access_token/', $post_args );

		$result = array();
		if ( ! is_wp_error( $raw_response ) ) {
			$response = json_decode( wp_remote_retrieve_body( $raw_response ), true );

			if ( isset( $response['access_token'] ) ) {
				update_option( 'yith_shippo_live_token', $response['access_token'] );
				update_option( 'nfd-ecommerce-captive-flow-shippo', 'true' );

			} else {
				$result['error']             = $response['error'];
				$result['error_description'] = isset( $response['error_description'] ) ? $response['error_description'] : __( 'Error: impossible to complete the onboarding.', 'yith-shippo-shippings-for-woocommerce' );
			}
		} else {
			$result['error']             = $raw_response->get_error_code();
			$result['error_description'] = $raw_response->get_error_message();
		}

		return $result;
	}

	/**
	 * Disconnect from shippo
	 *
	 * @since 1.0.0
	 */
	public function disconnect_from_shippo() {
		delete_transient( 'yith_shippo_token_' . $this->get_token() );
		delete_option( 'yith_shippo_live_token' );
		update_option( 'nfd-ecommerce-captive-flow-shippo', 'false' );
	}
}
