<?php

class WPSC_Settings_Form
{
	private $form_array = array();
	private $sections = array();
	private $validation_rules = array();

	public function __construct( $sections, $form_array ) {
		$this->form_array = $form_array;
		$this->sections = $sections;

		foreach ( $sections as $section_id => $section_array ) {
			add_settings_section( $section_id, $section_array['title'], array( $this, 'callback_section_description' ),  'wpsc-settings' );

			foreach ( $section_array['fields'] as $field_name ) {
				$field_array =& $this->form_array[$field_name];
				if ( empty( $field_array['id'] ) )
					$field_array['id'] = str_replace( '_', '-', $field_name );

				$field_array['internal_name'] = $field_name;
				$field_array['name'] = 'wpsc_' . $field_name;

				if ( ! array_key_exists( 'label_for', $field_array ) )
					$field_array['label_for'] = $field_array['id'];

				if ( ! array_key_exists( 'value', $field_array ) )
					$field_array['value'] = wpsc_get_option( $field_name );

				if ( ! array_key_exists( 'description', $field_array ) )
					$field_array['description'] = '';

				if ( array_key_exists( 'validation', $field_array ) ) {
					add_filter( 'sanitize_option_' . $field_array['name'], array( $this, 'validate_field' ), 10, 2 );
				}

				add_settings_field( $field_array['id'], $field_array['title'], array( $this, 'output_field' ), 'wpsc-settings', $section_id, $field_array );
				register_setting( 'wpsc-settings', $field_array['name'] );
			}
		}

		// validation rules
		add_filter( 'wpsc_settings_validation_rule_required', array( $this, 'filter_validation_rule_required' ), 10, 5 );

		// output field types
		add_filter( 'wpsc_settings_form_output_textfield' , array( $this, 'filter_output_textfield'  ), 10, 2 );
		add_filter( 'wpsc_settings_form_output_radios'    , array( $this, 'filter_output_radios'     ), 10, 2 );
		add_filter( 'wpsc_settings_form_output_checkboxes', array( $this, 'filter_output_checkboxes' ), 10, 2 );
	}

	public function filter_validation_rule_required( $valid, $value, $field_name, $field_title, $field_id ) {
		if ( $value == '' ) {
			$field_anchor = '<a href="#' . esc_attr( $field_id ) . '">' . esc_html( $field_title ) . '</a>';
			add_settings_error( $field_name, 'field-required' . $field_name, sprintf( __( 'The field %s cannot be blank.', 'wpsc' ), $field_anchor ) );
			$valid = false;
		}
		return $valid;
	}

	public function validate_field( $value, $field_name ) {
		$internal_name = substr( $field_name, 5 ); // remove the wpsc_ part, WP core passes the whole option name
		$rules = explode( '|', $this->form_array[$internal_name]['validation'] );
		$field_title = $this->form_array[$internal_name]['title'];
		$field_id = $this->form_array[$internal_name]['id'];
		$valid = true;
		foreach ( $rules as $rule ) {
			if ( is_callable( $rule ) )
				$valid = $valid && call_user_func( $rule, $value );
			else
				$valid = apply_filters( 'wpsc_settings_validation_rule_' . $rule, $valid, $value, $field_name, $field_title, $field_id );
		}

		if ( ! $valid )
			$value = wpsc_get_option( $internal_name );

		return $value;
	}

	public function callback_section_description( $section ) {
		$section_id = $section['id'];
		$description = $this->sections[$section_id]['description'];
		$description = apply_filters( 'wpsc_' . $section_id . '_description', $description );
		echo '<p>' . $description . '</p>';
	}

	public function filter_output_textfield( $output, $field_array ) {
		extract( $field_array );
		$description_html = apply_filters( $name . '_setting_description', $description, $field_array );
		if ( ! isset( $class ) )
			$class = 'regular-text wpsc-textfield';

		$output = wpsc_form_input( $name, $value, array( 'id' => $id, 'class' => $class ), false );
		$output .= '<p class="howto">' . $description_html . '</p>';

		return $output;
	}

	public function filter_output_radios( $output, $field_array ) {
		extract( $field_array );
		$description_html = apply_filters( 'wpsc_settings_' . $name . '_description', $description, $field_array );
		if ( ! isset( $class ) )
			$class = 'wpsc-radio';

		foreach ( $options as $radio_value => $radio_label ) {
			$radio_id  = $id . '-' . sanitize_title_with_dashes( $radio_value );
			$checked   = $value == $radio_value;
			$output   .= wpsc_form_radio( $name, $radio_value, $checked, array( 'id' => $radio_id, 'class' => $class ), false );
			$output   .= wpsc_form_label( $radio_label, $radio_id, array( 'class' => 'wpsc-form-radio-label' ), false );
		}

		$output .= '<br />';
		$output .= '<p class="howto">' . $description_html . '</p>';

		return $output;
	}

	public function filter_output_checkboxes( $output, $field_array ) {
		extract( $field_array );
		$description_html = apply_filters( 'wpsc_settings_' . $name . '_description', $description, $field_array );
		if ( ! isset( $class ) )
			$class = 'wpsc-checkbox';

		$output = '';
		foreach ( $options as $checkbox_value => $checkbox_label ) {
			$checkbox_id  = $id . '-' . sanitize_title_with_dashes( $checkbox_value );
			$checked      = $value == $checkbox_value;
			$output      .= wpsc_form_checkbox( $name, $checkbox_value, $checked, array( 'id' => $checkbox_id, 'class' => $class ), false );
			$output      .= wpsc_form_label( $checkbox_label, $checkbox_id, array( 'class' => 'wpsc-form-checkbox-label' ), false );
		}

		$output .= '<br />';
		$output .= '<p class="howto">' . $description_html . '</p>';

		return $output;
	}

	public function output_field( $field_array ) {
		$output = apply_filters( 'wpsc_settings_form_output_' . $field_array['type'], '', $field_array );
		echo $output;
	}

	public function display() {
		settings_fields( 'wpsc-settings' );
		do_settings_sections( 'wpsc-settings' );
	}
}