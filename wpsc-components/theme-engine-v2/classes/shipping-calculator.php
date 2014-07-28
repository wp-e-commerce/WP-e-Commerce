<?php

final class WPSC_Shipping_Calculator {

	private static $instances = array();

	public static function get_instance( $purchase_log = false ) {

		if ( empty( $purchase_log ) ) {
			$purchase_log = (int) wpsc_get_customer_meta( 'current_purchase_log_id' );
			if ( ! $purchase_log ) {
				return;
			}
		}

		if ( is_int( $purchase_log ) ) {
			$purchase_log = new WPSC_Purchase_Log( $purchase_log );
		}

		$id = $purchase_log->get( 'id' );

		if ( ! array_key_exists( $id, self::$instances ) ) {
			self::$instances[ $id ] = new WPSC_Shipping_Calculator( $purchase_log );
		}

		return self::$instances[ $id ];
	}

	private $purchase_log;
	private $modules;
	private $quotes;
	private $sorted_quotes;
	private $active_shipping_module;
	private $active_shipping_option;
	private $active_shipping_id;
	private $ids;

	public function __get( $name ) {
		if ( ! isset( $this->$name ) )
			switch ( $name ) {
				case 'quotes':
				case 'has_quotes':
				case 'ids':
					$this->get_all_quotes();
					break;
				case 'sorted_quotes':
					$this->get_all_quotes();
					$this->sort_quotes();
					break;
				case 'active_shipping_module':
				case 'active_shipping_option':
				case 'active_shipping_id':
					$this->get_active_shipping();
					break;
			}

		return $this->$name;
	}

	/**
	 * Constructor for shipping calculator object for a particular purchase log
	 *
	 * @param int|WPSC_Purchase_Log $purchase_log Purchase log ID or object. Optional. Default to purchase log of the current customer session
	 */
	public function __construct( $purchase_log = false ) {
		// get active shipping modules
		$this->modules = get_option( 'custom_shipping_options' );

		// default to current session's purchase log if called with no argument
		if ( empty( $purchase_log ) ) {
			$purchase_log = (int) wpsc_get_customer_meta( 'current_purchase_log_id' );
			if ( ! $purchase_log ) {
				return;
			}
		}

		// in case of integer argument, initialize the purchase log object
		if ( ! is_object( $purchase_log ) ) {
			$purchase_log = new WPSC_Purchase_Log( absint( $purchase_log ) );
		}

		$this->purchase_log = $purchase_log;
	}

	public function get_quotes( $module ) {
		global $wpsc_shipping_modules;

		$quote = false;

		if ( isset( $wpsc_shipping_modules[ $module ] ) &&
			 is_callable( array( $wpsc_shipping_modules[ $module ], 'getQuote' ) )
		) {
			$quote = $wpsc_shipping_modules[ $module ]->getQuote();
		}

		return $quote;
	}

	private function get_all_quotes() {
		global $wpsc_cart;
		$this->ids = array();

		foreach ( $this->modules as $module ) {
			$module_quotes = $this->get_quotes( $module );

			if ( ! empty( $module_quotes ) )
				foreach ( (array) $module_quotes as $option => $cost ) {
					$per_item = $wpsc_cart->calculate_per_item_shipping( $module );

					if ( ! isset( $this->quotes[ $module ] ) ) {
						$this->quotes[ $module ] = array();
					}

					$this->quotes[ $module ][ $option ] =
						(float) $cost + (float) $per_item;

					$this->ids[ $module ][ $option ]    =
						$this->encode_shipping_option_id( $module, $option );
				}
		}

		$this->has_quotes = count( $this->quotes ) > 0;
	}

	private function sort_quotes() {
		$this->sorted_quotes = $this->quotes;
		foreach ( $this->sorted_quotes as $module => $options ) {
			asort( $this->sorted_quotes[$module] );
		}
		uasort( $this->sorted_quotes, array( $this, '_callback_sort_quotes' ) );
	}

	public function _callback_sort_quotes( $method_1, $method_2 ) {
		$val_1 = reset( $method_1 );
		$val_2 = reset( $method_2 );

		if ( $val_1 == $val_2 ) {
			return 0;
		}

		return $val_1 < $val_2 ? -1 : 1;
	}

	public function set_active_method( $module, $option ) {
		if ( is_null( $this->quotes ) ) {
			$this->get_all_quotes();
		}

		if ( ! array_key_exists( $module, $this->quotes ) ||
			 ! array_key_exists( $option, $this->quotes[ $module ] )
		) {
			return;
		}

		$this->purchase_log->set( 'shipping_method', $module );
		$this->purchase_log->set( 'shipping_option', $option );
		$this->purchase_log->set( 'base_shipping'  , $this->quotes[ $module ][ $option ] );

		$this->purchase_log->save();

		$this->active_shipping_module = $module;
		$this->active_shipping_option = $option;
		$this->active_shipping_id     = $this->ids[ $module ][ $option ];
	}

	private function get_active_shipping() {

		if ( is_null( $this->ids ) ) {
			$this->get_all_quotes();
		}

		$current_purchase_log_id = wpsc_get_customer_meta( 'current_purchase_log_id' );

		$purchase_log = new WPSC_Purchase_Log( $current_purchase_log_id );
		$module       = $purchase_log->get( 'shipping_method' );
		$option       = $purchase_log->get( 'shipping_option' );

		if ( empty( $module ) || empty( $option ) ) {
			$this->active_shipping_id     = '';
			$this->active_shipping_option = '';
			$this->active_shipping_module = '';
			return;
		}

		$this->active_shipping_id     = $this->ids[ $module ][ $option ];
		$this->active_shipping_option = $option;
		$this->active_shipping_module = $module;
	}

	private function encode_shipping_option_id( $module, $option ) {
		return md5( $module . ':' . $option );
	}
}