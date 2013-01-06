<?php
/**
 * Coupon WP List Table Class
 *
 * @package     WP e-Commerce
 * @subpackage  Coupon List Table Class
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.8.10
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * WPSC Coupon Codes Table Class
 *
 * Renders the Coupons table on the Coupons page
 *
 * @access      private
 * @since       3.8.10
 */

class WPSC_Coupons_List_Table extends WP_List_Table {


	/**
	 * Number of results to show per page
	 *
	 * @since       3.8.10
	 */
	private $per_page = 30;

	private $total_count;
	private $active_count;
	private $inactive_count;


	/**
	 * Get things started
	 *
	 * @access      private
	 * @since       3.8.10
	 * @return      void
	 */

	function __construct(){
		global $status, $page;

		parent::__construct( array(
			'singular'  => 'coupon',
			'plural'    => 'coupons',
			'ajax'      => false
		) );

		$this->process_single_actions();
		$this->count_coupons();
	}


	/**
	 * Retrieve the view types
	 *
	 * @access      private
	 * @since       3.8.10
	 * @return      array
	 */

	function get_views() {
		$base           = admin_url('edit.php?post_type=wpsc-product&page=wpsc-edit-coupons');

		$current        = isset( $_GET['status'] ) ? $_GET['status'] : '';
		$total_count    = '&nbsp;<span class="count">(' . $this->total_count    . ')</span>';
		$active_count   = '&nbsp;<span class="count">(' . $this->active_count . ')</span>';
		$inactive_count = '&nbsp;<span class="count">(' . $this->inactive_count  . ')</span>';

		$views = array(
			'all'		=> sprintf( '<a href="%s"%s>%s</a>', remove_query_arg( 'status', $base ), $current === 'all' || $current == '' ? ' class="current"' : '', __('All', 'wpsc') . $total_count ),
			'active'	=> sprintf( '<a href="%s"%s>%s</a>', add_query_arg( 'status', 'active', $base ), $current === 'active' ? ' class="current"' : '', __('Active', 'wpsc') . $active_count ),
			'inactive'	=> sprintf( '<a href="%s"%s>%s</a>', add_query_arg( 'status', 'inactive', $base ), $current === 'inactive' ? ' class="current"' : '', __('Inactive', 'wpsc') . $inactive_count ),
		);

		return $views;
	}


	/**
	 * Retrieve the table columnds
	 *
	 * @access      private
	 * @since       3.8.10
	 * @return      array
	 */

	function get_columns() {
		$columns = array(
			'cb'           => '<input type="checkbox" />',
			'ID'           => __( 'ID', 'wpsc' ),
			'coupon'       => __( 'Code', 'wpsc' ),
			'discount'     => __( 'Discount', 'wpsc' ),
			'max_uses' 	   => __( 'Max Uses', 'wpsc' ),
			'start'        => __( 'Start Date', 'wpsc' ),
			'expiry'       => __( 'Expiration', 'wpsc' ),
			'status'  	   => __( 'Status', 'wpsc' ),
		);

		return $columns;
	}


	/**
	 * Retrieve the table's sortable columns
	 *
	 * @access      private
	 * @since       3.8.10
	 * @return      array
	 */

	function get_sortable_columns() {
		return array(
			'ID'     => array( 'ID', true )
		);
	}


	/**
	 * Render most columns
	 *
	 * @access      private
	 * @param       array $item Contains all the data of the discount code
	 * @param       string $column_name The name of the column
	 * @since       3.8.10
	 * @return      string
	 */

	function column_default( $item, $column_name ) {
		switch( $column_name ){
			case 'start' :
				$start_date = strtotime( $item[ $column_name ] );
				return date_i18n( get_option( 'date_format' ), $start_date );
			default:
				return $item[ $column_name ];
		}
	}

	/**
	 * Render the Name Column
	 *
	 * @access      private
	 * @param       array $item Contains all the data of the discount code
	 * @since       3.8.10
	 * @return      string
	 */
	function column_coupon( $item ) {

		$base     = admin_url( 'edit.php?post_type=wpsc-product&page=wpsc-edit-coupons&wpsc-action=edit_coupon&coupon=' . $item['ID'] );

		$row_actions = array();

		$row_actions['edit'] = '<a href="' . add_query_arg( array( 'wpsc-action' => 'edit_coupon', 'coupon' => $item['ID'] ) ) . '">' . __( 'Edit', 'wpsc' ) . '</a>';

		if( strtolower( $item['status'] ) == 'active' )
			$row_actions['deactivate'] = '<a href="' . add_query_arg( array( 'wpsc-action' => 'deactivate_coupon', 'coupon' => $item['ID'] ) ) . '">' . __( 'Deactivate', 'wpsc' ) . '</a>';
		else
			$row_actions['activate'] = '<a href="' . add_query_arg( array( 'wpsc-action' => 'activate_coupon', 'coupon' => $item['ID'] ) ) . '">' . __( 'Activate', 'wpsc' ) . '</a>';

		$row_actions['delete'] = '<a href="' . add_query_arg( array( 'wpsc-action' => 'delete_coupon', 'coupon' => $item['ID'] ) ) . '">' . __( 'Delete', 'wpsc' ) . '</a>';

		$row_actions = apply_filters( 'wpsc_coupon_row_actions', $row_actions, $item['ID'] );

		return $item['coupon'] . $this->row_actions( $row_actions );
	}


	/**
	 * Render the checkbox column
	 *
	 * @access      private
	 * @since       3.8.10
	 * @return      string
	 */

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['singular'],
			/*$2%s*/ $item['ID']
		);
	}


	/**
	 * Render the Status Column
	 *
	 * @access      private
	 * @param       array $item Contains all the data of the discount code
	 * @since       3.8.10
	 * @return      string
	 */

	function column_status( $item ) {
		switch( $item['status'] ) {
			case 'active' :
				$img = '<img src="' . WPSC_CORE_IMAGES_URL . '/yes_stock.gif"/>';
				break;
			case 'inactive' :
				$img = '<img src="' . WPSC_CORE_IMAGES_URL . '/no_stock.gif"/>';
				break;
		}
		return $img;
	}


	/**
	 * Render the Discount Column
	 *
	 * @access      private
	 * @param       array $item Contains all the data of the discount code
	 * @since       3.8.10
	 * @return      string
	 */

	function column_discount( $item ) {
		switch( $item['type'] ) {
			case 0:
				return wpsc_currency_display( $item['discount'] );
				break;
			case 1:
				return $item['discount'] . '%';
				break;
			case 2:
				return __( 'Free shipping', 'wpsc' );
				break;
		}
	}


	/**
	 * Retrieve the bulk actions
	 *
	 * @access      private
	 * @since       3.8.10
	 * @return      array
	 */

	function get_bulk_actions() {
		$actions = array(
			'activate'   => __( 'Activate', 'wpsc' ),
			'deactivate' => __( 'Deactivate', 'wpsc' ),
			'delete'     => __( 'Delete', 'wpsc' )
		);

		return $actions;
	}


	/**
	 * Process the bulk actions
	 *
	 * @access      private
	 * @since       3.8.10
	 * @return      void
	 */

	function process_bulk_action() {

		global $wpdb;

		$ids = isset( $_GET['coupon'] ) ? $_GET['coupon'] : false;

		if ( ! is_array( $ids ) )
			$ids = array( $ids );

		foreach ( $ids as $id ) {
			if ( 'delete' === $this->current_action() ) {

				// delete a discount
				$wpdb->query( $wpdb->prepare( "DELETE FROM " . WPSC_TABLE_COUPON_CODES . " WHERE id = %d", $id ) );

			} elseif( 'activate' === $this->current_action() ) {

				// activate a discount
				$wpdb->query( $wpdb->prepare( "UPDATE " . WPSC_TABLE_COUPON_CODES . " SET active = 1 WHERE id = %d", $id ) );


			} elseif( 'deactivate' === $this->current_action() ) {

				// deactivate a discount
				$wpdb->query( $wpdb->prepare( "UPDATE " . WPSC_TABLE_COUPON_CODES . " SET active = 0 WHERE id = %d", $id ) );

			}
		}

	}


	/**
	 * Process single actions
	 *
	 * @access      private
	 * @since       3.8.10
	 * @return      void
	 */
	function process_single_actions() {

		global $wpdb;

		if( ! isset( $_GET['wpsc-action'] ) || ! isset( $_GET['coupon'] ) )
			return;

		$coupon_id = absint( $_GET['coupon'] );

		switch( $_GET['wpsc-action'] ) {

			case 'activate_coupon':
				$wpdb->query( $wpdb->prepare( "UPDATE " . WPSC_TABLE_COUPON_CODES . " SET active = 1 WHERE id = %d", $coupon_id ) );
				break;
			case 'deactivate_coupon':
				$wpdb->query( $wpdb->prepare( "UPDATE " . WPSC_TABLE_COUPON_CODES . " SET active = 0 WHERE id = %d", $coupon_id ) );
				break;
			case 'delete_coupon':
				$wpdb->query( $wpdb->prepare( "DELETE FROM " . WPSC_TABLE_COUPON_CODES . " WHERE id = %d", $coupon_id ) );
				break;
		}

	}


	/**
	 * Retrieve the discount code counts
	 *
	 * @access      private
	 * @since       3.8.10
	 * @return      array
	 */
	function count_coupons() {

		global $wpdb;

		// retrieve all discounts here
		$this->active_count   = $wpdb->get_var( "SELECT COUNT(id) AS count FROM " . WPSC_TABLE_COUPON_CODES . " WHERE active=1;" );
		$this->inactive_count = $wpdb->get_var( "SELECT COUNT(id) AS count FROM " . WPSC_TABLE_COUPON_CODES . " WHERE active=0;" );
		$this->total_count    = $this->active_count + $this->inactive_count;
	}


	/**
	 * Retrieve all the data for all the discount codes
	 *
	 * @access      private
	 * @since       3.8.10
	 * @return      array
	 */
	function coupons_data() {

		global $wpdb;

		$coupons_data = array();

		if ( isset( $_GET['paged'] ) ) $page = $_GET['paged']; else $page = 1;

		$per_page = $this->per_page;
		$offset   = ( $page - 1 ) * $this->per_page;
		$order 	  = isset( $_GET['order'] ) ? $_GET['order'] : 'DESC';
		$limit    = " LIMIT $offset,$per_page;";

		$coupons  = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_COUPON_CODES . "` ORDER BY id {$order} {$limit} ", ARRAY_A );

		if ( $coupons ) {
			foreach ( $coupons as $coupon ) {


				$coupons_data[] = array(
					'ID'           => $coupon['id'],
					'coupon'       => $coupon['coupon_code'],
					'discount' 	   => $coupon['value'],
					'type' 	       => $coupon['is-percentage'],
					'max_uses' 	   => $coupon['use-x-times'],
					'start'        => $coupon['start'],
					'expiry'       => $coupon['expiry'],
					'status'  	   => $coupon['active'] == 1 ? 'active' : 'inactive',
				);
			}
		}
		return $coupons_data;
	}


	/**
	 * Setup the final data for the table
	 *
	 * @access      private
	 * @since       3.8.10
	 * @return      array
	 */
	function prepare_items() {

		$per_page = $this->per_page;

		$columns = $this->get_columns();

		$hidden = array();

		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		$data = $this->coupons_data();

		$current_page = $this->get_pagenum();

		$status = isset( $_GET['status'] ) ? $_GET['status'] : 'any';

		switch( $status ) {
			case 'active':
				$total_items = $this->active_count;
				break;
			case 'inactive':
				$total_items = $this->inactive_count;
				break;
			case 'any':
				$total_items = $this->total_count;
				break;
		}

		$this->items = $data;

		$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page )
			)
		);
	}
}