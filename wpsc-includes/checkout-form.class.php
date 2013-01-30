<?php

class WPSC_Checkout_Form {
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
	 		$sql = "SELECT * FROM " . WPSC_TABLE_CHECKOUT_FORMS . " WHERE checkout_set = %d AND active = 1 " . ( $exclude_heading ? "AND type != 'heading' " : '' ) . "ORDER BY checkout_order ASC";
			$this->fields = $wpdb->get_results( $wpdb->prepare( $sql, $this->id ) );
			$this->field_unique_name_id = null;
		}
		return $this->fields;
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
}