<?php



/**
 * Admin Menu widget class
 *
 * @since 3.8
 */
class WP_Widget_Price_Range extends WP_Widget {
	
	/**
	 * Widget Constuctor
	 */
	function WP_Widget_Price_Range() {

		$widget_ops = array(
			'classname'   => 'widget_wpsc_price_range',
			'description' => __( 'Price Range Widget', 'wpsc' )
		);
		
		$this->WP_Widget( 'wpsc_price_range', __( 'Price Range', 'wpsc' ), $widget_ops );
	
	}

	/**
	 * Widget Output
	 *
	 * @param $args (array)
	 * @param $instance (array) Widget values.
	 *
	 * @todo Add individual capability checks for each menu item rather than just manage_options.
	 */
	function widget( $args, $instance ) {
		
		global $wpdb, $table_prefix;
		
		extract( $args );
	
		echo $before_widget;
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Price Range', 'wpsc' ) : $instance['title'] );
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}
		wpsc_price_range();
		echo $after_widget;
	
	}

	/**
	 * Update Widget
	 *
	 * @param $new_instance (array) New widget values.
	 * @param $old_instance (array) Old widget values.
	 *
	 * @return (array) New values.
	 */
	function update( $new_instance, $old_instance ) {
	
		$instance = $old_instance;
		$instance['title']  = strip_tags( $new_instance['title'] );

		return $instance;
		
	}

	/**
	 * Widget Options Form
	 *
	 * @param $instance (array) Widget values.
	 */
	function form( $instance ) {
		
		global $wpdb;
		
		// Defaults
		$instance = wp_parse_args( (array)$instance, array( 'title' => '' ) );
		
		// Values
		$title  = esc_attr( $instance['title'] );
		
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'wpsc' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<?php
		
	}

}

add_action( 'widgets_init', create_function( '', 'return register_widget("WP_Widget_Price_Range");' ) );

/**
 * Price Range Widget content function
 *
 * Displays a list of price ranges.
 *
 * @param $args (array) Arguments.
 */
function wpsc_price_range( $args = null ) {

	global $wpdb;
	
	// Filter args not used at the moment, but this is here ready
	$args = wp_parse_args( (array)$args, array() );
	
	$siteurl = get_option( 'siteurl' );
	$product_page = get_option( 'product_list_url' );
	$result = $wpdb->get_results( "SELECT DISTINCT CAST(`meta_value` AS DECIMAL) AS `price` FROM " . $wpdb->postmeta . " AS `m` WHERE `meta_key` IN ('_wpsc_price') ORDER BY `price` ASC", ARRAY_A );
	
	if ( $result != null ) {
		sort( $result );
		$count = count( $result );
		$price_seperater = ceil( $count / 6 );
		for ( $i = 0; $i < $count; $i += $price_seperater ) {
			$ranges[] = round( $result[$i]['price'], -1 );
		}
		$ranges = array_unique( $ranges );
		
		$final_count = count( $ranges );
		$ranges = array_merge( array(), $ranges );
		$_SESSION['price_range'] = $ranges;
		echo '<ul>';
		for ( $i = 0; $i < $final_count; $i++ ) {
			$j = $i;
			if ( $i == $final_count - 1 ) {
				echo "<li><a href='" . esc_url(add_query_arg( 'range', $ranges[$i] . '-', $product_page )) . "'>" . esc_html_x( 'Over ', 'price range widget', 'wpsc' ) . wpsc_currency_display( $ranges[$i] ). "</a></li>";
			} else if ( $ranges[$i] == 0 ) {
				echo "<li><a href='" . esc_url(add_query_arg( 'range', '-' . ($ranges[$i+1]-1), $product_page )) . "'>" . esc_html_x( 'Under ', 'price range widget', 'wpsc' ) . wpsc_currency_display( $ranges[$i + 1] ). "</a></li>";
			} else {
				echo "<li><a href='" . esc_url(add_query_arg( 'range', $ranges[$i] . "-" . ($ranges[$i + 1]-1), $product_page )) . "'>" . wpsc_currency_display( $ranges[$i] ) . " - " . wpsc_currency_display(  ($ranges[$i + 1]-1) ) . "</a></li>";
			}
		}
		echo "<li><a href='" . esc_url(add_query_arg( 'range', 'all', get_option( 'product_list_url' )) ) . "'>" . _x( 'Show All', 'price range widget', 'wpsc' ) . "</a></li>";
		echo '</ul>';
	}
	
}

if(isset($_GET['range'])){
	add_filter( 'posts_where', 'wpsc_range_where' );
}
function wpsc_rage_where( $where ) {
    _deprecated_function( __FUNCTION__, '3.8.8', 'wpsc_range_where()' );
    
    return wpsc_range_where( $where );
    
}
function wpsc_range_where( $where ) {
	global $wpdb, $wp_query;
	$range = explode('-', $_GET['range']);
	if(!strpos($where,'wpsc-product'))
		return $where;
	if(!$range[0]){
		$where .= " AND $wpdb->posts.id IN ( SELECT $wpdb->posts.id FROM $wpdb->posts JOIN $wpdb->postmeta on $wpdb->postmeta.post_id = $wpdb->posts.id WHERE $wpdb->postmeta.meta_key=\"_wpsc_price\" AND $wpdb->postmeta.meta_value < " . ( $range[1] + 1 ) . ") ";
	}elseif(!$range[1]){
		$where .= " AND $wpdb->posts.id IN ( SELECT $wpdb->posts.id FROM $wpdb->posts JOIN $wpdb->postmeta on $wpdb->postmeta.post_id = $wpdb->posts.id WHERE $wpdb->postmeta.meta_key=\"_wpsc_price\" AND $wpdb->postmeta.meta_value > " . ( $range[0]-1 ) . ") ";
	}elseif($range[1] && $range[0]){
		$where .= " AND $wpdb->posts.id IN ( SELECT $wpdb->posts.id FROM $wpdb->posts JOIN $wpdb->postmeta on $wpdb->postmeta.post_id = $wpdb->posts.id WHERE $wpdb->postmeta.meta_key=\"_wpsc_price\" AND $wpdb->postmeta.meta_value > " . ( $range[0]-1 ) . " AND $wpdb->postmeta.meta_value < " . ( $range[1] + 1 ) . ") ";	
	}
	return $where;
}
?>