<?php

add_filter( 'wpsc_register_post_types_products_args', '_wpsc_te2_filter_drill_down_store_permalinks', 99 );
add_filter( 'wpsc_register_taxonomies_product_tag_args', '_wpsc_te2_filter_drill_down_tag_permalinks', 99 );
add_filter( 'wpsc_register_taxonomies_product_category_args', '_wpsc_te2_filter_drill_down_category_permalinks', 99 );
add_filter( 'query_vars', '_wpsc_te2_filter_drill_down_query_vars' );

// Add %wpsc_cat_drill_down% as this will be used in various drill down permastructs
add_rewrite_tag( '%wpsc_cat_drill_down_tax%'  , '([\d\-,]+)', 'wpsc_cat_drill_down=' );
add_rewrite_tag( '%wpsc_cat_drill_down_store%', '([\d\-,]+)', 'post_type=wpsc-product&wpsc_cat_drill_down=' );

/**
 * Add drill down permastruct for store
 *
 * @since  0.1
 * @access private
 * @param  array $args Product post type args
 * @return array
 */
function _wpsc_te2_filter_drill_down_store_permalinks( $args ) {
	add_permastruct( 'wpsc_cat_drill_down_store', $args['has_archive'] . '/product-filter/%wpsc_cat_drill_down_store%' );
	return $args;
}

/**
 * Add drill down permastruct for product tags
 *
 * @since  0.1
 * @access private
 * @param  array $args Product tag taxonomy args
 * @return array
 */
function _wpsc_te2_filter_drill_down_tag_permalinks( $args ) {
	add_permastruct( 'wpsc_cat_drill_down_tag', $args['rewrite']['slug'] . '/%wpsc_product_tag%/product-filter/%wpsc_cat_drill_down_tax%' );
	return $args;
}

/**
 * Add dril down permastruct for product categories
 *
 * @since  0.1
 * @access private
 * @param  array $args Product category taxonomy args
 * @return array
 */
function _wpsc_te2_filter_drill_down_category_permalinks( $args ) {
	add_permastruct( 'wpsc_cat_drill_down_category', $args['rewrite']['slug'] . '/%wpsc_product_category%/product-filter/%wpsc_cat_drill_down_tax%' );
	return $args;
}

/**
 * Add 'wpsc_cat_drill_down' query var
 *
 * @since  0.1
 * @access private
 * @param  array $q Query vars
 * @return array
 */
function _wpsc_te2_filter_drill_down_query_vars( $q ) {
	$q[] = 'wpsc_cat_drill_down';
	return $q;
}

/**
 * Category Drill Down Widget
 *
 * @since 4.0
 */
class WPSC_Widget_Category_Drill_Down extends WP_Widget {
	/**
	 * Default arguments for the widget instance in wp-admin
	 * @since 4.0
	 * @var array
	 */
	private $defaults;

	/**
	 * Parsed drill-down arguments based on current page's URL
	 * @since 4.0
	 * @var array
	 */
	private $url_args;

	/**
	 * Base URL of terms in drill-down widget
	 * @since 4.0
	 * @var string
	 */
	private $url_base;

	private $queried_object;

	/**
	 * count the number of instances
	 * @since 4.0
	 * @var int
	 */
	private $count = 0;

	/**
	 * Get a new ID for this drill down widget, and increase global counter
	 *
	 * @todo  Determine necessity of this method.
	 * @since  0.1
	 * @return int
	 */
	private function get_id() {
		$this->count++;
		return $this->count - 1;
	}

	/**
	 * Parse drill-down arguments based on current page's URL
	 *
	 * @access private
	 * @since 4.0
	 */
	public function _action_get_current_url_args() {
		global $wp_rewrite;

		$arg_str = get_query_var( 'wpsc_cat_drill_down' );

		// break down the query into arrays of categories for each widget
		// being in used
		$this->url_args = $this->parse_url( $arg_str );

		// depending on whether this is a store page, category or tag page,
		// prepare the base URL
		if ( wpsc_is_product_category() ) {
			$base                 = $wp_rewrite->get_extra_permastruct( 'wpsc_cat_drill_down_category' );
			$obj                  = get_queried_object();
			$this->url_base       = str_replace( '%wpsc_product_category%', $obj->slug, $base );
			$this->queried_object = $obj;
		} elseif ( wpsc_is_product_tag() ) {
			$base                 = $wp_rewrite->get_extra_permastruct( 'wpsc_cat_drill_down_tag' );
			$obj                  = get_queried_object();
			$this->url_base       = str_replace( '%wpsc_product_tag%', $obj->slug, $base );
			$this->queried_object = $obj;
		} else {
			$this->url_base = $wp_rewrite->get_extra_permastruct( 'wpsc_cat_drill_down_store' );
		}

	}

	/**
	 * Parse a string into an array of category IDs for each widget
	 *
	 * Example of input: '0-1,2,3-4,5,6'
	 *
	 * @param  string $str query arg
	 * @return array
	 */
	private function parse_url( $str ) {
		$instance_keys = array_keys( $this->get_settings() );

		// '-' delimits the widgets
		if ( $str == '' ) {
			$args = array();
		} else {
			$args = explode( '-', $str );
		}

		$return = array();

		foreach ( $args as $arg ) {
			$key = array_shift( $instance_keys );
			// 0 means the widget is not being drilled down yet
			if ( $arg == '0' ) {
				$return[ $key ] = array();
			} else {
				// ',' delimits the category IDs
				$return[ $key ] = array_map( 'absint', explode( ',', $arg ) );
			}
		}

		return $return;
	}

	private function generate_uri_part( $id, $term = false, $args = false ) {

		if ( $args === false ) {
			$args = $this->url_args;
		}

		$keys = array_keys( $this->get_settings() );

		foreach ( $keys as $key ) {
			if ( ! isset( $args[ $key ] ) ) {
				$args[ $key ] = array();
			}
		}

		if ( $term !== false ) {
			$args[ $id ][] = $term->term_id;
		}

		$args[ $id ] = array_unique( $args[ $id ] );

		$ret = array();

		foreach ( $args as $arg ) {

			if ( empty( $arg ) ) {
				$ret[] = '0';
			} else {
				$ret[] = implode( ',', $arg );
			}

		}

		return implode( '-', $ret );
	}

	public function __construct() {
		if ( ! is_admin() ) {
			add_action( 'pre_get_posts', array( $this, '_action_get_current_url_args' ), 1 );
			add_action( 'pre_get_posts', array( $this, '_action_pre_get_posts' ), 2 );
		}

		$this->defaults = array(
			'title'      => __( 'Category Drill Down', 'wp-e-commerce' ),
			'categories' => array(),
		);

		parent::__construct(
			'wpsc_widget_drill_down',
			__( '(WPEC) Category Drill Down', 'wp-e-commerce' ),
			array(
				'description' => __( 'WP eCommerce Category Drill Down Widget', 'wp-e-commerce' ),
			)
		);
	}

	public function _action_pre_get_posts( $query ) {

		if ( ! $query->is_main_query() ) {
			return;
		}

		$terms = array();

		foreach ( $this->url_args as $widget ) {
			$terms += $widget;
		}

		$terms = array_unique( $terms );

		if ( empty( $terms ) ) {
			return;
		}

		$tax_query = $query->get( 'tax_query' );

		if ( empty( $tax_query ) ) {
			$tax_query = array();
		}

		foreach ( $terms as $term ) {
			// create a separate tax query for each term because we need to include
			// children for each term as well
			$tax_query[] = array(
				'taxonomy'         => 'wpsc_product_category',
				'field'            => 'id',
				'terms'            => $term,
				'operator'         => 'IN',
				'include_children' => true,
			);
		}

		$tax_query['relation'] = 'AND';

		$query->set( 'tax_query', $tax_query );
	}

	private function get_terms( $widget_id, $defaults ) {
		$args = array(
			'hide_empty' => false,
		);

		if ( wpsc_is_product_category() ) {
			$args['child_of'] = get_queried_object()->term_id;
		}

		$ids = isset( $this->url_args[ $widget_id ] ) ? $this->url_args[ $widget_id ] : array();

		if ( ! empty( $this->url_args[ $widget_id ] ) ) {
			$args['parent'] = $this->url_args[ $widget_id ][ count( $this->url_args[ $widget_id ] ) - 1 ];
		} else {
			$args['include'] = $defaults;
		}

		return get_terms( 'wpsc_product_category', $args );
	}

	public function widget( $args, $instance ) {
		extract( $args );

		$title      = apply_filters( 'widget_title', $instance['title'] );
		$categories = ! empty( $instance['categories'] ) ? array_map( 'absint', $instance['categories'] ) : array();

		echo $before_widget;

		if ( ! empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}

		echo '<ul>';
		$this->output_terms( $this->number, $categories );
		echo '</ul>';

		echo $after_widget;
	}

	private function output_terms( $widget_id, $defaults ) {
		$ancestors = array();

		if ( ! empty( $this->url_args[ $widget_id ] ) ) {
			echo '<li class="ancestor">';
			echo '<a href="' . esc_attr( $this->go_up_link( $widget_id, 0 ) ) . '">' . sprintf( _x( '&lsaquo; %s', 'navigate up', 'wp-e-commerce' ), _x( 'Clear', 'category drill down', 'wp-e-commerce' ) ) . '</a>';
			echo '<ul class="children children-level-0">';

			$ancestors = get_terms( 'wpsc_product_category', array(
				'hide_empty' => false,
				'include'    => $this->url_args[ $widget_id ],
			) );
		}

		$level = 1;

		for ( $i = 0; $i < count( $ancestors ); $i++ ) {
			$ancestor = $ancestors[ $i ];
			$li_class = 'ancestor';

			if ( $i == count( $ancestors ) - 1 ) {
				$li_class .= ' active';
				$link      = '<span>' . esc_html( $ancestor->name ) . '</span>';
			} else {
				$link = '<a href="' . esc_attr( $this->go_up_link( $widget_id, $level ) ) . '">' . sprintf( _x( '&lsaquo; %s', 'navigate up', 'wp-e-commerce' ), esc_html( $ancestor->name ) ) . '</a>';
			}

			$level ++;

			echo '<li class="' . $li_class . '">';
			echo $link;
			echo '<ul class="children children-level-' . $level . '">';
		}

		$terms = $this->get_terms( $widget_id, $defaults );

		foreach ( $terms as $term ) {
			echo '<li>';
			echo '<a href="' . esc_url( $this->term_url( $widget_id, $term ) ) . '">' . esc_html( $term->name ) . '</a>';
			echo '</li>';
		}

		for ( $i = $level; $i > 0 ; $i -- ) {
			echo '</ul>';
			echo '</li>';
		}
	}

	private function go_up_link( $widget_id, $level ) {
		$args               = $this->url_args;
		$args[ $widget_id ] = array_slice( $args[ $widget_id ], 0, $level );

		$empty = true;

		foreach ( $args as $widget ) {
			if ( ! empty( $widget ) ) {
				$empty = false;
				break;
			}
		}

		if ( $empty ) {
			if ( empty( $this->url_base ) ) {
				return esc_url( remove_query_arg( 'wpsc_cat_drill_down' ) );
			} else {
				return str_replace(
					array( '/product-filter/%wpsc_cat_drill_down_store%', '/product-filter/%wpsc_cat_drill_down_tax%' ),
					'',
					$this->url_base
				);
			}
		}

		$uri = $this->generate_uri_part( $widget_id, false, $args );

		if ( empty( $this->url_base ) ) {
			return esc_url( add_query_arg( 'wpsc_cat_drill_down', $uri ) );
		} else {
			return str_replace(
				array( '%wpsc_cat_drill_down_store%', '%wpsc_cat_drill_down_tax%' ),
				$uri,
				$this->url_base
			);
		}
	}

	private function term_url( $widget_id, $term ) {
		$uri = $this->generate_uri_part( $widget_id, $term );

		if ( empty( $this->url_base ) ) {
			return esc_url( add_query_arg( 'wpsc_cat_drill_down', $uri ) );
		} else {
			return str_replace(
				array( '%wpsc_cat_drill_down_store%', '%wpsc_cat_drill_down_tax%' ),
				$uri,
				$this->url_base
			);
		}
	}

	public function form( $instance ) {
		$instance = wp_parse_args( $instance, $this->defaults );
		$terms    = get_terms( 'wpsc_product_category', array( 'hide_empty' => false ) );
		$options  = array();

		foreach ( $terms as $term ) {
			$options[ $term->term_id ] = $term->name;
		}
?>
<p>
	<?php wpsc_form_label(
		_x( 'Title', 'widget title', 'wp-e-commerce' ),
		$this->get_field_id( 'title' )
	); ?><br>
	<?php wpsc_form_input(
		$this->get_field_name( 'title' ),
		$instance['title'],
		array( 'id' => $this->get_field_id( 'title' ), 'class' => 'widefat' )
	); ?>
</p>

<p>
	<?php wpsc_form_label(
		__( 'Categories to display', 'wp-e-commerce' ),
		$this->get_field_id( 'categories' )
	); ?><br>
	<span class="wpsc-cat-drill-down-all-actions wpsc-settings-all-none">
		<?php
			printf(
				_x( 'Select: %1$s %2$s', 'select all / none', 'wp-e-commerce' ),
				'<a href="#" data-for="' . esc_attr( $this->get_field_id( 'categories' ) ) . '" class="wpsc-multi-select-all">' . _x( 'All', 'select all', 'wp-e-commerce' ) . '</a>',
				'<a href="#" data-for="' . esc_attr( $this->get_field_id( 'categories' ) ) . '" class="wpsc-multi-select-none">' . __( 'None', 'wp-e-commerce' ) . '</a>'
			);
		?>
	</span>
	<?php wpsc_form_select(
		$this->get_field_name( 'categories' ) . '[]',
		$instance['categories'],
		$options,
		array(
			'id'               => $this->get_field_id( 'categories' ),
			'multiple'         => 'multiple',
			'size'             => 5,
			'class'            => 'wpsc-multi-select widefat',
			'data-placeholder' => __( 'Select categories', 'wp-e-commerce' ),
		)
	); ?>
</p>
<?php
	}
}