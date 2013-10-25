<?php
/**
 * WP eCommerce edit and add product page functions
 *
 * These are the main WPSC Admin functions
 *
 * @package wp-e-commerce
 * @since 3.7
 */


require_once(WPSC_FILE_PATH . '/wpsc-admin/includes/products.php');


/**
 * wpsc_additional_column_names function.
 *
 * @access public
 * @param (array) $columns
 * @return (array) $columns
 *
 */
function wpsc_additional_column_names( $columns ){
	$columns = array();

	$columns['cb']            = '<input type="checkbox" />';
	$columns['image']         = '';
	$columns['title']         = __('Name', 'wpsc');
	$columns['stock']         = __('Stock', 'wpsc');
	$columns['price']         = __('Price', 'wpsc');
	$columns['sale_price']    = __('Sale', 'wpsc');
	$columns['SKU']           = __('SKU', 'wpsc');
	$columns['weight']        = __('Weight', 'wpsc');
	$columns['cats']          = __('Categories', 'wpsc');
	$columns['featured']      = '<img src="' . WPSC_CORE_IMAGES_URL . '/black-star.png" alt="' . __( 'Featured', 'wpsc' ) . '" title="' . __( 'Featured', 'wpsc' ) . '">';
	$columns['hidden_alerts'] = '';
	$columns['date']          = __('Date', 'wpsc');

	return $columns;
}

/**
 * @param array $columns        The array of sortable columns
 * @return array
 */
function wpsc_additional_sortable_column_names( $columns ){

	$columns['stock'] = 'stock';
	$columns['price'] = 'price';
	$columns['sale_price'] = 'sale_price';
	$columns['SKU'] = 'SKU';

	return $columns;
}

/**
 * Image column in Manage Products page
 *
 * @since 3.8.9
 * @access private
 *
 * @param object $post Post object
 * @param int $post_id Post ID
 *
 * @uses wpsc_the_product_thumbnail()   Prints URL to the product thumbnail
 * @uses esc_url()                      Makes sure we have a safe URL
 */
function _wpsc_manage_products_column_image( $post, $post_id ) {
	$src = wpsc_the_product_thumbnail( false, false, $post_id, 'manage-products' );

	if ( $src )
		echo '<img src="' . esc_url( $src ). '" alt="" />';
	else
		echo '<img src="' . esc_url( WPSC_CORE_IMAGES_URL . '/no-image-uploaded.gif' ) . '" width="38" height="38" />';
}
add_action( 'wpsc_manage_products_column_image', '_wpsc_manage_products_column_image', 10, 2 );

/**
 * Weight column in Manage Products page
 *
 * @since 3.8.9
 * @access private
 *
 * @param  object  $post    Post object
 * @param  int     $post_id Post ID
 * @param  boolean $has_variations Whether the product has variations
 *
 * @uses esc_html_e()                Safe HTML with translation
 * @uses get_post_meta()             Gets post meta given key and post_id
 * @uses maybe_unserialize()         Unserialize value only if it was serialized.
 * @uses wpsc_convert_weight()       Does weight conversions
 * @uses esc_html()                  Makes sure things are safe
 * @uses wpsc_weight_unit_display()  Gets weight unit for display
 */
function _wpsc_manage_products_column_weight( $post, $post_id, $has_variations ) {
	if( $has_variations ) {
		esc_html_e( 'N/A', 'wpsc' );
		return;
	}
	$product_data = array();
	$product_data['meta'] = array();
	$product_data['meta'] = get_post_meta( $post->ID, '' );
	foreach( $product_data['meta'] as $meta_name => $meta_value ) {
		$product_data['meta'][$meta_name] = maybe_unserialize( array_pop( $meta_value ) );
	}

	$product_data['transformed'] = array();
	if( !isset( $product_data['meta']['_wpsc_product_metadata']['weight'] ) )
	$product_data['meta']['_wpsc_product_metadata']['weight'] = "";
	if( !isset( $product_data['meta']['_wpsc_product_metadata']['weight_unit'] ) )
	$product_data['meta']['_wpsc_product_metadata']['weight_unit'] = "";

	$product_data['transformed']['weight'] = wpsc_convert_weight( $product_data['meta']['_wpsc_product_metadata']['weight'], "pound", $product_data['meta']['_wpsc_product_metadata']['weight_unit'] );

	$weight = $product_data['transformed']['weight'];
	if( $weight == '' )
	$weight = '0';

	$unit = $product_data['meta']['_wpsc_product_metadata']['weight_unit'];

	echo $weight . wpsc_weight_unit_display( $unit );
	echo '<div id="inline_' . $post->ID . '_weight" class="hidden">' . esc_html( $weight ) . '</div>';
}
add_action( 'wpsc_manage_products_column_weight', '_wpsc_manage_products_column_weight', 10, 3 );

/**
 * Stock column in Manage Products page.
 *
 * @since  3.8.9
 * @access private
 *
 * @param  object  $post           Post Object
 * @param  int     $post_id        Post ID
 * @param  boolean $has_variations Whether the product has variations
 *
 * @uses get_post_meta()                    Gets post meta given key and post_id
 * @uses wpsc_variations_stock_remaining()  Gets remaining stock level for given post_id
 * @uses esc_html()                         Because we need safe HTML right???
 */
function _wpsc_manage_products_column_stock( $post, $post_id, $has_variations ) {
	$stock = get_post_meta( $post->ID, '_wpsc_stock', true );

	if( $stock == '' )
		$stock = __('N/A', 'wpsc');

	if ( $has_variations ) {
		echo '~ ' . wpsc_variations_stock_remaining( $post->ID );
		return;
	}

	echo $stock;
	echo '<div id="inline_' . $post->ID . '_stock" class="hidden">' . esc_html( $stock ) . '</div>';

}
add_action( 'wpsc_manage_products_column_stock', '_wpsc_manage_products_column_stock', 10, 3 );

/**
 * Price column in Manage Products page
 *
 * @since  3.8.9
 * @access private
 *
 * @param  object   $post                     Post object
 * @param  int      $post_id                  Post ID
 * @param  boolean  $has_variations           Whether the product has variations
 *
 * @uses get_post_meta()                      Gets post meta given key and post_id
 * @uses wpsc_currency_display()              Returns the currency after dealing with how the user wants it to be displayed
 * @uses wpsc_product_variation_price_from()  Gets the lowest variation price for the given post_id
 */
function _wpsc_manage_products_column_price( $post, $post_id, $has_variations ) {
	$price = get_post_meta( $post->ID, '_wpsc_price', true );
	$has_var = '1';
	if( ! $has_variations ) {
		echo wpsc_currency_display( $price );
		echo '<div id="inline_' . $post->ID . '_price" class="hidden">' . trim( $price ) . '</div>';
		$has_var = '0';
	}
	else
		echo wpsc_product_variation_price_from( $post->ID, array(
			'only_normal_price' => true,
			'from_text'         => '%s+'
		) );
	echo '<input type="hidden" value="' . $has_var . '" id="inline_' . $post->ID . '_has_var" />';
}
add_action( 'wpsc_manage_products_column_price', '_wpsc_manage_products_column_price', 10, 3 );

/**
 * Sale price column in Manage Products page.
 *
 * @since  3.8.9
 * @access private
 *
 * @param  object   $post                     Post object
 * @param  int      $post_id                  Post ID
 * @param  boolean  $has_variations           Whether the product has variations
 *
 * @uses get_post_meta()                      Gets post meta given key and post_id
 * @uses wpsc_currency_display()              Returns currency after taking user display options in to account
 * @uses wpsc_product_variation_price_from()  Gets the lowest variation price for the given post_id
 */
function _wpsc_manage_products_column_sale_price( $post, $post_id, $has_variations ) {
	$price = get_post_meta( $post->ID, '_wpsc_special_price', true );
	if( ! $has_variations ) {
		echo wpsc_currency_display( $price );
		echo '<div id="inline_' . $post->ID . '_sale_price" class="hidden">' . $price  . '</div>';
	} else
		echo wpsc_product_variation_price_from( $post->ID, array( 'from_text' => '%s+' ) );
}
add_action( 'wpsc_manage_products_column_sale_price', '_wpsc_manage_products_column_sale_price', 10, 3 );

/**
 * SKU column in Manage Products page
 *
 * @since  3.8.9
 * @access private
 *
 * @param  object $post    Post object
 * @param  int    $post_id Post ID
 *
 * @uses get_post_meta()        Gets post meta given key and post_id
 * @uses esc_html()             Escapes the stuff inside
 */
function _wpsc_manage_products_column_sku( $post, $post_id ) {
	$sku = get_post_meta( $post->ID, '_wpsc_sku', true );
	if( $sku == '' )
		$sku = __('N/A', 'wpsc');

	echo $sku;
	echo '<div id="inline_' . $post->ID . '_sku" class="hidden">' . esc_html( $sku ) . '</div>';
}
add_action( 'wpsc_manage_products_column_sku', '_wpsc_manage_products_column_sku', 10, 2 );

/**
 * Categories column in Manage Products page
 *
 * @since  3.8.9
 * @access private
 *
 * @param  object $post    Post object
 * @param  int    $post_id Post ID
 *
 * @uses get_the_product_category()     Gets the category for the given post_id
 * @uses esc_html()                     Makes sure we have safe HTML
 * @uses sanitize_term_field()          Cleanse the field value in the term based on the context.
 */
function _wpsc_manage_products_column_cats( $post, $post_id ) {
	$categories = get_the_product_category( $post->ID );
	if ( !empty( $categories ) ) {
		$out = array();
		foreach ( $categories as $c )
			$out[] = "<a href='?post_type=wpsc-product&amp;wpsc_product_category={$c->slug}'> " . esc_html( sanitize_term_field( 'name', $c->name, $c->term_id, 'category', 'display' ) ) . "</a>";
			echo join( ', ', $out );
		} else {
		_e('Uncategorized', 'wpsc');
	}
}
add_action( 'wpsc_manage_products_column_cats', '_wpsc_manage_products_column_cats', 10, 2 );

/**
 * Featured column in Manage Products page.
 *
 * @since  3.8.9
 * @access private
 *
 * @param  object $post    Post object
 * @param  int    $post_id Post ID
 *
 * @uses get_option()       Gets option from the WordPress database
 * @uses _e()               Displays the returned translated text from translate()
 */
function _wpsc_manage_products_column_featured( $post, $post_id ) {
	$featured_product_url = wp_nonce_url( "index.php?wpsc_admin_action=update_featured_product&amp;product_id=$post->ID", 'feature_product_' . $post->ID);
	if ( in_array( $post->ID, (array) get_option( 'sticky_products' ) ) ) {
		$class = 'gold-star';
		$title = __( 'Unmark as Featured', 'wpsc' );
	} else {
		$class = 'grey-star';
		$title = __( 'Mark as Featured', 'wpsc' );
	}
	?>
	<a class="wpsc_featured_product_toggle featured_toggle_<?php echo $post->ID; ?> <?php echo esc_attr( $class ); ?>" href='<?php echo $featured_product_url; ?>' title="<?php echo esc_attr( $title ); ?>" ></a>
<?php
}
add_action( 'wpsc_manage_products_column_featured', '_wpsc_manage_products_column_featured', 10, 2 );

/**
 * Product alert column in Manage Products page
 *
 * @since  3.8.9
 * @access private
 *
 * @param  object $post    Post object
 * @param  int    $post_id Post ID
 *
 * @uses apply_filters()        Calls 'wpsc_product_alert'
 */
function _wpsc_manage_products_column_hidden_alerts( $post, $post_id ) {
	$product_alert = apply_filters( 'wpsc_product_alert', array( false, '' ), $post );
	if( !empty( $product_alert['messages'] ) )
		$product_alert['messages'] = implode( "\n",( array )$product_alert['messages'] );

	if( $product_alert['state'] === true ) {
		?>
			<img alt='<?php echo $product_alert['messages'];?>' title='<?php echo $product_alert['messages'];?>' class='product-alert-image' src='<?php echo  WPSC_CORE_IMAGES_URL;?>/product-alert.jpg' alt='' />
		<?php
	}

	// If a product alert has stuff to display, show it.
	// Can be used to add extra icons etc
	if ( !empty( $product_alert['display'] ) )
		echo $product_alert['display'];
}
add_action( 'wpsc_manage_products_column_hidden_alerts', '_wpsc_manage_products_column_hidden_alerts', 10, 2 );


/**
 * Adds extra data to post columns
 *
 * @access public
 *
 * @param (array) $column
 * @return void
 *
 * @todo Need to check titles / alt tags ( I don't think thumbnails have any in this code )
 * @desc Switch function to generate columns the right way...no more UI hacking!
 *
 * @uses get_post()                         Gets post object from provided post_id
 * @uses wpsc_product_has_children()        Checks if a product has variations or not
 * @uses do_action()                        Calls 'wpsc_manage_products_column_$column'
 */
function wpsc_additional_column_data( $column, $post_id ) {
	$post = get_post( $post_id );

	$is_parent = wpsc_product_has_children($post_id);
	$column = strtolower( $column );
	do_action( "wpsc_manage_products_column_{$column}", $post, $post_id, $is_parent );
}

/**
 * @param   array   $vars       Array of query vars
 * @return  array   $vars       Our modified vars
 */
function wpsc_column_sql_orderby( $vars ) {
	if ( ! isset( $vars['post_type'] ) || 'wpsc-product' != $vars['post_type'] || ! isset( $vars['orderby'] ) )
		return $vars;

			switch ( $vars['orderby'] ) :
				case 'stock' :
			$vars = array_merge(
				$vars,
				array(
					'meta_key' => '_wpsc_stock',
					'orderby' => 'meta_value_num'
				)
			);
			break;
		case 'price' :
			$vars = array_merge(
				$vars,
				array(
					'meta_key' => '_wpsc_price',
					'orderby' => 'meta_value_num'
				)
			);
			break;
				case 'sale_price' :
			$vars = array_merge(
				$vars,
				array(
					'meta_key' => '_wpsc_special_price',
					'orderby' => 'meta_value_num'
				)
			);

			break;
		case 'SKU' :
			$vars = array_merge(
				$vars,
				array(
					'meta_key' => '_wpsc_sku',
					'orderby' => 'meta_value'
				)
			);
			break;
		endswitch;

	return $vars;
}

/**
 *
 * @uses get_taxonomy()                                 Retrieves the taxonomy object of $taxonomy.
 * @uses wpsc_cats_restrict_manage_posts_print_terms()  @todo docs
 */
function wpsc_cats_restrict_manage_posts() {
    global $typenow;

    if ( $typenow == 'wpsc-product' ) {

        $filters = array( 'wpsc_product_category' );

        foreach ( $filters as $tax_slug ) {
            // retrieve the taxonomy object
            $tax_obj = get_taxonomy( $tax_slug );
            $tax_name = $tax_obj->labels->name;
            // retrieve array of term objects per taxonomy
            // output html for taxonomy dropdown filter
            echo "<select name='$tax_slug' id='$tax_slug' class='postform'>";
            echo "<option value=''>" . esc_html( sprintf( _x( 'Show All %s', 'Show all [category name]', 'wpsc' ), $tax_name ) ) . "</option>";
            wpsc_cats_restrict_manage_posts_print_terms($tax_slug);
            echo "</select>";
        }
    }
}

/**
 * @todo docs
 * @param $taxonomy
 * @param int $parent
 * @param int $level
 *
 * @uses get_terms()        Retrieve the terms in a given taxonomy or list of taxonomies.
 */
function wpsc_cats_restrict_manage_posts_print_terms( $taxonomy, $parent = 0, $level = 0 ) {
	$prefix = str_repeat( '&nbsp;&nbsp;&nbsp;' , $level );
	$terms = get_terms( $taxonomy, array( 'parent' => $parent, 'hide_empty' => false ) );
	if( !($terms instanceof WP_Error) && !empty($terms) )
		foreach ( $terms as $term ){
			echo '<option value="'. $term->slug . '"', ( isset($_GET[$term->taxonomy]) && $_GET[$term->taxonomy] == $term->slug) ? ' selected="selected"' : '','>' . $prefix . $term->name .' (' . $term->count .')</option>';
			wpsc_cats_restrict_manage_posts_print_terms($taxonomy, $term->term_id, $level+1);
		}
}

/**
 * Restrict the products page to showing only parent products and not variations.
 *
 * @since 3.8
 */
function wpsc_no_minors_allowed( $vars ) {
	$current_screen = get_current_screen();

	if( $current_screen->post_type != 'wpsc-product' )
		return $vars;

	$vars['post_parent'] = 0;

	return $vars;
}

/**
 * wpsc_sortable_column_load
 *
 * Only sorts columns on edit.php page.
 * @since 3.8.8
 *
 * @uses add_filter()
 */
function wpsc_sortable_column_load() {
	add_filter( 'request', 'wpsc_no_minors_allowed' );
	add_filter( 'request', 'wpsc_column_sql_orderby', 8 );
}

/**
 * Product List Exclude Child Categories
 *
 * When filtering the product list by category in the admin this ensures that
 * only products in the selected category are shown, not any of it's sub-categories.
 *
 * @param object $query WP_Query
 *
 * @uses get_current_screen()
 */
function wpsc_product_list_exclude_child_categories( $query ) {

	if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ! $query->is_main_query() )
		return;

	if ( 'edit-wpsc-product' == get_current_screen()->id ) {
		$wpsc_product_category = $query->get( 'wpsc_product_category' );
		if ( ! empty( $wpsc_product_category ) ) {
			$category_query = array(
					'taxonomy'         => 'wpsc_product_category',
					'field'            => 'slug',
					'terms'            => array( $wpsc_product_category ),
					'include_children' => false,
					'operator'         => 'IN'
			);
			$query->set( 'tax_query', array( $category_query ) );
			$query->tax_query->queries = $query->get( 'tax_query' );
		}
	}
}

add_action( 'pre_get_posts', 'wpsc_product_list_exclude_child_categories', 15 );

add_action( 'load-edit.php'                            , 'wpsc_sortable_column_load' );
add_action( 'restrict_manage_posts'                    , 'wpsc_cats_restrict_manage_posts' );
add_action( 'manage_wpsc-product_posts_custom_column'  , 'wpsc_additional_column_data', 10, 2 );
add_filter( 'manage_edit-wpsc-product_sortable_columns', 'wpsc_additional_sortable_column_names' );
add_filter( 'manage_edit-wpsc-product_columns'         , 'wpsc_additional_column_names' );
add_filter( 'manage_wpsc-product_posts_columns'        , 'wpsc_additional_column_names' );



/**
 * wpsc_update_featured_products function.
 *
 * @access public
 * @return void
 *
 * @uses check_admin_referer()     Makes sure that a user was referred from another admin page.
 * @uses get_option()              Gets option from the WordPress database
 * @uses update_option()           Updates an option in the WordPress database
 * @uses wp_redirect()             Redirects to another page.
 * @uses wp_get_referer()          Retrieve referer from '_wp_http_referer' or HTTP referer.
 */
function wpsc_update_featured_products() {
	if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) &&
		 ! ( isset( $_REQUEST['wpsc_admin_action'] ) &&
		 	( $_REQUEST['wpsc_admin_action'] == 'update_featured_product' ) ) )
		return;

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX && 'update_featured_product' !== $_REQUEST['action'] )
		return;

	$product_id = absint( $_REQUEST['product_id'] );

	if ( ! DOING_AJAX )
		check_admin_referer( 'feature_product_' . $product_id );

	$status = get_option( 'sticky_products' );

	$new_status = ! in_array( $product_id, $status );

	if ( $new_status ) {
		$status[] = $product_id;
	} else {
		$status = array_diff( $status, array( $product_id ) );
		$status = array_values( $status );
	}

	update_option( 'sticky_products', $status );

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		$json_response = array(
			'text'       => $new_status ? esc_attr__( 'Unmark as Featured', 'wpsc' ) : esc_attr__( 'Mark as Featured', 'wpsc' ),
			'product_id' => $product_id,
			'color'      => $new_status ? 'gold-star' : 'grey-star',
			'image'      => $new_status ? WPSC_CORE_IMAGES_URL . '/gold-star.png' : WPSC_CORE_IMAGES_URL . '/grey-star.png'
		);

		echo json_encode( $json_response );

		exit();
	}
	wp_redirect( wp_get_referer() );
	exit;
}

add_filter( 'page_row_actions','wpsc_action_row', 10, 2 );

/**
 * @param $actions
 * @param $post
 * @return mixed
 *
 * @uses admin_url()            Gets the WordPress admin url
 * @uses add_query_arg()        Adds a query arg to url
 * @uses esc_url()              Makes sure the URL is safe, we like safe
 * @uses esc_html_x()           Displays translated string with gettext context
 */
function wpsc_action_row( $actions, $post ) {

	if ( $post->post_type != "wpsc-product" )
			return $actions;

	$url = admin_url( 'edit.php' );
	$url = add_query_arg( array( 'wpsc_admin_action' => 'duplicate_product', 'product' => $post->ID ), $url );

    $actions['duplicate'] = '<a href="'.esc_url( $url ).'">' . esc_html_x( 'Duplicate', 'row-actions', 'wpsc' ) . '</a>';
	return $actions;
}

add_action( 'wp_ajax_update_featured_product', 'wpsc_update_featured_products' );
add_action( 'admin_init'                     , 'wpsc_update_featured_products' );
