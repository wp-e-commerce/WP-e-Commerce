<?php
/**
 * The WP eCommerce Checkout form data Class
 *
 * @package wp-e-commerce
 * @since 3.8
 */

class WPSC_Checkout_Form_Data extends WPSC_Query_Base {
	protected $raw_data       = array();
	protected $segmented_data = array();
	protected $gateway_data   = array();
	protected $submitted_data = array();
	protected $log_id = 0;

	/**
	 * An array of arrays of cache keys. Allows versioning the cached values,
	 * and busting cache for a group if needed (by incrementing the version).
	 *
	 * @var array
	 */
	protected $group_ids = array(
		'raw_data' => array(
			'group'     => 'wpsc_checkout_form_raw_data',
			'version' => 1,
		),
		'gateway_data' => array(
			'group'     => 'wpsc_checkout_form_gateway_data',
			'version' => 0,
		),
	);

	public function __construct( $log_id, $pre_fetch = true ) {
		$this->log_id = absint( $log_id );
		if ( $pre_fetch ) {
			$this->fetch();
		}
	}

	/**
	 * Fetches the actual $data array.
	 *
	 * @access protected
	 * @since 3.11.5
	 *
	 * @return WPSC_Checkout_Form_Data
	 */
	protected function fetch() {
		if ( $this->fetched ) {
			return;
		}

		global $wpdb;

		if ( ! $this->raw_data = $this->cache_get( $this->log_id, 'raw_data' ) ) {
			$sql = "
				SELECT c.id, c.name, c.type, c.mandatory, c.unique_name, c.checkout_set as form_group, s.id as data_id, s.value
				FROM " . WPSC_TABLE_SUBMITTED_FORM_DATA . " AS s
				INNER JOIN " . WPSC_TABLE_CHECKOUT_FORMS . " AS c
					ON c.id = s.form_id
				WHERE s.log_id = %d AND active = '1'
			";

			$sql = $wpdb->prepare( $sql, $this->log_id );
			$this->raw_data = $wpdb->get_results( $sql );

			// Set the cache for raw checkout for data
			$this->cache_set( $this->log_id, $this->raw_data, 'raw_data' );
		}

		$this->exists = ! empty( $this->raw_data );
		$this->segmented_data = array(
			'shipping' => array(),
			'billing'  => array(),
		);

		// At the moment, only core fields have unique_name. In the future,
		// all fields will have a unique name rather than just IDs.
		foreach ( $this->raw_data as $index => $field ) {
			if ( ! empty( $field->unique_name ) ) {

				$is_shipping = false !== strpos( $field->unique_name, 'shipping' );

				if ( $is_shipping ) {
					$this->segmented_data['shipping'][ str_replace( 'shipping', '', $field->unique_name ) ] = $index;
				} else {
					$this->segmented_data['billing'][ str_replace( 'billing', '', $field->unique_name ) ] = $index;
				}

				$this->data[ $field->unique_name ] = $field->value;
			}
		}

		do_action( 'wpsc_checkout_form_data_fetched', $this );

		$this->fetched = true;

		return $this;
	}

	/**
	 * Get the raw data indexed by the 'id' column.
	 *
	 * @since  3.11.5
	 *
	 * @return array
	 */
	public function get_indexed_raw_data() {
		$this->fetch();

		$data = array();
		foreach ( $this->raw_data as $field ) {
			$data[ $field->id ] = $field;
		}

		return $data;
	}

	/**
	 * Determines if values in shipping fields matches values in billing fields.
	 *
	 * @since  3.11.5
	 *
	 * @return bool  Whether shipping values match billing values.
	 */
	public function shipping_matches_billing() {
		$this->fetch();

		foreach ( $this->segmented_data['shipping'] as $id => $index ) {
			// If we're missing data from any of these arrays, something's wrong (and they don't match).
			if ( ! isset(
				$this->raw_data[ $index ],
				$this->segmented_data['billing'][ $id ],
				$this->raw_data[ $this->segmented_data['billing'][ $id ] ]
			) ) {
				return false;
			}

			// Now we can get the values for the fields.
			$ship_val    = $this->raw_data[ $index ]->value;
			$billing_val = $this->raw_data[ $this->segmented_data['billing'][ $id ] ]->value;

			// Do they match?
			if ( $ship_val !== $billing_val ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the segmented billing info.
	 *
	 * @since  3.11.5
	 *
	 * @return array
	 */
	public function get_billing_data() {
		$this->fetch();

		return $this->segmented_data['billing'];
	}

	/**
	 * Get the segmented shipping info.
	 *
	 * @since  3.11.5
	 *
	 * @return array
	 */
	public function get_shipping_data() {
		$this->fetch();

		return $this->segmented_data['shipping'];
	}

	/**
	 * Gets the raw data array.
	 *
	 * @since  3.11.5
	 *
	 * @return array
	 */
	public function get_raw_data() {
		$this->fetch();

		return $this->raw_data;
	}

	/**
	 * Prepares the return value for get() (apply_filters, etc).
	 *
	 * @access protected
	 * @since  3.11.5
	 *
	 * @param  mixed  $value Value fetched
	 * @param  string $key   Key for $data.
	 *
	 * @return mixed
	 */
	protected function prepare_get( $value, $key ) {
		return apply_filters( 'wpsc_checkout_form_data_get_property', $value, $key, $this );
	}

	/**
	 * Prepares the return value for get_data() (apply_filters, etc).
	 *
	 * @access protected
	 * @since  3.11.5
	 *
	 * @return mixed
	 */
	protected function prepare_get_data() {
		return apply_filters( 'wpsc_checkout_form_get_data', $this->data, $this->log_id, $this );
	}

	public function get_gateway_data() {
		if ( ! $this->gateway_data = $this->cache_get( $this->log_id, 'gateway_data' ) ) {
			$map = array(
				'firstname' => 'first_name',
				'lastname'  => 'last_name',
				'address'   => 'street',
				'city'      => 'city',
				'state'     => 'state',
				'country'   => 'country',
				'postcode'  => 'zip',
				'phone'     => 'phone',
			);

			foreach ( array( 'shipping', 'billing' ) as $type ) {

				$data_key = "{$type}_address";
				$this->gateway_data[ $data_key ] = array();

				foreach ( $map as $key => $new_key ) {
					$key = $type . $key;

					if ( isset( $this->data[ $key ] ) ) {
						$value = $this->data [$key ];

						if ( $new_key == 'state' && is_numeric( $value ) ) {
							$value = wpsc_get_state_by_id( $value, 'code' );
						}

						$this->gateway_data[ $data_key ][ $new_key ] = $value;
					}
				}

				$name  = isset( $this->gateway_data[ $data_key ]['first_name'] ) ? $this->gateway_data[ $data_key ]['first_name'] . ' ' : '';
				$name .= isset( $this->gateway_data[ $data_key ]['last_name']  ) ? $this->gateway_data[ $data_key ]['last_name']        : '';

				$this->gateway_data[ $data_key ]['name'] = trim( $name );
			}

			// Sets the cache for checkout form gateway data
			$this->cache_set( $this->log_id, $this->gateway_data, 'gateway_data' );
		}

		return apply_filters( 'wpsc_checkout_form_gateway_data', $this->gateway_data, $this->log_id );
	}

	/**
	 * Set specific database fields.
	 *
	 * @param string|int $key   Expects either form ID or unique name.
	 * @param string     $value Value to be set for field.
	 *
	 * @since  3.11.5
	 * @return WPSC_Checkout_Form_Data Current instance of form data.
	 */
	public function set( $key, $value = '' ) {

		if ( ! is_numeric( $key ) ) {
			$checkout_form = WPSC_Checkout_Form::get();
			$key = $checkout_form->get_field_id_by_unique_name( $key );
		}

		$this->submitted_data[ $key ] = $value;

		return $this;
	}

	/**
	 * Used in conjunction with set() method, saves individual checkout form fields to database.
	 *
	 * @since  3.11.5
	 * @return void
	 */
	public function save() {

		$log    = new WPSC_Purchase_Log( $this->log_id );
		$form   = WPSC_Checkout_Form::get();
		$fields = $form->get_fields();

		$original_data = wp_list_pluck( $this->get_raw_data(), 'value', 'id' );

		$this->submitted_data = array_replace( $original_data, $this->submitted_data );

		return self::save_form( $log, $fields, $this->submitted_data );
	}

	/**
	 * Save Submitted Form Fields to the wpsc_submited_form_data table.
	 *
	 * @param WPSC_Purchase_Log $purchase_log
	 * @param array $fields
	 * @param array $data
	 * @param bool  $update_customer
	 * @return void
	 */
	public static function save_form( $purchase_log, $fields, $data = array(), $update_customer = true ) {
		global $wpdb;

		$log_id = $purchase_log->get( 'id' );

		// delete previous field values
		$sql = $wpdb->prepare( "DELETE FROM " . WPSC_TABLE_SUBMITTED_FORM_DATA . " WHERE log_id = %d", $log_id );
		$wpdb->query( $sql );

		if ( empty( $data ) && isset( $_POST['wpsc_checkout_details'] ) ) {
			$data = wp_unslash( $_POST['wpsc_checkout_details'] );
		}

		$customer_details = array();

		foreach ( $fields as $field ) {

			if ( $field->type == 'heading' ) {
				continue;
			}

			$value = '';

			if ( isset( $data[ $field->id ] ) ) {
				$value = $data[ $field->id ];
			}

			$customer_details[ $field->id ] = $value;

			$wpdb->insert(
				WPSC_TABLE_SUBMITTED_FORM_DATA,
				array(
					'log_id'  => $log_id,
					'form_id' => $field->id,
					'value'   => $value,
				),
				array(
					'%d',
					'%d',
					'%s',
				)
			);
		}

		if ( $update_customer ) {
			wpsc_save_customer_details( $customer_details );
		}
	}

	/**
	 * Returns the log id property.
	 *
	 * @since  3.11.5
	 *
	 * @return int  The log id.
	 */
	public function get_log_id() {
		return $this->log_id;
	}

}
