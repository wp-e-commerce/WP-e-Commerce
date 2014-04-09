<?php
abstract class WPSC_Purchase_Log_Notification {
	protected $address;
	protected $args = array();
	protected $raw_message = '';
	protected $plaintext_message = '';
	protected $html_message = '';
	protected $title;
	protected $purchase_log;
	protected $plaintext_product_list = '';
	protected $html_product_list = '';
	protected $plaintext_args = '';
	protected $html_args = '';

	public function __construct( $purchase_log ) {
		$this->purchase_log   = $purchase_log;
		$this->address        = $this->get_address();
		$this->plaintext_args = $this->get_plaintext_args();
		$this->html_args      = $this->get_html_args();
		$this->raw_message    = $this->get_raw_message();
		$this->title          = $this->get_subject();

		$this->plaintext_message = $this->process_plaintext_args();
		$this->html_message = $this->process_html_args();
	}

	protected function get_common_args() {
		$data = $this->purchase_log->get_gateway_data();
		$tax      = wpsc_currency_display( $data['tax'     ], array( 'display_as_html' => false ) );
		$shipping = wpsc_currency_display( $data['shipping'], array( 'display_as_html' => false ) );
		$total    = wpsc_currency_display( $data['amount'  ], array( 'display_as_html' => false ) );
		$discount = wpsc_currency_display( $data['discount'], array( 'display_as_html' => false ) );
		$subtotal = wpsc_currency_display( $data['subtotal'], array( 'display_as_html' => false ) );

		$args = array(
			// Legacy tags
			// These tags are dumb because they force the string to go with the amount, giving no
			// control to the user. Unfortunately we still have to support those for the next decade.
			'purchase_id'     => sprintf( __( "Purchase # %s"     , 'wpsc' ), $this->purchase_log->get( 'id' ) ) . "\r\n",
			'total_tax'       => sprintf( __( 'Total Tax: %s'     , 'wpsc' ), $tax               ) . "\r\n",
			'total_shipping'  => sprintf( __( 'Total Shipping: %s', 'wpsc' ), $shipping          ) . "\r\n",
			'total_price'     => sprintf( __( 'Total: %s'         , 'wpsc' ), $total             ) . "\r\n",
			'shop_name'       => get_option( 'blogname' ),
			'find_us'         => $this->purchase_log->get( 'find_us' ),
			'discount'        => sprintf( __( 'Discount Amount: %s (%s)', 'wpsc' ), $discount, $this->purchase_log->get( 'discount_data' ) ) . "\r\n",

			// New tags
			'coupon_code'     => $this->purchase_log->get( 'discount_data'   ),
			'transaction_id'  => $this->purchase_log->get( 'transactid'      ),
			'purchase_log_id' => $this->purchase_log->get( 'id'              ),
			'payment_method'  => $this->purchase_log->get( 'gateway'         ),
			'shipping_method' => $this->purchase_log->get( 'shipping_method' ),
			'shipping_option' => $this->purchase_log->get( 'shipping_option' ),
			'discount_amount' => $discount,
			'tax'             => $tax,
			'shipping'        => $shipping,
			'total'           => $total,
			'subtotal'        => $subtotal,
		);

		return apply_filters( 'wpsc_purchase_log_notification_common_args', $args, $this );
	}

	private function get_table_args() {
		$log_id   = $this->purchase_log->get( 'id' );
		$log_data = $this->purchase_log->get_data();
		$rows     = array();

		$headings = array(
			_x( 'Name'       , 'purchase log notification table heading', 'wpsc' ) => 'left',
			_x( 'Price'      , 'purchase log notification table heading', 'wpsc' ) => 'right',
			_x( 'Quantity'   , 'purchase log notification table heading', 'wpsc' ) => 'right',
			_x( 'Item Total' , 'purchase log notification table heading', 'wpsc' ) => 'right',
		);

		$has_additional_details = false;
		$additional_details     = array();

		foreach ( $this->purchase_log->get_cart_contents() as $item ) {
			$cart_item_array = array(
				'purchase_id'  => $log_id,
				'cart_item'    => (array) $item,
				'purchase_log' => $log_data,
			);

			// legacy code, which Gary honestly doesn't fully understand because it just doesn't make sense
			// prior to 3.8.9, these actions are called on each product item. Don't really know what they do.
			do_action( 'wpsc_transaction_result_cart_item', $cart_item_array );
			do_action( 'wpsc_confirm_checkout', $log_id );

			// then there's also this annoying apply_filters call, which is apparently not the best example
			// of how to use it, but we have to preserve them anyways
			$additional_content = apply_filters( 'wpsc_transaction_result_content', $cart_item_array );

			if ( ! is_string( $additional_content ) ) {
				$additional_content = '';
			} else {
				$has_additional_details = true;
			}

			$additional_details[] = $additional_content;

			$item_total = $item->quantity * $item->price;
			$item_total = wpsc_currency_display( $item_total , array( 'display_as_html' => false ) );
			$item_price = wpsc_currency_display( $item->price, array( 'display_as_html' => false ) );
			$item_name  = apply_filters( 'the_title', $item->name );
			$rows[]     = array( $item->name, $item_price, $item->quantity, $item_total );
		}

		// Preserve the 'wpsc_transaction_result_content' filter for backward compat
		if ( $has_additional_details ) {
			$headings[] = __( 'Additional Details', 'wpsc' );
			foreach ( $rows as $index => $row ) {
				$rows[] = $additional_details[ $index ];
			}
		}

		$table_args = array( 'headings' => $headings, 'rows' => $rows );
		return apply_filters( 'wpsc_purchase_log_notification_product_table_args', $table_args, $this );
	}

	private function create_plaintext_product_list() {
		$table_args = $this->get_table_args();
		$output = wpsc_get_plaintext_table( $table_args['headings'], $table_args['rows'] );

		foreach ( $this->purchase_log->get_cart_contents() as $cart_item ) {
			if ( empty( $cart_item->custom_message ) )
				continue;

			$custom_message_string = apply_filters( 'wpsc_email_product_list_plaintext_custom_message', __( 'Customization for %s', 'wpsc' ) );
			$output .= "\r\n" . '=== ' . sprintf( $custom_message_string, $cart_item->name ) . ' ===' . "\r\n";
			$output .= $cart_item->custom_message;
			$output .= "\r\n";
		}

		$links = wpsc_get_downloadable_links( $this->purchase_log );
		if ( empty( $links ) )
			return $output;

		$output .= '==' . __( 'Downloadable items', 'wpsc' ) . "==\r\n";
		foreach ( $links as $item_name => $item_links ) {
			$output .= $item_name . "\r\n";
			foreach ( $item_links as $link ) {
				$output .= '  - ' . $link['name'] . "\r\n" . '    ' . $link['url'] . "\r\n";
			}
			$output .= "\r\n";
		}
		$output .= "\r\n";

		return $output;
	}

	private function create_html_product_list() {
		$table_args = $this->get_table_args();

		$output = wpsc_get_purchase_log_html_table( $table_args['headings'], $table_args['rows'] );

		foreach ( $this->purchase_log->get_cart_contents() as $cart_item ) {
			if ( empty( $cart_item->custom_message ) )
				continue;

			$custom_message_string = apply_filters( 'wpsc_email_product_list_html_custom_message', __( 'Customization for %s', 'wpsc' ) );
			$output .= '<hr />';
			$output .= '<p><strong>' . sprintf( $custom_message_string, esc_html( $cart_item->name ) ) . '</strong></p>';
			$output .= wpautop( esc_html( $cart_item->custom_message ) );
		}

		$links = wpsc_get_downloadable_links( $this->purchase_log );
		if ( empty( $links ) )
			return $output;

		$output .= '<hr /><p><strong>' . esc_html__( 'Downloadable items', 'wpsc' ) . '</strong></p>';
		foreach ( $links as $item_name => $item_links ) {
			$output .= '<p>';
			$output .= '<em>' . esc_html( $item_name ) . '</em><br />';
			foreach ( $item_links as $link ) {
				$output .= '<a href="' . esc_attr( $link['url'] ) . '">' . esc_html( $link['name'] ) . '</a><br />';
			}
			$output .= '</p>';
		}

		return $output;
	}

	private function get_plaintext_args() {
		$this->plaintext_product_list = $this->create_plaintext_product_list();
		$this->plaintext_args = array(
			'product_list' => $this->plaintext_product_list,
		);
		$this->plaintext_args = apply_filters( 'wpsc_purchase_log_notification_plaintext_args', $this->plaintext_args, $this );
		return array_merge( $this->get_common_args(), $this->plaintext_args );
	}

	private function get_html_args() {
		$common_args = $this->get_common_args();
		$common_args = array_map( 'esc_html', $common_args );
		$this->html_product_list = $this->create_html_product_list();
		$this->html_args = array(
			'product_list' => $this->html_product_list,
		);
		$this->html_args = apply_filters( 'wpsc_purchase_log_notification_html_args', $this->html_args, $this );
		return array_merge( $common_args, $this->html_args );
	}

	protected function maybe_add_discount( $message ) {
		// little hack to make sure discount is displayed even when the tag is not in the option value
		// which is a dumb behavior in previous versions
		if ( $this->purchase_log->get( 'discount_data' ) && strpos( $message, '%discount%' ) === false ) {
			$shipping_pos = strpos( $message, '%total_shipping%' );
			if ( $shipping_pos !== false )
				$message = str_replace( '%total_shipping%', '%total_shipping%%discount%', $message );
		}
		return $message;
	}

	public function get_address() {
		return '';
	}

	public function get_purchase_log() {
		return $this->purchase_log;
	}

	public function get_html_message() {
		return $this->html_message;
	}

	public function get_plaintext_message() {
		return $this->plaintext_message;
	}

	public function get_subject() {
		return '';
	}

	public function get_raw_message() {
		return '';
	}

	private function process_args( $args ) {
		$tokens = array_keys( $args );
		$values = array_values( $args );

		foreach ( $tokens as &$token ) {
			$token = "%{$token}%";
		}

		return str_replace( $tokens, $values, $this->raw_message );
	}

	protected function process_plaintext_args() {
		return $this->process_args( $this->plaintext_args );
	}

	protected function process_html_args() {
		$html = $this->process_args( $this->html_args );
		$html = wpautop( $html );
		return $html;
	}

	public function get_email_headers() {
		$from_email = apply_filters( 'wpsc_purchase_log_notification_from_email', get_option( 'return_email' ), $this );
		$from_name  = apply_filters( 'wpsc_purchase_log_notification_from_name', get_option( 'return_name' ), $this );

		// don't worry, wp_mail() will automatically assign default values if these options
		// are not set and empty
		$headers = 'From: "' . $from_name . '" <' . $from_email . '>';
		return apply_filters( 'wpsc_purchase_log_notification_headers', $headers, $this );
	}

	public function send() {
		if ( empty( $this->address ) )
			return;

		$headers = $this->get_email_headers();
		add_action( 'phpmailer_init', array( $this, '_action_phpmailer_init_multipart' ), 10, 1 );
		$email_sent = wp_mail( $this->address, $this->title, $this->html_message, $headers );
		remove_action( 'phpmailer_init', array( $this, '_action_phpmailer_init_multipart' ), 10, 1 );

		return $email_sent;
	}

	public function _action_phpmailer_init_multipart( $phpmailer ) {
		$phpmailer->AltBody = $this->plaintext_message;
	}
}

class WPSC_Purchase_Log_Customer_Notification extends WPSC_Purchase_Log_Notification {
	public function get_raw_message() {
		$raw_message = '';

		if ( ! $this->purchase_log->is_transaction_completed() )
			$raw_message = __( 'Thank you, your purchase is pending. You will be sent an email once the order clears.', 'wpsc' ) . "\n\r";

		$raw_message .= get_option( 'wpsc_email_receipt' );
		$raw_message = $this->maybe_add_discount( $raw_message );
		// pre-3.8.9 filter hook
		$raw_message = apply_filters( 'wpsc_transaction_result_message', $raw_message );
		return apply_filters( 'wpsc_purchase_log_customer_notification_raw_message', $raw_message, $this );
	}

	public function get_subject() {
		$subject = __( 'Purchase Receipt', 'wpsc' );

		if ( $this->purchase_log->get( 'processed' ) == WPSC_Purchase_Log::ORDER_RECEIVED )
			$subject = __( 'Order Pending: Payment Required', 'wpsc' );

		return apply_filters( 'wpsc_purchase_log_customer_notification_subject', $subject, $this );
	}

	public function get_address() {
		return apply_filters( 'wpsc_purchase_log_customer_notification_address', wpsc_get_buyers_email( $this->purchase_log->get( 'id' ) ), $this );
	}

	protected function process_plaintext_args() {
		// preserve pre-3.8.9 filter
		return apply_filters( 'wpsc_email_message', parent::process_plaintext_args(), $this->plaintext_args['purchase_id'], $this->plaintext_product_list, $this->plaintext_args['total_tax'], $this->plaintext_args['total_shipping'], $this->plaintext_args['total_price'] );
	}

	protected function process_html_args() {
		// preserve pre-3.8.9 filter
		return apply_filters( 'wpsc_email_message', parent::process_html_args(), $this->html_args['purchase_id'], $this->html_product_list, $this->html_args['total_tax'], $this->html_args['total_shipping'], $this->html_args['total_price'] );
	}
}

class WPSC_Purchase_Log_Admin_Notification extends WPSC_Purchase_Log_Notification {
	public function get_address() {
		return apply_filters( 'wpsc_purchase_log_admin_notification_address', get_option( 'purch_log_email' ), $this );
	}

	public function get_raw_message() {
		global $wpdb;

		$form_data = new WPSC_Checkout_Form_Data( $this->purchase_log->get( 'id' ) );
		$raw_data = $form_data->get_raw_data();

		$args = $this->get_common_args();

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

		$message = '';

		// Transaction details
		$message = '<strong>' . __( 'Transaction Details', 'wpsc' ) . "</strong>\r\n";
		$message .= __( 'Sale Log ID', 'wpsc' ) . ': %purchase_id%' . "\r\n";
		if ( ! empty( $args['transaction_id'] ) )
			$message .= __( 'Transaction ID', 'wpsc' ) . ': %transaction_id%' . "\r\n";

		// Discount
		if ( ! empty( $args['coupon_code'] ) ) {
			$message .= __( 'Coupon Code', 'wpsc' ) . ': %coupon_code%' . "\r\n";
			$message .= __( 'Discount Value', 'wpsc' ) . ': %discount%' . "\r\n";
		}

		// Subtotal, tax, shipping, total
		$message .= __( 'Subtotal' ,'wpsc' ) . ': %subtotal%' . "\r\n";
		$message .= __( 'Tax', 'wpsc' ) . ': %tax%' . "\r\n";
		$message .= __( 'Shipping', 'wpsc' ) . ': %shipping%' . "\r\n";
		$message .= __( 'Total', 'wpsc' ) . ': %total%' . "\r\n";
		$message .= __( 'Payment Method', 'wpsc' ) . ': %payment_method%' . "\r\n";

		if ( ! get_option( 'do_not_use_shipping' ) ) {
			$message .= __( 'Shipping Method', 'wpsc' ) . ': %shipping_method%' . "\r\n";
			$message .= __( 'Shipping Option', 'wpsc' ) . ': %shipping_option%' . "\r\n";
		}

		$message .= "\r\n";

		// Items
		$message .= '<strong>' . __( 'Items', 'wpsc' ) . "</strong>\r\n";
		$message .= "%product_list%\r\n";

		// Checkout fields
		$message .= "\r\n";
		foreach ( $data as $section ) {
			if ( empty( $section['fields'] ) )
				continue;

			$message .= "<strong>{$section['title']}</strong>\r\n";
			foreach ( $section['fields'] as $field ) {
				if ( strpos( $field->unique_name, 'state' ) && is_numeric( $field->value ) ) {
					$field->value = wpsc_get_region( $field->value );
				}
				$message .= $field->name . ' : ' . $field->value . "\r\n";
			}
			$message .= "\r\n";
		}

		// preserve pre-3.8.9 hooks
		$message = apply_filters( 'wpsc_transaction_result_report', $message );
		return apply_filters( 'wpsc_purchase_log_admin_notification_raw_message', $message, $this );
	}

	protected function process_plaintext_args() {
		$plaintext_message = parent::process_plaintext_args();

		$plaintext_message = str_replace( '<strong>', '== ', $plaintext_message );
		$plaintext_message = str_replace( '</strong>', ' ==', $plaintext_message );
		return $plaintext_message;
	}

	public function get_subject() {
		return apply_filters( 'wpsc_purchase_log_admin_notification_subject', __( 'Transaction Report', 'wpsc' ), $this );
	}
}

class WPSC_Purchase_Log_Customer_HTML_Notification extends WPSC_Purchase_Log_Customer_Notification {
	public function get_raw_message() {
		$raw_message = apply_filters( 'wpsc_pre_transaction_results', '', $this );
		if ( ! $this->purchase_log->is_transaction_completed() )
			$raw_message .= __( 'Thank you, your purchase is pending. You will be sent an email once the order clears.', 'wpsc' ) . "\n\r";

		$raw_message .= get_option( 'wpsc_email_receipt' );
		$raw_message = $this->maybe_add_discount( $raw_message );

		// preserve pre-3.8.9 filter hooks
		$raw_message = apply_filters( 'wpsc_transaction_result_message_html', $raw_message );
		return apply_filters( 'wpsc_purchase_log_customer_html_notification_raw_message', $raw_message, $this );
	}
}
