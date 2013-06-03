<?php 
/**
 * Tells us which custom object types have a custom meta table and are managed
 * using the wordpress meta data infrastructure
 *
 * Returns an array of strings holding the names of cire object types that
 * can be accessed using wpsc meta functions.  This list should grow as the
 * data in wpsc_{dbprfix}_is ported to this custom emta infrastructure
 *
 * @since 3.8.12
 *
 * @return array object types that have custom meta.
 */
function wpsc_meta_core_object_types() {
	/* note that the 'shopper' custom object type is used instead of 'customer' to avoid 
	 * conflicts with exiting 'customer' meta functions.  The api for the 'customer' 
	 * meta functions work for the current visitor. The 'shopper' custom object type 
	 * allowes manipualtion of data related to any visitor to the web site. 
	 */ 
	$meta_object_types = array( 'cart_item', 'visitor' );
	return $meta_object_types;
}

/**
 * Tells us what custom object types have a custom meta table.
 *
 * Returns an array of strings holding the names of custom object types that
 * can be accessed using custom meta functions.  List can be changed with filter
 * wpsc_meta_custom_object_types.
 *
 * @since 3.8.12
 *
 * @return array object types that have custom meta.
 */
function wpsc_meta_custom_object_types() {
	$meta_object_types = apply_filters( 'wpsc_meta_custom_object_types' , array() );
	return $meta_object_types;
}

/**
 * Register the custom meta type and confirms that necessary tables exist 
 * for the custom object types
 *
 * Use data from the legacy WPEC meta table.  Copies the data from the legacy
 * table to the custom meta table for the object type specified
 *
 * @since 3.8.12
 * 
 * @param array $meta_object_types array of object type names, when null core 
 * object types are used
 *
 */
function wpsc_meta_register_types( $meta_object_types = null ) {
	if ( empty( $meta_object_types ) )
		$meta_object_types = wpsc_meta_core_object_types();
	
	foreach ( $meta_object_types as $meta_object_type ) {
		wpsc_meta_register_type( $meta_object_type );		
	}	
}

/**
 * Register the custom meta type within wpdb
 *
 * @since 3.8.12
 *
 * @param string $meta_object_type object type to setup with wpdb
 *
 */
function wpsc_meta_register_type( $meta_object_type ) {
	global $wpdb;

	$wpdb_property = wpsc_meta_table_property( $meta_object_type );
	if ( ! isset( $wpdb->$wpdb_property ) ) {		
		$table_name = wpsc_meta_table_name( $meta_object_type );		
		
		if ( ! wpsc_meta_table_exists( $meta_object_type ) ) {
			/* Becuase a filter can override the list of object types we
			 * keep meta for we double check to be sure that the
			* functions required to access that meta exist before doing
			* anything in the database
			*/
			if ( wpsc_check_meta_access_functions( $meta_object_type ) ) {
				wpsc_create_meta_table( $meta_object_type );
				wpsc_initialize_meta_table( $meta_object_type );
			}
		}
		
		$wpdb->$wpdb_property = $table_name;
		$wpdb->tables[] = $meta_object_type.'meta';		
	}
}

/**
 * The name of the meta table for a specific meta object type.
 *
 * @since 3.8.12
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 * @return string Name of the custom meta table
 */
function wpsc_meta_table_name( $meta_object_type ) {
	global $wpdb;
	return $wpdb->prefix.$meta_object_type.'_meta';
}

/**
 * The name of the $wpdb meta property for a specific meta object type.
 *
 * @since 3.8.12
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 * @return string Name of the custom meta property
 */
function wpsc_meta_table_property( $meta_object_type ) {
	global $wpdb;
	return $wpdb_property = $meta_object_type.'meta';
}

/**
 * Does the meta table for the object type already exist
 *
 * @since 3.8.12
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 * @return boolean True if the meta table for the object type exits, false otherwise
 */
function wpsc_meta_table_exists( $meta_object_type ) {
	global $wpdb;

	if ( ! $meta_object_type )
		return false;

	$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '%s' AND table_name = '%s';", $wpdb->dbname, wpsc_meta_table_name( $meta_object_type ) ) );

	return intval( $count ) == 1;
}

/**
 * Does the meta table for the object type already exist
 *
 * @since 3.8.12
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 	* @return boolean True if the meta table for the object type exits, false otherwise
 */
function wpsc_meta_table_empty( $meta_object_type ) {
	if ( ! $meta_object_type )
		return false;
	
	if ( wpsc_meta_table_exists( $meta_object_type ) ) {
		global $wpdb;		
		$count = $wpdb->get_var( 'SELECT COUNT(*) FROM `'.wpsc_meta_table_name( $meta_object_type ).'`' );
	} else {
		$count = 0;
	}

	return intval( $count ) == 0;
}

/**
 * Create the meta table for the specified object type
 *
 * @since 3.8.12
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 */
function wpsc_create_meta_table( $meta_object_type ) {
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	global $wpdb;
	global $charset_collate;
	
	$sql = 'CREATE TABLE IF NOT EXISTS '.wpsc_meta_table_name( $meta_object_type ).' ('
				.'meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT, '
				.$meta_object_type.'_id bigint(20) unsigned NOT NULL DEFAULT 0 , '
				.'meta_key varchar(255) DEFAULT NULL, '
				.'meta_value longtext, '
				.'meta_timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, '
				.'PRIMARY KEY  (meta_id), '
				.'KEY '.$meta_object_type.'_id ('.$meta_object_type.'_id), '
				.'KEY meta_key (meta_key(191)), '
				.'KEY meta_value (meta_value(20)), '
				.'KEY meta_key_and_value (meta_key(191),meta_value(32)), '
				.'KEY meta_timestamp_index (meta_timestamp) '
				.') '. $charset_collate; 
			
	dbDelta( $sql );
}

/**
 * Initialize the meta table for the specified object type
 * 
 * Use data from the legacy WPEC meta table.  Copies the data from the legacy 
 * table to the custom meta table for the object type specified. Legacy data remains
 * untouched
 *
 * @since 3.8.12
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
*/
function wpsc_initialize_meta_table( $meta_object_type ) {	
	wpsc_meta_register_type( $meta_object_type );
	if ( wpsc_meta_table_empty( $meta_object_type ) ) {
		global $wpdb;
		$legacy_meta_table = $wpdb->prefix.'wpsc_meta';
		$sql = "SELECT meta_id, object_id, meta_key, meta_value FROM `{$legacy_meta_table}` WHERE object_type ='%s'"; 
		
		$old_meta_rows = $wpdb->get_results( $wpdb->prepare( $sql , 'wpsc_'.$meta_object_type ) );
		
		foreach ( $old_meta_rows as $old_meta_row ) {			
			$meta_data = maybe_unserialize( $old_meta_row->meta_value );
			add_metadata( $meta_object_type, $old_meta_row->object_id, $old_meta_row->meta_key, $meta_data, false );			
		}
		
		// we now have the custom meta table, and maybe data in it, it is safe to load the access routines
		include_once( wpsc_meta_functions_file( $meta_object_type ) );
		
		do_action( "wpsc_loaded_{$meta_object_type}_meta_table" );
	}	
}

/**
 * Get meta timestamp of the by object type, meta id and key, if multiple records exist
 * the timestamp of the newest record is returned
 * @since 3.8.12
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 * @param int $meta_id ID for a specific meta row
 * @return object Meta object or false.
 */
function wpsc_get_metadata_timestamp( $meta_object_type, $meta_id, $meta_key ) {
	global $wpdb;
	
	$meta_id = intval( $meta_id );

	if ( ! empty($meta_object_type) && !empty($meta_id)  && !empty($meta_key) ) {	
		$wpdb_property = $meta_object_type.'meta';
	
		if ( ! empty( $wpdb->$wpdb_property ) ) {
			$sql = 'SELECT meta_timestamp FROM '.wpsc_meta_table_name( $meta_object_type ).' WHERE meta_id = %d ORDER BY meta_timestamp DESC LIMIT 1';
			$timestamp = $wpdb->get_row( $wpdb->prepare( $sql , $meta_id ) );
		}
	}
	
	if ( empty( $timestamp ) )
		$timestamp = false;

	return $timestamp;
}

/**
 * Calls function for each meta matching the timestamp criteria.  Callback function
 * will get a single parameter that is an object representing the meta.
 *
 * @since 3.8.12
 *
 * @param function $callback function to invoke once for each meta matching criteria
 * @param int|string $timestamp timestamp to compare meta items against, if int a unix timestamp is assumed,
 *								if string a mysql timestamp is assumed
 * @param string $comparison any one of the supported comparison operators,(=,>=,>,<=,<,<>,!=)
 * @param string $meta_key restrict testing of meta to the values with the specified meta key
 * @return int count of meta items matching the criteria, false on error
 */
function wpsc_get_meta_by_timestamp( $meta_object_type, $callback = null, $timestamp = 0, $comparison = '>', $meta_key = '' ) {
	global $wpdb;
	
	$meta_table = wpsc_meta_table_name( $meta_object_type );		
	if ( ($timestamp == 0) || empty( $timestamp ) ) {
		$sql = "SELECT * FROM `{$meta_table}` WHERE 1=1 ";
	} else {
		// validate the comparison operator
		if ( '=' != $comparison &&
				'>=' != $comparison &&
					'>' != $comparison &&
						'<=' != $comparison &&
							'<' != $comparison &&
								'<>' != $comparison &&
									'!=' != $comparison
		) {
			return false;
		}
		
		if ( is_int( $timestamp ) ) {
			$timestamp = date( 'Y-m-d H:i:s', $timestamp );
		}

		$sql = "SELECT * FROM {$meta_table} where meta_timestamp {$comparison} '{$timestamp}'";
	}
	
	if ( !empty ($meta_key ) ) {
		$sql = $wpdb->prepare( $sql . ' AND meta_key = %s', $meta_key );
	}
	
	$meta_rows = $wpdb->get_results( $sql, OBJECT  );
		
	if ( !empty ( $callback ) ) {
		foreach ( $meta_rows as $meta_row ) {
			call_user_func( $callback, $meta_row );
		}
	}
	
	return $wpdb->num_rows;
}




/**
 * Confirm that the meta access functions exist and include them
 *
 * @since 3.8.12
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 * @return boolean True if meta functions file exists, false otehrwise
 */
function wpsc_check_meta_access_functions( $meta_object_type ) {
	
	$meta_functions_file = wpsc_meta_functions_file( $meta_object_type );
	if ( ! file_exists( $meta_functions_file ) ) {	
		$template = file_get_contents( dirname( __FILE__ ) .'\wpsc-meta-functions-template.txt' );
	
		$msg = '<?php'.PHP_EOL.
			'/* '.PHP_EOL.
			'** NOTICE: '.PHP_EOL.
			'** This file was automatically created, strongly suggest that it not be edited directly.'.PHP_EOL.
			'** See the code in the file '.basename( __FILE__ ).' near line '.__LINE__.' for more details.'.PHP_EOL.
			'*/'.PHP_EOL.
			'?>'.PHP_EOL.PHP_EOL;
		
		file_put_contents( $meta_functions_file , $msg );
		$new_code = str_replace( '{$OBJECT_TYPE}' , $meta_object_type, $template );
		file_put_contents( $meta_functions_file , $new_code , FILE_APPEND );
		chmod( $meta_functions_file, 555 );
		$meta_access_functions_ok = file_exists( $meta_functions_file );
	} else {
		$meta_access_functions_ok = true;
	}
	
	return $meta_access_functions_ok;
}

/**
 * The name of the file we expect to contain the meta functions used to access 
 * custom object meta.
 *
 * @since 3.8.12
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 * @return string Name of the custom meta property
 */
function wpsc_meta_functions_file( $meta_object_type ) {
	$file_name = 'wpsc-meta-'.$meta_object_type.'.php';
	$file_name = str_replace( '_', '-', $file_name );
	$meta_functions_file = dirname( __FILE__ ) .'/'.$file_name;
	return $meta_functions_file;
}

/*
 * Because the core types are created at plugin activation or upgrade these core types should
 * alwyas be laoded. This logic will load (include) the function files for each of the supported 
 * core meta object types. As the list of core meta types expands this code will automatically 
 * pick up the new types. 
 * 
 * The check for the core types here is done out of an abundance of caution to
 * confirm that the init/upgrade process completed properly.
 */
$meta_object_types = wpsc_meta_core_object_types();
foreach ( $meta_object_types as $meta_object_type ) {
	if ( wpsc_check_meta_access_functions( $meta_object_type ) ) {
		include_once( wpsc_meta_functions_file( $meta_object_type ) );
	}
}

/* We allow the wpsc meta supported custom object types to be added to, but we don't load the
 * functions for an object type that doesn't have a database table.  The side effect of this
* is that plugins or themes that use custom meta types won't have the tables until after the
* first page is viewed by a user or the admin when that page implements the filter that
* defines the custom meta types.
*
* This should be ok becuase the first page viewed should be an admin page not a user facing
* page. The first view of the admin page will cause the meta tables and custom access functions
* to be created/validated/upgraded.
*
*/
wpsc_meta_register_types( wpsc_meta_core_object_types() );

/*
 * We allow the custom object types to be extended, to the initialization for this 
 * after all plugins are loaded
 */
function wpsc_init_custom_object_types() {
	$meta_object_types = wpsc_meta_custom_object_types();
	if ( ! empty( $meta_object_types ) ){
		wpsc_meta_register_types( wpsc_meta_core_object_types() );
	}
}

add_action( 'plugins_loaded', 'wpsc_init_custom_object_types' );

/*
 *  migration routines to take meta from the old wpsc meta table and
 *  move the to the custom meta infrastructure
 */
function wpsc_meta_migrate_cart_item() {
	wpsc_initialize_meta_table( 'cart_item' );
}
