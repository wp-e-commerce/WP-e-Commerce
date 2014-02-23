<?php

/**
 * Shopping Cart widget class
 *
 * @since 3.8
 *
 * @todo  Check if widget_wp_shopping_cart_init function is still required?
 */
class WP_Widget_Shopping_Cart extends WP_Widget {

	/**
	 * Widget Constuctor
	 */
	function WP_Widget_Shopping_Cart() {

		$widget_ops = array(
			'classname'   => 'widget_wpsc_shopping_cart',
			'description' => __( 'Shopping Cart Widget', 'wpsc' )
		);

		$this->WP_Widget( 'wpsc_shopping_cart', __( '(WPEC) Shopping Cart', 'wpsc' ), $widget_ops );

	}

	/**
	 * Widget Output
	 *
	 * @param $args (array)
	 * @param $instance (array) Widget values.
	 *
	 */
	function widget( $args, $instance ) {

		extract( $args );

		// Create fancy collapser
		$fancy_collapser = '';
		if ( $instance['show_sliding_cart'] == 1 ) {
			if ( isset($_SESSION['slider_state']) && is_numeric( $_SESSION['slider_state'] ) ) {
				if ( $_SESSION['slider_state'] == 0 ) {
					$collapser_image = 'plus.png';
				} else {
					$collapser_image = 'minus.png';
				}
				$fancy_collapser = ' <a href="#" onclick="return shopping_cart_collapser()" id="fancy_collapser_link"><img src="' . WPSC_CORE_IMAGES_URL . '/' . $collapser_image . '" title="" alt="" id="fancy_collapser" /></a>';
			} else {
				if ( ! wpsc_get_customer_meta( 'nzshpcart' ) ) {
					$collapser_image = 'plus.png';
				} else {
					$collapser_image = 'minus.png';
				}
				$fancy_collapser = ' <a href="#" onclick="return shopping_cart_collapser()" id="fancy_collapser_link"><img src="' . WPSC_CORE_IMAGES_URL . '/' . $collapser_image . '" title="" alt="" id="fancy_collapser" /></a>';
			}
		}

		// Start widget output
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Shopping Cart', 'wpsc' ) : $instance['title'] );
		echo $before_widget;

		if ( $title )
			echo $before_title . $title . $fancy_collapser . $after_title;

		// Set display state
		$display_state = '';
		if ( ( ( isset( $_SESSION['slider_state'] ) && ( $_SESSION['slider_state'] == 0 ) ) || ( wpsc_cart_item_count() < 1 ) ) && ( get_option( 'show_sliding_cart' ) == 1 ) )
			$display_state = 'style="display: none;"';

		// Output start, if we are not allowed to save results ( WPSC_DONT_CACHE ) load the cart using ajax
		$use_object_frame = false;
		if ( WPSC_DONT_CACHE  ) {
			echo '<div id="sliding_cart" class="shopping-cart-wrapper">';
			if ( ( strstr( $_SERVER['HTTP_USER_AGENT'], 'MSIE' ) == false ) && ( $use_object_frame == true ) ) {
				?>
				<object codetype="text/html" type="text/html" data="index.php?wpsc_action=cart_html_page" border="0">
					<p><?php _e( 'Loading...', 'wpsc' ); ?></p>
				</object>
				<?php
			} else {
				?>
				<div class="wpsc_cart_loading"><p><?php _e( 'Loading...', 'wpsc' ); ?></p></div>
				<?php
			}
			echo '</div>';
		} else {
			echo '<div id="sliding_cart" class="shopping-cart-wrapper" ' . $display_state . '>';
			include( wpsc_get_template_file_path( 'wpsc-cart_widget.php' ) );
			echo '</div>';
		}

		// End widget output
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
		$instance['show_sliding_cart']  = strip_tags( $new_instance['show_sliding_cart'] );
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
			'title' => __( 'Shopping Cart', 'wpsc' ),
			'show_sliding_cart' => 0
		) );

		// Values
		$title = esc_attr( $instance['title'] );
		$show_sliding_cart = esc_attr( $instance['show_sliding_cart'] );
		if( 1 == $show_sliding_cart)
			$show_sliding_cart = 'checked="checked"';
		else
			$show_sliding_cart = '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'wpsc' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<input type='hidden' name="<?php echo $this->get_field_name( 'show_sliding_cart' ); ?>" value='0' />
		<p>

			<label for="<?php echo $this->get_field_id('show_sliding_cart'); ?>"><?php _e( 'Use Sliding Cart:', 'wpsc' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'show_sliding_cart' ); ?>" name="<?php echo $this->get_field_name( 'show_sliding_cart' ); ?>" type="checkbox" value="1" <?php echo $show_sliding_cart; ?> />
		</p>

		<?php
	}
}
add_action( 'widgets_init', create_function( '', 'return register_widget("WP_Widget_Shopping_Cart");' ) );

?>
