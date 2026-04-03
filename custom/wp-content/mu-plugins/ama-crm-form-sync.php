<?php
/**
 * Plugin Name: Ask Mortgage Authority CRM Form Sync
 * Description: Sends WPForms or Forminator submissions to EspoCRM using WordPress hooks instead of a public PHP endpoint.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AMA_CRM_Form_Sync {

	public static function boot(): void {
		add_action( 'wpforms_process_complete', [ __CLASS__, 'handle_wpforms_submission' ], 20, 4 );
		add_action( 'forminator_custom_form_submit_before_set_fields', [ __CLASS__, 'handle_forminator_submission' ], 20, 3 );
	}

	public static function handle_wpforms_submission( $fields, $entry, $form_data, $entry_id ): void {
		$form_id = isset( $form_data['id'] ) ? absint( $form_data['id'] ) : 0;

		if ( ! self::is_enabled() || ! self::should_process_form( 'wpforms', $form_id ) ) {
			return;
		}

		$normalized = self::normalize_wpforms_fields( is_array( $fields ) ? $fields : [] );
		$lead       = self::build_lead_payload( $normalized, 'WPForms', $form_id, (int) $entry_id );

		if ( ! self::has_contact_data( $lead ) ) {
			self::log( 'Skipping WPForms submission with no usable lead fields.', [
				'form_id'  => $form_id,
				'entry_id' => (int) $entry_id,
			] );
			return;
		}

		self::send_lead( $lead, 'wpforms', $form_id, (int) $entry_id );
	}

	public static function handle_forminator_submission( $entry, $form_id, $field_data_array ): void {
		$form_id  = absint( $form_id );
		$entry_id = isset( $entry->entry_id ) ? (int) $entry->entry_id : 0;

		if ( ! self::is_enabled() || ! self::should_process_form( 'forminator', $form_id ) ) {
			return;
		}

		$normalized = self::normalize_forminator_fields( is_array( $field_data_array ) ? $field_data_array : [] );
		$lead       = self::build_lead_payload( $normalized, 'Forminator', $form_id, $entry_id );

		if ( ! self::has_contact_data( $lead ) ) {
			self::log( 'Skipping Forminator submission with no usable lead fields.', [
				'form_id'  => $form_id,
				'entry_id' => $entry_id,
			] );
			return;
		}

		self::send_lead( $lead, 'forminator', $form_id, $entry_id );
	}

	private static function is_enabled(): bool {
		if ( ! self::is_truthy( self::first_config_value( [ 'AMA_CRM_SYNC_ENABLED', 'CRM_SYNC_ENABLED' ], false ) ) ) {
			return false;
		}

		$config = self::get_config();

		if ( empty( $config['base_url'] ) ) {
			return false;
		}

		if ( ! empty( $config['api_key'] ) ) {
			return true;
		}

		if ( empty( $config['username'] ) || empty( $config['password'] ) ) {
			return false;
		}

		return true;
	}

	private static function get_config(): array {
		$config = [
			'base_url'            => rtrim( (string) self::first_config_value( [ 'AMA_CRM_BASE_URL', 'CRM_URL' ], '' ), '/' ),
			'api_key'             => (string) self::first_config_value( [ 'AMA_CRM_API_KEY', 'CRM_API_KEY' ], '' ),
			'username'            => (string) self::first_config_value( [ 'AMA_CRM_USERNAME', 'CRM_USERNAME', 'USERNAME' ], '' ),
			'password'            => (string) self::first_config_value( [ 'AMA_CRM_PASSWORD', 'CRM_PASSWORD', 'PASSWORD' ], '' ),
			'timeout'             => max( 5, (int) self::config_value( 'AMA_CRM_TIMEOUT', 15 ) ),
			'lead_source'         => (string) self::first_config_value( [ 'AMA_CRM_LEAD_SOURCE', 'CRM_LEAD_SOURCE' ], 'Website' ),
			'lead_source_field'   => (string) self::first_config_value( [ 'AMA_CRM_LEAD_SOURCE_FIELD', 'CRM_LEAD_SOURCE_FIELD' ], 'source' ),
			'business_unit'       => (string) self::first_config_value( [ 'AMA_CRM_BUSINESS_UNIT', 'CRM_BUSINESS_UNIT' ], '' ),
			'business_unit_field' => (string) self::first_config_value( [ 'AMA_CRM_BUSINESS_UNIT_FIELD', 'CRM_BUSINESS_UNIT_FIELD' ], 'businessUnit' ),
			'product_type'        => (string) self::first_config_value( [ 'AMA_CRM_PRODUCT_TYPE', 'CRM_PRODUCT_TYPE' ], '' ),
			'product_type_field'  => (string) self::first_config_value( [ 'AMA_CRM_PRODUCT_TYPE_FIELD', 'CRM_PRODUCT_TYPE_FIELD' ], 'productType' ),
			'wpforms_ids'         => self::parse_id_list( self::config_value( 'AMA_CRM_WPFORMS_IDS', [] ) ),
			'forminator_ids'      => self::parse_id_list( self::config_value( 'AMA_CRM_FORMINATOR_IDS', [] ) ),
		];

		/**
		 * Filters CRM sync configuration.
		 *
		 * @param array $config Sync configuration.
		 */
		return apply_filters( 'ama_crm_sync_config', $config );
	}

	private static function config_value( string $key, $default ) {
		if ( defined( $key ) ) {
			return constant( $key );
		}

		$env_value = getenv( $key );

		return false === $env_value ? $default : $env_value;
	}

	private static function first_config_value( array $keys, $default ) {
		foreach ( $keys as $key ) {
			$value = self::config_value( (string) $key, null );

			if ( null !== $value && '' !== $value ) {
				return $value;
			}
		}

		return $default;
	}

	private static function parse_id_list( $value ): array {
		if ( is_array( $value ) ) {
			return array_values( array_filter( array_map( 'absint', $value ) ) );
		}

		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return [];
		}

		return array_values(
			array_filter(
				array_map(
					'absint',
					preg_split( '/[\s,]+/', trim( $value ) )
				)
			)
		);
	}

	private static function should_process_form( string $provider, int $form_id ): bool {
		$config    = self::get_config();
		$allowed   = 'wpforms' === $provider ? $config['wpforms_ids'] : $config['forminator_ids'];
		$should_run = empty( $allowed ) || in_array( $form_id, $allowed, true );

		/**
		 * Filters whether a form submission should be sent to the CRM.
		 *
		 * @param bool   $should_run Whether the form should be processed.
		 * @param string $provider   Form provider slug.
		 * @param int    $form_id    Form identifier.
		 */
		return (bool) apply_filters( 'ama_crm_sync_should_process_form', $should_run, $provider, $form_id );
	}

	private static function normalize_wpforms_fields( array $fields ): array {
		$normalized = [];

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$label = isset( $field['name'] ) ? (string) $field['name'] : '';
			$type  = isset( $field['type'] ) ? (string) $field['type'] : '';

			if ( 'name' === $type ) {
				self::assign_name_parts(
					$normalized,
					$field['first'] ?? '',
					$field['last'] ?? '',
					$field['value'] ?? ''
				);
			}

			self::map_field_value( $normalized, $label, $field['value'] ?? '', $type );
		}

		return (array) apply_filters( 'ama_crm_sync_normalized_wpforms_fields', $normalized, $fields );
	}

	private static function normalize_forminator_fields( array $field_data_array ): array {
		$normalized = [];

		foreach ( $field_data_array as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$field_array = isset( $field['field_array'] ) && is_array( $field['field_array'] ) ? $field['field_array'] : [];
			$label       = '';
			$type        = isset( $field['field_type'] ) ? (string) $field['field_type'] : '';

			if ( isset( $field_array['field_label'] ) ) {
				$label = (string) $field_array['field_label'];
			} elseif ( isset( $field_array['label'] ) ) {
				$label = (string) $field_array['label'];
			} elseif ( isset( $field['name'] ) ) {
				$label = (string) $field['name'];
			}

			if ( 'name' === $type ) {
				$value = is_array( $field['value'] ?? null ) ? $field['value'] : [];
				self::assign_name_parts(
					$normalized,
					$value['first-name'] ?? '',
					$value['last-name'] ?? '',
					$field['value'] ?? ''
				);
			}

			self::map_field_value( $normalized, $label, $field['value'] ?? '', $type );
		}

		return (array) apply_filters( 'ama_crm_sync_normalized_forminator_fields', $normalized, $field_data_array );
	}

	private static function assign_name_parts( array &$normalized, $first_name, $last_name, $fallback_full_name ): void {
		$first_name = self::clean_text( $first_name );
		$last_name  = self::clean_text( $last_name );

		if ( '' !== $first_name && empty( $normalized['first_name'] ) ) {
			$normalized['first_name'] = $first_name;
		}

		if ( '' !== $last_name && empty( $normalized['last_name'] ) ) {
			$normalized['last_name'] = $last_name;
		}

		if ( empty( $normalized['first_name'] ) && empty( $normalized['last_name'] ) ) {
			self::split_full_name( $normalized, $fallback_full_name );
		}
	}

	private static function map_field_value( array &$normalized, string $label, $value, string $type = '' ): void {
		$normalized_label = strtolower( trim( preg_replace( '/\s+/', ' ', $label ) ) );
		$text_value       = self::flatten_value( $value );

		if ( '' === $text_value ) {
			return;
		}

		if ( 'email' === $type || self::matches_label( $normalized_label, [ 'email', 'e-mail', 'email address' ] ) ) {
			$normalized['email'] = self::clean_email( $text_value );
			return;
		}

		if ( 'phone' === $type || 'tel' === $type || self::matches_label( $normalized_label, [ 'phone', 'phone number', 'telephone', 'mobile', 'cell' ] ) ) {
			$normalized['phone'] = self::clean_phone( $text_value );
			return;
		}

		if ( self::matches_label( $normalized_label, [ 'first name', 'firstname', 'fname' ] ) ) {
			$normalized['first_name'] = self::clean_text( $text_value );
			return;
		}

		if ( self::matches_label( $normalized_label, [ 'last name', 'lastname', 'lname', 'surname' ] ) ) {
			$normalized['last_name'] = self::clean_text( $text_value );
			return;
		}

		if ( self::matches_label( $normalized_label, [ 'full name', 'name', 'your name' ] ) ) {
			self::split_full_name( $normalized, $text_value );
			return;
		}

		if (
			'textarea' === $type ||
			'paragraph' === $type ||
			self::matches_label( $normalized_label, [ 'notes', 'message', 'comments', 'comment', 'description', 'details', 'how can we help', 'anything else' ] )
		) {
			$normalized['notes'] = self::clean_textarea( $text_value );
		}
	}

	private static function matches_label( string $label, array $candidates ): bool {
		return in_array( $label, $candidates, true );
	}

	private static function split_full_name( array &$normalized, $full_name ): void {
		$full_name = self::clean_text( $full_name );

		if ( '' === $full_name ) {
			return;
		}

		$parts = preg_split( '/\s+/', $full_name );

		if ( empty( $normalized['first_name'] ) ) {
			$normalized['first_name'] = array_shift( $parts );
		}

		if ( ! empty( $parts ) && empty( $normalized['last_name'] ) ) {
			$normalized['last_name'] = implode( ' ', $parts );
		}
	}

	private static function build_lead_payload( array $normalized, string $provider_label, int $form_id, int $entry_id ): array {
		$config            = self::get_config();
		$description_lines = [];

		if ( ! empty( $normalized['notes'] ) ) {
			$description_lines[] = $normalized['notes'];
		}

		$description_lines[] = sprintf( 'Site: %s', wp_parse_url( home_url(), PHP_URL_HOST ) ?: home_url() );
		$description_lines[] = sprintf( 'Imported from %s form #%d', $provider_label, $form_id );

		if ( $entry_id > 0 ) {
			$description_lines[] = sprintf( 'Entry ID: %d', $entry_id );
		}

		$lead = [
			'firstName'    => self::clean_text( $normalized['first_name'] ?? '' ),
			'lastName'     => self::clean_text( $normalized['last_name'] ?? '' ),
			'emailAddress' => self::clean_email( $normalized['email'] ?? '' ),
			'phoneNumber'  => self::clean_phone( $normalized['phone'] ?? '' ),
			'description'  => self::clean_textarea( implode( "\n\n", array_filter( $description_lines ) ) ),
		];

		if ( '' === $lead['lastName'] ) {
			$lead['lastName'] = 'Website Lead';
		}

		$lead_source_field = self::clean_text( $config['lead_source_field'] ?? '' );
		$lead_source_value = self::clean_text( $config['lead_source'] ?? '' );

		if ( '' !== $lead_source_field && '' !== $lead_source_value ) {
			$lead[ $lead_source_field ] = $lead_source_value;
		}

		$business_unit_field = self::clean_text( $config['business_unit_field'] ?? '' );
		$business_unit_value = self::clean_text( $config['business_unit'] ?? '' );

		if ( '' !== $business_unit_field && '' !== $business_unit_value ) {
			$lead[ $business_unit_field ] = $business_unit_value;
		}

		$product_type_field = self::clean_text( $config['product_type_field'] ?? '' );
		$product_type_value = self::clean_text( $config['product_type'] ?? '' );

		if ( '' !== $product_type_field && '' !== $product_type_value ) {
			$lead[ $product_type_field ] = $product_type_value;
		}

		/**
		 * Filters the EspoCRM lead payload before it is sent.
		 *
		 * @param array  $lead           Lead payload.
		 * @param array  $normalized     Normalized form data.
		 * @param string $provider_label Provider label.
		 * @param int    $form_id        Form identifier.
		 * @param int    $entry_id       Entry identifier.
		 */
		return (array) apply_filters( 'ama_crm_sync_lead_payload', $lead, $normalized, $provider_label, $form_id, $entry_id );
	}

	private static function has_contact_data( array $lead ): bool {
		return '' !== (string) ( $lead['emailAddress'] ?? '' )
			|| '' !== (string) ( $lead['phoneNumber'] ?? '' )
			|| '' !== (string) ( $lead['firstName'] ?? '' )
			|| '' !== (string) ( $lead['lastName'] ?? '' );
	}

	private static function send_lead( array $lead, string $provider, int $form_id, int $entry_id ): void {
		$config   = self::get_config();
		$headers  = [
			'Content-Type' => 'application/json',
			'Accept'       => 'application/json',
		];

		if ( ! empty( $config['api_key'] ) ) {
			$headers['X-Api-Key'] = $config['api_key'];
		} else {
			$headers['Authorization'] = 'Basic ' . base64_encode( $config['username'] . ':' . $config['password'] );
		}

		$response = wp_remote_post(
			self::api_url( $config['base_url'], 'Lead' ),
			[
				'timeout' => $config['timeout'],
				'headers' => $headers,
				'body'    => wp_json_encode( $lead ),
			]
		);

		if ( is_wp_error( $response ) ) {
			self::log( 'CRM lead submission request failed.', [
				'provider' => $provider,
				'form_id'  => $form_id,
				'entry_id' => $entry_id,
				'error'    => $response->get_error_message(),
			] );
			return;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );

		if ( $status < 200 || $status >= 300 ) {
			self::log( 'EspoCRM lead submission returned a non-success status.', [
				'provider' => $provider,
				'form_id'  => $form_id,
				'entry_id' => $entry_id,
				'status'   => $status,
				'body'     => self::shorten( wp_remote_retrieve_body( $response ) ),
			] );
			return;
		}

		self::log( 'EspoCRM lead submission succeeded.', [
			'provider' => $provider,
			'form_id'  => $form_id,
			'entry_id' => $entry_id,
			'status'   => $status,
		] );
	}

	private static function api_url( string $base_url, string $path ): string {
		$base_url = rtrim( $base_url, '/' );
		$base_url = preg_replace( '#/api/v1$#', '', $base_url );

		return $base_url . '/api/v1/' . ltrim( $path, '/' );
	}

	private static function flatten_value( $value ): string {
		if ( is_array( $value ) ) {
			$parts = [];

			array_walk_recursive(
				$value,
				static function ( $item ) use ( &$parts ): void {
					if ( is_scalar( $item ) && '' !== trim( (string) $item ) ) {
						$parts[] = (string) $item;
					}
				}
			);

			return trim( implode( ' ', $parts ) );
		}

		if ( is_scalar( $value ) ) {
			return trim( (string) $value );
		}

		return '';
	}

	private static function clean_text( $value ): string {
		return sanitize_text_field( self::flatten_value( $value ) );
	}

	private static function clean_textarea( $value ): string {
		return sanitize_textarea_field( self::flatten_value( $value ) );
	}

	private static function clean_email( $value ): string {
		$email = sanitize_email( self::flatten_value( $value ) );

		return is_email( $email ) ? $email : '';
	}

	private static function clean_phone( $value ): string {
		$phone = self::flatten_value( $value );
		$phone = preg_replace( '/[^0-9+(). x-]/', '', $phone );

		return is_string( $phone ) ? trim( $phone ) : '';
	}

	private static function shorten( string $body ): string {
		$body = trim( preg_replace( '/\s+/', ' ', $body ) );

		if ( strlen( $body ) <= 300 ) {
			return $body;
		}

		return substr( $body, 0, 300 ) . '...';
	}

	private static function is_truthy( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		return in_array( strtolower( trim( (string) $value ) ), [ '1', 'true', 'yes', 'on' ], true );
	}

	private static function log( string $message, array $context = [] ): void {
		if ( ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
			return;
		}

		error_log( '[AMA CRM Sync] ' . $message . ' ' . wp_json_encode( $context ) );
	}
}

AMA_CRM_Form_Sync::boot();
