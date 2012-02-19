<?php

class WPSC_Checkout_Form
{
	/**
	 * Contains an array of created instances of type WPSC_Checkout_Form
	 *
	 * @access private
	 * @static
	 * @since 3.9
	 *
	 * @var array
	 */
	private static $instances = array();

	/**
	 * Contains an array of form id => form names
	 *
	 * @access private
	 * @static
	 * @since 3.9
	 *
	 * @var array
	 */
	private static $form_titles = array();

	/**
	 * ID of the form instance
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @var int
	 */
	private $id = 0;

	/**
	 * Contains an array of form fields
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @var array
	 */
	private $fields;

	/**
	 * Contains an array of field_id => field_unique_name
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @var array
	 */
	private $field_unique_name_id;

	/**
	 * Returns an instance of the form with a particular ID
	 *
	 * @access public
	 * @static
	 * @since 3.9
	 *
	 * @param int $id Optional. Defaults to 0. The ID of the form
	 * @return WPSC_Checkout_Form
	 */
	public static function &get( $id = 0 ) {
		if ( ! self::$instances )
			self::$instances[$id] = new WPSC_Checkout_Form( $id );

		self::$form_titles = get_option( 'wpsc_checkout_form_sets' );

		return self::$instances[$id];
	}

	/**
	 * Constructor of an WPSC_Checkout_Form instance. Cannot be called publicly
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @param string $id Optional. Defaults to 0.
	 */
	private function __construct( $id = 0 ) {
		$this->id = $id;
		$this->get_fields();
	}

	/**
	 * Outputs the list of form field options
	 *
	 * @access public
	 * @since 3.9
	 *
	 * @param string $selected_id Optional. Defaults to false. The ID of the field
	 *                            currently being selected
	 * @return void
	 */
	public function field_drop_down_options( $selected_id = false ) {
		?>
		<option value=""><?php _e( 'Please select', 'wpsc' ); ?></option>
		<?php

		foreach ( $this->get_fields() as $field ) {
			?>
				<option <?php if ( $field->type == 'heading' ) echo 'disabled="disabled"'; ?> <?php selected( $field->id, $selected_id ) ?> value="<?php echo esc_attr( $field->id ) ?>"><?php echo esc_html( $field->name ); ?></option>
			<?php
		}
	}

	/**
	 * Returns the field ID based on unique name
	 *
	 * @access public
	 * @since 3.9
	 *
	 * @param string $unique_name Unique name of the field
	 * @return mixed False if not found, (int) ID if found.
	 */
	public function get_field_id_by_unique_name( $unique_name ) {
		if ( is_null( $this->field_unique_name_id ) ) {
			$this->field_unique_name_id = array();
			foreach ( $this->get_fields() as $field ) {
				$this->field_unique_name_id[$field->unique_name] = $field->id;
			}
		}
		return isset( $this->field_unique_name_id[$unique_name] ) ? $this->field_unique_name_id[$unique_name] : false;
	}

	/**
	 * Returns an array containing the fields of the form
	 *
	 * @access public
	 * @since 3.9
	 *
	 * @param bool $exclude_heading Optional. Defaults to false. Whether to exclude heading
	 *                              fields from the output
	 * @return array
	 */
	public function get_fields( $exclude_heading = false) {
		if ( is_null( $this->fields ) ) {
			global $wpdb;
	 		$sql = "SELECT * FROM " . WPSC_TABLE_CHECKOUT_FORMS . " WHERE checkout_set = %d AND active = 1 ORDER BY checkout_order ASC";
			$this->fields = $wpdb->get_results( $wpdb->prepare( $sql, $this->id ) );
			$this->field_unique_name_id = null;
		}

		$fields = $this->fields;

		if ( $exclude_heading )
			$fields = wp_list_filter( $fields, array( 'type' => 'heading' ), 'NOT' );

		return $fields;
	}

	/**
	 * Returns the title of the form
	 *
	 * @access public
	 * @since 3.9
	 *
	 * @return string
	 */
	public function get_title() {
		return isset( self::$form_titles[$this->id] ) ? self::$form_titles[$this->id] : '';
	}

	public function output_fields() {
		if ( is_null( $this->fields ) )
			$this->get_fields();

		$output = '';

		if ( empty( $_POST['wpsc_checkout_details'] ) )
			$_POST['wpsc_checkout_details'] = array();

		foreach ( $this->fields as $field ) {
			$id = empty( $field->unique_name ) ? $field->id : $field->unique_name;

			$field_arr = array(
				'type' => $field->type,
				'id'   => "wpsc-checkout-field-{$id}",
				'title' => $field->name,
				'name'  => 'wpsc_checkout_details[' . $field->id . ']',
				'value' => wpsc_submitted_value( $field->id, '', $_POST['wpsc_checkout_details'] ),
			);

			if ( in_array( $field->unique_name, array( 'billingstate', 'shippingstate' ) ) ) {
				$field_arr['type'] = 'state_dropdown';
				$field_arr['country'] = 'US';
			} elseif ( in_array( $field->unique_name, array( 'billingcountry', 'shippingcountry' ) ) || $field->type == 'delivery_country' ) {
				$field_arr['type'] = 'country_dropdown';
			} elseif ( $field->type == 'text' ) {
				$field_arr['type'] = 'textfield';
			} elseif ( $field->type == 'select' ) {
				$field_arr['options'] = array_flip( unserialize( $field->options ) );
			} elseif ( $field->type == 'radio' ) {
				$field_arr['type'] = 'radios';
				$field_arr['options'] = array_flip( unserialize( $field->options ) );
			} elseif ( $field->type == 'checkbox' ) {
				$field_arr['type'] = 'checkboxes';
				$field_arr['options'] = array_flip( unserialize( $field->options ) );
			}

			$output .= sprintf( apply_filters( 'wpsc_checkout_field_before', '<p class="%s" id="%s">', $field_arr ), "wpsc-checkout-field-wrapper wpsc-checkout-field-wrapper-{$field_arr['type']}", "wpsc-checkout-field-{$id}-wrapper" );
			$output .= apply_filters( "wpsc_checkout_field_{$field_arr['type']}", '', $field_arr );
			$output .= apply_filters( 'wpsc_checkout_field_after', '</p>', $field_arr );
		}

		echo $output;
	}
}

function wpsc_checkout_field_label( $output, $field ) {
	extract( $field );

	if ( $type != 'heading' )
		$output .= wpsc_form_label( $title, $id, array( 'class' => 'wpsc-checkout-label', 'id' => $id . '-label' ), false );

	return $output;
}
add_filter( 'wpsc_checkout_field_before', 'wpsc_checkout_field_label', 20, 2 );

function wpsc_checkout_field_heading( $output, $field ) {
	extract( $field );
	if ( ! isset( $class ) )
		$class = 'wpsc-checkout-heading';

	return esc_html( $title );
}
add_filter( 'wpsc_checkout_field_heading', 'wpsc_checkout_field_heading', 10, 2 );

function wpsc_checkout_field_textfield( $output, $field_array ) {
	extract( $field_array );
	if ( ! isset( $class ) )
		$class = 'wpsc-checkout-textfield';

	if ( $type !== 'textfield' )
		$class .= ' wpsc-checkout-' . $type;

	$output .= wpsc_form_input( $name, $value, array( 'id' => $id, 'class' => $class ), false );

	return $output;
}
add_filter( 'wpsc_checkout_field_address', 'wpsc_checkout_field_textfield', 10, 2 );
add_filter( 'wpsc_checkout_field_city', 'wpsc_checkout_field_textfield', 10, 2 );
add_filter( 'wpsc_checkout_field_email', 'wpsc_checkout_field_textfield', 10, 2 );
add_filter( 'wpsc_checkout_field_textfield', 'wpsc_checkout_field_textfield', 10, 2 );

function wpsc_checkout_field_country_dropdown( $output, $field_array ) {
	extract( $field_array );
	if ( ! isset( $class ) )
		$class = 'wpsc-checkout-country-dropdown';

	$country_data = WPSC_Country::get_all();
	$options = array();
	foreach ( $country_data as $country ) {
		$options[$country->isocode] = $country->country;
	}

	$output .= wpsc_form_select( $name, $value, $options, array( 'id' => $id, 'class' => $class ), false );

	return $output;
}
add_filter( 'wpsc_checkout_field_country_dropdown', 'wpsc_checkout_field_country_dropdown', 10, 2 );

function wpsc_checkout_field_state_dropdown( $output, $field_array ) {
	global $wpdb;

	extract( $field_array );
	if ( ! isset( $class ) )
		$class = 'wpsc-checkout-state-dropdown';

	$state_data = $wpdb->get_results( $wpdb->prepare( "SELECT `regions`.* FROM `" . WPSC_TABLE_REGION_TAX . "` AS `regions` INNER JOIN `" . WPSC_TABLE_CURRENCY_LIST . "` AS `country` ON `country`.`id` = `regions`.`country_id` WHERE `country`.`isocode` IN(%s)", $country ) );
	$options = array();
	foreach ( $state_data as $state ) {
		$options[$state->id] = $state->name;
	}

	$output .= wpsc_form_select( $name, $value, $options, array( 'id' => $id, 'class' => $class ), false );

	return $output;
}
add_filter( 'wpsc_checkout_field_state_dropdown', 'wpsc_checkout_field_state_dropdown', 10, 2 );

function wpsc_checkout_field_textarea( $output, $field_array ) {
	extract( $field_array );
	if ( ! isset( $class ) )
		$class = 'wpsc-checkout-textarea';

	$output .= wpsc_form_textarea( $name, $value, array( 'id' => $id, 'class' => $class ), false );

	return $output;
}
add_filter( 'wpsc_checkout_field_textarea', 'wpsc_checkout_field_textarea', 10, 2 );

function wpsc_checkout_field_select( $output, $field_array ) {
	extract( $field_array );
	if ( ! isset( $class ) )
		$class = 'wpsc-checkout-select';

	$output .= wpsc_form_select( $name, $value, $options, array( 'id' => $id, 'class' => $class ), false );

	return $output;
}
add_filter( 'wpsc_checkout_field_select', 'wpsc_checkout_field_select', 10, 2 );

function wpsc_checkout_field_radios( $output, $field_array ) {
	extract( $field_array );

	if ( ! isset( $class ) )
		$class = 'wpsc-checkout-radios wpsc-checkout-radio';

	$output .= wpsc_form_radios( $name, $value, $options, array( 'id' => $id, 'class' => $class ), false );

	return $output;
}
add_filter( 'wpsc_checkout_field_radios', 'wpsc_checkout_field_radios', 10, 2 );

function wpsc_checkout_field_checkboxes( $output, $field_array ) {
	extract( $field_array );

	if ( ! isset( $class ) )
		$class = 'wpsc-checkout-checkboxes wpsc-checkout-checkbox';

	$output .= wpsc_form_checkboxes( $name, $value, $options, array( 'id' => $id, 'class' => $class ), false );

	return $output;
}
add_filter( 'wpsc_checkout_field_checkboxes', 'wpsc_checkout_field_checkboxes', 10, 2 );