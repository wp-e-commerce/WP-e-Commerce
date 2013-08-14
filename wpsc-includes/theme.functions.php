<?php
/**
 * WP eCommerce theme functions
 *
 * These are the functions for the wp-eCommerce theme engine
 *
 * @package wp-e-commerce
 * @since 3.7
 */

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
add_action( 'init', 'wpsc_register_core_theme_files' );

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
add_action( 'wpsc_move_theme'  , 'wpsc_flush_theme_transients', 10, true );
add_action( 'wpsc_switch_theme', 'wpsc_flush_theme_transients', 10, true );
add_action( 'switch_theme'     , 'wpsc_flush_theme_transients', 10, true );

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
add_action('admin_init','wpsc_theme_admin_notices');

function wpsc_turn_on_wp_register() {?>

	<div id="message" class="updated fade">
		<p><?php printf( __( '<strong>Store Settings</strong>: You have set \'users must register before checkout\', for this to work you need to check \'Anyone can register\' in your WordPress <a href="%1s">General Settings</a>.', 'wpsc' ), admin_url( 'options-general.php' ) ) ?></p>
	</div>

<?php


}

if ( isset( $_REQUEST['wpsc_notices'] ) && $_REQUEST['wpsc_notices'] == 'theme_ignore' ) {
	update_option( 'wpsc_ignore_theme', true );
	wp_redirect( remove_query_arg( 'wpsc_notices' ) );
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
 * Checks the active theme folder for the particular file, if it exists then return the active theme directory otherwise
 * return the global wpsc_theme_path
 * @access public
 *
 * @since 3.8
 * @param $file string filename
 * @return PATH to the file
 */
function wpsc_get_template_file_path( $file = '' ){

	// If we're not looking for a file, do not proceed
	if ( empty( $file ) )
		return;

	// No cache, so find one and set it
	if ( false === ( $file_path = get_transient( WPEC_TRANSIENT_THEME_PATH_PREFIX . $file ) ) ) {
		// Look for file in stylesheet
		if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
			$file_path = get_stylesheet_directory() . '/' . $file;

		// Look for file in template
		} elseif ( file_exists( get_template_directory() . '/' . $file ) ) {
			$file_path = get_template_directory() . '/' . $file;

		// Backwards compatibility
		} else {
			// Look in old theme path
			$selected_theme_check = WPSC_OLD_THEMES_PATH . get_option( 'wpsc_selected_theme' ) . '/' . str_ireplace( 'wpsc-', '', $file );

			// Check the selected theme
			if ( file_exists( $selected_theme_check ) ) {
				$file_path = $selected_theme_check;

			// Use the bundled file
			} else {
				$file_path = path_join( WPSC_CORE_THEME_PATH, $file );
			}
		}
		// Save the transient and update it every 12 hours
		if ( !empty( $file_path ) )
			set_transient( WPEC_TRANSIENT_THEME_PATH_PREFIX . $file, $file_path, 60 * 60 * 12 );

	}elseif(!file_exists($file_path)){
		delete_transient(WPEC_TRANSIENT_THEME_PATH_PREFIX . $file);
		wpsc_get_template_file_path($file);
	}

	// Return filtered result
	return apply_filters( WPEC_TRANSIENT_THEME_PATH_PREFIX . $file, $file_path );
}

/**
 * Get the Product Category ID by either slug or name
 * @access public
 *
 * @since 3.8
 * @param $slug (string) to be searched
 * @param $type (string) column to search, i.e name or slug
 * @return $category_id (int) Category ID
 */
function wpsc_get_the_category_id($slug, $type = 'name'){
	global $wpdb,$wp_query;
	if(isset($wp_query->query_vars['taxonomy']))
		$taxonomy = $wp_query->query_vars['taxonomy'];
	else
		$taxonomy = 'wpsc_product_category';

	$category = get_term_by($type,$slug,$taxonomy);
	return empty( $category ) ? false : $category->term_id;
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
 * wpsc_form_action
 *
 * Echo the form action for use in the template files
 *
 * @global <type> $wpec_form_action
 * @return <type>
 */
function wpsc_form_action() {
	echo wpsc_get_form_action();
}
	/**
	 * wpsc_get_form_action
	 *
	 * Return the form action for use in the template files
	 *
	 * @global <type> $wpec_form_action
	 * @return <type>
	 */
	function wpsc_get_form_action() {
		global $wpec_form_action;

		$product_id = wpsc_the_product_id();

		// Function has already ran in this page load
		if ( isset( $wpec_form_action ) ) {
			$action =  $wpec_form_action;

		// No global so figure it out
		} else {

			// Use external if set
			if ( wpsc_is_product_external() ) {
				$action = wpsc_product_external_link( $product_id );

			// Otherwise use this page
			} else {
				$action = wpsc_this_page_url();
			}
		}

		// Return form action
		return $action;
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

		if ( get_option( 'wpsc_share_this' ) == 1 ) {
			$remote_protocol = is_ssl() ? 'https://ws' : 'http://w';
			wp_enqueue_script( 'sharethis', $remote_protocol . '.sharethis.com/button/buttons.js', array(), false, true );
			wp_enqueue_script( 'wpsc-sharethis', WPSC_CORE_JS_URL . '/sharethis.js', array( 'jquery', 'sharethis' ), $version_identifier );
			wp_enqueue_style( 'wpsc-sharethis-css', WPSC_CORE_JS_URL . '/sharethis.css', false, $version_identifier, 'all' );
		}

		wp_enqueue_script( 'jQuery' );
		wp_enqueue_script( 'wp-e-commerce',               WPSC_CORE_JS_URL	. '/wp-e-commerce.js',                 array( 'jquery' ), $version_identifier );
		wp_enqueue_script( 'wp-e-commerce-dynamic', home_url( '/index.php?wpsc_user_dynamic_js=true', $scheme ), false,             $version_identifier );

		wp_localize_script( 'wp-e-commerce-dynamic', 'wpsc_ajax', array(
			'ajaxurl'   => admin_url( 'admin-ajax.php', 'relative' ),
			'spinner'   => esc_url( admin_url( 'images/wpspin_light.gif' ) ),
			'no_quotes' => __( 'It appears that there are no shipping quotes for the shipping information provided.  Please check the information and try again.', 'wpsc' )
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
if ( ! is_admin() )
	add_action( 'init', 'wpsc_enqueue_user_script_and_css' );

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


function wpsc_product_list_rss_feed() {
	$rss_url = add_query_arg( 'wpsc_action', 'rss', home_url( '/' ) );
	$rss_url = str_replace('&', '&amp;', $rss_url);
	$rss_url = esc_url( $rss_url ); // URL santization - IMPORTANT!

	echo "<link rel='alternate' type='application/rss+xml' title='" . get_option( 'blogname' ) . " Product List RSS' href='{$rss_url}'/>";
}
add_action( 'wp_head', 'wpsc_product_list_rss_feed' );

function wpsc_user_dynamic_js() {
	header( 'Content-Type: text/javascript' );
	header( 'Expires: ' . gmdate( 'r', mktime( 0, 0, 0, date( 'm' ), (date( 'd' ) + 12 ), date( 'Y' ) ) ) . '' );
	header( 'Cache-Control: public, must-revalidate, max-age=86400' );
	header( 'Pragma: public' );
?>
		jQuery.noConflict();

		/* base url */
		var base_url = "<?php echo site_url(); ?>";
		var WPSC_URL = "<?php echo WPSC_URL; ?>";
		var WPSC_IMAGE_URL = "<?php echo WPSC_IMAGE_URL; ?>";
		var WPSC_DIR_NAME = "<?php echo WPSC_DIR_NAME; ?>";
		var WPSC_CORE_IMAGES_URL = "<?php echo WPSC_CORE_IMAGES_URL; ?>";

		/* LightBox Configuration start*/
		var fileLoadingImage = "<?php echo WPSC_CORE_IMAGES_URL; ?>/loading.gif";
		var fileBottomNavCloseImage = "<?php echo WPSC_CORE_IMAGES_URL; ?>/closelabel.gif";
		var fileThickboxLoadingImage = "<?php echo WPSC_CORE_IMAGES_URL; ?>/loadingAnimation.gif";
		var resizeSpeed = 9;  // controls the speed of the image resizing (1=slowest and 10=fastest)
		var borderSize = 10;  //if you adjust the padding in the CSS, you will need to update this variable
<?php
	exit();
}
if ( isset( $_GET['wpsc_user_dynamic_js'] ) && ($_GET['wpsc_user_dynamic_js'] == 'true') )
	add_action( "init", 'wpsc_user_dynamic_js' );


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

add_action( 'update_option_product_image_width'     , 'wpsc_cache_to_upload' );
add_action( 'update_option_product_image_height'    , 'wpsc_cache_to_upload' );
add_action( 'update_option_single_view_image_width' , 'wpsc_cache_to_upload' );
add_action( 'update_option_single_view_image_height', 'wpsc_cache_to_upload' );
add_action( 'update_option_category_image_width'    , 'wpsc_cache_to_upload' );
add_action( 'update_option_category_image_height'   , 'wpsc_cache_to_upload' );

//Potentially unnecessary, as I believe this option is deprecated
add_action( 'update_option_show_categorybrands'     , 'wpsc_cache_to_upload' );

if ( isset( $_GET['wpsc_user_dynamic_css'] ) && 'true' == $_GET['wpsc_user_dynamic_css'] )
	add_action( 'plugins_loaded', 'wpsc_user_dynamic_css', 1 );

function wpsc_get_the_new_id($prod_id){
	global $wpdb;
	$post_id = (int)$wpdb->get_var($wpdb->prepare( "SELECT `post_id` FROM `{$wpdb->postmeta}` WHERE meta_key = %s AND `meta_value` = %d LIMIT 1", '_wpsc_original_id', $prod_id ));
	return $post_id;

}
// Template tags
/**
 * wpsc display products function
 * @return string - html displaying one or more products
 */
function wpsc_display_products_page( $query ) {
	global $wpdb, $wpsc_query,$wp_query, $wp_the_query;

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
		$saved_display = wpsc_get_customer_meta( 'display_type' );
		$display_type  = ! empty( $saved_display ) ? $saved_display : wpsc_check_display_type();

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

function wpsc_thesis_compat( $loop ) {
	$loop[1] = 'page';
	return $loop;
}

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
add_action('template_redirect', 'wpsc_all_products_on_page');

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

//displays a list of categories when the code [showcategories] is present in a post or page.
function wpsc_show_categories( $content ) {

	ob_start();
	include( wpsc_get_template_file_path( 'wpsc-category-list.php' ) );
	$output = ob_get_contents();

	ob_end_clean();
	return $output;

}

add_shortcode('showcategories', 'wpsc_show_categories');

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

if ( ! is_admin() )
	add_filter('request','wpec_remap_shop_subpages');

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
add_filter('request', 'wpsc_remove_page_from_query_string');

/* 19-02-09
 * add to cart shortcode function used for shortcodes calls the function in
 * product_display_functions.php
 */

function add_to_cart_shortcode( $content = '' ) {
	static $fancy_notification_output = false;
	if ( ! in_the_loop() )
		return $content;

	if ( preg_match_all( "/\[add_to_cart=([\d]+)\]/", $content, $matches ) ) {
		foreach ( $matches[1] as $key => $product_id ) {
			$original_string = $matches[0][$key];
			$output = wpsc_add_to_cart_button( $product_id, true );
			$content = str_replace( $original_string, $output, $content );
		}

		if ( ! $fancy_notification_output ) {
			$content .= wpsc_fancy_notifications( true );
			$fancy_notification_output = true;
		}
	}
	return $content;
}
function wpsc_enable_page_filters( $excerpt = '' ) {
	add_filter( 'the_content', 'add_to_cart_shortcode', 12 ); //Used for add_to_cart_button shortcode
	add_filter( 'the_content', 'wpsc_products_page', 1 );
	add_filter( 'the_content', 'wpsc_single_template',12 );
	add_filter( 'archive_template','wpsc_the_category_template');
	add_filter( 'the_title', 'wpsc_the_category_title',10 );
	add_filter( 'the_content', 'wpsc_place_shopping_cart', 12 );
	add_filter( 'the_content', 'wpsc_transaction_results', 12 );
	add_filter( 'the_content', 'wpsc_user_log', 12 );
	return $excerpt;
}

wpsc_enable_page_filters();

/**
 * Body Class Filter
 * @modified:     2009-10-14 by Ben
 * @description:  Adds additional wpsc classes to the body tag.
 * @param:        $classes = Array of body classes
 * @return:       (Array) of classes
 */
function wpsc_body_class( $classes ) {
	global $wp_query, $wpsc_query;
	$post_id = 0;
	if ( isset( $wp_query->post->ID ) )
		$post_id = $wp_query->post->ID;
	$page_url = get_permalink( $post_id );

	// If on a product or category page...
	if ( get_option( 'product_list_url' ) == $page_url ) {

		$classes[] = 'wpsc';

		if ( !is_array( $wpsc_query->query ) )
			$classes[] = 'wpsc-home';

		if ( wpsc_is_single_product ( ) ) {
			$classes[] = 'wpsc-single-product';
			if ( absint( $wpsc_query->products[0]['id'] ) > 0 ) {
				$classes[] = 'wpsc-single-product-' . $wpsc_query->products[0]['id'];
			}
		}

		if ( wpsc_is_in_category() && !wpsc_is_single_product() )
			$classes[] = 'wpsc-category';

		if ( isset( $wpsc_query->query_vars['category_id'] ) && absint( $wpsc_query->query_vars['category_id'] ) > 0 )
			$classes[] = 'wpsc-category-' . $wpsc_query->query_vars['category_id'];

	}

	// If viewing the shopping cart...
	if ( get_option( 'shopping_cart_url' ) == $page_url ) {
		$classes[] = 'wpsc';
		$classes[] = 'wpsc-shopping-cart';
	}

	// If viewing the transaction...
	if ( get_option( 'transact_url' ) == $page_url ) {
		$classes[] = 'wpsc';
		$classes[] = 'wpsc-transaction-details';
	}

	// If viewing your account...
	if ( get_option( 'user_account_url' ) == $page_url ) {
		$classes[] = 'wpsc';
		$classes[] = 'wpsc-user-account';
	}

	return $classes;
}

add_filter( 'body_class', 'wpsc_body_class' );

/**
 * Featured Product
 *
 * Refactoring Featured Product Plugin to utilize Sticky Post Status, available since WP 2.7
 * also utilizes Featured Image functionality, available as post_thumbnail since 2.9, Featured Image since 3.0
 * Main differences - Removed 3.8 conditions, removed meta box from admin, changed meta_values
 * Removes shortcode, as it automatically ties in to top_of_page hook if sticky AND featured product exists.
 *
 * @package wp-e-commerce
 * @since 3.8
 */
function wpsc_the_sticky_image( $product_id ) {
	$attached_images = (array)get_posts( array(
				'post_type' => 'attachment',
				'numberposts' => 1,
				'post_status' => null,
				'post_parent' => $product_id,
				'orderby' => 'menu_order',
				'order' => 'ASC'
			) );
	if ( has_post_thumbnail( $product_id ) ) {
		add_image_size( 'featured-product-thumbnails', 540, 260, TRUE );
		$image = get_the_post_thumbnail( $product_id, 'featured-product-thumbnails' );
		return $image;
	} elseif ( !empty( $attached_images ) ) {
		$attached_image = $attached_images[0];
		$image_link = wpsc_product_image( $attached_image->ID, 540, 260 );
		return '<img src="' . $image_link . '" alt="" />';
	} else {
		return false;
	}
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
if(get_option( 'wpsc_hide_featured_products' ) == 1){
	add_action( 'wpsc_top_of_products_page', 'wpsc_display_featured_products_page', 12 );
}


/**
 * wpsc_display_products_page class
 *
 * Shows only products from current category, but not from subcategories.
 *
 * @access public
 * @return void
 */

class WPSC_Hide_subcatsprods_in_cat {
	var $q;

	function get_posts( &$q ) {
		$this->q =& $q;
		if ( ( !isset($q->query_vars['taxonomy']) || ( "wpsc_product_category" != $q->query_vars['taxonomy'] )) )
			return false;

		add_action( 'posts_where', array( &$this, 'where' ) );
		add_action( 'posts_join', array( &$this, 'join' ) );
	}

	function where( $where ) {
		global $wpdb;

		remove_action( 'posts_where', array( &$this, 'where' ) );

		$term_id=$wpdb->get_var($wpdb->prepare('SELECT term_id FROM '.$wpdb->terms.' WHERE slug = %s ', $this->q->query_vars['term']));

		if ( !is_numeric( $term_id ) || $term_id < 1 )
			return $where;

		$term_taxonomy_id = $wpdb->get_var($wpdb->prepare('SELECT term_taxonomy_id FROM '.$wpdb->term_taxonomy.' WHERE term_id = %d and taxonomy = %s', $term_id, $this->q->query_vars['taxonomy']));

		if ( !is_numeric($term_taxonomy_id) || $term_taxonomy_id < 1)
			return $where;

		$field = preg_quote( "$wpdb->term_relationships.term_taxonomy_id", '#' );

		$just_one = $wpdb->prepare( " AND $wpdb->term_relationships.term_taxonomy_id = %d ", $term_taxonomy_id );
		if ( preg_match( "#AND\s+$field\s+IN\s*\(\s*(?:['\"]?\d+['\"]?\s*,\s*)*['\"]?\d+['\"]?\s*\)#", $where, $matches ) )
			$where = str_replace( $matches[0], $just_one, $where );
		else
			$where .= $just_one;

		return $where;
	}

	function join($join){
		global $wpdb;
		remove_action( 'posts_where', array( &$this, 'where' ) );
		remove_action( 'posts_join', array( &$this, 'join' ) );
		if( strpos($join, "JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id)" ) ){
			return $join;
		}
		$join .= " JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id)";
		return $join;
	}
}

function wpsc_hidesubcatprods_init() {
	$hide_subcatsprods = new WPSC_Hide_subcatsprods_in_cat;
	add_action( 'pre_get_posts', array( &$hide_subcatsprods, 'get_posts' ) );
}


$show_subcatsprods_in_cat = get_option( 'show_subcatsprods_in_cat' );
if(!$show_subcatsprods_in_cat)
	add_action( 'init', 'wpsc_hidesubcatprods_init' );


function wpsc_the_featured_image_fix($stuff){
	global $wp_query;
	remove_action('post_thumbnail_html','wpsc_the_featured_image_fix');
	if(isset($wp_query->query_vars['wpsc-product'])){
		$stuff ='';	?>
		<img src="<?php header_image(); ?>" width="<?php echo HEADER_IMAGE_WIDTH; ?>" height="<?php echo HEADER_IMAGE_HEIGHT; ?>" alt="" /><?php
	}
	return $stuff;

}

add_action('post_thumbnail_html','wpsc_the_featured_image_fix');
?>
