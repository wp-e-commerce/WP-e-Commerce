<?php

class WPSC_Checkout_Form_Data
{
	private $data = array();
	private $gateway_data = array();
	private $log_id;

	public function __construct( $log_id ) {
		global $wpdb;

		$this->log_id = $log_id;

		if ( ! $this->data = wp_cache_get( $log_id, 'wpsc_checkout_form_data' ) ) {
			$sql = "
				SELECT c.unique_name, s.value
				FROM " . WPSC_TABLE_SUBMITTED_FORM_DATA . " AS s
				INNER JOIN " . WPSC_TABLE_CHECKOUT_FORMS . " AS c
					ON c.id = s.form_id
				WHERE s.log_id = %d
			";

			$sql = $wpdb->prepare( $sql, $log_id );
			$results = $wpdb->get_results( $sql, ARRAY_A );

			$address_types = array( 'shipping', 'billing' );
			foreach ( $results as $field ) {
				$this->data[$field['unique_name']] = $field['value'];
			}

			wp_cache_set( $log_id, $this->data, 'wpsc_checkout_form_data' );
		}
	}

	public function get( $key ) {
		$value = isset( $this->data[$key] ) ? $this->data[$key] : null;
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
				'address'    => 'street',
				'city'      => 'city',
				'state'     => 'state',
				'country'   => 'country',
				'postcode'  => 'zip',
				'phone'     => 'phone',
			);

			foreach( array( 'shipping', 'billing' ) as $type ) {
				$data_key = "{$type}_address";
				$this->gateway_data[$data_key] = array();
				foreach( $map as $key => $new_key ) {
					$key = $type . $key;
					if ( isset( $this->data[$key] ) ) {
						$value = $this->data[$key];

						if ( $new_key == 'state' && is_numeric( $value ) )
							$value = wpsc_get_state_by_id( $value, 'code' );

						$this->gateway_data[$data_key][$new_key] = $value;
					}
				}
				$this->gateway_data[$data_key]['name'] = $this->gateway_data[$data_key]['first_name'] + ' ' + $this->gateway_data[$data_key]['last_name'];
			}

			wp_cache_set( $this->log_id, $this->gateway_data, 'wpsc_checkout_form_gateway_data' );
		}

		return apply_filters( 'wpsc_checkout_form_gateway_data', $this->gateway_data, $this->log_id );
	}
}