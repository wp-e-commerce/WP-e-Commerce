<?php

require_once( WPSC_TE_V2_CLASSES_PATH . '/table.php' );

class WPSC_Orders_Table extends WPSC_Table {
	private static $instance;

	public static function get_instance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new WPSC_Orders_Table();
		}

		return self::$instance;
	}

	public $per_page    = 10;
	public $offset      = 0;
	public $total_items = 0;
	public $status      = 0;

	public function fetch_items() {
		global $wpdb;

		$where = 'user_ID = %d';
		$vars = array( get_current_user_id() );
		if ( $this->status !== 0 ) {
			$where .= ' AND processed = %d';
			$vars[] = $this->status;
		}

		$sql = $wpdb->prepare( "
			SELECT SQL_CALC_FOUND_ROWS
				*
			FROM " . WPSC_TABLE_PURCHASE_LOGS . "
			WHERE {$where}
			ORDER BY id
			DESC
			LIMIT {$this->offset}, {$this->per_page}
		", $vars );

		$this->items       = $wpdb->get_results( $sql );
		$this->total_items = $wpdb->get_var( "SELECT FOUND_ROWS()" );
	}

	public function __construct() {
		parent::__construct();

		$this->columns = array(
			'id'          => __( 'Order Number', 'wp-e-commerce' ),
			'date'        => __( 'Date', 'wp-e-commerce' ),
			'status'      => __( 'Status', 'wp-e-commerce' ),
			'tracking_id' => __( 'Tracking ID', 'wp-e-commerce' ),
			'total'       => __( 'Total', 'wp-e-commerce' ),
		);
	}

	private function item_url( $item ) {
		return wpsc_get_customer_account_url( 'orders/' . $item->id );
	}

	protected function column_id( $item ) {
		?>
		<a href="<?php echo esc_url( $this->item_url( $item ) ); ?>"><?php echo esc_html( $item->id ); ?></a>
		<?php
	}

	protected function column_date( $item ) {
		$format    = _x( 'Y/m/d g:i:s A', 'orders table column date format', 'wp-e-commerce' );
		$timestamp = (int) $item->date;
		$full_time = date( $format, $timestamp );
		$time_diff = time() - $timestamp;

		if ( $time_diff > 0 && $time_diff < 24 * 60 * 60 ) {
			$h_time = $h_time = sprintf( __( '%s ago', 'wp-e-commerce' ), human_time_diff( $timestamp ) );
		} else {
			$h_time = date( get_option( 'date_format', 'Y/m/d' ), $timestamp );
		}

		echo '<a href="' . $this->item_url( $item ) . '">';
		echo $h_time;
		echo '</a>';
	}

	protected function column_status( $item ) {
		global $wpsc_purchlog_statuses;

		$current_status = false;
		foreach ( $wpsc_purchlog_statuses as $status ) {
			if ( $status['order'] == $item->processed ) {
				$current_status = esc_html( $status['label'] );
				continue;
			}
		}
		echo esc_html( $current_status );
	}

	protected function column_tracking_id( $item ) {
		if ( empty( $item->track_id ) ) {
			echo __( 'n/a', 'wp-e-commerce' );
		} else {
			echo esc_html( $item->track_id );
		}
	}

	protected function column_total( $item ) {
		echo esc_html( wpsc_format_currency( $item->totalprice ) );
	}
}