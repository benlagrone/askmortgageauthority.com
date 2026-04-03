<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( esc_html__( 'Please don\'t access this file directly.', 'WP2SL' ) );
}

/**
 * Handles the AJAX request to save a custom label.
 *
 * This function updates the `wp_custom_label` field in the database for a given `pid`.
 * It performs a nonce verification for security and ensures that the data is sanitized before updating.
 *
 * @since 1.0.0
 * @return void Sends a JSON response indicating success or failure.
 */
function wp2sl_save_custom_label_callback() {
	global $wpdb;
	wp_unslash( $_POST );

	if ( isset( $_POST['oepl_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_POST['oepl_nonce'] ), 'my_thumb' ) ) {
		wp_die( esc_html__( 'Security check!', 'WP2SL' ), 'Error', array( 'back_link' => true ) );
	}

	$upd = $wpdb->update(
		OEPL_TBL_MAP_FIELDS,
		array( 'wp_custom_label' => sanitize_text_field( $_POST['label'] ) ),
		array( 'pid' => (int) $_POST['pid'] )
	);

	$response = array();
	if ( $upd !== false && $upd > 0 ) {
		$response['Message'] = __( 'Custom Label changed succesfully', 'WP2SL' );
	} else {
		$response['Message'] = __( 'Error occured while saving custom label. Please try again', 'WP2SL' );
	}
	wp_send_json( $response );
}
add_action( 'wp_ajax_WP2SL_save_custom_label', 'wp2sl_save_custom_label_callback' );

/**
 * Handles the AJAX request to update the display order of a custom label.
 *
 * This function updates the `display_order` field in the database for a given `pid`.
 * It performs nonce verification for security and ensures that the data is sanitized before updating.
 *
 * @since 1.0.0
 * @return void Sends a JSON response indicating success or failure.
 */
function wp2sl_save_custom_order_callback() {
	global $wpdb;
	wp_unslash( $_POST );
	if ( isset( $_POST['oepl_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_POST['oepl_nonce'] ), 'my_thumb' ) ) {
		wp_die( esc_html__( 'Security check!', 'WP2SL' ), 'Error', array( 'back_link' => true ) );
	}

	if ( isset( $_POST['pid'] ) && isset( $_POST['label'] ) ) {
		$upd = $wpdb->update(
			OEPL_TBL_MAP_FIELDS,
			array( 'display_order' => (int) $_POST['label'] ),
			array( 'pid' => (int) $_POST['pid'] )
		);

		$response = array();
		if ( $upd !== false && $upd > 0 ) {
			$response['Message'] = __( 'Display order changed succesfully', 'WP2SL' );
		} else {
			$response['Message'] = __( 'Error occured while saving display order value. Please try again', 'WP2SL' );
		}
		wp_send_json( $response );
	}
}
add_action( 'wp_ajax_WP2SL_save_custom_order', 'wp2sl_save_custom_order_callback' );

// Change field status - START.
add_action( 'wp_ajax_WP2SL_Grid_Ajax_Action', 'WP2SL_Grid_Ajax_Action_callback' );
function WP2SL_Grid_Ajax_Action_callback() {
	global $wpdb;
	wp_unslash( $_POST );
	if ( isset( $_POST['OEPL_Action'] ) && isset( $_POST['pid'] ) ) {
		$action = sanitize_text_field( $_POST['OEPL_Action'] );

		$upd_data = array();
		$flag     = false;
		if ( $action === 'OEPL_Change_Status' ) {
			$flag   = true;
			$sql    = $wpdb->prepare( 'SELECT is_show FROM ' . OEPL_TBL_MAP_FIELDS . ' WHERE pid = %d', (int) $_POST['pid'] );
			$status = $wpdb->get_row( $sql, ARRAY_A );
			if ( isset( $status['is_show'] ) && ! empty( $status ) ) {
				$upd_data['is_show'] = ( $status['is_show'] === 'Y' ) ? 'N' : 'Y';
			}
		} elseif ( $action === 'OEPL_Change_Hidden_Status' ) {
			$flag   = true;
			$sql    = $wpdb->prepare( 'SELECT hidden FROM ' . OEPL_TBL_MAP_FIELDS . ' WHERE pid = %d', (int) $_POST['pid'] );
			$status = $wpdb->get_row( $sql, ARRAY_A );
			if ( isset( $status['hidden'] ) && ! empty( $status ) ) {
				$upd_data['hidden'] = ( $status['hidden'] === 'Y' ) ? 'N' : 'Y';
			}
		} elseif ( $action === 'OEPL_Change_Required_Status' ) {
			$flag   = true;
			$sql    = $wpdb->prepare( 'SELECT required FROM ' . OEPL_TBL_MAP_FIELDS . ' WHERE pid = %d', (int) $_POST['pid'] );
			$status = $wpdb->get_row( $sql, ARRAY_A );
			if ( isset( $status['required'] ) && ! empty( $status ) ) {
				$upd_data['required'] = ( $status['required'] === 'Y' ) ? 'N' : 'Y';
			}
		} elseif ( $action === 'OEPL_Change_Hidden_Status_Val' ) {
			$flag                           = true;
			$upd_data['hidden_field_value'] = sanitize_text_field( $_POST['hidden_field_value'] );
		}
		$respone = array();
		if ( $flag && isset( $upd_data ) && ! empty( $upd_data ) ) {
			$upd = $wpdb->update( OEPL_TBL_MAP_FIELDS, $upd_data, array( 'pid' => (int) $_POST['pid'] ) );
			if ( $upd !== false && $upd > 0 ) {
				$respone['message'] = __( 'Field Updated Successfully', 'WP2SL' );
			} else {
				$respone['message'] = __( 'Error While updating field. Please try again', 'WP2SL' );
			}
		} else {
			$respone['message'] = __( 'Error While updating field. Please try again', 'WP2SL' );
		}
		wp_send_json( $respone );
	}
}
// Change field status - END.

// Plugin Update database changes Logic START.
add_action( 'plugins_loaded', 'WP2SL_plugin_update_function' );
function WP2SL_plugin_update_function() {
	global $oepl_update_version, $wpdb;
	$oepl_current_version = get_option( 'OEPL_PLUGIN_VERSION' );
	if ( $oepl_current_version !== $oepl_update_version ) {
		$sql  = 'SHOW COLUMNS FROM ' . OEPL_TBL_MAP_FIELDS;
		$rows = $wpdb->get_col( $sql );
		if ( ! in_array( 'wp_custom_label', $rows ) ) {
			$wpdb->query( 'ALTER TABLE ' . OEPL_TBL_MAP_FIELDS . ' ADD `wp_custom_label` VARCHAR( 50 ) NOT NULL AFTER `wp_meta_label`' );
		}
		if ( ! in_array( 'required', $rows ) ) {
			$wpdb->query( 'ALTER TABLE ' . OEPL_TBL_MAP_FIELDS . " ADD `required` ENUM( 'Y', 'N' ) NOT NULL DEFAULT 'N' AFTER `display_order`" );
		}
		if ( ! in_array( 'hidden', $rows ) ) {
			$wpdb->query( 'ALTER TABLE ' . OEPL_TBL_MAP_FIELDS . " ADD `hidden` ENUM('Y','N') NOT NULL DEFAULT 'N' AFTER `required`;" );
		}
		if ( ! in_array( 'custom_field', $rows ) ) {
			$wpdb->query( 'ALTER TABLE ' . OEPL_TBL_MAP_FIELDS . " ADD `custom_field` ENUM( 'Y', 'N' ) NOT NULL DEFAULT 'N';" );
		}
		update_option( 'OEPL_PLUGIN_VERSION', $oepl_update_version );
	}
}
// Plugin Update database changes Logic END.

// Change WordPress Default Upload dir START.
function WP2SL_Change_Upload_Dir( $upload ) {
	$upload['subdir'] = OEPL_FILE_UPLOAD_FOLDER;
	$upload['path']   = $upload['basedir'] . $upload['subdir'];
	$upload['url']    = $upload['baseurl'] . $upload['subdir'];
	return $upload;
}
// Change WordPress Default Upload dir END.

// Front end Save Function.
add_action( 'wp_ajax_WidgetForm', 'WP2SL_WidgetForm' );
add_action( 'wp_ajax_nopriv_WidgetForm', 'WP2SL_WidgetForm' );
function WP2SL_WidgetForm() {
	global $objSugar, $wpdb;
	wp_unslash( $_POST );
	if ( isset( $_POST['_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_POST['_nonce'] ), 'upload_thumb' ) ) {
		wp_die( esc_html__( 'Security check!', 'WP2SL' ), 'Error', array( 'back_link' => true ) );
	}

	$response           = array();
	$captcha_settings   = get_option( 'OEPL_Captcha_status' );
	$success_msg        = get_option( 'OEPL_SugarCRMSuccessMessage' );
	$failure_msg        = get_option( 'OEPL_SugarCRMFailureMessage' );
	$email_notification = get_option( 'OEPL_Email_Notification' );
	$wp2sl_sel_captcha  = get_option( 'OEPL_Select_Captcha' );

	$flag = true;
	if ( $captcha_settings === 'Y' ) {
		if ( $wp2sl_sel_captcha === 'google' ) {
			if ( ( isset( $_POST['g-recaptcha-response'] ) && empty( $_POST['g-recaptcha-response'] ) ) ) {
				$response['message']        = get_option( 'OEPL_SugarCRMInvalidCaptchaMessage' );
				$response['redirectStatus'] = 'N';
				$response['success']        = 'N';
				$flag                       = false;
			}
		} else {
			$wp2sl_captcha = get_transient( 'wp2sl_captcha' );

			if ( isset( $_POST['captcha'] ) && $_POST['captcha'] !== $wp2sl_captcha ) {
				$response['message']        = get_option( 'OEPL_SugarCRMInvalidCaptchaMessage' );
				$response['redirectStatus'] = 'N';
				$response['success']        = 'N';
				$flag                       = false;
			}
		}
	}

	if ( $flag ) {
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			include_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$file_array = array();
		if ( count( $_FILES ) > 0 ) {
			add_filter( 'upload_dir', 'WP2SL_Change_Upload_Dir' );
			foreach ( $_FILES as $key => $file ) {
				$upload_overrides    = array( 'test_form' => false );
				$movefile            = wp_handle_upload( $file, $upload_overrides );
				$response['message'] = '';
				if ( $movefile ) {
					if ( ! empty( $movefile['error'] ) && $movefile['error'] ) {
						$arr = array( 'br' => array() );

						$response['message'] .= __( 'Sorry! ', 'WP2SL' ) . $file['name'] . wp_kses( __( ' could not be uploaded due to security reasons.<br>', 'WP2SL' ), $arr );

					} else {
						$movefile['name'] = $file['name'];
						$file_array[]     = $movefile;
					}
				} else {
					$arr                  = array( 'br' => array() );
					$response['message'] .= wp_kses( __( 'Error occurred while uploading file. Please try again<br>', 'WP2SL' ), $arr );
				}
			}
			remove_filter( 'upload_dir', 'WP2SL_Change_Upload_Dir' );
		}

		$insert_lead_to_sugar = $objSugar->InsertLeadToSugar( $file_array );

		if ( ! $insert_lead_to_sugar ) {
			if ( $email_notification && $email_notification === 'Y' ) {
				$email_to        = get_option( 'OEPL_Email_Notification_Receiver' );
				$query           = $wpdb->prepare( 'SELECT custom_field, wp_meta_key, wp_meta_label FROM ' . OEPL_TBL_MAP_FIELDS . ' WHERE is_show = %s ORDER BY custom_field DESC, display_order', 'Y' );
				$results         = $wpdb->get_results( $query, ARRAY_A );
				$message         = '<h3>Lead Description</h3><table border="0">';
				$is_attach_avail = 'N';
				foreach ( $results as $attr ) {
					if ( $attr['custom_field'] !== 'Y' && isset( $_POST[ $attr['wp_meta_key'] ] ) ) {
						$message .= '<tr><th>';
						$message .= $attr['wp_meta_label'] . ' : </th><td>' . sanitize_text_field( $_POST[ $attr['wp_meta_key'] ] ) . '<br />';
						$message .= '</td></tr>';
					} else {
						$is_attach_avail = 'Y';
					}
				}
				if ( $is_attach_avail === 'Y' ) {
					$message .= '<tr><th>';
					$message .= 'Attachments</th><td> : All attachments are available in your SugarCRM history/notes module';
					$message .= '</td></tr>';
				}
				$message .= '</table>';

				$subject = 'Lead generated from ' . get_bloginfo( 'name' ) . '';

				$headers = array( 'Content-Type: text/html; charset=UTF-8' );

				wp_mail( $email_to, $subject, $message, $headers );
			}

			$redirect_status = get_option( 'OEPL_User_Redirect_Status' );
			$redirect_to     = get_option( 'OEPL_User_Redirect_To' );

			if ( $redirect_status === 'Y' ) {
				$response['redirectStatus'] = 'Y';
				$response['redirectTo']     = $redirect_to;
				$response['success']        = 'Y';
			} else {
				$response['redirectStatus'] = 'N';
				$response['message']        = $success_msg;
				$response['success']        = 'Y';
			}
		} else {
			$response['redirectStatus'] = 'N';
			$response['message']        = $failure_msg;
			$response['success']        = 'N';
		}
	}
	delete_transient( 'wp2sl_captcha' );
	wp_send_json( $response );
}
// Front end Save Function End.

// Save sugarCRM config START.
add_action( 'wp_ajax_WP2SL_saveConfig', 'WP2SL_saveConfig' );
function WP2SL_saveConfig() {
	wp_unslash( $_POST );
	if ( isset( $_POST['oepl_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_POST['oepl_nonce'] ), 'upload_thumb' ) ) {
		wp_die( esc_html__( 'Security check!', 'WP2SL' ), 'Error', array( 'back_link' => true ) );
	}

	$test_conn = new WP2SLSugarCRMClass();

	if ( isset( $_POST['sugar_url'] ) && isset( $_POST['sugar_user'] ) && isset( $_POST['sugar_pass'] ) ) {
		$test_conn->sugar_url  = sanitize_text_field( $_POST['sugar_url'] );
		$test_conn->sugar_user = sanitize_user( $_POST['sugar_user'] );
		$test_conn->sugar_pass = sanitize_text_field( md5( $_POST['sugar_pass'] ) );
		if ( isset( $_POST['is_htaccess_protected'] ) && $_POST['is_htaccess_protected'] === 'Y' && isset( $_POST['HtaccessUser'] ) && isset( $_POST['HtaccessPass'] ) ) {
			$test_conn->is_htaccess_protected = true;
			$test_conn->htaccess_admin_user   = sanitize_user( $_POST['HtaccessUser'] );
			$test_conn->htaccess_admin_pass   = sanitize_text_field( $_POST['HtaccessPass'] );
		}
	}

	$t = $test_conn->LoginToSugar();
	if ( strlen( $t ) > 10 && isset( $_POST['sugar_url'] ) && isset( $_POST['sugar_user'] ) && isset( $_POST['sugar_pass'] ) ) {
		update_option( 'OEPL_SUGARCRM_URL', sanitize_text_field( $_POST['sugar_url'] ) );
		update_option( 'OEPL_SUGARCRM_ADMIN_USER', sanitize_user( $_POST['sugar_user'] ) );
		update_option( 'OEPL_SUGARCRM_ADMIN_PASS', sanitize_text_field( md5( $_POST['sugar_pass'] ) ) );

		if ( isset( $_POST['is_htaccess_protected'] ) && $_POST['is_htaccess_protected'] === 'Y' && isset( $_POST['HtaccessUser'] ) && isset( $_POST['HtaccessPass'] ) ) {
			update_option( 'OEPL_is_SugarCRM_htaccess_Protected', 'Y' );
			update_option( 'OEPL_SugarCRM_htaccess_Username', sanitize_user( $_POST['HtaccessUser'] ) );
			update_option( 'OEPL_SugarCRM_htaccess_Password', sanitize_text_field( $_POST['HtaccessPass'] ) );
		} else {
			update_option( 'OEPL_is_SugarCRM_htaccess_Protected', 'N' );
			delete_option( 'OEPL_SugarCRM_htaccess_Username' );
			delete_option( 'OEPL_SugarCRM_htaccess_Password' );
		}

		$response['status']  = 'Y';
		$response['message'] = __( 'SugarCRM credentials saved successfully', 'WP2SL' );
	} else {
		$response['status']  = 'N';
		$response['message'] = __( 'Invalid SugarCRM credentials. Please try again', 'WP2SL' );
	}
	wp_send_json( $response );
}
// Save sugarCRM config END.

// Lead fileds Sync function START.
add_action( 'wp_ajax_WP2SL_LeadFieldSync', 'WP2SL_LeadFieldSync' );
function WP2SL_LeadFieldSync() {
	global $objSugar;
	$t = $objSugar->LoginToSugar();
	if ( ! strlen( $t ) > 10 ) {
		$response['status']  = 'N';
		$response['message'] = __( 'Error occured while synchronizing fields. Please try again.', 'WP2SL' );
	} else {
		WP2SL_FieldSynchronize();
		$response['status']  = 'Y';
		$response['message'] = __( 'Fields synchronized successfully', 'WP2SL' );
	}
	wp_send_json( $response );
}
// Lead fileds Sync function END.

// General message save function START.
add_action( 'wp_ajax_WP2SL_GeneralMessagesSave', 'WP2SL_GeneralMessagesSave' );
function WP2SL_GeneralMessagesSave() {
	wp_unslash( $_POST );
	if ( isset( $_POST['oepl_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_POST['oepl_nonce'] ), 'upload_thumb' ) ) {
		wp_die( esc_html__( 'Security check!', 'WP2SL' ), 'Error', array( 'back_link' => true ) );
	}

	if ( ! empty( $_POST ) ) {
		update_option( 'OEPL_SugarCRMSuccessMessage', sanitize_text_field( $_POST['SuccessMessage'] ) );
		update_option( 'OEPL_SugarCRMFailureMessage', sanitize_text_field( $_POST['FailureMessage'] ) );
		update_option( 'OEPL_SugarCRMReqFieldsMessage', sanitize_text_field( $_POST['ReqFieldsMessage'] ) );
		update_option( 'OEPL_SugarCRMInvalidCaptchaMessage', sanitize_text_field( $_POST['InvalidCaptchaMessage'] ) );

		$response['status']  = 'Y';
		$response['message'] = __( 'General Messages saved successfully', 'WP2SL' );
	} else {
		$response['status']  = 'N';
		$response['message'] = __( 'Error occured while saving General Messages. Please try again.', 'WP2SL' );
	}
	wp_send_json( $response );
}

// Custom css save function START.
add_action( 'wp_ajax_WP2SL_save_custom_css', 'WP2SL_save_custom_css' );
function WP2SL_save_custom_css() {
	wp_unslash( $_POST );
	if ( isset( $_POST['oepl_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_POST['oepl_nonce'] ), 'upload_thumb' ) ) {
		wp_die( esc_html__( 'Security check!', 'WP2SL' ), 'Error', array( 'back_link' => true ) );
	}

	if ( ! empty( $_POST ) ) {
		update_option( 'OEPL_Form_Custom_CSS', sanitize_text_field( $_POST['css'] ) );
		$response['status']  = 'Y';
		$response['message'] = __( 'Custom CSS saved successfully', 'WP2SL' );
	} else {
		$response['status']  = 'N';
		$response['message'] = __( 'Error occured while saving Custom CSS. Please try again.', 'WP2SL' );
	}
	wp_send_json( $response );
}
// Custom css save function END.

// General settings save function START.
add_action( 'wp_ajax_WP2SL_GeneralSettingSave', 'WP2SL_GeneralSettingSave' );
function WP2SL_GeneralSettingSave() {
	wp_unslash( $_POST );
	if ( isset( $_POST['oepl_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_POST['oepl_nonce'] ), 'upload_thumb' ) ) {
		wp_die( esc_html__( 'Security check!', 'WP2SL' ), 'Error', array( 'back_link' => true ) );
	}

	if ( ! empty( $_POST ) ) {
		update_option( 'OEPL_auto_IP_addr_status', sanitize_text_field( $_POST['IPaddrStatus'] ) );
		update_option( 'OEPL_Email_Notification', sanitize_text_field( $_POST['EmailNotification'] ) );
		update_option( 'OEPL_Email_Notification_Receiver', sanitize_text_field( $_POST['EmailReceiver'] ) );
		update_option( 'OEPL_Captcha_status', sanitize_text_field( $_POST['catpchaStatus'] ) );
		update_option( 'OEPL_Select_Captcha', sanitize_text_field( $_POST['selectcaptcha'] ) );

		update_option( 'OEPL_User_Redirect_Status', sanitize_text_field( $_POST['redirectStatus'] ) );
		update_option( 'OEPL_User_Redirect_To', sanitize_text_field( $_POST['redirectTo'] ) );

		if ( ! empty( $_POST['oepl_recaptcha_site_key'] ) && ! empty( $_POST['oepl_recaptcha_secret_key'] ) ) {
			update_option( 'OEPL_RECAPTCHA_SITE_KEY', sanitize_text_field( $_POST['oepl_recaptcha_site_key'] ) );
			update_option( 'OEPL_RECAPTCHA_SECRET_KEY', sanitize_text_field( $_POST['oepl_recaptcha_secret_key'] ) );
		}

		$response['status']  = 'Y';
		$response['message'] = __( 'Plugin General Settings saved successfully', 'WP2SL' );
	} else {
		$response['status']  = 'N';
		$response['message'] = __( 'Error occured while saving Plugin General Settings. Please try again.', 'WP2SL' );
	}
	wp_send_json( $response );
}
// General settings save function END.

// Save Custom Browse field START.
add_action( 'wp_ajax_WP2SL_Custom_Field_Save', 'WP2SL_Custom_Field_Save' );
function WP2SL_Custom_Field_Save() {
	global $wpdb;
	if ( isset( $_POST['Field_Name'] ) ) {
		$field_name = sanitize_text_field( $_POST['Field_Name'] );
		$field_name = str_replace( ' ', '_', $field_name );
	}

	$results = $wpdb->get_results( $wpdb->prepare( 'SELECT pid FROM ' . OEPL_TBL_MAP_FIELDS . ' WHERE wp_meta_label=%s', $field_name ) );

	if ( count( $results ) > 0 ) {
		esc_html_e( 'Duplicate field already exist. Please try with a different field name.', 'WP2SL' );
	} else {
		$ins_array = array(
			'module'        => 'OEPL',
			'field_type'    => 'file',
			'data_type'     => 'file',
			'wp_meta_label' => $field_name,
			'is_show'       => 'Y',
			'custom_field'  => 'Y',
		);
		$insert    = $wpdb->insert( OEPL_TBL_MAP_FIELDS, $ins_array );
		if ( $insert !== false ) {
			$where     = array( 'pid' => $wpdb->insert_id );
			$upd_array = array(
				'field_name'  => 'oepl_browse_' . $wpdb->insert_id,
				'wp_meta_key' => 'oepl_browse_' . $wpdb->insert_id,
			);

			$update = $wpdb->update( OEPL_TBL_MAP_FIELDS, $upd_array, $where );
			if ( $update !== false ) {
				echo esc_html__( 'Field added successfully', 'WP2SL' );
			} else {
				echo esc_html__( 'Error occured. Please try again', 'WP2SL' );
			}
		} else {
			echo esc_html__( 'Problem adding field. Please try again', 'WP2SL' );
		}
	}
	wp_die();
}
// Save Custom Browse field END.

// DELETE custom browse field START.
add_action( 'wp_ajax_WP2SL_Custom_Field_Delete', 'WP2SL_Custom_Field_Delete' );
function WP2SL_Custom_Field_Delete() {
	global $wpdb;
	wp_unslash( $_POST );
	if ( isset( $_POST['pid'] ) ) {
		$pid    = sanitize_text_field( $_POST['pid'] );
		$where  = array( 'pid' => $pid );
		$delete = $wpdb->delete( OEPL_TBL_MAP_FIELDS, $where );
		if ( $delete !== false ) {
			echo esc_html__( 'Field deleted successfully', 'WP2SL' );
		} else {
			echo esc_html__( 'Error occured ! Please try again', 'WP2SL' );
		}
	}
	wp_die();
}
// DELETE custom browse field END.

// Submenu under SugarCRM Menu START.
function WP2SL_SugarCRM_Submenu_function() {
	?>
	<div class="wrap">
		<div class="wp2sl_browse_div_cls">
			<h1><?php esc_html_e( 'SugarCRM Lead module field list', 'WP2SL' ); ?></h1>
			<table class="OEPL_add_field_box">
				<tr height="25" class="OEPL_hide_panel" is_show="No">
					<td><img src="<?php echo esc_url( OEPL_PLUGIN_URL . 'image/plus-icon.png' ); ?>" valign="center" /></td>
					<td colspan="4" class="cstm_browse_btn" valign="center"><?php esc_html_e( 'Add Custom Browse Field', 'WP2SL' ); ?></td>
				</tr>
				<tr class="OEPL_hidden_panel">
					<td></td>
					<td width="80"><?php esc_html_e( 'Field name :', 'WP2SL' ); ?> </td>
					<td width="80"><input type="text" id="OEPL_Custom_Field_Name" name="OEPL_Custom_Field_Name" /></td>
					<td align="left"><button class="button button-primary OEPL_Custom_Field_Add"><?php esc_html_e( 'Add Field', 'WP2SL' ); ?></button></td>
				</tr>
				<tr class="OEPL_hidden_panel">
					<td></td>
					<td colspan="4"><span class="description"><strong><?php esc_html_e( 'Note:', 'WP2SL' ); ?></strong> <?php esc_html_e( 'Uploaded files will be available in Notes module of SugarCRM and History subpanel of Lead module.', 'WP2SL' ); ?></span></td>
				</tr>
			</table>
		</div>
		<div class="OEPL_Vertical_Banner">
			<form id="OEPL-Leads_table" method="post">
				<?php
				include_once OEPL_PLUGIN_DIR . 'Fields_map_table.php';
				$table = new Fields_Map_Table();
				echo "<input type='hidden' id='oepl_nonce' value='" . esc_attr( wp_create_nonce( 'my_thumb' ) ) . "' name='oepl_nonce' />";
				echo '<input type="hidden" name="page" value="mapping_table" />';
				$table->search_box( 'Search', 'LeadSearchID' );
				$table->prepare_items();
				$table->display();
				?>
			</form>
		</div>
	</div>
	<?php
}

// Submenu under SugarCRM Menu END.
function WP2SL_FieldSynchronize() {
	global $objSugar, $wpdb;

	if ( $objSugar->sugar_sess_id === '' ) {
		$objSugar->LoginToSugar();
	}

	if ( ! strlen( $objSugar->sugar_sess_id ) > 10 ) {
		return false;
	}

	// Start - Set Module Fields in Table.
	foreach ( $objSugar->module_list as $key => $val ) {
		$module_name   = $val;
		$module_fileds = $objSugar->getLeadFieldsList();
		$sugar_flds    = array();

		if ( count( $module_fileds['module_fields'] ) > 0 ) {
			foreach ( $module_fileds['module_fields'] as $fkey => $fval ) {
				$f_type  = $fval['type'];
				$ins_arr = array();
				switch ( $f_type ) {
					case 'enum':
						$ins_arr['field_type']  = 'select';
						$ins_arr['field_value'] = serialize( $fval['options'] );
						break;
					case 'radioenum':
						$ins_arr['field_type']  = 'radio';
						$ins_arr['field_value'] = serialize( $fval['options'] );
						break;
					case 'bool':
						$ins_arr['field_type']  = 'checkbox';
						$ins_arr['field_value'] = serialize( $fval['options'] );
						break;
					case 'text':
						$ins_arr['field_type']  = 'textarea';
						$ins_arr['field_value'] = '';
						break;
					case 'file':
						$ins_arr['field_type']  = 'file';
						$ins_arr['field_value'] = '';
						break;
					default:
						$ins_arr['field_type']  = 'text';
						$ins_arr['field_value'] = '';
						break;
				}
				$ins_arr['module']        = $module_name;
				$ins_arr['field_name']    = $fkey;
				$ins_arr['wp_meta_key']   = OEPL_METAKEY_EXT . strtolower( $module_name ) . '_' . $fkey;
				$ins_arr['wp_meta_label'] = $fval['label'];
				$ins_arr['data_type']     = $fval['type'];
				$ins_arr['wp_meta_label'] = str_replace( ':', '', trim( $ins_arr['wp_meta_label'] ) );

				$query     = $wpdb->prepare( 'SELECT count(*) as tot FROM ' . OEPL_TBL_MAP_FIELDS . ' WHERE module = %s AND field_name = %s', $ins_arr['module'], $ins_arr['field_name'] );
				$rec_count = $wpdb->get_results( $query, ARRAY_A );

				if ( ! in_array( $ins_arr['field_name'], $objSugar->exclude_fields ) ) {
					$sugar_flds[] = $ins_arr['field_name'];
					if ( $rec_count[0]['tot'] <= 0 ) {
						$sql = 'INSERT INTO ' . OEPL_TBL_MAP_FIELDS . " SET 
						module 		  = '" . $ins_arr['module'] . "' , 
						field_type 	  = '" . $ins_arr['field_type'] . "' , 
						data_type 	  = '" . $ins_arr['data_type'] . "' , 
						field_name 	  = '" . $ins_arr['field_name'] . "' , 
						field_value   = '" . $ins_arr['field_value'] . "' , 
						wp_meta_label = '" . $ins_arr['wp_meta_label'] . "' , 
						wp_meta_key   = '" . $ins_arr['wp_meta_key'] . "' ";
						$wpdb->query( $sql );
					} else {
						$sql = 'UPDATE ' . OEPL_TBL_MAP_FIELDS . " SET 
							module 		  = '" . $ins_arr['module'] . "' , 
							field_type 	  = '" . $ins_arr['field_type'] . "' , 
							data_type 	  = '" . $ins_arr['data_type'] . "' , 
							field_name 	  = '" . $ins_arr['field_name'] . "' , 
							field_value   = '" . $ins_arr['field_value'] . "' , 
							wp_meta_label = '" . $ins_arr['wp_meta_label'] . "' , 
							wp_meta_key   = '" . $ins_arr['wp_meta_key'] . "' 
					WHERE module = '" . $ins_arr['module'] . "' AND field_name = '" . $ins_arr['field_name'] . "'";
						$wpdb->query( $sql );
					}
				}
			}
		}

		$query        = $wpdb->prepare( 'SELECT pid, field_name, wp_meta_key FROM ' . OEPL_TBL_MAP_FIELDS . ' WHERE module = %s', $module_name );
		$wp_fields_rs = $wpdb->get_results( $query, ARRAY_A );
		$fcnt         = count( $wp_fields_rs );
		for ( $i = 0; $i < $fcnt; $i++ ) {
			if ( ! in_array( $wp_fields_rs[ $i ]['field_name'], $sugar_flds ) ) {
				$del_sql = $wpdb->prepare( 'DELETE FROM ' . OEPL_TBL_MAP_FIELDS . ' WHERE pid = %d AND module = %s', $wp_fields_rs[ $i ]['pid'], $module_name );
				$wpdb->query( $del_sql );
			}
		}
	}
	// End - Set Module Fields in Table.
}

// Test CRM connection - START.
add_action( 'wp_ajax_WP2SL_TestSugarConn', 'WP2SL_TestSugarConn' );
function WP2SL_TestSugarConn() {
	$test_conn = new WP2SLSugarCRMClass();
	if ( isset( $_POST['URL'] ) && isset( $_POST['USER'] ) && isset( $_POST['PASS'] ) ) {
		$test_conn->sugar_url  = sanitize_text_field( $_POST['URL'] );
		$test_conn->sugar_user = sanitize_user( $_POST['USER'] );
		$test_conn->sugar_pass = sanitize_text_field( md5( $_POST['PASS'] ) );

		if ( $_POST['is_htaccess_protected'] === 'Y' && isset( $_POST['HtaccessUser'] ) && isset( $_POST['HtaccessPass'] ) ) {
			$test_conn->is_htaccess_protected = true;
			$test_conn->htaccess_admin_user   = sanitize_user( $_POST['HtaccessUser'] );
			$test_conn->htaccess_admin_pass   = sanitize_text_field( $_POST['HtaccessPass'] );
		}
	}

	$response = $test_conn->LoginToSugar();

	if ( strlen( $response ) > 10 ) {
		$set['status']  = 'Y';
		$set['message'] = __( 'Connection Established Successfully', 'WP2SL' );
	} else {
		$set['status']  = 'N';
		$set['message'] = __( 'Cannot connect to your Sugar / SuiteCRM. Please try again with correct Sugar / SuiteCRM credentials', 'WP2SL' );
	}
	wp_send_json( $set );
}
// Test CRM connection - END.