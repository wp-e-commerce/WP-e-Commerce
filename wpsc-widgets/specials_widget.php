<?php
/**
 * Admin Menu widget class
 *
 * @since 3.8
 *
 * @todo Special count does not work when figuring out wether to show widget.
 * @todo Add option to set how many products show?
 */
class WP_Widget_Product_Specials extends WP_Widget {

	/**
	 * Widget Constuctor
	 */
	function WP_Widget_Product_Specials() {

		$widget_ops = array(
			'classname'   => 'widget_wpsc_product_specials',
			'description' => __( 'Product Specials Widget', 'wpsc' )
		);

		$this->WP_Widget( 'wpsc_product_specials', __( 'Product Specials', 'wpsc' ), $widget_ops );

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
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Product Specials', 'wpsc' ) : $instance['title'] );
		if ( $title )
			echo $before_title . $title . $after_title;

		wpsc_specials($args, $instance);
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
		$instance['number'] = (int)$new_instance['number'];
		$instance['show_thumbnails'] = (bool)$new_instance['show_thumbnails'];
		$instance['show_description']  = (bool)$new_instance['show_description'];

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
			'title' => '',
			'show_description' => false,
			'show_thumbnails' => false,
			'number' => 5
		) );

		// Values
		$title = esc_attr( $instance['title'] );
		$number = (int)$instance['number'];
		$show_thumbnails = (bool)$instance['show_thumbnails'];
		$show_description = (bool)$instance['show_description'];

		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'wpsc' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of products to show:', 'wpsc' ); ?></label>
			<input type="text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" value="<?php echo $number; ?>" size="3" />
		</p>
		<p>
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_description' ); ?>" name="<?php echo $this->get_field_name( 'show_description' ); ?>" <?php echo $show_description ? 'checked="checked"' : ""; ?>>
			<label for="<?php echo $this->get_field_id( 'show_description' ); ?>"><?php _e( 'Show Description', 'wpsc' ); ?></label><br />
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_thumbnails' ); ?>" name="<?php echo $this->get_field_name( 'show_thumbnails' ); ?>" <?php echo $show_thumbnails ? 'checked="checked"' : ""; ?>>
			<label for="<?php echo $this->get_field_id( 'show_thumbnails' ); ?>"><?php _e( 'Show Thumbnails', 'wpsc' ); ?></label>
		</p>
<?php
	}

}

add_action( 'widgets_init', create_function( '', 'return register_widget("WP_Widget_Product_Specials");' ) );



/**
 * Product Specials Widget content function
 *
 * Displays the latest products.
 *
 * @todo Remove marketplace theme specific code and maybe replce with a filter for the image output? (not required if themeable as above)
 *
 * Changes made in 3.8 that may affect users:
 *
 * 1. The product title link text does now not have a bold tag, it should be styled via css.
 * 2. <br /> tags have been ommitted. Padding and margins should be applied via css.
 * 3. Each product is enclosed in a <div> with a 'wpec-special-product' class.
 * 4. The product list is enclosed in a <div> with a 'wpec-special-products' class.
 * 5. Function now expect a single paramter with an array of options (used to be a string which prepended the output).
 */

function wpsc_specials( $args = null, $instance ) {

	global $wpdb;

	$args = wp_parse_args( (array)$args, array( 'number' => 5 ) );

	$siteurl = get_option( 'siteurl' );

	if ( !$number = (int) $instance['number'] )
		$number = 5;

	$show_thumbnails  = isset($instance['show_thumbnails']) ? (bool)$instance['show_thumbnails'] : FALSE;
	$show_description  = isset($instance['show_description']) ? (bool)$instance['show_description'] : FALSE;

	$excludes = wpsc_specials_excludes();
	$args = array(
		'post_type'   		=> 'wpsc-product',
		'caller_get_posts' 	=> 1,
		'post_status' 		=> 'publish',
		'post_parent'		=> 0,
		'post__not_in' 		=> $excludes,
		'posts_per_page' 	=> $number
	) ;
	$special_products = query_posts( $args );
	$output = '';
	$product_ids[] = array();
	if ( count( $special_products ) > 0 ) {
		list( $wp_query, $special_products ) = array( $special_products, $wp_query ); // swap the wpsc_query object
		while ( wpsc_have_products() ) : wpsc_the_product();

				if(!in_array(wpsc_the_product_id(),$product_ids)):
				$product_ids[] = wpsc_the_product_id();
				if( $show_thumbnails ):
				 if ( wpsc_the_product_thumbnail() ) : ?>
						<a rel="<?php echo str_replace(array(" ", '"',"'", '&quot;','&#039;'), array("_", "", "", "",''), wpsc_the_product_title()); ?>" href="<?php echo wpsc_the_product_permalink(); ?>">
							<img class="product_image" id="product_image_<?php echo wpsc_the_product_id(); ?>" alt="<?php echo wpsc_the_product_title(); ?>" title="<?php echo wpsc_the_product_title(); ?>" src="<?php echo wpsc_the_product_thumbnail(); ?>"/>
						</a>
				<?php else: ?>
							<a href="<?php echo wpsc_the_product_permalink(); ?>">
							<img class="no-image" id="product_image_<?php echo wpsc_the_product_id(); ?>" alt="No Image" title="<?php echo wpsc_the_product_title(); ?>" src="<?php echo WPSC_URL; ?>/wpsc-theme/wpsc-images/noimage.png" width="<?php esc_attr_e( get_option('product_image_width') ); ?>" height="<?php esc_attr_e( get_option('product_image_height') ); ?>" />
							</a>
				<?php endif; ?>
				<?php endif; // close show thumbnails ?>
				<br />
				<span id="special_product_price_<?php echo wpsc_the_product_id(); ?>">
				<!-- price display -->
				<?php echo wpsc_the_product_price(); ?>
				</span><br />
				<strong><a class="wpsc_product_title" href="<?php echo wpsc_product_url( wpsc_the_product_id(), false ); ?>"><?php echo wpsc_the_product_title(); ?></a></strong><br />

				<?php if( $show_description ): ?>
					<div class="wpsc-special-description">
						<?php echo wpsc_the_product_description(); ?>
					</div>
				<?php endif; // close show description ?>

				<?php


				endif;
		endwhile;
		list( $wp_query, $special_products ) = array( $special_products, $wp_query ); // swap the wpsc_query object
	}
	wp_reset_query();
}
function wpsc_specials_excludes(){
	global $wpdb;
	$exclude_products = $wpdb->get_col("SELECT ID FROM ".$wpdb->posts." JOIN ".$wpdb->postmeta." ON (".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id) WHERE ".$wpdb->posts.".post_type = 'wpsc-product' AND ".$wpdb->posts.".post_status = 'publish' AND ".$wpdb->postmeta.".meta_key = '_wpsc_special_price' AND ".$wpdb->postmeta.".meta_value = 0 GROUP BY ".$wpdb->posts.".ID ORDER BY ".$wpdb->posts.".post_date DESC");

	return $exclude_products;
}
?>
