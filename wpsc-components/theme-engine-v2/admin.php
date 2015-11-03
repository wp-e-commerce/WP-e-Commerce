<?php

define( 'WPSC_TE_V2_CSS_URL', WPSC_TE_V2_URL . '/admin/css' );
define( 'WPSC_TE_V2_JS_URL' , WPSC_TE_V2_URL . '/admin/js' );

add_action( 'wpsc_register_settings_tabs', '_wpsc_te2_register_settings_tabs', 10, 1 );
add_action( 'wpsc_load_settings_tab_class', '_wpsc_te2_load_settings_tab_class', 10, 1 );
add_action( 'admin_enqueue_scripts', '_wpsc_te2_action_admin_enqueue_styles' );
add_action( 'admin_enqueue_scripts', '_wpsc_te2_action_admin_enqueue_scripts' );

require_once( WPSC_TE_V2_HELPERS_PATH . '/settings-page.php' );

/**
 * Register and enqueue styles
 *
 * Action hook: admin_enqueue_scripts
 *
 * @since  0.1
 * @access private
 */
function _wpsc_te2_action_admin_enqueue_styles() {
	wp_register_style( 'wpsc-te2-select2', WPSC_TE_V2_CSS_URL . '/select2.min.css' );
	wp_register_style( 'wpsc-te2-admin', WPSC_TE_V2_CSS_URL . '/admin.css' );

	$current_screen = get_current_screen();

	if ( in_array( $current_screen->id, array( 'settings_page_wpsc-settings', 'widgets' ) ) ) {
		wp_enqueue_style( 'wpsc-te2-select2' );
	}

	wp_enqueue_style( 'wpsc-te2-admin' );
}

/**
 * Register and enqueue scripts
 *
 * Action hook: admin_enqueue_scripts
 *
 * @since  0.1
 */
function _wpsc_te2_action_admin_enqueue_scripts() {
	wp_register_script(
		'wpsc-auto-resize-field', WPSC_TE_V2_JS_URL . '/auto-resize-field.js',
		array( 'jquery' ),
		WPSC_VERSION
	);

	wp_register_script(
		'wpsc-fix-reading-settings', WPSC_TE_V2_JS_URL . '/fix-reading-settings.js',
		array( 'jquery' ),
		WPSC_VERSION
	);

	wp_register_script(
		'wpsc-presentation-settings', WPSC_TE_V2_JS_URL . '/presentation-settings.js',
		array( 'jquery' ),
		WPSC_VERSION
	);

	wp_register_script(
		'wpsc-multi-select', WPSC_TE_V2_JS_URL . '/multi-select.js',
		array( 'jquery', 'wpsc-select2' ),
		WPSC_VERSION
	);

	wp_register_script(
		'wpsc-select2', WPSC_TE_V2_JS_URL . '/select2.full.min.js',
		array( 'jquery' ),
		WPSC_VERSION
	);

	$current_screen = get_current_screen();

	switch ( $current_screen->id ) {
		case 'settings_page_wpsc-settings':
			// Settings->Store->Pages
			wp_enqueue_script( 'wpsc-auto-resize-field' );

			// Settings->Store->Presentation
			wp_enqueue_script( 'wpsc-multi-select' );
			wp_enqueue_script( 'wpsc-presentation-settings' );
			break;

		// Appearance->Widgets
		case 'widgets':
			wp_enqueue_script( 'wpsc-multi-select' );
			break;

		// Settings->Reading
		case 'options-reading':
			_wpsc_te2_enqueue_reading_settings_fix();
			break;
	}
}

/**
 * Use JavaScript to dynamically inject "Main store as front page" option in
 * Settings->Reading.
 *
 * This is not a very elegant hack, but it helps make it easier for user to
 * select the main store as the front page, rather than having to dive into
 * Settings->Store->Pages.
 *
 * @since  0.1
 * @access private
 */
function _wpsc_te2_enqueue_reading_settings_fix() {
	$store_as_front_page = wpsc_get_option( 'store_as_front_page' );

	// generate the HTML for the Main store as front page option in Settings->Reading
	// the radio's value is 'wpsc_main_store', but this will be reset back to
	// either 'posts' or 'page' in {@link _wpsc_te2_action_sanitize_show_on_front() }
	$dropdown = '<label>' . sprintf( __( 'Posts page: %s', 'wp-e-commerce' ), wp_dropdown_pages( array( 'id' => 'wpsc_page_for_posts', 'name' => 'page_for_posts', 'echo' => 0, 'show_option_none' => __( '&mdash; Select &mdash;', 'wp-e-commerce' ), 'option_none_value' => '0', 'selected' => get_option( 'page_for_posts' ) ) ) ) . '</label>';

	$html = '<div class="wpsc-main-store-on-front"><p><label><input class="tog" %1$s type="radio" name="show_on_front" value="wpsc_main_store" />%2$s</label></p>';
	$html .= '<ul><li>%3$s</li></ul></div>';

	// the radio box will be checked if 'wpsc_store_as_front_page' option is true
	$html = sprintf(
		$html,
		checked( $store_as_front_page, true, false ),
		__( 'Main store page', 'wp-e-commerce' ),
		$dropdown
	);

	// enqueue the script that will dynamically inject this HTML
	wp_enqueue_script( 'wpsc-fix-reading-settings' );
	wp_localize_script(
		'wpsc-fix-reading-settings',
		'WPSC_Fix_Reading',
		array(
			'html' => $html,
			'store_as_front_page' => $store_as_front_page,
		)
	);
}