<?php
 /* The WP_List_Table class isn't automatically available to plugins, so we need
 * to check if it's available and load it if necessary.
 */
require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
require_once( ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php' );

class WPSC_Purchase_Log_List_Table extends WP_List_Table
{
	public function __construct() {
		WP_List_Table::__construct( array(
			'plural' => 'purchase-logs',
		) );
	}

	public function prepare_items() {
		global $wpdb;

		$per_page = 20; // it's currently hardcoded
		$page = $this->get_pagenum();
		$offset = ( $page - 1 ) * $per_page;

		$checkout_fields_sql = "
			SELECT id, unique_name FROM " . WPSC_TABLE_CHECKOUT_FORMS . " WHERE unique_name IN ('billingfirstname', 'billinglastname', 'billingemail')
		";
		$checkout_fields = $wpdb->get_results( $checkout_fields_sql );

		$joins = array();
		$i = 1;
		$selects = array( 'p.id', 'p.totalprice AS amount', 'p.processed AS status', 'p.track_id', 'p.date' );
		$selects[] = '
			(
				SELECT COUNT(*) FROM ' . WPSC_TABLE_CART_CONTENTS . ' AS c
				WHERE c.purchaseid = p.id
			) AS item_count';
		foreach ( $checkout_fields as $field ) {
			$as = 's' . $i;
			$selects[] = $as . '.value AS ' . str_replace('billing', '', $field->unique_name );
			$joins[] = $wpdb->prepare( "INNER JOIN " . WPSC_TABLE_SUBMITED_FORM_DATA . " AS {$as} ON {$as}.log_id = p.id AND {$as}.form_id = %d", $field->id );
			$i++;
		}

		$selects = implode( ', ', $selects );
		$joins = implode( ' ', $joins );

		$submitted_data_log = WPSC_TABLE_SUBMITED_FORM_DATA;
		$purchase_log_sql = "
			SELECT {$selects}
			FROM " . WPSC_TABLE_PURCHASE_LOGS . " AS p
			{$joins}
			ORDER BY p.id DESC
			LIMIT {$offset}, {$per_page}
		";
		$this->items = $wpdb->get_results( $purchase_log_sql );

		$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM " . WPSC_TABLE_PURCHASE_LOGS );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
		) );
	}

	public function get_columns() {
		return array(
			'cb'       => '<input type="checkbox" />',
			'customer' => __( 'Customer', 'wpsc' ),
			'amount'   => __( 'Amount', 'wpsc' ),
			'status'   => _x( 'Status', 'sales log list table column', 'wpsc' ),
			'date'     => __( 'Date', 'wpsc' ),
		);
	}

	public function get_sortable_columns() {
		return array(
			'date'   => 'id',
			'status' => 'status',
		);
	}

	public function column_cb( $item ){
	    return sprintf(
	        '<input type="checkbox" name="%1$s[]" value="%2$s" />',
	        /*$1%s*/ 'post',
	        /*$2%s*/ $item->id
	    );
	}

	public function column_customer( $item ) {
		?>
		<strong>
			<a class="row-title" href="#" title="<?php esc_attr_e( 'View order details', 'wpsc' ) ?>"><?php echo esc_html( $item->firstname . ' ' . $item->lastname ); ?></a>
		</strong><br />
		<small><?php echo make_clickable( $item->email ); ?></small>
		<?php
	}

	public function column_date( $item ) {
		$format = __( 'Y/m/d g:i:s A' );
		$timestamp = (int) $item->date;
		$full_time = date( $format, $timestamp );
		$time_diff = time() - $timestamp;
		if ( $time_diff > 0 && $time_diff < 24 * 60 * 60 )
			$h_time = $h_time = sprintf( __( '%s ago' ), human_time_diff( $timestamp ) );
		else
			$h_time = date( __( 'Y/m/d' ), $timestamp );

		echo '<abbr title="' . $full_time . '">' . $h_time . '</abbr>';
	}

	public function column_amount( $item ) {
		echo '<a href="#" title="' . esc_attr__( 'View order details', 'wpsc' ) . '">';
		echo wpsc_currency_display( $item->amount ) . "<br />";
		echo '<small>' . sprintf( _n( '1 item', '%s items', $item->item_count, 'wpsc' ), number_format_i18n( $item->item_count ) ) . '</small>';
		echo '</a>';
	}

	public function column_default( $item, $column_name ) {
		return esc_html( $item->$column_name );
	}

	public function column_status( $item ) {
		global $wpsc_purchlog_statuses;
		foreach ( $wpsc_purchlog_statuses as $status ) {
			if ( $status['order'] == $item->status )
				return esc_html( $status['label'] );
		}

		return $item->status;
	}

	public function get_bulk_actions() {
	    $actions = array(
			'delete' => _x( 'Delete', 'bulk action', 'wpsc' ),
			'1'      => __( 'Incomplete Sale', 'wpsc' ),
			'2'      => __( 'Order Recieved', 'wpsc' ),
			'3'      => __( 'Accepted Payment', 'wpsc' ),
			'4'      => __( 'Job dispatched', 'wpsc' ),
			'5'      => __( 'Closed Order', 'wpsc' ),
			'6'      => __( 'Payment Declined', 'wpsc' ),
	    );
	    return $actions;
	}

	public function process_bulk_action() {
		global $wpdb;

		if( 'view'===$this->current_action() ) {
			exit('This will be the single view page that i need to think about creating...');
		}

		//Detect when a bulk action is being triggered...
		if( 'delete'===$this->current_action() ) {
			/* this needs some js "are you sure you want to delete this" */

			if ( isset($_POST['post']) )
				$post_ids = array_map( 'intval', $_POST['post'] ); // pull out the items that need updating

			//if there are no post ids then the id will
			//be in the url from the hover link
			//if( empty($post_ids) )
			if ( isset( $_GET['post'] ) && $_GET['action'] === 'delete' )
			$post_ids = array(1 => $_GET['post']);

			$wpdb->query($wpdb->prepare('DELETE FROM ' . WPSC_TABLE_PURCHASE_LOGS . ' WHERE `id` IN(' . implode(',' , $post_ids ).')'));
		}

		/*
		if numeric then we know we are updating the order status the
		current_action will be the status number to update
		*/
		if( is_numeric($this->current_action())  ) {
			$post_ids = array_map( 'intval', $_POST['post'] ); // pull out the items that need updating
			$wpdb->query($wpdb->prepare('UPDATE ' . WPSC_TABLE_PURCHASE_LOGS . ' SET `processed` = ' . $this->current_action() . ' WHERE `id` IN(' . implode(',' , $post_ids ).')'));

		}

	}
}

class WPSC_Purchase_Log_Page
{
	private $list_table;

	public function __construct() {
		add_action( 'wpsc_display_purchase_logs_page', array( $this, 'display' ) );

		//Create an instance of our package class...
	    $this->list_table = new WPSC_Purchase_Log_List_Table();

	    //Fetch, prepare, sort, and filter our data...
	    $this->list_table->prepare_items();
	}

	public function display() {
	    ?>
	    <div class="wrap">

	        <div id="icon-users" class="icon32"><br/></div>
	        <h2><?php esc_html_e( 'Sales Log' ); ?></h2>

	        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	        <form id="purchase-logs-filter" method="post" action="">
	        	<?php $this->list_table->search_box( 'Search Sales Logs', 'post' ); ?>
	            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
	            <!-- Now we can render the completed list table -->

	            <?php $this->list_table->display() ?>

	        </form>

	    </div>
	    <?php
	}
}