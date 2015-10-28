<?php

class WPSC_Widget_Cart extends WP_Widget {
	private $defaults;

	public function __construct() {
		parent::__construct(
			'wpsc_cart_widget',
			__( '(WPEC) Shopping Cart', 'wp-e-commerce' ),
			array(
				'description' => __( 'WP eCommerce Shopping Cart Widget', 'wp-e-commerce' ),
			)
		);

		$this->defaults = array(
			'title' => __( 'Shopping Cart', 'wp-e-commerce' ),
		);
	}

	public function widget( $args, $instance ) {
		global $wpsc_cart;

		if ( wpsc_is_cart() ) {
			return;
		}

		$instance = wp_parse_args( $instance, $this->defaults );

		extract( $args );

		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;

		if ( ! empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}

		echo '<div class="wpsc-cart-widget-table">';
		if ( ! count( $wpsc_cart->cart_items ) ) {
			echo '<p>' . __( 'No item in cart.', 'wp-e-commerce' ) . '</p>';
		} else {
			require_once( WPSC_TE_V2_CLASSES_PATH . '/cart-item-table-widget-form.php' );
			$table = new WPSC_Cart_Item_Table_Widget_Form();
			$table->display();
		}
		echo '</div>';
		echo $after_widget;
	}

	public function form( $instance ) {
		$instance = wp_parse_args( $instance, $this->defaults );
?>
<p>
	<?php wpsc_form_label(
		__( 'Title:', 'wp-e-commerce' ),
		$this->get_field_id( 'title' )
	); ?><br />
	<?php wpsc_form_input(
		$this->get_field_name( 'title' ),
		$instance['title'],
		array( 'id' => $this->get_field_id( 'title' ), 'class' => 'widefat' )
	); ?>
</p>
<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance          = wp_parse_args( $new_instance, $old_instance );
		$instance['title'] = strip_tags( $new_instance['title'] );
		return $instance;
	}
}