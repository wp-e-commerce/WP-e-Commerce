<?php
/**
 * WP e-Commerce REST API Class
 *
 * This class provides a front-facing JSON/XML API that makes it possible to
 * query data from the shop.
 *
 * The primary purpose of this class is for external sales / earnings tracking
 * systems, such as mobile applications.
 *
 * @package     WPSC
 * @subpackage  Includes/API
 * @copyright   Copyright (c) 2013, Instinct Entertainment
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.9
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WPSC_API Class
 *
 * Renders API returns as a JSON/XML array
 *
 * @since  3.9
 */
class WPSC_REST_API {

	/**
	 * API version
	 */
	const VERSION = '1.0';

	/**
	 * @var WPSC_REST_API The one true WPSC_REST_API
	 * @since 3.9
	 */
	private static $instance;

	/**
	 * Pretty Print?
	 *
	 * @var bool
	 * @access private
	 * @since 3.9
	 */
	private $pretty_print = false;

	/**
	 * Log API requests?
	 *
	 * @var bool
	 * @access private
	 * @since 3.9
	 */
	private $log_requests = true;

	/**
	 * Is this a valid request?
	 *
	 * @var bool
	 * @access private
	 * @since 3.9
	 */
	private $is_valid_request = true; // TODO - set to false once testing without keys is finished

	/**
	 * User ID Performing the API Request
	 *
	 * @var int
	 * @access private
	 * @since 3.9
	 */
	private $user_id = 0;

	/**
	 * Request Errors
	 *
	 * @var array
	 * @access private
	 * @since 3.9
	 */
	private $errors = array();

	/**
	 * Response data to return
	 *
	 * @var array
	 * @access private
	 * @since 3.9
	 */
	private $data = array();

	/**
	 * Main WPSC_REST_API Instance
	 *
	 * Insures that only one instance of WPSC_REST_API exists in memory at any one
	 * time.
	 *
	 * @var object
	 * @access public
	 * @since 3.9
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WPSC_REST_API ) ) {
			self::$instance = new WPSC_REST_API;
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Setup the EDD API
	 *
	 * @access public
	 * @since 3.9
	 * @return void
	 */
	public function init() {
		add_action( 'init',                    array( $this, 'add_endpoint'   ) );
      	add_action( 'template_redirect',       array( $this, 'process_query'  ), -1 );
		add_filter( 'query_vars',              array( $this, 'query_vars'     ) );

		// Determine if JSON_PRETTY_PRINT is available
		$this->pretty_print = defined( 'JSON_PRETTY_PRINT' ) ? JSON_PRETTY_PRINT : null;

		// Allow API request logging to be turned off
		$this->log_requests = apply_filters( 'wpsc_api_log_requests', $this->log_requests );
	}

	/**
	 * Registers a new rewrite endpoint for accessing the API
	 *
	 * @access public
	 * @param array $rewrite_rules WordPress Rewrite Rules
	 * @since 3.9
	 */
	public function add_endpoint( $rewrite_rules ) {
		add_rewrite_endpoint( 'wpsc-api', EP_ALL );
	}

	/**
	 * Registers query vars for API access
	 *
	 * @access public
	 * @since 3.9
	 * @param array $vars Query vars
	 * @return array $vars New query vars
	 */
	public function query_vars( $vars ) {
		$vars[] = 'token';
		$vars[] = 'key';
		$vars[] = 'query';
		$vars[] = 'type';
		$vars[] = 'product';
		$vars[] = 'number';
		$vars[] = 'date';
		$vars[] = 'startdate';
		$vars[] = 'enddate';
		$vars[] = 'customer';
		$vars[] = 'coupon';
		$vars[] = 'format';

		return $vars;
	}

	/**
	 * Validate the API request
	 *
	 * Checks for the user's public key and token against the secret key
	 *
	 * @access private
	 * @global object $wp_query WordPress Query
	 * @uses wpsc_API::get_user()
	 * @uses wpsc_API::invalid_key()
	 * @uses wpsc_API::invalid_auth()
	 * @since 3.9
	 * @return void
	 */
	private function validate_request() {
		global $wp_query;


		// Make sure we have both user and api key
		if ( empty( $wp_query->query_vars['token'] ) || empty( $wp_query->query_vars['key'] ) )
			$this->missing_auth();



		return; // TODO - remove once actual keys are implemented
		// TODO validate request
	}

	/**
	 * Displays a missing authentication error if all the parameters aren't
	 * provided
	 *
	 * @access public
	 * @uses wpsc_API::output()
	 * @since 3.9
	 */
	public function missing_auth() {
		$this->errors['missing_keys'] = __( 'You must specify both a token and API key!', 'wpsc' );
	}

	/**
	 * Displays an authentication failed error if the user failed to provide valid
	 * credentials
	 *
	 * @access public
	 * @since  3.9
	 * @uses wpsc_API::output()
	 * @return void
	 */
	function invalid_auth() {
		$this->errors['not_authenticated'] = __( 'Your request could not be authenticated!', 'wpsc' );
	}

	/**
	 * Displays an invalid API key error if the API key provided couldn't be
	 * validated
	 *
	 * @access public
	 * @since 3.9
	 * @uses wpsc_API::output()
	 * @return void
	 */
	function invalid_key() {
		$this->errors['invalid_api_key'] = __( 'Invalid API key!', 'wpsc' );
	}


	/**
	 * Listens for the API and then processes the API requests
	 *
	 * @access public
	 * @global $wp_query
	 * @since 3.9
	 * @return void
	 */
	public function process_query() {
		global $wp_query;

		// Check for wpsc-api var. Get out if not present
		if ( ! isset( $wp_query->query_vars['wpsc-api'] ) )
			return;

		// Check for a valid user and set errors if necessary
		$this->validate_request();

		// Only proceed if no errors have been noted
		if( ! empty( $this->errors ) )
			$this->output();

		// Determine the kind of query
		$query_mode = $this->get_query_mode();

		$data = array();

		switch( $query_mode ) :

			case 'products' :

				$product = ! empty( $wp_query->query_vars['product'] ) ? $wp_query->query_vars['product']   : null;

				$data = $this->get_products( $product );

				break;

			case 'customers' :

				$customer = ! empty( $wp_query->query_vars['customer'] ) ? $wp_query->query_vars['customer']  : null;

				$data = $this->get_customers( $customer );

				break;

			case 'sales' :

				$data = $this->get_recent_sales();

				break;

			case 'coupons' :

				$coupon = ! empty( $wp_query->query_vars['coupon'] ) ? $wp_query->query_vars['coupon']  : null;

				$data = $this->get_coupons( $coupon );

				break;

			case 'stats' :
			default :
				$data = $this->get_stats( array(
					'type'      => ! empty( $wp_query->query_vars['type'] )      ? $wp_query->query_vars['type']      : null,
					'product'   => ! empty( $wp_query->query_vars['product'] )   ? $wp_query->query_vars['product']   : null,
					'date'      => ! empty( $wp_query->query_vars['date'] )      ? $wp_query->query_vars['date']      : null,
					'startdate' => ! empty( $wp_query->query_vars['startdate'] ) ? $wp_query->query_vars['startdate'] : null,
					'enddate'   => ! empty( $wp_query->query_vars['enddate'] )   ? $wp_query->query_vars['enddate']   : null
				) );

				break;

		endswitch;

		if( ! empty( $this->errors ) ) {

			$data = $this->errors;

		} else {

			// Log this API request, if enabled. We log it here because we have access to errors.
			//$this->log_request( $this->data );

		}

		// Allow extensions to setup their own return data
		$this->data = apply_filters( 'wpsc_api_output_data', $data, $query_mode, $this );

		// Send out data to the output function
		$this->output();
	}

	/**
	 * Determines the kind of query requested and also ensure it is a valid query
	 *
	 * @access private
	 * @since 3.9
	 * @global $wp_query
	 * @return string $query Query mode
	 */
	private function get_query_mode() {
		global $wp_query;

		// Whitelist our query options
		$accepted = apply_filters( 'wpsc_api_valid_query_modes', array(
			'stats',
			'products',
			'customers',
			'sales',
			'coupons'
		) );

		$query = ! empty( $wp_query->query_vars['wpsc-api'] ) ? $wp_query->query_vars['wpsc-api'] : 'stats';

		// Make sure our query is valid
		if ( ! in_array( $query, $accepted ) ) {
			$this->errors['invalid_query'] = __( 'Invalid query!', 'wpsc' );
		}

		return $query;
	}

	/**
	 * Get page number
	 *
	 * @access private
	 * @since 3.9
	 * @global $wp_query
	 * @return int $wp_query->query_vars['page'] if page number returned (default: 1)
	 */
	private function get_paged() {
		global $wp_query;

		return isset( $wp_query->query_vars['page'] ) ? $wp_query->query_vars['page'] : 1;
	}


	/**
	 * Number of results to display per page
	 *
	 * @access private
	 * @since 3.9
	 * @global $wp_query
	 * @return int $per_page Results to display per page (default: 10)
	 */
	private function per_page() {
		global $wp_query;

		$per_page = isset( $wp_query->query_vars['number'] ) ? $wp_query->query_vars['number'] : 10;

		if( $per_page < 0 && $this->get_query_mode() == 'customers' )
			$per_page = 99999999; // Customers query doesn't support -1

		return apply_filters( 'wpsc_api_results_per_page', $per_page );
	}

	/**
	 * Retrieve the output format
	 *
	 * Determines whether results should be displayed in XML or JSON
	 *
	 * @access private
	 * @since 3.9
	 * @global $wp_query
	 * @return $format Output format
	 */
	private function get_output_format() {
		global $wp_query;

		$format = ! empty( $wp_query->query_vars['format'] ) ? $wp_query->query_vars['format'] : 'json';

		return apply_filters( 'wpsc_api_output_format', $format );
	}

	/**
	 * Sets up the dates used to retrieve earnings/sales
	 *
	 * @access public
	 * @since 3.9
	 * @param array $args Arguments to override defaults
	 * @return array $dates
	*/
	private function get_dates( $args = array() ) {
		$dates = array();

		$defaults = array(
			'type'      => '',
			'product'   => null,
			'date'      => null,
			'startdate' => null,
			'enddate'   => null
		);

		$args = wp_parse_args( $args, $defaults );

		//date_default_timezone_set( get_option( 'timezone_string' ) );

		if ( 'range' === $args['date'] ) {
			$startdate          = strtotime( $args['startdate'] );
			$enddate            = strtotime( $args['enddate'] );
			$dates['day_start'] = date( 'd', $startdate );
			$dates['day_end'] 	= date( 'd', $enddate );
			$dates['m_start'] 	= date( 'n', $startdate );
			$dates['m_end'] 	= date( 'n', $enddate );
			$dates['year'] 		= date( 'Y', $startdate );
			$dates['year_end'] 	= date( 'Y', $enddate );
		} else {
			// Modify dates based on predefined ranges
			switch ( $args['date'] ) :

				case 'this_month' :
					$dates['day'] 	    = null;
					$dates['m_start'] 	= date( 'n' );
					$dates['m_end']		= date( 'n' );
					$dates['year']		= date( 'Y' );
				break;

				case 'last_month' :
					$dates['day'] 	  = null;
					$dates['m_start'] = date( 'n' ) == 1 ? 12 : date( 'n' ) - 1;
					$dates['m_end']	  = $dates['m_start'];
					$dates['year']    = date( 'n' ) == 1 ? date( 'Y' ) - 1 : date( 'Y' );
				break;

				case 'today' :
					$dates['day']		= date( 'd' );
					$dates['m_start'] 	= date( 'n' );
					$dates['m_end']		= date( 'n' );
					$dates['year']		= date( 'Y' );
				break;

				case 'yesterday' :
					$month              = date( 'n' ) == 1 ? 12 : date( 'n' );
					$days_in_month      = cal_days_in_month( CAL_GREGORIAN, $month, date( 'Y' ) );
					$yesterday          = date( 'd' ) == 1 ? $days_in_month : date( 'd' ) - 1;
					$dates['day']		= $yesterday;
					$dates['m_start'] 	= $month;
					$dates['m_end'] 	= $month;
					$dates['year']		= $month == 1 && date( 'd' ) == 1 ? date( 'Y' ) - 1 : date( 'Y' );
				break;

				case 'this_quarter' :
					$month_now = date( 'n' );

					$dates['day'] 	        = null;

					if ( $month_now <= 3 ) {

						$dates['m_start'] 	= 1;
						$dates['m_end']		= 3;
						$dates['year']		= date( 'Y' );

					} else if ( $month_now <= 6 ) {

						$dates['m_start'] 	= 4;
						$dates['m_end']		= 6;
						$dates['year']		= date( 'Y' );

					} else if ( $month_now <= 9 ) {

						$dates['m_start'] 	= 7;
						$dates['m_end']		= 9;
						$dates['year']		= date( 'Y' );

					} else {

						$dates['m_start'] 	= 10;
						$dates['m_end']		= 12;
						$dates['year']		= date( 'Y' );

					}
				break;

				case 'last_quarter' :
					$month_now = date( 'n' );

					$dates['day'] 	        = null;

					if ( $month_now <= 3 ) {

						$dates['m_start'] 	= 10;
						$dates['m_end']		= 12;
						$dates['year']		= date( 'Y' ) - 1; // Previous year

					} else if ( $month_now <= 6 ) {

						$dates['m_start'] 	= 1;
						$dates['m_end']		= 3;
						$dates['year']		= date( 'Y' );

					} else if ( $month_now <= 9 ) {

						$dates['m_start'] 	= 4;
						$dates['m_end']		= 6;
						$dates['year']		= date( 'Y' );

					} else {

						$dates['m_start'] 	= 7;
						$dates['m_end']		= 9;
						$dates['year']		= date( 'Y' );

					}
				break;

				case 'this_year' :
					$dates['day'] 	    = null;
					$dates['m_start'] 	= null;
					$dates['m_end']		= null;
					$dates['year']		= date( 'Y' );
				break;

				case 'last_year' :
					$dates['day'] 	    = null;
					$dates['m_start'] 	= null;
					$dates['m_end']		= null;
					$dates['year']		= date( 'Y' ) - 1;
				break;

			endswitch;
		}

		return apply_filters( 'wpsc_api_stat_dates', $dates );
	}

	/**
	 * Generate the default sales stats returned by the 'stats' endpoint
	 *
	 * @access private
	 * @since 3.9
	 * @return array default sales statistics
	 */
	private function get_default_sales_stats() {
		// Default sales return
		$previous_month = date( 'n' ) == 1 ? 12 : date( 'n' ) - 1;
		$previous_year  = date( 'n' ) == 1 ? date( 'Y' ) - 1 : date( 'Y' );

		$sales['sales']['current_month'] = 0;
		$sales['sales']['last_month']    = 0;
		$sales['sales']['totals']        = 0;

		return $sales;
	}

	/**
	 * Generate the default earnings stats returned by the 'stats' endpoint
	 *
	 * @access private
	 * @since 3.9
	 * @return array default earnings statistics
	 */
	private function get_default_earnings_stats() {
		// Default earnings return
		$previous_month = date( 'n' ) == 1 ? 12 : date( 'n' ) - 1;
		$previous_year  = date( 'n' ) == 1 ? date( 'Y' ) - 1 : date( 'Y' );

		$earnings['earnings']['current_month'] = 0;
		$earnings['earnings']['last_month']    = 0;
		$earnings['earnings']['totals']        = 0;

		return $earnings;
	}

	/**
	 * Process Get Customers API Request
	 *
	 * @access public
	 * @since 3.9
	 * @global object $wpdb Used to query the database using the WordPress
	 *   Database API
	 * @param int $customer Customer ID
	 * @return array $customers Multidimensional array of the customers
	 */
	public function get_customers( $customer = null ) {
		if ( $customer == null ) {
			global $wpdb;

			$paged    = $this->get_paged();
			$per_page = $this->per_page();
			$offset   = $per_page * ( $paged - 1 );
			$customer_list_query = $wpdb->get_col( "SELECT DISTINCT meta_value FROM $wpdb->postmeta where meta_key = '_wpsc_payment_user_email' ORDER BY meta_id DESC LIMIT $per_page OFFSET $offset" );
			$customer_count = 0;

			foreach ( $customer_list_query as $customer_email ) {
				$customer_info = get_user_by( 'email', $customer_email );

				if ( $customer_info ) {
					// Customer with registered account
					$customers['customers'][$customer_count]['info']['id']           = $customer_info->ID;
					$customers['customers'][$customer_count]['info']['username']     = $customer_info->user_login;
					$customers['customers'][$customer_count]['info']['display_name'] = $customer_info->display_name;
					$customers['customers'][$customer_count]['info']['first_name']   = $customer_info->user_firstname;
					$customers['customers'][$customer_count]['info']['last_name']    = $customer_info->user_lastname;
					$customers['customers'][$customer_count]['info']['email']        = $customer_info->user_email;
				} else {
					// Guest customer
					$customers['customers'][$customer_count]['info']['id']           = -1;
					$customers['customers'][$customer_count]['info']['username']     = __( 'Guest', 'wpsc' );
					$customers['customers'][$customer_count]['info']['display_name'] = __( 'Guest', 'wpsc' );
					$customers['customers'][$customer_count]['info']['first_name']   = __( 'Guest', 'wpsc' );
					$customers['customers'][$customer_count]['info']['last_name']    = __( 'Guest', 'wpsc' );
					$customers['customers'][$customer_count]['info']['email']        = $customer_email;
				}

				$customers['customers'][$customer_count]['stats']['total_purchases'] = 0;
				$customers['customers'][$customer_count]['stats']['total_spent']     = 0;

				$customer_count++;
			}
		} else {
			if ( is_numeric( $customer ) ) {
				$customer_info = get_userdata( $customer );
			} else {
				$customer_info = get_user_by( 'email', $customer );
			}

			if ( $customer_info && wpsc_has_purchases( $customer_info->ID ) ) {
				$customers['customers'][0]['info']['id']               = $customer_info->ID;
				$customers['customers'][0]['info']['username']         = $customer_info->user_login;
				$customers['customers'][0]['info']['display_name']     = $customer_info->display_name;
				$customers['customers'][0]['info']['first_name']       = $customer_info->user_firstname;
				$customers['customers'][0]['info']['last_name']        = $customer_info->user_lastname;
				$customers['customers'][0]['info']['email']            = $customer_info->user_email;

				$customers['customers'][0]['stats']['total_purchases'] = 0;
				$customers['customers'][0]['stats']['total_spent']     = 0;
			} else {
				$this->errors['no_customer'] = sprintf( __( 'Customer %s not found!', 'wpsc' ), $customer );
			}
		}

		return $customers;
	}

	/**
	 * Process Get Products API Request
	 *
	 * @access public
	 * @since 3.9
	 * @param int $product Product (Download) ID
	 * @return array $customers Multidimensional array of the products
	 */
	public function get_products( $product = null ) {

		$products = array();
		$products['products'] = array();

		if( ! empty( $product ) ) {

			// Retrieve the specified product
			$p = get_post( $product );

			// No product found
			if( ! $p ) {

				$this->errors['no_product'] = sprintf( __( 'Product %s not found!', 'wpsc' ), $product );

			} elseif( 'wpsc-product' != $p->post_type ) {

				$this->errors['no_product'] = sprintf( __( 'Specified ID is not a WP e-Commerce product!', 'wpsc' ), $product );

			} else {

				$product_query = array(
					0 => $p
				);

			}

		} else {

			// Query multiple products
			$product_query = get_posts( array(
				'post_type'      => 'wpsc-product',
				'posts_per_page' => $this->per_page(),
				'paged'          => $this->get_paged()
			) );

			if( ! $product_query ) {
				$this->errors['no_products'] = __( 'No products found!', 'wpsc' );
			}

		}


		if ( empty( $this->errors ) ) {

			$i = 0;

			foreach ( $product_query as $product_info ) {

				$products['products'][$i]['info']['id']                           = $product_info->ID;
				$products['products'][$i]['info']['slug']                         = $product_info->post_name;
				$products['products'][$i]['info']['title']                        = $product_info->post_title;
				$products['products'][$i]['info']['create_date']                  = $product_info->post_date;
				$products['products'][$i]['info']['modified_date']                = $product_info->post_modified;
				$products['products'][$i]['info']['status']                       = $product_info->post_status;
				$products['products'][$i]['info']['link']                         = html_entity_decode( $product_info->guid );
				$products['products'][$i]['info']['description']                  = $product_info->post_content;
				$products['products'][$i]['info']['additional_description']       = $product_info->post_content;
				$products['products'][$i]['info']['thumbnail']                    = wp_get_attachment_url( get_post_thumbnail_id( $product_info->ID ) );
				$products['products'][$i]['info']['tags']                         = wp_get_product_tags( $product_info->ID );
				$products['products'][$i]['info']['categories']                   = wp_get_product_categories( $product_info->ID );
				$products['products'][$i]['info']['stock']                        = 0;
				$products['products'][$i]['info']['sku']                          = 0;
				$products['products'][$i]['info']['taxable_amount']               = 0;
				$products['products'][$i]['info']['external_link']                = array(
					'url'    => '',
					'text'   => '',
					'target' => ''
				);

				$products['products'][$i]['info']['featured_image']               = wp_get_attachment_url( get_post_thumbnail_id( $product_info->ID ) );
				/*
				 * TODO
				 *
				 * Do something with product images here
				 */

				/*
				 * TODO
				 *
				 * Do something with variations here
				 */


				/*
				 * TODO
				 *
				 * Do something with file downloads here
				 */

				/*
				 * TODO
				 *
				 * Retrieve products notes here, if relevant
				 */
				$products['products'][$i]['notes'] = '';


				/*
				 * TODO
				 *
				 * Retrieve custom meta fields here
				 */


				$products['products'][$i]['stats']['total']['earnings']           = 0;
				$products['products'][$i]['stats']['monthly_average']['sales']    = 0;
				$products['products'][$i]['stats']['monthly_average']['earnings'] = 0;

				$i++;
			}
		}

		return $products;

	}

	/**
	 * Process Get Stats API Request
	 *
	 * @access public
	 * @since 3.9
	 * @global object $wpdb Used to query the database using the WordPress
	 *   Database API
	 * @param array $args Arguments provided by API Request
	 */
	public function get_stats( $args = array() ) {
		$defaults = array(
			'type'      => null,
			'product'   => null,
			'date'      => null,
			'startdate' => null,
			'enddate'   => null
		);

		$args = wp_parse_args( $args, $defaults );

		$dates = $this->get_dates( $args );

		if ( $args['type'] == 'sales' ) {
			if ( $args['product'] == null ) {
				if ( $args['date'] == null ) {
					$sales = $this->get_default_sales_stats();
				} elseif( $args['date'] === 'range' ) {
					// Return sales for a date range

					// Ensure the end date is later than the start date
					if( $args['enddate'] < $args['startdate'] ) {
						$this->errors['invalid_end_date'] = __( 'The end date must be later than the start date!', 'wpsc' );
					}

					// Ensure both the start and end date are specified
					if ( empty( $args['startdate'] ) || empty( $args['enddate'] ) ) {
						$this->errors['invalid_date_range'] = __( 'Invalid or no date range specified!', 'wpsc' );
					}

					$total = 0;

					// Loop through the years
					$year = $dates['year'];
					while( $year <= $dates['year_end' ] ) :
						// Loop through the months
						$month = $dates['m_start'];

						while( $month <= $dates['m_end'] ) :
							// Loop through the days
							$day           = $month > $dates['m_start'] ? 1 : $dates['day_start'];
							$days_in_month = cal_days_in_month( CAL_GREGORIAN, $month, $year );

							while( $day <= $days_in_month ) :
								$sale_count = 0; // TODO - get sales by date, something like wpsc_get_sales_by_date( $day, $month, $year );
								$sales['sales'][ date( 'Ymd', strtotime( $year . '/' . $month . '/' . $day ) ) ] = $sale_count;
								$total += $sale_count;

								$day++;
							endwhile;

							$month++;
						endwhile;

						$year++;
					endwhile;

					$sales['totals'] = $total;
				} else {
					if( $args['date'] == 'this_quarter' || $args['date'] == 'last_quarter'  ) {
   						$sales_count = 0;

						// Loop through the months
						$month = $dates['m_start'];

						while( $month <= $dates['m_end'] ) :
							$sales_count += 0; // TODO - get sales by date, something like wpsc_get_sales_by_date( $day, $month, $year );
							$month++;
						endwhile;

						$sales['sales'][ $args['date'] ] = $sales_count;
   					} else {
						$sales['sales'][ $args['date'] ] = 0; // TODO - get sales by date, something like wpsc_get_sales_by_date( $day, $month, $year );
   					}
				}
			} elseif ( $args['product'] == 'all' ) {
				$products = get_posts( array( 'post_type' => 'wpsc-product', 'nopaging' => true ) );
				$i = 0;
				foreach ( $products as $product_info ) {
					$sales['sales'][$i] = array(
						$product_info->post_name => 0 // TODO - get sales by date, something like wpsc_get_product_sales_stats( $args['product'] )
					);
					$i++;
				}
			} else {
				if ( get_post_type( $args['product'] ) == 'wpsc-product' ) {
					$product_info = get_post( $args['product'] );
					$sales['sales'][0] = array(
						$product_info->post_name => 0 // TODO get sale stats for product, something like wpsc_get_product_sales_stats( $args['product'] )
					);
				} else {
					$this->errors['no_product'] = sprintf( __( 'Product %s not found!', 'wpsc' ), $args['product'] );
				}
			}

			if ( ! empty( $error ) )
				return $error;

			return $sales;

		} elseif ( $args['type'] == 'earnings' ) {

			if ( $args['product'] == null ) {

				if ( $args['date'] == null ) {

					$earnings = $this->get_default_earnings_stats();

				} elseif ( $args['date'] === 'range' ) {
					// Return sales for a date range

					// Ensure the end date is later than the start date
					if ( $args['enddate'] < $args['startdate'] ) {
						$this->errors['invalid_end_date'] = __( 'The end date must be later than the start date!', 'wpsc' );
					}

					// Ensure both the start and end date are specified
					if ( empty( $args['startdate'] ) || empty( $args['enddate'] ) ) {
						$this->errors['invalid_date_range'] = __( 'Invalid or no date range specified!', 'wpsc' );
					}

					$total = (float) 0.00;

					// Loop through the years
					$year = $dates['year'];
					while ( $year <= $dates['year_end' ] ) :
						// Loop through the months
						$month = $dates['m_start'];

						while( $month <= $dates['m_end'] ) :
							// Loop through the days
							$day           = $month > $dates['m_start'] ? 1 : $dates['day_start'];
							$days_in_month = cal_days_in_month( CAL_GREGORIAN, $month, $year );

							while( $day <= $days_in_month ) :
								$sale_count = 0; // TODO something like wpsc_get_earnings_by_date( $day, $month, $year );
								$earnings['earnings'][ date( 'Ymd', strtotime( $year . '/' . $month . '/' . $day ) ) ] = $sale_count;
								$total += $sale_count;

								$day++;
							endwhile;

							$month++;
						endwhile;

						$year++;
					endwhile;

					$earnings['totals'] = $total;
				} else {
					if ( $args['date'] == 'this_quarter' || $args['date'] == 'last_quarter'  ) {
   						$earnings_count = (float) 0.00;

						// Loop through the months
						$month = $dates['m_start'];

						while ( $month <= $dates['m_end'] ) :
							$earnings_count += 0; // TODO something like wpsc_get_earnings_by_date( $day, $month, $year );
							$month++;
						endwhile;

						$earnings['earnings'][ $args['date'] ] = $earnings_count;
   					} else {
						$earnings['earnings'][ $args['date'] ] = 0; // TODO something like wpsc_get_earnings_by_date( $day, $month, $year );
   					}
				}
			} elseif ( $args['product'] == 'all' ) {
				$products = get_posts( array( 'post_type' => 'download', 'nopaging' => true ) );

				$i = 0;
				foreach ( $products as $product_info ) {
					$earnings['earnings'][ $i ] = array(
						$product_info->post_name => 0 // TODO get sale stats for product, something like wpsc_get_product_earnings_stats( $args['product'] )
					);
					$i++;
				}
			} else {
				if ( get_post_type( $args['product'] ) == 'download' ) {
					$product_info = get_post( $args['product'] );
					$earnings['earnings'][0] = array(
						$product_info->post_name => 0 // TODO get sale stats for product, something like wpsc_get_product_earnings_stats( $args['product'] )
					);
				} else {
					$this->errors['no_product'] = sprintf( __( 'Product %s not found!', 'wpsc' ), $args['product'] );
				}
			}

			if ( ! empty( $error ) )
				return $error;

			return $earnings;

		} elseif ( $args['type'] == 'customers' ) {

			global $wpdb;

			$stats = array();

			//
			$count = $wpdb->get_col( "SELECT COUNT(DISTINCT meta_value) FROM $wpdb->postmeta WHERE meta_key = '_wpsc_payment_user_email'" );

			$stats['customers']['total_customers'] = $count[0];

			return $stats;

		} elseif ( empty( $args['type'] ) ) {

			$stats = array();
			$stats = array_merge( $stats, $this->get_default_sales_stats() );
			$stats = array_merge ( $stats, $this->get_default_earnings_stats() );

			return array( 'stats' => $stats );
		}
	}

	/**
	 * Retrieves Recent Sales
	 *
	 * @access public
	 * @since  3.9
	 * @return array
	 */
	public function get_recent_sales() {
		$sales = array();

		// TODO - get payments
		$query = array();

		/* Example:
		$query = wpsc_get_payments( array(
			'number' => $this->per_page(),
			'page'   => $this->get_paged(),
			'status' => 'publish'
		) );
		*/

		if ( $query ) {
			$i = 0;
			foreach ( $query as $payment ) {

				$cart_items            = array(); // TODO - get cart items for a purchase

				$sales['sales'][ $i ]['ID']       = $payment->ID;
				$sales['sales'][ $i ]['subtotal'] = 0;
				$sales['sales'][ $i ]['tax']      = 0;
				$sales['sales'][ $i ]['fees']     = 0;
				$sales['sales'][ $i ]['total']    = 0;
				$sales['sales'][ $i ]['gateway']  = 0;
				$sales['sales'][ $i ]['email']    = 0;
				$sales['sales'][ $i ]['date']     = $payment->post_date;
				$sales['sales'][ $i ]['products'] = array();

				$c = 0;

				foreach ( $cart_items as $key => $item ) {

					// TODO - loop through all products in the cart and ad them here

					$sales['sales'][ $i ]['products'][ $c ]['name'] = get_the_title( $item['id'] );
					$c++;
				}

				$i++;
			}
		}
		return $sales;
	}

	/**
	 * Process Get coupons API Request
	 *
	 * @access public
	 * @since 1.6
	 * @global object $wpdb Used to query the database using the WordPress
	 *   Database API
	 * @param int $coupon coupon ID
	 * @return array $coupons Multidimensional array of the coupons
	 */
	public function get_coupons( $coupon = null ) {

		if ( empty( $coupon ) ) {

			global $wpdb;

			$paged     = $this->get_paged();
			$per_page  = $this->per_page();
			$coupons = array(); // TODO - get coupon codes
			$count     = 0;

			foreach ( $coupons as $coupon ) {

				$coupon_list['coupons'][$count]['ID']                    = '';
				$coupon_list['coupons'][$count]['name']                  = '';
				$coupon_list['coupons'][$count]['code']                  = '';
				$coupon_list['coupons'][$count]['amount']                = '';
				$coupon_list['coupons'][$count]['min_price']             = '';
				$coupon_list['coupons'][$count]['type']                  = '';
				$coupon_list['coupons'][$count]['uses']                  = '';
				$coupon_list['coupons'][$count]['max_uses']              = '';
				$coupon_list['coupons'][$count]['start_date']            = '';
				$coupon_list['coupons'][$count]['exp_date']              = '';
				$coupon_list['coupons'][$count]['status']                = '';
				$coupon_list['coupons'][$count]['conditions']            = '';
				$coupon_list['coupons'][$count]['single_use']            = '';

				$count++;
			}

		} else {

			if ( is_numeric( $coupon ) && get_post( $coupon ) ) {

				$coupon_list['coupons'][0]['ID']                         = '';
				$coupon_list['coupons'][0]['name']                       = '';
				$coupon_list['coupons'][0]['code']                       = '';
				$coupon_list['coupons'][0]['amount']                     = '';
				$coupon_list['coupons'][0]['min_price']                  = '';
				$coupon_list['coupons'][0]['type']                       = '';
				$coupon_list['coupons'][0]['uses']                       = '';
				$coupon_list['coupons'][0]['max_uses']                   = '';
				$coupon_list['coupons'][0]['start_date']                 = '';
				$coupon_list['coupons'][0]['exp_date']                   = '';
				$coupon_list['coupons'][0]['status']                     = '';
				$coupon_list['coupons'][0]['conditions']                 = '';
				$coupon_list['coupons'][0]['single_use']                 = '';

			} else {

				$this->errors['no_coupon'] = sprintf( __( 'coupon %s not found!', 'wpsc' ), $coupon );
				return $error;

			}

		}

		return $coupon_list;
	}


	/**
	 * Log each API request, if enabled
	 *
	 * @access private
	 * @since  3.9
	 * @global $wpsc_logs
	 * @global $wp_query
	 * @param array $data
	 * @return void
	 */
	private function log_request( $data = array() ) {

		if ( ! $this->log_requests )
			return;

		global $wpsc_logs, $wp_query;

		$query = array(
			'key'       => $wp_query->query_vars['key'],
			'query'     => isset( $wp_query->query_vars['query'] )     ? $wp_query->query_vars['query']     : null,
			'type'      => isset( $wp_query->query_vars['type'] )      ? $wp_query->query_vars['type']      : null,
			'product'   => isset( $wp_query->query_vars['product'] )   ? $wp_query->query_vars['product']   : null,
			'customer'  => isset( $wp_query->query_vars['customer'] )  ? $wp_query->query_vars['customer']  : null,
			'date'      => isset( $wp_query->query_vars['date'] )      ? $wp_query->query_vars['date']      : null,
			'startdate' => isset( $wp_query->query_vars['startdate'] ) ? $wp_query->query_vars['startdate'] : null,
			'enddate'   => isset( $wp_query->query_vars['enddate'] )   ? $wp_query->query_vars['enddate']   : null,
		);

		$log_data = array(
			'log_type'     => 'api_request',
			'post_excerpt' => http_build_query( $query ),
			'post_content' => ! empty( $data['error'] ) ? $data['error'] : '',
		);

		$log_meta = array(
			'request_ip' => '', // TODO get the IP of the API request
			'user'       => $this->user_id,
			'key'        => $wp_query->query_vars['key']
		);

		// TODO - implement once WPEC logging system is built
		//$wpsc_logs->insert_log( $log_data, $log_meta );
	}


	/**
	 * Retrieve the output data
	 *
	 * @access public
	 * @since 3.9
	 * @return array
	 */
	public function get_output() {
		if( ! empty( $this->errors ) ) {
			$this->data = array();
			foreach( $this->errors as $error_id => $error ) {
				$this->data['errors'][ $error_id ] = $error;
			}
		}
		return $this->data;
	}


	/**
	 * Output Query in either JSON/XML. The query data is outputted as JSON
	 * by default
	 *
	 * @access public
	 * @since 3.9
	 * @global $wp_query
	 * @param array $data
	 * @return void
	 */
	public function output() {
		global $wp_query;

		$data = $this->get_output();

		$format = $this->get_output_format();

		do_action( 'wpsc_api_output_before', $data, $this, $format );

		switch( $format ) :

			case 'xml' :

				require_once WPSC_FILE_PATH . '/wpsc-includes/array2xml.php';
				$xml = Array2XML::createXML( 'wpsc', $data );
				echo $xml->saveXML();

				break;

			case 'json' :

				header( 'Content-Type: application/json' );
				if ( ! empty( $this->pretty_print ) )
					echo json_encode( $data, $this->pretty_print );
				else
					echo json_encode( $data );

				break;


			default :

				// Allow other formats to be added via extensions
				do_action( 'wpsc_api_output_' . $format, $data, $this );

				break;

		endswitch;

		do_action( 'wpsc_api_output_after', $data, $this, $format );

		die();
	}

}
add_action( 'wpsc_init', array( 'WPSC_REST_API', 'get_instance' ) );