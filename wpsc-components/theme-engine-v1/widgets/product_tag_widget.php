<?php

include_once( WPSC_FILE_PATH . '/wpsc-widgets/tagging_functions.php' );

/**
 * Product Tags widget class
 *
 * @since 3.8
 */
class WP_Widget_Product_Tags extends WP_Widget {

	/**
	 * Widget Constuctor
	 */
	function __construct() {

		$widget_ops = array(
			'classname'   => 'widget_wpsc_product_tags',
			'description' => __( 'Product Tags Widget', 'wp-e-commerce' )
		);

		parent::__construct( 'wpsc_product_tags', __( '(WPEC) Product Tags', 'wp-e-commerce' ), $widget_ops );

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
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Product Tags', 'wp-e-commerce' ) : $instance['title'] );
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}
		product_tag_cloud();
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
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'wp-e-commerce' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<?php

	}

}

add_action( 'widgets_init', create_function( '', 'return register_widget("WP_Widget_Product_Tags");' ) );



?>