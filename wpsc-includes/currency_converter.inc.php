<?php
	
	/*
		CURRENCYCONVERTER 
		Date - Feb 23,2005
		Author - Harish Chauhan
		Email - harishc@ultraglobal.biz

		ABOUT
		This PHP script will use for conversion of currency.
		you can find it is tricky but it is usefull.
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
		function convert($amt = NULL, $to = "", $from = "")
		{
			if ($amt == 0) {
				return 0;
			}
			if($amt>1)
				$this->_amt=$amt;
			if(!empty($to))
				$this->_to=$to;
			if(!empty($from))
				$this->_from=$from;
				
			$count = 0;

			$dom = new DOMDocument();
			do {
				@$dom->loadHTML(file_get_contents('http://www.exchange-rates.org/converter/' . $this->_to . '/' . $this->_from . '/' . $this->_amt));
				$result = $dom->getElementById('ctl00_M_lblToAmount');
				if ($result) {
					return round($result->nodeValue, 2);
				}
				sleep(1);
				$count++;
			} while ($count < 10);
			
			trigger_error('Unable to connect to currency conversion service', E_USER_ERROR);
			return FALSE;
		}
	}
?>