<?php

add_action( 'wpsc_loaded', 'wpsc_core_load_page_titles' );
add_action( 'init', 'wpsc_register_core_theme_files' );
add_action( 'wpsc_move_theme', 'wpsc_flush_theme_transients', 10, true );
add_action( 'wpsc_switch_theme', 'wpsc_flush_theme_transients', 10, true );
add_action( 'switch_theme', 'wpsc_flush_theme_transients', 10, true );
add_action('admin_init','wpsc_theme_admin_notices');
add_action( 'update_option_product_image_width'     , 'wpsc_cache_to_upload' );
add_action( 'update_option_product_image_height'    , 'wpsc_cache_to_upload' );
add_action( 'update_option_single_view_image_width' , 'wpsc_cache_to_upload' );
add_action( 'update_option_single_view_image_height', 'wpsc_cache_to_upload' );
add_action( 'update_option_category_image_width'    , 'wpsc_cache_to_upload' );
add_action( 'update_option_category_image_height'   , 'wpsc_cache_to_upload' );
add_action('template_redirect', 'wpsc_all_products_on_page');
add_action('post_thumbnail_html','wpsc_the_featured_image_fix');
add_filter( 'aioseop_description', 'wpsc_set_aioseop_description' );
add_filter('request', 'wpsc_remove_page_from_query_string');

//Potentially unnecessary, as I believe this option is deprecated
add_action( 'update_option_show_categorybrands'     , 'wpsc_cache_to_upload' );

if ( ! is_admin() )
	add_action( 'init', 'wpsc_enqueue_user_script_and_css' );

if ( isset( $_REQUEST['wpsc_flush_theme_transients'] ) && ( $_REQUEST['wpsc_flush_theme_transients'] == 'true' ) )
	add_action( 'admin_init', 'wpsc_force_flush_theme_transients' );

if ( isset( $_GET['wpsc_user_dynamic_css'] ) && 'true' == $_GET['wpsc_user_dynamic_css'] )
	add_action( 'plugins_loaded', 'wpsc_user_dynamic_css', 1 );

if ( ! is_admin() )
	add_filter('request','wpec_remap_shop_subpages');

if(get_option( 'wpsc_hide_featured_products' ) == 1)
	add_action( 'wpsc_top_of_products_page', 'wpsc_display_featured_products_page', 12 );

$show_subcatsprods_in_cat = get_option( 'show_subcatsprods_in_cat' );
if(!$show_subcatsprods_in_cat)
	add_action( 'init', 'wpsc_hidesubcatprods_init' );

/**
 * wpsc_register_theme_file( $file_name )
 *
 * Adds a file name to a global list of
 *
 * @param string $file_name Name of file to add to global list of files
 */
function wpsc_register_theme_file( $file_name ) {
	global $wpec_theme_files;

	if ( !in_array( $file_name, (array)$wpec_theme_files ) )
		$wpec_theme_files[] = $file_name;
}

/**
 * wpsc_register_core_theme_files()
 *
 * Registers the core WPEC files into the global array
 */
function wpsc_register_core_theme_files() {
	wpsc_register_theme_file( 'wpsc-single_product.php' );
	wpsc_register_theme_file( 'wpsc-grid_view.php' );
	wpsc_register_theme_file( 'wpsc-list_view.php' );
	wpsc_register_theme_file( 'wpsc-products_page.php' );
	wpsc_register_theme_file( 'wpsc-shopping_cart_page.php' );
	wpsc_register_theme_file( 'wpsc-transaction_results.php' );
	wpsc_register_theme_file( 'wpsc-user-log.php' );
	wpsc_register_theme_file( 'wpsc-cart_widget.php' );
	wpsc_register_theme_file( 'wpsc-featured_product.php' );
	wpsc_register_theme_file( 'wpsc-category-list.php' );
	wpsc_register_theme_file( 'wpsc-category_widget.php' );
	// Let other plugins register their theme files
	do_action( 'wpsc_register_core_theme_files' );
}

/**
 * wpsc_get_theme_files()
 *
 * Returns the global wpec_theme_files
 *
 * @global array $wpec_theme_files
 * @return array
 */
function wpsc_get_theme_files() {
	global $wpec_theme_files;
	if ( empty( $wpec_theme_files ) )
		return array();
	else
		return apply_filters( 'wpsc_get_theme_files', (array)array_values( $wpec_theme_files ) );
}

/**
 * wpsc_flush_theme_transients()
 *
 * This function will delete the temporary values stored in WordPress transients
 * for all of the additional WPEC theme files and their locations. This is
 * mostly used when the active theme changes, or when files are moved around. It
 * does a complete flush of all possible path/url combinations of files.
 *
 * @uses wpsc_get_theme_files
 */
function wpsc_flush_theme_transients( $force = false ) {

	if ( true === $force || isset( $_REQUEST['wpsc_flush_theme_transients'] ) && !empty( $_REQUEST['wpsc_flush_theme_transients'] ) ) {

		// Loop through current theme files and remove transients
		if ( $theme_files = wpsc_get_theme_files() ) {
			foreach( $theme_files as $file ) {
				delete_transient( WPEC_TRANSIENT_THEME_PATH_PREFIX . $file );
				delete_transient( WPEC_TRANSIENT_THEME_URL_PREFIX . $file );
			}

			delete_transient( 'wpsc_theme_path' );

			return true;
		}
	}

	// No files were registered so return false
	return false;
}

function wpsc_force_flush_theme_transients() {
	// Flush transients
	wpsc_flush_theme_transients( true );

	// Bounce back
	$sendback = wp_get_referer();
	wp_redirect( $sendback );

	exit();
}

/**
 * wpsc_check_theme_location()
 *
 * Check theme location, compares the active theme and the themes within WPSC_CORE_THEME_PATH
 * finds files of the same name.
 *
 * @access public
 * @since 3.8
 * @param null
 * @return $results (Array) of Files OR false if no similar files are found
 */
function wpsc_check_theme_location() {
	// Get the current theme
	$current_theme       = get_stylesheet_directory();

	// Load up the files in the current theme
	$current_theme_files = wpsc_list_product_templates( $current_theme . '/' );

	// Load up the files in the wpec themes folder
	$wpsc_template_files = wpsc_list_product_templates( WPSC_CORE_THEME_PATH );

	// Compare the two
	$results             = array_intersect( $current_theme_files, $wpsc_template_files );

	// Return the differences
	if ( count( $results ) > 0 )
		return $results;

	// No differences so return false
	else
		return false;
}

/**
 * wpsc_list_product_templates( $path = '' )
 *
 * Lists the files within the WPSC_CORE_THEME_PATH directory
 *
 * @access public
 * @since 3.8
 * @param $path - you can provide a path to find the files within it
 * @return $templates (Array) List of files
 */
function wpsc_list_product_templates( $path = '' ) {

	$selected_theme = get_option( 'wpsc_selected_theme' );

	// If no path, then try to make some assuptions
	if ( empty( $path ) ) {
		if ( file_exists( WPSC_OLD_THEMES_PATH . $selected_theme . '/' . $selected_theme . '.css' ) ) {
			$path = WPSC_OLD_THEMES_PATH . $selected_theme . '/';
		} else {
			$path = WPSC_CORE_THEME_PATH;
		}
	}

	// Open the path and get the file names
	$dh = opendir( $path );
	while ( ( $file = readdir( $dh ) ) !== false ) {
		if ( $file != "." && $file != ".." && !strstr( $file, ".svn" ) && !strstr( $file, "images" ) && is_file( $path . $file ) ) {
			$templates[] = $file;
		}
	}

	// Return template names
	return $templates;
}

/**
 * Displays the theme upgrade notice
 * @access public
 *
 * @since 3.8
 * @param null
 * @return null
 */
function wpsc_theme_upgrade_notice() { ?>

	<div id="message" class="updated fade">
		<p><?php printf( __( '<strong>WP e-Commerce is ready</strong>. If you plan on editing the look of your site, you should <a href="%1s">update your active theme</a> to include the additional WP e-Commerce files. <a href="%2s">Click here</a> to ignore and remove this box.', 'wpsc' ), admin_url( 'admin.php?page=wpsc-settings&tab=presentation' ), admin_url( 'admin.php?page=wpsc-settings&tab=presentation&wpsc_notices=theme_ignore' ) ) ?></p>
	</div>

<?php
}

/**
 * Displays the database update notice
 * @access public
 *
 * @since 3.8
 * @param null
 * @return null
 */
function wpsc_database_update_notice() { ?>

	<div class="error fade">
		<p><?php printf( __( '<strong>Your WP e-Commerce data needs to be updated</strong>. You\'ve upgraded from a previous version of the WP e-Commerce plugin, and your store needs updating.<br>You should <a href="%1s">update your database</a> for your store to continue working.', 'wpsc' ), admin_url( 'index.php?page=wpsc-update' ) ) ?></p>
	</div>

<?php
}


function wpsc_theme_admin_notices() {
	// Database update notice is most important
	if ( get_option ( 'wpsc_version' ) < 3.8 ) {

		add_action ( 'admin_notices', 'wpsc_database_update_notice' );

	// If that's not an issue check if theme updates required
	} else {

		if ( get_option('wpsc_ignore_theme','') == '' ) {
			add_option('wpsc_ignore_theme',false);
		}
		if (!get_option('wpsc_ignore_theme')) {
			add_action( 'admin_notices', 'wpsc_theme_upgrade_notice' );
		}

	}

	// Flag config inconsistencies
	if ( 1 == get_option( 'require_register' ) && 1 != get_option( 'users_can_register' )) {
		add_action( 'admin_notices', 'wpsc_turn_on_wp_register' );
	}

}

function wpsc_turn_on_wp_register() {?>

	<div id="message" class="updated fade">
		<p><?php printf( __( '<strong>Store Settings</strong>: You have set \'users must register before checkout\', for this to work you need to check \'Anyone can register\' in your WordPress <a href="%1s">General Settings</a>.', 'wpsc' ), admin_url( 'options-general.php' ) ) ?></p>
	</div>

<?php


}

/**
 * wpsc_get_template_file_url( $file )
 *
 * Checks the active theme folder for the particular file, if it exists then
 * return the active theme url, otherwise return the global wpsc_theme_url
 *
 * @access public
 * @since 3.8
 * @param $file string filename
 * @return PATH to the file
 */
function wpsc_get_template_file_url( $file = '' ) {
	// If we're not looking for a file, do not proceed
	if ( empty( $file ) )
		return;

	// Look for file in stylesheet
	if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
		$file_url = get_stylesheet_directory_uri() . '/' . $file;

	// Look for file in template
	} elseif ( file_exists( get_template_directory() . '/' . $file ) ) {
		$file_url = get_template_directory_uri() . '/' . $file;

	// Backwards compatibility
	} else {
		// Look in old theme url
		$selected_theme_check = WPSC_OLD_THEMES_PATH . get_option( 'wpsc_selected_theme' ) . '/' . str_ireplace( 'wpsc-', '', $file );
		// Check the selected theme
		if ( file_exists( $selected_theme_check ) ) {

			$file_url = WPSC_OLD_THEMES_URL . get_option( 'wpsc_selected_theme' ) . '/' . str_ireplace( 'wpsc-', '', $file );
		// Use the bundled theme CSS
		} else {
			$file_url = WPSC_CORE_THEME_URL . $file;
		}
	}

	if ( is_ssl() )
		$file_url = str_replace('http://', 'https://', $file_url);

	// Return filtered result
	return apply_filters( WPEC_TRANSIENT_THEME_URL_PREFIX . $file, $file_url );
}

/**
 * wpsc_user_enqueues products function,
 * enqueue all javascript and CSS for wp ecommerce
 */
function wpsc_enqueue_user_script_and_css() {
	global $wp_styles, $wpsc_theme_url, $wp_query;
	/**
	 * added by xiligroup.dev to be compatible with touchshop
	 */
	if ( has_filter( 'wpsc_enqueue_user_script_and_css' ) && apply_filters( 'wpsc_mobile_scripts_css_filters', false ) ) {
		do_action( 'wpsc_enqueue_user_script_and_css' );
	} else {
		/**
		 * end of added by xiligroup.dev to be compatible with touchshop
		 */
		$scheme = is_ssl() ? 'https' : 'http';
		$version_identifier = WPSC_VERSION . "." . WPSC_MINOR_VERSION;

		$category_id = wpsc_get_current_category_id();

		if( get_option( 'wpsc_share_this' ) == 1 ) {
			$remote_protocol = is_ssl() ? 'https://ws' : 'http://w';
			wp_enqueue_script( 'sharethis', $remote_protocol . '.sharethis.com/button/buttons.js', array(), false, true );
		}

		wp_enqueue_script( 'jQuery' );
		wp_enqueue_script( 'wp-e-commerce',               WPSC_CORE_JS_URL	. '/wp-e-commerce.js',                 array( 'jquery' ), $version_identifier );
		wp_enqueue_script( 'wp-e-commerce-dynamic', home_url( '/index.php?wpsc_user_dynamic_js=true', $scheme ), false,             $version_identifier );

		wp_localize_script( 'wp-e-commerce', 'wpsc_ajax', array(
			'ajaxurl'   => admin_url( 'admin-ajax.php', 'relative' ),
			'spinner'   => esc_url( admin_url( 'images/wpspin_light.gif' ) ),
			'no_quotes' => __( 'It appears that there are no shipping quotes for the shipping information provided.  Please check the information and try again.', 'wpsc' ),
			'ajax_get_cart_error' => __( '<i>There was a problem getting the current contents of the shopping cart.</i>', 'wpsc' ),
			)
		);

		wp_enqueue_script( 'livequery',                   WPSC_URL 			. '/wpsc-admin/js/jquery.livequery.js',   array( 'jquery' ), '1.0.3' );
		if( get_option( 'product_ratings' ) == 1 )
			wp_enqueue_script( 'jquery-rating',               WPSC_CORE_JS_URL 	. '/jquery.rating.js',                 array( 'jquery' ), $version_identifier );
		wp_enqueue_script( 'wp-e-commerce-legacy',        WPSC_CORE_JS_URL 	. '/user.js',                          array( 'jquery' ), WPSC_VERSION . WPSC_MINOR_VERSION );
		if ( get_option( 'show_thumbnails_thickbox' ) == 1 ){
			$lightbox = get_option('wpsc_lightbox', 'thickbox');
			if( $lightbox == 'thickbox' ) {
				wp_enqueue_script( 'wpsc-thickbox',				WPSC_CORE_JS_URL . '/thickbox.js',                      array( 'jquery' ), 'Instinct_e-commerce' );
				wp_enqueue_style( 'wpsc-thickbox',				WPSC_CORE_JS_URL . '/thickbox.css',						false, $version_identifier, 'all' );
			} elseif( $lightbox == 'colorbox' ) {
				wp_enqueue_script( 'colorbox-min',				WPSC_CORE_JS_URL . '/jquery.colorbox-min.js',			array( 'jquery' ), 'Instinct_e-commerce' );
				wp_enqueue_script( 'wpsc_colorbox',				WPSC_CORE_JS_URL . '/wpsc_colorbox.js',					array( 'jquery', 'colorbox-min' ), 'Instinct_e-commerce' );
				wp_enqueue_style( 'wpsc-colorbox-css',				WPSC_CORE_JS_URL . '/wpsc_colorbox.css',			false, $version_identifier, 'all' );
			}
		}
		wp_enqueue_style( 'wpsc-theme-css',               wpsc_get_template_file_url( 'wpsc-' . get_option( 'wpsc_selected_theme' ) . '.css' ), false, $version_identifier, 'all' );
		wp_enqueue_style( 'wpsc-theme-css-compatibility', wpsc_get_template_file_url( 'compatibility.css' ),                                    array( 'wpsc-theme-css' ), $version_identifier, 'all' );

		if ( function_exists( 'wp_add_inline_style' ) )
			wp_add_inline_style( 'wpsc-theme-css', wpsc_get_user_dynamic_css() );
		else
			wp_enqueue_style( 'wp-e-commerce-dynamic', wpsc_get_dynamic_user_css_url(), array( 'wpsc-theme-css' ), $version_identifier );

		if( get_option( 'product_ratings' ) == 1 )
			wp_enqueue_style( 'wpsc-product-rater',           WPSC_CORE_JS_URL 	. '/product_rater.css',                                       false, $version_identifier, 'all' );

	}


	if ( !defined( 'WPSC_MP3_MODULE_USES_HOOKS' ) && function_exists( 'listen_button' ) ) {

		function wpsc_legacy_add_mp3_preview( $product_id, &$product_data ) {
			global $wpdb;
			if ( function_exists( 'listen_button' ) ) {
				$file_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_PRODUCT_FILES . "` WHERE `id` = %d LIMIT 1", $product_data['file'] ), ARRAY_A );
				if ( $file_data != null ) {
					echo listen_button( $file_data['idhash'], $file_data['id'] );
				}
			}
		}

		add_action( 'wpsc_product_before_description', 'wpsc_legacy_add_mp3_preview', 10, 2 );
	}
}

/**
 * Checks the category slug for a display type, if none set returns default
 * << May need reworking to be more specific to the taxonomy type >>
 * @access public
 *
 * @since 3.8
 * @param $slug(string)
 * @return $slug either from db or 'default' if none set
 */

function wpsc_get_the_category_display($slug){
	global $wpdb;
	$default_display_type = get_option('product_view');
	if ( !empty($slug) && is_string($slug) ) {
		$category_id = wpsc_get_the_category_id($slug , 'slug');
		$display_type = wpsc_get_categorymeta( $category_id, 'display_type' );
	}
	if(!empty($display_type))
		return $display_type;
	else
		return  $default_display_type;
}

/**
 * wpsc display products function
 * @return string - html displaying one or more products
 */
function wpsc_display_products_page( $query ) {global $wpdb, $wpsc_query,$wp_query, $wp_the_query;

	remove_filter('the_title','wpsc_the_category_title');

	// If the data is coming from a shortcode parse the values into the args variable,
	// I did it this was to preserve backwards compatibility
	if(!empty($query)){
		$args = array();

		$args['post_type'] = 'wpsc-product';
		if(!empty($query['product_id']) && is_array($query['product_id'])){
			$args['post__in'] = $query['product_id'];
		}elseif(is_string($query['product_id'])){
			$args['post__in'] = (array)$query['product_id'];
		}
		if(!empty($query['old_product_id'])){
			$post_id = wpsc_get_the_new_id($query['old_product_id']);
			$args['post__in'] = (array)$post_id;
		}
		if(!empty($query['price']) && 'sale' != $query['price']){
			$args['meta_key'] = '_wpsc_price';
			$args['meta_value'] = $query['price'];
		}elseif(!empty($query['price']) && 'sale' == $query['price']){
			$args['meta_key'] = '_wpsc_special_price';
			$args['meta_compare'] = '>=';
			$args['meta_value'] = '1';
		}
		if(!empty($query['product_name'])){
			$args['pagename'] = $query['product_name'];
		}
		if(!empty($query['category_id'])){
			$term = get_term($query['category_id'],'wpsc_product_category');
			$id = wpsc_get_meta($query['category_id'], 'category_id','wpsc_old_category');
			if( !empty($id)){
				$term = get_term($id,'wpsc_product_category');
				$args['wpsc_product_category'] = $term->slug;
				$args['wpsc_product_category__in'] = $term->term_id;
			}else{
				$args['wpsc_product_category'] = $term->slug;
				$args['wpsc_product_category__in'] = $term->term_id;
			}
		}
		if(!empty($query['category_url_name'])){
			$args['wpsc_product_category'] = $query['category_url_name'];
		}
		$orderby = ( !empty($query['sort_order']) ) ? $query['sort_order'] : null;

		$args = array_merge( $args, wpsc_product_sort_order_query_vars($orderby) );

		if(!empty($query['order'])){
			$args['order'] = $query['order'];
		}
		if(!empty($query['limit_of_items']) && '1' == get_option('use_pagination')){
			$args['posts_per_page'] = $query['limit_of_items'];
		}
		if(!empty($query['number_per_page']) && '1' == get_option('use_pagination')){
			$args['posts_per_page'] = $query['number_per_page'];
			$args['paged'] = $query['page'];
		}
		if( '0' == get_option('use_pagination') ){
			$args['nopaging'] = true;
			$args['posts_per_page'] = '-1';
		}
		if(!empty($query['tag'])){
			$args['product_tag'] = $query['tag'];
		}
		query_posts( $args );
	}
	// swap the wpsc_query objects

	$GLOBALS['nzshpcrt_activateshpcrt'] = true;

	// Pretty sure this single_product code is legacy...but fixing it up just in case.
	// get the display type for the selected category
	if(!empty($temp_wpsc_query->query_vars['term']))
		$display_type = wpsc_get_the_category_display($temp_wpsc_query->query_vars['term']);
	elseif( !empty( $args['wpsc_product_category'] ) )
		$display_type = wpsc_get_the_category_display($args['wpsc_product_category']);
	else
		$display_type = 'default';

	$saved_display = wpsc_get_customer_meta( 'display_type' );
	$display_type  = ! empty( $saved_display ) ? $saved_display : wpsc_check_display_type();

	ob_start();
	if( 'wpsc-product' == $wp_query->post->post_type && !is_archive() && $wp_query->post_count <= 1 )
		include( wpsc_get_template_file_path( 'wpsc-single_product.php' ) );
	else
		wpsc_include_products_page_template($display_type);

	$output = ob_get_contents();
	ob_end_clean();
	$output = str_replace('\$','$', $output);

	if ( ! empty( $query ) ) {
		wp_reset_query();
		wp_reset_postdata();
	}
	return $output;
}

/**
 * Checks if wpsc-single_product.php has been moved to the active theme, if it has then include the
 * template from the active theme.
 * @access public
 *
 * @since 3.8
 * @param $content content of the page
 * @return $content with wpsc-single_product content if its a single product
 */
function wpsc_single_template( $content ) {
	global $wpdb, $post, $wp_query, $wpsc_query, $_wpsc_is_in_custom_loop;
	if ( ! in_the_loop() )
		return $content;

	//if we dont belong here exit out straight away
	if((!isset($wp_query->is_product)) && !isset($wp_query->query_vars['wpsc_product_category']))return $content;

	// If we are a single products page
	if ( !is_archive() && $wp_query->post_count > 0 && 'wpsc-product' == $wp_query->post->post_type && $wp_query->post_count <= 1 ) {
		remove_filter( "the_content", "wpsc_single_template", 12 );
		$single_theme_path = wpsc_get_template_file_path( 'wpsc-single_product.php' );
		if( isset( $wp_query->query_vars['preview'] ) && $wp_query->query_vars['preview'])
			$is_preview = 'true';
		else
			$is_preview = 'false';
		$wpsc_temp_query = new WP_Query( array( 'p' => $wp_query->post->ID , 'post_type' => 'wpsc-product','posts_per_page'=>1, 'preview' => $is_preview ) );

		list( $wp_query, $wpsc_temp_query ) = array( $wpsc_temp_query, $wp_query ); // swap the wpsc_query object
		$_wpsc_is_in_custom_loop = true;
		ob_start();
		include( $single_theme_path );
		$content = ob_get_contents();
		ob_end_clean();
		list( $wp_query, $wpsc_temp_query ) = array( $wpsc_temp_query, $wp_query ); // swap the wpsc_query objects back
		$_wpsc_is_in_custom_loop = false;
	}

	return $content;
}

function wpsc_is_viewable_taxonomy(){
	global $wp_query;
	if(isset($wp_query->query_vars['taxonomy']) && ('wpsc_product_category' == $wp_query->query_vars['taxonomy'] ||  'product_tag' == $wp_query->query_vars['taxonomy'] ) || isset($wp_query->query_vars['wpsc_product_category']))
		return true;
	else
		return false;
}

function _wpsc_is_in_custom_loop() {
	global $_wpsc_is_in_custom_loop;
	return (bool) $_wpsc_is_in_custom_loop;
}

/**
 * Checks and replaces the Page title with the category title if on a category page
 *
 * @since 3.8
 * @access public
 *
 * @param string    $title      The Page Title
 * @param int       $id         The Page ID
 * @return string   $title      The new title
 *
 * @uses in_the_loop()                  Returns true if you are  in the loop
 * @uses _wpsc_is_in_custom_loop()      Returns true if in the WPSC custom loop
 * @uses is_tax()                       Returns true if you are on the supplied registered taxonomy
 * @uses get_term_by()                  Gets term object by defined item, and what you pass
 * @uses get_query_var()                Gets query var from wp_query
 */
function wpsc_the_category_title( $title='', $id='' ){

	if ( ! empty( $id ) )
		_wpsc_deprecated_argument( __FUNCTION__, '3.8.10', 'The $id param is not used. If you are trying to get the title of the category use get_term' );

	if ( ! in_the_loop() || _wpsc_is_in_custom_loop() )
		return $title;

	$term = null;
	if ( is_tax( 'wpsc_product_category' ) ){
		$term = get_term_by( 'slug', get_query_var( 'wpsc_product_category' ),'wpsc_product_category' );
	} elseif ( is_tax( 'product_tag' ) ){
		$term = get_term_by( 'slug', get_query_var( 'term' ),'product_tag' );
	} // is_tax

	if ( $term )
		return $term->name;

	return $title;

}

//handles replacing the tags in the pages
function wpsc_products_page( $content = '' ) {
	global $wpdb, $wp_query, $wpsc_query, $wpsc_query_vars, $_wpsc_is_in_custom_loop;
	$output = '';
	if ( ! in_the_loop() )
		return $content;
	if ( preg_match( "/\[productspage\]/", $content ) ) {
		global $more;
		$more = 0;
		remove_filter( 'the_content', 'wpautop' );

		list($wp_query, $wpsc_query) = array( $wpsc_query, $wp_query ); // swap the wpsc_query object
		$_wpsc_is_in_custom_loop = true;

		$GLOBALS['nzshpcrt_activateshpcrt'] = true;

		// get the display type for the productspage
		$display_type = wpsc_check_display_type();
		if ( get_option( 'show_search' ) && get_option( 'show_advanced_search' ) ) {
			$saved_display = wpsc_get_customer_meta( 'display_type' );
			if ( ! empty( $saved_display ) )
				$display_type = $saved_display;
		}

		ob_start();
		wpsc_include_products_page_template($display_type);
		$is_single = false;
		$output .= ob_get_contents();
		ob_end_clean();
		$output = str_replace( '$', '\$', $output );

		if ( $wp_query->post_count > 0 ) {
			$product_id = $wp_query->post->ID;
			$product_meta = get_post_meta( $product_id, '_wpsc_product_metadata', true );

			list($wp_query, $wpsc_query) = array( $wpsc_query, $wp_query ); // swap the wpsc_query objects back

			$_wpsc_is_in_custom_loop = false;

			if ( ($is_single == false) || ($product_meta['enable_comments'] == '0') )
				wp_reset_postdata();
		}
		$wp_query->current_post = $wp_query->post_count;
		return preg_replace( "/(<p>)*\[productspage\](<\/p>)*/", $output, $content );
	} elseif(is_archive() && wpsc_is_viewable_taxonomy()){

		remove_filter( 'the_content', 'wpautop' );
		return wpsc_products_page('[productspage]');
	} else {
		return $content;
	}
}

/**
 * wpsc_the_category_template swaps the template used for product categories with pageif archive template is being used use
 * @access public
 *
 * @since 3.8
 * @param $template (string) template path
 * @return $template (string)
 */
function wpsc_the_category_template($template){
	global $wp_query;
	//this bit of code makes sure we use a nice standard page template for our products
	if(wpsc_is_viewable_taxonomy() && false !== strpos($template,'archive'))
		return str_ireplace('archive', 'page',$template);
	else
		return $template;

}

/**
 * Returns current product category ID or default category ID if one is set.  If one is not set and there is no current category, returns empty string
 * @return mixed
 */
function wpsc_get_current_category_id() {
	global $wp_query;

	$category_id = '';

	if ( isset( $wp_query ) && isset( $wp_query->query_vars['taxonomy'] ) && ('wpsc_product_category' ==  $wp_query->query_vars['taxonomy'] ) || is_numeric( get_option( 'wpsc_default_category' ) ) )
		$category_id = isset( $wp_query->query_vars['term'] ) && is_string( $wp_query->query_vars['term'] ) ? wpsc_get_category_id( $wp_query->query_vars['term'], 'slug' ) : get_option( 'wpsc_default_category' );

	return $category_id;
}

/**
 * Returns Dynamic User CSS URL
 *
 * This produces the cached CSS file if it exists and the uploads folder is writeable.
 * If the folder is not writeable, we return the dynamic URL
 * If the folder is writeable, but for some reason a cached copy of the CSS doesn't exist, we attempt to create it and return that URL.
 *
 * @since 3.8.9
 * @return string
 */

function wpsc_get_dynamic_user_css_url() {

	$uploads_dir     = wp_upload_dir();
	$upload_folder   = $uploads_dir['path'];

	if ( is_writable( $upload_folder ) && file_exists( $upload_folder . '/wpsc_cached_styles.css' ) )
		return add_query_arg( 'timestamp', get_option( 'wpsc_dynamic_css_hash', time() ), $uploads_dir['url'] . '/wpsc_cached_styles.css' );

	if ( ! is_writable( $upload_folder ) )
		return add_query_arg( 'wpsc_user_dynamic_css', 'true', home_url( 'index.php' ) );

	if ( is_writable( $upload_folder ) && ! file_exists( $upload_folder . '/wpsc_cached_styles.css' ) )
		return wpsc_cache_to_upload();
}

/**
 * Moves dynamically generated input into a file in the uploads folder.
 * Also updates CSS hash timestamp.  Timestamp is appended to URL
 *
 * @since 3.8.9
 * @return mixed File URL on successful move, false on failure
 */
function wpsc_cache_to_upload() {

	$uploads_dir     = wp_upload_dir();
	$upload_folder   = $uploads_dir['path'];
	$path            = $upload_folder . '/wpsc_cached_styles.css';

	if ( ! is_writable( $upload_folder ) )
		return false;

	if ( false === file_put_contents( $path, wpsc_get_user_dynamic_css() ) )
		return false;

	$timestamp = time();

	update_option( 'wpsc_dynamic_css_hash', $timestamp );

	return add_query_arg( 'timestamp', $timestamp, $uploads_dir['url'] . '/wpsc_cached_styles.css' );

}

/**
 * Prints dynamic CSS.  This function is run either when the dynamic URL is hit, or when we need to grab new CSS to cache.
 *
 * @since 3.8.9
 * @return CSS
 */
function wpsc_user_dynamic_css() {

	header( 'Content-Type: text/css' );
	header( 'Expires: ' . gmdate( 'r', mktime( 0, 0, 0, date( 'm' ), ( date( 'd' ) + 12 ), date( 'Y' ) ) ) );
	header( 'Cache-Control: public, must-revalidate, max-age=86400' );
	header( 'Pragma: public' );

	echo wpsc_get_user_dynamic_css();
	exit;
}

/**
 * Returns dynamic CSS as string.  This function is run either when the dynamic URL is hit, or when we need to grab new CSS to cache.
 *
 * @since 3.8.9
 * @return string
 */
function wpsc_get_user_dynamic_css() {
	global $wpdb;

	ob_start();

	if ( ! defined( 'WPSC_DISABLE_IMAGE_SIZE_FIXES' ) || (constant( 'WPSC_DISABLE_IMAGE_SIZE_FIXES' ) != true ) ) {

		$thumbnail_width = get_option( 'product_image_width' );

		if ( $thumbnail_width <= 0 )
			$thumbnail_width = 96;

		$thumbnail_height = get_option( 'product_image_height' );

		if ( $thumbnail_height <= 0 )
			$thumbnail_height = 96;

		$single_thumbnail_width  = get_option( 'single_view_image_width' );
		$single_thumbnail_height = get_option( 'single_view_image_height' );

		if ( $single_thumbnail_width <= 0 )
			$single_thumbnail_width = 128;

		$category_height = get_option( 'category_image_height' );
		$category_width  = get_option( 'category_image_width' );
?>

		/*
		* Default View Styling
		*/
		div.default_product_display div.textcol{
			margin-left: <?php echo $thumbnail_width + 10; ?>px !important;
			min-height: <?php echo $thumbnail_height; ?>px;
			_height: <?php echo $thumbnail_height; ?>px;
		}

		div.default_product_display  div.textcol div.imagecol{
			position:absolute;
			top:0px;
			left: 0px;
			margin-left: -<?php echo $thumbnail_width + 10; ?>px !important;
		}

		div.default_product_display  div.textcol div.imagecol a img {
			width: <?php echo $thumbnail_width; ?>px;
			height: <?php echo $thumbnail_height; ?>px;
		}

		.wpsc_category_grid_item  {
			display:block;
			float:left;
			width: <?php echo $category_width; ?>px;
			height: <?php echo $category_height; ?>px;
		}
		.wpsc_category_grid_item  span{
			position:relative;
			top:<?php echo ($thumbnail_height - 2)/9; ?>px;
		}
		div.default_product_display div.item_no_image a  {
			width: <?php echo $thumbnail_width - 2; ?>px;
		}

		div.default_product_display .imagecol img.no-image, #content div.default_product_display .imagecol img.no-image {
			width: <?php echo $thumbnail_width; ?>px;
			height: <?php echo $thumbnail_height; ?>px;
        }

		<?php do_action( 'wpsc_dynamic_css' ); ?>

		/*
		* Single View Styling
		*/

		div.single_product_display div.item_no_image  {
			width: <?php echo $single_thumbnail_width - 2; ?>px;
			height: <?php echo $single_thumbnail_height - 2; ?>px;
		}
		div.single_product_display div.item_no_image a  {
			width: <?php echo $single_thumbnail_width - 2; ?>px;
		}

		div.single_product_display div.textcol{
			margin-left: <?php echo $single_thumbnail_width + 10; ?>px !important;
			min-height: <?php echo $single_thumbnail_height; ?>px;
			_height: <?php echo $single_thumbnail_height; ?>px;
		}


		div.single_product_display  div.textcol div.imagecol{
			position:absolute;

			margin-left: -<?php echo $single_thumbnail_width + 10; ?>px !important;
		}

		div.single_product_display  div.textcol div.imagecol a img {
			width: <?php echo $single_thumbnail_width; ?>px;
			height: <?php echo $single_thumbnail_height; ?>px;
		}

<?php
}
	if ( (isset($_GET['brand']) && is_numeric( $_GET['brand'] )) || (get_option( 'show_categorybrands' ) == 3) ) {
		$brandstate = 'block';
		$categorystate = 'none';
	} else {
		$brandstate = 'none';
		$categorystate = 'block';
	}
?>
	div#categorydisplay{
		display: <?php echo $categorystate; ?>;
	}

	div#branddisplay{
		display: <?php echo $brandstate; ?>;
	}
<?php

	$css = ob_get_contents();

	ob_end_clean();

	return $css;
}

function wpsc_get_the_new_id($prod_id){
	global $wpdb;
	$post_id = (int)$wpdb->get_var($wpdb->prepare( "SELECT `post_id` FROM `{$wpdb->postmeta}` WHERE meta_key = %s AND `meta_value` = %d LIMIT 1", '_wpsc_original_id', $prod_id ));
	return $post_id;

}

/**
 * This switched between the 3 view types on category and products pages and includes the necessary tempalte part
 * @access public
 *
 * @since 3.8
 * @param $display_type
 * @return NULL
 */
function wpsc_include_products_page_template($display_type = 'default'){
	if ( isset( $_GET['view_type'] ) && get_option( 'show_search' ) && get_option( 'show_advanced_search' ) ) {
		switch ( $_GET['view_type'] ) {
			case 'grid':
				$display_type = 'grid';
				wpsc_update_customer_meta( 'display_type', $display_type );
				break;

			case 'list':
				$display_type = 'list';
				wpsc_update_customer_meta( 'display_type', $display_type );
				break;

			case 'default':
				$display_type = 'default';
				wpsc_update_customer_meta( 'display_type', $display_type );
				break;

			default:
				break;
		}
	}
		// switch the display type, based on the display type variable...
		switch ( $display_type ) {
			case "grid":
				include( wpsc_get_template_file_path( 'wpsc-grid_view.php' ) );
				break; // only break if we have the function;

			case "list":
				include( wpsc_get_template_file_path( 'wpsc-list_view.php' ) );
				break; // only break if we have the file;
			default:
				include( wpsc_get_template_file_path( 'wpsc-products_page.php' ) );
				break;
		}

}

function wpsc_thesis_compat( $loop ) {
	$loop[1] = 'page';
	return $loop;
}

// Template tags
function wpsc_all_products_on_page(){
	global $wp_query,$wpsc_query;
	do_action('wpsc_swap_the_template');
	$products_page_id = wpsc_get_the_post_id_by_shortcode('[productspage]');
	$term = get_query_var( 'wpsc_product_category' );
	$tax_term = get_query_var ('product_tag' );
	$obj = $wp_query->get_queried_object();

	$id = isset( $obj->ID ) ? $obj->ID : null;

	if( get_query_var( 'post_type' ) == 'wpsc-product' || $term || $tax_term || ( $id == $products_page_id )){

		$templates = array();

		if ( $term && ! is_single() ) {
			array_push( $templates, "taxonomy-wpsc_product_category-{$term}.php", 'taxonomy-wpsc_product_category.php' );
		}

		if ( $tax_term && ! is_single() ) {
			array_push( $templates, "taxonomy-product_tag-{$tax_term}.php", 'taxonomy-product_tag.php' );
		}


		// Attempt to use the [productspage]'s custom page template as a higher priority than the normal page.php template
		if ( false !== $productspage_page_template = get_post_meta($products_page_id, '_wp_page_template', true) )
			array_push( $templates, $productspage_page_template );

		array_push( $templates, 'page.php', 'single.php' );

		if ( is_single() )
			array_unshift( $templates, 'single-wpsc-product.php' );

		// have to pass 'page' as the template type. This is lame, btw, and needs a rewrite in 4.0
		if ( ! $template = get_query_template( 'page', $templates ) )
			$template = get_index_template();

		add_filter( 'thesis_custom_loop', 'wpsc_thesis_compat' );

		include( $template );
		exit;
	}
}

/**
 * wpsc_count_themes_in_uploads_directory, does exactly what the name says
 */
function wpsc_count_themes_in_uploads_directory() {
	$uploads_dir = false;
	if ( is_dir( WPSC_OLD_THEMES_PATH.get_option('wpsc_selected_theme').'/' ) )
		$uploads_dir = @opendir( WPSC_OLD_THEMES_PATH.get_option('wpsc_selected_theme').'/' ); // might cause problems if dir doesnt exist

	if ( !$uploads_dir )
		return false;

	$file_names = array( );
	while ( ($file = @readdir( $uploads_dir )) !== false ) {
		if ( is_dir( WPSC_OLD_THEMES_PATH . get_option('wpsc_selected_theme') . '/' . $file ) && ($file != "..") && ($file != ".") && ($file != ".svn") )
			$file_names[] = $file;

	}
	@closedir( $uploads_dir );
	return count( $file_names );
}

function wpsc_place_shopping_cart( $content = '' ) {
	if ( ! in_the_loop() )
		return $content;

	if ( preg_match( "/\[shoppingcart\]/", $content ) ) {
		// BEGIN: compatibility fix for outdated theme files still relying on sessions
		$_SESSION['coupon_numbers'                    ] = wpsc_get_customer_meta( 'coupon'                       );
		$_SESSION['wpsc_checkout_misc_error_messages' ] = wpsc_get_customer_meta( 'checkout_misc_error_messages' );
		$_SESSION['categoryAndShippingCountryConflict'] = wpsc_get_customer_meta( 'category_shipping_conflict'   );
		$_SESSION['shippingSameBilling'               ] = wpsc_get_customer_meta( 'shipping_same_as_billing'     );
		$_SESSION['wpsc_checkout_user_error_messages' ] = wpsc_get_customer_meta( 'registration_error_messages'  );
		// END: compatibility fix
		$GLOBALS['nzshpcrt_activateshpcrt'] = true;
		if ( ! defined( 'DONOTCACHEPAGE' ) )
			define( 'DONOTCACHEPAGE', true );

		// call this function to detect conflicts when the cart page is first loaded, otherwise
		// any conflict messages will only be displayed on the next page load
		wpsc_get_acceptable_countries();
		ob_start();
		do_action( 'wpsc_before_shopping_cart_page' );
		include( wpsc_get_template_file_path( 'wpsc-shopping_cart_page.php' ) );
		do_action( 'wpsc_after_shopping_cart_page' );
		$output = ob_get_contents();
		ob_end_clean();
		$output = str_replace( '$', '\$', $output );
		wpsc_delete_customer_meta( 'checkout_misc_error_messages' );
		wpsc_delete_customer_meta( 'category_shipping_conflict'   );
		wpsc_delete_customer_meta( 'registration_error_messages'  );
		wpsc_delete_customer_meta( 'checkout_error_messages'      );
		wpsc_delete_customer_meta( 'gateway_error_messages'       );
		return preg_replace( "/(<p>)*\[shoppingcart\](<\/p>)*/", $output, $content );
	} else {
		return $content;
	}
}

function wpsc_transaction_results( $content = '' ) {
	if ( ! in_the_loop() )
		return $content;

	if ( preg_match( "/\[transactionresults\]/", $content ) ) {
		define( 'DONOTCACHEPAGE', true );
		ob_start();
		include( wpsc_get_template_file_path( 'wpsc-transaction_results.php' ) );
		$output = ob_get_contents();
		ob_end_clean();
		$output = preg_replace( '#(?<!\\\\)(\\$|\\\\)#', '\\\\$1', $output );
		return preg_replace( "/(<p>)*\[transactionresults\](<\/p>)*/", $output, $content );
	} else {
		return $content;
	}
}

function wpsc_user_log( $content = '' ) {
	if ( ! in_the_loop() )
		return $content;
	if ( preg_match( "/\[userlog\]/", $content ) ) {
		define( 'DONOTCACHEPAGE', true );

		ob_start();

		include( wpsc_get_template_file_path('wpsc-user-log.php') );
		$output = ob_get_clean();
		$content = preg_replace( "/(<p>)*\[userlog\](<\/p>)*/", '[userlog]', $content );
		return str_replace( '[userlog]', $output, $content );
	} else {
		return $content;
	}
}

function wpsc_get_the_post_id_by_shortcode( $shortcode ) {

	$shortcode_options = array(
			'[productspage]'       => 'product_list_url',
			'[shoppingcart]'       => 'shopping_cart_url',
			'[checkout]'           => 'shopping_cart_url',
			'[transactionresults]' => 'transact_url',
			'[userlog]'            => 'user_account_url'
		);

	if ( ! isset( $shortcode_options[$shortcode] ) )
		return 0;

	$page_ids = get_option( 'wpsc_shortcode_page_ids', false );

	if ( $page_ids === false ) {
		wpsc_update_permalink_slugs();
		$page_ids = get_option( 'wpsc_shortcode_page_ids', false );
	}

	$post_id = isset( $page_ids[$shortcode] ) ? $page_ids[$shortcode] : null;

	// For back compat
	$post_id = apply_filters( 'wpec_get_the_post_id_by_shortcode', $post_id );

	return apply_filters( 'wpsc_get_the_post_id_by_shortcode', $post_id, $shortcode );

}

function wpec_remap_shop_subpages( $vars ) {
	if( empty( $vars ) )
		return $vars;
	$reserved_names = array('[shoppingcart]','[userlog]','[transactionresults]');
	foreach($reserved_names as $reserved_name){
		if ( isset( $vars['taxonomy'] ) && $vars['taxonomy'] == 'wpsc_product_category' && $isset( $vars['term'] ) && $vars['term'] == $page->post_name ) {
			$page_id = wpsc_get_the_post_id_by_shortcode( $reserved_name );
			$page = get_post( $page_id );
			return array( 'page_id' => $page->ID );
		}
	}
	return $vars;
}

function wpsc_remove_page_from_query_string($query_string)
{

	if ( isset($query_string['name']) && $query_string['name'] == 'page' && isset($query_string['page']) ) {
		unset($query_string['name']);
		list($delim, $page_index) = explode( '/', $query_string['page'] );

		$query_string['paged'] = $page_index;
	}

	if ( isset($query_string['wpsc-product']) && 'page' == $query_string['wpsc-product'] )
		$query_string['wpsc-product'] = '';

	if ( isset($query_string['name']) && is_numeric($query_string['name']) ) {
		$query_string['paged'] = $query_string['name'];
		$query_string['page'] = '/'.$query_string['name'];

		$query_string['posts_per_page'] = get_option('wpsc_products_per_page');
	}
	if ( isset($query_string['wpsc-product']) && is_numeric($query_string['wpsc-product']) )
		unset( $query_string['wpsc-product'] );

	if ( isset($query_string['wpsc_product_category']) && 'page' == $query_string['wpsc_product_category'] )
		unset( $query_string['wpsc_product_category'] );
	if ( isset($query_string['name']) && is_numeric($query_string['name']) )
		unset( $query_string['name'] );
	if ( isset($query_string['term']) && 'page' == $query_string['term'] )	{
		unset( $query_string['term'] );
		unset( $query_string['taxonomy'] );
	}
	return $query_string;
}

function is_products_page(){
	global $post;

	$product_page_id = wpsc_get_the_post_id_by_shortcode( '[productspage]' );

	return $post->ID == $product_page_id;

}

/**
 * wpsc_display_products_page function.
 *
 * @access public
 * @param mixed $query
 * @return void
 */
function wpsc_display_featured_products_page() {
	global $wp_query;
	$sticky_array = get_option( 'sticky_products' );
	if ( (is_front_page() || is_home() || is_products_page() ) && !empty( $sticky_array ) && $wp_query->post_count > 1) {
		$query = get_posts( array(
					'post__in' => $sticky_array,
					'post_type' => 'wpsc-product',
					'orderby' => 'rand',
					'numberposts' => 1,
					'posts_per_page' => 1
				) );

		if ( count( $query ) > 0 ) {

			$GLOBALS['nzshpcrt_activateshpcrt'] = true;
			$image_width = get_option( 'product_image_width' );
			$image_height = get_option( 'product_image_height' );
			$featured_product_theme_path = wpsc_get_template_file_path( 'wpsc-featured_product.php' );
	ob_start();
		include_once($featured_product_theme_path);
		$is_single = false;
		$output .= ob_get_contents();
		ob_end_clean();

			//Begin outputting featured product.  We can worry about templating later, or folks can just CSS it up.
			echo $output;
			//End output
		}
	}
}

function wpsc_hidesubcatprods_init() {
	$hide_subcatsprods = new WPSC_Hide_subcatsprods_in_cat;
	add_action( 'pre_get_posts', array( &$hide_subcatsprods, 'get_posts' ) );
}

function wpsc_the_featured_image_fix($stuff){
	global $wp_query;
	remove_action('post_thumbnail_html','wpsc_the_featured_image_fix');
	if(isset($wp_query->query_vars['wpsc-product'])){
		$stuff ='';	?>
		<img src="<?php header_image(); ?>" width="<?php echo HEADER_IMAGE_WIDTH; ?>" height="<?php echo HEADER_IMAGE_HEIGHT; ?>" alt="" /><?php
	}
	return $stuff;

}

// check for all in one SEO pack and the is_static_front_page function
if ( is_callable( array( "All_in_One_SEO_Pack", 'is_static_front_page' ) ) ) {

	function wpsc_change_aioseop_home_title( $title ) {
		global $aiosp, $aioseop_options;

		if ( (get_class( $aiosp ) == 'All_in_One_SEO_Pack') && $aiosp->is_static_front_page() ) {
			$aiosp_home_title = $aiosp->internationalize( $aioseop_options['aiosp_home_title'] );
			$new_title = wpsc_obtain_the_title();
			if ( $new_title != '' ) {
				$title = str_replace( $aiosp_home_title, $new_title, $title );
			}
		}
		return $title;
	}

	add_filter( 'aioseop_home_page_title', 'wpsc_change_aioseop_home_title' );
}

/**
 * wpsc_obtain_the_title function, for replaacing the page title with the category or product
 * @return string - the new page title
 */
function wpsc_obtain_the_title() {
	global $wpdb, $wp_query, $wpsc_title_data;
	$output = null;
	$category_id = null;
	if( !isset( $wp_query->query_vars['wpsc_product_category']) &&  !isset( $wp_query->query_vars['wpsc-product']))
		return;

	if ( !isset( $wp_query->query_vars['wpsc_product_category'] ) && isset($wp_query->query_vars['wpsc-product']) )
		$wp_query->query_vars['wpsc_product_category'] = 0;


	if ( isset( $wp_query->query_vars['taxonomy'] ) && 'wpsc_product_category' ==  $wp_query->query_vars['taxonomy'] || isset($wp_query->query_vars['wpsc_product_category']))
		$category_id = wpsc_get_the_category_id($wp_query->query_vars['wpsc_product_category'],'slug');

	if ( $category_id > 0 ) {

		if ( isset( $wpsc_title_data['category'][$category_id] ) ) {
			$output = $wpsc_title_data['category'][$category_id];
		} else {
			$term = get_term($category_id, 'wpsc_product_category');
			$output = $term->name;
			$wpsc_title_data['category'][$category_id] = $output;
		}
	}

	if ( !isset( $_GET['wpsc-product'] ) )
		$_GET['wpsc-product'] = 0;

	if ( !isset( $wp_query->query_vars['wpsc-product'] ) )
		$wp_query->query_vars['wpsc-product'] = '';

	if ( isset( $wp_query->query_vars['wpsc-product'] ) || is_string( $_GET['wpsc-product'] ) ) {
		$product_name = $wp_query->query_vars['wpsc-product'];
		if ( isset( $wpsc_title_data['product'][$product_name] ) ) {
			$product_list = array( );
			$full_product_name = $wpsc_title_data['product'][$product_name];
		} else if ( $product_name != '' ) {
			$product_id = $wp_query->post->ID;
			$full_product_name = $wpdb->get_var( $wpdb->prepare( "SELECT `post_title` FROM `$wpdb->posts` WHERE `ID`= %d LIMIT 1", $product_id ) );
			$wpsc_title_data['product'][$product_name] = $full_product_name;
		} else {
			if(isset($_REQUEST['product_id'])){
				$product_id = absint( $_REQUEST['product_id'] );
				$product_name = $wpdb->get_var( $wpdb->prepare( "SELECT `post_title` FROM `$wpdb->posts` WHERE `ID`= %d LIMIT 1", $product_id ) );
				$full_product_name = $wpdb->get_var( $wpdb->prepare( "SELECT `post_title` FROM `$wpdb->posts` WHERE `ID`= %d LIMIT 1", $product_id ) );
				$wpsc_title_data['product'][$product_name] = $full_product_name;
			}else{
				//This has to exist, otherwise we would have bailed earlier.
				$category = $wp_query->query_vars['wpsc_product_category'];
				$cat_term = get_term_by('slug',$wp_query->query_vars['wpsc_product_category'], 'wpsc_product_category');
				$full_product_name = $cat_term->name;
			}
		}
		$output = $full_product_name;
	}

	if ( isset( $full_product_name ) && ($full_product_name != null) )
		$output = esc_html(  $full_product_name );
	$seperator = ' | ';
	$seperator = apply_filters('wpsc_the_wp_title_seperator' , $seperator);
	return $output.$seperator;
}

/**
 *	Return category or product description depending on queried item
 */
function wpsc_obtain_the_description() {

	$output = null;

	// Return Category Description
	if ( is_numeric( get_query_var('category_id') ) ) {
		$output = wpsc_get_categorymeta( get_query_var('category_id'), 'description' );
	} else if ( ! empty($_GET['category']) ) {
		$output = wpsc_get_categorymeta( absint( $_GET['category'] ), 'description' );
	}

	// Return product content as description if product page
	if ( !empty($_GET['product_id'] ) && is_numeric( $_GET['product_id'] ) ) {
		$product = get_post(absint( $_GET['product_id'] ));
		$output = $product->post_content;
	}

	return $output;
}

function wpsc_set_aioseop_description( $data ) {
	$replacement_data = wpsc_obtain_the_description();
	if ( $replacement_data != '' ) {
		$data = $replacement_data;
	}
	return $data;
}

/**
 * 	this page url function, returns the URL of this page
 * @return string - the URL of the current page
 */
function wpsc_this_page_url() {
	global $wpsc_query, $wp_query;
	if ( $wpsc_query->is_single === true ) {
		$output = get_permalink( $wp_query->post->ID );
	} else if ( isset( $wpsc_query->category ) && $wpsc_query->category != null ) {
		$output = wpsc_category_url( $wpsc_query->category );
		if ( $wpsc_query->query_vars['page'] > 1 ) {
			if ( get_option( 'permalink_structure' ) ) {
				$output .= "page/{$wpsc_query->query_vars['page']}/";
			} else {
				$output = add_query_arg( 'page_number', $wpsc_query->query_vars['page'], $output );
			}
		}
	} elseif ( isset( $id ) ) {
		$output = get_permalink( $id );
	} else {
		$output = get_permalink( get_the_ID() );
	}
	return $output;
}

