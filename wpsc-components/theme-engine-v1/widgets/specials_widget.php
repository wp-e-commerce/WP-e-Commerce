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
	public function __construct() {

		$widget_ops = array(
			'classname'   => 'widget_wpsc_product_specials',
			'description' => __( 'Product Specials Widget', 'wp-e-commerce' )
		);

		parent::__construct( 'wpsc_product_specials', __( '(WPEC) Product Specials', 'wp-e-commerce' ), $widget_ops );

	}

	/**
	 * Widget Output
	 *
	 * @param $args (array)
	 * @param $instance (array) Widget values.
	 *
	 * @todo Add individual capability checks for each menu item rather than just manage_options.
	 */
	public function widget( $args, $instance ) {

		global $wpdb, $table_prefix;

		extract( $args );

		echo $before_widget;
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Product Specials', 'wp-e-commerce' ) : $instance['title'] );
		if ( $title )
			echo $before_title . $title . $after_title;

		wpsc_specials( $args, $instance );
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
	public function update( $new_instance, $old_instance ) {

		$instance = $old_instance;
		$instance['title']            = strip_tags( $new_instance['title'] );
		$instance['number']           = (int) $new_instance['number'];
		$instance['show_thumbnails']  = (bool) $new_instance['show_thumbnails'];
		$instance['show_description'] = (bool) $new_instance['show_description'];
		$instance['show_old_price']   = (bool) $new_instance['show_old_price'];
		$instance['show_discount']    = (bool) $new_instance['show_discount'];

		return $instance;

	}

	/**
	 * Widget Options Form
	 *
	 * @param $instance (array) Widget values.
	 */
	public function form( $instance ) {

		global $wpdb;

		// Defaults
		$instance = wp_parse_args( (array) $instance, array(
			'title'            => '',
			'show_description' => false,
			'show_thumbnails'  => false,
			'number'           => 5,
			'show_old_price'   => false,
			'show_discount'    => false,
		) );

		// Values
		$title = esc_attr( $instance['title'] );
		$number = (int) $instance['number'];
		$show_thumbnails  = (bool) $instance['show_thumbnails'];
		$show_description = (bool) $instance['show_description'];
		$show_discount    = (bool) $instance['show_discount'];
		$show_old_price   = (bool) $instance['show_old_price'];

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'wp-e-commerce' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of products to show:', 'wp-e-commerce' ); ?></label>
			<input type="text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" value="<?php echo $number; ?>" size="3" />
		</p>
		<p>
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_description' ); ?>" name="<?php echo $this->get_field_name( 'show_description' ); ?>" <?php checked( $show_description ); ?>>
			<label for="<?php echo $this->get_field_id( 'show_description' ); ?>"><?php _e( 'Show Description', 'wp-e-commerce' ); ?></label><br />
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_thumbnails' ); ?>" name="<?php echo $this->get_field_name( 'show_thumbnails' ); ?>" <?php checked( $show_thumbnails ); ?>>
			<label for="<?php echo $this->get_field_id( 'show_thumbnails' ); ?>"><?php _e( 'Show Thumbnails', 'wp-e-commerce' ); ?></label><br />
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_old_price' ); ?>" name="<?php echo $this->get_field_name( 'show_old_price' ); ?>" <?php checked( $show_old_price, '1' ); ?>>
			<label for="<?php echo $this->get_field_id( 'show_old_price' ); ?>"><?php _e( 'Show Old Price', 'wp-e-commerce' ); ?></label><br />
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_discount' ); ?>" name="<?php echo $this->get_field_name( 'show_discount' ); ?>" <?php checked( $show_discount, '1' ); ?>>
			<label for="<?php echo $this->get_field_id( 'show_discount' ); ?>"><?php _e( 'Show Discount', 'wp-e-commerce' ); ?></label>
		</p>
<?php
	}

}

add_action( 'widgets_init', '_wpsc_action_register_specials_widget' );
function _wpsc_action_register_specials_widget() {
	register_widget( 'WP_Widget_Product_Specials' );
}


function _wpsc_filter_special_widget_where( $where ) {
	global $wpdb;

	// find variations that have sales price, then get a list of parent IDs
	$sql = "
		SELECT DISTINCT(p.post_parent)
		FROM {$wpdb->posts} AS p
		INNER JOIN {$wpdb->postmeta} AS pm
			ON p.ID = pm.post_id AND pm.meta_key = '_wpsc_special_price' AND pm.meta_value > 0
		WHERE p.post_parent != 0 AND p.post_status IN ('publish', 'inherit')
	";

	$parent_ids = $wpdb->get_col( $sql );

	if ( $parent_ids ) {
		$parent_ids = array_map( 'absint', $parent_ids );
		$where .= " AND ({$wpdb->posts}.ID IN (" . implode( ', ', $parent_ids ) . ") OR pm.meta_value > 0) ";
	} else {
		$where .= " AND pm.meta_value > 0 ";
	}

	return $where;
}

function _wpsc_filter_special_widget_join( $join ) {
	global $wpdb;
	$join .= " INNER JOIN {$wpdb->postmeta} AS pm ON {$wpdb->posts}.ID = pm.post_id AND pm.meta_key = '_wpsc_special_price' ";
	return $join;
}

/**
 * Product Specials Widget content function
 *
 * Displays the latest products.
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

	$args = wp_parse_args( (array) $args, array( 'number' => 5 ) );

	if ( ! $number = (int) $instance['number'] )
		$number = 5;

	$show_thumbnails  = isset( $instance['show_thumbnails']  ) ? (bool) $instance['show_thumbnails']  : false;
	$show_description = isset( $instance['show_description'] ) ? (bool) $instance['show_description'] : false;
	$show_discount    = isset( $instance['show_discount']    ) ? (bool) $instance['show_discount']    : false;
	$show_old_price   = isset( $instance['show_old_price']   ) ? (bool) $instance['show_old_price']   : false;

	$args = array(
		'post_type'           => 'wpsc-product',
		'ignore_sticky_posts' => 1,
		'post_status'         => 'publish',
		'post_parent'         => 0,
		'posts_per_page'      => $number,
		'no_found_rows'       => true,
	);

	add_filter( 'posts_join', '_wpsc_filter_special_widget_join' );
	add_filter( 'posts_where', '_wpsc_filter_special_widget_where' );
	$special_products = new WP_Query( $args );
	remove_filter( 'posts_join', '_wpsc_filter_special_widget_join' );
	remove_filter( 'posts_where', '_wpsc_filter_special_widget_where' );

	if ( ! $special_products->post_count ) {
		echo apply_filters( 'wpsc_specials_widget_no_items_message', __( 'We currently have no items on special.', 'wp-e-commerce' ) );
		return;
	}

	$product_ids = array();

	while ( $special_products->have_posts() ) :
		$special_products->the_post();
		?>
		<h4><strong><a class="wpsc_product_title" href="<?php echo esc_url( wpsc_product_url( wpsc_the_product_id(), false ) ); ?>"><?php echo esc_html( wpsc_the_product_title() ); ?></a></h4></strong>

		<?php if ( $show_description ): ?>
			<div class="wpsc-special-description">
				<?php echo wpsc_the_product_description(); ?>
			</div>
		<?php endif; // close show description

		if ( ! in_array( wpsc_the_product_id(), $product_ids ) ) :
			$product_ids[] = wpsc_the_product_id();
			$has_children  = wpsc_product_has_children( get_the_ID() );
			$width         = get_option( 'product_image_width' );
			$height        = get_option( 'product_image_height' );
			if ( $show_thumbnails ) :
				if ( wpsc_the_product_thumbnail() ) : ?>
					<a rel="<?php echo str_replace(array(" ", '"',"'", '&quot;','&#039;'), array("_", "", "", "",''), wpsc_the_product_title()); ?>" href="<?php echo esc_url( wpsc_the_product_permalink() ); ?>"><img class="product_image" id="product_image_<?php echo esc_attr( wpsc_the_product_id() ); ?>" alt="<?php echo esc_attr( wpsc_the_product_title() ); ?>" title="<?php echo esc_attr( wpsc_the_product_title() ); ?>" src="<?php echo esc_url( wpsc_the_product_thumbnail( $width, $height ) ); ?>"/></a>
				<?php else : ?>
					<a href="<?php esc_url( wpsc_the_product_permalink() ); ?>"><img class="no-image" id="product_image_<?php echo esc_attr( wpsc_the_product_id() ); ?>" alt="<?php echo esc_attr( wpsc_the_product_title() ); ?>" title="<?php echo esc_attr( wpsc_the_product_title() ); ?>" src="<?php echo esc_url( WPSC_CORE_THEME_PATH . '/wpsc-images/noimage.png' ); ?>" width="<?php echo esc_attr( $width ); ?>" height="<?php echo esc_attr( $height ); ?>" /></a>
				<?php endif; ?>
				<br />
			<?php endif; // close show thumbnails ?>
			<div id="special_product_price_<?php echo esc_attr( wpsc_the_product_id() ); ?>">
				<?php
					wpsc_the_product_price_display(
						array(
							'output_old_price' => $show_old_price,
							'output_you_save'  => $show_discount,
						)
					);
				?>
			</div><br />
			<?php
		endif;
	endwhile;
	wp_reset_postdata();
}
