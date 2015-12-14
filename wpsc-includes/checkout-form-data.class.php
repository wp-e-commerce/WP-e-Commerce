<?php
/**
 * The WP eCommerce Checkout form data Class
 *
 * @package wp-e-commerce
 * @since 3.8
 */

class WPSC_Checkout_Form_Data {
	private $data           = array();
	private $raw_data       = array();
	private $gateway_data   = array();
	private $submitted_data = array();
	private $log_id;

	public function __construct( $log_id ) {
		global $wpdb;

		$this->log_id = $log_id;

		if ( ! $this->raw_data = wp_cache_get( $log_id, 'wpsc_checkout_form_raw_data' ) ) {
			$sql = "
				SELECT c.id, c.name, c.unique_name, s.value
				FROM " . WPSC_TABLE_SUBMITTED_FORM_DATA . " AS s
				INNER JOIN " . WPSC_TABLE_CHECKOUT_FORMS . " AS c
					ON c.id = s.form_id
				WHERE s.log_id = %d AND active = '1'
			";

			$sql = $wpdb->prepare( $sql, $log_id );
			$this->raw_data = $wpdb->get_results( $sql );

			//Set the cache for raw checkout for data
			wp_cache_set( $log_id, $this->raw_data, 'wpsc_checkout_form_raw_data' );
		}

		// At the moment, only core fields have unique_name. In the future, all fields will have
		// a unique name rather than just IDs.
		foreach ( $this->raw_data as $field ) {
			if ( ! empty( $field->unique_name ) ) {
				$this->data[ $field->unique_name ] = $field->value;
			}
		}
	}

	public function get_raw_data() {
		return $this->raw_data;
	}

	public function get( $key ) {
		$value = isset( $this->data[ $key ] ) ? $this->data[ $key ] : null;
		return apply_filters( 'wpsc_checkout_form_data_get_property', $value, $key, $this );
	}

	public function get_data() {
		return apply_filters( 'wpsc_checkout_form_get_data', $this->data, $this->log_id );
	}

	public function get_gateway_data() {
		if ( ! $this->gateway_data = wp_cache_get( $this->log_id, 'wpsc_checkout_form_gateway_data' ) ) {
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

			//Sets the cache for checkout form gateway data
			wp_cache_set( $this->log_id, $this->gateway_data, 'wpsc_checkout_form_gateway_data' );
		}

		return apply_filters( 'wpsc_checkout_form_gateway_data', $this->gateway_data, $this->log_id );
	}

	/**
	 * Set specific database fields.
	 *
	 * @param string|int $key   Expects either form ID or unique name.
	 * @param string     $value Value to be set for field.
	 *
	 * @since  4.0
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
	 * @since  4.0
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
	 * @return void
	 */
	public static function save_form( $purchase_log, $fields, $data = array() ) {
		global $wpdb;

		$log_id = $purchase_log->get( 'id' );

		// delete previous field values
		$sql = $wpdb->prepare( "DELETE FROM " . WPSC_TABLE_SUBMITTED_FORM_DATA . " WHERE log_id = %d", $log_id );
		$wpdb->query( $sql );

		if ( empty( $data ) && isset( $_POST['wpsc_checkout_details'] ) ) {
			$data = $_POST['wpsc_checkout_details'];
		}

		$customer_details = array();

		foreach ( $fields as $field ) {

			if ( $field->type == 'heading' ) {
				continue;
			}

			$value = '';

			if ( isset( $data[ $field->id ] ) ) {
				$value = wp_unslash( $data[ $field->id ] );
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

		wpsc_save_customer_details( $customer_details );
	}
}