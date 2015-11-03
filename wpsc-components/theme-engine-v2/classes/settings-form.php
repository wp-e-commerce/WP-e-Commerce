<?php

class WPSC_Settings_Form {
	private $form_array   = array();
	private $sections     = array();
	private $extra_fields = array();

	public function __construct( $sections, $form_array, $extra_fields = array() ) {
		$this->form_array   = $form_array;
		$this->sections     = $sections;
		$this->extra_fields = $extra_fields;

		if ( empty( $this->sections ) ) {
			$this->sections = array(
				'default' => array(
					'title'  => '',
					'fields' => array_keys( $this->form_array ),
				),
			);
		}

		$separator_id = 0;

		foreach ( $this->sections as $section_id => $section_array ) {
			add_settings_section( $section_id, $section_array['title'], array( $this, 'callback_section_description' ),  'wpsc-settings' );

			foreach ( $section_array['fields'] as $field_name ) {
				$this->process_field( $field_name );
				$this->add_settings_field( $field_name, $section_id );
			}

			$separator_id++;
			add_settings_section( 'section_separator_' . $separator_id, '', array( $this, 'callback_section_separator' ), 'wpsc-settings' );
		}

		$this->add_extra_fields();

		// validation rules
		add_filter( 'wpsc_settings_validation_rule_required', array( $this, 'filter_validation_rule_required' ), 10, 5 );

		// output field types
		add_filter( 'wpsc_settings_form_output_textfield' , array( $this, 'filter_output_textfield'  ), 10, 2 );
		add_filter( 'wpsc_settings_form_output_number'    , array( $this, 'filter_output_number'     ), 10, 2 );
		add_filter( 'wpsc_settings_form_output_radios'    , array( $this, 'filter_output_radios'     ), 10, 2 );
		add_filter( 'wpsc_settings_form_output_checkboxes', array( $this, 'filter_output_checkboxes' ), 10, 2 );
	}

	private function process_field( $field_name ) {
		$defaults = array(
			'id'            => str_replace( '_', '-', $field_name ),
			'internal_name' => $field_name,
			'name'          => 'wpsc_'. $field_name,
			'value'         => wpsc_get_option( $field_name ),
			'description'   => '',
			'validation'    => '',
		);

		$this->form_array[ $field_name ] = wp_parse_args(
			$this->form_array[ $field_name ],
			$defaults
		);

		$this->form_array[ $field_name ]['label_for'] = $this->form_array[ $field_name ]['id'];

		if ( ! empty( $this->form_array[ $field_name ]['validation'] ) ) {
			add_filter( 'sanitize_option_' . $field_name, array( $this, 'validate_field' ), 10, 2 );
		}

	}

	private function add_settings_field( $field_name, $section_id ) {
		$field_array = $this->form_array[ $field_name ];

		add_settings_field(
			$field_array['id'],
			$field_array['title'],
			array( $this, 'output_field' ),
			'wpsc-settings',
			$section_id,
			$field_array
		);

		register_setting( 'wpsc-settings', $field_array['name'] );
	}

	private function add_extra_fields() {
		foreach ( $this->extra_fields as $field ) {
			register_setting( 'wpsc-settings', "wpsc_{$field}" );
		}
	}

	public function filter_validation_rule_required( $valid, $value, $field_name, $field_title, $field_id ) {
		if ( $value == '' ) {
			$field_anchor = '<a href="#' . esc_attr( $field_id ) . '">' . esc_html( $field_title ) . '</a>';
			add_settings_error( $field_name, 'field-required' . $field_name, sprintf( __( 'The field %s cannot be blank.', 'wp-e-commerce' ), $field_anchor ) );
			$valid = false;
		}
		return $valid;
	}

	public function validate_field( $value, $field_name ) {

		$internal_name = substr( $field_name, 5 ); // remove the wpsc_ part, WP core passes the whole option name
		$rules         = explode( '|', $this->form_array[$internal_name]['validation'] );
		$field_title   = $this->form_array[$internal_name]['title'];
		$field_id      = $this->form_array[$internal_name]['id'];
		$valid         = true;

		foreach ( $rules as $rule ) {
			if ( is_callable( $rule ) ) {
				$valid = $valid && call_user_func( $rule, $value );
			} else {
				$valid = apply_filters( 'wpsc_settings_validation_rule_' . $rule, $valid, $value, $field_name, $field_title, $field_id );
			}
		}

		if ( ! $valid ) {
			$value = wpsc_get_option( $internal_name );
		}

		return $value;
	}

	public function callback_section_description( $section ) {
		$section_id = $section['id'];

		if ( ! array_key_exists( 'description', $this->sections[ $section_id ] ) ) {
			return;
		}

		$description = $this->sections[ $section_id ]['description'];
		$description = apply_filters( 'wpsc_' . $section_id . '_description', $description );
		echo '<p>' . $description . '</p>';
	}

	public function callback_section_separator() {
		submit_button( __( 'Save Changes', 'wp-e-commerce' ) );
	}

	public function filter_output_number( $output, $field_array ) {
		extract( $field_array );

		$description_html = apply_filters( $name . '_setting_description', $description, $field_array );

		if ( ! isset( $class ) ) {
			$class = 'small-text wpsc-number';
		}

		$output = '';

		if ( ! empty( $prepend ) ) {
			$output .= $prepend;
		}

		$atts = array(
			'id'    => $id,
			'class' => $class,
			'type'  => 'number',
		);

		$output .= wpsc_form_input( $name, $value, $atts, false );

		if ( ! empty( $append ) ) {
			$output .= $append;
		}

		if ( $description ) {
			$output .= '<p class="howto">' . $description_html . '</p>';
		}

		return $output;
	}

	public function filter_output_textfield( $output, $field_array ) {
		extract( $field_array );

		$description_html = apply_filters( $name . '_setting_description', $description, $field_array );

		if ( ! isset( $class ) ) {
			$class = 'regular-text wpsc-textfield';
		}

		$output = '';

		if ( ! empty( $prepend ) ) {
			$output .= $prepend;
		}

		$output .= wpsc_form_input( $name, $value, array( 'id' => $id, 'class' => $class ), false );

		if ( ! empty( $append ) ) {
			$output .= $append;
		}

		if ( $description ) {
			$output .= '<p class="howto">' . $description_html . '</p>';
		}

		return $output;
	}

	public function filter_output_radios( $output, $field_array ) {
		extract( $field_array );

		$description_html = apply_filters( 'wpsc_settings_' . $name . '_description', $description, $field_array );

		if ( ! isset( $class ) ) {
			$class = 'wpsc-radio';
		}

		foreach ( $options as $radio_value => $radio_label ) {
			$radio_id  = $id . '-' . sanitize_title_with_dashes( $radio_value );
			$checked   = $value == $radio_value;
			$output   .= wpsc_form_radio( $name, $radio_value, $radio_label, $checked, array( 'id' => $radio_id, 'class' => $class ), false );
		}

		$output .= '<p class="howto">' . $description_html . '</p>';

		return $output;
	}

	public function filter_output_checkboxes( $output, $field_array ) {
		extract( $field_array );

		$description_html = apply_filters( 'wpsc_settings_' . $name . '_description', $description, $field_array );

		if ( ! isset( $class ) ) {
			$class = 'wpsc-checkbox';
		}

		// For checkboxes, we should assume these are in a set.  As such, they need to be POSTed as an array.
		$name  .= '[]' !== substr( $name, -2 ) ? '[]' : '';
		$output = '';

		foreach ( $options as $checkbox_value => $checkbox_label ) {
			$checkbox_id  = $id . '-' . sanitize_title_with_dashes( $checkbox_value );
			$checked      = in_array( $checkbox_value, (array) $value );
			$output      .= wpsc_form_checkbox( $name, $checkbox_value, $checkbox_label, $checked, array( 'id' => $checkbox_id, 'class' => $class ), false );
		}

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