<?php

if ( ! defined( 'WPSC_FLASH_DATA_COOKIE_NAME' ) ) {
	define( 'WPSC_FLASH_DATA_COOKIE_NAME', 'wpsc_flash_data_' . COOKIEHASH );
}

if ( ! defined( 'WPSC_FLASH_DATA_COOKIE_EXPIRATION' ) ) {
	define( 'WPSC_FLASH_DATA_COOKIE_EXPIRATION', 30 * 60 ); // valid for 30 minutes
}

final class WPSC_Flash_Data {
	private static $instance;
	public static function get_instance() {

		if ( ! self::$instance ) {
			self::$instance = new WPSC_Flash_Data();
		}

		return self::$instance;
	}

	private $data = array();

	private function __construct() {

		if ( ! $this->get_cookie() ) {
			$this->set_cookie();
		}

		$this->sweep();
		$this->mark();
	}

	private function sweep() {
		foreach ( $this->data as $key => $value ) {
			if ( strpos( $key, 'old:' ) === 0 ) {
				unset( $this->data[ $key ] );
			}
		}

		$this->set_cookie();
	}

	private function mark() {
		foreach ( $this->data as $key => $value ) {
			if ( strpos( $key, 'new:' ) === 0 ) {
				$new_key                = preg_replace( "/^new:/", 'old:', $key );
				$this->data[ $new_key ] = $this->data[ $key ];
				unset( $this->data[ $key ] );
			}
		}

		$this->set_cookie();
	}

	private function set_cookie() {
		setcookie( WPSC_FLASH_DATA_COOKIE_NAME, serialize( $this->data ), time() + WPSC_FLASH_DATA_COOKIE_EXPIRATION, COOKIEPATH, COOKIE_DOMAIN );
	}

	private function get_cookie() {
		$this->data = isset( $_COOKIE[ WPSC_FLASH_DATA_COOKIE_NAME ] ) ? unserialize( stripslashes( $_COOKIE[ WPSC_FLASH_DATA_COOKIE_NAME ] ) ) : array();
		return $this->data;
	}

	public function set( $key, $value ) {
		$this->data["new:{$key}"] = $value	;
		$this->set_cookie();
	}

	public function get( $key ) {
		if ( array_key_exists( "old:{$key}", $this->data ) ) {
			return $this->data["old:{$key}"];
		}

		return false;
	}

	public function keep( $key ) {
		$old_key = "old:{$key}";

		if ( ! array_key_exists( $old_key, $this->data ) ) {
			return false;
		}

		$this->data["new:{$key}"] = $this->data[ $old_key ];
		unset( $this->data[ $old_key ] );

		return true;
	}
}