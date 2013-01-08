<?php

/**
 * The WPSC Gateway class
 */
class wpsc_gateways {

	var $wpsc_gateways;
	var $gateway;
	var $gateway_count = 0;
	var $current_gateway = -1;
	var $in_the_loop = false;

	function wpsc_gateways() {
		global $nzshpcrt_gateways;

		$gateway_options = get_option( 'custom_gateway_options' );
		foreach ( $nzshpcrt_gateways as $gateway ) {
			if ( array_search( $gateway['internalname'], (array)$gateway_options ) !== false ) {
				$this->wpsc_gateways[] = $gateway;
			}
		}

		$this->wpsc_gateways = apply_filters(
			'wpsc_merchant_v2_gateway_loop_items',
			$this->wpsc_gateways,
			$this
		);
		$this->gateway_count = count( $this->wpsc_gateways );
	}

	/**
	 * checkout loop methods
	 */
	function next_gateway() {
		$this->current_gateway++;
		$this->gateway = $this->wpsc_gateways[$this->current_gateway];
		return $this->gateway;
	}

	function the_gateway() {
		$this->in_the_loop = true;
		$this->gateway = $this->next_gateway();
		if ( $this->current_gateway == 0 ) // loop has just started
			do_action( 'wpsc_checkout_loop_start' );
	}

	function have_gateways() {
		if ( $this->current_gateway + 1 < $this->gateway_count ) {
			return true;
		} else if ( $this->current_gateway + 1 == $this->gateway_count && $this->gateway_count > 0 ) {
			do_action( 'wpsc_checkout_loop_end' );
			// Do some cleaning up after the loop,
			$this->rewind_gateways();
		}

		$this->in_the_loop = false;
		return false;
	}

	function rewind_gateways() {
		$this->current_gateway = -1;
		if ( $this->gateway_count > 0 ) {
			$this->gateway = $this->wpsc_gateways[0];
		}
	}

}