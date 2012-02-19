<?php

function _wpsc_form_attributes( $atts ) {
	$attributes = '';
	foreach ( $atts as $attribute => $value ) {
		$attributes .= ' ' . $attribute . '="' . esc_attr( $value ) . '" ';
	}
	$attributes = trim( $attributes );
	return $attributes;
}

function _wpsc_input_type_field( $atts, $echo = true ) {
	$attributes    = _wpsc_form_attributes( $atts );
	$output = "<input {$attributes} />";
	if ( $echo )
		echo $output;
	return $output;
}

function wpsc_submitted_value( $name, $default = '', &$from = null ) {
	if ( ! is_array( $from ) )
		$from =& $_REQUEST;
	return isset( $from[$name] ) ? $from[$name] : $default;
}

function wpsc_checked( $name, $current = true, $default = false, $echo = true, &$from = null ) {
	if ( ! is_array( $from ) )
		$from =& $_REQUEST;

	if ( isset( $from[$name] ) )
		$checked = $from[$name];
	else
		$checked = $default;

	return checked( $checked, $current, $echo );
}

function wpsc_form_label( $label, $for = '', $atts = array(), $echo = true ) {
	if ( ! is_array( $atts ) )
		$atts = array();

	if ( ! empty( $for ) )
		$atts['for'] = $for;

	$output = '<label ' . _wpsc_form_attributes( $atts ) . '">' . esc_html( $label ) . '</label>';
	if ( $echo )
		echo $output;
	return $output;
}

function wpsc_form_input( $name, $value = '', $atts = array(), $echo = true ) {
	if ( ! is_array( $atts ) )
		$atts = array();

	$atts['name']  = $name;
	$atts['value'] = $value;
	$atts['type']  = 'text';
	return _wpsc_input_type_field( $atts, $echo );
}

function wpsc_form_password( $name, $atts = array(), $echo = true ) {
	if ( ! is_array( $atts ) )
		$atts = array();

	$atts['name'] = $name;
	$atts['type'] = 'password';
	return _wpsc_input_type_field( $atts, $echo );
}

function wpsc_form_checkbox( $name, $value, $label = false, $checked = false, $atts = array(), $echo = true ) {
	if ( ! is_array( $atts ) )
		$atts = array();

	$atts['name'] = $name;
	$atts['type'] = 'checkbox';
	$atts['value'] = $value;

	if ( $checked )
		$atts['checked'] = 'checked';

	if ( $label ) {
		$output = '<label class="wpsc-form-checkbox-wrapper">' . _wpsc_input_type_field( $atts, false ) . esc_html ( $label ) . '</label>';
		if ( ! $echo )
			return $output;
		echo $output;
	} else {
		return _wpsc_input_type_field( $atts, $echo );
	}
}

function wpsc_form_checkboxes( $name, $selected_value = '', $options = array(), $atts = array(), $echo = true ) {
	if ( ! is_array( $atts ) )
		$atts = array();

	$output = '';
	foreach ( $options as $value => $title ) {
		$option_atts = $atts;
		$option_atts['id'] = $atts['id'] . '-' . sanitize_title( $value );
		$checked = ( $value == $selected_value );
		$output .= wpsc_form_checkbox( $name, $value, $title, $checked, $option_atts, false );
	}

	if ( ! $echo )
		return $output;

	echo $output;
}

function wpsc_form_radio( $name, $value, $label = false, $checked = false, $atts = array(), $echo = true ) {
	if ( ! is_array( $atts ) )
		$atts = array();

	$atts['name'] = $name;
	$atts['type'] = 'radio';
	$atts['value'] = $value;

	if ( $checked )
		$atts['checked'] = 'checked';

	if ( $label ) {
		$output = '<label class="wpsc-form-radio-wrapper">' . _wpsc_input_type_field( $atts, false ) . esc_html( $label ) . '</label>';
		if ( ! $echo )
			return $output;
		echo $output;
	} else {
		return _wpsc_input_type_field( $atts, $echo );
	}
}

function wpsc_form_radios( $name, $selected_value = '', $options = array(), $atts = array(), $echo = true ) {
	if ( ! is_array( $atts ) )
		$atts = array();

	$output = '';
	foreach ( $options as $value => $title ) {
		$option_atts = $atts;
		$option_atts['id'] = $atts['id'] . '-' . sanitize_title( $value );
		$checked = ( $value == $selected_value );
		$output .= wpsc_form_radio( $name, $value, $title, $checked, $option_atts, false );
	}

	if ( ! $echo )
		return $output;

	echo $output;
}

function wpsc_form_select( $name, $selected_value = '', $options = array(), $atts = array(), $echo = true ) {
	if ( ! is_array( $atts ) )
		$atts = array();

	$atts['name'] = $name;

	$output = '<select ' . _wpsc_form_attributes( $atts ) . '>';
	foreach ( $options as $value => $option_title ) {
		$output .= '<option value="' . esc_attr( $value ) . '" ' . checked( $value, $selected_value ) . '>' . esc_html( $option_title ) . '</option>';
	}
	$output .= '</select>';

	if ( ! $echo )
		return $output;

	echo $output;
}

function wpsc_form_textarea( $name, $value = '', $atts = array(), $echo = true ) {
	if ( ! is_array( $atts ) )
		$atts = array();

	$atts['name'] = $name;

	$output = '<textarea ' . _wpsc_form_attributes( $atts ) . '>';
	$output .= esc_html( $value );
	$output .= '</textarea>';

	if ( ! $echo )
		return $output;

	echo $output;
}