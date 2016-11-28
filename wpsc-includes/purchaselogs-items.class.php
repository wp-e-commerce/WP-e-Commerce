<?php
class wpsc_purchaselogs_items {

	var $purchlogid;
	var $extrainfo;
	// the loop
	var $currentitem = -1;
	var $purchitem;
	var $allcartcontent;
	var $purch_item_count;
	// grand total
	var $totalAmount;
	// usersinfo
	var $userinfo;
	var $shippinginfo;
	var $customcheckoutfields = array();
	var $additional_fields = array();

	private $log = null;

	public function __construct( $id, $purchase_log = null ) {
		$this->purchlogid = $id;

		$this->log = $purchase_log instanceof WPSC_Purchase_Log
			? $purchase_log
			: new WPSC_Purchase_Log( $this->purchlogid );

		$this->get_purchlog_details();
	}

	function shippingstate( $id ) {
		if ( is_numeric( $id ) ) {
			return wpsc_get_region( $id );
		} else {
			return $id;
		}
	}

	function get_purchlog_details() {
		$this->allcartcontent = $this->log->get_items();
		$this->extrainfo      = (object) $this->log->get_data();

		// Need to manipulate the data array to match the previously expected style.
		$userinfo = $this->log->form_data()->get_raw_data();

		foreach ( $userinfo as $index => $field ) {
			$field = (array) $field;
			$field['form_field_id'] = $field['id'];
			$field['id'] = $field['data_id'];
			$userinfo[ $index ] = $field;
		}

		usort( $userinfo, array( $this, 'by_id' ) );

		// the $this->customcheckoutfields array is buggy because if the fields have the same name, they will
		// overwrite each other.
		// $this->additional_fields is introduced to fix this. However, the $this->customcheckoutfields array as well
		// as $this->customcheckoutfields needs to be kept for compatibility purposes.

		$this->additional_fields = $this->userinfo = $this->shippinginfo = array();

		foreach ( (array) $userinfo as $input_row ) {
			if ( stristr( $input_row['unique_name'], 'shipping' ) ) {
				$this->shippinginfo[ $input_row['unique_name'] ] = $input_row;
			} elseif ( stristr( $input_row['unique_name'], 'billing' ) ) {
				$this->userinfo[ $input_row['unique_name'] ] = $input_row;
			} else {
				$this->customcheckoutfields[ $input_row['name'] ] = $input_row;
				$this->additional_fields[] = $input_row;
			}
		}

		$this->purch_item_count = count( $this->allcartcontent );
	}

	private function by_id( $a, $b ) {
		return $a['id'] > $b['id'];
	}

	public function next_purch_item() {
		$this->currentitem++;
		$this->purchitem = $this->allcartcontent[ $this->currentitem ];
		return $this->purchitem;
	}

	public function the_purch_item() {
		$this->purchitem = $this->next_purch_item();
	}

	public function have_purch_item() {
		if ( $this->currentitem + 1 < $this->purch_item_count ) {
			return true;
		} else if ( $this->currentitem + 1 == $this->purch_item_count && $this->purch_item_count > 0 ) {
			// Do some cleaning up after the loop,
			$this->rewind_purch_item();
		}
		return false;
	}

	public function rewind_purch_item() {
		$this->currentitem = -1;
		if ( $this->purch_item_count > 0 ) {
			$this->purchitem = $this->allcartcontent[0];
		}
	}

	public function have_downloads_locked() {
		return $this->log->have_downloads_locked();
	}

	public function log() {
		return $this->log;
	}
}
