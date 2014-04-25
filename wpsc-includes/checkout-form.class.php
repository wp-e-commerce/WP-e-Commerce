<?php

class WPSC_Checkout_Form {
	/**
	 * Contains an array of created instances of type WPSC_Checkout_Form
	 *
	 * @access private
	 * @static
	 * @since 3.8.10
	 *
	 * @var array
	 */
	private static $instances = array();

	/**
	 * Contains an array of form id => form names
	 *
	 * @access private
	 * @static
	 * @since 3.8.10
	 *
	 * @var array
	 */
	private static $form_titles = array();

	/**
	 * ID of the form instance
	 *
	 * @access private
	 * @since 3.8.10
	 *
	 * @var int
	 */
	private $id = 0;

	/**
	 * Contains an array of form fields
	 *
	 * @access private
	 * @since 3.8.10
	 *
	 * @var array
	 */
	private $fields;

	/**
	 * Contains an array of field_id => field_unique_name
	 *
	 * @access private
	 * @since 3.8.10
	 *
	 * @var array
	 */
	private $field_unique_name_id;

	/**
	 * only include active checkout elements
	 *
	 * @access private
	 * @since 3.8.14
	 *
	 * @var boolean
	 */
	private $active_only = true;

	/**
	 * Returns an instance of the form with a particular ID
	 *
	 * @access public
	 * @static
	 * @since 3.8.10
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
	 * @since 3.8.10
	 *
	 * @param string $id Optional. Defaults to 0.
	 */
	public function __construct( $id = 0, $active_only = true ) {
		$this->id = $id;
		$this->active_only = $active_only;
		$this->get_fields();
	}

	/**
	 * Outputs the list of form field options
	 *
	 * @access public
	 * @since 3.8.10
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
	 * @since 3.8.10
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
	 * Returns the field based on unique name
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param string $unique_name Unique name of the field
	 * @return mixed False if not found, field information if found.
	 */
	public function get_field_by_unique_name( $unique_name ) {
		$field = false;

		$id = $this->get_field_id_by_unique_name( $unique_name );

		if ( $id ) {
			$field = $this->fields[$id];
		}

		return $field;
	}

	/**
	 * Returns an array containing the fields of the form
	 *
	 * @access public
	 *
	 * @since 3.9
	 *
	 * @param bool $exclude_heading Optional 	Defaults to false. Whether to exclude heading fields from the output
	 *
	 * @return array of stdClass ordered by checkout order, index is cehcout item id
	 */
	public function get_fields( $exclude_heading = false ) {
		if ( is_null( $this->fields ) ) {
			global $wpdb;
			if ( $this->active_only ) {
				$sql = 'SELECT `id`, `name`, `type`, `mandatory`, `display_log`, `default`, `active`, `checkout_order`, `unique_name`, `options`, `checkout_set` '
							. ' FROM '. WPSC_TABLE_CHECKOUT_FORMS
							. ' WHERE checkout_set = %d  AND active = 1 ORDER BY checkout_order ASC';
			} else {
				$sql = 'SELECT `id`, `name`, `type`, `mandatory`, `display_log`, `default`, `active`, `checkout_order`, `unique_name`, `options`, `checkout_set` '
						. ' FROM '. WPSC_TABLE_CHECKOUT_FORMS
						. ' WHERE checkout_set = %d ORDER BY checkout_order ASC';
			}

			$this->fields = $wpdb->get_results( $wpdb->prepare( $sql, $this->id ), OBJECT_K );
			$this->field_unique_name_id = null;
		}

		$fields_to_return = $this->fields;

		if ( $exclude_heading ) {
			foreach ( $fields_to_return as $index => $field ) {
				if ( $field->type == 'heading' ) {
					unset( $fields_to_return[$index] );
				}
			}
		}
		return $fields_to_return;
	}

	/**
	 * Returns a count of how many fields are in the checkout form
	 *
	 * @access public
	 * @since 3.8.10
	 *
	 * @param bool $exclude_heading Optional. Defaults to false. Whether to exclude heading
	 *                                        fields from the output
	 * @return array
	 */
	public function get_field_count( $exclude_heading = false ) {
		return count( $this->get_fields( $exclude_heading ) );
	}

	/**
	 * Returns the title of the form
	 *
	 * @access public
	 * @since 3.8.10
	 *
	 * @return string
	 */
	public function get_title() {
		return isset( self::$form_titles[$this->id] ) ? self::$form_titles[$this->id] : '';
	}
}