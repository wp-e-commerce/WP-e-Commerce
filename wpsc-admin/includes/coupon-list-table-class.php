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
	private $statuses;


	/**
	 * Get things started
	 *
	 * @access      private
	 * @since       3.8.10
	 * @return      void
	 */

	public function __construct(){
		global $status, $page;

		parent::__construct( array(
			'singular'  => 'coupon',
			'plural'    => 'coupons',
			'ajax'      => false
		) );

		$this->statuses = array(
			'active'   => _x( 'Active', 'coupon status', 'wp-e-commerce' ),
			'inactive' => _x( 'Inactive', 'coupon status', 'wp-e-commerce' ),
			'unknown'  => _x( 'Unknown', 'coupon status', 'wp-e-commerce' ),
		);

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

	public function get_views() {
		$base           = admin_url('edit.php?post_type=wpsc-product&page=wpsc-edit-coupons');

		$current        = isset( $_GET['status'] ) ? $_GET['status'] : 'all';
		$total_count    = '&nbsp;<span class="count">(' . $this->total_count    . ')</span>';
		$active_count   = '&nbsp;<span class="count">(' . $this->active_count . ')</span>';
		$inactive_count = '&nbsp;<span class="count">(' . $this->inactive_count  . ')</span>';

		$views = array(
			'all'		=> sprintf( '<a href="%s"%s>%s</a>', esc_url( remove_query_arg( 'status', $base ) ), $current === 'all' || $current == '' ? ' class="current"' : '', __('All', 'wp-e-commerce') . $total_count ),
			'active'	=> sprintf( '<a href="%s"%s>%s</a>', esc_url( add_query_arg( 'status', '1', $base ) ), $current === '1' ? ' class="current"' : '', __('Active', 'wp-e-commerce') . $active_count ),
			'inactive'	=> sprintf( '<a href="%s"%s>%s</a>', esc_url( add_query_arg( 'status', '0', $base ) ), $current === '0' ? ' class="current"' : '', __('Inactive', 'wp-e-commerce') . $inactive_count ),
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

	public function get_columns() {
		$columns = array(
			'cb'           => '<input type="checkbox" />',
			'coupon'       => __( 'Code', 'wp-e-commerce' ),
			'discount'     => __( 'Discount', 'wp-e-commerce' ),
			'start'        => __( 'Start Date', 'wp-e-commerce' ),
			'expiry'       => __( 'Expiration', 'wp-e-commerce' ),
			'status'  	   => __( 'Status', 'wp-e-commerce' ),
		);

		return $columns;
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

	protected function column_default( $item, $column_name ) {
		switch( $column_name ){
			case 'start' :

				if( ! empty( $item[ 'start'] ) && '0000-00-00 00:00:00' != $item['start'] ) {
					$start_date = strtotime( get_date_from_gmt( $item[ $column_name ] ) );
					$value      = date_i18n( get_option( 'date_format' ), $start_date );
				} else {
					$value = __( 'None', 'wp-e-commerce' );
				}

				return $value;

			case 'expiry' :

				if( ! empty( $item[ 'expiry'] ) && '0000-00-00 00:00:00' != $item['expiry'] ) {
					$expiry_date = strtotime( get_date_from_gmt( $item[ $column_name ] ) );
					$value       = date_i18n( get_option( 'date_format' ), $expiry_date );
				} else {
					$value = __( 'None', 'wp-e-commerce' );
				}

				return $value;

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
	protected function column_coupon( $item ) {

		$base     = admin_url( 'edit.php?post_type=wpsc-product&page=wpsc-edit-coupons&wpsc-action=edit_coupon&coupon=' . $item['ID'] );

		$coupon   = '<strong><a href="' . esc_url( add_query_arg( array( 'wpsc-action' => 'edit_coupon', 'coupon' => $item['ID'] ) ) ) . '">' . esc_html( $item['coupon'] ) . '</a></strong>';

		$row_actions = array();

		$row_actions['edit'] = '<a href="' . esc_url( add_query_arg( array( 'wpsc-action' => 'edit_coupon', 'coupon' => $item['ID'] ) ) ) . '">' . __( 'Edit', 'wp-e-commerce' ) . '</a>';

		if( strtolower( $item['status'] ) == 'active' )
			$row_actions['deactivate'] = '<a href="' . esc_url( add_query_arg( array( 'wpsc-action' => 'deactivate_coupon', 'coupon' => $item['ID'] ) ) ) . '">' . __( 'Deactivate', 'wp-e-commerce' ) . '</a>';
		else
			$row_actions['activate'] = '<a href="' . esc_url( add_query_arg( array( 'wpsc-action' => 'activate_coupon', 'coupon' => $item['ID'] ) ) ) . '">' . __( 'Activate', 'wp-e-commerce' ) . '</a>';

		$row_actions['delete'] = '<a href="' . esc_url( add_query_arg( array( 'wpsc-action' => 'delete_coupon', 'coupon' => $item['ID'] ) ) ) . '">' . __( 'Delete', 'wp-e-commerce' ) . '</a>';

		$row_actions = apply_filters( 'wpsc_coupon_row_actions', $row_actions, $item['ID'] );

		return $coupon . $this->row_actions( $row_actions );
	}


	/**
	 * Render the checkbox column
	 *
	 * @access      private
	 * @since       3.8.10
	 * @return      string
	 */

	protected function column_cb( $item ) {
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

	protected function column_status( $item ) {
		if ( ! array_key_exists( $item['status'], $this->statuses ) )
			$item['status'] = 'unknown';

		$column = '<span class="wpsc-coupon-status wpsc-coupon-status-%1$s">%2$s</a>';
		$column = sprintf( $column, $item['status'], $this->statuses[$item['status']] );

		return $column;
	}


	/**
	 * Render the Discount Column
	 *
	 * @access      private
	 * @param       array $item Contains all the data of the discount code
	 * @since       3.8.10
	 * @return      string
	 */

	protected function column_discount( $item ) {
		switch( $item['type'] ) {
			case 0:
				return wpsc_currency_display( $item['discount'] );
				break;
			case 1:
				return $item['discount'] . '%';
				break;
			case 2:
				return __( 'Free shipping', 'wp-e-commerce' );
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

	public function get_bulk_actions() {
		$actions = array(
			'activate'   => __( 'Activate', 'wp-e-commerce' ),
			'deactivate' => __( 'Deactivate', 'wp-e-commerce' ),
			'delete'     => __( 'Delete', 'wp-e-commerce' )
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

	public function process_bulk_action() {

		global $wpdb;

		$ids = isset( $_GET['coupon'] ) ? $_GET['coupon'] : false;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		foreach ( $ids as $id ) {

			$coupon = new WPSC_Coupon( $id );

			if ( 'delete' === $this->current_action() ) {

				// Delete a discount
				$coupon->delete();

			} elseif( 'activate' === $this->current_action() ) {

				// Activate a discount
				$coupon->activate();

			} elseif( 'deactivate' === $this->current_action() ) {

				// Deactivate a discount
				$coupon->deactivate();

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
	public function process_single_actions() {

		global $wpdb;

		if ( ! isset( $_GET['wpsc-action'] ) || ! isset( $_GET['coupon'] ) ) {
			return;
		}

		$coupon = new WPSC_Coupon( $_GET['coupon'] );

		switch ( $_GET['wpsc-action'] ) {

			case 'activate_coupon':

				$updated = $coupon->activate();

				if ( $updated ) {
					printf( '<div class="updated"><p>%s</p></div>', __( 'The coupon has been activated.', 'wp-e-commerce' ) );
				}

				break;

			case 'deactivate_coupon':

				$updated = $coupon->deactivate();

				if ( $updated ) {
					printf( '<div class="updated"><p>%s</p></div>', __( 'The coupon has been deactivated.', 'wp-e-commerce' ) );
				}

				break;

			case 'delete_coupon':

				$deleted = $coupon->delete();

				if ( $deleted ) {
					printf( '<div class="updated"><p>%s</p></div>', __( 'The coupon has been deleted.', 'wp-e-commerce' ) );
				}

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
	public function count_coupons() {

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
	public function coupons_data() {

		global $wpdb;

		$coupons_data = array();

		if ( isset( $_GET['paged'] ) ) $page = $_GET['paged']; else $page = 1;

		$per_page = $this->per_page;
		$offset   = ( $page - 1 ) * $this->per_page;

		$status   = isset( $_GET['status'] ) ? absint( $_GET['status'] ) : false;
		$where    = $status !== false ? "WHERE active = $status" : '';

		$order 	  = isset( $_GET['order'] ) && strtoupper( $_GET['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		$limit    = " LIMIT $offset,$per_page;";
		$coupons  = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_COUPON_CODES . "` {$where} ORDER BY id {$order} {$limit} ", ARRAY_A );

		if ( $coupons ) {
			foreach ( $coupons as $data ) {

				$coupon = new WPSC_Coupon( array(
					'id'            => $data['id'],
					'coupon_code'   => $data['coupon_code'],
					'value'         => $data['value'],
					'is-percentage' => $data['is-percentage'],
					'start'         => $data['start'],
					'expiry'        => $data['expiry'],
					'active'        => $data['active']
				) );

				// Re-map data to array for legacy handling of this method's return data.
				// (would be nicer to return an object?)
				$coupons_data[] = array(
					'ID'       => $coupon->get( 'id' ),
					'coupon'   => $coupon->get( 'coupon_code' ),
					'discount' => $coupon->get( 'value' ),
					'type'     => $coupon->get( 'is-percentage' ),
					'start'    => $coupon->get( 'start' ),
					'expiry'   => $coupon->get( 'expiry' ),
					'status'   => $coupon->get( 'active' ) == 1 ? 'active' : 'inactive'
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
	public function prepare_items() {

		$hidden                = array();
		$total_items           = 0;
		$per_page              = $this->per_page;
		$columns               = $this->get_columns();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		$data = $this->coupons_data();
		$this->items = $data;

		$status = isset( $_GET['status'] ) ? $_GET['status'] : 'any';
		switch ( $status ) {
			case '1':
				$total_items = $this->active_count;
				break;
			case '0':
				$total_items = $this->inactive_count;
				break;
			case 'any':
				$total_items = $this->total_count;
				break;
		}

		$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page )
			)
		);
	}
}
