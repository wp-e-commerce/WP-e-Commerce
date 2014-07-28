<?php
require_once( WPSC_TE_V2_CLASSES_PATH . '/flash-data.php' );

class WPSC_Message_Collection {

	private $messages = array();
	private $flash_data;
	private static $instance = null;

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new WPSC_Message_Collection();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->flash_data = WPSC_Flash_Data::get_instance();
	}

	private function get_flash_messages() {
		$flash_messages = $this->flash_data->get( 'messages' );

		if ( ! is_array( $flash_messages ) ) {
			return array();
		}

		return $flash_messages;
	}

	public function add( $message, $type = 'success', $context = 'main', $mode = 'normal', $id = false ) {

		if ( 'flash' == $mode ) {
			$messages = $this->get_flash_messages();
		} else {
			$messages =& $this->messages;
		}

		if ( ! isset( $messages[ $type ] ) ) {
			$messages[ $type ] = array();
		}

		if ( ! isset( $messages[ $type ][ $context ] ) ) {
			$messages[ $type ][ $context ] = array();
		}

		if ( $id ) {
			$messages[ $type ][ $context ][ $id ] = $message;
		} else {
			$messages[ $type ][ $context ][] = $message;
		}

		if ( 'flash' == $mode ) {
			$this->flash_data->set( 'messages', $messages );
		}

		return true;
	}

	private function filter( $array, $types = 'all', $context = 'main' ) {

		if ( $types == 'all' ) {
			$types = array_keys( $array );
		}

		if ( ! is_array( $types ) ) {
			$types = explode( ',', $types );
		}

		$messages = array();

		foreach ( $types as $type ) {
			if ( isset( $array[ $type ] ) && isset( $array[ $type ][ $context ] ) ) {
				$messages = array_merge( $messages, array( $type => $array[ $type ][ $context ] ) );
			}
		}

		return $messages;
	}

	public function query( $types = 'all', $context = 'main', $mode = 'all' ) {
		$messages = array();

		if ( in_array( $mode, array( 'all', 'normal' ) ) ) {
			$messages += $this->filter( $this->messages, $types, $context );
		}

		if ( in_array( $mode, array( 'all', 'flash' ) ) ) {
			$flash_messages = $this->get_flash_messages();
			$messages      += $this->filter( $flash_messages, $types, $context );
		}

		return $messages;
	}

	private function filter_contains( $array, $types = 'all', $context = 'main' ) {

		if ( $types == 'all' ) {
			$types = array_keys( $array );
		}

		if ( ! is_array( $types ) ) {
			$types = explode( ',', $types );
		}

		foreach ( $types as $type ) {
			if ( ! empty( $array[ $type ] ) && ! empty( $array[ $type ][ $context ] ) ) {
				return true;
			}
		}

		return false;
	}

	public function contains( $types = 'all', $context = 'main', $mode = 'all' ) {

		if ( in_array( $mode, array( 'all', 'normal' ) ) && ! $this->filter_contains( $this->messages, $types, $context ) ) {
			return false;
		}

		if ( in_array( $mode, array( 'all', 'flash' ) ) ) {

			/* @todo: Investigate whether or not we need this variable here.  It is dead, but $this->get_flash_messages() may be necessary */
			$flash_messages = $this->get_flash_messages();

			if ( ! $this->filter_contains( $this->messages, $types, $context ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 *
	 * @todo  Investigate the necessity of this function.  It is dead and currently unused in core.
	 *
	 * @param [type] $message [description]
	 * @param string $type    [description]
	 * @param string $context [description]
	 */
	private function add_flash_message( $message, $type = 'success', $context = 'main' ) {
		$flash_messages = $this->get_flash_messages();

		if ( ! isset( $flash_messages[ $type ] ) ) {
			$flash_messages[ $type ] = array();
		}

		if ( ! isset( $flash_messages[$type][$context] ) ) {
			$flash_messages[ $type ][ $context ] = array();
		}

		$flash_messages[ $type ][ $context ][] = $message;

		$this->flash_data->set( 'messages', $flash_messages );
	}
}

WPSC_Message_Collection::get_instance();