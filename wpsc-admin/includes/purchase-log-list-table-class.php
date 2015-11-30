<?php
 /* The WP_List_Table class isn't automatically available to plugins, so we need
 * to check if it's available and load it if necessary.
 */
require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
require_once( ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php' );

class WPSC_Purchase_Log_List_Table extends WP_List_Table {
	private $search_box = true;
	private $bulk_actions = true;
	private $sortable = true;
	private $month_filter = true;
	private $views = true;
	private $status = 'all';
	private $per_page = 20;
	private $total_amount = 0;
	private $joins;
	private $where;
	private $where_no_filter;

	public function __construct( $args = array() ) {
		$args['plural'] = 'purchase-logs';

		$this->set_per_page( $this->set_purchase_logs_per_page_by_user() );

		parent::__construct( $args );

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			$_SERVER['REQUEST_URI'] = wp_get_referer();
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

	public function disable_month_filter() {
		$this->month_filter = false;
	}

	public function disable_views() {
		$this->views = false;
	}

	private function set_purchase_logs_per_page_by_user() {

		$per_page = get_user_meta( get_current_user_id(), 'wpsc_purchases_per_page', true );

		return empty( $per_page ) || $per_page < 1 ? 20 : $per_page;
	}

	// Override the default Purchase Logs Per Page
	public function set_per_page( $per_page ) {
		$this->per_page = (int) $per_page;
	}

	public function prepare_items() {
		global $wpdb;

		$page = $this->get_pagenum();
		$offset = ( $page - 1 ) * $this->per_page;

		$checkout_fields_sql = "
			SELECT id, unique_name FROM " . WPSC_TABLE_CHECKOUT_FORMS . " WHERE unique_name IN ('billingfirstname', 'billinglastname', 'billingemail') AND active='1' AND checkout_set='0'
		";
		$checkout_fields = $wpdb->get_results( $checkout_fields_sql );

		$joins = array();
		$where = array( '1 = 1' );

		if ( isset( $_REQUEST['post'] ) ) {
			$posts   = array_map( 'absint', $_REQUEST['post'] );
			$where[] = ' and (p.id IN (' . implode( ', ', $posts ) . '))';
		}

		$i = 1;
		$selects = array( 'p.id', 'p.totalprice AS amount', 'p.processed AS status', 'p.track_id', 'p.date' );
		$selects[] = '
			(
				SELECT SUM(quantity) FROM ' . WPSC_TABLE_CART_CONTENTS . ' AS c
				WHERE c.purchaseid = p.id
			) AS item_count';

		$search_terms = empty( $_REQUEST['s'] ) ? array() : explode( ' ', $_REQUEST['s'] );
		$search_sql   = array();

		foreach ( $checkout_fields as $field ) {
			$table_as  = 's' . $i;
			$select_as = str_replace('billing', '', $field->unique_name );
			$selects[] = $table_as . '.value AS ' . $select_as;
			$joins[]   = $wpdb->prepare( "LEFT OUTER JOIN " . WPSC_TABLE_SUBMITTED_FORM_DATA . " AS {$table_as} ON {$table_as}.log_id = p.id AND {$table_as}.form_id = %d", $field->id );

			// build search term queries for first name, last name, email
			foreach ( $search_terms as $term ) {

				if ( version_compare( $GLOBALS['wp_version'], '4.0', '>=' ) ) {
					$escaped_term = esc_sql( like_escape( $term ) );
				} else {
					$escaped_term = esc_sql( $wpdb->esc_like( $term ) );
				}

				if ( ! array_key_exists( $term, $search_sql ) ) {
					$search_sql[ $term ] = array();
				}

				$search_sql[ $term ][] = $table_as . ".value LIKE '%" . $escaped_term . "%'";
			}

			$i++;
		}

		// combine query phrases into a single query string
		foreach ( $search_terms as $term ) {
			$search_sql[ $term ][] = "p.track_id = '" . esc_sql( $term ) . "'";
			if ( is_numeric( $term ) )
				$search_sql[ $term ][] = 'p.id = ' . esc_sql( $term );
			$search_sql[ $term ] = '(' . implode( ' OR ', $search_sql[ $term ] ) . ')';
		}
		$search_sql = implode( ' AND ', array_values( $search_sql ) );

		if ( $search_sql ) {
			$where[] = " AND ({$search_sql})";
		}

		// filter by status
		if ( ! empty( $_REQUEST['status'] ) && $_REQUEST['status'] != 'all' ) {
			$this->status = absint( $_REQUEST['status'] );
			$where[] = ' AND (processed = ' . $this->status .')';
		}

		$this->where_no_filter = implode( ' ', $where );

		// filter by month
		if ( ! empty( $_REQUEST['m'] ) ) {

			// so we can tell WP_Date_Query we're legit
			add_filter( 'date_query_valid_columns', array( $this, 'set_date_column_to_date') );

			if ( strlen( $_REQUEST['m'] ) < 4 ) {
				$query_args = $this->assemble_predefined_periods_query( $_REQUEST['m'] );
			} else {
				$query_args = array(
					'year'     => (int) substr( $_REQUEST['m'], 0, 4),
					'monthnum' => (int) substr( $_REQUEST['m'], -2 ),
				);
			}

			$date_query = new WP_Date_Query( $query_args , $column = '__date__' );
			/* this is a subtle hack since the FROM_UNIXTIME doesn't survive WP_Date_Query
			 * so we use __date__ as a proxy
			 */
			$where[] = str_replace( '__date__', 'FROM_UNIXTIME(p.date)', $date_query->get_sql() );
		}

		$selects     = apply_filters( 'wpsc_manage_purchase_logs_selects', implode( ', ', $selects ) );
		$this->joins = apply_filters( 'wpsc_manage_purchase_logs_joins'  , implode( ' ', $joins ) );
		$this->where = apply_filters( 'wpsc_manage_purchase_logs_where'  , implode( ' ', $where ) );

		$limit = ( $this->per_page !== 0 ) ? "LIMIT {$offset}, {$this->per_page}" : '';

		$orderby = empty( $_REQUEST['orderby'] ) ? 'p.id' : 'p.' . $_REQUEST['orderby'];
		$order   = empty( $_REQUEST['order'] ) ? 'DESC' : $_REQUEST['order'];

		$orderby = esc_sql( apply_filters( 'wpsc_manage_purchase_logs_orderby', $orderby ) );
		$order   = esc_sql( $order );

		$purchase_log_sql   = apply_filters( 'wpsc_manage_purchase_logs_sql', "
			SELECT SQL_CALC_FOUND_ROWS {$selects}
			FROM " . WPSC_TABLE_PURCHASE_LOGS . " AS p
			{$this->joins}
			WHERE {$this->where}
			ORDER BY {$orderby} {$order}
			{$limit}
		" );

		$this->items = apply_filters( 'wpsc_manage_purchase_logs_items', $wpdb->get_results( $purchase_log_sql ) );
		if ( $this->per_page ) {
			$total_items = $wpdb->get_var( "SELECT FOUND_ROWS()" );

			$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page'    => $this->per_page,
			) );
		}

		$total_where = apply_filters( 'wpsc_manage_purchase_logs_total_where', $this->where );

		if ( $this->status == 'all' ) {
			$total_where .= ' AND p.processed IN (3, 4, 5) ';
		}

		$total_sql = "
			SELECT SUM(totalprice)
			FROM " . WPSC_TABLE_PURCHASE_LOGS . " AS p
			{$this->joins}
			WHERE {$total_where}
		";

		$this->total_amount = $wpdb->get_var( $total_sql );
	}

	/**
	 * Construct the date queries for pre-defined periods in the Sales Log.
	 *
	 * Supports pre-defined periods for the purchase log, including today, yesterday, this week,
	 * last week, this month, last month, last two months + month to date (this quarter),
	 * prior 3 months, this year, last year. You can insert your own custom periods by filtering
	 * either based on the $period_flag or just filter the final query setup.
	 *
	 * @since 4.0
	 *
	 * @param array $period_flag The period requested from $_REQUEST['m'].
	 *
	 * @return array formatted to pass to WP_Date_Query.
	 */

	private function assemble_predefined_periods_query( $period_flag ){
		// warning: period flag is unsanitized user input directly from $_REQUEST - only compare against actual values

		/**
		 *	date functions
		 */
		$now_string     = current_time( 'mysql' );
		$week_start_end = get_weekstartend( $now_string ); // returns array with start/end
		$blog_time_zone = get_option( 'timezone_string' );

		if ( empty( $blog_time_zone ) ) {
			$blog_time_zone = date_default_timezone_get();
		}

		$timezone = new DateTimeZone( $blog_time_zone );
		$now      = new DateTime( 'now',  $timezone );

		// Based on $_REQUEST['m']
		switch ( $period_flag ) {
			// Today
			case 1:
				$date_query = array(
					'year'     => $now->format( 'Y' ),
					'monthnum' => $now->format( 'n' ),
					'day'      => $now->format( 'd' )
				);
				break;

			// Yesterday
			case 2:
				$yesterday = new DateTime( date( 'Y-m-d', strtotime( 'yesterday' ) ), $timezone );
				$date_query = array(
					'year'     => $yesterday->format( 'Y' ),
					'monthnum' => $yesterday->format( 'n' ),
					'day'      => $yesterday->format( 'd' )
				);
				break;

			// This Week-to-date
			case 3:
				$start_of_this_week = new DateTime( date( 'Y-m-d 00:00:00', $week_start_end['start'] ), $timezone );
				$date_query = array( 'date_query' => array(
					'after' 	=> $start_of_this_week->format('Y-m-d 00:00:00'),
					'compare'	=> '>',
					'inclusive'	=> true
					));
				break;

			// Last Week
			case 4:
				$start_of_last_week = new DateTime( date('Y-m-d 00:00:00', $week_start_end['start'] - ( DAY_IN_SECONDS * 7 ) ), $timezone );
				$start = $start_of_last_week->format( 'Y-m-d 00:00:00' );
				$start_of_last_week->modify( '+7 days' );
				$date_query = array( 'date_query' => array(
					'after'	    => $start,
					'before'    => $start_of_last_week->format( 'Y-m-d 00:00:00' ),
					'inclusive'	=> false,
				));
				break;

			// This Month-to-Date (Same as choosing the explicit month on selector)
			case 5:
				$date_query = array(
					'year'     => $now->format('Y'),
					'monthnum' => $now->format('n'),
				);
				break;

			// Last Month (Same as choosing the explicit month on selector)
			case 6:
				$now->modify('-1 month');
				$date_query = array(
					'year'     => $now->format('Y'),
					'monthnum' => $now->format('n'),
				);
				break;

			// This Quarter (last three months inclusive)
			case 7:
				$date_query = array('date_query' => array(
					'after'	=> 'first day of -2 months' ),
					'compare'	=> '>',
					'inclusive'	=> true,
					);

				break;

			// Prior Three Months
			case 8:
				$date_query = array( 'date_query' => array(
					'after'	=> 'first day of -2 months',
					'before' => 'last day of -1 month',
					'inclusive'	=> true,
				));
				break;

			// This Year
			case 9:
				$date_query = array( 'date_query' => array(
					'after'	    => '1/1 this year',
					'compare'	=> '>',
					'inclusive'	=> true,
				));
				break;

			// Last year
			case 10:
				$date_query = array( 'date_query' => array(
					'after'	    => '1/1 last year',
					'before'    => '12/31 last year',
					'inclusive'	=> true,
				));
				break;

			// default - return empty where clause
			default:
				/**
				 * Return a custom date query for custom period_flag's.
				 *
				 * This filter extends the functionality allowing for custom periods if a new value
				 * is passed via $_REQUEST['m']. {@see 'purchase_log_special_periods'}.
				 *
				 * @since 4.1.0
				 *
				 * @param array Empty array to be filled with a valid query {@see WP_Date_Query}.
				 */
				$date_query = apply_filters( 'wpsc_purchase_log_predefined_periods_' . $period_flag, array() );
		}

		/**
		 * Filter the parsed date query.
		 *
		 * This filter can be used to override the constructed date_query.
		 *
		 * @since 4.1.0
		 *
		 * @param array $date_query    Empty array to be filled with a valid date query {@see WP_Date_Query}
		 * @param string $period_flag  Value passed from $_REQUEST['m'].
		 */
		return apply_filters( 'wpsc_purchase_log_predefined_periods', $date_query, $period_flag );
	}

	public function set_date_column_to_date( $columns ){

		$columns[] = '__date__';
		return $columns;

	}

	public function is_pagination_enabled() {
		return $this->per_page !== 0;
	}

	public function is_sortable() {
		return $this->sortable;
	}

	public function is_views_enabled() {
		return $this->views;
	}

	public function is_search_box_enabled() {
		return $this->search_box;
	}

	/**
	 * Define the columns in our list table. You can add/amend this list using
	 * WordPress core filter manage_{screen}_columns, specifically
	 * manage_dashboard_page_wpsc-purchase-logs_columns.
	 *
	 * @return array[string]string List of column headings
	 */
	public function get_columns() {
		return array(
			'cb'       => '<input type="checkbox" />',
			'id'       => __( 'Order ID', 'wp-e-commerce' ),
			'customer' => __( 'Customer', 'wp-e-commerce' ),
			'amount'   => __( 'Amount', 'wp-e-commerce' ),
			'status'   => _x( 'Status', 'sales log list table column', 'wp-e-commerce' ),
			'date'     => __( 'Date', 'wp-e-commerce' ),
			'tracking' => _x( 'Tracking ID', 'purchase log', 'wp-e-commerce' ),
		) ;
	}

	/**
	 * Define the columns in the table which are sortable. You can add/amend
	 * this list using the WordPress core filter manage_{screen}_sortable_columns
	 * Specifically: manage_dashboard_page_wpsc-purchase-logs_sortable_columns
 	 *
	 * @return array[string]string List of sortable column IDs and corresponding db column of the item
	 */
	public function get_sortable_columns() {
		if ( ! $this->sortable )
			return array();

		return array(
			'date'   => 'id',
			'status' => 'processed',
			'amount' => 'totalprice',
		) ;
	}

	private function get_months() {
		global $wpdb;

		// "date" column is not indexed. Might be better to use transient just in case
		// there are lots of logs
		$today = getdate();
		$transient_key = 'wpsc_purchase_logs_months_' . $today['year'] . $today['month'];
		/* if ( $months = get_transient( $transient_key ) )
			return $months; */

		$sql = "
			SELECT DISTINCT YEAR(FROM_UNIXTIME(date)) AS year, MONTH(FROM_UNIXTIME(date)) AS month
			FROM " . WPSC_TABLE_PURCHASE_LOGS . " AS p
			{$this->joins}
			WHERE {$this->where_no_filter}
			ORDER BY date DESC
		";

		$months = $wpdb->get_results( $sql );
		set_transient( $transient_key, $months, 60 * 24 * 7 );
		return $months;
	}

	/**
	 * Get an array of the untranslated, base view labels for the purchase log statuses.
	 *
	 * @return array The untranslated, base view label for each status.
	 */
	private function get_status_view_labels() {

		global $wpsc_purchlog_statuses;

		$view_labels = array();
		foreach ( $wpsc_purchlog_statuses as $status ) {
			if ( ! empty( $status['view_label'] ) ) {
				// The status provides a view label i18n _nx_noop-able object.
				$view_labels[$status['order']]['view_label'] = $status['view_label'];
			} else {
				// The status doesn't provide a view label i18n string. Use a generic one.
				$view_labels[$status['order']]['view_label'] = _nx_noop(
					'%s <span class="count">(%d)</span>',
					'%s <span class="count">(%d)</span>',
					'Purchase log view links for custom status with no explicit translation.',
					'wp-e-commerce'
				);
				$view_labels[$status['order']]['label'] = $status['label'];
			}
		}
		return $view_labels;
	}

	/**
	 * Returns a list of the purchase logs indexed on status number. Also includes a grand total
	 * indexed as 'all'.
	 *
	 * @return array An array indexed on status number giving the count of purchase logs in that status.
	 */
	private function get_per_status_counts() {

		global $wpdb;

		$sql = 'SELECT DISTINCT processed, COUNT(*) AS count FROM ' . WPSC_TABLE_PURCHASE_LOGS . ' GROUP BY processed ORDER BY processed';
		$results = $wpdb->get_results( $sql );
		$statuses = array(
			'all' => 0,
		);

		if ( ! empty( $results ) ) {
			foreach ( $results as $status ) {
				$statuses[$status->processed] = $status->count;
				$statuses['all']              += $status->count;
			}
		}
		return $statuses;
	}

	/**
	 * Get the sub-views of the list table.
	 *
	 * Allows user to limit the list to orders in a particular status.
	 *
	 * @return array The available view links.
	 */
	public function get_views() {

		$view_labels = $this->get_status_view_labels();
		$statuses    = $this->get_per_status_counts();

		$all_text = sprintf(
			_nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $statuses['all'], 'purchase logs', 'wp-e-commerce' ),
			number_format_i18n( $statuses['all'] )
		);

		$all_href = remove_query_arg( array(
			'status',
			'paged',
			'action',
			'action2',
			'm',
			'deleted',
			'updated',
			'paged',
			's',
		) );
		$all_class = ( $this->status == 'all' && empty( $_REQUEST['m'] ) && empty( $_REQUEST['s'] ) ) ? 'class="current"' : '';
		$views = array(
			'all' => sprintf(
				'<a href="%s" %s>%s</a>',
				esc_url( $all_href ),
				$all_class,
				$all_text
			),
		);

		foreach ( $statuses as $status => $count ) {
			if ( ! isset( $view_labels[$status] ) ) {
				continue;
			}
			if ( empty( $view_labels[$status]['label'] ) ) {
				// This translation needs only the quantity dropping in.
				$text = sprintf(
					translate_nooped_plural( $view_labels[$status]['view_label'], $count, 'wp-e-commerce' ),
					number_format_i18n( $count )
				);
			} else {
				// This translation needs the status label, and quantity dropping in.
				$text = sprintf(
					translate_nooped_plural( $view_labels[$status]['view_label'], $count, 'wp-e-commerce' ),
					$view_labels[$status]['label'],
					number_format_i18n( $count )
				);
			}
			$href = add_query_arg( 'status', $status );
			$href = remove_query_arg( array(
				'deleted',
				'updated',
				'action',
				'action2',
				'm',
				'paged',
				's',
			), $href );
			$class = ( $this->status == $status ) ? 'class="current"' : '';
			$views[$status] = sprintf(
				'<a href="%s" %s>%s</a>',
				esc_url( $href ),
				$class,
				$text
			);
		}

		return $views;
	}

	public function months_dropdown( $post_type = '' ) {
		global $wp_locale;

		$m = isset( $_REQUEST['m'] ) ? $_REQUEST['m'] : 0;

		if ( ! $this->month_filter ) {
			if ( $m !== 0 )
				echo '<input type="hidden" name="m" value="' . esc_attr( $m ) . '" />';

			return false;
		}

		$months = $this->get_months();
		if ( ! empty( $months ) ) {
			?>
			<select name="m">
				<option <?php selected( 0, $m ); ?> value="0"><?php _e( 'Show all dates', 'wp-e-commerce' ); ?></option>
				<?php

				$this->special_periods( $m );

				foreach ( $months as $arc_row ) {
					$month = zeroise( $arc_row->month, 2 );
					$year = $arc_row->year;

					printf( "<option %s value='%s'>%s</option>\n",
						selected( $arc_row->year . $month, $m, false ),
						esc_attr( $arc_row->year . $month ),
						$wp_locale->get_month( $month ) . ' ' . $year
					);
				}
				?>
			</select>
			<?php
			submit_button( _x( 'Filter', 'extra navigation in purchase log page', 'wp-e-commerce' ), 'secondary', false, false, array( 'id' => 'post-query-submit' ) );

		}
	}

	/**
	 * Outputs the pre-defined selectable periods.
	 *
	 * Inserts new predefined periods into the period filter select on sales log screen.
	 *
	 * @since 4.1.0
	 *
	 * @param string $selected The value of $_REQUEST['m'] - unsanitized.
	 */
	private function special_periods( $selected ){

		/**
		 * Filter the available special periods on the purchase log listing screen.
		 *
		 * Can Used to remove periods or add new period definitions {@see wpsc_purchase_log_predefined_periods_}
		 *
		 * @since 4.1.0
		 *
		 * @param array array() The periods currently defined.
		 */
		$periods = apply_filters( 'wpsc_purchase_log_special_periods', array(
			1 => _x('Today', 'time period for the current day', 'wp-e-commerce'),
			2 => _x('Yesterday', 'time period for the previous day', 'wp-e-commerce'),
			3 => _x('This Week', 'time period for the current week', 'wp-e-commerce'),
			4 => _x('Last Week', 'time period for the prior week', 'wp-e-commerce'),
			5 => _x('This Month', 'time period for the current month to date', 'wp-e-commerce'),
			6 => _x('Last Month', 'time period for the prior month', 'wp-e-commerce'),
			7 => _x('This Quarter', 'time period for the prior two months plus this month-to-date', 'wp-e-commerce'),
			8 => _x('Prior 3 Months', 'time period for the three months prior to the current month', 'wp-e-commerce'),
			9 => _x('This Year', 'time period for the current year to date', 'wp-e-commerce'),
			10 => _x('Last Year', 'time period for the prior year', 'wp-e-commerce'),
		) );

		echo '<option disabled="disabled">---------</option>';
		foreach( $periods as $value => $label ){
			printf( "<option %s value='%s'>%s</option>\n",
				selected( $value, $selected, false ),
				esc_attr( $value ),
				esc_html( $label )
			);
		}
		echo '<option disabled="disabled">---------</option>';

	}

	public function extra_tablenav( $which ) {
		if ( 'top' == $which ) {
			echo '<div class="alignleft actions">';
			$this->months_dropdown();
			do_action( 'wpsc_sales_log_extra_tablenav' );
			echo '</div>';
		}
	}

	public function pagination( $which ) {
		ob_start();
		parent::pagination( $which );
		$output = ob_get_clean();
		if ( $this->status == 'all' )
			$string = _x( 'Total (excluding Incomplete and Declined): %s', 'sales log page total', 'wp-e-commerce' );
		else
			$string = _x( 'Total: %s', 'sales log page total', 'wp-e-commerce' );
		$total_amount = ' - ' . sprintf( $string, wpsc_currency_display( $this->total_amount ) );
		$total_amount = str_replace( '$', '\$', $total_amount );
		$output = preg_replace( '/(<span class="displaying-num">)([^<]+)(<\/span>)/', '${1}${2}' . ' ' . $total_amount . '${3}', $output );

		echo $output;
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
			'updated',
			'deleted',
		) );
		$location = add_query_arg( array(
			'c'  => 'item_details',
			'id' => $item->id,
		), $location );
		return $location;
	}

	public function column_customer( $item ) {

		$name = '';

		if ( isset( $item->firstname ) ) {
			$name .= $item->firstname;
		}

		if ( isset( $item->lastname ) ) {
			$name .= ' ' . $item->lastname;
		}

		$name = trim( $name );

		if ( empty( $name ) ) {
			$name = apply_filters( 'wpsc_purchase_log_list_no_name', __( 'No name provided', 'wp-e-commerce' ), $item );
		}


	?>
		<strong>
			<a class="row-title" href="<?php echo esc_url( $this->item_url( $item ) ); ?>" title="<?php esc_attr_e( 'View order details', 'wp-e-commerce' ) ?>"><?php echo esc_html( $name ); ?></a>
		</strong><br />

		<?php if ( isset( $item->email ) ) : ?>
			<small><?php echo make_clickable( $item->email ); ?></small>
		<?php endif; ?>
		<?php
	}

	private function delete_url( $item ) {
		$nonce = wp_create_nonce( 'bulk-' . $this->_args['plural'] );
		$location = add_query_arg( array(
			'_wpnonce' => $nonce,
			'_wp_http_referer' => urlencode( $_SERVER['REQUEST_URI'] ),
			'action' => 'delete',
			urlencode( 'post[]' ) => $item->id,
		) );
		$location = remove_query_arg( array(
			'updated',
			'deleted',
		), $location );
		return $location;
	}

	public function column_id( $item ) {
		?>
		<a href="<?php echo esc_url( $this->item_url( $item ) ); ?>" title="<?php esc_attr_e( 'View order details', 'wp-e-commerce' ) ?>"><?php echo esc_html( $item->id ); ?></a>
		<?php if ( ! $this->current_action() == 'delete' ): ?>
			<br />
			<small><a class="delete" href="<?php echo esc_url( $this->delete_url( $item ) ); ?>"><?php echo esc_html_x( 'Delete', 'Sales log page', 'wp-e-commerce' ); ?></a></small>
		<?php endif ?>
		<?php
	}

	public function column_date( $item ) {
		$format = _x( 'Y/m/d g:i:s A', 'default date format for purchase log columns', 'wp-e-commerce' );
		$timestamp = (int) $item->date;
		$full_time = date( $format, $timestamp );
		$time_diff = time() - $timestamp;
		if ( $time_diff > 0 && $time_diff < 24 * 60 * 60 )
			$h_time = $h_time = sprintf( __( '%s ago', 'wp-e-commerce' ), human_time_diff( $timestamp ) );
		else
			$h_time = date( get_option( 'date_format', 'Y/m/d' ), $timestamp );

		echo '<abbr title="' . esc_attr( $full_time ) . '">' . esc_html( $h_time ) . '</abbr>';
	}

	public function column_amount( $item ) {
		echo '<a href="' . esc_attr( $this->item_url( $item ) ) . '" title="' . esc_attr__( 'View order details', 'wp-e-commerce' ) . '">';
		echo wpsc_currency_display( $item->amount ) . "<br />";
		echo '<small>' . sprintf( _n( '1 item', '%s items', $item->item_count, 'wp-e-commerce' ), number_format_i18n( $item->item_count ) ) . '</small>';
		echo '</a>';
	}

	public function column_default( $item, $column_name ) {
		$default = isset( $item->$column_name ) ? $item->$column_name : '';
		$output = apply_filters( 'wpsc_manage_purchase_logs_custom_column', $default, $column_name, $item );
		return $output;
	}

	public function column_status( $item ) {
		global $wpsc_purchlog_statuses;
		$dropdown_options = '';
		$current_status = false;
		foreach ( $wpsc_purchlog_statuses as $status ) {
			$selected = '';
			if ( $status['order'] == $item->status ) {
				$current_status = esc_html( $status['label'] );
				$selected = 'selected="selected"';
			}
			$dropdown_options .= '<option value="' . esc_attr( $status['order'] ) . '" ' . $selected . '>' . esc_html( $status['label'] ) . '</option>';
		}

		echo '<span>' . $current_status . '</span>';
		echo '<select class="wpsc-purchase-log-status" data-log-id="' . $item->id . '">';
		echo $dropdown_options;
		echo '</select>';
		echo '<img src="' . esc_url( wpsc_get_ajax_spinner() ) . '" class="ajax-feedback" title="" alt="" />';
	}

	public function column_tracking( $item ) {
		$classes = array( 'wpsc-purchase-log-tracking-id' );
		$empty = empty( $item->track_id );
		?>
			<div data-log-id="<?php echo esc_attr( $item->id ); ?>" <?php echo $empty ? ' class="empty"' : ''; ?>>
				<a class="add" href="#"><?php echo esc_html_x( 'Add Tracking ID', 'add purchase log tracking id', 'wp-e-commerce' ); ?></a>
				<input type="text" class="wpsc-purchase-log-tracking-id" value="<?php echo esc_attr( $item->track_id ); ?>" />
				<a class="button save" href="#"><?php echo esc_html_x( 'Save', 'save sales log tracking id', 'wp-e-commerce' ); ?></a>
				<img src="<?php echo esc_url( wpsc_get_ajax_spinner() ); ?>" class="ajax-feedback" title="" alt="" /><br class="clear" />
				<small class="send-email"><a href="#"><?php echo esc_html_x( 'Send Email', 'sales log', 'wp-e-commerce' ); ?></a></small>
			</div>
		<?php
	}

	/**
	 * Provide bulk actions for sales logs.
	 *
	 * Generates bulk actions for standard order statuses. Also generates a "delete" bulk action,
	 * and actions for any additional statuses that have been registered via the
	 * wpsc_set_purchlog_statuses filter.
	 *
	 * @return array Array of bulk actions available.
	 */
	public function get_bulk_actions() {

		global $wpsc_purchlog_statuses;

		if ( ! $this->bulk_actions ) {
			return array();
		}

		// Standard actions.
		$actions = array(
			'delete' => _x( 'Delete', 'bulk action', 'wp-e-commerce' ),
		);

		// Loop through all statuses and register bulk actions for them.
		foreach ( $wpsc_purchlog_statuses as $status ) {
			$actions[$status['order']] = $status['label'];
		}

		/**
		 * Filter the available bulk actions on the purchase log listing screen.
		 *
		 * @since 4.0
		 *
		 * @param array $actions The bulk actions currently defined.
		 */
		return apply_filters( 'wpsc_manage_purchase_logs_bulk_actions', $actions );
	}

	public function search_box( $text, $input_id ) {
		if ( ! $this->search_box ) {
			return '';
		}

		parent::search_box( $text, $input_id );
	}
}
