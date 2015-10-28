<?php

class WPSC_Widget_On_Sale extends WP_Widget {
	private $defaults;

	public function __construct() {
		$this->defaults = array(
			'title'             => __( 'Products On Sale', 'wp-e-commerce' ),
			'width'             => 45,
			'height'            => 45,
			'show_name'         => true,
			'show_image'        => false,
			'show_description'  => false,
			'show_sale_price'   => true,
			'show_normal_price' => true,
			'show_you_save'     => true,
			'post_count'        => 5,
		);

		parent::__construct(
			'wpsc_widget_on_sale',
			__( '(WPEC) Products On Sale', 'wp-e-commerce' ),
			array(
				'description' => __( 'WP eCommerce Product On Sale Widget', 'wp-e-commerce' )
			)
		);
	}

	public function widget( $args, $instance ) {
		global $post;

		$instance = wp_parse_args( $instance, $this->defaults );
		$title    = apply_filters( 'widget_title', $instance['title'] );

		extract( $args );

		add_image_size(
			'wpsc_product_widget_thumbnail',
			$instance['width'],
			$instance['height'],
			wpsc_get_option( 'crop_thumbnails' )
		);

		$on_sale_products = get_posts( array(
			'post_type'   => 'wpsc-product',
			'nopaging'    => true,
			'post_status' => array( 'publish', 'inherit' ),
			'meta_query'  => array(
				array(
					'key'     => '_wpsc_special_price',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			)
		) );

		// extract products with no parents
		$products = wp_list_filter( $on_sale_products, array( 'post_parent' => 0 ) );

		// get parent of variations
		$parent_ids = array_unique( wp_list_pluck( $on_sale_products, 'post_parent' ) );
		$parents    = array();

		if ( ! empty( $parent_ids ) ) {
			$parents = get_posts( array(
				'post_type' => 'wpsc-product',
				'nopaging' => true,
				'post_status' => 'publish',
				'post__in' => $parent_ids,
				'post__not_in' => wp_list_pluck( $products, 'ID' ),
			) );
		}

		$products = array_merge( $products, $parents );

		if ( ! empty( $instance['post_count'] ) ) {
			$products = array_slice( $products, 0, $instance['post_count'] );
		}

		include( WPSC_TE_V2_SNIPPETS_PATH . '/widgets/on-sale/widget.php' );
	}

	public function form( $instance ) {
		$instance = wp_parse_args( $instance, $this->defaults );
		include( WPSC_TE_V2_SNIPPETS_PATH . '/widgets/on-sale/form.php' );
	}

	public function update( $new_instance, $old_instance ) {
		$instance                      = array_merge( $this->defaults, $old_instance, $new_instance );
		$instance['title']             = strip_tags( $new_instance['title'] );
		$instance['show_image']        = ! empty( $new_instance['show_image'] );
		$instance['show_name']         = ! empty( $new_instance['show_name'] );
		$instance['show_normal_price'] = ! empty( $new_instance['show_normal_price'] );
		$instance['show_sale_price']   = ! empty( $new_instance['show_sale_price'] );
		$instance['show_you_save']     = ! empty( $new_instance['show_you_save'] );
		$instance['show_description']  = ! empty( $new_instance['show_description'] );

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