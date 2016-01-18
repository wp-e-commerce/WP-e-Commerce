<?php
/**
* wpsc has breadcrumbs function
* @return boolean - true if we have and use them, false otherwise
*/
function wpsc_has_breadcrumbs() {
	global $wpsc_breadcrumbs;
	$wpsc_breadcrumbs = new wpsc_breadcrumbs();

	if(($wpsc_breadcrumbs->breadcrumb_count > 0) && (get_option("show_breadcrumbs") == 1)){
		return true;
	} else {
		return false;
	}
}

/**
* wpsc have breadcrumbs function
* @return boolean - true if we have breadcrumbs to loop through
*/
function wpsc_have_breadcrumbs() {
	global $wpsc_breadcrumbs;

	return $wpsc_breadcrumbs->have_breadcrumbs();
}

/**
* wpsc the breadcrumbs function
* @return nothing - iterate through the breadcrumbs
*/
function wpsc_the_breadcrumb() {
	global $wpsc_breadcrumbs;

	$wpsc_breadcrumbs->the_breadcrumb();
}

/**
* wpsc breadcrumb name function
* @return string - the breadcrumb name
*/
function wpsc_breadcrumb_name() {
	global $wpsc_breadcrumbs;

	return $wpsc_breadcrumbs->breadcrumb['name'];
}

/**
* wpsc breadcrumb slug function
* @return string - the breadcrumb slug - for use in the CSS ID
*/
function wpsc_breadcrumb_slug() {
	global $wpsc_breadcrumbs;

	return ( isset( $wpsc_breadcrumbs->breadcrumb['slug'] ) ? sanitize_title_with_dashes( $wpsc_breadcrumbs->breadcrumb['slug'] ) : '' );
}

/**
* wpsc breadcrumb URL function
* @return string - the breadcrumb URL
*/
function wpsc_breadcrumb_url() {
	global $wpsc_breadcrumbs;

	if($wpsc_breadcrumbs->breadcrumb['url'] == '') {
		return false;
	} else {
		return $wpsc_breadcrumbs->breadcrumb['url'];
	}
}

/**
* Output breadcrumbs if configured
* @return None - outputs breadcrumb HTML
*/
function wpsc_output_breadcrumbs( $options = null ) {

	/**
	 * Filter the arguments passed to wpsc_output_breadcrumbs.
	 *
	 * @since 3.9.0
	 * @param array $options {
	 *     associative array of options for outputting the breadcrumbs to this page.
	 *
	 *     'before-breadcrumbs' string  HTML output to start the breadcrumb.
	 *     'after-breadcrumbs'  string  HTML output at the end of the breadcrumb eg closing tag.
	 *     'before-crumb'       string  output before each step in path, typically decorative.
	 *     'after-crumb'        string  output after each step in path.
	 *     'crumb-separator'    string  demarks space between steps in path.
	 *     'show_home_page'     bool    Whether to include home page as step at start of path.
	 *     'show_products_page' bool    Whether to include products-page as second step in path.
	 *     'echo'               bool    if true, outputs constructed string; if false, returns constructed string.
	 *     'products_page_id'   int     Override the page id used for products_page
	 *     }
	 *
	 */
	// Defaults
	$options = apply_filters( 'wpsc_output_breadcrumbs_options', $options );
	$options = wp_parse_args( (array) $options, array(
		'before-breadcrumbs' => '<div class="wpsc-breadcrumbs">',
		'after-breadcrumbs'  => '</div>',
		'before-crumb'       => '',
		'after-crumb'        => '',
		'crumb-separator'    => ' &raquo; ',
		'show_home_page'     => true,
		'show_products_page' => true,
		'echo'               => true,
		'products_page_id'   => wpsc_get_the_post_id_by_shortcode( '[productspage]' )
	) );

	$output = '';
	$products_page_id = absint( $options['products_page_id'] );
	$products_page = get_post( $products_page_id );

	if ( ! wpsc_has_breadcrumbs() ) {
		return;
	}

	$filtered_products_page = array(
		'url'  => get_option( 'product_list_url' ),
		'name' => apply_filters ( 'the_title', $products_page->post_title, $products_page_id )
	);

	/**
	 * Filter the values used to create the products-page step.
	 *
	 * Overrides the url and text show for the products-page step in breadcrumb.
	 *
	 * @since 3.9.0
	 * @param array $filtered_products_page {
	 *     associative array of options for outputting the breadcrumbs to this page.
	 *
	 *     'url'   string  Fully-qualified url to products-page.
	 *     'name'  string  The name that will appear for the products-page in breadcrumb.
	 *     }
	 *
	 */
	$filtered_products_page = apply_filters( 'wpsc_change_pp_breadcrumb', $filtered_products_page );

	// Home Page Crumb
	// If home if the same as products page only show the products-page link and not the home link
	if ( get_option( 'page_on_front' ) != $products_page_id && $options['show_home_page'] ) {
		$output .= $options['before-crumb'];
		$output .= '<a class="wpsc-crumb" id="wpsc-crumb-home" href="' . get_option( 'home' ) . '">' . get_option( 'blogname' ) . '</a>';
		$output .= $options['after-crumb'];
	}

	// Products Page Crumb
	if ( $options['show_products_page'] ) {
		if ( !empty( $output ) ) {
			$output .= $options['crumb-separator'];
		}
		$output .= $options['before-crumb'];
		$output .= '<a class="wpsc-crumb" id="wpsc-crumb-' . $products_page_id . '" href="' . $filtered_products_page['url'] . '">' . $filtered_products_page['name'] . '</a>';
		$output .= $options['after-crumb'];
	}

	// Remaining Crumbs
	while ( wpsc_have_breadcrumbs() ) {
		wpsc_the_breadcrumb();
		if ( !empty( $output ) ) {
			$output .= $options['crumb-separator'];
		}
		$output .= $options['before-crumb'];
		if ( wpsc_breadcrumb_url() ) {
			$output .= '<a class="wpsc-crumb" id="wpsc-crumb-' . wpsc_breadcrumb_slug() . '" href="' . wpsc_breadcrumb_url() . '">' . wpsc_breadcrumb_name() . '</a>';
		} else {
			$output .= '<span class="wpsc-crumb" id="wpsc-crumb-' . wpsc_breadcrumb_slug() . '">' . wpsc_breadcrumb_name() . '</span>';
		}
		$output .= $options['after-crumb'];
	}

	/**
	 * Filter the assembled breadcrumb.
	 *
	 * @since 3.9.0
	 * @param string $output The constructed breadcrumb string.
	 * @param array $options {@see 'wpsc_output_breadcrumbs_options'}
	 *
	 */
	$output = $options['before-breadcrumbs'] . apply_filters( 'wpsc_output_breadcrumbs', $output, $options ) . $options['after-breadcrumbs'];
	if ( $options['echo'] ) {
		echo $output;
	} else {
		return $output;
	}
}

/**
 * wpsc_breadcrumbs class.
 *
 */
class wpsc_breadcrumbs {
	var $breadcrumbs;
	var $breadcrumb_count = 0;
	var $current_breadcrumb = -1;
	var $breadcrumb;

	/**
	 * wpsc__breadcrumbs function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		global $wp_query, $wpsc_query;
		$this->breadcrumbs = array();
		$query_data = Array();
		if ( isset($wp_query->query_vars['post_type']) && 'wpsc-product' == $wp_query->query_vars['post_type'] && 1 == $wp_query->query_vars['posts_per_page'] && isset($wp_query->post) && is_single() )
			$query_data['product'] = $wp_query->post->post_title;

		if ( !empty($wpsc_query->query_vars['wpsc_product_category']) )
			$query_data['category'] = $wpsc_query->query_vars['wpsc_product_category'];

		if(!empty($query_data['product']) && !empty($wp_query->post)) {
			$this->breadcrumbs[] = array(
				'name' => esc_html( $wp_query->post->post_title ),
				'url'  => '',
				'slug' => $query_data['product']
			);
		}
		if( is_single() ){
			$categories = wpsc_get_product_terms( $wp_query->post->ID , 'wpsc_product_category' );
			//if product is associated w more than one category
			if(count($categories) > 1 && isset($wpsc_query->query_vars['wpsc_product_category']))
				$query_data['category'] = $wpsc_query->query_vars['wpsc_product_category'];
			elseif(count($categories) > 0)
				$query_data['category'] = $categories[0]->slug;

		}
		if( isset( $query_data['category'] ) )
			$term_data = get_term_by('slug', $query_data['category'], 'wpsc_product_category');
		else
			$term_data = get_term_by('slug', 'uncategorized', 'wpsc_product_category');

		if( $term_data != false) {
			$this->breadcrumbs[] = array(
				'name' => esc_html( $term_data->name ),
				'url'  => get_term_link( $term_data->slug, 'wpsc_product_category'),
				'slug' => $term_data->slug
			);

			$i = 0;

			while(($term_data->parent > 0) && ($i <= 20)) {
				$term_data = get_term($term_data->parent, 'wpsc_product_category');
				$this->breadcrumbs[] = array(
					'name' => esc_html( $term_data->name ),
					'url'  => get_term_link( $term_data->slug, 'wpsc_product_category')
				);
				$i++;
			}
		}
		$this->breadcrumbs = apply_filters( 'wpsc_breadcrumbs', array_reverse( $this->breadcrumbs ) );
		$this->breadcrumb_count = count($this->breadcrumbs);
	}

	/**
	 * next_breadcrumbs function.
	 *
	 * @access public
	 * @return void
	 */
	public function next_breadcrumbs() {
		$this->current_breadcrumb++;
		$this->breadcrumb = $this->breadcrumbs[$this->current_breadcrumb];
		return $this->breadcrumb;
	}


	/**
	 * the_breadcrumb function.
	 *
	 * @access public
	 * @return void
	 */
	public function the_breadcrumb() {
		$this->breadcrumb = $this->next_breadcrumbs();
	}

	/**
	 * have_breadcrumbs function.
	 *
	 * @access public
	 * @return void
	 */
	public function have_breadcrumbs() {
		if ($this->current_breadcrumb + 1 < $this->breadcrumb_count) {
			return true;
		} else if ($this->current_breadcrumb + 1 == $this->breadcrumb_count && $this->breadcrumb_count > 0) {
			$this->rewind_breadcrumbs();
		}
		return false;
	}

	/**
	 * rewind_breadcrumbs function.
	 *
	 * @access public
	 * @return void
	 */
	public function rewind_breadcrumbs() {
		$this->current_breadcrumb = -1;
		if ($this->breadcrumb_count > 0) {
			$this->breadcrumb = $this->breadcrumbs[0];
		}
	}

}