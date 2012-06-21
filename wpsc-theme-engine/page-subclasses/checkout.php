<?php

class WPSC_Page_Checkout extends WPSC_Page
{
	protected $current_step  = '';

	public function __construct( $callback ) {
		parent::__construct( $callback );
	}

	public function get_current_step() {
		return $this->current_step;
	}

	public function process_validate_details() {
		$checkout_fields = WPSC_Checkout_Form::get()->get_fields();

		$validation_rules = array();
		foreach ( $checkout_fields as $field ) {
			$rules = array();
			if ( $field->mandatory )
				$rules[] = 'required';

			if ( $field->type == 'email' )
				$rules[] = 'email';

			if ( ! empty( $rules ) )
				$validation_rules[$field->id] = array(
					'title' => $field->name,
					'rules' => $rules,
				);
		}

		$validation = wpsc_validate_form( $validation_rules, $_POST['wpsc_checkout_details'] );

		if ( is_wp_error( $validation ) ) {
			$this->set_validation_errors( $validation );
			$this->current_step = 'details';
			return;
		}

		$this->current_step = 'payment-delivery';
	}

	public function main() {
		$this->current_step = 'details';
	}
}
function wpsc_get_current_checkout_step() {
	if (  ! wpsc_is_checkout() )
		return false;

	global $wpsc_page_instance;
	return $wpsc_page_instance->get_current_step();
}