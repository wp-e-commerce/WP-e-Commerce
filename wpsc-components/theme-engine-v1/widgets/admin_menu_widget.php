<?php

/**
 * Admin Menu widget class
 *
 * @since 3.8
 */
class WP_Widget_Admin_Menu extends WP_Widget {

	/**
	 * Widget Constuctor
	 */
	function __construct() {
		$widget_ops = array(
			'classname'   => 'widget_wpsc_admin_menu',
			'description' => __( 'Admin Menu Widget', 'wp-e-commerce' )
		);

		parent::__construct( 'wpsc_admin_menu', __( '(WPEC) Admin Menu', 'wp-e-commerce' ), $widget_ops );

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

		extract( $args );

		if ( current_user_can( 'manage_options' ) ) {
			echo $before_widget;
			$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Admin Menu', 'wp-e-commerce' ) : $instance['title'] );
			if ( $title ) {
				echo $before_title . $title . $after_title;
			}
			admin_menu();
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
		$instance['title']  = esc_attr( strip_tags( $new_instance['title'] ) );

		return $instance;

	}

	/**
	 * Widget Options Form
	 *
	 * @param $instance (array) Widget values.
	 */
	function form( $instance ) {

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

add_action( 'widgets_init', create_function( '', 'return register_widget("WP_Widget_Admin_Menu");' ) );

/**
 * Admin Menu Widget content function
 *
 * Displays admin links.
 *
 * @todo Add individual capability checks for each menu item.
 */
function admin_menu( $args = null ) {

	if ( current_user_can( 'manage_options' ) ) {
		echo '<ul>';
		echo '<li><a title="' . esc_attr__( 'People come here to write new pages', 'wp-e-commerce' ) . '" href="' . admin_url( 'post-new.php?post_type=page' ) . '">' . esc_html__( 'Add Pages', 'wp-e-commerce' ) . '</a></li>';
		echo '<li><a title="' . esc_attr__( 'People come here to add products', 'wp-e-commerce' ) . '" href="' . admin_url( 'admin.php?page=wpsc-edit-products&amp;action=wpsc_add_edit' ) . '">' . esc_html__( 'Add Products', 'wp-e-commerce' ) . '</a></li>';
		echo '<li><a title="' . esc_attr__( 'People come here to change themes and widgets settings', 'wp-e-commerce' ) . '" href="' . admin_url( 'themes.php' ) . '">' . esc_html__( 'Presentation', 'wp-e-commerce' ) . '</a></li>';
		echo '</ul>';
	}

}