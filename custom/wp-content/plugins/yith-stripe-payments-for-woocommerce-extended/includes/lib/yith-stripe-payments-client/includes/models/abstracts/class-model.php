<?php
/**
 * Abstract model
 * Base model extended by specific objects
 *
 * @author  YITH
 * @package YITH\StripeClient\Models
 * @version 1.0.0
 */

namespace YITH\StripeClient\Models\Abstracts;

use Ramsey\Uuid\Uuid;
use YITH\StripeClient\Client as Client;
use YITH\StripeClient\Exceptions\Abstracts\Exception;

defined( 'YITH_STRIPE_CLIENT_DIR' ) || exit;

if ( ! class_exists( 'YITH\StripeClient\Models\Abstracts\Model' ) ) {
	/**
	 * Representation of account instance
	 *
	 * @since 1.0.0
	 */
	abstract class Model {

		/**
		 * Constructor method
		 *
		 * @param array $raw Optional array of data used to populate model object.
		 *
		 * @throws \Exception When raw data passed do not match Model structure.
		 */
		public function __construct( $raw = array() ) {
			$raw = self::get_data( $raw );

			foreach ( $raw as $field_id => $field_value ) {
				$this->{$field_id} = $field_value;
			}
		}

		/**
		 * Flag constant; describes validation that ignores required fields
		 *
		 * @const int
		 */
		const IGNORE_REQUIRED = 1;

		/**
		 * Flag constant; describes validation that returns just validated value and ignores errors.
		 *
		 * @const int
		 */
		const SANITIZE_ONLY = 2;

		/**
		 * Data structure describing the setting to match; EG:
		 * [
		 *     'setting_1' => [
		 *         'label' => 'first_name',
		 *         'type'  => 'text',
		 *         'options' => array(
		 *             'option_1' => 'value 1',
		 *             'option_2' => 'value 2'
		 *          ),
		 *          'required' => false,
		 *          'default' => false,
		 *     ],
		 *     ...
		 * ].
		 *
		 * @var array
		 */
		protected static $data_structure;

		/**
		 * Endpoint related to this object on remote server
		 *
		 * @var string
		 */
		protected static $endpoint;

		/**
		 * Returns endpoint for current object
		 *
		 * @param string $subpath Optional subpath from base endpoint.
		 *
		 * @return string
		 */
		public static function get_endpoint( $subpath = '' ) {
			$parts = array_filter(
				array(
					Client::format_endpoint( static::$endpoint ),
					$subpath,
				)
			);

			return implode( '/', $parts );
		}

		/**
		 * Returns data structure describing current object
		 *
		 * @return array Data structure ({@see self::$data_structure} for more info).
		 */
		public static function get_data_structure() {
			return static::$data_structure;
		}

		/**
		 * Returns sanitized data for current object
		 *
		 * @param array $data  An array of sanitized data for the object.
		 * @param int   $flags {@see self::parse_data()} for further information.
		 *
		 * @throws \Exception When an error occurs with validation.
		 */
		public static function get_data( $data, $flags = false ) {
			return self::parse_data( $data, $flags );
		}

		/**
		 * Matches a set of posted values, against a data structure describing available options
		 * It returns sanitized values, or throws an error when one setting doesn't match requirements
		 *
		 * TODO: review this code: does 'type' make sense in this context? Maybe type and sanitized should be unified.
		 *
		 * @param array $data      An array of posted, un-sanitized, values.
		 * @param int   $flags     Bitmask for function options; accepts following values:<br>
		 *                         * self::IGNORE_REQUIRED => 1<br>
		 *                         * self::SANITIZE_ONLY = 2.
		 * @param array $structure Data structure describing the setting to match; EG:<br>
		 *                         [<br>
		 *                         'setting_1' => [<br>
		 *                         'label' => 'first_name',<br>
		 *                         'type'  => 'text',<br>
		 *                         'options' => array(<br>
		 *                         'option_1' => 'value 1',<br>
		 *                         'option_2' => 'value 2'<br>
		 *                         ),<br>
		 *                         'required' => false,<br>
		 *                         'default' => false,<br>
		 *                         ],<br>
		 *                         ...<br>
		 *                         ].
		 *
		 * @return array Array of sanitized options.
		 * @throws \Exception When an error occurs with validation.
		 */
		protected static function parse_data( $data, $flags = false, $structure = false ) {
			$validated_options = array();

			if ( ! $structure ) {
				$structure = static::get_data_structure();
			}

			// process options.
			$ignore_required = false !== $flags && $flags & self::IGNORE_REQUIRED;
			$sanitize_only   = false !== $flags && $flags & self::SANITIZE_ONLY;

			// maybe convert data keys case.
			$data = self::maybe_convert_case( $data );

			foreach ( $structure as $setting_id => $setting ) {
				$value = isset( $data[ $setting_id ] ) ? $data[ $setting_id ] : false;

				// if setting has sub-settings, process those recursively.
				$has_sub_settings = ! empty( $setting ) && is_array( current( $setting ) );

				if ( $has_sub_settings ) {
					$parsed_sub_value = self::parse_data( $value, $flags, $setting );

					if ( ! empty( $parsed_sub_value ) ) {
						$validated_options[ $setting_id ] = $parsed_sub_value;
					}
				} else {
					$required = isset( $setting[ 'required' ] ) && $setting[ 'required' ];
					$default  = isset( $setting[ 'default' ] ) ? $setting[ 'default' ] : false;
					$type     = isset( $setting[ 'type' ] ) ? $setting[ 'type' ] : false;
					$options  = isset( $setting[ 'options' ] ) ? $setting[ 'options' ] : array();
					$deps     = isset( $setting[ 'deps' ] ) ? $setting[ 'deps' ] : false;
					$label    = isset( $setting[ 'label' ] ) ? $setting[ 'label' ] : $setting_id;

					if ( ! $type ) {
						$type = 'text';
					}

					if ( ! $options ) {
						$options = array();
					}

					if ( in_array( $type, array( 'checkbox', 'onoff', 'bool' ), true ) ) {
						$value = (int) in_array( $value, array( 'yes', 'on', 'true', '1', true, 1 ), true );
					} elseif ( in_array( $type, array( 'select', 'radio' ), true ) && ! empty( $options ) && $value && ! array_key_exists( $value, $options ) ) {
						$value = false;

						if ( ! $value && ! $sanitize_only ) {
							// translators: 1. Label of the required field missing.
							throw new \Exception( sprintf( __( 'Please, choose a valid option for %s', 'yith-stripe-client' ), $label ) );
						}
					} elseif ( 'email' === $type && $value ) {
						$value = filter_var( $value, FILTER_VALIDATE_EMAIL );

						if ( ! $value && ! $sanitize_only ) {
							// translators: 1. Label of the required field missing.
							throw new \Exception( sprintf( __( 'Please, provide a valid email address for %s', 'yith-stripe-client' ), $label ) );
						}
					} elseif ( 'number' === $type && $value && ! is_numeric( $value ) ) {
						$value = false;

						if ( ! $value && ! $sanitize_only ) {
							// translators: 1. Label of the required field missing.
							throw new \Exception( sprintf( __( 'Please, provide a valid value for %s', 'yith-stripe-client' ), $label ) );
						}
					} elseif ( 'textarea' === $type && $value ) {
						$value = sanitize_textarea_field( wp_unslash( $value ) );
					} elseif ( 'hash' === $type && is_array( $value ) ) {
						array_walk_recursive( $value, 'sanitize_text_field' );
					} elseif ( $value ) {
						$value = sanitize_text_field( wp_unslash( $value ) );
					} elseif ( ! empty( $default ) ) {
						$value = $default;
					} else {
						$value = null;
					}

					if ( $value && isset( $setting[ 'validation' ] ) ) {
						switch ( $setting[ 'validation' ] ) {
							case 'email':
								$value = sanitize_email( $value );

								if ( ! is_email( $value ) && ! $sanitize_only ) {
									// translators: 1. Label of the required field missing.
									throw new \Exception( sprintf( __( 'Please, make sure to enter a valid email address for %s', 'yith-stripe-client' ), $label ) );
								}
								break;
							case 'uuid':
								if ( ! Uuid::isValid( $value ) && ! $sanitize_only ) {
									// translators: 1. Label of the required field missing.
									throw new \Exception( sprintf( __( 'Please, make sure to enter a valid UUID for %s', 'yith-stripe-client' ), $label ) );
								}
								break;
							case 'url':
								$value = filter_var( $value, FILTER_SANITIZE_URL );

								if ( ! $value && ! $sanitize_only ) {
									// translators: 1. Label of the required field missing.
									throw new \Exception( sprintf( __( 'Please, make sure to enter a valid URL address for %s', 'yith-stripe-client' ), $label ) );
								}
								break;
							case 'country':
								if ( ! preg_match( '/[A-z]{2}/', $value ) && ! $sanitize_only ) {
									// translators: 1. Label of the required field missing.
									throw new \Exception( sprintf( __( 'Please, make sure to enter a valid contry code for %s', 'yith-stripe-client' ), $label ) );
								}
								break;
						}
					}

					// if field has some dependency, make sure that is matched before requiring it.
					if ( ! empty( $deps ) ) {
						$dep_id    = isset( $setting[ 'id' ] ) && $setting[ 'id' ];
						$dep_value = isset( $setting[ 'value' ] ) && $setting[ 'value' ];

						$required = $required && isset( $posted[ $dep_id ] ) && in_array( $posted[ $dep_id ], (array) $dep_value, true );
					}

					if ( ! empty( $required ) && ! $value && ! $ignore_required && ! $sanitize_only ) {
						// translators: 1. Label of the required field missing.
						throw new \Exception( sprintf( __( '%s is a required field', 'yith-stripe-client' ), $label ) );
					}

					$validated_options[ $setting_id ] = $value;
				}
			}

			$validated_options = array_filter(
				$validated_options,
				function ( $i ) {
					return ! is_null( $i );
				}
			);

			return $validated_options;
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
			return new static( $raw );
		}

		/**
		 * Returns an array of instances of current Model, populating them with data from $raw array passed
		 *
		 * @param array $items Optional array of data used to populate model objects.
		 *
		 * @return Model[]
		 */
		protected static function list( $items = array() ) {
			$set = array();

			if ( ! $items ) {
				return $set;
			}

			foreach ( $items as $raw ) {
				try {
					$set[] = self::get( $raw );
				} catch ( \Exception $e ) {
					continue;
				}
			}

			return $set;
		}

		/**
		 * Server responds with CamelCase attributes
		 * This utility helps converting data key from CamelCase to snake_case
		 * If data do not use CamelCase, set will be returned intact.
		 *
		 * @param array $data An array of sanitized data for the object.
		 *
		 * @return array Set with snake_case keys.
		 */
		protected static function maybe_convert_case( $data ) {
			if ( ! $data ) {
				return $data;
			}

			$formatted = array();

			foreach ( $data as $field => $value ) {
				$field = strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', $field ) );

				$formatted[ $field ] = $value;
			}

			return $formatted;
		}
	}
}
