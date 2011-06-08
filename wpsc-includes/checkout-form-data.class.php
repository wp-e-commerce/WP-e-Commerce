<?php

class WPSC_Checkout_Form_Data
{
	private $data = array();
	private $log_id;
	
	public function __construct( $log_id ) {
		global $wpdb;
		
		$this->log_id = $log_id;
		
		$sql = "
			SELECT *
			FROM " . WPSC_TABLE_SUBMITTED_FORM_DATA . " AS s
			INNER JOIN " . WPSC_TABLE_CHECKOUT_FORMS . " AS c
				ON c.id = s.form_id
			WHERE s.log_id = %d
		";
		
		$sql = $wpdb->prepare( $sql, $log_id );
		$this->data = $wpdb->get_row( $sql, ARRAY_A );
	}
}