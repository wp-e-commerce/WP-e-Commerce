<?php

class WPSC_Widget_Latest_Products extends WP_Widget {
	private $defaults;

	public function __construct() {
		parent::__construct(
			'wpsc_latest_products_widget',
			__( '(WPEC) Latest Products', 'wp-e-commerce' ),
			array(
				'description' => __( 'WP eCommerce Latest Products Widget', 'wp-e-commerce' ),
			)
		);

		$this->defaults = array(
			'title'      => __( 'Latest Products', 'wp-e-commerce' ),
			'width'      => 45,
			'height'     => 45,
			'show_name'  => true,
			'show_image' => false,
			'post_count' => 5,
		);
	}

	public function widget( $args, $instance ) {
		$query = new WP_Query( array(
			'posts_per_page' => $instance['post_count'],
			'post_type'      => 'wpsc-product',
			'post_status'    => 'publish',
		) );

		if ( ! $query->have_posts() ) {
			return;
		}

		$instance = wp_parse_args( $instance, $this->defaults );

		extract( $args );

		add_image_size(
			'wpsc_product_widget_thumbnail',
			$instance['width'],
			$instance['height'],
			wpsc_get_option( 'crop_thumbnails' )
		);

		$title = apply_filters( 'widget_title', $instance['title'] );

		include( WPSC_TE_V2_SNIPPETS_PATH . '/widgets/latest-products/widget.php' );
	}

	public function form( $instance ) {
		$instance = wp_parse_args( $instance, $this->defaults );
		include( WPSC_TE_V2_SNIPPETS_PATH . '/widgets/latest-products/form.php' );
	}

	public function update( $new_instance, $old_instance ) {
		$instance               = array_merge( $this->defaults, $old_instance, $new_instance );
		$instance['title']      = strip_tags( $new_instance['title'] );
		$instance['show_image'] = ! empty( $new_instance['show_image'] );
		$instance['show_name']  = ! empty( $new_instance['show_name'] );

		if ( ! $instance['show_name'] && ! $instance['show_image'] ) {
			$instance['show_name'] = true;
		}

		if ( ! is_numeric( $new_instance['height'] ) ) {
			$new_instance['height'] = $old_instance['height'];
		}

		if ( ! is_numeric( $new_instance['width'] ) ) {
			$new_instance['width'] = $old_instance['width'];
		}

		if ( ! is_numeric( $new_instance['post_count'] ) ) {
			$new_instance['post_count'] = $old_instance['post_count'];
		}

		return $instance;
	}
}