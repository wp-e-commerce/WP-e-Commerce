<?php

class WPSC_Widget_Tag_Cloud extends WP_Widget {
	private $defaults;

	public function __construct() {
		$this->defaults = array(
			'title' => __( 'Product Tag Cloud', 'wp-e-commerce' ),
		);

		parent::__construct(
			'wpsc_tag_cloud_widget',
			__( '(WPEC) Product Tag Cloud', 'wp-e-commerce' ),
			array(
				'description' => __( 'WP eCommerce Tag Cloud Widget', 'wp-e-commerce' )
			)
		);
	}

	public function widget( $args, $instance ) {
		$cloud = wp_tag_cloud( array(
			'taxonomy' => 'product_tag',
			'orderby'  => 'count',
			'order'    => 'DESC',
			'echo'     => false,
		) );

		if ( ! $cloud ) {
			return;
		}

		$instance = wp_parse_args( $instance, $this->defaults );
		$title    = apply_filters( 'widget_title', $instance['title'] );

		extract( $args );

		echo $before_widget;

		if ( ! empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}

		echo $cloud;

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
		$instance = wp_parse_args( $new_instance, $old_instance );
		$instance['title'] = strip_tags( $new_instance['title'] );
		return $instance;
	}
}