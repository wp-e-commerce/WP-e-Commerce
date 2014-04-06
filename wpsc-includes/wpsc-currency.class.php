<?php


class WPSC_Currency {

	public $code        = '';
	public $symbol      = '';
	public $symbol_html = '';
	public $name        = '';

	public function __construct( $code, $symbol = null, $symbol_html = null, $name = null ) {

		// if all parameters are specified we are trying to make a new currency object
		if ( ! empty ( $code ) && ( $symbol != null ) && ( $symbol_html != null ) && ( $name != null ) ) {
			// Create a new currency object
			$this->code        = $code;
			$this->symbol      = $symbol;
			$this->symbol_html = $symbol_html;
			$this->name        = $name;
		} else {
			// if only code is specified the constructor is typing to get the information about an existing currency
			WPSC_Countries::currencies( $code );

			$this->code        = WPSC_Countries::currencies( $code )->code;
			$this->symbol      = WPSC_Countries::currencies( $code )->symbol;
			$this->symbol_html = WPSC_Countries::currencies( $code )->symbol_html;
			$this->name        = WPSC_Countries::currencies( $code )->name;
		}
	}

	public function _copy_properties_from_stdclass( $currency ) {
		$this->code        = $currency->code;
		$this->symbol      = $currency->symbol;
		$this->symbol_html = $currency->symbol_html;


		// handle the case where the name is coming directly from the databse
		if ( property_exits( $currency, 'currency' ) ) {
			$this->name        = $currency->currency;
		}

		if ( property_exits( $currency, 'name' ) ) {
			$this->name        = $currency->name;
		}

	}

}
