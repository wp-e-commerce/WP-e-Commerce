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
 * WPSC find purchlog status name looks through the wpsc_purchlog_statuses variable to find the name of the given status
 *
 * @since 3.8
 * $param int $id the id for the region
 * @param string $return_value either 'name' or 'code' depending on what you want returned
 */
function wpsc_find_purchlog_status_name( $purchlog_status ) {
	global $wpsc_purchlog_statuses;

	$status_name = '';

	foreach ( $wpsc_purchlog_statuses as $status ) {
		if ( $status['order'] == $purchlog_status ) {
			$status_name = $status['label'];
		} else {
			continue;
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

	$region = new WPSC_Region( WPSC_Countries::get_country_id_by_region_id( $id ), $id );

	$value = '';

	if ( $return_value == 'name' ) {
		$value = $region->get_name();
	} elseif ( $return_value == 'code' ) {
		$value = $region->get_code();
	}

	return $value;
}

function wpsc_country_has_state( $country_code ){

	$country_data = WPSC_Countries::get_country( $country_code, true ); // TODO this function does not seem to do what it's name indicates? What's up with that.
	return $country_data;
}

/**
 * Convert time interval to seconds.
 *
 * Takes a number an unit of time (hour/day/week) and converts it to seconds.
 * It allows decimal intervals like 1.5 days.
 *
 * @since   3.8.14
 * @access  public
 *
 * @param   int  $time      Stock keeping time.
 * @param   int  $interval  Stock keeping interval unit (hour/day/week).
 * @return  int             Seconds.
 *
 * @uses  MINUTE_IN_SECONDS, HOUR_IN_SECONDS, DAY_IN_SECONDS, WEEK_IN_SECONDS, YEAR_IN_SECONDS
 */
function wpsc_convert_time_interval_to_seconds( $time, $interval ) {
	$convert = array(
		'minute' => MINUTE_IN_SECONDS,
		'hour'   => HOUR_IN_SECONDS,
		'day'    => DAY_IN_SECONDS,
		'week'   => WEEK_IN_SECONDS,
		'year'   => YEAR_IN_SECONDS,
	);
	return floor( $time * $convert[ $interval ] );
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
	$errors = new WP_Error();
	$user_login = sanitize_user( $user_login );
	$user_email = apply_filters( 'user_registration_email', $user_email );

	// Check the username
	if ( $user_login == '' ) {
		$errors->add( 'empty_username', __( '<strong>ERROR</strong>: Please enter a username.', 'wp-e-commerce' ) );
	} elseif ( !validate_username( $user_login ) ) {
		$errors->add( 'invalid_username', __( '<strong>ERROR</strong>: This username is invalid.  Please enter a valid username.', 'wp-e-commerce' ) );
		$user_login = '';
	} elseif ( username_exists( $user_login ) ) {
		$errors->add( 'username_exists', __( '<strong>ERROR</strong>: This username is already registered, please choose another one.', 'wp-e-commerce' ) );
	}

	// Check the e-mail address
	if ( $user_email == '' ) {
		$errors->add( 'empty_email', __( '<strong>ERROR</strong>: Please type your e-mail address.', 'wp-e-commerce' ) );
	} elseif ( !is_email( $user_email ) ) {
		$errors->add( 'invalid_email', __( '<strong>ERROR</strong>: The email address isn&#8217;t correct.', 'wp-e-commerce' ) );
		$user_email = '';
	} elseif ( email_exists( $user_email ) ) {
		$errors->add( 'email_exists', __( '<strong>ERROR</strong>: This email is already registered, please choose another one.', 'wp-e-commerce' ) );
	}

	if ( $errors->get_error_code() ) {
		return $errors;
	}
	$user_id = wp_create_user( $user_login, $user_pass, $user_email );
	if ( !$user_id ) {
		$errors->add( 'registerfail', sprintf( __( '<strong>ERROR</strong>: Couldn&#8217;t register you... please contact the <a href="mailto:%s">webmaster</a> !', 'wp-e-commerce' ), get_option( 'admin_email' ) ) );
		return $errors;
	}

	$user = wp_signon( array( 'user_login' => $user_login, 'user_password' => $user_pass, 'remember' => true ) );
	wp_set_current_user( $user->ID );

	return $user;
}


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
		$replacement_data_array += wpsc_get_product_terms( $product_id, 'product_tag', 'name' );
		$replacement_data .= implode( ",", $replacement_data_array );
		if ( $replacement_data != '' ) {
			$data = strtolower( $replacement_data );
		}
	}

	return $data;
}

add_filter( 'aioseop_keywords', 'wpsc_set_aioseop_keywords' );

function wpsc_get_country_form_id_by_type($type){
	global $wpdb;
	$sql = $wpdb->prepare( 'SELECT `id` FROM `'.WPSC_TABLE_CHECKOUT_FORMS.'` WHERE `type`= %s LIMIT 1', $type );
	$id = $wpdb->get_var($sql);
	return $id;
}

function wpsc_get_country( $country_code ) {
	$wpsc_country = new WPSC_Country( $country_code );
	return $wpsc_country->get_name();
}

function wpsc_get_region( $region_id ) {
	$country_id = WPSC_Countries::get_country_id_by_region_id( $region_id );
	$wpsc_region = new WPSC_Region( $country_id, $region_id );
	return $wpsc_region->get_name();
}

function nzshpcrt_display_preview_image() {
	global $wpdb;
	if ( (isset( $_GET['wpsc_request_image'] ) && ($_GET['wpsc_request_image'] == 'true'))
			|| (isset( $_GET['productid'] ) && is_numeric( $_GET['productid'] ))
			|| (isset( $_GET['image_id'] ) && is_numeric( $_GET['image_id'] ))
			|| (isset( $_GET['image_name'] ))
	) {

		if ( function_exists( "getimagesize" ) ) {

			$imagepath   = '';
			$category_id = 0;

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

			if ( ! is_file( $imagepath ) ) {
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

			$product_id = (int) $_GET['productid'];
			$image_id   = (int) $_GET['image_id'];

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
				case IMAGETYPE_GIF:
					$extension = ".gif";
					break;

				case IMAGETYPE_PNG:
					$extension = ".png";
					break;

				case IMAGETYPE_JPEG:
				default:
					$extension = ".jpg";
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
				$cache_url = set_url_scheme( WPSC_CACHE_URL );
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
						$src_img      = false;
						$pass_imgtype = false;
						break;
				}

				if ( $pass_imgtype === true && $src_img ) {
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
	$dirlist = array();

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

	$cats = wpsc_get_product_terms( $product_id, 'product_tag' );
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

	$cats = wpsc_get_product_terms( $product_id, 'wpsc_product_category' );
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

	$categories = wpsc_get_product_terms( $id, 'wpsc_product_category' );

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
				$result = sprintf( __( 'Please refrain from uploading images larger than <strong>%d x %d</strong> pixels', 'wp-e-commerce' ), $size['width'], $size['height'] );
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

/**
 * Marks a function as deprecated and informs when it has been used.
 *
 * There is a hook wpsc_deprecated_function_run that will be called that can be
 * used to get the backtrace up to what file and function called the deprecated
 * function.
 *
 * The current behavior is to trigger a user error if WP_DEBUG is true.
 *
 * This function is to be used in every function that is deprecated.
 *
 * @since 3.8.10
 * @access private
 *
 * @uses do_action() Calls 'wpsc_deprecated_function_run' and passes the function name, what to use instead,
 *   and the version the function was deprecated in.
 * @uses apply_filters() Calls 'wpsc_deprecated_function_trigger_error' and expects boolean value of true to do
 *   trigger or false to not trigger error.
 *
 * @param string $function The function that was called
 * @param string $version The version of WP eCommerce that deprecated the function
 * @param string $replacement Optional. The function that should have been called
 */
function _wpsc_deprecated_function( $function, $version, $replacement = null ) {
	do_action( 'wpsc_deprecated_function_run', $function, $replacement, $version );

	// Allow plugin to filter the output error trigger
	if ( WP_DEBUG && apply_filters( 'wpsc_deprecated_function_trigger_error', true ) ) {
		if ( ! is_null( $replacement ) )
			trigger_error(
				sprintf( __( '%1$s is <strong>deprecated</strong> since WP eCommerce version %2$s! Use %3$s instead.', 'wp-e-commerce' ),
					$function,
					$version,
					$replacement
				)
			);
		else
			trigger_error(
				sprintf( __( '%1$s is <strong>deprecated</strong> since WP eCommerce version %2$s with no alternative available.', 'wp-e-commerce' ),
					$function,
					$version
				)
			);
	}
}

/**
 * Marks a file as deprecated and informs when it has been used.
 *
 * There is a hook wpsc_deprecated_file_included that will be called that can be
 * used to get the backtrace up to what file and function included the
 * deprecated file.
 *
 * The current behavior is to trigger a user error if WP_DEBUG is true.
 *
 * This function is to be used in every file that is deprecated.
 *
 * @since 3.8.10
 * @access private
 *
 * @uses do_action() Calls 'wpsc_deprecated_file_included' and passes the file name, what to use instead,
 *   the version in which the file was deprecated, and any message regarding the change.
 * @uses apply_filters() Calls 'wpsc_deprecated_file_trigger_error' and expects boolean value of true to do
 *   trigger or false to not trigger error.
 *
 * @param string $file The file that was included
 * @param string $version The version of WP eCommerce that deprecated the file
 * @param string $replacement Optional. The file that should have been included based on ABSPATH
 * @param string $message Optional. A message regarding the change
 */
function _wpsc_deprecated_file( $file, $version, $replacement = null, $message = '' ) {

	do_action( 'wpsc_deprecated_file_included', $file, $replacement, $version, $message );

	// Allow plugin to filter the output error trigger
	if ( WP_DEBUG && apply_filters( 'wpsc_deprecated_file_trigger_error', true ) ) {
		$message = empty( $message ) ? '' : ' ' . $message;
		if ( ! is_null( $replacement ) )
			trigger_error(
				sprintf( __( '%1$s is <strong>deprecated</strong> since WP eCommerce version %2$s! Use %3$s instead.', 'wp-e-commerce' ),
					$file,
					$version,
					$replacement
				) . $message
			);
		else
			trigger_error(
				sprintf( __( '%1$s is <strong>deprecated</strong> since WP eCommerce version %2$s with no alternative available.', 'wp-e-commerce' ),
					$file,
					$version
				) . $message
			);
	}
}
/**
 * Marks a function argument as deprecated and informs when it has been used.
 *
 * This function is to be used whenever a deprecated function argument is used.
 * Before this function is called, the argument must be checked for whether it
 * was used by comparing it to its default value or evaluating whether it is
 * empty.
 *
 * For example:
 * <code>
 * if ( ! empty( $deprecated ) )
 * 	_wpsc_deprecated_argument( __FUNCTION__, '3.8.10' );
 * </code>
 *
 * There is a hook wpsc_deprecated_argument_run that will be called that can be
 * used to get the backtrace up to what file and function used the deprecated
 * argument.
 *
 * The current behavior is to trigger a user error if WP_DEBUG is true.
 *
 * @since 3.8.10
 * @access private
 *
 * @uses do_action() Calls 'wpsc_deprecated_argument_run' and passes the function name, a message on the change,
 *   and the version in which the argument was deprecated.
 * @uses apply_filters() Calls 'wpsc_deprecated_argument_trigger_error' and expects boolean value of true to do
 *   trigger or false to not trigger error.
 *
 * @param string $function The function that was called
 * @param string $version The version of WP eCommerce that deprecated the argument used
 * @param string $message Optional. A message regarding the change.
 */
function _wpsc_deprecated_argument( $function, $version, $message = null ) {

	do_action( 'wpsc_deprecated_argument_run', $function, $message, $version );

	// Allow plugin to filter the output error trigger
	if ( WP_DEBUG && apply_filters( 'wpsc_deprecated_argument_trigger_error', true ) ) {
		if ( ! is_null( $message ) )
			trigger_error(
				sprintf(
					__( '%1$s was called with an argument that is <strong>deprecated</strong> since WP eCommerce version %2$s! %3$s', 'wp-e-commerce' ),
					$function,
					$version,
					$message
				)
			);
		else
			trigger_error(
				sprintf(
					__( '%1$s was called with an argument that is <strong>deprecated</strong> since WP eCommerce version %2$s with no alternative available.', 'wp-e-commerce' ),
					$function,
					$version
				)
			);
	}
}

/**
 * Marks something as being incorrectly called.
 *
 * There is a hook wpsc_doing_it_wrong_run that will be called that can be used
 * to get the backtrace up to what file and function called the deprecated
 * function.
 *
 * The current behavior is to trigger a user error if WP_DEBUG is true.
 *
 * @since 3.8.10
 * @access private
 *
 * @uses do_action() Calls 'wpsc_doing_it_wrong_run' and passes the function arguments.
 * @uses apply_filters() Calls 'wpsc_doing_it_wrong_trigger_error' and expects boolean value of true to do
 *   trigger or false to not trigger error.
 *
 * @param string $function The function that was called.
 * @param string $message A message explaining what has been done incorrectly.
 * @param string $version The version of WP eCommerce where the message was added.
 */
function _wpsc_doing_it_wrong( $function, $message, $version ) {

	do_action( 'wpsc_doing_it_wrong_run', $function, $message, $version );

	// Allow plugin to filter the output error trigger
	if ( WP_DEBUG && apply_filters( 'wpsc_doing_it_wrong_trigger_error', true ) ) {
		$version =   is_null( $version )
		           ? ''
		           : sprintf( __( '(This message was added in WP eCommerce version %s.)', 'wp-e-commerce' ), $version );
		$message .= ' ' . __( 'Please see <a href="http://codex.wordpress.org/Debugging_in_WordPress">Debugging in WordPress</a> for more information.', 'wp-e-commerce' );
		trigger_error(
			sprintf(
				__( '%1$s was called <strong>incorrectly</strong>. %2$s %3$s', 'wp-e-commerce' ),
				$function,
				$message,
				$version
			)
		);
	}
}

/**
 * Returns the ID of the highest numbered purchase log
 *
 * Fetches the max_purchase_id transient, or fetches it from the database and sets the transient
 *
 * @since 3.8.11
 *
 * @return integer The ID of the highest numbered purchase log in the database
 *
 * @see wpsc_invalidate_max_purchase_id_transient()
 */
function wpsc_max_purchase_id() {
	global $wpdb;
	if ( false === ( $max_purchase_id = get_transient( 'max_purchase_id' ) ) ) {
		 $max_purchase_id = $wpdb->get_var( 'SELECT MAX( id ) FROM ' . WPSC_TABLE_PURCHASE_LOGS );
		set_transient( 'max_purchase_id', $max_purchase_id, 60 * 60 * 24 ); // day of seconds
	}
	return (int) $max_purchase_id;
}

/**
 * Invalidates transient for highest numbered purchase log id
 *
 * Used especially with actions wpsc_purchase_log_insert and wpsc_purchase_log_delete
 *
 * @since 3.8.11
 *
 * @see wpsc_max_purchase_id()
 */

function wpsc_invalidate_max_purchase_id_transient () {
	delete_transient( 'max_purchase_id' );
}

add_action( 'wpsc_purchase_log_insert', 'wpsc_invalidate_max_purchase_id_transient' );
add_action( 'wpsc_purchase_log_delete', 'wpsc_invalidate_max_purchase_id_transient' );

/** Checks to see whether terms and conditions are empty
 * @access public
 *
 * @since 3.8
 * @return (boolean) true or false
 */
function wpsc_has_tnc(){
	if('' == get_option('terms_and_conditions'))
		return false;
	else
		return true;
}

if ( isset( $_GET['termsandconds'] ) && 'true' == $_GET['termsandconds'] )
	add_action( 'init', 'wpsc_show_terms_and_conditions' );

function wpsc_show_terms_and_conditions() {
	echo wpautop( wp_kses_post( get_option( 'terms_and_conditions' ) ) );
	die();
}

/**
 * Helper function to display proper spinner icon, depending on WP version used.
 * This way, WP 3.8+ users will not feel like they are in a time-warp.
 *
 * @since 3.8.13
 *
 * @return void
 */
function wpsc_get_ajax_spinner() {
	global $wp_version;

	if ( version_compare( $wp_version, '3.8', '<' ) ) {
		$url = admin_url( 'images/wpspin_light.gif' );
	} else {
		$url = admin_url( 'images/spinner.gif' );
	}

	return apply_filters( 'wpsc_get_ajax_spinner', $url );
}

function _wpsc_remove_erroneous_files() {

	if ( ! wpsc_is_store_admin() ) {
		return;
	}

	$files = array(
		 WPSC_FILE_PATH . '/wpsc-components/marketplace-core-v1/library/Sputnik/.htaccess',
		 WPSC_FILE_PATH . '/wpsc-components/marketplace-core-v1/library/Sputnik/error_log',
		 WPSC_FILE_PATH . '/wpsc-components/marketplace-core-v1/library/Sputnik/functions.php',
		 WPSC_FILE_PATH . '/wpsc-components/marketplace-core-v1/library/Sputnik/admin-functions.php',
		 WPSC_FILE_PATH . '/wpsc-components/marketplace-core-v1/library/Sputnik/advanced-cache.php'
	);

	foreach ( $files as $file ) {
		if ( is_file( $file ) ) {
			@unlink( $file );
		}
	}

	update_option( 'wpsc_38131_file_check', false );
}

if ( get_option( 'wpsc_38131_file_check', true ) ) {
	add_action( 'admin_init', '_wpsc_remove_erroneous_files' );
}


/**
 * Store a WP eCommerce Transient
 * Wrapper function to cover WordPress' set transient function.
 * Note: Initial reason for implmenting this was unusual derserialization errors coming from the APC
 * component when APC tries to deserialize a transient containing nested objects. This wrapper function
 * encodes the transient contents so that APC will not try to deserialize it into component objects.
 *
 * @since 3.9.3
 * @param string $transient  Transient name. Expected to not be SQL-escaped. Must be
 *                           45 characters or fewer in length.
 * @param mixed  $value      Transient value. Must be serializable if non-scalar.
 *                           Expected to not be SQL-escaped.
 * @param int    $expiration Optional. Time until expiration in seconds. Default 0.
 * @return bool  false if value was not set and true if value was set.*
 */
function _wpsc_set_transient(  $transient, $value, $expiration = 0 )  {
	$serialized_value = serialize( $value );
	$encoded_value = base64_encode( $serialized_value );
	return set_transient( $transient, $encoded_value, $expiration );
}

/**
 * Retrieve a WP eCommerce Transient
 * Wrapper function to cover WordPress' get transient function.
 * Note: Initial reason for implmenting this was unusual derserialization errors coming from the APC
 * component when APC tries to deserialize a transient containing nested objects. This wrapper function
 * decodes the transient contents that were encoded so that APC will would try to deserialize it into
 * component objects. If the transient contents can not be decoded, the transient is deleted and the
 * function will return false as if the tranient never existed.
 *
 * @since 3.9.3
 * @param string $transient Transient name. Expected to not be SQL-escaped.
 * @return mixed value of transient, false if transient did not exist
 */
function _wpsc_get_transient( $transient )  {
	$encoded_value = get_transient( $transient );
	$value = false;

	if ( false !== $encoded_value ) {
		if ( ! empty( $encoded_value ) && is_string( $encoded_value ) ) {
			$serialized_value = @base64_decode( $encoded_value );
			if ( is_string( $serialized_value ) ) {
				$value = unserialize( $serialized_value );
			} else {
				$value = false;
			}

			// if there was a transient, but it could not be decoded, we delete the transient to get back
			// to a working state
			if ( false === $value ) {
				_wpsc_delete_transient( $transient );
			}
		}
	}

	return $value;
}

/**
 * Delete a WP eCommerce Transient
 * Wrapper function to cover WordPress' delete transient function.
 * Note: Initial reason for implmenting this was unusual derserialization errors coming from the APC
 * component when APC tries to deserialize a transient containing nested objects.
 *
 * @since 3.9.3
 * @param string $transient Transient name. Expected to not be SQL-escaped.
 * @return mixed value of transient, false if transient did not exist
 */
function _wpsc_delete_transient( $transient )  {
	return delete_transient( $transient );
}


