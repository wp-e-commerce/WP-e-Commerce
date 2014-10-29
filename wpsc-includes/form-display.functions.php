<?php

/*
 * TODO: If the uploads directory of wordpress doesn't have the right permissions there
 * is a memory exhausting problem in this function.
 */

function wpsc_uploaded_files() {
	global $wpdb, $wpsc_uploaded_file_cache;

	$dir = @opendir( WPSC_FILE_DIR );
	$num = 0;
	$dirlist = array( );

	if ( count( $wpsc_uploaded_file_cache ) > 0 ) {
		$dirlist = $wpsc_uploaded_file_cache;
	} elseif ( $dir ) {
		while ( ($file = @readdir( $dir )) !== false ) {
			//filter out the dots, macintosh hidden files and any backup files
			if ( ($file != "..") && ($file != ".") && ($file != "product_files") && ($file != "preview_clips") && !stristr( $file, "~" ) && !( strpos( $file, "." ) === 0 ) && !strpos( $file, ".old" ) ) {
				$file_data = null;
				$args = array(
					'post_type' => 'wpsc-product-file',
					'post_name' => $file,
					'numberposts' => 1,
					'post_status' => 'all'
				);

				//// @TODO broken, does not select by post_name, need to loop at wordpress API to fix.
				//$file_data = (array)get_posts($args);


				if ( $file_data[0] != null ) {
					$dirlist[$num]['display_filename'] = $file_data[0]->post_title;
					$dirlist[$num]['file_id'] = $file_data[0]->ID;
				} else {
					$dirlist[$num]['display_filename'] = $file;
					$dirlist[$num]['file_id'] = null;
				}
				$dirlist[$num]['real_filename'] = $file;
				$num++;
			}
		}

		if ( count( $dirlist ) > 0 ) {
			$wpsc_uploaded_file_cache = $dirlist;
		}
	}

	$dirlist = apply_filters( 'wpsc_downloadable_file_list', $dirlist );

	return $dirlist;
}

/**
 * Returns HTML for Digital Download UI
 *
 * @param int $product_id
 * @return HTML
 */
function wpsc_select_product_file( $product_id = null ) {
	global $wpdb;
	$product_id = absint( $product_id );
	$file_list = wpsc_uploaded_files();

	$args = array(
		'post_type' => 'wpsc-product-file',
		'post_parent' => $product_id,
		'numberposts' => -1,
		'post_status' => 'all'
	);

	$attached_files = (array)get_posts( $args );

	$output = '<table id="wpsc_digital_download_table" class="wp-list-table widefat posts select_product_file">';
		$output .= '<thead>';
			$output .= '<tr>';
				$output .= '<th>' . _x( 'Title', 'Digital download UI', 'wpsc' ) . '</th>';
				$output .= '<th>' . _x( 'Size', 'Digital download UI', 'wpsc' ) . '</th>';
				$output .= '<th>' . _x( 'File Type', 'Digital download UI', 'wpsc' ) . '</th>';
				$output .= '<th id="wpsc_digital_download_action_th">' . _x( 'Actions', 'Digital download UI', 'wpsc' ) . '</th>';
			$output .= '</tr>';
		$output .= '</thead>';
		$output .= '<tfoot>';
			$output .= '<tr>';
				$output .= '<th>' . _x( 'Title', 'Digital download UI', 'wpsc' ) . '</th>';
				$output .= '<th>' . _x( 'Size', 'Digital download UI', 'wpsc' ) . '</th>';
				$output .= '<th>' . _x( 'File Type', 'Digital download UI', 'wpsc' ) . '</th>';
				$output .= '<th id="wpsc_digital_download_action_th">' . _x( 'Actions', 'Digital download UI', 'wpsc' ) . '</th>';
			$output .= '</tr>';
		$output .= '</tfoot>';

	$num = 0;

	$output .= '<tbody>';
	$delete_nonce = _wpsc_create_ajax_nonce( 'delete_file' );
	foreach ( (array)$attached_files as $file ) {

		$file_dir = WPSC_FILE_DIR . $file->post_title;
		$file_size = ( 'http://s3file' == $file->guid ) ? __( 'Remote file sizes cannot be calculated', 'wpsc' ) : wpsc_convert_byte( filesize( $file_dir ) );

		$file_url = add_query_arg(
			array(
				'wpsc_download_id' => $file->ID,
				'_wpnonce'         => wp_create_nonce( 'wpsc-admin-download-file-' . $file->ID ),
			),
			admin_url()
		);
		$deletion_url = wp_nonce_url( "admin.php?wpsc_admin_action=delete_file&amp;file_name={$file->post_title}&amp;product_id={$product_id}&amp;row_number={$num}", 'delete_file_' . $file->post_title );

		$class = ( ! wpsc_is_odd( $num ) ) ? 'alternate' : '';

		$file_type = get_post_mime_type($file->ID);
		$icon_url  = wp_mime_type_icon($file_type);

		$output .= '<tr class="wpsc_product_download_row ' . $class . '">';
		$output .= '<td style="padding-right: 30px;"><img src="'. $icon_url .'"><span>' . $file->post_title . '</span></td>';
		$output .= '<td>' . $file_size .'</td>';
		$output .= '<td>' . $file_type . '</td>';
		$output .= '<td><a href="' .$file_url .'">' . _x( 'Download', 'Digital download row UI', 'wpsc' ) . '</a><a data-file-name="' . esc_attr( $file->post_title ) . '" data-product-id="' . esc_attr( $product_id ) . '" data-nonce="' . esc_attr( $delete_nonce ) . '" class="file_delete_button" href="{$deletion_url}" >' . _x( "Delete", "Digital download row UI", "wpsc" ) . '</a></td>';

		$output .= '</tr>';

		$num++;
	}

	$output .= '</tbody>';
	$output .= '</table>';

	if( empty( $attached_files ) )
		$output .= "<p class='no-item'>" . __( 'There are no files attached to this product. Upload a new file or select from other product files.', 'wpsc' ) . "</p>";
	$output .= "<div class='" . ( ( is_numeric( $product_id ) ) ? 'edit_' : '') . "select_product_handle'></div>";
	$output .= "<script type='text/javascript'>\r\n";
	$output .= "var select_min_height = " . ( 25 * 3 ) . ";\r\n";
	$output .= "var select_max_height = " . ( 25 * ( $num + 1 ) ) . ";\r\n";
	$output .= "</script>";

	return $output;
}

function _wpsc_admin_download_file() {

	if ( ! wpsc_is_store_admin() ) {
		return;
	}

	$file_id = $_REQUEST['wpsc_download_id'];
	check_admin_referer( 'wpsc-admin-download-file-' . $file_id );

	$file_data = get_post( $file_id );
	_wpsc_force_download_file( $file_id );
}

if ( ! empty( $_REQUEST['wpsc_download_id'] ) ) {
	add_action( 'admin_init', '_wpsc_admin_download_file' );
}

function wpsc_select_variation_file( $file_id, $variation_ids, $variation_combination_id = null ) {
	global $wpdb;
	$file_list = wpsc_uploaded_files();
	$unique_id_component = ((int)$variation_combination_id) . "_" . str_replace( ",", "_", $variation_ids );

	$output = "<div class='variation_settings_contents'>\r\n";
	$output .= "<span class='admin_product_notes select_product_note '>" . __( 'Choose a downloadable file for this variation', 'wpsc' ) . "</span>\r\n";
	$output .= "<div class='select_variation_file'>\r\n";

	$num = 0;
	$output .= "  <p>\r\n";
	$output .= "    <input type='radio' name='variation_priceandstock[{$variation_ids}][file]' value='0' id='select_variation_file{$unique_id_component}_{$num}' " . ((!is_numeric( $file_id ) || ($file_id < 1)) ? "checked='checked'" : "") . " />\r\n";
	$output .= "    <label for='select_variation_file{$unique_id_component}_{$num}'>" . __( 'No Product', 'wpsc' ) . "</label>\r\n";
	$output .= "  </p>\r\n";

	foreach ( (array)$file_list as $file ) {
		$num++;
		$output .= "  <p>\r\n";
		$output .= "    <input type='radio' name='variation_priceandstock[{$variation_ids}][file]' value='" . $file['file_id'] . "' id='select_variation_file{$unique_id_component}_{$num}' " . ((is_numeric( $file_id ) && ($file_id == $file['file_id'])) ? "checked='checked'" : "") . " />\r\n";
		$output .= "    <label for='select_variation_file{$unique_id_component}_{$num}'>" . $file['display_filename'] . "</label>\r\n";
		$output .= "  </p>\r\n";
	}

	$output .= "</div>\r\n";
	$output .= "</div>\r\n";

	return $output;
}

function wpsc_list_product_themes( $theme_name = null ) {
	global $wpdb;

	if ( !$selected_theme = get_option( 'wpsc_selected_theme' ) )
		$selected_theme = 'default';

	$theme_list = wpsc_list_dir( $theme_path );

	foreach ( $theme_list as $theme_file ) {
		if ( is_dir( WPSC_CORE_THEME_PATH . $theme_file ) && is_file( WPSC_CORE_THEME_PATH . $theme_file . "/" . $theme_file . ".css" ) ) {
			$theme[$theme_file] = get_theme_data( WPSC_CORE_THEME_PATH . $theme_file . "/" . $theme_file . ".css" );
		}
	}

	$output .= "<select name='wpsc_options[wpsc_selected_theme]'>\r\n";

	foreach ( (array)$theme as $theme_file => $theme_data ) {
		if ( stristr( $theme_file, $selected_theme ) ) {
			$selected = "selected='selected'";
		} else {
			$selected = "";
		}
		$output .= "<option value='$theme_file' $selected>" . $theme_data['Name'] . "</option>\r\n";
	}

	$output .= "</select>\r\n";

	return $output;
}

?>
