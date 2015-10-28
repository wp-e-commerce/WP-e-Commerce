<?php

function wpsc_auto_update() {
	global $wpdb;

	include( WPSC_FILE_PATH . '/wpsc-updates/updating_tasks.php' );

	wpsc_create_or_update_tables();
	wpsc_create_upload_directories();
	wpsc_product_files_htaccess();
	wpsc_check_and_copy_files();

	$wpsc_version = get_option( 'wpsc_version' );
	$wpsc_minor_version = get_option( 'wpsc_minor_version' );

	if ( $wpsc_version === false )
		add_option( 'wpsc_version', WPSC_VERSION, '', 'no' );
	else
		update_option( 'wpsc_version', WPSC_VERSION );

	if ( $wpsc_minor_version === false )
		add_option( 'wpsc_minor_version', WPSC_MINOR_VERSION, '', 'no' );
	else
		update_option( 'wpsc_minor_version', WPSC_MINOR_VERSION );

	if ( version_compare( $wpsc_version, '3.8', '<' ) )
		update_option( 'wpsc_needs_update', true );
	else
		update_option( 'wpsc_needs_update', false );
}

function wpsc_install() {
	global $wpdb, $user_level, $wp_rewrite, $wp_version, $wpsc_page_titles;

	$table_name    = $wpdb->prefix . "wpsc_product_list";

	if( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name ) {
		// Table doesn't exist
		add_option( 'wpsc_purchaselogs_fixed', true );
	}

	// the only consistent and reliable way to detect whether this is a fresh install is by checking
	// whether WPSC_TABLE_CART_CONTENTS exists. This is an unfortunate hack, but we can do away with
	// it in 3.9 as we'll drop support for 3.7.x then
	if ( $wpdb->get_var( "SHOW TABLES LIKE '" . WPSC_TABLE_CART_CONTENTS . "'" ) != WPSC_TABLE_CART_CONTENTS )
		add_option( 'wpsc_db_version', WPSC_DB_VERSION, '', 'no' );

	// run the create or update code here.
	wpsc_create_or_update_tables();
	wpsc_create_upload_directories();

	// All code to add new database tables and columns must be above here
	$wpsc_version       = get_option( 'wpsc_version', 0 );
	$wpsc_minor_version = get_option( 'wpsc_minor_version', 0 );

	if ( $wpsc_version === false ) {
		add_option( 'wpsc_version', WPSC_VERSION, '', 'no' );
	} else {
		update_option( 'wpsc_version', WPSC_VERSION );
	}

	if ( $wpsc_minor_version === false )
		add_option( 'wpsc_minor_version', WPSC_MINOR_VERSION, '', 'no' );
	else
		update_option( 'wpsc_minor_version', WPSC_MINOR_VERSION );

	if ( version_compare( $wpsc_version, '3.8', '<' ) )
		update_option( 'wpsc_needs_update', true );
	else
		update_option( 'wpsc_needs_update', false );

	if('' == get_option('show_subcatsprods_in_cat'))
		update_option('show_subcatsprods_in_cat',0);

	if('' == get_option('wpsc_share_this'))
		update_option('wpsc_share_this',0);

	if('' == get_option('wpsc_crop_thumbnails'))
		update_option('wpsc_crop_thumbnails',0);

	if('' == get_option('wpsc_products_per_page'))
		update_option('wpsc_products_per_page',0);

	if('' == get_option('wpsc_force_ssl'))
		update_option('wpsc_force_ssl',0);

	if('' == get_option('use_pagination'))
		update_option('use_pagination',0);

	if('' == get_option('hide_name_link'))
		update_option('hide_name_link',0);

	if('' == get_option('wpsc_enable_comments'))
		update_option('wpsc_enable_comments',0);

	if('' == get_option('multi_add'))
		update_option('multi_add',1);

	if('' == get_option('hide_addtocart_button'))
		update_option('hide_addtocart_button',0);

	if('' == get_option('wpsc_addtocart_or_buynow'))
		update_option('wpsc_addtocart_or_buynow',0);


	add_option( 'show_thumbnails', 1, '', 'no' );
	add_option( 'show_thumbnails_thickbox', 1, '', 'no' );

	require_once( WPSC_FILE_PATH . '/wpsc-core/wpsc-functions.php' );
	require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-theme-engine-bootstrap.php' );

	$te = get_option( 'wpsc_get_active_theme_engine', '1.0' );

	if ( '1.0' == $te ) {
		add_option( 'product_list_url', '', '', 'no' );
		add_option( 'shopping_cart_url', '', '', 'no' );
		add_option( 'checkout_url', '', '', 'no' );
		add_option( 'transact_url', '', '', 'no' );
		/*
		 * This part creates the pages and automatically puts their URLs into the options page.
		 * As you can probably see, it is very easily extendable, just pop in your page and the deafult content in the array and you are good to go.
		 */
		$post_date = date( "Y-m-d H:i:s" );
		$post_date_gmt = gmdate( "Y-m-d H:i:s" );

		$pages = array(
			'products-page' => array(
				'name' => 'products-page',
				'title' => __( 'Products Page', 'wp-e-commerce' ),
				'tag' => '[productspage]',
				'option' => 'product_list_url'
			),
			'checkout' => array(
				'name' => 'checkout',
				'title' => __( 'Checkout', 'wp-e-commerce' ),
				'tag' => '[shoppingcart]',
				'option' => 'shopping_cart_url'
			),
			'transaction-results' => array(
				'name' => 'transaction-results',
				'title' => __( 'Transaction Results', 'wp-e-commerce' ),
				'tag' => '[transactionresults]',
				'option' => 'transact_url'
			),
			'your-account' => array(
				'name' => 'your-account',
				'title' => __( 'Your Account', 'wp-e-commerce' ),
				'tag' => '[userlog]',
				'option' => 'user_account_url'
			)
		);

		//indicator. if we will create any new pages we need to flush.. :)
		$newpages = false;

		//get products page id. if there's no products page then create one
		$products_page_id = $wpdb->get_var("SELECT id FROM `" . $wpdb->posts . "` WHERE `post_content` LIKE '%" . $pages['products-page']['tag'] . "%'	AND `post_type` != 'revision'");
		if( empty($products_page_id) ){
			$products_page_id = wp_insert_post( array(
				'post_title' 	=>	$pages['products-page']['title'],
				'post_type' 	=>	'page',
				'post_name'		=>	$pages['products-page']['name'],
				'comment_status'=>	'closed',
				'ping_status' 	=>	'closed',
				'post_content' 	=>	$pages['products-page']['tag'],
				'post_status' 	=>	'publish',
				'post_author' 	=>	1,
				'menu_order'	=>	0
			));
			$newpages = true;
		}
		update_option( $pages['products-page']['option'], _get_page_link($products_page_id) );
		//done. products page created. no we can unset products page data and create all other pages.

		//unset products page
		unset($pages['products-page']);

		//create other pages
		foreach( (array)$pages as $page ){
			//check if page exists and get it's ID
			$page_id = $wpdb->get_var("SELECT id FROM `" . $wpdb->posts . "` WHERE `post_content` LIKE '%" . $page['tag'] . "%'	AND `post_type` != 'revision'");
			//if there's no page - create
			if( empty($page_id) ){
				$page_id = wp_insert_post( array(
					'post_title' 	=>	$page['title'],
					'post_type' 	=>	'page',
					'post_name'		=>	$page['name'],
					'comment_status'=>	'closed',
					'ping_status' 	=>	'closed',
					'post_content' 	=>	$page['tag'],
					'post_status' 	=>	'publish',
					'post_author' 	=>	1,
					'menu_order'	=>	0,
					'post_parent'	=>	$products_page_id
				));
				$newpages = true;
			}
			//update option
			update_option( $page['option'], get_permalink( $page_id ) );
			//also if this is shopping_cart, then update checkout url option
			if ( $page['option'] == 'shopping_cart_url' )
				update_option( 'checkout_url', get_permalink( $page_id ) );
		}

		//if we have created any new pages, then flush... do we need to do this? probably should be removed
		if ( $newpages ) {
			wp_cache_delete( 'all_page_ids', 'pages' );
			wpsc_update_permalink_slugs();
		}
	}

	add_option( 'payment_gateway', '','', 'no' );

	$default_payment_gateways_names = array(
		'chronopay'						=> '',
		'wpsc_merchant_paypal_express'	=> '',
		'wpsc_merchant_paypal_pro'		=> '',
		'wpsc_merchant_paypal_standard'	=> '',
		'amazon-payments'           	=> ''
	);

	$existing_payment_gateways_names = get_option( 'payment_gateway_names' );

	$new_payment_gateways_name = array_merge( $default_payment_gateways_names, (array) $existing_payment_gateways_names);
	update_option( 'payment_gateway_names', $new_payment_gateways_name );


	if ( function_exists( 'register_sidebar' ) )
		add_option( 'cart_location', '4','', 'no' );
	else
		add_option( 'cart_location', '1', '', 'no' );

	add_option( 'currency_type', '136','', 'no' );
	add_option( 'currency_sign_location', '3', '', 'no' );

	add_option( 'gst_rate', '1','', 'no' );

	add_option( 'max_downloads', '1','', 'no' );

	add_option( 'display_pnp', '1', '', 'no' );

	add_option( 'display_specials', '1', '', 'no' );
	add_option( 'do_not_use_shipping', '0', '', 'no' );

	add_option( 'postage_and_packaging', '0','', 'no' );
    add_option( 'shipwire', '0', '', 'no' );
    add_option( 'shipwire_test_server', '0', '', 'no' );

	add_option( 'purch_log_email', get_option( 'admin_email', '' ), '', 'no' );
	add_option( 'return_email', '', '', 'no' );
	add_option( 'terms_and_conditions', '', '', 'no' );

	add_option( 'default_brand', 'none', '', 'no' );
	add_option( 'wpsc_default_category', 'all', '', 'no' );

	add_option( 'product_view', 'default', "", 'no' );
	add_option( 'add_plustax', 'default', "", '1' );


	if ( !((get_option( 'show_categorybrands' ) > 0) && (get_option( 'show_categorybrands' ) < 3)) )
		update_option( 'show_categorybrands', 2 );

	// PayPal options
	add_option( 'paypal_business', '', '', 'no' );
	add_option( 'paypal_url', '', '', 'no' );
	add_option( 'paypal_ipn', '1', '', 'no' );


	add_option( 'paypal_multiple_business', '', '', 'no' );

	add_option( 'paypal_multiple_url', "https://www.paypal.com/cgi-bin/webscr" );

	add_option( 'product_ratings', '0', '', 'no' );
	add_option( 'wpsc_email_receipt', __( 'Thank you for purchasing with %shop_name%, any items to be shipped will be processed as soon as possible, any items that can be downloaded can be downloaded using the links on this page. All prices include tax and postage and packaging where applicable.
You ordered these items:
%product_list%%total_shipping%%total_price%', 'wp-e-commerce' ), '', 'no' );

	add_option( 'wpsc_email_admin', __( '%product_list%%total_shipping%%total_price%', 'wp-e-commerce' ), '','no' );

	add_option( 'wpsc_selected_theme', 'default', '', 'no' );

	add_option( 'product_image_height', 148);
	add_option( 'product_image_width', 148);

	add_option( 'category_image_height', 148 );
	add_option( 'category_image_width', 148 );

	add_option( 'single_view_image_height', 148 );
	add_option( 'single_view_image_width', 148 );

	add_option( 'wpsc_gallery_image_height', 31 );
	add_option( 'wpsc_gallery_image_width', 31 );

	add_option( 'wpsc_thousands_separator', ',' );
	add_option( 'wpsc_decimal_separator', '.' );

	add_option( 'custom_gateway_options', array( 'wpsc_merchant_testmode' ), '', 'no' );

	add_option( 'wpsc_category_url_cache', array(), '', 'no' );

	// add in some default tax settings
	add_option( 'wpec_taxes_inprice', 'exclusive' );

	add_option( 'wpec_taxes_product', 'replace' );

	add_option( 'wpec_taxes_logic', 'billing' );

	wpsc_product_files_htaccess();

	// Product categories, temporarily register them to create first default category if none exist
	// @todo: investigate those require once lines and move them to right place (not from here, but from their original location, which seems to be wrong, since i cant access wpsc_register_post_types and wpsc_update_categorymeta here) - Vales <v.bakaitis@gmail.com>
	wpsc_core_load_page_titles();
	wpsc_register_post_types();
	$category_list = get_terms( 'wpsc_product_category', 'hide_empty=0&parent=0' );
	if ( count( $category_list ) == 0 ) {
		require_once( WPSC_FILE_PATH . '/wpsc-includes/meta.functions.php' );

		$new_category = wp_insert_term( __( 'Product Category', 'wp-e-commerce' ), 'wpsc_product_category', "parent=0" );
		$category_id = $new_category['term_id'];
		$term = get_term_by( 'id', $new_category['term_id'], 'wpsc_product_category' );
		$url_name = $term->slug;

		wpsc_update_categorymeta( $category_id, 'nice-name', $url_name );
		wpsc_update_categorymeta( $category_id, 'description', __( "This is a description", 'wp-e-commerce' ) );
		wpsc_update_categorymeta( $category_id, 'image', '' );
		wpsc_update_categorymeta( $category_id, 'fee', '0' );
		wpsc_update_categorymeta( $category_id, 'active', '1' );
		wpsc_update_categorymeta( $category_id, 'order', '0' );
	}

	flush_rewrite_rules( false );
	wpsc_theme_engine_v2_activate();
}

function wpsc_product_files_htaccess() {
	if ( !is_file( WPSC_FILE_DIR . ".htaccess" ) ) {
		$htaccess = "order deny,allow\n\r";
		$htaccess .= "deny from all\n\r";
		$htaccess .= "allow from none\n\r";
		$filename = WPSC_FILE_DIR . ".htaccess";
		$file_handle = @ fopen( $filename, 'w+' );
		@ fwrite( $file_handle, $htaccess );
		@ fclose( $file_handle );
		@ chmod( $file_handle, 0665 );
	}
}

function wpsc_check_and_copy_files() {
	$upload_path = 'wp-content/plugins/' . WPSC_DIR_NAME;

	$wpsc_dirs['files']['old'] = ABSPATH . "{$upload_path}/files/";
	$wpsc_dirs['files']['new'] = WPSC_FILE_DIR;

	$wpsc_dirs['previews']['old'] = ABSPATH . "{$upload_path}/preview_clips/";
	$wpsc_dirs['previews']['new'] = WPSC_PREVIEW_DIR;

	// I don't include the thumbnails directory in this list, as it is a subdirectory of the images directory and is moved along with everything else
	$wpsc_dirs['images']['old'] = ABSPATH . "{$upload_path}/product_images/";
	$wpsc_dirs['images']['new'] = WPSC_IMAGE_DIR;

	$wpsc_dirs['categories']['old'] = ABSPATH . "{$upload_path}/category_images/";
	$wpsc_dirs['categories']['new'] = WPSC_CATEGORY_DIR;
	$incomplete_file_transfer = false;

	foreach ( $wpsc_dirs as $wpsc_dir ) {
		if ( is_dir( $wpsc_dir['old'] ) ) {
			$files_in_dir = glob( $wpsc_dir['old'] . "*" );
			$stat = stat( $wpsc_dir['new'] );

			if ( count( $files_in_dir ) > 0 ) {
				foreach ( $files_in_dir as $file_in_dir ) {
					$file_name = str_replace( $wpsc_dir['old'], '', $file_in_dir );
					if ( @ rename( $wpsc_dir['old'] . $file_name, $wpsc_dir['new'] . $file_name ) ) {
						if ( is_dir( $wpsc_dir['new'] . $file_name ) ) {
							$perms = $stat['mode'] & 0000775;
						} else {
							$perms = $stat['mode'] & 0000665;
						}

						@ chmod( ($wpsc_dir['new'] . $file_name ), $perms );
					} else {
						$incomplete_file_transfer = true;
					}
				}
			}
		}
	}
	if ( $incomplete_file_transfer == true ) {
		add_option( 'wpsc_incomplete_file_transfer', 'default', "", 'true' );
	}
}

function wpsc_create_upload_directories() {

	// Create the required folders
	$folders = array(
		WPSC_UPLOAD_DIR,
		WPSC_FILE_DIR,
		WPSC_PREVIEW_DIR,
		WPSC_IMAGE_DIR,
		WPSC_THUMBNAIL_DIR,
		WPSC_CATEGORY_DIR,
		WPSC_USER_UPLOADS_DIR,
		WPSC_CACHE_DIR,
		WPSC_UPGRADES_DIR,
		// WPSC_THEMES_PATH
	);
	foreach ( $folders as $folder ) {
		wp_mkdir_p( $folder );
		@ chmod( $folder, 0775 );
	}
}

function wpsc_copy_themes_to_uploads() {
	$old_theme_path = WPSC_CORE_THEME_PATH;
	$new_theme_path = WPSC_THEMES_PATH;
	$new_dir = @ opendir( $new_theme_path );
	$num = 0;
	$file_names = array( );
	while ( ($file = @ readdir( $new_dir )) !== false ) {
		if ( is_dir( $new_theme_path . $file ) && ($file != "..") && ($file != ".") ) {
			$file_names[] = $file;
		}
	}
	if ( count( $file_names ) < 1 ) {
		$old_dir = @ opendir( $old_theme_path );
		while ( ($file = @ readdir( $old_dir )) !== false ) {
			if ( is_dir( $old_theme_path . $file ) && ($file != "..") && ($file != ".") ) {
				@ wpsc_recursive_copy( $old_theme_path . $file, $new_theme_path . $file );
			}
		}
	}
}

/**
 * wpsc_create_or_update_tables count function,
 * * @return boolean true on success, false on failure
 */
function wpsc_create_or_update_tables( $debug = false ) {
	global $wpdb;
	// creates or updates the structure of the shopping cart tables

	include( WPSC_FILE_PATH . '/wpsc-updates/database_template.php' );

	$template_hash = sha1( serialize( $wpsc_database_template ) );

	// Filter for adding to or altering the wpsc database template, make sure you return the array your function gets passed, else you will break updating the database tables
	$wpsc_database_template = apply_filters( 'wpsc_alter_database_template', $wpsc_database_template );

	$failure_reasons = array( );
	$upgrade_failed = false;
	foreach ( (array)$wpsc_database_template as $table_name => $table_data ) {
		// check that the table does not exist under the correct name, then checkk if there was a previous name, if there was, check for the table under that name too.
		if ( !$wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) && (!isset( $table_data['previous_names'] ) || (isset( $table_data['previous_names'] ) && !$wpdb->get_var( "SHOW TABLES LIKE '{$table_data['previous_names']}'" )) ) ) {
			//if the table does not exixt, create the table
			$constructed_sql_parts = array( );
			$constructed_sql = "CREATE TABLE `{$table_name}` (\n";

			// loop through the columns
			foreach ( (array)$table_data['columns'] as $column => $properties ) {
				$constructed_sql_parts[] = "`$column` $properties";
			}
			// then through the indexes
			foreach ( (array)$table_data['indexes'] as $properties ) {
				$constructed_sql_parts[] = "$properties";
			}
			$constructed_sql .= implode( ",\n", $constructed_sql_parts );
			$constructed_sql .= "\n) ENGINE=MyISAM";


			// if mySQL is new enough, set the character encoding
			if ( method_exists( $wpdb, 'db_version' ) && version_compare( $wpdb->db_version(), '4.1', '>=' ) ) {
				$constructed_sql .= " CHARSET=utf8";
			}
			$constructed_sql .= ";";

			if ( !$wpdb->query( $constructed_sql ) ) {
				$upgrade_failed = true;
				$failure_reasons[] = $wpdb->last_error;
			}

			if ( isset( $table_data['actions']['after']['all'] ) && is_callable( $table_data['actions']['after']['all'] ) ) {
				$table_data['actions']['after']['all']();
			}
		} else {
			// check to see if the new table name is in use
			if ( !$wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) && (isset( $table_data['previous_names'] ) && $wpdb->get_var( "SHOW TABLES LIKE '{$table_data['previous_names']}'" )) ) {
				$wpdb->query( "ALTER TABLE	`{$table_data['previous_names']}` RENAME TO `{$table_name}`;" );
				$failure_reasons[] = $wpdb->last_error;
			}

			//check to see if the table needs updating
			$existing_table_columns = array( );
			//check and possibly update the character encoding
			if ( method_exists( $wpdb, 'db_version' ) && version_compare( $wpdb->db_version(), '4.1', '>=' ) ) {
				$table_status_data = $wpdb->get_row( "SHOW TABLE STATUS LIKE '$table_name'", ARRAY_A );
				if ( $table_status_data['Collation'] != 'utf8_general_ci' ) {
					$wpdb->query( "ALTER TABLE `$table_name`	DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci" );
				}
			}

			if ( isset( $table_data['actions']['before']['all'] ) && is_callable( $table_data['actions']['before']['all'] ) ) {
				$table_data['actions']['before']['all']();
			}

			//get the column list
			$existing_table_column_data = $wpdb->get_results( "SHOW FULL COLUMNS FROM `$table_name`", ARRAY_A );

			foreach ( (array)$existing_table_column_data as $existing_table_column ) {
				$column_name = $existing_table_column['Field'];
				$existing_table_columns[] = $column_name;

				$null_match = false;
				if ( 'NO' == $existing_table_column['Null'] ) {
					if ( isset( $table_data['columns'][$column_name] ) && stristr( $table_data['columns'][$column_name], "NOT NULL" ) !== false ) {
						$null_match = true;
					}
				} else {
					if ( isset( $table_data['columns'][$column_name] ) && stristr( $table_data['columns'][$column_name], "NOT NULL" ) === false ) {
						$null_match = true;
					}
				}

				if ( isset( $table_data['columns'][$column_name] ) && ((stristr( $table_data['columns'][$column_name], $existing_table_column['Type'] ) === false) || ($null_match != true)) ) {
					if ( isset( $table_data['actions']['before'][$column_name] ) && is_callable( $table_data['actions']['before'][$column_name] ) ) {
						$table_data['actions']['before'][$column_name]( $column_name );
					}
					if ( !$wpdb->query( "ALTER TABLE `$table_name` CHANGE `$column_name` `$column_name` {$table_data['columns'][$column_name]} " ) ) {
						$upgrade_failed = true;
						$failure_reasons[] = $wpdb->last_error;
					}
				}
			}
			$supplied_table_columns = array_keys( $table_data['columns'] );

			// compare the supplied and existing columns to find the differences
			$missing_or_extra_table_columns = array_diff( $supplied_table_columns, $existing_table_columns );

			if ( count( $missing_or_extra_table_columns ) > 0 ) {
				foreach ( (array)$missing_or_extra_table_columns as $missing_or_extra_table_column ) {
					if ( isset( $table_data['columns'][$missing_or_extra_table_column] ) ) {
						//table column is missing, add it
						$index = array_search( $missing_or_extra_table_column, $supplied_table_columns ) - 1;

						$previous_column = isset( $supplied_table_columns[$index] ) ? $supplied_table_columns[$index] : '';
						if ( $previous_column != '' ) {
							$previous_column = "AFTER `$previous_column`";
						}
						$constructed_sql = "ALTER TABLE `$table_name` ADD `$missing_or_extra_table_column` " . $table_data['columns'][$missing_or_extra_table_column] . " $previous_column;";
						if ( !$wpdb->query( $constructed_sql ) ) {
							$upgrade_failed = true;
							$failure_reasons[] = $wpdb->last_error;
						}
						// run updating functions to do more complex work with default values and the like
						if ( isset( $table_data['actions']['after'][$missing_or_extra_table_column] ) && is_callable( $table_data['actions']['after'][$missing_or_extra_table_column] ) ) {
							$table_data['actions']['after'][$missing_or_extra_table_column]( $missing_or_extra_table_column );
						}
					}
				}
			}

			if ( isset( $table_data['actions']['after']['all'] ) && is_callable( $table_data['actions']['after']['all'] ) ) {
				$table_data['actions']['after']['all']();
			}
			// get the list of existing indexes
			$existing_table_index_data = $wpdb->get_results( "SHOW INDEX FROM `$table_name`", ARRAY_A );
			$existing_table_indexes = array( );
			foreach ( $existing_table_index_data as $existing_table_index ) {
				$existing_table_indexes[] = $existing_table_index['Key_name'];
			}

			$existing_table_indexes = array_unique( $existing_table_indexes );
			$supplied_table_indexes = array_keys( $table_data['indexes'] );

			// compare the supplied and existing indxes to find the differences
			$missing_or_extra_table_indexes = array_diff( $supplied_table_indexes, $existing_table_indexes );

			if ( count( $missing_or_extra_table_indexes ) > 0 ) {
				foreach ( $missing_or_extra_table_indexes as $missing_or_extra_table_index ) {
					if ( isset( $table_data['indexes'][$missing_or_extra_table_index] ) ) {
						$constructed_sql = "ALTER TABLE `$table_name` ADD " . $table_data['indexes'][$missing_or_extra_table_index] . ";";
						if ( !$wpdb->query( $constructed_sql ) ) {
							$upgrade_failed = true;
							$failure_reasons[] = $wpdb->last_error;
						}
					}
				}
			}
		}
	}

	if ( $upgrade_failed !== true ) {
		update_option( 'wpsc_database_check', $template_hash );
		return true;
	} else {
		return $failure_reasons;
	}
}

/**
 * The following functions are used exclusively in database_template.php
 */

/**
 * wpsc_add_currency_list function,	converts values to decimal to satisfy mySQL strict mode
 * * @return boolean true on success, false on failure
 */
function wpsc_add_currency_list() {
	global $wpdb, $currency_sql;
	require_once(WPSC_FILE_PATH . "/wpsc-updates/currency_list.php");
	$currency_data = $wpdb->get_var( "SELECT COUNT(*) AS `count` FROM `" . WPSC_TABLE_CURRENCY_LIST . "`" );
	if ( $currency_data == 0 ) {
		$currency_array = explode( "\n", $currency_sql );
		foreach ( $currency_array as $currency_row ) {
			$wpdb->query( $currency_row );
		}
	}
}

/**
 * wpsc_add_region_list function,	converts values to decimal to satisfy mySQL strict mode
 * * @return boolean true on success, false on failure
 */
function wpsc_add_region_list() {
	global $wpdb;
	$add_regions = $wpdb->get_var( "SELECT COUNT(*) AS `count` FROM `" . WPSC_TABLE_REGION_TAX . "`" );
	if ( $add_regions < 1 ) {
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '100', 'Alberta', 'AB', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '100', 'British Columbia', 'BC', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '100', 'Manitoba', 'MB', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '100', 'New Brunswick', 'NB', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '100', 'Newfoundland and Labrador', 'NL', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '100', 'Northwest Territories', 'NT', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '100', 'Nova Scotia', 'NS', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '100', 'Nunavut', 'NU', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '100', 'Ontario', 'ON', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '100', 'Prince Edward Island', 'PE', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '100', 'Quebec', 'QC', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '100', 'Saskatchewan', 'SK', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '100', 'Yukon', 'YK', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Alabama', 'AL', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Alaska', 'AK', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Arizona', 'AZ', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Arkansas', 'AR', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'California', 'CA', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Colorado', 'CO', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Connecticut', 'CT', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Delaware', 'DE', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Florida', 'FL', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Georgia', 'GA', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Hawaii', 'HI', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Idaho', 'ID', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Illinois', 'IL', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Indiana', 'IN', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Iowa', 'IA', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Kansas', 'KS', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Kentucky', 'KY', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Louisiana', 'LA', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Maine', 'ME', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Maryland', 'MD', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Massachusetts', 'MA', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Michigan', 'MI', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Minnesota', 'MN', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Mississippi', 'MS', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Missouri', 'MO', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Montana', 'MT', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Nebraska', 'NE', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Nevada', 'NV', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'New Hampshire', 'NH', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'New Jersey', 'NJ', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'New Mexico', 'NM', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'New York', 'NY', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'North Carolina', 'NC', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'North Dakota', 'ND', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Ohio', 'OH', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Oklahoma', 'OK', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Oregon', 'OR', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Pennsylvania', 'PA', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Rhode Island', 'RI', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'South Carolina', 'SC', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'South Dakota', 'SD', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Tennessee', 'TN', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Texas', 'TX', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Utah', 'UT', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Vermont', 'VT', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Virginia', 'VA', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Washington', 'WA', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Washington DC', 'DC', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'West Virginia', 'WV', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Wisconsin', 'WI', '0')" );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_REGION_TAX . "` ( `country_id` , `name` ,`code`, `tax` ) VALUES ( '136', 'Wyoming', 'WY', '0')" );
	}

	if ( $wpdb->get_var( "SELECT COUNT(*) FROM `" . WPSC_TABLE_REGION_TAX . "` WHERE `code`=''" ) > 0 ) {
		$wpdb->query( "UPDATE `" . WPSC_TABLE_REGION_TAX . "` SET `code` = 'AB' WHERE `name` IN('Alberta') LIMIT 1 ;" );
		$wpdb->query( "UPDATE `" . WPSC_TABLE_REGION_TAX . "` SET `code` = 'BC' WHERE `name` IN('British Columbia') LIMIT 1 ;" );
		$wpdb->query( "UPDATE `" . WPSC_TABLE_REGION_TAX . "` SET `code` = 'MB' WHERE `name` IN('Manitoba') LIMIT 1 ;" );
		$wpdb->query( "UPDATE `" . WPSC_TABLE_REGION_TAX . "` SET `code` = 'NK' WHERE `name` IN('New Brunswick') LIMIT 1 ;" );
		$wpdb->query( "UPDATE `" . WPSC_TABLE_REGION_TAX . "` SET `code` = 'NF' WHERE `name` IN('Newfoundland') LIMIT 1 ;" );
		$wpdb->query( "UPDATE `" . WPSC_TABLE_REGION_TAX . "` SET `code` = 'NT' WHERE `name` IN('Northwest Territories') LIMIT 1 ;" );
		$wpdb->query( "UPDATE `" . WPSC_TABLE_REGION_TAX . "` SET `code` = 'NS' WHERE `name` IN('Nova Scotia') LIMIT 1 ;" );
		$wpdb->query( "UPDATE `" . WPSC_TABLE_REGION_TAX . "` SET `code` = 'ON' WHERE `name` IN('Ontario') LIMIT 1 ;" );
		$wpdb->query( "UPDATE `" . WPSC_TABLE_REGION_TAX . "` SET `code` = 'PE' WHERE `name` IN('Prince Edward Island') LIMIT 1 ;" );
		$wpdb->query( "UPDATE `" . WPSC_TABLE_REGION_TAX . "` SET `code` = 'PQ' WHERE `name` IN('Quebec') LIMIT 1 ;" );
		$wpdb->query( "UPDATE `" . WPSC_TABLE_REGION_TAX . "` SET `code` = 'SN' WHERE `name` IN('Saskatchewan') LIMIT 1 ;" );
		$wpdb->query( "UPDATE `" . WPSC_TABLE_REGION_TAX . "` SET `code` = 'YT' WHERE `name` IN('Yukon') LIMIT 1 ;" );
		$wpdb->query( "UPDATE `" . WPSC_TABLE_REGION_TAX . "` SET `code` = 'NU' WHERE `name` IN('Nunavut') LIMIT 1 ;" );
	}
}

/**
 * wpsc_add_checkout_fields function,	converts values to decimal to satisfy mySQL strict mode
 * * @return boolean true on success, false on failure
 */
function wpsc_add_checkout_fields() {
	global $wpdb;
	$data_forms = $wpdb->get_results( "SELECT COUNT(*) AS `count` FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "`", ARRAY_A );

	if ( isset( $data_forms[0] ) && $data_forms[0]['count'] == 0 ) {

		$sql = " INSERT INTO `" . WPSC_TABLE_CHECKOUT_FORMS . "` ( `name`, `type`, `mandatory`, `display_log`, `default`, `active`, `checkout_order`, `unique_name`) VALUES ( '" . __( 'Your billing/contact details', 'wp-e-commerce' ) . "', 'heading', '0', '0', '1', '1', 1,''),
	( '" . __( 'First Name', 'wp-e-commerce' ) . "', 'text', '1', '1', '1', '1', 2,'billingfirstname'),
	( '" . __( 'Last Name', 'wp-e-commerce' ) . "', 'text', '1', '1', '1', '1', 3,'billinglastname'),
	( '" . __( 'Address', 'wp-e-commerce' ) . "', 'address', '1', '0', '1', '1', 4,'billingaddress'),
	( '" . __( 'City', 'wp-e-commerce' ) . "', 'city', '1', '0', '1', '1', 5,'billingcity'),
	( '" . __( 'State', 'wp-e-commerce' ) . "', 'text', '0', '0', '1', '1', 6,'billingstate'),
	( '" . __( 'Country', 'wp-e-commerce' ) . "', 'country', '1', '0', '1', '1', 7,'billingcountry'),
	( '" . __( 'Postal Code', 'wp-e-commerce' ) . "', 'text', '0', '0', '1', '1', 8,'billingpostcode'),
	( '" . __( 'Email', 'wp-e-commerce' ) . "', 'email', '1', '1', '1', '1', 9,'billingemail'),
	( '" . __( 'Shipping Address', 'wp-e-commerce' ) . "', 'heading', '0', '0', '1', '1', 10,'delivertoafriend'),
	( '" . __( 'First Name', 'wp-e-commerce' ) . "', 'text', '0', '0', '1', '1', 11,'shippingfirstname'),
	( '" . __( 'Last Name', 'wp-e-commerce' ) . "', 'text', '0', '0', '1', '1', 12,'shippinglastname'),
	( '" . __( 'Address', 'wp-e-commerce' ) . "', 'address', '0', '0', '1', '1', 13,'shippingaddress'),
	( '" . __( 'City', 'wp-e-commerce' ) . "', 'city', '0', '0', '1', '1', 14,'shippingcity'),
	( '" . __( 'State', 'wp-e-commerce' ) . "', 'text', '0', '0', '1', '1', 15,'shippingstate'),
	( '" . __( 'Country', 'wp-e-commerce' ) . "', 'delivery_country', '0', '0', '1', '1', 16,'shippingcountry'),
	( '" . __( 'Postal Code', 'wp-e-commerce' ) . "', 'text', '0', '0', '1', '1', 17,'shippingpostcode');";

		$wpdb->query( $sql );
		$wpdb->query( "INSERT INTO `" . WPSC_TABLE_CHECKOUT_FORMS . "` ( `name`, `type`, `mandatory`, `display_log`, `default`, `active`, `checkout_order`, `unique_name` ) VALUES ( '" . __( 'Phone', 'wp-e-commerce' ) . "', 'text', '0', '0', '', '1', '8','billingphone');" );
	}
}
function wpsc_rename_checkout_column(){
	global $wpdb;
	$sql = "SHOW COLUMNS FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` LIKE 'checkout_order'";
	$col = $wpdb->get_results($sql);
	if(empty($col)){
		$sql = "ALTER TABLE  `" . WPSC_TABLE_CHECKOUT_FORMS . "` CHANGE  `order`  `checkout_order` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0'";
		$wpdb->query($sql);
	}

}

/**
 * In 3.8.8, we removed the ability for the user to delete or add core checkout fields (things like billingfirstname, billinglastname etc.) in order to reduce user error.
 * Mistakenly deleting or duplicating those fields could cause unexpected bugs with checkout form validation.
 *
 * Some users have encountered an issue where, if they had previously deleted a core checkout field, now they can't add it back again.
 * With this function, we should check to see whether any core fields are missing (by checking the uniquenames)
 * If there are some missing, we automatically generate those with the intended uniquename.
 *
 * We set the 'active' field to 0, so as to mitigate any unintended consequences of adding additional fields.
 *
 * @since 3.8.8.2
 * @return none
 */
function wpsc_3882_database_updates() {
	global $wpdb;

	// Check if we have done this before
	if ( version_compare( get_option( 'wpsc_version' ), '3.8.8.2', '>=' ) )
		return;

	$unique_names = array(
		'billingfirstname'  => __( 'First Name', 'wp-e-commerce' ),
		'billinglastname'   => __( 'Last Name', 'wp-e-commerce' ),
		'billingaddress'    => __( 'Address', 'wp-e-commerce' ),
		'billingcity'       => __( 'City', 'wp-e-commerce' ),
		'billingstate'      => __( 'State', 'wp-e-commerce' ),
		'billingcountry'    => __( 'Country', 'wp-e-commerce' ),
		'billingemail'      => __( 'Email', 'wp-e-commerce' ),
		'billingphone'      => __( 'Phone', 'wp-e-commerce' ),
		'billingpostcode'   => __( 'Postal Code', 'wp-e-commerce' ),
		'delivertoafriend'  => __( 'Shipping Address', 'wp-e-commerce' ),
		'shippingfirstname' => __( 'First Name', 'wp-e-commerce' ),
		'shippinglastname'  => __( 'Last Name', 'wp-e-commerce' ),
		'shippingaddress'   => __( 'Address', 'wp-e-commerce' ),
		'shippingcity'      => __( 'City', 'wp-e-commerce' ),
		'shippingstate'     => __( 'State', 'wp-e-commerce' ),
		'shippingcountry'   => __( 'Country', 'wp-e-commerce' ),
		'shippingpostcode'  => __( 'Postal Code', 'wp-e-commerce' ),
	);

	// Check if any uniquenames are missing
	$current_columns = array_filter( $wpdb->get_col( $wpdb->prepare( 'SELECT unique_name FROM ' . WPSC_TABLE_CHECKOUT_FORMS ) ) );

	$columns_to_add = array_diff_key( $unique_names, array_flip( $current_columns ) );

	if ( empty( $columns_to_add ) )
		return update_option( 'wpsc_version', '3.8.8.2' );

	foreach ( $columns_to_add as $unique_name => $name ) {

			// We need to add the row.  A few cases to check for type.  Quick and procedural felt like less overkill than a switch statement
			$type = 'text';
			$type = stristr( $unique_name, 'address' ) ? 'address'         : $type;
			$type = stristr( $unique_name, 'city' )    ? 'city'            : $type;
			$type = 'billingcountry'  == $unique_name  ? 'country'         : $type;
			$type = 'billingemail'    == $unique_name  ? 'email'           : $type;
			$type = 'shippingcountry' == $unique_name  ? 'deliverycountry' : $type;

			$wpdb->insert( WPSC_TABLE_CHECKOUT_FORMS,
				array( 'unique_name' => $unique_name, 'active' => '0', 'type' => $type, 'name' => $name, 'checkout_set' => '0' ),
				array( '%s', '%d', '%s', '%s', '%d' )
			);
	}

	// Update option to database to indicate that we have patched this.
	update_option( 'wpsc_version', '3.8.8.2' );
}

function wpsc_theme_engine_v2_activate() {
	$path = WPSC_FILE_PATH . '/wpsc-components/theme-engine-v2';
	require_once( $path . '/core.php' );
	_wpsc_te_v2_includes();
	wpsc_register_post_types();
	flush_rewrite_rules( true );
	update_option( 'transact_url', wpsc_get_checkout_url( 'results' ) );
	WPSC_Settings::get_instance();
	/**
	 * Runs after the WPSC Theme engine V2 is activated
	 */
	do_action( 'wpsc_theme_engine_v2_activate' );
}
