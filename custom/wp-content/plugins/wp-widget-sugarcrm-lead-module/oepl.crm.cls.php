<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( esc_html__( 'Please don\'t access this file directly.', 'WP2SL' ) );
}

class WP2SLSugarCRMClass {
	public $drop_sugar_tbl_on_uninstall;
	public $module_list      = array();
	public $module_list_name = array();
	public $module_list_str;
	public $sugar_url;
	public $sugar_user;
	public $sugar_pass; 
	public $sugar_sess_data; 
	public $is_htaccess_protected;
	public $htaccess_admin_user; 
	public $htaccess_admin_pass;
	public $sugar_client;
	public $sugar_sess_id;
	public $exclude_fields      = array();
	public $exclude_field_types = array();
	
	function __construct() {
		$this->drop_sugar_tbl_on_uninstall = false;

		$this->module_list      = array( 'Leads' );
		$this->module_list_name = array( 'Leads' => 'Leads' );
		$this->module_list_str  = "'" . implode( "','", $this->module_list ) . "'";
			
		$this->sugar_url       = '';
		$this->sugar_user      = ''; 
		$this->sugar_pass      = ''; 
		$this->sugar_sess_data = '';
		
		$this->is_htaccess_protected = false; 
		$this->htaccess_admin_user   = '';
		$this->htaccess_admin_pass   = '';
		
		$this->sugar_client        = ''; 
		$this->sugar_sess_id       = ''; 
		$this->exclude_fields      = array( 'id', 'date_entered', 'date_modified', 'modified_user_id', 'modified_by_name', 'created_by', 'created_by_name', 'deleted', 'assigned_user_id', 'assigned_user_name', 'team_id', 'team_set_id', 'team_count', 'team_name', 'email_addresses_non_primary', 'account_description', 'opportunity_name', 'opportunity_amount', 'email2', 'invalid_email', 'email_opt_out', 'webtolead_email1', 'webtolead_email2', 'webtolead_email_opt_out', 'webtolead_invalid_email', 'email', 'full_name', 'reports_to_id', 'report_to_name', 'contact_id', 'account_id', 'opportunity_id', 'refered_by', 'c_accept_status_fields', 'm_accept_status_fields', 'lead_remote_ip_c' );
		$this->exclude_field_types = array();
	}

	function wp2sl_activate() {
		global $wpdb;
		$sql = 'CREATE TABLE IF NOT EXISTS `' . OEPL_TBL_MAP_FIELDS . "` (
				  `pid` int(11) NOT NULL AUTO_INCREMENT,
				  `module` varchar(100) NOT NULL,
				  `field_type` enum('text','select','radio','checkbox','textarea','file','filler') NOT NULL DEFAULT 'text',
				  `data_type` varchar(50) NOT NULL,
				  `field_name` varchar(255) NOT NULL,
				  `field_value` text NOT NULL,
				  `wp_meta_key` varchar(150) NOT NULL,
				  `wp_meta_label` varchar(200) NOT NULL,
				  `wp_custom_label` varchar(50) NOT NULL,
				  `display_order` int(11) NOT NULL,
				  `required` enum('Y','N') NOT NULL DEFAULT 'N',
				  `hidden` enum('Y','N') NOT NULL DEFAULT 'N',
				  `is_show` enum('Y','N') NOT NULL DEFAULT 'N',
				  `show_column` enum('1','2') NOT NULL DEFAULT '1',
				  `custom_field` enum('Y','N') NOT NULL DEFAULT 'N',
				  `hidden_field_value` VARCHAR(255) NOT NULL,
				  PRIMARY KEY (`pid`)
				) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;";
		$wpdb->query( $sql );
		
		add_option( 'OEPL_SugarCRMSuccessMessage', 'Thank you! Your message was sent successfully.' );
		add_option( 'OEPL_SugarCRMFailureMessage', 'Sorry. An error occurred while sending the message!' );
		add_option( 'OEPL_SugarCRMReqFieldsMessage', 'Please fill in all the required fields.' ); 
		add_option( 'OEPL_SugarCRMInvalidCaptchaMessage', 'Invalid captcha ! Please try again.' );
		add_option( 'OEPL_Captcha_status', 'Y' );
	}

	function LoginToSugar() {
		$login_parameters = array(
			'user_auth'        => array(
				'user_name' => $this->sugar_user,
				'password'  => $this->sugar_pass,
				'version'   => '1',
			),
			'application_name' => 'RestTest',
			'name_value_list'  => array(),
		);
		
		$this->sugar_sess_data = $this->SugarCall( 'login', $login_parameters, $this->sugar_url );
		if ( is_array( $this->sugar_sess_data ) && isset( $this->sugar_sess_data['id'] ) ) {
			$this->sugar_sess_id = $this->sugar_sess_data['id'];
			
			if ( empty( $this->sugar_sess_id ) ) {
				$subject = get_option( 'blogname' ) . esc_html_e( 'CRM connection failed.', 'WP2SL' );
				$body    = get_option( 'siteurl' ) . esc_html_e( 'not able to connect with CRM. Plesae review your CRM settings', 'WP2SL' );
				wp_mail( get_option( 'admin_email' ), $subject, $body );
			}
			return $this->sugar_sess_id;
		}
		return false;
	}

	function LogoutToSugar() {
		$login_parameters = array(
			'user_auth'        => array(
				'user_name' => $this->sugar_user,
				'password'  => md5( $this->sugar_pass ),
				'version'   => '1',
			),
			'application_name' => 'RestTest',
			'name_value_list'  => array(),
		);
		$this->SugarCall( 'logout', $login_parameters, $this->sugar_url );
	}

	function get_size( $file, $type ) {
		$filesize = filesize( $file );
	
		switch ( $type ) {
			case 'KB':
				$filesize /= 1024;
				break;
			case 'MB':
				$filesize /= 1024 ** 2;
				break;
			case 'GB':
				$filesize /= 1024 ** 3;
				break;
		}
	
		if ( $filesize <= 0 ) {
			return 0;
		} else {
			return round( $filesize, 2 );
		}
	}

	function ErrorLogWrite( $content ) {
		$text    = "\n\n\n";
		$text   .= 'Log: ' . "\n" . $content . "\n";
		$my_file = OEPL_PLUGIN_DIR . 'Log.txt';
		
		if ( $this->get_size( $my_file, 'MB' ) > 2 ) {
			rename( OEPL_PLUGIN_DIR . 'Log.txt', OEPL_PLUGIN_DIR . 'Log_till_' . date( 'd-M-Y-H-i-s-u' ) . '.txt' );
		} 

		$fh = fopen( $my_file, 'a+' ) || wp_die( "can't open file" );
		fwrite( $fh, $text );
		fclose( $fh );
		return null;
	}

	function SugarCall( $method, $parameters, $url ) {
		if ( $this->sugar_url === '' ) {
			return null;
		}
		$headers = array();

		if ( $this->is_htaccess_protected === true ) {
			$auth_credentials         = 'Basic ' . base64_encode( $this->htaccess_admin_user . ':' . $this->htaccess_admin_pass );
			$headers['Authorization'] = $auth_credentials;
		}
		
		$json_encoded_data = wp_json_encode( $parameters );
		
		$request_body = array(
			'method'        => $method,
			'input_type'    => 'JSON',
			'response_type' => 'JSON',
			'rest_data'     => $json_encoded_data,
		);
		
		$args = array(  
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => $headers,
			'body'        => $request_body,
			'cookies'     => array(),
		);
		
		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_messages();
		}
		
		$body = wp_remote_retrieve_body( $response );
		return json_decode( $body, true );
	}

	function getLeadFieldsList() {
		$result = (object) array();
		if ( $this->sugar_sess_id === '' ) {
			$this->LoginToSugar();
		}
		
		if ( $this->sugar_sess_id ) {
			$set_entry_parameters = array(   
				'session'     => $this->sugar_sess_id,
				'module_name' => 'Leads',
			);

			$result = $this->SugarCall( 'get_module_fields', $set_entry_parameters, $this->sugar_url );
		}
		return $result;
	}
	
	function InsertLeadToSugar( $files_to_sugar = array() ) {
		global $wpdb;
		
		wp_unslash( $_POST );
		
		if ( $this->sugar_sess_id === '' ) {
			$this->LoginToSugar();
		}

		if ( $this->sugar_sess_id ) {
			$query               = $wpdb->prepare(
				'SELECT wp_meta_key, data_type, field_name FROM ' . OEPL_TBL_MAP_FIELDS . ' AS mp WHERE mp.module = %s',
				'Leads'
			);
			$mapped_fields       = $wpdb->get_results( $query, ARRAY_A );
			$mapped_fields_count = count( $mapped_fields );
			$name_value_list     = array();

			for ( $i = 0; $i < $mapped_fields_count; $i++ ) {
				$meta_key   = $mapped_fields[ $i ]['wp_meta_key'];
				$data_type  = $mapped_fields[ $i ]['data_type'];
				$field_name = $mapped_fields[ $i ]['field_name'];

				if ( isset( $_POST[ $meta_key ] ) ) {
					$sanitized_value = sanitize_text_field( $_POST[ $meta_key ] );

					if ( $data_type === 'date' ) {
						$query              = "SELECT STR_TO_DATE('" . $sanitized_value . "','%m/%d/%Y') as date";
						$date_result        = $wpdb->get_results( $query, ARRAY_A );
						$_POST[ $meta_key ] = $date_result[0]['date'];
					} elseif ( $data_type === 'datetimecombo' ) {
						$query              = "SELECT STR_TO_DATE('" . $sanitized_value . "','%m/%d/%Y %H:%i') as date";
						$date_result        = $wpdb->get_results( $query, ARRAY_A );
						$_POST[ $meta_key ] = $date_result[0]['date'];
					}

					$name_value_list[] = array(
						'name'  => $field_name, 
						'value' => trim( sanitize_text_field( $_POST[ $meta_key ] ) ),
					);
				}
			}

			$auto_ip_status = get_option( 'OEPL_auto_IP_addr_status' );
			if ( $auto_ip_status === 'Y' && isset( $_SERVER['REMOTE_ADDR'] ) ) {
				$name_value_list[] = array(
					'name'  => 'lead_remote_ip',
					'value' => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ),
				);
			}   
			
			if ( ! empty( $name_value_list ) && $this->sugar_sess_id !== '' ) {
				$set_entry_parameters = array(   
					'session'         => $this->sugar_sess_id,
					'module_name'     => 'Leads',
					'name_value_list' => $name_value_list,
				);
				$lead_result          = $this->SugarCall( 'set_entry', $set_entry_parameters, $this->sugar_url );
			}
			
			if ( ! empty( $lead_result->id ) && is_array( $files_to_sugar ) && count( $files_to_sugar ) > 0 ) {
				foreach ( $files_to_sugar as $file_index => $file ) {
					if ( ! empty( $file['name'] ) && file_exists( $file['file'] ) && ! is_dir( $file['file'] ) ) {
						$name_value_list = array(
							array(
								'name'  => 'parent_type',
								'value' => 'Leads',
							),
							array(
								'name'  => 'parent_id',
								'value' => $lead_result->id,
							),
							array(
								'name'  => 'name',
								'value' => 'Attachment-' . ( $file_index + 1 ),
							),
						);

							$set_entry_parameters = array(   
								'session'         => $this->sugar_sess_id,
								'module_name'     => 'Notes',
								'name_value_list' => $name_value_list,
							);
							$note_result          = $this->SugarCall( 'set_entry', $set_entry_parameters, $this->sugar_url );
						
							if ( ! empty( $note_result->id ) ) {
								$file_contents   = file_get_contents( $file['file'] );                    
								$attachment      = array(
									'id'             => $note_result->id,
									'filename'       => sanitize_file_name( $file['name'] ),
									'file_mime_type' => sanitize_mime_type( $file['type'] ),
									'file'           => base64_encode( $file_contents ),
								);                              
								$note_attachment = array( 
									'session' => $this->sugar_sess_id,
									'note'    => $attachment,
								);
								$this->SugarCall( 'set_note_attachment', $note_attachment, $this->sugar_url );
								unlink( $file['file'] );
							}
					}
				}
			}

			return $lead_result->id ?? false;
		}
		return false;
	}
}
