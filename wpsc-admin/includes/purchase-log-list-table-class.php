<?php
 /* The WP_List_Table class isn't automatically available to plugins, so we need
 * to check if it's available and load it if necessary.
 */
require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
require_once( ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php' );

class WPSC_Purchase_Log_List_Table extends WP_List_Table
{
	private $search_box = true;
	private $bulk_actions = true;
	private $sortable = true;
	private $per_page = 20;

	public function __construct() {
		WP_List_Table::__construct( array(
			'plural' => 'purchase-logs',
		) );
	}

	public function disable_sortable() {
		$this->sortable = false;
	}

	public function disable_search_box() {
		$this->search_box = false;
	}

	public function disable_bulk_actions() {
		$this->bulk_actions = false;
	}

	public function set_per_page( $per_page ) {
		$this->per_page = (int) $per_page;
	}

	public function prepare_items() {
		global $wpdb;

		$page = $this->get_pagenum();
		$offset = ( $page - 1 ) * $this->per_page;

		$checkout_fields_sql = "
			SELECT id, unique_name FROM " . WPSC_TABLE_CHECKOUT_FORMS . " WHERE unique_name IN ('billingfirstname', 'billinglastname', 'billingemail')
		";
		$checkout_fields = $wpdb->get_results( $checkout_fields_sql );

		$joins = array();
		$where = array( '1 = 1' );

		if ( isset( $_REQUEST['post'] ) )
			$where[] = 'p.id IN (' . implode( ', ', $_REQUEST['post'] ) . ')';

		$i = 1;
		$selects = array( 'p.id', 'p.totalprice AS amount', 'p.processed AS status', 'p.track_id', 'p.date' );
		$selects[] = '
			(
				SELECT COUNT(*) FROM ' . WPSC_TABLE_CART_CONTENTS . ' AS c
				WHERE c.purchaseid = p.id
			) AS item_count';

		$search_terms = empty( $_REQUEST['s'] ) ? array() : explode( ' ', $_REQUEST['s'] );
		$search_sql = array();
		foreach ( $checkout_fields as $field ) {
			$table_as = 's' . $i;
			$select_as = str_replace('billing', '', $field->unique_name );
			$selects[] = $table_as . '.value AS ' . $select_as;
			$joins[] = $wpdb->prepare( "INNER JOIN " . WPSC_TABLE_SUBMITED_FORM_DATA . " AS {$table_as} ON {$table_as}.log_id = p.id AND {$table_as}.form_id = %d", $field->id );

			// build search term queries for first name, last name, email
			foreach ( $search_terms as $term ) {
				$escaped_term = esc_sql( like_escape( $term ) );
				if ( ! array_key_exists( $term, $search_sql ) )
					$search_sql[$term] = array();

				$search_sql[$term][] = $table_as . ".value LIKE '%" . $escaped_term . "%'";
			}

			$i++;
		}

		// combine query phrases into a single query string
		foreach ( $search_terms as $term ) {
			$search_sql[$term][] = "p.track_id = '" . esc_sql( $term ) . "'";
			if ( is_numeric( $term ) )
				$search_sql[$term][] = 'p.id = ' . esc_sql( $term );
			$search_sql[$term] = '(' . implode( ' OR ', $search_sql[$term] ) . ')';
		}
		$search_sql = implode( ' AND ', array_values( $search_sql ) );

		if ( $search_sql ) {
			$where[] = $search_sql;
		}

		$selects = implode( ', ', $selects );
		$joins = implode( ' ', $joins );
		$where = implode( ' AND ', $where );
		$limit = ( $this->per_page !== 0 ) ? "LIMIT {$offset}, {$this->per_page}" : '';

		$orderby = empty( $_REQUEST['orderby'] ) ? 'p.id' : 'p.' . $_REQUEST['orderby'];
		$order = empty( $_REQUEST['order'] ) ? 'DESC' : $_REQUEST['order'];

		$orderby = esc_sql( $orderby );
		$order = esc_sql( $order );

		$submitted_data_log = WPSC_TABLE_SUBMITED_FORM_DATA;
		$purchase_log_sql = "
			SELECT SQL_CALC_FOUND_ROWS {$selects}
			FROM " . WPSC_TABLE_PURCHASE_LOGS . " AS p
			{$joins}
			WHERE {$where}
			ORDER BY {$orderby} {$order}
			{$limit}
		";
		$this->items = $wpdb->get_results( $purchase_log_sql );

		if ( $this->per_page ) {
			$total_items = $wpdb->get_var( "SELECT FOUND_ROWS()" );

			$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page'    => $this->per_page,
			) );
		}
	}

	public function get_columns() {
		return array(
			'cb'       => '<input type="checkbox" />',
			'id'       => __( 'Order ID', 'wpsc' ),
			'customer' => __( 'Customer', 'wpsc' ),
			'amount'   => __( 'Amount', 'wpsc' ),
			'status'   => _x( 'Status', 'sales log list table column', 'wpsc' ),
			'date'     => __( 'Date', 'wpsc' ),
		);
	}

	public function get_sortable_columns() {
		if ( ! $this->sortable )
			return array();

		return array(
			'date'   => 'id',
			'status' => 'processed',
			'amount' => 'totalprice',
		);
	}

	public function column_cb( $item ){
		$checked = isset( $_REQUEST['post'] ) ? checked( in_array( $item->id, $_REQUEST['post'] ), true, false ) : '';
		return sprintf(
			'<input type="checkbox" ' . $checked . ' name="%1$s[]" value="%2$s" />',
			/*$1%s*/ 'post',
			/*$2%s*/ $item->id
		);
	}

	private function item_url( $item ) {
		$location = remove_query_arg( array(
			'paged',
			'order',
			'orderby',
			's',
		) );
		$location = add_query_arg( array(
			'c'  => 'item_details',
			'id' => $item->id,
		), $location );
		return $location;
	}

	public function column_customer( $item ) {
		?>
		<strong>
			<a class="row-title" href="<?php echo esc_attr( $this->item_url( $item ) ); ?>" title="<?php esc_attr_e( 'View order details', 'wpsc' ) ?>"><?php echo esc_html( $item->firstname . ' ' . $item->lastname ); ?></a>
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
		echo '<a href="' . esc_attr( $this->item_url( $item ) ) . '" title="' . esc_attr__( 'View order details', 'wpsc' ) . '">';
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
		if ( ! $this->bulk_actions )
			return array();

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

	public function search_box( $text, $input_id ) {
		if ( ! $this->search_box )
			return '';

		parent::search_box( $text, $input_id );
	}
}

class WPSC_Purchase_Log_Page
{
	private $list_table;
	private $output;

	public function __construct() {
		$controller = 'default';
		$controller_method = 'controller_default';

		if ( isset( $_REQUEST['c'] ) && method_exists( $this, 'controller_' . $_REQUEST['c'] ) ) {
			$controller = $_REQUEST['c'];
			$controller_method = 'controller_' . $controller;
		}

		$this->$controller_method();
	}

	public function controller_item_details() {
		if ( ! isset( $_REQUEST['id'] ) )
			die( 'Invalid sales log ID' );

		global $purchlogitem;

		$this->log_id = (int) $_REQUEST['id'];

		// TODO: seriously get rid of all these badly coded purchaselogs.class.php functions in 4.0
		$purchlogitem = new wpsc_purchaselogs_items( $this->log_id );

		$columns = array(
			'title'    => __( 'Name','wpsc' ),
			'sku'      => __( 'SKU','wpsc' ),
			'quantity' => __( 'Quantity','wpsc' ),
			'price'    => __( 'Price','wpsc' ),
			'shipping' => __( 'Item Shipping','wpsc'),
		);

		if ( wpec_display_product_tax() ) {
			$columns['tax'] = __( 'Item Tax', 'wpsc' );
		}

		$columns['total'] = __( 'Item Total','wpsc' );

		register_column_headers( 'wpsc_purchase_log_item_details', $columns );

		add_action( 'wpsc_display_purchase_logs_page', array( $this, 'display_purchase_log' ) );
	}

	public function controller_packing_slip() {
		if ( ! isset( $_REQUEST['id'] ) )
			die( 'Invalid sales log ID' );

		global $purchlogitem;

		$this->log_id = (int) $_REQUEST['id'];

		$purchlogitem = new wpsc_purchaselogs_items( $this->log_id );

		$columns = array(
			'title'    => __( 'Item Name','wpsc' ),
			'sku'      => __( 'SKU','wpsc' ),
			'quantity' => __( 'Quantity','wpsc' ),
			'price'    => __( 'Price','wpsc' ),
			'shipping' => __( 'Item Shipping','wpsc'),
		);

		if ( wpec_display_product_tax() ) {
			$columns['tax'] = __( 'Item Tax', 'wpsc' );
		}

		$columns['total'] = __( 'Item Total','wpsc' );

		$cols = count( $columns ) - 2;

		register_column_headers( 'wpsc_purchase_log_item_details', $columns );

		include( 'purchase-logs-page/packing-slip.php' );
		exit;
	}

	public function controller_default() {
		//Create an instance of our package class...
		$this->list_table = new WPSC_Purchase_Log_List_Table();
		$this->process_bulk_action();
		$this->list_table->prepare_items();
		add_action( 'wpsc_display_purchase_logs_page', array( $this, 'display_list_table' ) );
	}

	public function display_purchase_log() {
		if ( wpec_display_product_tax() )
			$cols = 5;
		else
			$cols = 4;
		include( 'purchase-logs-page/item-details.php' );
	}

	public function process_bulk_action() {
		global $wpdb;
		$current_action = $this->list_table->current_action();

		if ( ! $current_action ) {
			unset( $_REQUEST['post'] );
			return;
		}

		if ( $current_action == 'delete' ) {

			// delete action
			if ( empty( $_REQUEST['confirm'] ) ) {
				$this->list_table->disable_search_box();
				$this->list_table->disable_bulk_actions();
				$this->list_table->disable_sortable();
				$this->list_table->set_per_page(0);
				add_action( 'wpsc_purchase_logs_list_table_before', array( $this, 'action_list_table_before' ) );
				return;
			} else {
				if ( empty( $_REQUEST['post'] ) )
					return;

				$ids = array_map( 'intval', $_REQUEST['post'] );
				$in = 'IN (' . implode( ', ', $ids ) . ")";
				$wpdb->query( "DELETE FROM " . WPSC_TABLE_PURCHASE_LOGS . " WHERE id {$in}" );
				$wpdb->query( "DELETE FROM " . WPSC_TABLE_CLAIMED_STOCK . " WHERE cart_id {$in}" );
				$wpdb->query( "DELETE FROM " . WPSC_TABLE_CART_CONTENTS . " WHERE purchaseid {$in}" );
				$wpdb->query( "DELETE FROM " . WPSC_TABLE_SUBMITED_FORM_DATA . " WHERE log_id {$in}" );
				unset( $_REQUEST['post'] );
				return;
			}
		}

		// change status actions
		if ( is_numeric( $current_action ) && $current_action < 7 && ! empty( $_REQUEST['post'] ) ) {
			foreach ( $_REQUEST['post'] as $id ) {
				wpsc_purchlog_edit_status( $id, $current_action );
			}

			unset( $_REQUEST['post'] );
		}
	}

	public function action_list_table_before() {
		include( 'purchase-logs-page/bulk-delete-confirm.php' );
	}

	public function display_list_table() {
		if ( ! empty( $this->output ) ) {
			echo $this->output;
			return;
		}

		include( 'purchase-logs-page/list-table.php' );
	}
}