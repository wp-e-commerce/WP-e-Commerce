<?php

class WPSC_Settings_Tab_Presentation extends _WPSC_Settings_Tab_Form {
	public function __construct() {
		$this->hide_submit_button();
		$this->populate_form_array();

		add_filter( 'wpsc_settings_form_output_filter_categories', array( $this, '_filter_field_category_filter' ), 10, 2 );

		parent::__construct();
	}

	public function _filter_field_category_filter( $output, $field_array ) {
		extract( $field_array );

		$description_html = apply_filters( 'wpsc_settings_' . $name . '_description', $description, $field_array );

		if ( ! isset( $class ) ) {
			$class = 'wpsc-settings-category-filter';
		}

		$output   = '';
		$selected = wpsc_get_option( 'categories_to_filter' );

		$output .= wpsc_form_radios(
			$name,
			$selected,
			array(
				'all'         => _x( 'All categories', 'category filter settings', 'wp-e-commerce' ),
				'first_level' => _x( 'First level categories only', 'category filter settings', 'wp-e-commerce' ),
				'custom'      => _x( 'Custom', 'category filter settings', 'wp-e-commerce' ),
			),
			array( 'id' => $id ),
			false
		);

		$terms = get_terms( 'wpsc_product_category', array( 'hide_empty' => false ) );

		$options = array();

		foreach ( $terms as $term ) {
			$options[ $term->term_id ] = $term->name;
		}

		$selected = wpsc_get_option( "categories_to_filter_custom" );

		$output .= '<div class="wpsc-settings-category-filter-custom">';
		$output .= '<div class="wpsc-settings-category-filter-custom-all wpsc-settings-all-none">';
		$output .= sprintf(
			_x( 'Select: %1$s %2$s', 'select all / none', 'wp-e-commerce' ),
			'<a href="#" data-for="' . $id . '-custom-select" class="wpsc-multi-select-all">' . _x( 'All', 'select all', 'wp-e-commerce' ) . '</a>',
			'<a href="#" data-for="' . $id . '-custom-select" class="wpsc-multi-select-none">' . _x( 'None', 'select none', 'wp-e-commerce' ) . '</a>'
		);
		$output .= '</div>';
		$output .= wpsc_form_select(
			"{$name}_custom[]",
			$selected,
			$options,
			array(
				'id'               => "{$id}-custom-select",
				'class'            => 'wpsc-multi-select',
				'multiple'         => 'multiple',
				'size'             => 5,
				'data-placeholder' => __( 'Select categories', 'wp-e-commerce' ),
			),
			false
		);

		$output .= '</div>';

		return $output;
	}

	public function display() {
	?>
		<h3><?php _e( 'Wondering where all the old presentation settings have gone?', 'wp-e-commerce' ); ?></h3>
		<p><?php _e( "Do not worry. We're taking this opportunity to rewrite them properly using the new WordPress settings API throughout this beta phase.", 'wp-e-commerce' ); ?></p>
		<p><?php _e( "We'll either add them right back or release mini Plugins. To help us decide what goes back into core and what will become a Plugin, please <a href='https://github.com/wp-e-commerce/WP-e-Commerce/issues/516'>let us know on Github</a> what your most important setting is.", 'wp-e-commerce' ); ?></p>
	<?php

		parent::display();
	}

	private function populate_form_array() {
		$this->sections = apply_filters(
			'wpsc_settings_presentation_sections',
			array(
				'default_styles' => array(
					'title'  => _x( 'Default styling', 'presentation settings', 'wp-e-commerce' ),
					'fields' => array(
						'default_styles'
					),
				),
				'category_filter' => array(
					'title'  => _x( 'Category filter', 'presentation settings', 'wp-e-commerce' ),
					'fields' => array(
						'display_category_filter',
						'categories_to_filter',
						'category_filter_drill_down',
					),
				),
			)
		);

		$this->form_array = apply_filters(
			'wpsc_settings_presentation_form',
			array(
				'display_category_filter' => array(
					'type'    => 'radios',
					'title'   => _x( 'Display category filter on store pages', 'presentation settings', 'wp-e-commerce' ),
					'options' => array(
						1 => _x( 'Yes', 'settings', 'wp-e-commerce' ),
						0 => _x( 'No', 'settings', 'wp-e-commerce' ),
					),
				),

				'categories_to_filter' => array(
					'type'  => 'filter_categories',
					'title' => _x( 'Which categories to filter', 'presentation settings', 'wp-e-commerce' )
				),

				'category_filter_drill_down' => array(
					'type'    => 'radios',
					'title'   => _x( 'Allow category filter drill down', 'presentation settings', 'wp-e-commerce' ),
					'options' => array(
						1 => _x( 'Yes', 'settings', 'wp-e-commerce' ),
						0 => _x( 'No', 'settings', 'wp-e-commerce' ),
					),
				),

				'default_styles' => array(
					'type'    => 'checkboxes',
					'title'   => _x( 'Use the following default stylesheets', 'presentation settings', 'wp-e-commerce' ),
					'options' => apply_filters( 'wpsc_default_styles_options', array(
						'wpsc-common'        => _x( '<code>wpsc-common</code>: Common CSS for all pages', 'default styles options', 'wp-e-commerce' ),
						'wpsc-common-inline' => _x( '<code>wpsc-common-inline</code>: Inline CSS for all pages', 'default styles options', 'wp-e-commerce' ),
					) ),
				),
			)
		);

		$this->extra_fields = array( 'categories_to_filter_custom' );
	}
}