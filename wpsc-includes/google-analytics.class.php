<?php

/**
 *
 * Google Analytics class for WP E-Commerce.
 *
 * No longer dependent upon Google Analytics for WordPress, we have a great internal analytics class that actually works now.
 *
 * @since 3.8.9
 * @package wp-e-commerce
 */
class WPSC_Google_Analytics {

	private $is_analytics_disabled = false;
	private $is_theme_tracking     = false;
	private $advanced_code         = false;
	private $tracking_id           = '';

	public function __construct() {
		$this->is_theme_tracking     = (bool) get_option( 'wpsc_ga_currently_tracking' );
		$this->advanced_code         = (bool) get_option( 'wpsc_ga_advanced' );
		$this->tracking_id           = esc_attr( get_option( 'wpsc_ga_tracking_id' ) );
		$this->is_analytics_disabled =
			   (bool) get_option( 'wpsc_ga_disable_tracking' )
			|| ( ! $this->is_theme_tracking && empty( $this->tracking_id ) );

		// TODO: make it work with new theme engine as well
		if ( ! $this->is_analytics_disabled )
			add_action( 'wpsc_transaction_results_shutdown', array( $this, 'print_script' ), 10, 3 );
	}

	/**
	 * Sanitizes strings for Google Analytics.
	 * Gratefully borrowed and modified from Google Analytics for WordPress
	 *
	 * @param string $string
	 * @since 3.8.9
	 * @return string
	 */
	public function sanitize( $string ) {

		return remove_accents( str_replace( '---', '-', str_replace( ' ', '-', strtolower( html_entity_decode( $string, ENT_QUOTES, get_option( 'blog_charset' ) ) ) ) ) );
	}

	/**
	 * Builds out the proper script for tracking.
	 *
	 * Checks options to ensure we're actually supposed to be building the script, and which part of the script to build.
	 * If analytics are disabled, we build nothing.
	 * If the site already is tracking OR using the advanced option, we insert only the e-commerce portion, not the initial tracking info.
	 *
	 * @param $purchase_log      Purchase Log object
	 * @param $session_id        Session ID
	 * @param $display_to_screen Whether or not the output is displayed to the screen
	 *
	 * @since 3.8.9
	 * @return javascript
	 */
	public function print_script( $purchase_log, $session_id, $display_to_screen ) {

		if ( ! $display_to_screen )
			return false;

		$output = '';

		if ( $this->is_analytics_disabled )
			return $output;

		if ( ! $this->is_theme_tracking && ! $this->advanced_code )
			$output .= $this->general_init();

		$output .= $this->add_pushes( $session_id );

		if ( ! $this->is_theme_tracking && ! $this->advanced_code )
			$output .= $this->general_shutdown();

		echo $output;
	}

	public function general_init() {
		return "<script type='text/javascript'>\n\r
		var _gaq = _gaq || [];\n\r
		_gaq.push(['_setAccount', '" . $this->tracking_id ."']);\n\r
		_gaq.push(['_setDomainName', '" . $this->get_domain_name() . "']);\n\r
		_gaq.push(['_trackPageview']);\n\r";
	}

	public function general_shutdown() {
		return "(function() {\n\r
		    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;\n\r
		    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';\n\r
		    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);\n\r
		  })();\n\r
		</script>\n\r";
	}

	public function get_domain_name() {

		$site_url = $this->sanitize( str_replace( array( 'https://www.', 'http://www.', 'https://', 'http://',  ), '', untrailingslashit( network_home_url() ) ) );

		return apply_filters( 'wpsc_google_analytics_domain_name', $site_url );
	}

	public function get_site_name() {

		$site_name = $this->sanitize( get_bloginfo( 'name' ) );

		return apply_filters( 'wpsc_google_analytics_site_name', $site_name );
	}

	public function remove_currency_and_html( $args ) {

		$args['display_currency_symbol'] = false;
		$args['display_as_html']         = false;

		return $args;

	}
	public function add_pushes( $session_id ) {
		global $wpdb;

		$purchase = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `sessionid`= %s LIMIT 1", $session_id ) );
		$purchase_id = $purchase->id;
		$output = '';

		$city = $wpdb->get_var( $wpdb->prepare( "
						SELECT tf.value FROM " . WPSC_TABLE_SUBMITTED_FORM_DATA . " tf
						LEFT JOIN " . WPSC_TABLE_CHECKOUT_FORMS . " cf
						ON cf.id = tf.form_id
						WHERE cf.unique_name = 'billingcity'
						AND log_id = %d", $purchase_id ) );

		$state = $wpdb->get_var( $wpdb->prepare( "
						SELECT tf.value
						FROM " . WPSC_TABLE_SUBMITTED_FORM_DATA . " tf
						LEFT JOIN " . WPSC_TABLE_CHECKOUT_FORMS . " cf
						ON cf.id = tf.form_id
						WHERE cf.unique_name = 'billingstate'
						AND log_id = %d", $purchase_id ) );

		$country = $wpdb->get_var( $wpdb->prepare( "
						SELECT tf.value
						FROM " . WPSC_TABLE_SUBMITTED_FORM_DATA . " tf
						LEFT JOIN " . WPSC_TABLE_CHECKOUT_FORMS . " cf
						ON cf.id = tf.form_id
						WHERE cf.unique_name = 'billingcountry'
						AND log_id = %d", $purchase_id ) );

		$city    = ! empty( $city )    ? $city : '';
		$state   = ! empty( $state )   ? wpsc_get_state_by_id( $state, 'name' ) : '';
		$country = ! empty( $country ) ? $country : '';

		$cart_items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . WPSC_TABLE_CART_CONTENTS . " WHERE purchaseid = %d", $purchase_id ), ARRAY_A );

		$total_shipping = wpsc_get_total_shipping( $purchase_id );
		$total_tax = $total_price = 0;

		foreach ( $cart_items as $item ) {
			$total_tax	+=  $item['tax_charged'];
			$total_price += $item['price'];
		}

		if ( $this->is_theme_tracking || $this->advanced_code )
			$output .= "<script type='text/javascript'>\n\r";

		add_filter( 'wpsc_toggle_display_currency_code', array( $this, 'remove_currency_and_html' ) );

		$output .= "
			_gaq.push(['_addTrans',
			'" . $purchase_id . "',                                     // order ID - required
			'" . wp_specialchars_decode( $this->get_site_name() ) . "', // affiliation or store name
			'" . number_format( $total_price, 2, '.', '' ) . "',   // total - required
			'" . wpsc_currency_display( $total_tax ) . "',              // tax
			'" . wpsc_currency_display( $total_shipping ) . "',         // shipping
			'" . wp_specialchars_decode( $city ) . "',                  // city
			'" . wp_specialchars_decode( $state ) . "',                 // state or province
			'" . wp_specialchars_decode( $country ) . "'                // country
  		]);\n\r";

		remove_filter( 'wpsc_toggle_display_currency_code', array( $this, 'remove_currency_and_html' ) );

		foreach( $cart_items as $item ) {

			$category = wp_get_object_terms(
				$item['prodid'],
				'wpsc_product_category',
				array( 'orderby' => 'count', 'order' => 'DESC', 'fields' => 'all_with_object_id' ) );

			$item['sku']      = get_post_meta( $item['prodid'], '_wpsc_sku', true );
			if ( $category )
				$item['category'] = $category[0]->name;
			else
				$item['category'] = '';

			$item = array_map( 'wp_specialchars_decode', $item );

			$output .= "_gaq.push(['_addItem',"
			. "'" . $purchase_id . "',"		    // Order ID
			. "'" . $item['sku'] . "',"			// Item SKU
			. "'" . $item['name'] . "',"		// Item Name
			. "'" . $item['category'] . "',"	// Item Category
			. "'" . $item['price'] . "',"		// Item Price
			. "'" . $item['quantity'] . "']);\n\r";	// Item Quantity
		}

		$output .= "_gaq.push(['_trackTrans']);\n\r";

		if ( $this->is_theme_tracking || $this->advanced_code )
			$output .= "</script>\n\r";

		return $output;
	}

}

$GLOBALS['wpsc_google_analytics'] = new WPSC_Google_Analytics();

//For some reason, this function exists in the 3.8 branch on GC, but not in WP Extend.
if ( ! function_exists( 'wpsc_get_total_shipping' ) ) :

	/**
	 * New helper function for grabbing the total shipping of a purchase log
	 * @param int $purchase_id
	 * @return float shipping price
	 */
	function wpsc_get_total_shipping( $purchase_id ) {
		global $wpdb;

		$per_item_shipping = $wpdb->get_col( $wpdb->prepare( 'SELECT pnp FROM ' . WPSC_TABLE_CART_CONTENTS . " WHERE purchaseid = %d", $purchase_id ) );
		$base_shipping     = $wpdb->get_var( $wpdb->prepare( 'SELECT base_shipping FROM ' . WPSC_TABLE_PURCHASE_LOGS . " WHERE id = %d", $purchase_id ) );

		$total_shipping    = 0.00;

		$per_item_shipping = array_sum( $per_item_shipping );

		$total_shipping    = $base_shipping + $per_item_shipping;

		return $total_shipping;
	}

endif;

?>
