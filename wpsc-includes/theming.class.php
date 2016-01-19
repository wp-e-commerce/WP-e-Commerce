<?php
function wpsc_move_theme_images(){
	$image_dir = WPSC_CORE_THEME_PATH.'wpsc-images';
	$end_location = trailingslashit( get_stylesheet_directory() ).'wpsc-images';
	@mkdir( $end_location );
	$imgdr = opendir($image_dir);
	while ( false !== ( $file = readdir( $imgdr )) ) {
		@ copy( $image_dir . '/' . $file, $end_location . '/' . $file );
	}
	closedir( $imgdr );
}
/**
 * WP eCommerce theme porting class
 *
 * This class is responsible for moving all of the core template files from the
 * plugin folder to the active theme folder on new installs.
 *
 * On upgrades, it is responsible for checking appropriate folders for themes,
 * converting and porting to the active theme folder.
 *
 * @package wp-e-commerce
 * @since 3.8
 */
class wpsc_theming {

	var $active_wp_theme;
	var $active_wp_style;
	var $active_wpsc_theme;
	var $theme_file_prefix;
	var $templates_to_move;
	var $list_of_templates;

	/**
	 * wpsc_theming
	 *
	 * Construct
	 *
	 * @return
	 */
	public function __construct() {
		check_admin_referer( 'wpsc_copy_themes' );

		$this->active_wp_style   = trailingslashit( get_stylesheet_directory() );
		$this->templates_to_move = isset( $_POST['wpsc_templates_to_port'] ) ? $_POST['wpsc_templates_to_port'] : array();
		$this->list_of_templates = wpsc_list_product_templates( $this->active_wp_style );
		$this->theme_file_prefix = 'wpsc-';

		if ( $this->files_exist() ) {
			return;
		} else {
			$_SESSION['wpsc_themes_copied_results'] = array();
			// WP-WPSC theme doesn't exist, so let's figure out where we're porting from, either the plugin directory or the wpsc-themes directory
			$theme_location        = $this->theme_location();
			$this->active_wp_theme = trailingslashit( get_stylesheet_directory() );

			// Now that we have the theme location, let's copy it over to the themes directory and mod from there.
			$this->move_theme( $theme_location, $this->active_wp_style );

			// The rest of this is ported from the previous copy_theme function
			$_SESSION['wpsc_themes_copied'] = true;

			$sendback = wp_get_referer();
			//$sendback = add_query_arg('tab', $_SESSION['wpsc_settings_curr_page'], remove_query_arg('tab', $sendback));
			wp_redirect( $sendback );
		}
	}

	/**
	 * files_exist()
	 *
	 * Checks to see which theme files exist in the current WP theme folder
	 * and which theme files that have been selected but have not been moved over
	 *
	 * @access public
	 * @since 3.8
	 * @param None
	 * @return true if no templates need to be moved or false if some templates do need to be moved
	 */
	function files_exist() {

		if( empty( $this->templates_to_move ) ) {
			$_SESSION['wpsc_theme_empty'] = true;
			wp_redirect( admin_url('options-general.php?page=wpsc-settings&tab=presentation') );

		}


		$results = array_diff( $this->templates_to_move, $this->list_of_templates );
		$this->templates_to_move = $results;
		// If theme already exists, we're set, do nothing
		// This should also indicate a new install
		if ( count( $results ) == 0 )
			return true;
		else
			return false;
	}

	/*
	 * Determines the current theme location
	 * @access public
	 *
	 * @since 3.8
	 * @param None
	 * @return None
	 */
	function theme_location() {

		$selected_theme         = get_option( 'wpsc_selected_theme' );
		$active_wpsc_theme_path = WPSC_OLD_THEMES_PATH . $selected_theme;

		// Check if theme exists in uploads folder. If so, that's theme location.
		if ( file_exists( $active_wpsc_theme_path . '/functions.php' ) )
			$theme_location = $active_wpsc_theme_path;

		// If it's not there, the theme location will be the plugins folder.
		else
			$theme_location = WPSC_THEMES_PATH;

		return $theme_location;
	}

	function recursive_copy( $src, $dst ) {

		if ( $src != WPSC_THEMES_PATH )
			$theme_file_prefix = $this->theme_file_prefix;
		else
			$theme_file_prefix = '';

		$dir = opendir( $src );
		@mkdir( $dst );

		while ( false !== ( $file = readdir( $dir )) ) {
			if ( in_array( $file, $this->templates_to_move ) ) {
				if ( is_dir( $src . '/' . $file ) )
					$this->recursive_copy( $src . '/' . $file, $dst . '/' . $file );
				else
					$_SESSION['wpsc_themes_copied_results'][] = @ copy( $src . '/' . $file, $dst . '/' . $theme_file_prefix . $file );
			}
		}
		closedir( $dir );
	}

	/* Moves, renames, and appends header and footer functions to theme if they do not currently have it.
	 * @access public
	 *
	 * @since 3.8
	 * @param old - Current location of theme
	 * @param new -New location for theme
	 * @return None
	 */
	function move_theme( $old, $new ) {

		if ( $old != WPSC_THEMES_PATH )
			$theme_file_prefix = $this->theme_file_prefix;
		else
			$theme_file_prefix = '';

		$this->recursive_copy( $old, $new );
		$path = $new;
		$dh   = opendir( $old );

		while ( false !== ( $file = readdir( $dh ) ) ) {
			if ( $file != "." && $file != ".." && !strstr( $file, ".svn" ) && !strstr( $file, "images" ) && ( strstr( $file, 'wpsc-' ) || strstr($file, '.css') ) ) {
				if('wpsc-default.css' == $file)
					wpsc_move_theme_images();
				if ( in_array( $file, $this->templates_to_move ) ) {
					if ( !strstr( $file, "functions" ) && !strstr( $file, 'widget' ) ) {
						$file_data = file_get_contents( $old . "/" . $file );
						$_SESSION['wpsc_themes_copied_results'][] = @file_put_contents( $path . "/" . $file, $file_data );
						rename( $path . "/" . $file, $path . "/" . $theme_file_prefix . $file );
					}
				}
			}
		}
		closedir( $dh );

		do_action( 'wpsc_move_theme' );
	}
}
/**
 * Initializes WPSC_Theming global.
 *
 * A relic of days gone by, an awkwardly named class intended for use in the migration of theme templates from
 * the core theme folders into the currently active theme folder.
 *
 * @since  3.8.14.4
 * @return void
 */
function wpsc_init_theming_global() {
	global $wpsc_theming;
	$wpsc_theming = new wpsc_theming();
}

if ( isset( $_REQUEST['wpsc_move_themes'] ) && ! empty( $_REQUEST['wpsc_move_themes'] ) ) {
	add_action( 'admin_init', 'wpsc_init_theming_global' );
}