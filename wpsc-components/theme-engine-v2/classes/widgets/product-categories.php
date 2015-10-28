<?php
/**
 * Product Categories widget class
 *
 * Takes the settings, works out if there is anything to display, if so, displays it.
 *
 * @since 3.7.1
 */
class WPSC_Widget_Product_Categories extends WP_Widget {

	private $defaults;
	private $children_of;
	private $instance;

	/**
	 * Widget Constuctor
	 */
	public function __construct() {
		$widget_ops = array(
			'description' => __( 'Product Categories Widget', 'wp-e-commerce' )
		);

		parent::__construct(
			'wpsc_product_categories',
			__( '(WPEC) Product Categories', 'wp-e-commerce' ),
			$widget_ops
		);

		$this->defaults = array(
			'title'          => __( 'Product Categories', 'wp-e-commerce' ),
			'width'          => 45,
			'height'         => 45,
			'show_name'      => true,
			'show_count'     => false,
			'show_image'     => false,
			'show_hierarchy' => true,
			'categories'     => array(),
		);
	}

	/**
	 * Widget Output
	 *
	 * @param $args (array)
	 * @param $instance (array) Widget values.
	 */
	function widget( $args, $instance ) {

		$instance = wp_parse_args( $instance, $this->defaults );
		$title    = apply_filters( 'widget_title', $instance['title'] );

		extract( $args );

		echo $before_widget;

		if ( $title ) {
			echo $before_title . $title . $after_title;
		}

		$cat_args = array(
			'hide_empty' => false,
		);

		if ( ! empty( $instance['categories'] ) ) {
			$cat_args['include'] = $instance['categories'];
		}

		$cats = get_terms( 'wpsc_product_category', $cat_args );

		$this->children_of = array();
		$keys              = array();

		foreach ( $cats as $cat ) {
			$keys[] = $cat->term_id;

			if ( $instance['show_hierarchy'] ) {
				$parent = $cat->parent;
			} else {
				$parent = 0;
			}

			if ( empty( $this->children_of[ $parent ] ) ) {
				$this->children_of[ $parent ] = array();
			}

			$this->children_of[ $parent ][] = $cat;
		}
		$this->instance = $instance;
		$cats           = array_combine( $keys, $cats );

		if ( $this->instance['show_count'] ) {
			foreach ( $cats as $cat ) {
				$temp_cat = $cat;
				while ( $temp_cat->parent ) {
					$cats[ $temp_cat->parent ]->count += $cat->count;
					$temp_cat = $cats[ $temp_cat->parent ];
				}
			}
		}

		$this->list_child_categories_of(0);
		echo $after_widget;
	}

	private function list_child_categories_of( $parent ) {
		static $level = -1;

		$level++;
		if ( ! empty( $this->children_of[ $parent ] ) ) {
			$categories = $this->children_of[ $parent ];
			include( WPSC_TE_V2_SNIPPETS_PATH . '/widgets/product-categories/widget-list.php' );
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

		$instance               = wp_parse_args( $old_instance, $this->defaults );
		$instance['title']      = strip_tags( $new_instance['title'] );
		$instance['show_image'] = ! empty( $new_instance['show_image'] );
		$instance['categories'] = ! empty( $new_instance['categories'] ) ? $new_instance['categories'] : array();

		if ( is_numeric( $new_instance['height'] ) ) {
			$instance['height'] = (int) $new_instance['height'];
		}

		if ( is_numeric( $new_instance['width'] ) ) {
			$instance['width'] = (int) $new_instance['width'];
		}

		$instance['show_name']	= ! empty( $new_instance['show_name'] );

		if ( ! $instance['show_image'] && ! $instance['show_name'] ) {
			$instance['show_name'] = true;
		}

		$instance['show_count']     = ! empty( $new_instance['show_count'] );
		$instance['show_hierarchy'] = ! empty( $new_instance['show_hierarchy'] );

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
		$instance = wp_parse_args( $instance, $this->defaults );

		// Values
		$title          = esc_attr( $instance['title'] );
		$width          = (int) $instance['width'];
		$height         = (int) $instance['height'];
		$show_name      = (bool) $instance['show_name'];
		$show_hierarchy = (bool) $instance['show_hierarchy'];
		$show_count     = (bool) $instance['show_count'];
		$categories     = get_terms( 'wpsc_product_category', array(
			'hide_empty' => false,
		) );
		$options  = array();

		foreach ( $categories as $category ) {
			$options[ $category->term_id ] = $category->name;
		}

		include( WPSC_TE_V2_SNIPPETS_PATH . '/widgets/product-categories/form.php' );
	}

	private function category_image( $cat ) {
		$img = wpsc_get_categorymeta( $cat->term_id, 'image' );

		if ( $img ) {
			$url = WPSC_CATEGORY_URL . $img;
		} else {
			$url = wpsc_locate_asset_uri( 'images/noimage.png' );
		}

		echo "<img src='" . esc_url( $url ) . "' width='" . esc_attr( $this->instance['width'] ) . "' height='" . esc_attr( $this->instance['height'] ) . "' />";
	}
}