<?php

function _wpsc_populate_field_default_args( $field ) {
	static $field_id = 0;
	$field_id ++;

	$field_defaults = array(
		'title'   => '',
		'type'    => 'textfield',
		'id'      => "wpsc-field-{$field_id}",
		'value'   => '',
		'primary' => false,
		'name'    => $field_id,
		'class'   => "wpsc-field",
	);

	$field_defaults['class'] .= ' ' . "wpsc-field-{$field['name']}";
	$field_defaults['title_validation'] = isset( $field['title'] ) ? strtolower( $field['title'] ) : $field['title'];

	$field = wp_parse_args( $field, $field_defaults );

	return $field;
}

function _wpsc_get_field_output( $field, $r ) {
	$output = '';

	$field = _wpsc_populate_field_default_args( $field );

	$before_field = apply_filters( 'wpsc_field_before', $r['before_field'], $field, $r );
	$before_field = sprintf( $before_field, $field['id'], $field['class']);

	$output .= $before_field;
	$output .= apply_filters( 'wpsc_control_before'          , $r['before_controls'], $field, $r );
	$output .= apply_filters( "wpsc_control_{$field['type']}", ''                   , $field, $r );
	$output .= apply_filters( 'wpsc_control_after'           , $r['after_controls'] , $field, $r );
	$output .= apply_filters( 'wpsc_field_after'             , $r['after_field']    , $field, $r );

	return $output;
}

function _wpsc_get_action_field_output( $field, $r ) {
	static $field_id = 0;
	$field_id ++;
	$output = '';

	$field_defaults = array(
		'title' => '',
		'id'    => "wpsc-action-field-{$field_id}",
		'value' => '',
		'class' => '',
		'name'  => '',
	);

	if ( $field['type'] == 'submit' )
		$field_defaults['primary'] = false;

	$field = wp_parse_args( $field, $field_defaults );

	$field_class = 'wpsc-field';
	if ( ! empty( $field['name'] ) )
		$field_class .= " wpsc-field-{$field['name']}";
	$field['class'] .= ' ' . $field_class;

	$output .= apply_filters( 'wpsc_action_field_before'          , '', $field, $r );
	$output .= apply_filters( "wpsc_action_field_{$field['type']}", '', $field, $r );
	$output .= apply_filters( 'wpsc_action_field_after'           , '', $field, $r );

	return $output;
}

function wpsc_get_form_output( $args ) {
	static $form_id;

	$form_id ++;

	$defaults = array(
		'method'              => 'post',
		'id'                  => "wpsc-form-{$form_id}",
		'class'               => 'wpsc-form wpsc-form-horizontal',
		'before_field'        => '<div id="%1$s" class="%2$s">',
		'after_field'         => '</div>',
		'before_field_description' => '<div id="%1$s" class="%2$s">',
		'after_field_description'  => '</div>',
		'before_label'        => '',
		'after_label'         => '',
		'before_controls'     => '<div class="wpsc-controls">',
		'after_controls'      => '</div>',
		'before_form_actions' => '<div class="wpsc-form-actions">',
		'after_form_actions'  => '</div>',
	);

	$defaults = apply_filters( 'wpsc_get_form_output_default_args', $defaults );

	$r = wp_parse_args( $args, $defaults );

	if ( empty( $r['fields'] ) )
		return '';

	$output = "<form id='{$r['id']}' method='{$r['method']}' action='{$r['action']}' class='{$r['class']}'>";

	foreach ( $r['fields'] as $field ) {
		$output .= _wpsc_get_field_output( $field, $r );
	}

	$output .= $r['before_form_actions'];

	foreach ( $r['form_actions'] as $action_field ) {
		$output .= _wpsc_get_action_field_output( $action_field, $r );
	}

	$output .= $r['after_form_actions'];

	$output .= '</form>';

	return $output;
}

function wpsc_display_form( $args ) {
	echo wpsc_get_form_output( $args );
}

add_filter( 'wpsc_control_before'         , '_wpsc_filter_control_before'   , 10, 3 );
add_filter( 'wpsc_control_after'          , '_wpsc_filter_control_after'    , 15, 3 );
add_filter( 'wpsc_control_textfield'      , '_wpsc_filter_control_textfield', 10, 3 );
add_filter( 'wpsc_control_password'       , '_wpsc_filter_control_password' , 10, 3 );
add_filter( 'wpsc_control_select'         , '_wpsc_filter_control_select'   , 10, 3 );
add_filter( 'wpsc_control_select_country' , '_wpsc_filter_control_select_country' , 10, 3 );
add_filter( 'wpsc_control_select_region'  , '_wpsc_filter_control_select_region'  , 10, 3 );
add_filter( 'wpsc_control_submit'         , '_wpsc_filter_control_submit'   , 10, 3 );
add_filter( 'wpsc_control_hidden'         , '_wpsc_filter_control_hidden'   , 10, 3 );
add_filter( 'wpsc_control_button'         , '_wpsc_filter_control_button'   , 10, 3 );
add_filter( 'wpsc_control_checkbox'       , '_wpsc_filter_control_checkbox' , 10, 3 );
add_filter( 'wpsc_control_radio'          , '_wpsc_filter_control_radio'    , 10, 3 );
add_filter( 'wpsc_control_checkboxes'     , '_wpsc_filter_control_checkboxes', 10, 3 );
add_filter( 'wpsc_control_radios'         , '_wpsc_filter_control_radios'    , 10, 3 );

add_filter( 'wpsc_action_field_submit', '_wpsc_filter_control_submit', 10, 3 );
add_filter( 'wpsc_action_field_hidden', '_wpsc_filter_control_hidden', 10, 3 );
add_filter( 'wpsc_action_field_button', '_wpsc_filter_control_button', 10, 3 );

function _wpsc_filter_control_before( $output, $field, $args ) {
	extract( $field );

	$label_output = '';
	$controls_without_labels = array( 'submit', 'checkbox', 'radio', 'hidden' );
	if ( ! in_array( $type, $controls_without_labels ) ) {
		$label_output .= $args['before_label'];
		$label_output .= wpsc_form_label( $title, $id . '-control', array( 'id' => $id . '-label', 'class' => 'wpsc-control-label' ), false );
		$label_output .= $args['after_label'];
	}

	$output = $label_output . $output;

	return $output;
}

function _wpsc_filter_control_after( $output, $field, $args ) {
	if ( empty( $field['description'] ) )
		return $output;

	$output .= sprintf( $args['before_field_description'], $field['id'] . '-description', 'wpsc-field-description' );
	$output .= apply_filters( 'wpsc_field_description', esc_html( $field['description'] ) );
	$output .= $args['after_field_description'];

	return $output;
}

function _wpsc_filter_control_textfield( $output, $field, $args ) {
	extract( $field );

	$output .= wpsc_form_input( $name, $value, array( 'id' => $id . '-control' ), false );
	return $output;
}

function _wpsc_filter_control_password( $output, $field, $args ) {
	extract( $field );

	$output .= wpsc_form_password( $name, array( 'id' => $id . '-control' ), false );
	return $output;
}

function _wpsc_filter_control_select( $output, $field, $args ) {
	extract( $field );

	$output .= wpsc_form_select( $name, $value, $options, array( 'id' => $id . '-control' ), false );
	return $output;
}

function wpsc_checkout_field_country_dropdown( $output, $field, $args ) {
	extract( $field );

	$country_data = WPSC_Country::get_all();
	$options = array();
	foreach ( $country_data as $country ) {
		$options[$country->isocode] = $country->country;
	}

	$output .= wpsc_form_select( $name, $value, $options, array( 'id' => $id . '-control' ), false );
	return $output;
}

function wpsc_checkout_field_select_region( $output, $field, $args ) {
	global $wpdb;

	extract( $field );

	$state_data = $wpdb->get_results( $wpdb->prepare( "SELECT `regions`.* FROM `" . WPSC_TABLE_REGION_TAX . "` AS `regions` INNER JOIN `" . WPSC_TABLE_CURRENCY_LIST . "` AS `country` ON `country`.`id` = `regions`.`country_id` WHERE `country`.`isocode` IN(%s)", $country ) );
	$options = array();
	foreach ( $state_data as $state ) {
		$options[$state->id] = $state->name;
	}

	$output .= wpsc_form_select( $name, $value, $options, array( 'id' => $id . '-control' ), false );
	return $output;
}

function _wpsc_filter_control_submit( $output, $field, $args ) {
	extract( $field );

	$class = $args['id'] . '-button wpsc-button';
	if ( $field['primary'] )
		$class .= ' wpsc-button-primary';

	$output .= wpsc_form_submit( $name, $title, array( 'class' => $class ), false );

	return $output;
}

function _wpsc_filter_control_hidden( $output, $field, $args ) {
	extract( $field );

	$class = $args['id'] . '-hidden wpsc-hidden-input';
	$id = $args['id'] . '-' . $field['name'];

	$output .= wpsc_form_hidden( $name, $value, array( 'class' => $class, 'id' => $id ), false );

	return $output;
}

function _wpsc_filter_control_button( $output, $field, $args ) {
	extract( $field );
	$class = $args['id'] . '-button wpsc-button';
	if ( $field['primary'] )
		$class .= ' wpsc-button-primary';
	if ( ! isset( $field['icon'] ) )
		$field['icon'] = '';

	$output .= wpsc_form_button( $name, $title, array( 'class' => $class, 'icon' => $field['icon'] ), false );

	return $output;
}

function _wpsc_filter_control_checkbox( $output, $field, $args ) {
	extract( $field );
	if ( ! isset( $checked ) )
		$checked = false;
	$output .= wpsc_form_checkbox( $name, $value, $title, $checked, array( 'id' => $id . '-control' ), false );
	return $output;
}

function _wpsc_filter_control_radio( $output, $field, $args ) {
	extract( $field );
	if ( ! isset( $checked ) )
		$checked = false;
	$output .= wpsc_form_radio( $name, $value, $title, $checked, array( 'id' => $id . '-control' ), false );
	return $output;
}

function _wpsc_filter_control_checkboxes( $output, $field, $args ) {
	extract( $field );
	if ( ! isset( $value ) )
		$value = '';
	$output .= wpsc_form_checkboxes( $name, $value, $options, array( 'id' => $id . '-control' ), false );
	return $output;
}

function _wpsc_filter_control_radios( $output, $field, $args ) {
	extract( $field );
	if ( ! isset( $value ) )
		$value = '';
	$output .= wpsc_form_radios( $name, $value, $options, array( 'id' => $id . '-control' ), false );
}