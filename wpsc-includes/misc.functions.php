<?php

/**
 * WP eCommerce misc functions
 *
 * These are the WPSC miscellaneous functions
 *
 * @package wp-e-commerce
 * @since 3.7
 */

/**
 * WPSC find purchlog status name looksthrough the wpsc_purchlog_statuses variable to find the name of the given status
 *
 * @since 3.8
 * $param int $id the id for the region
 * @param string $return_value either 'name' or 'code' depending on what you want returned
 */
function wpsc_find_purchlog_status_name( $purchlog_status ) {
	global $wpsc_purchlog_statuses;
	foreach ( $wpsc_purchlog_statuses as $status ) {
		if ( $status['order'] == $purchlog_status ) {
			$status_name = $status['label'];
		}
	}
	return $status_name;
}

/**
 * WPSC get state by id function, gets either state code or state name depending on param
*
 * @since 3.7
 * $param int $id the id for the region
 * @param string $return_value either 'name' or 'code' depending on what you want returned
 */
function wpsc_get_state_by_id( $id, $return_value ) {
	global $wpdb;
	$sql = $wpdb->prepare( "SELECT " . esc_sql( $return_value ) . " FROM `" . WPSC_TABLE_REGION_TAX . "` WHERE `id`= %d", $id );
	$value = $wpdb->get_var( $sql );
	return $value;
}

function wpsc_country_has_state($country_code){
	global $wpdb;
	$country_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `".WPSC_TABLE_CURRENCY_LIST."` WHERE `isocode`= %s LIMIT 1", $country_code ), ARRAY_A );
	return $country_data;
}

/**
 * WPSC add new user function, validates and adds a new user, for the
 *
 * @since 3.7
 *
 * @param string $user_login The user's username.
 * @param string $password The user's password.
 * @param string $user_email The user's email (optional).
 * @return int The new user's ID.
 */
function wpsc_add_new_user( $user_login, $user_pass, $user_email ) {
	require_once(ABSPATH . WPINC . '/registration.php');
	$errors = new WP_Error();
	$user_login = sanitize_user( $user_login );
	$user_email = apply_filters( 'user_registration_email', $user_email );

	// Check the username
	if ( $user_login == '' ) {
		$errors->add( 'empty_username', __( '<strong>ERROR</strong>: Please enter a username.', 'wpsc' ) );
	} elseif ( !validate_username( $user_login ) ) {
		$errors->add( 'invalid_username', __( '<strong>ERROR</strong>: This username is invalid.  Please enter a valid username.', 'wpsc' ) );
		$user_login = '';
	} elseif ( username_exists( $user_login ) ) {
		$errors->add( 'username_exists', __( '<strong>ERROR</strong>: This username is already registered, please choose another one.', 'wpsc' ) );
	}

	// Check the e-mail address
	if ( $user_email == '' ) {
		$errors->add( 'empty_email', __( '<strong>ERROR</strong>: Please type your e-mail address.', 'wpsc' ) );
	} elseif ( !is_email( $user_email ) ) {
		$errors->add( 'invalid_email', __( '<strong>ERROR</strong>: The email address isn&#8217;t correct.', 'wpsc' ) );
		$user_email = '';
	} elseif ( email_exists( $user_email ) ) {
		$errors->add( 'email_exists', __( '<strong>ERROR</strong>: This email is already registered, please choose another one.', 'wpsc' ) );
	}

	if ( $errors->get_error_code() ) {
		return $errors;
	}
	$user_id = wp_create_user( $user_login, $user_pass, $user_email );
	if ( !$user_id ) {
		$errors->add( 'registerfail', sprintf( __( '<strong>ERROR</strong>: Couldn&#8217;t register you... please contact the <a href="mailto:%s">webmaster</a> !', 'wpsc' ), get_option( 'admin_email' ) ) );
		return $errors;
	}
	$credentials = array( 'user_login' => $user_login, 'user_password' => $user_pass, 'remember' => true );
	$user = wp_signon( $credentials );
	return $user;

	//wp_new_user_notification($user_id, $user_pass);
}

/**
 * Deprecated function
 *
 * @deprecated 3.8.9
 */
function wpsc_post_title_seo( $title ) {
	global $wpdb, $page_id, $wp_query;
	$new_title = wpsc_obtain_the_title();
	if ( $new_title != '' ) {
		$title = $new_title;
	}
	return esc_html( $title );
}

//add_filter( 'single_post_title', 'wpsc_post_title_seo' );

/**
 * WPSC canonical URL function
 * Needs a recent version
 * @since 3.7
 * @param int product id
 * @return bool true or false
 */
function wpsc_change_canonical_url( $url = '' ) {
	global $wpdb, $wp_query, $wpsc_page_titles;

	if ( $wp_query->is_single == true && 'wpsc-product' == $wp_query->query_vars['post_type']) {
		$url = get_permalink( $wp_query->get_queried_object()->ID );
	}
	return apply_filters( 'wpsc_change_canonical_url', $url );
}

add_filter( 'aioseop_canonical_url', 'wpsc_change_canonical_url' );

function wpsc_insert_canonical_url() {
	$wpsc_url = wpsc_change_canonical_url( null );
	echo "<link rel='canonical' href='$wpsc_url' />\n";
}

function wpsc_canonical_url() {
	$wpsc_url = wpsc_change_canonical_url( null );
	if ( $wpsc_url != null ) {
		remove_action( 'wp_head', 'rel_canonical' );
		add_action( 'wp_head', 'wpsc_insert_canonical_url' );
	}
}
add_action( 'template_redirect', 'wpsc_canonical_url' );
// check for all in one SEO pack and the is_static_front_page function
if ( is_callable( array( "All_in_One_SEO_Pack", 'is_static_front_page' ) ) ) {

	function wpsc_change_aioseop_home_title( $title ) {
		global $aiosp, $aioseop_options;

		if ( (get_class( $aiosp ) == 'All_in_One_SEO_Pack') && $aiosp->is_static_front_page() ) {
			$aiosp_home_title = $aiosp->internationalize( $aioseop_options['aiosp_home_title'] );
			$new_title = wpsc_obtain_the_title();
			if ( $new_title != '' ) {
				$title = str_replace( $aiosp_home_title, $new_title, $title );
			}
		}
		return $title;
	}

	add_filter( 'aioseop_home_page_title', 'wpsc_change_aioseop_home_title' );
}

function wpsc_set_aioseop_description( $data ) {
	$replacement_data = wpsc_obtain_the_description();
	if ( $replacement_data != '' ) {
		$data = $replacement_data;
	}
	return $data;
}

add_filter( 'aioseop_description', 'wpsc_set_aioseop_description' );

function wpsc_set_aioseop_keywords( $data ) {
	global $wpdb, $wp_query, $wpsc_title_data, $aioseop_options;

	if ( isset( $wp_query->query_vars['product_url_name'] ) ) {
		$product_name = $wp_query->query_vars['product_url_name'];
		$product_id = $wpdb->get_var( "SELECT `product_id` FROM `" . WPSC_TABLE_PRODUCTMETA . "` WHERE `meta_key` IN ( 'url_name' ) AND `meta_value` IN ( '{$wp_query->query_vars['product_url_name']}' ) ORDER BY `id` DESC LIMIT 1" );

		$replacement_data = '';
		$replacement_data_array = array( );
		if ( $aioseop_options['aiosp_use_categories'] ) {
			$category_list = $wpdb->get_col( "SELECT `categories`.`name` FROM `" . WPSC_TABLE_ITEM_CATEGORY_ASSOC . "` AS `assoc` , `" . WPSC_TABLE_PRODUCT_CATEGORIES . "` AS `categories` WHERE `assoc`.`product_id` IN ('{$product_id}') AND `assoc`.`category_id` = `categories`.`id` AND `categories`.`active` IN('1')" );
			$replacement_data_array += $category_list;
		}
		$replacement_data_array += wp_get_object_terms( $product_id, 'product_tag', array( 'fields' => 'names' ) );
		$replacement_data .= implode( ",", $replacement_data_array );
		if ( $replacement_data != '' ) {
			$data = strtolower( $replacement_data );
		}
	}

	return $data;
}

add_filter( 'aioseop_keywords', 'wpsc_set_aioseop_keywords' );

/**
 * wpsc_populate_also_bought_list function, runs on checking out, populates the also bought list.
 */
function wpsc_populate_also_bought_list() {
	global $wpdb, $wpsc_cart, $wpsc_coupons;
	$new_also_bought_data = array( );
	foreach ( $wpsc_cart->cart_items as $outer_cart_item ) {
		$new_also_bought_data[$outer_cart_item->product_id] = array( );
		foreach ( $wpsc_cart->cart_items as $inner_cart_item ) {
			if ( $outer_cart_item->product_id != $inner_cart_item->product_id ) {
				$new_also_bought_data[$outer_cart_item->product_id][$inner_cart_item->product_id] = $inner_cart_item->quantity;
			} else {
				continue;
			}
		}
	}

	$insert_statement_parts = array( );
	foreach ( $new_also_bought_data as $new_also_bought_id => $new_also_bought_row ) {
		$new_other_ids = array_keys( $new_also_bought_row );
		$also_bought_data = $wpdb->get_results( $wpdb->prepare( "SELECT `id`, `associated_product`, `quantity` FROM `" . WPSC_TABLE_ALSO_BOUGHT . "` WHERE `selected_product` IN(%d) AND `associated_product` IN(" . implode( "','", $new_other_ids ) . ")", $new_also_bought_id ), ARRAY_A );
		$altered_new_also_bought_row = $new_also_bought_row;

		foreach ( (array)$also_bought_data as $also_bought_row ) {
			$quantity = $new_also_bought_row[$also_bought_row['associated_product']] + $also_bought_row['quantity'];

			unset( $altered_new_also_bought_row[$also_bought_row['associated_product']] );
			$wpdb->update(
				WPSC_TABLE_ALSO_BOUGHT,
				array(
				    'quantity' => $quantity
				),
				array(
				    'id' => $also_bought_row['id']
				),
				'%d',
				'%d'
			    );
	    }


		if ( count( $altered_new_also_bought_row ) > 0 ) {
			foreach ( $altered_new_also_bought_row as $associated_product => $quantity ) {
				$insert_statement_parts[] = "(" . absint( esc_sql( $new_also_bought_id ) ) . "," . absint( esc_sql( $associated_product ) ) . "," . absint( esc_sql( $quantity ) ) . ")";
			}
		}
	}

	if ( count( $insert_statement_parts ) > 0 ) {

		$insert_statement = "INSERT INTO `" . WPSC_TABLE_ALSO_BOUGHT . "` (`selected_product`, `associated_product`, `quantity`) VALUES " . implode( ",\n ", $insert_statement_parts );
		$wpdb->query( $insert_statement );
	}
}

function wpsc_get_country_form_id_by_type($type){
	global $wpdb;
	$sql = $wpdb->prepare( 'SELECT `id` FROM `'.WPSC_TABLE_CHECKOUT_FORMS.'` WHERE `type`= %s LIMIT 1', $type );
	$id = $wpdb->get_var($sql);
	return $id;
}

function wpsc_get_country( $country_code ) {
	global $wpdb;
	$country = $wpdb->get_var( $wpdb->prepare( "SELECT `country` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `isocode` IN (%s) LIMIT 1", $country_code ) );
	return $country;
}

function wpsc_get_region( $region_id ) {
	global $wpdb;
	$region = $wpdb->get_var( $wpdb->prepare( "SELECT `name` FROM `" . WPSC_TABLE_REGION_TAX . "` WHERE `id` IN(%d)", $region_id ) );
	return $region;
}

function nzshpcrt_display_preview_image() {
	global $wpdb;
	if ( (isset( $_GET['wpsc_request_image'] ) && ($_GET['wpsc_request_image'] == 'true'))
			|| (isset( $_GET['productid'] ) && is_numeric( $_GET['productid'] ))
			|| (isset( $_GET['image_id'] ) && is_numeric( $_GET['image_id'] ))
			|| (isset( $_GET['image_name'] ))
	) {

		if ( function_exists( "getimagesize" ) ) {
			if ( $_GET['image_name'] ) {
				$image = basename( $_GET['image_name'] );
				$imagepath = WPSC_USER_UPLOADS_DIR . $image;
			} else if ( $_GET['category_id'] ) {
				$category_id = absint( $_GET['category_id'] );
				$image = $wpdb->get_var( $wpdb->prepare( "SELECT `image` FROM `" . WPSC_TABLE_PRODUCT_CATEGORIES . "` WHERE `id` = %d LIMIT 1", $category_id ) );
				if ( $image != '' ) {
					$imagepath = WPSC_CATEGORY_DIR . $image;
				}
			}

			if ( !is_file( $imagepath ) ) {
				$imagepath = WPSC_FILE_PATH . "/images/no-image-uploaded.gif";
			}
			$image_size = @getimagesize( $imagepath );
			if ( is_numeric( $_GET['height'] ) && is_numeric( $_GET['width'] ) ) {
				$height = (int)$_GET['height'];
				$width = (int)$_GET['width'];
			} else {
				$width = $image_size[0];
				$height = $image_size[1];
			}
			if ( !(($height > 0) && ($height <= 1024) && ($width > 0) && ($width <= 1024)) ) {
				$width = $image_size[0];
				$height = $image_size[1];
			}
			if ( $product_id > 0 ) {
				$cache_filename = basename( "product_{$product_id}_{$height}x{$width}" );
			} else if ( $category_id > 0 ) {
				$cache_filename = basename( "category_{$category_id}_{$height}x{$width}" );
			} else {
				$cache_filename = basename( "product_img_{$image_id}_{$height}x{$width}" );
			}
			$imagetype = @getimagesize( $imagepath );
			$use_cache = false;
			switch ( $imagetype[2] ) {
				case IMAGETYPE_JPEG:
					$extension = ".jpg";
					break;

				case IMAGETYPE_GIF:
					$extension = ".gif";
					break;

				case IMAGETYPE_PNG:
					$extension = ".png";
					break;
			}
			if ( file_exists( WPSC_CACHE_DIR . $cache_filename . $extension ) ) {
				$original_modification_time = filemtime( $imagepath );
				$cache_modification_time = filemtime( WPSC_CACHE_DIR . $cache_filename . $extension );
				if ( $original_modification_time < $cache_modification_time ) {
					$use_cache = true;
				}
			}

			if ( $use_cache === true ) {
				$cache_url = WPSC_CACHE_URL;
				if ( is_ssl ( ) ) {
					$cache_url = str_replace( "http://", "https://", $cache_url );
				}
				header( "Location: " . $cache_url . $cache_filename . $extension );
				exit( '' );
			} else {
				switch ( $imagetype[2] ) {
					case IMAGETYPE_JPEG:
						$src_img = imagecreatefromjpeg( $imagepath );
						$pass_imgtype = true;
						break;

					case IMAGETYPE_GIF:
						$src_img = imagecreatefromgif( $imagepath );
						$pass_imgtype = true;
						break;

					case IMAGETYPE_PNG:
						$src_img = imagecreatefrompng( $imagepath );
						$pass_imgtype = true;
						break;

					default:
						$pass_imgtype = false;
						break;
				}

				if ( $pass_imgtype === true ) {
					$source_w = imagesx( $src_img );
					$source_h = imagesy( $src_img );

					//Temp dimensions to crop image properly
					$temp_w = $width;
					$temp_h = $height;

					// select our scaling method
					$scaling_method = apply_filters( 'wpsc_preview_image_cropping_method', 'cropping' );

					// set both offsets to zero
					$offset_x = $offset_y = 0;

					// Here are the scaling methods, non-cropping causes black lines in tall images, but doesnt crop images.
					switch ( $scaling_method ) {
						case 'cropping':
							// if the image is wider than it is high and at least as wide as the target width.
							if ( ($source_h <= $source_w ) ) {
								if ( $height < $width ) {
									$temp_h = ($width / $source_w) * $source_h;
								} else {
									$temp_w = ($height / $source_h) * $source_w;
								}
							} else {
								$temp_h = ($width / $source_w) * $source_h;
							}
							break;

						case 'non-cropping':
						default:
							if ( $height < $width ) {
								$temp_h = ($width / $source_w) * $source_h;
							} else {
								$temp_w = ($height / $source_h) * $source_w;
							}
							break;
					}

					// Create temp resized image
					$bgcolor_default = apply_filters( 'wpsc_preview_image_bgcolor', array( 255, 255, 255 ) );
					$temp_img = ImageCreateTrueColor( $temp_w, $temp_h );
					$bgcolor = ImageColorAllocate( $temp_img, $bgcolor_default[0], $bgcolor_default[1], $bgcolor_default[2] ) ;
					ImageFilledRectangle( $temp_img, 0, 0, $temp_w, $temp_h, $bgcolor );
					ImageAlphaBlending( $temp_img, TRUE );
					ImageCopyResampled( $temp_img, $src_img, 0, 0, 0, 0, $temp_w, $temp_h, $source_w, $source_h );

					$dst_img = ImageCreateTrueColor( $width, $height );
					$bgcolor = ImageColorAllocate( $dst_img, $bgcolor_default[0], $bgcolor_default[1], $bgcolor_default[2] );
					ImageFilledRectangle( $dst_img, 0, 0, $width, $height, $bgcolor );
					ImageAlphaBlending( $dst_img, TRUE );

					// X & Y Offset to crop image properly
					if ( $temp_w < $width ) {
						$w1 = ($width / 2) - ($temp_w / 2);
					} else if ( $temp_w == $width ) {
						$w1 = 0;
					} else {
						$w1 = ($width / 2) - ($temp_w / 2);
					}

					if ( $temp_h < $height ) {
						$h1 = ($height / 2) - ($temp_h / 2);
					} else if ( $temp_h == $height ) {
						$h1 = 0;
					} else {
						$h1 = ($height / 2) - ($temp_h / 2);
					}

					switch ( $scaling_method ) {
						case 'cropping':
							ImageCopy( $dst_img, $temp_img, $w1, $h1, 0, 0, $temp_w, $temp_h );
							break;

						case 'non-cropping':
						default:
							ImageCopy( $dst_img, $temp_img, 0, 0, 0, 0, $temp_w, $temp_h );
							break;
					}

					$image_quality = wpsc_image_quality();

					ImageAlphaBlending( $dst_img, false );
					switch ( $imagetype[2] ) {
						case IMAGETYPE_JPEG:
							header( "Content-type: image/jpeg" );
							imagejpeg( $dst_img );
							imagejpeg( $dst_img, WPSC_CACHE_DIR . $cache_filename . '.jpg', $image_quality );
							@ chmod( WPSC_CACHE_DIR . $cache_filename . ".jpg", 0775 );
							break;

						case IMAGETYPE_GIF:
							header( "Content-type: image/gif" );
							ImagePNG( $dst_img );
							ImagePNG( $dst_img, WPSC_CACHE_DIR . $cache_filename . ".gif" );
							@ chmod( WPSC_CACHE_DIR . $cache_filename . ".gif", 0775 );
							break;

						case IMAGETYPE_PNG:
							header( "Content-type: image/png" );
							ImagePNG( $dst_img );
							ImagePNG( $dst_img, WPSC_CACHE_DIR . $cache_filename . ".png" );
							@ chmod( WPSC_CACHE_DIR . $cache_filename . ".png", 0775 );
							break;

						default:
							$pass_imgtype = false;
							break;
					}
					exit();
				}
			}
		}
	}
}

add_action( 'init', 'nzshpcrt_display_preview_image' );

function wpsc_list_dir( $dirname ) {
	/*
	  lists the provided directory, was nzshpcrt_listdir
	 */
	$dir = @opendir( $dirname );
	$num = 0;
	while ( ($file = @readdir( $dir )) !== false ) {
		//filter out the dots and any backup files, dont be tempted to correct the "spelling mistake", its to filter out a previous spelling mistake.
		if ( ($file != "..") && ($file != ".") && !stristr( $file, "~" ) && !stristr( $file, "Chekcout" ) && !stristr( $file, "error_log" ) && !( strpos( $file, "." ) === 0 ) ) {
			$dirlist[$num] = $file;
			$num++;
		}
	}
	return $dirlist;
}

/**
 * wpsc_recursive_copy function, copied from here and renamed: http://nz.php.net/copy
 * Why doesn't PHP have one of these built in?
 */
function wpsc_recursive_copy( $src, $dst ) {
	$dir = opendir( $src );
	@mkdir( $dst );
	while ( false !== ( $file = readdir( $dir )) ) {
		if ( ( $file != '.' ) && ( $file != '..' ) ) {
			if ( is_dir( $src . '/' . $file ) ) {
				wpsc_recursive_copy( $src . '/' . $file, $dst . '/' . $file );
			} else {
				@ copy( $src . '/' . $file, $dst . '/' . $file );
			}
		}
	}
	closedir( $dir );
}

/**
 * wpsc_replace_reply_address function,
 * Replace the email address for the purchase receipts
 */
function wpsc_replace_reply_address( $input ) {
	$output = get_option( 'return_email' );
	if ( $output == '' ) {
		$output = $input;
	}
	return $output;
}

/**
 * wpsc_replace_reply_address function,
 * Replace the email address for the purchase receipts
 */
function wpsc_replace_reply_name( $input ) {
	$output = get_option( 'return_name' );
	if ( $output == '' ) {
		$output = $input;
	}
	return $output;
}

/**
 * wpsc_object_to_array, recusively converts an object to an array, for usage with SOAP code
 * Copied from here, then modified:
 * http://www.phpro.org/examples/Convert-Object-To-Array-With-PHP.html
 */
function wpsc_object_to_array( $object ) {
	if ( !is_object( $object ) && !is_array( $object ) ) {
		return $object;
	} else if ( is_object( $object ) ) {
		$object = get_object_vars( $object );
	}
	return array_map( 'wpsc_object_to_array', $object );
}

function wpsc_readfile_chunked( $filename, $retbytes = true ) {
	$chunksize = 1 * (1024 * 1024); // how many bytes per chunk
	$buffer = '';
	$cnt = 0;
	$handle = fopen( $filename, 'rb' );
	if ( $handle === false ) {
		return false;
	}
	while ( !feof( $handle ) ) {
		$buffer = fread( $handle, $chunksize );
		echo $buffer;
		ob_flush();
		flush();
		if ( $retbytes ) {
			$cnt += strlen( $buffer );
		}
	}
	$status = fclose( $handle );
	if ( $retbytes && $status ) {
		return $cnt; // return num. bytes delivered like readfile() does.
	}
	return $status;
}

/**
 * Retrieve the list of tags for a product.
 *
 * Compatibility layer for themes and plugins. Also an easy layer of abstraction
 * away from the complexity of the taxonomy layer. Copied from the Wordpress posts code
 *
 * @since 3.8.0
 *
 * @uses wp_get_object_terms() Retrieves the tags. Args details can be found here.
 *
 * @param int $product_id Optional. The Post ID.
 * @param array $args Optional. Overwrite the defaults.
 * @return array
 */
function wp_get_product_tags( $product_id = 0, $args = array( ) ) {
	$product_id = (int)$product_id;

	$defaults = array( 'fields' => 'ids' );
	$args = wp_parse_args( $args, $defaults );

	$cats = wp_get_object_terms( $product_id, 'product_tag' );
	return $cats;
}

/**
 * Retrieve the list of categories for a product.
 *
 * Compatibility layer for themes and plugins. Also an easy layer of abstraction
 * away from the complexity of the taxonomy layer. Copied from the Wordpress posts code
 *
 * @since 3.8.0
 *
 * @uses wp_get_object_terms() Retrieves the categories. Args details can be found here.
 *
 * @param int $post_id Optional. The Post ID.
 * @param array $args Optional. Overwrite the defaults.
 * @return array
 */
function wp_get_product_categories( $product_id = 0, $args = array( ) ) {
	$product_id = (int)$product_id;

	$defaults = array( 'fields' => 'ids' );
	$args = wp_parse_args( $args, $defaults );

	$cats = wp_get_object_terms( $product_id, 'wpsc_product_category' );
	return $cats;
}

/**
 * Set categories for a product.
 *
 * If the post categories parameter is not set, then the default category is
 * going used.  Copied from the Wordpress posts code
 *
 * @since 3.8.0
 *
 * @param int $post_ID Post ID.
 * @param array $post_categories Optional. List of categories.
 * @return bool|mixed
 */
function wp_set_product_categories( $product_id, $post_categories = array( ) ) {
	$product_id = (int)$product_id;
	// If $post_categories isn't already an array, make it one:
	if ( !is_array( $post_categories ) || 0 == count( $post_categories ) || empty( $post_categories ) ) {
		return;
	} else if ( 1 == count( $post_categories ) && '' == $post_categories[0] ) {
		return true;
	}

	$post_categories = array_map( 'intval', $post_categories );
	$post_categories = array_unique( $post_categories );

	return wp_set_object_terms( $product_id, $post_categories, 'wpsc_product_category' );
}

//*/

/**
 * Retrieve product categories. Copied from the corresponding wordpress function
 *
 * @since 3.8.0
 *
 * @param int $id Mandatory, the product ID
 * @return array
 */
function get_the_product_category( $id ) {

	$id = (int)$id;

	$categories = get_object_term_cache( $id, 'wpsc_product_category' );
	if ( false === $categories ) {
		$categories = wp_get_object_terms( $id, 'wpsc_product_category' );
		wp_cache_add( $id, $categories, 'product_category_relationships' );
	}

	if ( !empty( $categories ) )
		usort( $categories, '_usort_terms_by_name' );
	else
		$categories = array( );

	foreach ( (array)array_keys( $categories ) as $key ) {
		_make_cat_compat( $categories[$key] );
	}

	return $categories;
}

/**
 * Check the memory_limit and calculate a recommended memory size
 * inspired by nextGenGallery Code
 *
 * @return string message about recommended image size
 */
function wpsc_check_memory_limit() {

	if ( (function_exists( 'memory_get_usage' )) && (ini_get( 'memory_limit' )) ) {

		// get memory limit
		$memory_limit = ini_get( 'memory_limit' );
		if ( $memory_limit != '' )
			$memory_limit = substr( $memory_limit, 0, -1 ) * 1024 * 1024;

		// calculate the free memory
		$freeMemory = $memory_limit - memory_get_usage();

		// build the test sizes
		$sizes = array( );
		$sizes[] = array( 'width' => 800, 'height' => 600 );
		$sizes[] = array( 'width' => 1024, 'height' => 768 );
		$sizes[] = array( 'width' => 1280, 'height' => 960 );  // 1MP
		$sizes[] = array( 'width' => 1600, 'height' => 1200 ); // 2MP
		$sizes[] = array( 'width' => 2016, 'height' => 1512 ); // 3MP
		$sizes[] = array( 'width' => 2272, 'height' => 1704 ); // 4MP
		$sizes[] = array( 'width' => 2560, 'height' => 1920 ); // 5MP
		// test the classic sizes
		foreach ( $sizes as $size ) {
			// very, very rough estimation
			if ( $freeMemory < round( $size['width'] * $size['height'] * 5.09 ) ) {
				$result = sprintf( __( 'Please refrain from uploading images larger than <strong>%d x %d</strong> pixels', 'wpsc' ), $size['width'], $size['height'] );
				return $result;
			}
		}
	}
	return;
}

/* Thanks to: http://www.if-not-true-then-false.com/2009/format-bytes-with-php-b-kb-mb-gb-tb-pb-eb-zb-yb-converter */
function wpsc_convert_byte($bytes, $unit = "", $decimals = 2) {

	$units = array('B' => 0, 'KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4,
			'PB' => 5, 'EB' => 6, 'ZB' => 7, 'YB' => 8);
	$value = 0;
	if ($bytes > 0) {
		// Generate automatic prefix by bytes
		// If wrong prefix given
		if (!array_key_exists($unit, $units)) {
			$pow = floor(log($bytes)/log(1024));
			$unit = array_search($pow, $units);
		}

		// Calculate byte value by prefix
		$value = ($bytes/pow(1024,floor($units[$unit])));
	}

	// If decimals is not numeric or decimals is less than 0
	// then set default value
	if (!is_numeric($decimals) || $decimals < 0) {
		$decimals = 2;
	}

	// Format output
	return sprintf('%.' . $decimals . 'f '.$unit, $value);
  }

/**
 * Check whether an integer is odd
 * @return bool - true if is odd, false otherwise
 */
function wpsc_is_odd( $int ) {

	$int = absint( $int );
	return( $int & 1 );
}

/**
 * Retrieves extension of file.
 * @return string - extension of the passed filename
 */
function wpsc_get_extension( $str ) {

	$parts = explode( '.', $str );
	return end( $parts );

}
