<?php

class WPSC_Settings_Form
{
	private $form_array;
	public function __construct( $form_array ) {
		$this->form_array = $form_array;

		foreach ( $form_array as $section_id => $section_array ) {
			$callback = create_function('', "
				echo '<p>" . addslashes( $section_array['description'] ) . "</p>';
			");
			add_settings_section( $section_id, $section_array['title'], $callback,  'wpsc-settings' );

			foreach ( $section_array['fields'] as $field_name => $field_array ) {
				if ( empty( $field_array['id'] ) )
					$field_array['id'] = str_replace( '_', '-', $field_name );

				$field_array['name'] = 'wpsc_' . $field_name;

				if ( ! array_key_exists( 'label_for', $field_array ) )
					$field_array['label_for'] = $field_array['id'];

				if ( ! array_key_exists( 'value', $field_array ) )
					$field_array['value'] = wpsc_get_option( $field_name );

				if ( ! array_key_exists( 'sanitize', $field_array ) )
					$field_array['sanitize'] = '';

				add_settings_field( $field_array['id'], $field_array['title'], array( $this, 'output_field' ), 'wpsc-settings', $section_id, $field_array );
				register_setting( 'wpsc-settings', $field_array['name'], $field_array['sanitize'] );
			}
		}
	}

	private function output_textfield( $field_array ) {
		extract( $field_array );
		$description_html = apply_filters( 'wpsc_settings_' . $name . '_description', esc_html( $description ), $field_array );
		if ( ! isset( $class ) )
			$class = 'regular-text wpsc-textfield';
		?>
		<input
			class="<?php echo esc_attr( $class ); ?>"
			id   ="<?php echo esc_attr( $id    ); ?>"
			name ="<?php echo esc_attr( $name  ); ?>"
			type ="text"
			value="<?php echo esc_attr( $value ); ?>"
		/>
		<p class="howto"><?php echo $description_html; ?></p>
		<?php
	}

	private function output_radios( $field_array ) {
		extract( $field_array );
		$description_html = apply_filters( 'wpsc_settings_' . $name . '_description', esc_html( $description ), $field_array );
		if ( ! isset( $class ) )
			$class = 'wpsc-radio';

		foreach ( $options as $radio_value => $radio_label ) {
			$radio_id = $id . '-' . sanitize_title_with_dashes( $value );
			?>
			<label class="wpsc-radio-label">
				<input
					class="<?php echo esc_attr( $class    ); ?>"
					id   ="<?php echo esc_attr( $radio_id ); ?>"
					name ="<?php echo esc_attr( $name     ); ?>"
					<?php checked( $value, $radio_value ); ?>
					type ="radio"
					value="<?php echo esc_attr( $radio_value    ); ?>"
				/>
				<?php echo esc_html( $radio_label ); ?>
			</label>
			<?php
		}
		echo '<br />';
		echo '<p class="howto">' . $description_html . '</p>';
	}

	public function output_field( $field_array ) {
		$output_function = 'output_' . $field_array['type'];
		$this->$output_function( $field_array );
		?>
		<?php
	}

	public function display() {
		settings_fields( 'wpsc-settings' );
		do_settings_sections( 'wpsc-settings' );
	}
}