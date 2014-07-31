<?php
/**
 * Collection of functions to display variables and text
 * to the terminal for debugging.
 *
 * @package    SimpleTest
 * @subpackage WebTester
 * @version    $Id: user_agent.php 2011 2011-04-29 08:22:48Z pp11 $

 */

/**
 * Outputs a string representation of a variable to the terminal
 *
 * @param mixed $var Variable to output
 * @return void
 */
function st_log( $var ) {
	global $argv;
	// Checks that logging is enabled
	if ( in_array( '--enable-log', $argv ) ) {
		// Display the variable in the terminal
		var_export( $var );
	}
}

/**
 * Print text to the terminal
 *
 * @param string $var String to output
 * @return void
 */
function st_echo( $var ) {
	global $argv;
	// Checks that logging is enabled
	if ( in_array( '--enable-log', $argv ) ) {
		// Outputs text to the terminal
		echo( $var );
	}
}	
