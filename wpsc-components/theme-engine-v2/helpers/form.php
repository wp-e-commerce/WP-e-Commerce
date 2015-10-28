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
		'class'   => '',
		'rules'   => ''
	);

	if ( isset( $field['name'] ) ) {
		$field_defaults['class'] .= ' wpsc-field-' . sanitize_title_with_dashes( $field['name'] );
	}

	$field_defaults['title_validation'] = isset( $field['title'] ) ? strtolower( $field['title'] ) : $field_defaults['title'];

	$field   = wp_parse_args( $field, $field_defaults );
	$classes = array(
		'wpsc-field',
		"wpsc-field-{$field['type']}"
	);

	if ( ! empty( $field['class'] ) ) {
		$classes[] = $field['class'];
	}

	$rules = explode( '|', $field['rules'] );

	if ( in_array( 'required', $rules ) ) {
		$classes[] = 'wpsc-field-required';
	}

	$field['class'] = implode( ' ', $classes );

	return $field;
}

function _wpsc_get_field_output( $field, $r ) {
	$output = '';
	$field  = _wpsc_populate_field_default_args( $field );

	if ( $field['type'] == 'fieldset' ) {
		return _wpsc_get_fieldset_output( $field, $r );
	}

	$before_field = apply_filters( 'wpsc_field_before', $r['before_field'], $field, $r );
	$before_field = sprintf( $before_field, $field['id'], $field['class'] );

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

	if ( $field['type'] == 'submit' ) {
		$field_defaults['primary'] = false;
	}

	$field = wp_parse_args( $field, $field_defaults );

	$field_class = 'wpsc-field';

	if ( ! empty( $field['name'] ) ) {
		$field_class .= " wpsc-field-{$field['name']}";
	}

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
		'before_inline_validation_error' => '<div id="%1$s" class="%2$s">',
		'after_inline_validation_error'  => '</div>',
		'inline_validation_errors' => false,
		'fieldsets' => array(),
	);

	$defaults = apply_filters( 'wpsc_get_form_output_default_args', $defaults );

	$r = wp_parse_args( $args, $defaults );

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

function _wpsc_get_fieldset_output( $fieldset, $r ) {
	$id = '';

	if ( ! empty( $fieldset['id'] ) ) {
		$id = ' id="' . $fieldset['id'] . '"';
	}

	$output = '<fieldset' . $id . '>';
	$output .= '<legend>' . $fieldset['title'] . '</legend>';

	foreach ( $fieldset['fields'] as $field ) {
		$output .= _wpsc_get_field_output( $field, $r );
	}

	$output .= '</fieldset>';

	return $output;
}

function wpsc_display_form( $args ) {
	echo wpsc_get_form_output( $args );
}

add_filter( 'wpsc_control_before'        , '_wpsc_filter_control_before'        , 10, 3 );
add_filter( 'wpsc_control_after'         , '_wpsc_filter_control_after'         , 15, 3 );
add_filter( 'wpsc_control_textfield'     , '_wpsc_filter_control_textfield'     , 10, 3 );
add_filter( 'wpsc_control_password'      , '_wpsc_filter_control_password'      , 10, 3 );
add_filter( 'wpsc_control_select'        , '_wpsc_filter_control_select'        , 10, 3 );
add_filter( 'wpsc_control_select_country', '_wpsc_filter_control_select_country', 10, 3 );
add_filter( 'wpsc_control_select_region' , '_wpsc_filter_control_select_region' , 10, 3 );
add_filter( 'wpsc_control_submit'        , '_wpsc_filter_control_submit'        , 10, 3 );
add_filter( 'wpsc_control_hidden'        , '_wpsc_filter_control_hidden'        , 10, 3 );
add_filter( 'wpsc_control_button'        , '_wpsc_filter_control_button'        , 10, 3 );
add_filter( 'wpsc_control_checkbox'      , '_wpsc_filter_control_checkbox'      , 10, 3 );
add_filter( 'wpsc_control_radio'         , '_wpsc_filter_control_radio'         , 10, 3 );
add_filter( 'wpsc_control_checkboxes'    , '_wpsc_filter_control_checkboxes'    , 10, 3 );
add_filter( 'wpsc_control_radios'        , '_wpsc_filter_control_radios'        , 10, 3 );
add_filter( 'wpsc_control_heading'       , '_wpsc_filter_control_heading'       , 10, 3 );
add_filter( 'wpsc_control_before'        , '_wpsc_filter_control_before_heading', 10, 3 );
add_filter( 'wpsc_control_after'         , '_wpsc_filter_control_after_heading' , 10, 3 );

add_filter( 'wpsc_action_field_submit', '_wpsc_filter_control_submit', 10, 3 );
add_filter( 'wpsc_action_field_hidden', '_wpsc_filter_control_hidden', 10, 3 );
add_filter( 'wpsc_action_field_button', '_wpsc_filter_control_button', 10, 3 );

function _wpsc_filter_control_before( $output, $field, $args ) {
	extract( $field );

	$label_output            = '';
	$controls_without_labels = array( 'submit', 'checkbox', 'radio', 'hidden', 'heading' );

	if ( ! in_array( $type, $controls_without_labels ) ) {
		$label_output .= $args['before_label'];
		$label_output .= wpsc_form_label( $title, $id . '-control', array( 'id' => $id . '-label', 'class' => 'wpsc-control-label' ), false );
		$label_output .= $args['after_label'];
	}

	$output = $label_output . $output;

	return $output;
}

function _wpsc_filter_control_before_heading( $output, $field, $args ) {
	if ( $field['type'] != 'heading' ) {
		return $output;
	}

	return '';
}

function _wpsc_filter_control_after_heading( $output, $field, $args ) {
	if ( $field['type'] != 'heading' ) {
		return $output;
	}

	return '';
}

function _wpsc_filter_control_after( $output, $field, $args ) {
	if ( ! empty( $field['description'] ) && ! in_array( $field['type'], array( 'checkboxes', 'radios' ) ) ) {
		$output .= _wpsc_get_field_description( $field, $args );
	}

	if ( $args['inline_validation_errors'] ) {
		$output .= sprintf( $args['before_inline_validation_error'], $field['id'] . '-error', 'wpsc-inline-validation-error' );
		$errors  = wpsc_get_inline_validation_error( $field['name'] );
		$output .= apply_filters( 'wpsc_field_inline_validation_error', implode( '<br />', $errors ), $errors, $field, $args );
		$output .= $args['after_inline_validation_error'];
	}

	return $output;
}

function _wpsc_get_field_description( $field, $args ) {
	$output  = sprintf( $args['before_field_description'], $field['id'] . '-description', 'wpsc-field-description' );
	$output .= apply_filters( 'wpsc_field_description', $field['description'] );
	$output .= $args['after_field_description'];

	return $output;
}

/**
 * Add "Copy billing details" to the shipping form header.
 *
 * @since  0.1
 * @access private
 *
 * @uses   get_option()   Get 'shippingsameasbilling' option
 * @uses   apply_filters() Applies 'wpsc_copy_billing_details_button_title' filter
 * @uses   apply_filters() Applies 'wpsc_copy_billing_details_button' filter
 * @param  string $output HTML output of the heading field
 * @param  array $field  field arguments
 * @param  array $args   form arguments
 * @return string        HTML output for the heading
 */
function _wpsc_filter_control_heading( $output, $field, $args ) {
	$output .= sprintf( '<strong>%s</strong>', $field['title'] );
	if ( get_option( 'shippingsameasbilling', 0 ) && ! empty( $field['shipping_heading'] ) ) {
		$title = apply_filters(
			'wpsc_copy_billing_details_button_title',
			__( 'Copy billing details', 'wp-e-commerce' )
		);

		$button = wpsc_form_input(
			'wpsc_copy_billing_details',
			$title,
			array(
				'type' => 'button',
				'class' => 'wpsc-button wpsc-button-mini',
			),
			false
		);

		$button = apply_filters( 'wpsc_copy_billing_details_button', $button );

		$output .= $button;
	}

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

function _wpsc_filter_control_select_country( $output, $field, $args ) {
	extract( $field );

	$country_data = WPSC_Countries::get_countries();
	$options      = array();

	foreach ( $country_data as $country ) {
		$isocode      = $country->get_isocode();
		$alternatives = array( $country->get_isocode());

		switch ( $isocode ) {
			case 'US':
				$alternatives[] = __( 'United States of America', 'wp-e-commerce' );
				break;
			case 'GB':
				$alternatives[] = __( 'Great Britain', 'wp-e-commerce' );
				$alternatives[] = __( 'England', 'wp-e-commerce' );
				$alternatives[] = __( 'Wales', 'wp-e-commerce' );
				$alternatives[] = __( 'UK', 'wp-e-commerce' );
				$alternatives[] = __( 'Scotland', 'wp-e-commerce' );
				$alternatives[] = __( 'Northern Ireland', 'wp-e-commerce' );
				break;
		}

		$alternatives = apply_filters( 'wpsc_country_alternative_spellings', $alternatives, $isocode, $country );

		$options[ $country->get_isocode() ] = array(
			'title'      => $country->get_name(),
			'attributes' => array(
				'data-alternative-spellings' => implode( ' ', $alternatives )
			),
		);
	}

	$output .= wpsc_form_select(
		$name,
		$value,
		$options,
		array(
			'id' => $id . '-control',
			'class' => 'wpsc-form-select-country'
		),
		false );
	return $output;
}

function _wpsc_filter_control_select_region( $output, $field, $args ) {
	global $wpdb;

	extract( $field );

	$options = array();

	if ( $country == 'all' ) {
		$state_data = $wpdb->get_results( "SELECT `regions`.*, country.country as country, country.isocode as country_isocode FROM `" . WPSC_TABLE_REGION_TAX . "` AS `regions` INNER JOIN `" . WPSC_TABLE_CURRENCY_LIST . "` AS `country` ON `country`.`id` = `regions`.`country_id`" );
		$options[__( 'No State', 'wp-e-commerce' )] = array(
			'' => __( 'No State', 'wp-e-commerce' ),
		);
		foreach ( $state_data as $state ) {

			if ( ! array_key_exists( $state->country, $options ) ) {
				$options[ $state->country ] = array();
			}

			$options[ $state->country ][ $state->id ] = array(
				'title'      => $state->name,
				'attributes' => array(
					'data-alternative-spellings' => $state->code,
					'data-country-id'            => $state->country_id,
					'data-country-isocode'       => $state->country_isocode,
				)
			);
		}
	} else {
		$state_data = $wpdb->get_results( $wpdb->prepare( "SELECT `regions`.*, country.isocode as country FROM `" . WPSC_TABLE_REGION_TAX . "` AS `regions` INNER JOIN `" . WPSC_TABLE_CURRENCY_LIST . "` AS `country` ON `country`.`id` = `regions`.`country_id` WHERE `country`.`isocode` IN(%s)", $country ) );

		foreach ( $state_data as $state ) {
			$options[ $state->id ] = $state->name;
		}
	}
	$output .= wpsc_form_select(
		$name,
		$value,
		$options,
		array(
			'id' => $id . '-control',
			'class' => 'wpsc-form-select-region'
		),
		false
	);
	return $output;
}

function _wpsc_filter_control_submit( $output, $field, $args ) {
	extract( $field );

	if ( ! empty( $value ) ) {
		$title = $value;
	}

	$class = $args['id'] . '-button wpsc-button ' . $class;

	if ( $field['primary'] ) {
		$class .= ' wpsc-button-primary';
	}

	$output .= wpsc_form_submit( $name, $title, array( 'class' => $class ), false );

	return $output;
}

function _wpsc_filter_control_hidden( $output, $field, $args ) {
	extract( $field );

	$class = $args['id'] . '-hidden wpsc-hidden-input';
	$id    = $args['id'] . '-' . $field['name'];

	$output .= wpsc_form_hidden( $name, $value, array( 'class' => $class, 'id' => $id ), false );

	return $output;
}

function _wpsc_filter_control_button( $output, $field, $args ) {
	extract( $field );
	$class = $args['id'] . '-button wpsc-button';

	if ( $field['primary'] ) {
		$class .= ' wpsc-button-primary';
	}

	if ( ! isset( $field['icon'] ) ) {
		$field['icon'] = '';
	}

	$output .= wpsc_form_button( $name, $title, array( 'class' => $class, 'icon' => $field['icon'] ), false );

	return $output;
}

function _wpsc_filter_control_checkbox( $output, $field, $args ) {
	extract( $field );

	if ( ! isset( $checked ) ) {
		$checked = false;
	}

	$output .= wpsc_form_checkbox( $name, $value, $title, $checked, array( 'id' => $id . '-control' ), false );
	return $output;
}

function _wpsc_filter_control_radio( $output, $field, $args ) {
	extract( $field );

	if ( ! isset( $checked ) ) {
		$checked = false;
	}

	$output .= wpsc_form_radio( $name, $value, $title, $checked, array( 'id' => $id . '-control' ), false );
	return $output;
}

function _wpsc_filter_control_checkboxes( $output, $field, $args ) {
	extract( $field );

	if ( ! isset( $value ) ) {
		$value = '';
	}

	$output .= wpsc_form_checkboxes( $name, $value, $options, array( 'id' => $id . '-control' ), false );
	return $output;
}

function _wpsc_filter_control_radios( $output, $field, $args ) {
	extract( $field );

	if ( ! isset( $value ) ) {
		$value = '';
	}

	foreach ( $field['options'] as $value => $field ) {
		if ( ! is_array( $field ) ) {
			$field = array(
				'value' => $value,
				'title' => $field,
				'checked' => '',
			);
		}

		$field['name'] = $name;

		if ( ! isset( $field['id'] ) ) {
			$field['id'] = $id . '-' . sanitize_title_with_dashes( $value );
		}

		$output .= _wpsc_filter_control_radio( '', $field, $args );

		if ( isset( $field['description'] ) ) {
			$output .= _wpsc_get_field_description( $field, $args );
		}
	}

	return $output;
}

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
	$output        = "<input {$attributes} />";

	if ( $echo ) {
		echo $output;
	}

	return $output;
}

function wpsc_submitted_value( $name, $default = '', &$from = false ) {
	if ( ! is_array( $from ) ) {
		$from =& $_REQUEST;
	}

	$i = strpos( $name, '[' );

	if ( $i !== false ) {
		$head = substr( $name, 0, $i );
		preg_match_all( '/\[([^\]]+)\]/', $name, $matches );
		$matches = $matches[1];
		array_unshift( $matches, $head );

		$val = $from;
		foreach ( $matches as $token ) {
			if ( array_key_exists( $token, $val ) ) {
				$val = $val[ $token ];
			} else {
				$val = $default;
				break;
			}
		}

		return $val;
	}

	return isset( $from[ $name ] ) ? $from[ $name ] : $default;
}

function wpsc_checked( $name, $current = true, $default = false, $echo = true, &$from = null ) {
	if ( ! is_array( $from ) ) {
		$from =& $_REQUEST;
	}

	if ( isset( $from[ $name ] ) ) {
		$checked = $from[ $name ];
	} else {
		$checked = $default;
	}

	return checked( $checked, $current, $echo );
}

function wpsc_form_label( $label, $for = '', $atts = array(), $echo = true ) {
	if ( ! is_array( $atts ) ) {
		$atts = array();
	}

	if ( ! empty( $for ) ) {
		$atts['for'] = $for;
	}

	$output = '<label ' . _wpsc_form_attributes( $atts ) . '>' . $label . '</label>';

	if ( $echo ) {
		echo $output;
	}

	return $output;
}

function wpsc_form_input( $name, $value = '', $atts = array(), $echo = true ) {
	if ( ! is_array( $atts ) ) {
		$atts = array();
	}

	$atts['name']  = $name;
	$atts['value'] = $value;

	if ( ! isset( $atts['type'] ) ) {
		$atts['type']  = 'text';
	}

	return _wpsc_input_type_field( $atts, $echo );
}

function wpsc_form_password( $name, $atts = array(), $echo = true ) {
	if ( ! is_array( $atts ) ) {
		$atts = array();
	}

	$atts['name'] = $name;
	$atts['type'] = 'password';

	return _wpsc_input_type_field( $atts, $echo );
}

function wpsc_form_checkbox( $name, $value, $label = false, $checked = false, $atts = array(), $echo = true ) {
	if ( ! is_array( $atts ) ) {
		$atts = array();
	}

	$atts['name']  = $name;
	$atts['type']  = 'checkbox';
	$atts['value'] = $value;

	if ( $checked ) {
		$atts['checked'] = 'checked';
	}

	if ( $label ) {
		$output = '<label class="wpsc-form-checkbox-wrapper">' . _wpsc_input_type_field( $atts, false ) . ' ' . $label . '</label>';
		if ( ! $echo ) {
			return $output;
		}

		echo $output;
	} else {
		return _wpsc_input_type_field( $atts, $echo );
	}
}

function wpsc_form_checkboxes( $name, $selected_values = '', $options = array(), $atts = array(), $echo = true ) {
	if ( ! is_array( $atts ) ) {
		$atts = array();
	}

	$selected_values = (array) $selected_values;

	$output = '';

	foreach ( $options as $value => $title ) {
		$option_atts       = $atts;
		$option_atts['id'] = $atts['id'] . '-' . sanitize_title( $value );
		$checked           = in_array( $value, $selected_values );
		$output           .= wpsc_form_checkbox( $name, $value, $title, $checked, $option_atts, false );
	}

	if ( ! $echo ) {
		return $output;
	}

	echo $output;
}

function wpsc_form_radio( $name, $value, $label = false, $checked = false, $atts = array(), $echo = true ) {
	if ( ! is_array( $atts ) ) {
		$atts = array();
	}

	$atts['name']  = $name;
	$atts['type']  = 'radio';
	$atts['value'] = $value;

	if ( $checked ) {
		$atts['checked'] = 'checked';
	}

	if ( $label ) {
		$output = '<label class="wpsc-form-radio-wrapper">' . _wpsc_input_type_field( $atts, false ) . ' ' . $label . '</label>';
		if ( ! $echo ) {
			return $output;
		}
		echo $output;
	} else {
		return _wpsc_input_type_field( $atts, $echo );
	}
}

function wpsc_form_radios( $name, $selected_value = '', $options = array(), $atts = array(), $echo = true ) {
	if ( ! is_array( $atts ) ) {
		$atts = array();
	}

	$output = '';

	foreach ( $options as $value => $title ) {
		$option_atts       = $atts;
		$option_atts['id'] = $atts['id'] . '-' . sanitize_title( $value );
		$checked           = ( $value == $selected_value );
		$output           .= wpsc_form_radio( $name, $value, $title, $checked, $option_atts, false );
	}

	if ( ! $echo ) {
		return $output;
	}

	echo $output;
}

function wpsc_form_select( $name, $selected_values = '', $options = array(), $atts = array(), $echo = true ) {
	if ( ! is_array( $atts ) ) {
		$atts = array();
	}

	$atts['name'] = $name;

	$output  = '<select ' . _wpsc_form_attributes( $atts ) . '>';
	$output .= _wpsc_form_select_options( $options, $selected_values );
	$output .= '</select>';

	if ( ! $echo ) {
		return $output;
	}

	echo $output;
}

function _wpsc_form_select_options( $options, $selected_values ) {
	$output = '';

	$selected_values = (array) $selected_values;

	foreach ( $options as $value => $option_title ) {
		if ( is_array( $option_title ) ) {
			if ( array_key_exists( 'title', $option_title ) ) {
				$attributes = array(
					'value' => $value,
				);
				if ( array_key_exists( 'attributes', $option_title ) ) {
					$attributes = array_merge( $attributes, $option_title['attributes'] );
				}

				$attributes = _wpsc_form_attributes( $attributes ) . ' ';
				$output    .= '<option ' . $attributes . selected( in_array( $value, $selected_values ), true, false ) . '>' . $option_title['title'] . '</option>';
			} else {
				$output .= _wpsc_form_select_optgroup( $value, $option_title, $selected_values );
			}
		} else {
			$output .= '<option value="' . esc_attr( $value ) . '" ' . selected( in_array( $value, $selected_values ), true, false ) . '>' . $option_title . '</option>';
		}
	}
	return $output;
}

function _wpsc_form_select_optgroup( $group_name, $options, $selected_values ) {
	$output  = '<optgroup label="' . esc_attr( $group_name ) . '">';
	$output .= _wpsc_form_select_options( $options, $selected_values );
	$output .= '</optgroup>';
	return $output;
}

function wpsc_form_textarea( $name, $value = '', $atts = array(), $echo = true ) {
	if ( ! is_array( $atts ) ) {
		$atts = array();
	}

	$atts['name'] = $name;

	$output  = '<textarea ' . _wpsc_form_attributes( $atts ) . '>';
	$output .= esc_html( $value );
	$output .= '</textarea>';

	if ( ! $echo ) {
		return $output;
	}

	echo $output;
}

function wpsc_form_submit( $name, $value = '', $atts = array(), $echo = true ) {
	if ( ! is_array( $atts ) ) {
		$atts = array();
	}

	$atts['name']  = $name;
	$atts['value'] = empty( $value ) ? _x( 'Submit', 'generic submit button title', 'wp-e-commerce' ) : $value;
	$atts['type']  = 'submit';

	return _wpsc_input_type_field( $atts, $echo );
}

function wpsc_form_hidden( $name, $value, $atts = array(), $echo = true ) {
	if ( ! is_array( $atts ) ) {
		$atts = array();
	}

	$atts['name'] = $name;
	$atts['value'] = $value;
	$atts['type'] = 'hidden';

	return _wpsc_input_type_field( $atts, $echo );
}

/**
 * Get the HTML output of or display a button.
 *
 * Use the third argument to output additional HTML attributes. There's one
 * special attribute called 'icon' which will generate an icon for the button if
 * default styling is enabled.
 *
 * @since  0.1
 *
 * @param  string  $name  Name of button, can be empty
 * @param  string  $title Button title, must be already escaped
 * @param  array   $args  Additional arguments
 * @param  boolean $echo  Whether to echo the HTML output
 * @return string|void    Return the HTML output if $echo is set to false
 */
function wpsc_form_button( $name, $title, $args = array(), $echo = true ) {
	if ( ! is_array( $args ) ) {
		$args = array();
	}

	$args['name'] = $name;

	$icon = '';

	if ( ! empty( $args['icon'] ) ) {
		foreach ( $args['icon'] as &$icon_class ) {
			$icon_class = 'wpsc-icon-' . $icon_class;
		}
		$class = implode( ' ', $args['icon'] );
		$icon = "<i class='{$class}'></i> ";
	}

	unset( $args['icon'] );

	$output  = '<button ' . _wpsc_form_attributes( $args ) . '>';
	$output .= $icon . $title;
	$output .= '</button>';

	if ( $echo ) {
		echo $output;
	}

	return $output;
}