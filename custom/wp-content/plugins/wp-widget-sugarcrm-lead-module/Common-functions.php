<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( esc_html__( 'Please don\'t access this file directly.', 'WP2SL' ) );
}

function WP2SL_getHTMLElement( $type, $name_id, $values, $set_value, $set_class = '', $jquery_cls = '', $extra = '' ) {
	$element = '';
	switch ( $type ) {
		case 'text':
			$element = '<input type="text" name="' . $name_id . '" id="' . $name_id . '" value="' . $set_value . '" class="' . $set_class . $jquery_cls . '" ' . $extra . ' />';
			break;
			
		case 'select':
			$element = '<select name="' . $name_id . '" class="' . $jquery_cls . '" id="' . $name_id . '" ' . $extra . '>';
			$s_obj   = unserialize( $values );
			foreach ( $s_obj as $k => $v ) {
				if ( $v['name'] === $set_value ) {
					$selected = 'selected="selected"';
				} else {
					$selected = '';
				}
				$element .= '<option value="' . $v['name'] . '" ' . $selected . '>' . $v['value'] . '</option>';
			}
			$element .= '</select>';
			break;
		case 'radio':
			$s_obj = unserialize( $values );
			foreach ( $s_obj as $k => $v ) {
				if ( $v['value'] === $set_value ) {
					$selected = 'checked="checked""';
				} else {
					$selected = '';
				}
				$element .= '<input type="radio" name="' . $name_id . '" id="' . $name_id . '_' . $v['value'] . '" value="' . $v['value'] . '" class="Hradio ' . $set_class . $jquery_cls . '" ' . $selected . ' ' . $extra . '/>&nbsp;' . $v['name'] . '&nbsp;&nbsp;';          
			}
			break;
		case 'checkbox':
			if ( 1 === $set_value ) {
				$selected = 'checked="checked"';
			} else {
				$selected = '';
			}
			$s_obj    = unserialize( $values );
			$element  = '';
			$element .= '<input type="checkbox" name="' . $name_id . '" ' . $selected . ' class="' . $jquery_cls . '" id="' . $name_id . '" value="1" ' . $extra . ' />';
			break;
		case 'textarea':
			$element = '<textarea cols="30" class="' . $jquery_cls . '" rows="5" name="' . $name_id . '" ' . $extra . ' id="' . $name_id . '">' . $set_value . '</textarea>';
			break;
		
		case 'file':
			$element = '<input type="file" name="' . $name_id . '" id="' . $name_id . '" class="' . $jquery_cls . '" ' . $extra . '  />';
			break;
				
		default:
			$element = '';
			break;
	}
	return $element;
}

function NumFrmt( $val, $separator = ',' ) {
	$val = str_replace( ',', '', $val );
	if ( $val === '' ) {
		return $val;
	}
	$val = number_format( $val, 0, '', $separator );
	return $val;
}

function NumFrmtForTraget( $val, $separator = ',' ) {
	$val = str_replace( ',', '', $val );
	if ( $val === '' ) {
		return $val;
	}
	$val = number_format( $val, 0, '', $separator );
	return $val;
}
