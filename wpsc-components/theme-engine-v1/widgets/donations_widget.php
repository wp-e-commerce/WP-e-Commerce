<?php



/**
 * Donations widget class
 *
 * @since 3.8
 */
class WP_Widget_Donations extends WP_Widget {

	/**
	 * Widget Constuctor
	 */
	function WP_Widget_Donations() {

		$widget_ops = array(
			'classname'   => 'widget_wpsc_donations',
			'description' => __( 'Donations Widget', 'wpsc' )
		);

		$this->WP_Widget( 'wpsc_donations', __( '(WPEC) Product Donations', 'wpsc' ), $widget_ops );

	}

	/**
	 * Widget Output
	 *
	 * @param $args (array)
	 * @param $instance (array) Widget values.
	 *
	 */
	function widget( $args, $instance ) {

		global $wpdb, $table_prefix;

		extract( $args );

		$donation_count = $wpdb->get_var( "SELECT COUNT(DISTINCT `p`.`ID`) AS `count`
			FROM `" . $wpdb->postmeta . "` AS `m`
			JOIN `" . $wpdb->posts . "` AS `p` ON `m`.`post_id` = `p`.`ID`
			WHERE `p`.`post_parent` IN ('0')
				AND `m`.`meta_key` IN ('_wpsc_is_donation')
				AND `m`.`meta_value` IN( '1' )
				AND `p`.`post_status` = 'publish'" );

		if ( $donation_count > 0 ) {
			echo $before_widget;
			$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Product Donations', 'wpsc'  ) : $instance['title'] );
			if ( $title ) {
				echo $before_title . $title . $after_title;
			}
			wpsc_donations();
			echo $after_widget;
		}

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
		$instance = wp_parse_args( (array)$instance, array(
			'title' => __( 'Product Donations', 'wpsc' )
		) );

		// Values
		$title = esc_attr( $instance['title'] );

		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'wpsc'  ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<?php

	}

}

add_action( 'widgets_init', create_function( '', 'return register_widget("WP_Widget_Donations");' ) );



/*
 * Specials Widget content function
 * Displays the products
 *
 * @todo make this use wp_query and a theme file if possible
 *
 * Changes made in 3.8 that may affect users:
 *
 * 1. $input does not get prepended to output.
 */
function wpsc_donations( $args = null ) {

	global $wpdb;

	// Args not used yet but this is ready for when it is
	$args = wp_parse_args( (array)$args, array() );

	$products = $wpdb->get_results( "SELECT DISTINCT `p` . * , `m`.`meta_value` AS `special_price`
		FROM `" . $wpdb->postmeta . "` AS `m`
		JOIN `" . $wpdb->posts . "` AS `p` ON `m`.`post_id` = `p`.`ID`
		WHERE `p`.`post_parent` IN ('0')
			AND `m`.`meta_key` IN ('_wpsc_is_donation')
			AND `m`.`meta_value` IN( '1' )
		ORDER BY RAND( )
		LIMIT 1", ARRAY_A );

	$output = '';

	if ( $products != null ) {
		foreach ( $products as $product ) {
			$attached_images = (array)get_posts( array(
				'post_type'   => 'attachment',
				'numberposts' => 1,
				'post_status' => null,
				'post_parent' => $product['ID'],
				'orderby'     => 'menu_order',
				'order'       => 'ASC'
			) );
			$attached_image = $attached_images[0];
			$output .= "<div class='wpsc_product_donation'>";
			if ( ( $attached_image->ID > 0 ) ) {
				$output .= "<img src='" . wpsc_product_image( $attached_image->ID, get_option( 'product_image_width' ), get_option( 'product_image_height' ) ) . "' title='" . $product['post_title'] . "' alt='" . esc_attr( $product['post_title'] ) . "' /><br />";
			}

			// Get currency options
			$currency_sign_location = get_option( 'currency_sign_location' );
			$currency_type = get_option( 'currency_type' );
			WPSC_Countries::currency_symbol( $currency_type );
			$price = get_post_meta(  $product['ID'] , '_wpsc_price', true );
			// Output
			$output .= "<strong>" . $product['post_title'] . "</strong><br />";
			$output .= $product['post_content'] . "<br />";
			$output .= "<form class='product_form'  name='donation_widget_" . $product['ID'] . "' method='post' action='' id='donation_widget_" . $product['ID'] . "'>";
			$output .= "<input type='hidden' name='product_id' value='" . $product['ID'] . "'/>";
			$output .= "<input type='hidden' name='item' value='" . $product['ID'] . "' />";
			$output .= "<input type='hidden' name='wpsc_ajax_action' value='add_to_cart' />";
			$output .= "<label for='donation_widget_price_" . $product['ID'] . "'>" . __( 'Donation', 'wpsc' ) . ":</label> $currency_symbol<input type='text' id='donation_widget_price_" . $product['ID'] . "' name='donation_price' value='" . esc_attr( number_format( $price, 2 ) ) . "' size='6' /><br />";
			$output .= "<input type='submit' id='donation_widget_" . $product['ID'] . "_submit_button' name='Buy' class='wpsc_buy_button' value='" . __( 'Add To Cart', 'wpsc' ) . "' />";
			$output .= "</form>";
			$output .= "</div>";
		}
	} else {
		$output = '';
	}

	echo $output;

}



?>