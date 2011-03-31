<?php
/**
 * WP eCommerce database updating functions
 *
 * @package wp-e-commerce
 * @since 3.8
 */

/**
 * wpsc_convert_category_groups function.
 * 
 * @access public
 * @return void
 */
function wpsc_convert_category_groups() {
	global $wpdb, $user_ID;

	//if they're updating from 3.6, and they've got categories with no group, let's fix that problem, eh?
 	$categorisation_groups = $wpdb->get_results("SELECT * FROM `".WPSC_TABLE_CATEGORISATION_GROUPS."` WHERE `active` IN ('1')");
	if(count($categorisation_groups) == 0) {
		$sql = "insert into `".WPSC_TABLE_CATEGORISATION_GROUPS."` set `id` = 1000, `name` = 'Default Group', `description` = 'This is your default category group', `active` = 1, `default` = 1;";
	$wpdb->query($sql);
		$sql = "update `".WPSC_TABLE_PRODUCT_CATEGORIES."` set group_id = 1000";
		$wpdb->query($sql);
		$categorisation_groups = $wpdb->get_results("SELECT * FROM `".WPSC_TABLE_CATEGORISATION_GROUPS."` WHERE `active` IN ('1')");
	}
		
	foreach((array)$categorisation_groups as $cat_group) {
		$category_id = wpsc_get_meta($cat_group->id, 'category_group_id', 'wpsc_category_group');

		if(!is_numeric($category_id) || ( $category_id < 1)) {
			$new_category = wp_insert_term( $cat_group->name, 'wpsc_product_category', array('description' => $cat_group->description));
				if(!is_wp_error($new_category))
				$category_id = $new_category['term_id'];
				
		}
		if(is_numeric($category_id)) {
			
			wpsc_update_meta($cat_group->id, 'category_group_id', $category_id, 'wpsc_category_group');
			wpsc_update_categorymeta($category_id, 'category_group_id', $cat_group->id);
			
			wpsc_update_categorymeta($category_id, 'image', '');
			wpsc_update_categorymeta($category_id, 'uses_billing_address', 0);
		}	
		
		if(!is_wp_error($new_category))
		wpsc_convert_categories($category_id, $cat_group->id);
	}
delete_option("wpsc_product_category_children");
_get_term_hierarchy('wpsc_product_category');
}

/**
 * wpsc_convert_categories function.
 * 
 * @access public
 * @param int $parent_category. (default: 0)
 * @return void
 */
function wpsc_convert_categories($new_parent_category, $group_id, $old_parent_category = 0) {
	global $wpdb, $user_ID;
	
	if($old_parent_category > 0) {
		$categorisation = $wpdb->get_results("SELECT * FROM `".WPSC_TABLE_PRODUCT_CATEGORIES."` WHERE `active` IN ('1') AND `group_id` IN ('{$group_id}') AND `category_parent` IN ('{$old_parent_category}')");
	} else {
		$categorisation = $wpdb->get_results("SELECT * FROM `".WPSC_TABLE_PRODUCT_CATEGORIES."` WHERE `active` IN ('1') AND `group_id` IN ('{$group_id}') AND `category_parent` IN (0)");
	}
	
	if($categorisation > 0) {

		foreach((array)$categorisation as $category) {
			$category_id = wpsc_get_meta($category->id, 'category_id', 'wpsc_old_category');

			if(!is_numeric($category_id) || ( $category_id < 1)) {
				$new_category = wp_insert_term( $category->name, 'wpsc_product_category', array('description' => $category->description, 'parent' => $new_parent_category));
					if(!is_wp_error($new_category))
						$category_id = $new_category['term_id'];			
			}
			
			if(is_numeric($category_id)) {
				
				wpsc_update_meta($category->id, 'category_id', $category_id, 'wpsc_old_category');
				wpsc_update_categorymeta($category_id, 'category_id', $category->id);
				
				wpsc_update_categorymeta($category_id, 'image', $category->image);
				wpsc_update_categorymeta($category_id, 'display_type', $category->display_type);
				
				wpsc_update_categorymeta($category_id, 'image_height', $category->image_height);	
			    wpsc_update_categorymeta($category_id, 'image_width', $category->image_width);
			    
				$use_additonal_form_set = wpsc_get_categorymeta($category->id, 'use_additonal_form_set');
	      		if($use_additonal_form_set != '') {
					wpsc_update_categorymeta($category_id, 'use_additonal_form_set', $use_additonal_form_set);
				} else {
					wpsc_delete_categorymeta($category_id, 'use_additonal_form_set');
				}
	
	
				wpsc_update_categorymeta($category_id, 'uses_billing_address', (bool)(int)wpsc_get_categorymeta($category->id, 'uses_billing_address'));

	
			}
			if($category_id > 0) {
				wpsc_convert_categories($category_id, $group_id, $category->id);
			}
				
		}	
	}	
}

function wpsc_convert_variation_sets() {
	global $wpdb, $user_ID;
	$variation_sets = $wpdb->get_results("SELECT * FROM `".WPSC_TABLE_PRODUCT_VARIATIONS."`");
	
	foreach((array)$variation_sets as $variation_set) {
		$variation_set_id = wpsc_get_meta($variation_set->id, 'variation_set_id', 'wpsc_variation_set');
		
		if(!is_numeric($variation_set_id) || ( $variation_set_id < 1)) {
			$new_variation_set = wp_insert_term( $variation_set->name, 'wpsc-variation',array('parent' => 0));
		
			if(!is_wp_error($new_variation_set))
				$variation_set_id = $new_variation_set['term_id'];		
		}
		
		if(is_numeric($variation_set_id)) {
			wpsc_update_meta($variation_set->id, 'variation_set_id', $variation_set_id, 'wpsc_variation_set');
			
			
			$variations = $wpdb->get_results("SELECT * FROM `".WPSC_TABLE_VARIATION_VALUES."` WHERE `variation_id` IN ({$variation_set->id})");
			foreach((array)$variations as $variation) {
				$variation_id = wpsc_get_meta($variation->id, 'variation_id', 'wpsc_variation');
				
				if(!is_numeric($variation_id) || ( $variation_id < 1)) {
					$new_variation = wp_insert_term( $variation->name, 'wpsc-variation',array('parent' => $variation_set_id));
					
					if(!is_wp_error($new_variation))
						$variation_id = $new_variation['term_id'];				
				}				
				if(is_numeric($variation_id)) {
					wpsc_update_meta($variation->id, 'variation_id', $variation_id, 'wpsc_variation');
					
				}
			}			
		}	
	}

}

/**
 * wpsc_convert_products_to_posts function.
 * 
 * @access public
 * @return void
 */
function wpsc_convert_products_to_posts() {
  global $wpdb, $user_ID;
  // Select all products
  
	$product_data = $wpdb->get_results("SELECT `".WPSC_TABLE_PRODUCT_LIST."`. * , `".WPSC_TABLE_PRODUCT_ORDER."`.order FROM `".WPSC_TABLE_PRODUCT_LIST."` LEFT JOIN `".WPSC_TABLE_PRODUCT_ORDER."` ON `".WPSC_TABLE_PRODUCT_LIST."`.id = `".WPSC_TABLE_PRODUCT_ORDER."`.product_id WHERE `".WPSC_TABLE_PRODUCT_LIST."`.`active` IN ( '1' )
GROUP BY ".WPSC_TABLE_PRODUCT_LIST.".id", ARRAY_A);
	foreach((array)$product_data as $product) {
		$post_id = (int)$wpdb->get_var($wpdb->prepare( "SELECT `post_id` FROM `{$wpdb->postmeta}` WHERE meta_key = %s AND `meta_value` = %d LIMIT 1", '_wpsc_original_id', $product['id'] ));
		
		$sku = old_get_product_meta($product['id'], 'sku', true);

		if($post_id == 0) {
			$post_status = "publish";
			if($product['publish'] != 1) {
				$post_status = "draft";
			}
			
			//check the product added time with the current time to make sure its not out - this aviods the future post status
			$product_added_time = strtotime($product['date_added']);
			$current_time = time();
			
			$post_date = $product['date_added'];
			if ((int)$current_time < (int)$product_added_time)
				$post_date = date("Y-m-d H:i:s");
			
			$product_post_values = array(
				'post_author' => $user_ID,
				'post_date' => $post_date,
				'post_content' => $product['description'],
				'post_excerpt' => $product['additional_description'],
				'post_title' => $product['name'],
				'post_status' => $post_status,
				'post_type' => "wpsc-product",
				'post_name' => sanitize_title($product['name']),
				'menu_order' => $product['order']
			);
			$post_id = wp_insert_post($product_post_values);
		}
		
		$product_meta = $wpdb->get_results("
			SELECT 	IF( ( `custom` != 1	),
					CONCAT( '_wpsc_', `meta_key` ) ,
				`meta_key`
				) AS `meta_key`,
				`meta_value`
			FROM `".WPSC_TABLE_PRODUCTMETA."`
			WHERE `product_id` = " . $product['id'] . "
			AND `meta_value` != ''", ARRAY_A);
					
		$post_data = array();
		
		foreach($product_meta as $k => $pm) :
			if($pm['meta_value'] == 'om')
				$pm['meta_value'] = 1;
			$pm['meta_value'] = maybe_unserialize($pm['meta_value']);
			if(strpos($pm['meta_key'], '_wpsc_') === 0)
				$post_data['_wpsc_product_metadata'][$pm['meta_key']] = $pm['meta_value'];
			else
				update_post_meta($post_id, $pm['meta_key'], $pm['meta_value']);
		endforeach;


		$post_data['_wpsc_original_id'] = (int)$product['id'];
		$post_data['_wpsc_price'] = (float)$product['price'];
		$post_data['_wpsc_special_price'] = (float)$product['special_price'];
		$post_data['_wpsc_stock'] = (float)$product['quantity'];
		$post_data['_wpsc_is_donation'] = $product['donation'];
		$post_data['_wpsc_sku'] = $sku;
		if((bool)$product['quantity_limited'] != true) {
		  $post_data['_wpsc_stock'] = false;
		}
		unset($post_data['_wpsc_limited_stock']);
		
		$post_data['_wpsc_product_metadata']['is_stock_limited'] = (int)(bool)$product['quantity_limited'];
		
		// Product Weight
		$post_data['_wpsc_product_metadata']['weight'] = wpsc_convert_weight($product['weight'], $product['weight_unit'], "pound", true);
		$post_data['_wpsc_product_metadata']['weight_unit'] = $product['weight_unit'];
		$post_data['_wpsc_product_metadata']['display_weight_as'] = $product['weight_unit'];
		
		$post_data['_wpsc_product_metadata']['has_no_shipping'] = (int)(bool)$product['no_shipping'];
		$post_data['_wpsc_product_metadata']['shipping'] = array('local' => $product['pnp'], 'international' => $product['international_pnp']);
		
		
		$post_data['_wpsc_product_metadata']['quantity_limited'] = (int)(bool)$product['quantity_limited'];
		$post_data['_wpsc_product_metadata']['special'] = (int)(bool)$product['special'];
		if(isset($post_data['meta'])) {
			$post_data['_wpsc_product_metadata']['unpublish_when_none_left'] = (int)(bool)$post_data['meta']['_wpsc_product_metadata']['unpublish_when_none_left'];
		}
		$post_data['_wpsc_product_metadata']['no_shipping'] = (int)(bool)$product['no_shipping'];
						
		foreach($post_data as $meta_key => $meta_value) {
			// prefix all meta keys with _wpsc_
			update_post_meta($post_id, $meta_key, $meta_value);
		}

		// get the wordpress upload directory data
		$wp_upload_dir_data = wp_upload_dir();
		$wp_upload_basedir = $wp_upload_dir_data['basedir'];
		
		$category_ids = array();
		$category_data = $wpdb->get_col("SELECT `category_id` FROM `".WPSC_TABLE_ITEM_CATEGORY_ASSOC."` WHERE `product_id` IN ('{$product['id']}')");
		foreach($category_data as $old_category_id) {
			$category_ids[] = wpsc_get_meta($old_category_id, 'category_id', 'wpsc_old_category');
		
		}
		wp_set_product_categories($post_id, $category_ids);
		
		$product_data = get_post($post_id);
		$image_data = $wpdb->get_results("SELECT * FROM `".WPSC_TABLE_PRODUCT_IMAGES."` WHERE `product_id` IN ('{$product['id']}') ORDER BY `image_order` ASC", ARRAY_A);
		foreach((array)$image_data as $image_row) {
			// Get the image path info
			$image_pathinfo = pathinfo($image_row['image']);
			
			// use the path info to clip off the file extension
			$image_name = basename($image_pathinfo['basename'], ".{$image_pathinfo['extension']}");
			
			// construct the full image path
			$full_image_path = WPSC_IMAGE_DIR.$image_row['image'];
			$attached_file_path = str_replace($wp_upload_basedir."/", '', $full_image_path);
			$upload_dir = wp_upload_dir();
			$new_path = $upload_dir['path'].'/'.$image_name.'.'.$image_pathinfo['extension'];
			if(is_file($full_image_path)){
				copy($full_image_path, $new_path);
			}else{
				continue;
			}
			// construct the full image url
			$subdir = $upload_dir['subdir'].'/'.$image_name.'.'.$image_pathinfo['extension'];
			$subdir = substr($subdir , 1);
			$attachment_id = (int)$wpdb->get_var("SELECT `ID` FROM `{$wpdb->posts}` WHERE `post_title` IN('$image_name') AND `post_parent` IN('$post_id') LIMIT 1");

			// get the image MIME type
			$mime_type_data = wpsc_get_mimetype($full_image_path, true);
			if((int)$attachment_id == 0 ) {
				// construct the image data array
				$image_post_values = array(
					'post_author' => $user_ID,
					'post_parent' => $post_id,
					'post_date' => $product_data->post_date,
					'post_content' => $image_name,
					'post_title' => $image_name,
					'post_status' => "inherit",
					'post_type' => "attachment",
					'post_name' => sanitize_title($image_name),
					'post_mime_type' => $mime_type_data['mime_type'],
					'menu_order' => absint($image_row['image_order']),
					'guid' => $new_path
				);
				$attachment_id = wp_insert_post($image_post_values);
			}
			$image_size_data = @getimagesize($full_image_path);
			$image_metadata = array(
				'width' => $image_size_data[0],
				'height' => $image_size_data[1],
				'file' => $subdir
			);
			
		
			update_post_meta( $attachment_id, '_wp_attached_file', $subdir );
			update_post_meta( $attachment_id, '_wp_attachment_metadata', $image_metadata);

		}

	}
	
	//Just throwing the payment gateway update in here because it doesn't really warrant it's own function :)
	
	$custom_gateways = get_option('custom_gateway_options');
	array_walk($custom_gateways, "wpec_update_gateway");
	update_option('custom_gateway_options', $custom_gateways);
}

function wpec_update_gateway(&$value,$key) {
		if ( $value == "testmode" )
			$value = "wpsc_merchant_testmode";
		if ( $value == "paypal_certified" )
			$value = "wpsc_merchant_paypal_express";
		if ( $value == "paypal_multiple" )
			$value = "wpsc_merchant_paypal_standard";
		if ( $value == "paypal_pro" )
			$value = "wpsc_merchant_paypal_pro";
		
}
function wpsc_convert_variation_combinations() {
	global $wpdb, $user_ID, $current_version_number;

	// get the posts
	// I use a direct SQL query here because the get_posts function sometimes does not function for a reason that is not clear.
	$posts = $wpdb->get_results("SELECT * FROM `{$wpdb->posts}` WHERE `post_type` IN('wpsc-product')");
	
	
	$posts = get_posts( array(
		'post_type' => 'wpsc-product',
		'post_status' => 'all',
		'numberposts' => -1
	) );
    
	foreach((array)$posts as $post) {
	
		$base_product_terms = array();
		//create a post template
		$child_product_template = array(
			'post_author' => $user_ID,
			'post_content' => $post->post_content,
			'post_excerpt' => $post->post_excerpt,
			'post_title' => $post->post_title,
			'post_status' => 'inherit',
			'post_type' => "wpsc-product",
			'post_name' => sanitize_title($post->post_title),
			'post_parent' => $post->ID
		);
		
		// select the original product ID
		$original_id = get_post_meta($post->ID, '_wpsc_original_id', true);
		$parent_stock = get_post_meta($post->ID, '_wpsc_stock', true);

		// select the variation set associations
		$variation_set_associations = $wpdb->get_col("SELECT `variation_id` FROM ".WPSC_TABLE_VARIATION_ASSOC." WHERE `associated_id` = '{$original_id}'");
		// select the variation associations if the count of variation sets is greater than zero
		if(($original_id > 0) && (count($variation_set_associations) > 0)) {
			$variation_associations = $wpdb->get_col("SELECT `value_id` FROM ".WPSC_TABLE_VARIATION_VALUES_ASSOC." WHERE `product_id` = '{$original_id}' AND `variation_id` IN(".implode(", ", $variation_set_associations).") AND `visible` IN ('1')");
		} else {
			// otherwise, we have no active variations, skip to the next product
			continue;
		}
	
		foreach($variation_set_associations as $variation_set_id) {
			$base_product_terms[] = wpsc_get_meta($variation_set_id, 'variation_set_id', 'wpsc_variation_set');
		}
	
		foreach($variation_associations as $variation_association_id) {
			$base_product_terms[] = wpsc_get_meta($variation_association_id, 'variation_id', 'wpsc_variation');
		}
		
		// Now that we have the term IDs, we need to retrieve the slugs, as wp_set_object_terms will not use IDs in the way we want
		// If we pass IDs into wp_set_object_terms, it creates terms using the ID as the name.
		$parent_product_terms = get_terms('wpsc-variation', array(
			'hide_empty' => 0,
			'include' => implode(",", $base_product_terms),
			'orderby' => 'parent'
		));
		$base_product_term_slugs = array();
		foreach($parent_product_terms as $parent_product_term) {
			$base_product_term_slugs[] = $parent_product_term->slug;
		
		}
		
		wp_set_object_terms($post->ID, $base_product_term_slugs, 'wpsc-variation');
		
		// select all variation "products"
		$variation_items = $wpdb->get_results("SELECT * FROM ".WPSC_TABLE_VARIATION_PROPERTIES." WHERE `product_id` = '{$original_id}'");

		foreach((array)$variation_items as $variation_item) {
			// initialize the requisite arrays to empty
			$variation_ids = array();
			$term_data = array();
			// make a temporary copy of the product teplate
			$product_values = $child_product_template;
			
			// select all values this "product" is associated with, then loop through them, getting the term id of the variation using the value ID
			$variation_associations_combinations = $wpdb->get_results("SELECT * FROM ".WPSC_TABLE_VARIATION_COMBINATIONS." WHERE `priceandstock_id` = '{$variation_item->id}'");
			foreach((array)$variation_associations_combinations as $association) {
				$variation_id = (int)wpsc_get_meta($association->value_id, 'variation_id', 'wpsc_variation');
				// discard any values that are null, as they break the selecting of the terms
				if($variation_id > 0 && in_array($association->value_id, $variation_associations) ) {
					$variation_ids[] = $variation_id;
				}
			} 
			
			// if we have more than zero remaining terms, get the term data, then loop through it to convert it to a more useful set of arrays.
			if(count($variation_ids) > 0 && ( count($variation_set_associations) == count($variation_ids) ) ) {
				$combination_terms = get_terms('wpsc-variation', array(
					'hide_empty' => 0,
					'include' => implode(",", $variation_ids),
					'orderby' => 'parent',
				));

				foreach($combination_terms as $term) {
					$term_data['ids'][] = $term->term_id;
					$term_data['slugs'][] = $term->slug;
					$term_data['names'][] = $term->name;
				}
				
				$product_values['post_title'] .= " (".implode(", ", $term_data['names']).")";
				$product_values['post_name'] = sanitize_title($product_values['post_title']);
				
				$selected_post = get_posts(array(
					'name' => $product_values['post_name'],
					'post_parent' => $post->ID,
					'post_type' => "wpsc-product",
					'post_status' => 'all',
					'suppress_filters' => true
				));
				
				$selected_post = array_shift($selected_post);
				
				$child_product_id = wpsc_get_child_object_in_terms($post->ID, $term_data['ids'], 'wpsc-variation');
				$post_data = array();
				$post_data['_wpsc_price'] = (float)$variation_item->price;
				$post_data['_wpsc_stock'] = (float)$variation_item->stock;
				if( !is_numeric( $parent_stock ) )
					$post_data['_wpsc_stock'] = false;
				  
				$post_data['_wpsc_original_variation_id'] = (float)$variation_item->id;
			
				// Product Weight
				$post_data['_wpsc_product_metadata']['weight'] = wpsc_convert_weight($variation_item->weight, $variation_item->weight_unit, "pound", true);
				$post_data['_wpsc_product_metadata']['display_weight_as'] = $variation_item->weight_unit;
				$post_data['_wpsc_product_metadata']['weight_unit'] = $variation_item->weight_unit;
 	
            	//file
	
				if($child_product_id == false) {
					if($selected_post != null) {
						$child_product_id = $selected_post->ID;
					} else {
						$child_product_id = wp_update_post($product_values);
					}
				} else {
					// sometimes there have been problems saving the variations, this gets the correct product ID
					if(($selected_post != null) && ($selected_post->ID != $child_product_id)) {
						$child_product_id = $selected_post->ID;
					}
				}
				if($child_product_id > 0) {
					
					foreach($post_data as $meta_key => $meta_value) {
						// prefix all meta keys with _wpsc_
						update_post_meta($child_product_id, $meta_key, $meta_value);
					}
							
				
					wp_set_object_terms($child_product_id, $term_data['slugs'], 'wpsc-variation');
				}
				
				unset($term_data);
			}

		}
	}
delete_option("wpsc-variation_children");
_get_term_hierarchy('wpsc-variation');
delete_option("wpsc_product_category_children");
_get_term_hierarchy('wpsc_product_category');
}

function wpsc_update_files() {
	global $wpdb, $user_ID; 
	$product_files = $wpdb->get_results("SELECT * FROM ".WPSC_TABLE_PRODUCT_FILES."");
	
	foreach($product_files as $product_file) {
		$variation_post_ids = array();
		if(!empty($product_file->product_id)){
			$product_post_id = (int)$wpdb->get_var($wpdb->prepare( "SELECT `post_id` FROM `{$wpdb->postmeta}` WHERE meta_key = %s AND `meta_value` = %d LIMIT 1", '_wpsc_original_id', $product_file->product_id ));
		}else{
			$product_post_id = (int)$wpdb->get_var("SELECT `id` FROM ".WPSC_TABLE_PRODUCT_LIST." WHERE file=".$product_file->id);
			$product_post_id = (int)$wpdb->get_var($wpdb->prepare( "SELECT `post_id` FROM `{$wpdb->postmeta}` WHERE meta_key = %s AND `meta_value` = %d LIMIT 1", '_wpsc_original_id', $product_post_id ));
		}	
		$variation_items = $wpdb->get_col("SELECT `id` FROM ".WPSC_TABLE_VARIATION_PROPERTIES." WHERE `file` = '{$product_file->id}'");
		
		if(count($variation_items) > 0) {
			$variation_post_ids = $wpdb->get_col("SELECT `post_id` FROM `{$wpdb->postmeta}` WHERE meta_key = '_wpsc_original_variation_id' AND `meta_value` IN(".implode(", ", $variation_items).")");
		}

		$attachment_template = array(
			'post_mime_type' => $product_file->mimetype,
			'post_title' => $product_file->filename,
			'post_name' => $product_file->idhash,
			'post_content' => '',
			'post_parent' => $product_post_id,
			'post_type' => "wpsc-product-file",
			'post_status' => 'inherit'
		);
		
		$file_id = wpsc_get_meta($product_file->id, '_new_file_id', 'wpsc_files');
		
		if($file_id == null && count($variation_post_ids) == 0) {
			$file_data = $attachment_template;
			$file_data['post_parent'] = $product_post_id;
			$new_file_id = wp_insert_post($file_data);
			wpsc_update_meta($product_file->id, '_new_file_id', $new_file_id, 'wpsc_files');
		}
		if(count($variation_post_ids) > 0) {
			foreach($variation_post_ids as $variation_post_id) {				
				$old_file_id = get_product_meta($variation_post_id, 'old_file_id', true);
				if($old_file_id == null) {
					$file_data = $attachment_template;
					$file_data['post_parent'] = $variation_post_id;
					$new_file_id = wp_insert_post($file_data);
					update_product_meta($variation_post_id, 'old_file_id', $product_file->id, 'wpsc_files');
				}
			}
		}
		
		if(!empty($product_file->preview)){
			$preview_template = array(
			'post_mime_type' => $product_file->preview_mimetype,
			'post_title' => $product_file->preview,
			'post_name' => $product_file->filename,
			'post_content' => '',
			'post_parent' => $new_file_id,
			'post_type' => "wpsc-product-preview",
			'post_status' => 'inherit'
			);
			wp_insert_post($preview_template);
		
		
		}
	}
		
	$download_ids = $wpdb->get_col("SELECT `id` FROM ".WPSC_TABLE_DOWNLOAD_STATUS."");
	foreach($download_ids as $download_id) {
		if(wpsc_get_meta($download_id, '_is_legacy', 'wpsc_downloads') !== 'false') {
			wpsc_update_meta($download_id, '_is_legacy', 'true', 'wpsc_downloads');
		}
	}
}

function wpsc_update_database() {
	global $wpdb;
	
		$result = $wpdb->get_results("SHOW COLUMNS FROM ". WPSC_TABLE_PURCHASE_LOGS."", ARRAY_A);
	if (!$result) {
		echo 'Could not run query: ' . mysql_error();
		exit;
	}
	foreach($result as $row_key=>$value) {
		$has_taxes = ($value["Field"] == "wpec_taxes_total" || $value["Field"] == "wpec_taxes_rate") ? true: false;
	}
	if (!$has_taxes) {
		$add_fields = $wpdb->query($wpdb->prepare("ALTER TABLE ".WPSC_TABLE_PURCHASE_LOGS." ADD wpec_taxes_total decimal(11,2)"));
		$add_fields = $wpdb->query($wpdb->prepare("ALTER TABLE ".WPSC_TABLE_PURCHASE_LOGS." ADD wpec_taxes_rate decimal(11,2)"));
	}	
}
/*
 * The Old Get Product Meta for 3.7 Tables used in converting Products to Posts
 */

function old_get_product_meta($product_id, $key, $single = false) {
  global $wpdb, $post_meta_cache, $blog_id;  
  $product_id = (int)$product_id;
  if($product_id > 0) {
    $meta_id = $wpdb->get_var("SELECT `id` FROM `".WPSC_TABLE_PRODUCTMETA."` WHERE `meta_key` IN('$key') AND `product_id` = '$product_id' LIMIT 1");
    //exit($meta_id);
    if(is_numeric($meta_id) && ($meta_id > 0)) {      
      if($single != false) {
        $meta_values = maybe_unserialize($wpdb->get_var("SELECT `meta_value` FROM `".WPSC_TABLE_PRODUCTMETA."` WHERE `meta_key` IN('$key') AND `product_id` = '$product_id' LIMIT 1"));
			} else {
        $meta_values = $wpdb->get_col("SELECT `meta_value` FROM `".WPSC_TABLE_PRODUCTMETA."` WHERE `meta_key` IN('$key') AND `product_id` = '$product_id'");
				$meta_values = array_map('maybe_unserialize', $meta_values);
			}
		}
	} else {
    $meta_values = false;
	}
	if (is_array($meta_values) && (count($meta_values) == 1)) {
		return array_pop($meta_values);
	} else {
		return $meta_values;
	}
}
?>