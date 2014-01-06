<?php
/**
 * WPSC Product modifying functions
 *
 * @package wp-e-commerce
 * @since 3.7
 */

function wpsc_get_max_upload_size(){
	return size_format( wp_max_upload_size() );
}

/**
* wpsc_admin_submit_product function
* @internal Was going to completely refactor sanitise forms and wpsc_insert_product, but they are also used by the import system
 * which I'm not really familiar with...so I'm not touching them :)  Erring on the side of redundancy and caution I'll just
 * refactor this to do the job.
* @return nothing
*/
function wpsc_admin_submit_product( $post_ID, $post ) {
	if ( ! is_admin() )
		return;

	global $wpdb;

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || $post->post_type != 'wpsc-product' )
		return;

    //Type-casting ( not so much sanitization, which would be good to do )
    $post_data  = stripslashes_deep( $_POST );
    $product_id = $post_ID;

	$post_data['additional_description'] = isset( $post_data['additional_description'] ) ? $post_data['additional_description'] : '';

	if ( ! isset( $post_data['meta'] ) && isset( $_POST['meta'] ) ) {
		$post_data['meta'] = (array) $_POST['meta'];
	}

	if ( isset( $post_data['meta']['_wpsc_price'] ) )
		$post_data['meta']['_wpsc_price'] = wpsc_string_to_float( $post_data['meta']['_wpsc_price'] );

	if ( isset( $post_data['meta']['_wpsc_special_price'] ) )
		$post_data['meta']['_wpsc_special_price'] = wpsc_string_to_float( $post_data['meta']['_wpsc_special_price'] );

	if ( isset( $post_data['meta']['_wpsc_sku'] ) && $post_data['meta']['_wpsc_sku'] == __('N/A', 'wpsc') ) {
		$post_data['meta']['_wpsc_sku'] = '';
	}

	if( isset( $post_data['meta']['_wpsc_is_donation'] ) )
		$post_data['meta']['_wpsc_is_donation'] = 1;
	else
		$post_data['meta']['_wpsc_is_donation'] = 0;

	if ( ! isset( $post_data['meta']['_wpsc_limited_stock'] ) ){
		$post_data['meta']['_wpsc_stock'] = false;
	} else {
		$post_data['meta']['_wpsc_stock'] = isset( $post_data['meta']['_wpsc_stock'] ) ? (int) $post_data['meta']['_wpsc_stock'] : 0;
	}

	unset($post_data['meta']['_wpsc_limited_stock']);
	if(!isset($post_data['meta']['_wpsc_product_metadata']['notify_when_none_left'])) $post_data['meta']['_wpsc_product_metadata']['notify_when_none_left'] = 0;
	if(!isset($post_data['meta']['_wpsc_product_metadata']['unpublish_when_none_left'])) $post_data['meta']['_wpsc_product_metadata']['unpublish_when_none_left'] = '';
    if(!isset($post_data['quantity_limited'])) $post_data['quantity_limited'] = '';
    if(!isset($post_data['special'])) $post_data['special'] = '';
    if(!isset($post_data['meta']['_wpsc_product_metadata']['no_shipping'])) $post_data['meta']['_wpsc_product_metadata']['no_shipping'] = '';

	$post_data['meta']['_wpsc_product_metadata']['notify_when_none_left'] = (int)(bool)$post_data['meta']['_wpsc_product_metadata']['notify_when_none_left'];
	$post_data['meta']['_wpsc_product_metadata']['unpublish_when_none_left'] = (int)(bool)$post_data['meta']['_wpsc_product_metadata']['unpublish_when_none_left'];
	$post_data['meta']['_wpsc_product_metadata']['quantity_limited'] = (int)(bool)$post_data['quantity_limited'];
	$post_data['meta']['_wpsc_product_metadata']['special'] = (int)(bool)$post_data['special'];
	$post_data['meta']['_wpsc_product_metadata']['no_shipping'] = (int)(bool)$post_data['meta']['_wpsc_product_metadata']['no_shipping'];

	// Product Weight
	if(!isset($post_data['meta']['_wpsc_product_metadata']['display_weight_as'])) $post_data['meta']['_wpsc_product_metadata']['display_weight_as'] = '';

	if ( isset( $post_data['meta']['_wpsc_product_metadata']['weight'] ) ) {
		$weight = wpsc_string_to_float( $post_data['meta']['_wpsc_product_metadata']['weight'] );
		$weight = wpsc_convert_weight( $weight, $post_data['meta']['_wpsc_product_metadata']['weight_unit'], "pound", true);
		$post_data['meta']['_wpsc_product_metadata']['weight'] = $weight;
        $post_data['meta']['_wpsc_product_metadata']['display_weight_as'] = $post_data['meta']['_wpsc_product_metadata']['weight_unit'];
	}

	if ( isset( $post_data['meta']['_wpsc_product_metadata']['dimensions'] ) ) {
		$dimensions =& $post_data['meta']['_wpsc_product_metadata']['dimensions'];
		foreach ( $dimensions as $key => $value ) {
			if ( ! in_array( $key, array( 'height', 'width', 'length' ) ) )
				continue;

			$dimensions[$key] = wpsc_string_to_float( $value );
		}
	}

	// table rate price
	$post_data['meta']['_wpsc_product_metadata']['table_rate_price'] = isset( $post_data['table_rate_price'] ) ? $post_data['table_rate_price'] : array();

	// if table_rate_price is unticked, wipe the table rate prices
	if ( empty( $post_data['table_rate_price']['state'] ) ) {
		$post_data['meta']['_wpsc_product_metadata']['table_rate_price']['table_price'] = array();
		$post_data['meta']['_wpsc_product_metadata']['table_rate_price']['quantity'] = array();
	}

	if ( ! empty( $post_data['meta']['_wpsc_product_metadata']['table_rate_price']['table_price'] ) ) {
		foreach ( (array) $post_data['meta']['_wpsc_product_metadata']['table_rate_price']['table_price'] as $key => $value ){
			if(empty($value)){
				unset($post_data['meta']['_wpsc_product_metadata']['table_rate_price']['table_price'][$key]);
				unset($post_data['meta']['_wpsc_product_metadata']['table_rate_price']['quantity'][$key]);
			}
		}
	}

	if ( isset( $post_data['meta']['_wpsc_product_metadata']['shipping'] ) ) {
		$post_data['meta']['_wpsc_product_metadata']['shipping']['local'] = wpsc_string_to_float( $post_data['meta']['_wpsc_product_metadata']['shipping']['local'] );
		$post_data['meta']['_wpsc_product_metadata']['shipping']['international'] = wpsc_string_to_float( $post_data['meta']['_wpsc_product_metadata']['shipping']['international'] );
	}

	if ( ! empty( $post_data['meta']['_wpsc_product_metadata']['wpec_taxes_taxable_amount'] ) )
		$post_data['meta']['_wpsc_product_metadata']['wpec_taxes_taxable_amount'] = wpsc_string_to_float(
			$post_data['meta']['_wpsc_product_metadata']['wpec_taxes_taxable_amount']
		);

	// Advanced Options
	if ( isset( $post_data['meta']['_wpsc_product_metadata']['engraved'] ) ) {
		$post_data['meta']['_wpsc_product_metadata']['engraved'] = (int) (bool) $post_data['meta']['_wpsc_product_metadata']['engraved'];
	} else {
		$post_data['meta']['_wpsc_product_metadata']['engraved'] = 0;
	}

	if ( isset( $post_data['meta']['_wpsc_product_metadata']['can_have_uploaded_image'] ) ) {
		$post_data['meta']['_wpsc_product_metadata']['can_have_uploaded_image'] = (int) (bool) $post_data['meta']['_wpsc_product_metadata']['can_have_uploaded_image'];
	} else {
		$post_data['meta']['_wpsc_product_metadata']['can_have_uploaded_image'] = 0;
	}

	if ( ! isset($post_data['meta']['_wpsc_product_metadata']['google_prohibited'])) $post_data['meta']['_wpsc_product_metadata']['google_prohibited'] = '';
	$post_data['meta']['_wpsc_product_metadata']['google_prohibited'] = (int)(bool)$post_data['meta']['_wpsc_product_metadata']['google_prohibited'];

	$post_data['files'] = $_FILES;

	if(isset($post_data['post_title']) && $post_data['post_title'] != '') {

	$product_columns = array(
		'name' => '',
		'description' => '',
		'additional_description' => '',
		'price' => null,
		'weight' => null,
		'weight_unit' => '',
		'pnp' => null,
		'international_pnp' => null,
		'file' => null,
		'image' => '0',
		'quantity_limited' => '',
		'quantity' => null,
		'special' => null,
		'special_price' => null,
		'display_frontpage' => null,
		'notax' => null,
		'publish' => null,
		'active' => null,
		'donation' => null,
		'no_shipping' => null,
		'thumbnail_image' => null,
		'thumbnail_state' => null
	);

	foreach($product_columns as $column => $default)
	{
		if (!isset($post_data[$column])) $post_data[$column] = '';

		if($post_data[$column] !== null) {
			$update_values[$column] = $post_data[$column];
		} else if(($update != true) && ($default !== null)) {
			$update_values[$column] = ($default);
		}
	}
	// if we succeed, we can do further editing (todo - if_wp_error)

	// if we have no categories selected, assign one.
	if ( isset( $post_data['tax_input']['wpsc_product_category'] ) && count( $post_data['tax_input']['wpsc_product_category'] ) == 1 && $post_data['tax_input']['wpsc_product_category'][0] == 0){
		$post_data['tax_input']['wpsc_product_category'][1] = wpsc_add_product_category_default($product_id);
	}

	// and the meta
	wpsc_update_product_meta($product_id, $post_data['meta']);

	// and the custom meta
	wpsc_update_custom_meta($product_id, $post_data);

	//and the alt currency
	if ( ! empty( $post_data['newCurrency'] ) ) {
		foreach( (array) $post_data['newCurrency'] as $key =>$value ){
			wpsc_update_alt_product_currency( $product_id, $value, $post_data['newCurrPrice'][$key] );
		}
	}

	if($post_data['files']['file']['tmp_name'] != '') {
		wpsc_item_process_file($product_id, $post_data['files']['file']);
	} else {
		if (!isset($post_data['select_product_file'])) $post_data['select_product_file'] = null;
	  	wpsc_item_reassign_file($product_id, $post_data['select_product_file']);
	}

	if(isset($post_data['files']['preview_file']['tmp_name']) && ($post_data['files']['preview_file']['tmp_name'] != '')) {
 		wpsc_item_add_preview_file($product_id, $post_data['files']['preview_file']);
	}
	do_action('wpsc_edit_product', $product_id);
	}
	return $product_id;
}


function wpsc_pre_update( $data , $postarr ) {
 	if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || $postarr["post_type"] != 'wpsc-product' )
        return $data;
    if( isset( $postarr["additional_description"] ) )
        $data["post_excerpt"] = $postarr["additional_description"];

	 if( isset( $postarr["parent_post"] ) && !empty( $postarr["parent_post"] ) )
        $data["post_parent"] = $postarr["parent_post"];

	// Sanitize status for variations (see #324)
	if ( $data['post_parent'] && ( ! isset( $data['ID'] ) || $data['post_parent'] != $data['ID'] ) && $data['post_status'] == 'publish' ) {
		$data['post_status'] = 'inherit';
	}

	if ( ! empty( $postarr['meta'] ) && ( ! isset( $postarr['meta']['_wpsc_product_metadata']['enable_comments'] ) || $postarr['meta']['_wpsc_product_metadata']['enable_comments'] == 0 || empty( $postarr['meta']['_wpsc_product_metadata']['enable_comments'] ) ) ) {
		$data["comment_status"] = "closed";
	} else {
		$data["comment_status"] = "open";
	}

    //Can anyone explain to me why this is here?
    if ( isset( $sku ) && ( $sku != '' ) )
        $data['guid'] = $sku;

    return $data;
}
add_filter( 'wp_insert_post_data','wpsc_pre_update', 99, 2 );
add_action( 'save_post', 'wpsc_admin_submit_product', 5, 2 );
add_action( 'admin_notices', 'wpsc_admin_submit_notices' );

/**
 * Remove category meta box from variation editor. This would disassociate variations
 * with the default category. See #431 (http://code.google.com/p/wp-e-commerce/issues/detail?id=431)
 *
 */
function wpsc_variation_remove_metaboxes() {
	global $post;
	if ( ! $post->post_parent )
		return;

	remove_meta_box( 'wpsc_product_categorydiv', 'wpsc-product', 'side' );
}
add_action( 'add_meta_boxes_wpsc-product', 'wpsc_variation_remove_metaboxes', 99 );

function wpsc_admin_submit_notices() {
    global $current_screen, $post;

    if( $current_screen->id != 'wpsc-product' || !isset( $_SESSION['product_error_messages'] ) )
            return;
    foreach ( $_SESSION['product_error_messages'] as $error )
        echo "<div id=\"message\" class=\"updated below-h2\"><p>".$error."</p></div>";
    unset( $_SESSION['product_error_messages'] );
}

/**
  * wpsc_add_product_category_default, if there is no category assigned assign first product category as default
  *
  * @since 3.8
  * @param $product_id (int) the Post ID
  * @return null
  */
function wpsc_add_product_category_default( $product_id ){
	$terms = get_terms( 'wpsc_product_category', array( 'orderby' => 'id', 'hide_empty' => 0 ) );
	if ( ! empty( $terms ) ) {
		$default = array_shift( $terms );
		wp_set_object_terms( $product_id , array( $default->slug ) , 'wpsc_product_category' );
	}
}
/**
* wpsc_sanitise_product_forms function
*
* @return array - Sanitised product details
*/
function wpsc_sanitise_product_forms($post_data = null) {
	if ( empty($post_data) ) {
		$post_data = &$_POST;
	}

	$post_data = stripslashes_deep( $post_data );

	$post_data['name'] = isset($post_data['post_title']) ? $post_data['post_title'] : '';
	$post_data['title'] = $post_data['name'];
	$post_data['description'] = isset($post_data['content']) ? $post_data['content'] : '';
	$post_data['additional_description'] = isset($post_data['additional_description']) ? $post_data['additional_description'] : '';
	$post_data['post_status'] = 'draft';

	if(isset($post_data['publish'])) {
		$post_data['post_status'] = 'publish';
	} else if(isset($post_data['unpublish'])) {
		$post_data['post_status'] = 'draft';
	}

	$post_data['meta']['_wpsc_price'] = wpsc_string_to_float( $post_data['meta']['_wpsc_price'] );
	$post_data['meta']['_wpsc_special_price'] = wpsc_string_to_float( $post_data['meta']['_wpsc_special_price'] );
	$post_data['meta']['_wpsc_sku'] = $post_data['meta']['_wpsc_sku'];
	if (!isset($post_data['meta']['_wpsc_is_donation'])) $post_data['meta']['_wpsc_is_donation'] = '';
	$post_data['meta']['_wpsc_is_donation'] = (int)(bool)$post_data['meta']['_wpsc_is_donation'];
	$post_data['meta']['_wpsc_stock'] = (int)$post_data['meta']['_wpsc_stock'];

	if (!isset($post_data['meta']['_wpsc_limited_stock'])) $post_data['meta']['_wpsc_limited_stock'] = '';
	if((bool)$post_data['meta']['_wpsc_limited_stock'] != true) {
	  $post_data['meta']['_wpsc_stock'] = false;
	}
	unset($post_data['meta']['_wpsc_limited_stock']);
	if(!isset($post_data['meta']['_wpsc_product_metadata']['notify_when_none_left'])) $post_data['meta']['_wpsc_product_metadata']['notify_when_none_left'] = 0;
	if(!isset($post_data['meta']['_wpsc_product_metadata']['unpublish_when_none_left'])) $post_data['meta']['_wpsc_product_metadata']['unpublish_when_none_left'] = '';
    if(!isset($post_data['quantity_limited'])) $post_data['quantity_limited'] = '';
    if(!isset($post_data['special'])) $post_data['special'] = '';
    if(!isset($post_data['meta']['_wpsc_product_metadata']['no_shipping'])) $post_data['meta']['_wpsc_product_metadata']['no_shipping'] = '';

	$post_data['meta']['_wpsc_product_metadata']['notify_when_none_left'] = (int)(bool)$post_data['meta']['_wpsc_product_metadata']['notify_when_none_left'];
	$post_data['meta']['_wpsc_product_metadata']['unpublish_when_none_left'] = (int)(bool)$post_data['meta']['_wpsc_product_metadata']['unpublish_when_none_left'];
	$post_data['meta']['_wpsc_product_metadata']['quantity_limited'] = (int)(bool)$post_data['quantity_limited'];
	$post_data['meta']['_wpsc_product_metadata']['special'] = (int)(bool)$post_data['special'];
	$post_data['meta']['_wpsc_product_metadata']['no_shipping'] = (int)(bool)$post_data['meta']['_wpsc_product_metadata']['no_shipping'];

	// Product Weight
	if(!isset($post_data['meta']['_wpsc_product_metadata']['display_weight_as'])) $post_data['meta']['_wpsc_product_metadata']['display_weight_as'] = '';
    if(!isset($post_data['meta']['_wpsc_product_metadata']['display_weight_as'])) $post_data['meta']['_wpsc_product_metadata']['display_weight_as'] = '';

    $weight = wpsc_string_to_float( $post_data['meta']['_wpsc_product_metadata']['weight'] );
	$weight = wpsc_convert_weight( $weight, $post_data['meta']['_wpsc_product_metadata']['weight_unit'], "pound", true);
	$post_data['meta']['_wpsc_product_metadata']['weight'] = $weight;
	$post_data['meta']['_wpsc_product_metadata']['display_weight_as'] = $post_data['meta']['_wpsc_product_metadata']['weight_unit'];

	$post_data['files'] = $_FILES;
	return $post_data;
}

 /**
	* wpsc_insert_product function
	*
	* @param unknown
	* @return unknown
*/
function wpsc_insert_product($post_data, $wpsc_error = false) {
	global $wpdb, $user_ID;
	$adding = false;
	$update = false;

	$product_columns = array(
		'name' => '',
		'description' => '',
		'additional_description' => '',
		'price' => null,
		'weight' => null,
		'weight_unit' => '',
		'pnp' => null,
		'international_pnp' => null,
		'file' => null,
		'image' => '0',
		'quantity_limited' => '',
		'quantity' => null,
		'special' => null,
		'special_price' => null,
		'display_frontpage' => null,
		'notax' => null,
		'publish' => null,
		'active' => null,
		'donation' => null,
		'no_shipping' => null,
		'thumbnail_image' => null,
		'thumbnail_state' => null
	);


	foreach($product_columns as $column => $default)
	{
		if (!isset($post_data[$column])) $post_data[$column] = '';

		if($post_data[$column] !== null) {
			$update_values[$column] = $post_data[$column];
		} else if(($update != true) && ($default !== null)) {
			$update_values[$column] = $default;
		}
	}

	$product_post_values = array(
		'post_author' => $user_ID,
		'post_content' => $post_data['description'],
		'post_excerpt' => $post_data['additional_description'],
		'post_title' => $post_data['name'],
		'post_status' => $post_data['post_status'],
		'post_type' => "wpsc-product",
		'post_name' => sanitize_title($post_data['name'])
	);
	$product_post_values["comment_status"] = "open";

	if(isset($sku) && ($sku != '')) {
		$product_post_array['guid'] = $sku;
	}



	$product_id = wp_insert_post($product_post_values);
	if ( isset ( $post_data["sticky"] ) ) {
		stick_post($product_id);
	}else {
		unstick_post($product_id);
	}
	if ($product_id == 0 ) {
		if ( $wp_error ) {
			return new WP_Error('db_insert_error', __( 'Could not insert product into the database', 'wpsc' ), $wpdb->last_error);
		} else {
			return 0;
		}
	}
	$adding = true;

	// if we succeed, we can do further editing

	// and the meta
	wpsc_update_product_meta($product_id, $post_data['meta']);
	do_action('wpsc_edit_product', $product_id);
	return $product_id;
}

/**
 * term_id_price function
 * Retreives associated price, if any, with term_id
 * @param integer term ID
 * @param integer parent product price
 * @return integer modified price for child product, based on term ID price and parent price
 */

function term_id_price($term_id, $parent_price) {

	$term_price_arr = get_option( 'term_prices' );

	if ( isset($term_price_arr[$term_id]) ) {
		$price = $term_price_arr[$term_id]["price"];
	} else {
		$price = 0;
	}

	//Check for flat, percentile or differential
		$var_price_type = '';

		if (flat_price($price)) {
			$var_price_type = 'flat';
			$price = floatval($price);
		} elseif ( differential_price($price) ) {
			$var_price_type = 'differential';
		} elseif (percentile_price($price)) {
			$var_price_type = 'percentile';
		}

		if (strchr($price, '-') ) {
			$negative = true;
		} else {
			$positive = true;
		}

		if ($positive) {

			if ( $var_price_type == 'differential' ) {
				$differential = (floatval($price));
				$price = $parent_price + $differential;
			} elseif ( $var_price_type == 'percentile' ) {
				$percentage = (floatval($price) / 100);
				$price = $parent_price + ($parent_price * $percentage);
			}

		} else {

			if ( $var_price_type == 'differential' ) {
				$differential = (floatval($price));
				$price = $parent_price - $differential;
			} elseif ( $var_price_type == 'percentile' ) {
				$percentage = (floatval($price) / 100);
				$price = $parent_price - ($parent_price * $percentage);
			}
		}
	return $price;
}

/**
 * Determine the price of a variation product based on the variation it's assigned
 * to. Because each variation term can have its own price (eg. 10, +10, -5%), this
 * function also takes those into account.
 *
 * @since 3.8.6
 * @param int $variation_id ID of the variation product
 * @param string $terms Optional. Defaults to false. Variation terms assigned to
 * the variation product. Pass this argument to save one SQL query.
 * @return float Calculated price of the variation
 */
function wpsc_determine_variation_price( $variation_id, $term_ids = false ) {
	$flat = array();
	$diff = 0;

	$variation = get_post( $variation_id );
	$price = (float) get_product_meta( $variation->post_parent, 'price', true );

	if ( ! $term_ids )
		$term_ids = wpsc_get_product_terms( $variation_id, 'wpsc-variation', 'term_id' );

	$term_price_arr = get_option( 'term_prices' );
	foreach ( $term_ids as $term_id ) {
		if ( isset( $term_price_arr[$term_id] ) )
			$term_price = trim( $term_price_arr[$term_id]['price'] );
		else
			continue;
		if ( flat_price( $term_price ) ) {
			$flat[] = $term_price;
		} elseif ( differential_price( $term_price ) ) {
			$diff += (float) $term_price;
		} elseif ( percentile_price( $term_price ) ) {
			$diff += (float) $term_price / 100 * $price;
		}
	}
	// Variation price should at least be the maximum of all flat prices
	if ( ! empty( $flat ) )
		$price = max( $flat );
	$price += $diff;
	return $price;
}

/**
 * wpsc_edit_product_variations function.
 * this is the function to make child products using variations
 *
 * @access public
 * @param mixed $product_id
 * @param mixed $post_data
 * @return void
 */
function wpsc_edit_product_variations($product_id, $post_data) {
	global $user_ID;

	$parent = get_post_field( 'post_parent', $product_id );

	if( ! empty( $parent ) )
		return;

	$variations = array();
	$product_children = array();
	if (!isset($post_data['edit_var_val']))
		$post_data['edit_var_val'] = '';

	$variations = (array)$post_data['edit_var_val'];

	// Generate the arrays for variation sets, values and combinations
    $wpsc_combinator = new wpsc_variation_combinator($variations);

	// Retrieve the array containing the variation set IDs
	$variation_sets = $wpsc_combinator->return_variation_sets();

	// Retrieve the array containing the combinations of each variation set to be associated with this product.
	$variation_values = $wpsc_combinator->return_variation_values();

	// Retrieve the array containing the combinations of each variation set to be associated with this product.
	$combinations = $wpsc_combinator->return_combinations();

	$product_terms = wpsc_get_product_terms( $product_id, 'wpsc-variation' );

	$variation_sets_and_values = array_merge($variation_sets, $variation_values);
	$variation_sets_and_values = apply_filters('wpsc_edit_product_variation_sets_and_values', $variation_sets_and_values, $product_id);
	wp_set_object_terms($product_id, $variation_sets_and_values, 'wpsc-variation');

	$parent_id = $_REQUEST['product_id'];

	$child_product_template = array(
		'post_author' 	=> $user_ID,
		'post_content' 	=> get_post_field( 'post_content', $parent_id, 'raw' ),
		'post_excerpt' 	=> get_post_field( 'post_excerpt', $parent_id, 'raw' ),
		'post_title' 	=> get_post_field( 'post_title', $parent_id, 'raw' ),
		'post_status' 	=> 'inherit',
		'post_type' 	=> "wpsc-product",
		'post_parent' 	=> $product_id
	);

	$child_product_meta = get_post_custom($product_id);

	// here we loop through the combinations, get the term data and generate custom product names
	foreach($combinations as $combination) {
		$term_names = array();
		$term_ids = array();
		$term_slugs = array();
		$product_values = $child_product_template;

		$combination_terms = get_terms('wpsc-variation', array(
			'hide_empty'	=> 0,
			'include' 		=> implode(",", $combination),
			'orderby' 		=> 'parent',
		));

		foreach($combination_terms as $term) {
			$term_ids[] = $term->term_id;
			$term_slugs[] = $term->slug;
			$term_names[] = $term->name;
		}

		$product_values['post_title'] .= " (".implode(", ", $term_names).")";
		$product_values['post_name'] = sanitize_title($product_values['post_title']);

		$selected_post = get_posts(array(
			'name' 				=> $product_values['post_name'],
			'post_parent' 		=> $product_id,
			'post_type' 		=> "wpsc-product",
			'post_status' 		=> 'all',
			'suppress_filters' 	=> true
		));
		$selected_post = array_shift($selected_post);
		$child_product_id = wpsc_get_child_object_in_terms($product_id, $term_ids, 'wpsc-variation');
		$already_a_variation = true;
		if($child_product_id == false) {
			$already_a_variation = false;
			if($selected_post != null) {
				$child_product_id = $selected_post->ID;
			} else {
				$child_product_id = wp_insert_post($product_values);
			}
		} else {
			// sometimes there have been problems saving the variations, this gets the correct product ID
			if(($selected_post != null) && ($selected_post->ID != $child_product_id)) {
				$child_product_id = $selected_post->ID;
			}
		}
		$product_children[] = $child_product_id;
		if($child_product_id > 0) {
			wp_set_object_terms($child_product_id, $term_slugs, 'wpsc-variation');
		}
		//JS - 7.9 - Adding loop to include meta data in child product.
		if(!$already_a_variation){
			$this_child_product_meta = apply_filters( 'insert_child_product_meta', $child_product_meta, $product_id, $combination_terms );
			foreach ($this_child_product_meta as $meta_key => $meta_value ) :
				if ($meta_key == "_wpsc_product_metadata") {
					update_post_meta($child_product_id, $meta_key, unserialize($meta_value[0]));
				} else {
					update_post_meta($child_product_id, $meta_key, $meta_value[0]);
				}

			endforeach;

			if ( is_array( $term_ids ) && $price = wpsc_determine_variation_price( $child_product_id, $term_ids ) )
				update_product_meta( $child_product_id, 'price', $price );
		}
	}


	//For reasons unknown, this code did not previously deal with variation deletions.
	//Basically, we'll just check if any existing term associations are missing from the posted variables, delete if they are.
	//Get posted terms (multi-dimensional array, first level = parent var, second level = child var)
	$posted_term = $variations;
	//Get currently associated terms
	$currently_associated_var = $product_terms;

	foreach ($currently_associated_var as $current) {
		$currently_associated_vars[] = $current->term_id;
	}

	foreach ($posted_term as $term=>$val) {
		$posted_terms[] = $term;
		if(is_array($val)) {
			foreach($val as $term2=>$val2) {
				$posted_terms[] = $term2;
			}
		}
	}
	if(!empty($currently_associated_vars)){
		$term_ids_to_delete = array();
		$term_ids_to_delete = array_diff($currently_associated_vars, $posted_terms);
	}
	if(isset($_REQUEST["post_ID"]))
		$post_id = $_REQUEST["post_ID"];
	elseif(isset($_REQUEST["product_id"]))
		$post_id = $_REQUEST["product_id"];
	if(!empty($term_ids_to_delete) && (isset($_REQUEST["product_id"]) ||  isset($post_id))) {
		$post_ids_to_delete = array();

		// Whatever remains, find child products of current product with that term, in the variation taxonomy, and delete
		$post_ids_to_delete = wpsc_get_child_object_in_terms_var($_REQUEST["product_id"], $term_ids_to_delete, 'wpsc-variation');

		if(is_array($post_ids_to_delete) && !empty($post_ids_to_delete)) {
			foreach($post_ids_to_delete as $object_ids) {
				foreach($object_ids as $object_id) {
					wp_delete_post($object_id);
				}
			}
		}
	}
	$current_children = get_posts(array(
		'post_parent'	=> $post_id,
		'post_type'		=> 'wpsc-product',
		'post_status'	=> 'all',
		'numberposts'   => -1
		));

	foreach((array)$current_children as $child_prod){
		$childs[] = $child_prod->ID;
	}
	if(!empty($childs)){
		$old_ids_to_delete = array_diff($childs, $product_children);
		$old_ids_to_delete = apply_filters('wpsc_edit_product_variations_deletion', $old_ids_to_delete);
		if(is_array($old_ids_to_delete) && !empty($old_ids_to_delete)) {
			foreach($old_ids_to_delete as $object_ids) {
				wp_delete_post($object_ids);
			}
		}
	}
}

function wpsc_update_alt_product_currency($product_id, $newCurrency, $newPrice){
	global $wpdb;

	$old_curr = get_product_meta($product_id, 'currency',true);
	$sql = $wpdb->prepare( "SELECT `isocode` FROM `".WPSC_TABLE_CURRENCY_LIST."` WHERE `id`= %d", $newCurrency );
	$isocode = $wpdb->get_var($sql);

	$newCurrency = 'currency';
	$old_curr[$isocode] = $newPrice;
	if(($newPrice != '') &&  ($newPrice > 0.00)){
		update_product_meta($product_id, $newCurrency, $old_curr);
	} else {
		if((empty($old_curr[$isocode]) || 0.00 == $old_curr[$isocode]) && is_array($old_curr))
			unset($old_curr[$isocode]);
		update_product_meta($product_id, $newCurrency, $old_curr);

	}

}

 /**
 * wpsc_update_product_meta function
 *
 * @param integer product ID
 * @param string comma separated tags
 */
function wpsc_update_product_meta($product_id, $product_meta) {
    if($product_meta != null) {
		foreach((array)$product_meta as $key => $value) {
			update_post_meta($product_id, $key, $value);
		}
	}
}

/**
 * Called from javascript within product page to toggle publish status - AJAX
 * @return bool	publish status
 */
function wpsc_ajax_toggle_publish() {
/**
 * @todo - Check Admin Referer
 * @todo - Check Permissions
 */
	$status = (wpsc_toggle_publish_status($_REQUEST['productid'])) ? ('true') : ('false');
	exit( $status );
}
/*
/*  END - Publish /No Publish functions
*/

function wpsc_update_custom_meta($product_id, $post_data) {

	if ( isset( $post_data['new_custom_meta'] ) && $post_data['new_custom_meta'] != null ) {
	foreach((array)$post_data['new_custom_meta']['name'] as $key => $name) {
	    $value = $post_data['new_custom_meta']['value'][(int)$key];
	    if(($name != '') && ($value != '')) {
		add_post_meta($product_id, $name, $value);
	    }
	}
	}

    if (!isset($post_data['custom_meta'])) $post_data['custom_meta'] = '';
    if($post_data['custom_meta'] != null) {
	    foreach((array)$post_data['custom_meta'] as $key => $values) {
		    if(($values['name'] != '') && ($values['value'] != '')) {
			    update_post_meta($product_id, $values['name'], $values['value']);
		    }
	    }
    }
}

 /**
 * wpsc_item_process_file function
 *
 * @param integer product ID
 * @param array the file array from $_FILES
 * @param array the preview file array from $_FILES
 */
function wpsc_item_process_file( $product_id, $submitted_file, $preview_file = null ) {

	add_filter( 'upload_dir', 'wpsc_modify_upload_directory' );

	$overrides = array( 'test_form' => false );

	$time = current_time('mysql');
	if ( $post = get_post( $product_id ) ) {
		if ( substr( $post->post_date, 0, 4 ) > 0 )
			$time = $post->post_date;
	}

	$file = wp_handle_upload( $submitted_file, $overrides, $time );

	if ( isset( $file['error'] ) ) {
		return new WP_Error( 'upload_error', $file['error'] );
	}

	$name_parts = pathinfo( $file['file'] );
	$name       = $name_parts['basename'];

	$url     = $file['url'];
	$type    = $file['type'];
	$file    = $file['file'];
	$title   = $name;
	$content = '';

	// Construct the attachment array
	$attachment = array(
		'post_mime_type' => $type,
		'guid'           => $url,
		'post_parent'    => $product_id,
		'post_title'     => $title,
		'post_content'   => $content,
		'post_type'      => "wpsc-product-file",
		'post_status'    => 'inherit'
	);

	// Save the data
	$id = wp_insert_attachment( $attachment, $file, $product_id );
	remove_filter( 'upload_dir', 'wpsc_modify_upload_directory' );
}

function wpsc_modify_upload_directory($input) {
	$previous_subdir = $input['subdir'];
	$download_subdir = str_replace($input['basedir'], '', WPSC_FILE_DIR);
	$input['path'] = substr_replace(str_replace($previous_subdir, $download_subdir, $input['path']),'',-1);
	$input['url'] = substr_replace(str_replace($previous_subdir, $download_subdir, $input['url']),'',-1);
	$input['subdir'] = substr_replace(str_replace($previous_subdir, $download_subdir, $input['subdir']),'',-1);
	return $input;
}

function wpsc_modify_preview_directory($input) {
	$previous_subdir = $input['subdir'];
	$download_subdir = str_replace($input['basedir'], '', WPSC_PREVIEW_DIR);

	$input['path'] = substr_replace(str_replace($previous_subdir, $download_subdir, $input['path']),'',-1);
	$input['url'] = substr_replace(str_replace($previous_subdir, $download_subdir, $input['url']),'',-1);
	$input['subdir'] = substr_replace(str_replace($previous_subdir, $download_subdir, $input['subdir']),'',-1);

	return $input;
}

 /**
 * wpsc_item_reassign_file function
 *
 * @param integer product ID
 * @param string the selected file name;
 */
function wpsc_item_reassign_file($product_id, $selected_files) {
	global $wpdb;
	$product_file_list = array();
	// initialise $idhash to null to prevent issues with undefined variables and error logs
	$idhash = null;

	$args = array(
		'post_type' => 'wpsc-product-file',
		'post_parent' => $product_id,
		'numberposts' => -1,
		'post_status' => 'any'
	);

	$attached_files = (array)get_posts($args);

	foreach($attached_files as $key => $attached_file) {
		$attached_files_by_file[$attached_file->post_title] = $attached_files[$key];
	}

	/* if we are editing, grab the current file and ID hash */
	if(!$selected_files) {
		// unlikely that anyone will ever upload a file called .none., so its the value used to signify clearing the product association
		return null;
	}

	foreach($selected_files as $selected_file) {
		// if we already use this file, there is no point doing anything more.
		$file_is_attached = false;
		$selected_file_path = WPSC_FILE_DIR.basename($selected_file);

		if(isset($attached_files_by_file[$selected_file])) {
			$file_is_attached = true;
		}

		if($file_is_attached == false ) {
			$type = wpsc_get_mimetype($selected_file_path);
			$attachment = array(
				'post_mime_type' => $type,
				'post_parent' => $product_id,
				'post_title' => $selected_file,
				'post_content' => '',
				'post_type' => "wpsc-product-file",
				'post_status' => 'inherit'
			);
			wp_insert_post($attachment);
		} else {
			$product_post_values = array(
				'ID' => $attached_files_by_file[$selected_file]->ID,
				'post_status' => 'inherit'
			);
			wp_update_post($product_post_values);
		}
	}


	foreach($attached_files as $attached_file) {
		if(!in_array($attached_file->post_title, $selected_files)) {
			$product_post_values = array(
				'ID' => $attached_file->ID,
				'post_status' => 'draft'
			);
			wp_update_post($product_post_values);
		}
	}

	return true;
}

 /**
 * wpsc_delete_preview_file
 *
 * @param integer product ID
 */

function wpsc_delete_preview_file($product_id) {

	$args = array(
	'post_type' => 'wpsc-preview-file',
	'post_parent' => $product_id,
	'numberposts' => -1,
	'post_status' => 'all'
	);

	$preview_files = (array)get_posts( $args );

	foreach( $preview_files as $preview ) {
		$preview_id = $preview->ID;
		wp_delete_post($preview_id);
	}
	return true;
}

 /**
 * wpsc_item_add_preview_file function
 *
 * @param integer product ID
 * @param array the preview file array from $_FILES
 */
function wpsc_item_add_preview_file($product_id, $preview_file) {
  global $wpdb;

  wpsc_delete_preview_file($product_id);

  add_filter('upload_dir', 'wpsc_modify_preview_directory');
	$overrides = array('test_form'=>false);

	$time = current_time('mysql');
	if ( $post = get_post($product_id) ) {
		if ( substr( $post->post_date, 0, 4 ) > 0 )
			$time = $post->post_date;
	}

	$file = wp_handle_upload($preview_file, $overrides, $time);

	if ( isset($file['error']) )
		return new WP_Error( 'upload_error', $file['error'] );

	$name_parts = pathinfo($file['file']);
	$name = $name_parts['basename'];

	$url = $file['url'];
	$type = $file['type'];
	$file = $file['file'];
	$title = $name;
	$content = '';

	// Construct the attachment array
	$attachment = array(
		'post_mime_type' => $type,
		'guid' => $url,
		'post_parent' => $product_id,
		'post_title' => $title,
		'post_content' => $content,
		'post_type' => "wpsc-preview-file",
		'post_status' => 'inherit'
	);

	// Save the data
	$id = wp_insert_post($attachment, $file, $product_id);
	remove_filter('upload_dir', 'wpsc_modify_preview_directory');
  	return $id;


}

/**
 * wpsc_variation_combinator class.
 * Produces all combinations of variations selected for this product
 * this class is based off the example code from here:
 * http://www.php.net/manual/en/ref.array.php#94910
 * Thanks, phektus, you are awesome, whoever you are.
 */
class wpsc_variation_combinator {
	var $variation_sets = array();
	var $variation_values = array();
	var $reprocessed_array = array();
	var $combinations= array();

function wpsc_variation_combinator($variation_sets) {
	if( $variation_sets ) {
		foreach($variation_sets as $variation_set_id => $variation_set) {
			$this->variation_sets[] = absint($variation_set_id);
			$new_variation_set = array();
			if( $variation_set ) {
				foreach($variation_set as $variation => $active) {
					if($active == 1) {
						$new_variation_set[] = array(absint($variation));
						$this->variation_values[] = $variation;
					}
				}
			}
			$this->reprocessed_array[] = $new_variation_set;
		}
		$this->get_combinations(array(), $this->reprocessed_array, 0);
	}
}


	function get_combinations($batch, $elements, $i)  {
        if ($i >= count($elements)) {
            $this->combinations[] = $batch;
        } else {
            foreach ($elements[$i] as $element) {
                $this->get_combinations(array_merge($batch, $element), $elements, $i + 1);
            }
        }
	}

	function return_variation_sets() {
		return $this->variation_sets;
	}

	function return_variation_values() {
		return $this->variation_values;
	}

	function return_combinations() {
		return $this->combinations;

	}
}

function wpsc_variations_stock_remaining($product_id){
	global $wpdb;
	return $wpdb->get_var( $wpdb->prepare( '
		SELECT
			sum(`pm`.`meta_value`)
		FROM
			`' . $wpdb->postmeta . '` `pm`
		JOIN
			`' . $wpdb->posts . '` `p`
			ON
			`pm`.`post_id` = `p`.`id`
		WHERE
			`p`.`post_type`= "wpsc-product"
			AND
			`p`.`post_parent` = %d
			AND
			`pm`.`meta_key` = "_wpsc_stock"
	', $product_id ) );
}

function flat_price( $price ) {
	if ( ! empty( $price ) && strchr( $price, '-' ) === false && strchr( $price, '+' ) === false && strchr( $price, '%' ) === false )
		return true;
}

function percentile_price( $price ) {
	if ( ! empty( $price ) && ( strchr( $price, '-' ) || strchr( $price, '+' ) ) && strchr( $price, '%' ) )
		return true;
}

function differential_price( $price ) {
	if ( ! empty( $price ) && ( strchr( $price, '-' ) || strchr( $price, '+' ) ) && strchr( $price, '%' ) === false )
		return true;
}

/**
 * Refresh variation terms assigned to parent product based on the variations it has.
 *
 * @since 3.8.9
 * @access private
 * @param  int $parent_id Parent product ID
 */
function _wpsc_refresh_parent_product_terms( $parent_id ) {
	$children = get_children( array(
		'post_parent' => $parent_id,
		'post_status' => array( 'publish', 'inherit' ),
	) );

	$children_ids = wp_list_pluck( $children, 'ID' );

	$children_terms = wp_get_object_terms( $children_ids, 'wpsc-variation' );
	$new_terms = array();
	foreach ( $children_terms as $term ) {
		if ( $term->parent )
			$new_terms[] = $term->parent;
	}

	$children_term_ids = wp_list_pluck( $children_terms, 'term_id' );
	$new_terms = array_merge( $new_terms, $children_term_ids );
	$new_terms = array_unique( $new_terms );
	$new_terms = array_map( 'absint', $new_terms );
	wp_set_object_terms( $parent_id, $new_terms, 'wpsc-variation' );
}

/**
 * Make sure parent product's assigned terms are refreshed when its variations are deleted or trashed
 *
 * @since 3.8.9
 * @access private
 * @param  int $post_id Parent product ID
 */
function _wpsc_action_refresh_variation_parent_terms( $post_id ) {
	$post = get_post( $post_id );
	if ( $post->post_type != 'wpsc-product' || ! $post->post_parent || in_array( $post->post_status, array( 'publish', 'inherit' ) ) )
		return;

	_wpsc_refresh_parent_product_terms( $post->post_parent );
}

/**
 * Make sure parent product's assigned terms are refresh when its variations' statuses are changed
 *
 * @since 3.8.9
 * @access private
 * @param  string $new_status New status
 * @param  string $old_status Old status
 * @param  object $post       Variation object
 */
function _wpsc_action_transition_post_status( $new_status, $old_status, $post ) {
	if ( $post->post_type != 'wpsc-product' || ! $post->post_parent )
		return;

	_wpsc_refresh_parent_product_terms( $post->post_parent );
}

/**
 * Prevent parent terms from being refreshed when its variations are updated. This is useful when
 * the variations are being mass updated.
 *
 * @since  3.8.9
 * @access private
 */
function _wpsc_remove_refresh_variation_parent_term_hooks() {
	remove_action( 'transition_post_status', '_wpsc_action_transition_post_status', 10, 3 );
	remove_action( 'deleted_post', '_wpsc_action_refresh_variation_parent_terms', 10, 1 );
}

/**
 * Add hooks so that parent product's assigned terms are refreshed when its variations are updated.
 *
 * @since  3.8.9
 * @access private
 */
function _wpsc_add_refresh_variation_parent_term_hooks() {
	add_action( 'transition_post_status', '_wpsc_action_transition_post_status', 10, 3 );
	add_action( 'deleted_post', '_wpsc_action_refresh_variation_parent_terms', 10, 1 );
}

_wpsc_add_refresh_variation_parent_term_hooks();