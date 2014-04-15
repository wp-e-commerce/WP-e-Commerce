<?php
/**
 * WPeC Currency Class
 *
 * A Currency
 *
 *
 * @since: 3.8.14
 *
 */
class WPSC_Currency {

	public $code        = '';
	public $symbol      = '';
	public $symbol_html = '';
	public $name        = '';

	/**
	 * Create a WPSC_Currency object
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @param 	string	$code			this currency's code, like "USD" for a U.S.A dollar, or "EUR" for a euro
	 * @param 	string	$symbol			the text symbol for this currency, like "$"
	 * @param 	string	$symbol_html    the HTML representation of the symbol, like "&#036;"
	 * @param 	string	$name           the currency name, like "US Dollar" or "Euro"
	 *
	 * @return void
	 */
	public function __construct( $code, $symbol = null, $symbol_html = null, $name = null ) {

		// if all parameters are specified we are trying to make a new currency object
		if ( ! empty( $code ) && ( ( $symbol != null ) || ( $symbol_html != null ) || ( $name != null ) ) ) {
			// Create a new currency object
			$this->code        = $code;
			$this->symbol      = $symbol;
			$this->symbol_html = $symbol_html;
			$this->name        = $name;
		} else {
			// if only code is specified the constructor is typing to get the information about an existing currency
			$wpsc_currency = WPSC_Countries::get_currency( $code );

			$this->code        = $wpsc_currency->code;
			$this->symbol      = $wpsc_currency->symbol;
			$this->symbol_html = $wpsc_currency->symbol_html;
			$this->name        = $wpsc_currency->name;
		}
	}

	/**
	 * get the currency object as an array of key =>value pairs
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return array
	 */
	public function as_array() {
		$result = array(
							'code'        => $wpsc_currency->code,
							'symbol'      => $wpsc_currency->symbol,
							'symbol_html' => $wpsc_currency->symbol_html,
							'name'        => $wpsc_currency->name,  // name is included for consistency with other classes
							'currency'    => $wpsc_currency->name,  // currency included for backwards compatibility
						);

		return $result;

	}

	/**
	 * Copy the country properties from a stdClass object to this class object.  Needed when retrieving
	 * objects from the database, but could be useful elsewhere in WPeC?
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @param stdClass	$currency	the stdClass having properties that will be used to create a currency
	 *
	 * @return self			for method chaining
	 */
	public function _copy_properties_from_stdclass( $currency ) {

		// no properties are really required, so we will check that they exist before we copy them
		// into our object
		if ( property_exits( $currency, 'code' ) ) {
			$this->code        = $currency->code;
		}

		if ( property_exits( $currency, 'symbol' ) ) {
			$this->symbol      = $currency->symbol;
		}

		if ( property_exits( $currency, 'symbol_html' ) ) {
			$this->symbol_html = $currency->symbol_html;
		}

		// We check for name and currency for the currency name, a backwards compatibility feature name is preferred
		if ( property_exits( $currency, 'currency' ) ) {
			$this->name        = $currency->currency;
		}

		if ( property_exits( $currency, 'name' ) ) {
			$this->name        = $currency->name;
		}

		return $this;
	}

}
