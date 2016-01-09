<?php

/**
 * Includes WP-CLI command files, and registers commands with WP-CLI.
 */

require_once( WPSC_FILE_PATH . '/wpsc-includes/wp-cli/wpsc-wp-cli-category.php');
WP_CLI::add_command( 'wpsc-category', 'WPSC_WP_CLI_Category_Command' );

require_once( WPSC_FILE_PATH . '/wpsc-includes/wp-cli/wpsc-wp-cli-product-tag.php');
WP_CLI::add_command( 'wpsc-product-tag', 'WPSC_WP_CLI_Product_Tag_Command' );
