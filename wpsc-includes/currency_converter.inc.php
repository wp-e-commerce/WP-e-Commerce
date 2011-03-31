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
		function convert($amt=NULL,$to="",$from="")
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

			$host="www.xe.com";
			$fp = @fsockopen($host, 80, $errno, $errstr, 30);
			if (!$fp)
			{
				$this->_error="$errstr ($errno)<br />\n";
				return false;
			}
			else
			{
				$file="/ucc/convert.cgi";
				$str = "?language=xe&Amount=".$this->_amt."&From=".$this->_from."&To=".$this->_to;
				$out = "GET ".$file.$str." HTTP/1.0\r\n";
			    $out .= "Host: $host\r\n";
				$out .= "Connection: Close\r\n\r\n";

				@fputs($fp, $out);
				while (!@feof($fp))
				{
					$data.= @fgets($fp, 128);
				}
				@fclose($fp);
				
				@preg_match("/^(.*?)\r?\n\r?\n(.*)/s", $data, $match);
				$data =$match[2];
				$search = array ("'<script[^>]*?>.*?</script>'si",  // Strip out javascript
								 "'<[\/\!]*?[^<>]*?>'si",           // Strip out HTML tags
								 "'([\r\n])[\s]+'",                 // Strip out white space
								 "'&(quot|#34);'i",                 // Replace HTML entities
								 "'&(amp|#38);'i",
								 "'&(lt|#60);'i",
								 "'&(gt|#62);'i",
								 "'&(nbsp|#160);'i",
								 "'&(iexcl|#161);'i",
								 "'&(cent|#162);'i",
								 "'&(pound|#163);'i",
								 "'&(copy|#169);'i",
								 "'&#(\d+);'e");                    // evaluate as php

				$replace = array ("",
								  "",
								  "\\1",
								  "\"",
								  "&",
								  "<",
								  ">",
								  " ",
								  chr(161),
								  chr(162),
								  chr(163),
								  chr(169),
								  "chr(\\1)");

				$data = @preg_replace($search, $replace, $data);
				@preg_match_all("/(\d[^\.]*(\.\d+)?)/",$data,$mathces);
				$return=preg_replace("/[^\d\.]*/","",$mathces[0][1]);
				return (double)$return;
			}
		}
	}
?>