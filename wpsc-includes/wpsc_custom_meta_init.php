<?php 

/**
 * Register the custom meta tables for the custom object types
 *
 * Use data from the legacy WPEC meta table.  Copies the data from the legacy
 * table to the custom meta table for the object type specified
 *
 * @since 3.9.0
 *
 */
function wpsc_register_custom_meta_tables () {

	global $wpdb;

	$meta_object_types = wpsc_custom_meta_object_types ();

	foreach ( $meta_object_types as $meta_object_type ) {
		$table_name = wpsc_meta_table_name( $meta_object_type );
		$wpdb_property = wpsc_meta_table_property( $meta_object_type );
		$wpdb->$wpdb_property = $table_name;

		if ( !wpsc_meta_table_exists( $meta_object_type ) ) {
			/* Vecuase a filter can override the list of object_types we 
			 * can keep meta for, we double check to be sure that the
			 * functions required to access that meta exist before doing
			 * anything iin the database
			 */ 
			if ( wpsc_check_meta_access_functions( $meta_object_type ) ) {
				wpsc_create_meta_table( $meta_object_type );
				wpsc_initialize_meta_table( $meta_object_type );
			}
		}
	}
	$end = microtime(true);

}

add_action( 'init', 'wpsc_register_custom_meta_tables', 1 );
add_action( 'switch_blog', 'wpsc_register_custom_meta_tables' );


/**
 * Tells us what custom object types have a custom meta table.
 *
 * Returns an array of strings holding the names of custom object types that
 * can be accessed using custom meta functions.  List can be changed with filter
 * wpsc_custom_meta_object_types.
 *
 * @since 3.9.0
 *
 * @return array object types that have custom meta. 
 */
function wpsc_custom_meta_object_types () {
	$meta_object_types = array ( 'cart_item', 'customer', 'category', 'purchase_log', 'variation' );
	$meta_object_types = apply_filters('wpsc_custom_meta_object_types', $meta_object_types);
	return $meta_object_types;
}

/**
 * The name of the meta table for a specific meta object type.
 *
 * @since 3.9.0
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 * @return string Name of the custom meta table
 */
function wpsc_meta_table_name ( $meta_object_type ) {
	global $wpdb;
	return $wpdb->prefix.$meta_object_type.'_meta';
}

/**
 * The name of the $wpdb meta property for a specific meta object type.
 *
 * @since 3.9.0
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 * @return string Name of the custom meta property
 */
function wpsc_meta_table_property ( $meta_object_type ) {
	global $wpdb;
	return $wpdb_property = $meta_object_type.'meta';
}


/**
 * Does the meta table for the object type already exist
 *
 * @since 3.9.0
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 * @return boolean True if the meta table for the object type exits, false otherwise
 */
function wpsc_meta_table_exists( $meta_object_type ) {
	global $wpdb;

	if ( ! $meta_object_type )
		return false;

	$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '%s' AND table_name = '%s';", $wpdb->dbname, wpsc_meta_table_name($meta_object_type) ) );

	return intval($count) == 1;
}

/**
 * Create the meta table for the specified object type
 *
 * @since 3.9.0
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 */
function wpsc_create_meta_table( $meta_object_type ) {
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	global $wpdb;
	global $charset_collate;
	
	$sql = "CREATE TABLE IF NOT EXISTS `".wpsc_meta_table_name($meta_object_type)."` ("
				."`meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,"
				."`".$meta_object_type."_id` bigint(20) unsigned NOT NULL DEFAULT '0',"
				."`meta_key` varchar(255) DEFAULT NULL,"
				."`meta_value` longtext,"
				."`meta_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,"
				."PRIMARY KEY  (`meta_id`),"
				."KEY `".$meta_object_type."_id` (`".$meta_object_type."_id`),"
				."KEY `meta_key` (`meta_key`(191)),"
				."KEY `meta_value` (`meta_value`(20)),"
				."KEY `meta_key_and_value` (`meta_key`(191),`meta_value`(32)),"
				."KEY `meta_timestamp_index` (`meta_timestamp`)"
				.") ". $charset_collate; 
			
	dbDelta( $sql );
}


/**
 * Initialize the meta table for the specified object type
 * 
 * Use data from the legacy WPEC meta table.  Copies the data from the legacy 
 * table to the custom meta table for the object type specified. Legacy data remains
 * untouched
 *
 * @since 3.9.0
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
*/

function wpsc_initialize_meta_table( $meta_object_type ) {
	global $wpdb;
	
	$legacy_meta_table  = $wpdb->prefix.'wpsc_meta';
	$sql = "SELECT meta_id, object_id, meta_key, meta_value FROM `{$legacy_meta_table}` WHERE object_type ='%s'"; 
	
	$old_meta_rows = $wpdb->get_results( $wpdb->prepare($sql, 'wpsc_'.$meta_object_type ) );
	
	foreach ( $old_meta_rows as $old_meta_row ) {
		add_metadata($meta_object_type, $old_meta_row->object_id, $old_meta_row->meta_key, $old_meta_row->meta_value, false);
	}	
}

/**
 * Get meta timestamp of the by object type, meta id and key, if multiple records exist
 * the timestamp of the newest record is returned
 * @since 3.9.0
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 * @param int $meta_id ID for a specific meta row
 * @return object Meta object or false.
 */
function wpsc_get_metadata_timestamp( $meta_object_type, $meta_id, $meta_key ) {
	global $wpdb;
	
	$meta_id = intval( $meta_id );

	if ( !empty($meta_object_type) && !empty($meta_id)  && !empty($meta_key) ) {	
		$wpdb_property = $meta_object_type.'meta';
	
		if ( !empty( $wpdb->$wpdb_property)) {
			$sql =  "SELECT meta_timestamp FROM ".wpsc_meta_table_name($meta_object_type)." WHERE meta_id = %d ORDER BY meta_timestamp DESC LIMIT 1";
			$timestamp = $wpdb->get_row( $wpdb->prepare($sql, $meta_id ) );
		}
	}
	
	if ( empty( $timestamp ) )
		$timestamp = false;

	return $timestamp;
}

/**
 * Confirm that the meta access functions exist and include them
 *
 * @since 3.9.0
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 * @return boolean True if meta functions file exists, false otehrwise
 */
function wpsc_check_meta_access_functions($meta_object_type) {
	
	$meta_functions_file = wpsc_meta_functions_file( $meta_object_type );
	if ( ! file_exists( $meta_functions_file ) ) {
	
		$template = file_get_contents( dirname( __FILE__ ) .'\meta_functions_template.txt' );
	
		$object_types = wpsc_custom_meta_object_types ();
	
		$msg =
			'<?php'.PHP_EOL.
			' /* '.PHP_EOL.
			' * NOTICE: '.PHP_EOL.
			' * This file was automatically created, strongly suggest that it not be edited directly.'.PHP_EOL.
			' * See the code in the file '.basename(__FILE__).' at line '.__LINE__.' for more details.'.PHP_EOL.
			' */'.PHP_EOL.
			'?>'.PHP_EOL.PHP_EOL;
		
		file_put_contents($meta_functions_file, $msg);
		$new_code = str_replace( 'THINGAMABOB', $meta_object_type, $template );
		file_put_contents($meta_functions_file, $new_code, FILE_APPEND);
		chmod ( $meta_functions_file, 555 );
		$meta_access_functions_ok = file_exists( $meta_functions_file ) ;
	} else {
		$meta_access_functions_ok = true;
	}
	
	return $meta_access_functions_ok;
}


/**
 * The name of the file we expect to contain the meta functions used to access 
 * custom object meta.
 *
 * @since 3.9.0
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 	* @return string Name of the custom meta property
 */
function wpsc_meta_functions_file ( $meta_object_type ) {
	$meta_functions_file = dirname( __FILE__ ) .'\wpsc_'.$meta_object_type.'_meta.php';
	return $meta_functions_file;
}



/* We allow the meta supported custom object types to be changed, but we don't load the
 * functions for an object type that has been removed.  This gives developers a chance to
 * stop the custom object type infrastructure from being used for a specific type.
 * include the function files for each of the supported custom object types 
 */
$meta_object_types = wpsc_custom_meta_object_types ();
foreach ( $meta_object_types as $meta_object_type ) {
	if ( wpsc_meta_table_exists( $meta_object_type ) && wpsc_check_meta_access_functions( $meta_object_type ) ) {
		$meta_functions_file = wpsc_meta_functions_file( $meta_object_type);
		include_once( $meta_functions_file );
	}
}



