<?php

final class WPSC_Checkout_Wizard {
	private static $instance;

	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new WPSC_Checkout_Wizard();
		}

		return self::$instance;
	}

	public $steps;
	private $disabled;
	private $completed;
	private $active_step;
	private $pending_step;

	private function __construct() {
	}

	public function __get( $name ) {
		if ( is_null( $this->$name ) ) {
			switch ( $name ) {
				case 'disabled':
					$this->get_disabled();
					break;
				case 'completed':
					$this->get_completed();
					break;
				case 'active_step':
					$this->get_active_step();
					break;
				case 'pending_step':
					$this->get_pending_step();
					break;
			}
		}

		if ( ! isset( $this->$name ) ) {
			return null;
		}

		return $this->$name;
	}

	public function get_active_step() {
		$this->active_step = _wpsc_get_current_controller_slug();

		if ( empty( $this->active_step ) || 'index' == $this->active_step ) {
			$this->active_step = 'shipping-and-billing';
		}
	}

	private function get_disabled() {
		if ( is_null( $this->completed ) ) {
			$this->get_completed();
		}

		$this->disabled     = array_diff( array_keys( $this->steps ), $this->completed );
		$this->pending_step = array_shift( $this->disabled );

		if ( ! is_array( $this->disabled ) ) {
			$this->disabled = array();
		}
	}

	private function get_completed() {
		$this->completed = wpsc_get_customer_meta( 'checkout_wizard_completed_steps' );

		if ( ! is_array( $this->completed ) ) {
			$this->completed = array();
		}
	}

	private function get_pending_step() {
		$this->get_disabled();
	}

	public function is_active( $step ) {
		if ( is_null( $this->active_step ) ) {
			$this->get_active_step();
		}

		return $this->active_step == $step;
	}

	public function is_disabled( $step ) {
		if ( is_null( $this->disabled ) ) {
			$this->get_disabled();
		}

		return in_array( $step, $this->disabled );
	}

	public function is_completed( $step ) {
		if ( is_null( $this->completed ) ) {
			$this->get_completed();
		}

		return in_array( $step, $this->completed );
	}

	public function completed_step( $step ) {
		if ( is_null( $this->completed ) ) {
			$this->get_completed();
		}

		$this->completed[] = $step;
		wpsc_update_customer_meta( 'checkout_wizard_completed_steps', $this->completed );
		$this->get_disabled();
	}

	public function reset() {
		wpsc_delete_customer_meta( 'checkout_wizard_completed_steps' );
		$this->completed = array();
		$this->get_disabled();
	}
}