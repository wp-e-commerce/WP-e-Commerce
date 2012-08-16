<?php
abstract class WPSC_Purchase_Log_Notification
{
	protected $address;
	protected $args = array();
	protected $raw_message;
	protected $message;
	protected $title;

	public function __construct( $address, $args = array() ) {
		$this->address     = $address;
		$this->args        = $args;
		$this->raw_message = $this->get_raw_message();
		$this->title       = $this->get_subject();

		if ( ! empty( $this->args ) )
			$this->message = $this->process_args();
	}

	public function get_raw_message() {
		return '';
	}

	public function get_message() {
		return $this->message;
	}

	public function get_subject() {
		return '';
	}

	public function process_args() {
		$tokens = array_keys( $this->args );
		$values = array_values( $this->args );

		foreach ( $tokens as &$token ) {
			$token = "%{$token}%";
		}

		return str_replace( $tokens, $values, $this->raw_message );
	}

	public function send() {
		$from_email = get_option( 'return_email' );
		$from_name  = get_option( 'return_name' );

		// don't worry, wp_mail() will automatically assign default values if these options
		// are not set and empty
		$headers = 'From: "' . $from_name . '" <' . $from_email . '>';
		wp_mail( $this->address, $this->title, $this->message );
	}
}

class WPSC_Purchase_Log_Customer_Notification extends WPSC_Purchase_Log_Notification
{
	public function get_raw_message() {
		return apply_filters( 'wpsc_purchase_log_customer_notification_raw_message', get_option( 'wpsc_email_receipt' ), $this->args['purchase_log_id'] );
	}

	public function get_subject() {
		return __( 'Purchase Receipt', 'wpsc' );
	}
}

class WPSC_Purchase_Log_Customer_Pending_Notification extends WPSC_Purchase_Log_Customer_Notification
{
	public function get_raw_message() {
		$message = __( 'Thank you, your purchase is pending, you will be sent an email once the order clears.', 'wpsc' ) . "\n\r";
		$message .= parent::get_raw_message();

		return apply_filters( 'wpsc_purchase_log_customer_pending_notification_raw_message', $message, $this->args['purchase_log_id'] );
	}

	public function get_subject() {
		return __( 'Order Pending: Payment Required', 'wpsc' );
	}
}

class WPSC_Purchase_Log_Admin_Notification extends WPSC_Purchase_Log_Notification
{
	public function get_raw_message() {
		$form_data = new WPSC_Checkout_Form_Data( $this->args['purchase_log_id'] );
		$raw_data = $form_data->get_raw_data();

		$data = array(
			'billing'  => array( 'title' => __( 'Billing Details', 'wpsc' ), 'fields' => array() ),
			'shipping' => array( 'title' => __( 'Shipping Details', 'wpsc' ), 'fields' => array() ),
			'misc'     => array( 'title' => __( 'Other Details', 'wpsc' ), 'fields' => array() ),
		);

		foreach ( $raw_data as $field ) {
			if ( strpos( $field->unique_name, 'billing' ) !== false )
				$type = 'billing';
			elseif ( strpos( $field->unique_name, 'shipping' ) !== false )
				$type = 'shipping';
			else
				$type = 'misc';
			$data[$type]['fields'][] = $field;
		}

		// Transaction details
		$message = '== ' . __( 'Transaction Details', 'wpsc' ) . " ==\r\n";
		$message .= __( 'Sale Log ID', 'wpsc' ) . ': %purchase_id%' . "\r\n";
		if ( ! empty( $this->args['transaction_id'] ) )
			$message .= __( 'Transaction ID', 'wpsc' ) . ': ' . $this->args['transaction_id'] . "\r\n";

		// Discount
		if ( ! empty( $this->args['coupon_code'] ) ) {
			$message .= __( 'Coupon Code', 'wpsc' ) . ': ' . $this->args['coupon_code'] . "\r\n";
			$message .= __( 'Discount Value', 'wpsc' ) . ': ' . $this->args['discount'] . "\r\n";
		}

		// Subtotal, tax, shipping, total
		$message .= __( 'Subtotal' ,'wpsc' ) . ': ' . $this->args['subtotal'] . "\r\n";
		$message .= __( 'Tax', 'wpsc' ) . ': ' . $this->args['tax'] . "\r\n";
		$message .= __( 'Shipping', 'wpsc' ) . ': ' . $this->args['shipping'] . "\r\n";
		$message .= __( 'Total', 'wpsc' ) . ': ' . $this->args['total'] . "\r\n";

		$message .= "\r\n";

		// Items
		$message .= '== ' . __( 'Items', 'wpsc' ) . " ==\r\n";
		$message .= "%product_list%\r\n";

		// Checkout fields
		$message .= "\r\n";
		foreach ( $data as $section ) {
			$message .= "== {$section['title']} ==\r\n";
			foreach ( $section['fields'] as $field ) {
				$message .= $field->name . ' : ' . $field->value . "\r\n";
			}
			$message .= "\r\n";
		}

		return apply_filters( 'wpsc_purchase_log_admin_notification_raw_message', $message, $this->args['purchase_log_id'] );
	}

	public function get_subject() {
		return __( 'Transaction Report', 'wpsc' );
	}
}

class WPSC_Purchase_Log_Customer_HTML_Notification extends WPSC_Purchase_Log_Customer_Notification
{
	public function get_raw_message() {
		return apply_filters( 'wpsc_purchase_log_customer_html_notification_raw_message', get_option( 'wpsc_email_receipt' ), $this->args['purchase_log_id'] );
	}
}

class WPSC_Purchase_Log_Customer_Pending_HTML_Notification extends WPSC_Purchase_Log_Customer_HTML_Notification
{
	public function get_raw_message() {
		$message = __( 'Thank you, your purchase is pending, you will be sent an email once the order clears.', 'wpsc' ) . "\n\r";
		$message .= parent::get_raw_message();

		return apply_filters( 'wpsc_purchase_log_customer_pending_html_notification_raw_message', $message, $this->args['purchase_log_id'] );
	}
}