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

	Class CURRENCYCONVERTER
	{
		var $_amt=1;
		var $_to="";
		var $_from="";
		var $_error="";
		function CURRENCYCONVERTER($amt=1,$to="",$from="")
		{
			$this->_amt=$amt;
			$this->_to=$to;
			$this->_from=$from;
		}
		function error()
		{
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
		function convert($amt = NULL, $to = "", $from = ""){

			$amount = urlencode(round($amt,2));
			$from_Currency = urlencode($from);
			$to_Currency = urlencode($to);

			$url = "http://www.google.com/ig/calculator?hl=en&q=$amount$from_Currency=?$to_Currency";

			$ch = curl_init();
			$timeout = 20;
			curl_setopt ($ch, CURLOPT_URL, $url);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			$rawdata = curl_exec($ch);
			curl_close($ch);
			if(empty($rawdata)){
				throw new Exception( __( 'unable to connect to currency conversion service', 'wpsc' ) );
			}

			// google doesn't return a valid JSON response, so we have to
			// parse that.
			// attempt to use regexp to parse the converted amount. if that fails,
			// fall back to using json_decode().
			preg_match( '/rhs[^"]+"([\d\s.,]+)/', $rawdata, $matches );
			if ( isset( $matches[1] ) ) {
				$to_amount = (float) str_replace( array( ',', ' ' ), '', $matches[1] );
			} else {
				$rawdata = preg_replace( '/(\{|,\s*)([^\s:]+)(\s*:)/', '$1"$2"$3', $rawdata );
				$data = json_decode( $rawdata );
			}
			$to_amount = round( $to_amount, 2 );

			return $to_amount;
		}
	}
?>