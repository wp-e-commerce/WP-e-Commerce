<?php

function wpsc_force_flush_theme_transients() {
	// Flush transients
	wpsc_flush_theme_transients( true );

	// Bounce back
	$sendback = wp_get_referer();
	wp_redirect( $sendback );

	exit();
}
if ( isset( $_REQUEST['wpsc_flush_theme_transients'] ) && ( $_REQUEST['wpsc_flush_theme_transients'] == 'true' ) )
	add_action( 'admin_init', 'wpsc_force_flush_theme_transients' );

function wpsc_backup_theme() {
	$wp_theme_path = get_stylesheet_directory();
	wpsc_recursive_copy( $wp_theme_path, WPSC_THEME_BACKUP_DIR );
	$_SESSION['wpsc_themes_backup'] = true;
	$sendback = wp_get_referer();
	wp_redirect( $sendback );

	exit();
}
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ( $_REQUEST['wpsc_admin_action'] == 'backup_themes' ) )
	add_action( 'admin_init', 'wpsc_backup_theme' );