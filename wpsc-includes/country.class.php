<?php

/////////////////////////////////////////////////////////////////////////////////////////////////////////
// In release 3.8.14 the WPSC_Country class was moved to wpsc-country.class.php to be consistent with
// the other geography classes added in the release
//
// if deprecated processing is enabled we will give a message, just as if we were allowed to put class
// methods in the deprecated file, if deprecated processing is not enabled we exit with the method, much
// like would happen with an undefined function call.
//
// TODO: This processing is added at version 3.8.14 and intended to be removed after a reasonable number
// of interim releases. See GitHub Issue https://github.com/wp-e-commerce/WP-e-Commerce/issues/1016
/////////////////////////////////////////////////////////////////////////////////////////////////////////

if ( defined( 'WPSC_LOAD_DEPRECATED' ) && WPSC_LOAD_DEPRECATED ) {
	_wpsc_deprecated_file(
							__FILE__,
							'3.8.14',
							__( 'You should not be including the country.class.php (or any WPeC) file directly. The WPSC_Country class as been moved to wpsc-country.class.php', 'wpsc' )
						);
	require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-country.class.php' );
}
