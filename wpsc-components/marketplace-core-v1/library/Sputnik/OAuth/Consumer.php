<?php

class Sputnik_OAuth_Consumer {
	public $key;
	public $secret;

	function __construct($key, $secret, $callback_url=NULL) {
		$this->key = $key;
		$this->secret = $secret;
		$this->callback_url = $callback_url;
	}

	function __toString() {
		return "Sputnik_OAuth_Consumer[key=$this->key,secret=$this->secret]";
	}
}
