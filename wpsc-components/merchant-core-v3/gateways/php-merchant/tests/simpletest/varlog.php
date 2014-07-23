<?php
/**
 * Outputs a string representation of a variable to the terminal
 *
 * @param string $var Variable to output
 * @return void
 */
function st_log( $var ) {
	global $argv;
	// If logging is enabled
	if ( in_array( '--enable-log', $argv ) ) {
		// Display the variable in the terminal
		var_export( $var );
	}
}
