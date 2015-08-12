<?php

function _wpsc_db_upgrade_4() {
	_wpsc_maybe_update_submitted_form_data_value();
	_wpsc_maybe_update_user_log_file();
}

function _wpsc_maybe_update_submitted_form_data_value() {
	global $wpdb;
	$wpdb->query( 'ALTER TABLE ' . WPSC_TABLE_SUBMITTED_FORM_DATA . ' MODIFY value LONGTEXT;' );
}

function _wpsc_maybe_update_user_log_file() {
	$hashes = array(
		'3.8'    => '1526bcf18869f9ea2f4061f528a1a21a',
		'3.8.4'  => '1d17c7fb086e2afcf942ca497629b4c9',
		'3.8.8'  => 'f9549ba1b1956c78f96b1551ab965c13',
		'3.8.9'  => '4d0bcba88d211147399e79661cf3b41d',
		'3.8.10' => '09e2cb9c753587c9228a4e9e8008a82f',
	);

	if ( function_exists( 'wpsc_flush_theme_transients' ) ) {
		wpsc_flush_theme_transients( true );
	}

	// Using TEv2
	if ( ! function_exists( 'wpsc_get_template_file_path' ) ) {
		return;
	}

	//Make sure the theme has actually been moved.
	$file = wpsc_get_template_file_path( 'wpsc-user-log.php' );

	if ( false !== strpos( WPSC_CORE_THEME_PATH, $file ) ) {
		return;
	}

	//If it has been moved, but it's the 3.8.10 version, we should be good to go.
	$hash = md5_file( $file );

	if ( $hashes['3.8.10'] === $hash ) {
		return;
	}

	//At this point, we know the file has been moved to the active file folder.  Checking now if it has been modified.
	if ( in_array( $hash, $hashes ) ) {
		//We now know that they've moved the file, but haven't actually changed anything.  We can safely overwrite the file with the new core file.
		@ copy( $file, path_join( get_stylesheet_directory(), 'wpsc-user-log.php' ) );
	} else {
		//This means they have indeed changed the file.  We need to add a notice letting them know about the issue and how to fix it.
		update_option( '_wpsc_3811_user_log_notice', '1' );
	}
}