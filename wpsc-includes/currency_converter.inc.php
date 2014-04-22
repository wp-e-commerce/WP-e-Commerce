<?php

	/*
		CURRENCYCONVERTER
		Date - Feb 23,2005
		Author - Harish Chauhan
		Email - harishc@ultraglobal.biz

		ABOUT
		This PHP script will use for conversion of currency.
		you can find it is tricky but it is usefull.

		Modified by Brian Barnes to change from one service that was
		not meant to be used from automated purposes to another that
		had no such restriction
	*/

	class CURRENCYCONVERTER {
		public $_amt   = 1;
		public $_to    = '';
		public $_from  = '';
		public $_error = '';

		function __construct( $amt = 1, $to = '', $from = '' ) {
			$this->_amt  = $amt;
			$this->_to   = $to;
			$this->_from = $from;

			return wpsc_convert_currency( $amt, $from, $to );
		}

		function error() {
			return $this->_error;
		}

		/**
		 * Given all details converts currency amount
		 *
		 * @param $amt double
		 *   The amount to convert.
		 *
		 * @param $to string
		 *   The currency you wish to convert to.
		 *
		 * @param $from string
		 *   The currency you are converting from.
		 */
		function convert( $amt = NULL, $to = '', $from = '' ) {

			$amount = urlencode( round( $amt, 2 ) );
			$from   = urlencode( $from );
			$to     = urlencode( $to );

			return wpsc_convert_currency( $amount, $from, $to );
		}
	}
?>