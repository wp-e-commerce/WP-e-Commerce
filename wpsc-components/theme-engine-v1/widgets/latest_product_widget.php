<?php
/**
 * Latest Product widget class
 *
 * Takes the settings, works out if there is anything to display, if so, displays it.
 *
 * @since 3.8
 */
class WP_Widget_Latest_Products extends WP_Widget {

	/**
	 * Widget Constuctor
	 */
	function WP_Widget_Latest_Products() {
		$widget_ops = array( 'classname' => 'widget_wpsc_latest_products','description' => __( 'Latest Products Widget', 'wpsc' ) );
		$this->WP_Widget( 'wpsc_latest_products', __( '(WPEC) Latest Products', 'wpsc' ), $widget_ops );
	}

	/**
	 * Widget Output
	 *
	 * @param $args (array)
	 * @param $instance (array) Widget values.
	 */
	function widget( $args, $instance ) {

		global $wpdb, $table_prefix;

		extract( $args );

		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Latest Products', 'wpsc' ) : $instance['title'] );

		echo $before_widget;

		if ( $title )
			echo $before_title . $title . $after_title;
		wpsc_latest_product( $args, $instance );
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
		$instance['title']      = strip_tags( $new_instance['title'] );
		$instance['number']     = (int)$new_instance['number'];
		$instance['image']      = (bool)$new_instance['image'];
		$instance['height']     = (int)$new_instance['height'];
		$instance['width']      = (int)$new_instance['width'];

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
			'title'  => '',
			'number' => 5,
			'width'  => 45,
			'height' => 45,
			'image'  => false,
		) );

		// Values
		$title    = esc_attr( $instance['title'] );
		$number   = (int)$instance['number'];
		$image    = (bool)$instance['image'];
		$width    = (int) $instance['width'];
		$height   = (int) $instance['height']; ?>

		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'wpsc' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of products to show:', 'wpsc' ); ?></label>
			<input type="text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" value="<?php echo $number; ?>" size="3" />
		</p>

		<p>
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'image' ); ?>" name="<?php echo $this->get_field_name( 'image' ); ?>"<?php checked($image); ?> onclick="jQuery('.wpsc_latest_image').toggle()">
			<label for="<?php echo $this->get_field_id( 'image' ); ?>"><?php _e( 'Show Thumbnails', 'wpsc' ); ?></label>
		</p>

		<div class="wpsc_latest_image"<?php if( !checked( $image ) ) { echo ' style="display:none;"'; } ?>>
			<p>
				<label for="<?php echo $this->get_field_id('width'); ?>"><?php _e('Width:', 'wpsc'); ?></label>
				<input type="text" id="<?php echo $this->get_field_id('width'); ?>" name="<?php echo $this->get_field_name('width'); ?>" value="<?php echo $width ; ?>" size="3" />
				<label for="<?php echo $this->get_field_id('height'); ?>"><?php _e('Height:', 'wpsc'); ?></label>
				<input type="text" id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" value="<?php echo $height ; ?>" size="3" />
			</p>
		</div>
<?php
	}
}

add_action( 'widgets_init', create_function( '', 'return register_widget("WP_Widget_Latest_Products");' ) );

function wpsc_latest_product( $args = null, $instance ) {
	global $wpdb;
	$args = wp_parse_args( (array)$args, array( 'number' => 5 ) );
	$options = get_option( 'wpsc-widget_latest_products' );
	$number  = isset($instance['number']) ? (int)$instance['number'] : 5;
	$image  = isset($instance['image']) ? (bool)$instance['image'] : FALSE;

	if ( isset($instance['width'] ) )
		$width = $instance['width'];

	if ( isset( $instance['height'] ) )
		$height = $instance['height'];

	$latest_products = get_posts( array(
		'post_type'   => 'wpsc-product',
		'numberposts' => $number,
		'orderby'     => 'post_date',
		'post_parent' => 0,
		'post_status' => 'publish',
		'order'       => 'DESC'
	) );
	$output = '';

	if ( count( $latest_products ) > 0 ) {
		$output .= '<ul class="wpsc-latest-products">';
		foreach ( $latest_products as $latest_product ) {
			$output .= '<li class="wpsc-latest-product">';
			// Thumbnails, if required
			if ($image) {
				$output .= '<div class="item_image">';
				$output .= '<a href="' . get_permalink( $latest_product->ID, null ) . '">';
				$thumbnail = wpsc_the_product_thumbnail( $width, $height, $latest_product->ID, '' );

				if ( $thumbnail )
					$output .= '<img src="' . esc_url( $thumbnail ) . '" title="' . apply_filters( 'the_title', $latest_product->post_title ) . '" alt="' . apply_filters( 'the_title', $latest_product->post_title ) . '" />';
				else
					$output .='<img class="no-image" id="product_image_'.wpsc_the_product_id().'" alt="' . esc_attr__( 'No Image', 'wpsc' ) . '" title="'.wpsc_the_product_title().'" src="' . WPSC_CORE_THEME_PATH . '/wpsc-images/noimage.png" width="' . $width . '" height="' . $height . '" />';

				$output .= '</a>';
				$output .= '</div>';
			}
			// Link
			$output .= '<a href="' . esc_url( get_permalink( $latest_product->ID ) ) . '" class="wpsc-product-title">'. apply_filters( 'the_title', $latest_product->post_title ).'</a>';
			$output .= '</li>';
		}
		$output .= "</ul>";
	}
	echo $output;
	}
?>
