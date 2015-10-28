<?php
require_once( WPSC_TE_V2_CLASSES_PATH . '/orders-table.php' );

class WPSC_Controller_Customer_Account extends WPSC_Controller {
	private $status_filters;
	private $current_page = 1;
	private $current_status = 0;
	private $total_pages = 1;
	private $total_items = 0;
	private $count_items = 0;
	private $order_id = 0;
	private $form_data;
	private $form;
	private $cart_item_table;
	private $log;
	public $per_page = 10;

	public function __get( $name ) {

		if ( ! isset( $this->$name ) ) {
			switch ( $name ) {
				case 'status_filters':
					$this->fetch_status_filters();
					break;
			}
		}

		if ( property_exists( $this, $name ) ) {
			return $this->$name;
		}

		return null;
	}

	private function fetch_status_filters() {
		global $wpdb;

		$sql = $wpdb->prepare( "
			SELECT DISTINCT processed, COUNT(*) AS count FROM " . WPSC_TABLE_PURCHASE_LOGS . "
			WHERE user_ID = %d
			GROUP BY processed
			ORDER BY processed
		", get_current_user_id() );

		$results     = $wpdb->get_results( $sql );
		$statuses    = array();
		$total_count = 0;

		if ( ! empty( $results ) ) {
			foreach ( $results as $status ) {
				$statuses[ $status->processed ] = (int) $status->count;
			}

			$total_count = array_sum( $statuses );
		}

		$statuses[0] = $total_count;

		$this->status_filters = $statuses;
	}

	public function __construct() {
		parent::__construct();

		if ( ! is_user_logged_in() ) {
			wp_redirect( wpsc_get_login_url() );
			exit;
		}

		$this->title = wpsc_get_customer_account_title();
	}

	public function index() {
		wp_redirect( wpsc_get_customer_account_url( 'orders' ) );
		exit;
	}

	public function orders() {
		$this->parse_index_args( func_get_args() );

		if ( $this->order_id ) {
			$this->order( $this->order_id );
			return;
		}

		$table = WPSC_Orders_Table::get_instance();
		$table->offset = ( $this->current_page - 1 ) * $table->per_page;
		$table->status = $this->current_status;
		$table->fetch_items();
		$this->total_pages = ceil( $table->total_items / $table->per_page );
		$this->total_items = $table->total_items;
		$this->count_items = count( $table->items );
		$this->fetch_status_filters();
		$this->view = 'customer-account-index';
	}

	private function order( $id ) {
		$this->view    = 'customer-account-order';
		$form_data_obj = new WPSC_Checkout_Form_Data( $id );
		$this->form    = WPSC_Checkout_Form::get();
		$this->log     = new WPSC_Purchase_Log( $id );
		$this->title   = sprintf(
			__( 'View Order #%d', 'wp-e-commerce' ),
			$id
		);

		foreach ( $form_data_obj->get_raw_data() as $data ) {
			$this->form_data[ (int) $data->id ] = $this->process_checkout_form_value( $data );
		}

		require_once( WPSC_TE_V2_CLASSES_PATH . '/cart-item-table-order.php' );
		$this->cart_item_table = new WPSC_Cart_Item_Table_Order( $id );
	}

	private function process_checkout_form_value( $data ) {
		if ( 'billingstate' !== $data->unique_name && 'shippingstate' !== $data->unique_name ) {
			return $data;
		}

		if ( ! is_numeric( $data->value ) ) {
			return $data;
		}

		$data->value = wpsc_get_state_by_id( $data->value, 'name' );

		return $data;
	}

	private function parse_index_args( $args ) {
		if ( ! empty( $args ) && is_numeric( $args[0] ) ) {
			$this->order_id = (int) $args[0];
			return;
		}
		while ( ! empty( $args ) ) {
			$arg = array_shift( $args );

			switch ( $arg ) {
				case 'page':
					$this->current_page = (int) array_shift( $args );
					break;

				case 'status':
					$this->current_status = (int) array_shift( $args );
					break;
			}
		}
	}

	public function get_current_pagination_base() {
		$slug = 'orders';

		if ( $this->current_status > 0 ) {
			$slug .= '/status/' . $this->current_status;
		}

		return wpsc_get_customer_account_url( $slug );
	}

	public function settings() {
		$this->view = 'customer-account-settings';
		_wpsc_enqueue_shipping_billing_scripts();

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'submit_customer_settings_form' ) {
			$this->submit_customer_settings();
		}
	}

	private function submit_customer_settings() {
		if ( ! $this->verify_nonce( 'wpsc-customer-settings-form' ) ) {
			return;
		}

		$form_args  = wpsc_get_customer_settings_form_args();
		$validation = wpsc_validate_form( $form_args );

		if ( is_wp_error( $validation ) ) {
			$this->message_collection->add(
				__( 'Sorry, but it looks like there are some errors with your submitted information.', 'wp-e-commerce' ),
				'error'
			);
			wpsc_set_validation_errors( $validation, $context = 'inline' );
			return;
		}

		if ( ! empty( $_POST['wpsc_copy_billing_details'] ) ) {
			_wpsc_copy_billing_details();
		}

		$this->save_customer_settings();
	}

	private function save_customer_settings() {
		$form   = WPSC_Checkout_Form::get();
		$fields = $form->get_fields();

		$customer_details = wpsc_get_customer_meta( 'checkout_details' );

		if ( ! is_array( $customer_details ) ) {
			$customer_details = array();
		}

		foreach ( $fields as $field ) {
			if ( ! array_key_exists( $field->id, $_POST['wpsc_checkout_details'] ) ) {
				continue;
			}

			$value                          = $_POST['wpsc_checkout_details'][ $field->id ];
			$customer_details[ $field->id ] = $value;

			switch ( $field->unique_name ) {
				case 'billingstate':
					wpsc_update_customer_meta( 'billing_region', $value );
					break;
				case 'shippingstate':
					wpsc_update_customer_meta( 'shipping_region', $value );
					break;
				case 'billingcountry':
					wpsc_update_customer_meta( 'billing_country', $value );
					break;
				case 'shippingcountry':
					wpsc_update_customer_meta( 'shipping_country', $value );
					break;
				case 'shippingpostcode':
					wpsc_update_customer_meta( 'shipping_zip', $value );
					break;
			}
		}

		_wpsc_update_location();
		wpsc_save_customer_details( $customer_details );
	}

	public function digital_content() {
		require_once( WPSC_TE_V2_CLASSES_PATH . '/digital-contents-table.php' );
		$table = WPSC_Digital_Contents_Table::get_instance();
		$table->offset = ( $this->current_page - 1 ) * $table->per_page;
		$table->status = $this->current_status;
		$table->fetch_items();
		$this->total_pages = ceil( $table->total_items / $table->per_page );
		$this->total_items = $table->total_items;
		$this->count_items = count( $table->items );

		$this->view = 'customer-account-digital-content';
	}

}
