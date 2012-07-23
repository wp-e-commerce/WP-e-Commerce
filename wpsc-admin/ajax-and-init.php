<?php

/**
 * WP eCommerce Admin AJAX functions
 *
 * These are the WPSC Admin AJAX functions
 *
 * @package wp-e-commerce
 * @since 3.7
 */
function wpsc_admin_ajax() {
	global $wpdb;

	if ( isset( $_POST['action'] ) && $_POST['action'] == 'product-page-order' ) {
		$current_order = get_option( 'wpsc_product_page_order' );
		$new_order = $_POST['order'];

		if ( isset( $new_order["advanced"] ) ) {
			$current_order["advanced"] = array_unique( explode( ',', $new_order["advanced"] ) );
		}
		if ( isset( $new_order["side"] ) ) {
			$current_order["side"] = array_unique( explode( ',', $new_order["side"] ) );
		}

		update_option( 'wpsc_product_page_order', $current_order );
		exit( print_r( $order, 1 ) );
	}


	if ( isset( $_POST['save_image_upload_state'] ) && $_POST['save_image_upload_state'] == 'true' && is_numeric( $_POST['image_upload_state'] ) ) {
		$upload_state = (int)(bool)$_POST['image_upload_state'];
		update_option( 'wpsc_use_flash_uploader', $upload_state );
		exit( "done" );
	}

	if ( isset( $_POST['remove_variation_value'] ) && $_POST['remove_variation_value'] == "true" && is_numeric( $_POST['variation_value_id'] ) ) {
		$value_id = absint( $_GET['variation_value_id'] );
		echo wp_delete_term( $value_id, 'wpsc-variation' );
		exit();
	}

	if ( isset( $_POST['hide_ecom_dashboard'] ) && $_POST['hide_ecom_dashboard'] == 'true' ) {
		require_once (ABSPATH . WPINC . '/rss.php');
		$rss = fetch_rss( 'http://www.instinct.co.nz/feed/' );
		$rss->items = array_slice( $rss->items, 0, 5 );
		$rss_hash = sha1( serialize( $rss->items ) );
		update_option( 'wpsc_ecom_news_hash', $rss_hash );
		exit( 1 );
	}

	if ( isset( $_REQUEST['log_state'] ) && $_REQUEST['log_state'] == "true" && is_numeric( $_POST['id'] ) && is_numeric( $_POST['value'] ) ) {
		$newvalue = $_POST['value'];
		if ( $_REQUEST['suspend'] == 'true' ) {
			if ( $_REQUEST['value'] == 1 && function_exists('wpsc_member_dedeactivate_subscriptions'))
					wpsc_member_dedeactivate_subscriptions( $_POST['id'] );
			elseif( function_exists('wpsc_member_deactivate_subscriptions'))
				wpsc_member_deactivate_subscriptions( $_POST['id'] );

			exit();
		} else {

			$log_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `id` = '%d' LIMIT 1", $_POST['id'] ), ARRAY_A );
			if ( ($newvalue == 2) && function_exists( 'wpsc_member_activate_subscriptions' ) ) {
				wpsc_member_activate_subscriptions( $_POST['id'] );
			}

			$wpdb->update(
				    WPSC_TABLE_PURCHASE_LOGS,
				    array(
					'processed' => $newvalue
				    ),
				    array(
					'id' => $_POST['id']
				    ),
				    '%d',
				    '%d'
				);
			if ( ($newvalue > $log_data['processed']) && ($log_data['processed'] < 2) ) {
				transaction_results( $log_data['sessionid'], false );
			}

			$status_name = wpsc_find_purchlog_status_name( $purchase['processed'] );
			echo "document.getElementById(\"form_group_" . absint( $_POST['id'] ) . "_text\").innerHTML = '" . $status_name . "';\n";


			$year = date( "Y" );
			$month = date( "m" );
			$start_timestamp = mktime( 0, 0, 0, $month, 1, $year );
			$end_timestamp = mktime( 0, 0, 0, ($month + 1 ), 0, $year );

			echo "document.getElementById(\"log_total_month\").innerHTML = '" . addslashes( wpsc_currency_display( admin_display_total_price( $start_timestamp, $end_timestamp ) ) ) . "';\n";
			echo "document.getElementById(\"log_total_absolute\").innerHTML = '" . addslashes( wpsc_currency_display( admin_display_total_price() ) ) . "';\n";
			exit();
		}
	}
}

function wpsc_change_currency() {
	if ( is_numeric( $_POST['currencyid'] ) ) {
		$currency_data = $wpdb->get_results( "SELECT `symbol`,`symbol_html`,`code` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id`='" . $_POST['currencyid'] . "' LIMIT 1", ARRAY_A );
		$price_out = null;
		if ( $currency_data[0]['symbol'] != '' ) {
			$currency_sign = $currency_data[0]['symbol_html'];
		} else {
			$currency_sign = $currency_data[0]['code'];
		}
		echo $currency_sign;
	}
}
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'change_currency') )
	add_action( 'admin_init', 'wpsc_change_currency' );

function wpsc_rearrange_images() {
	global $wpdb;
	$images = explode( ",", $_POST['order'] );
	$product_id = absint( $_POST['product_id'] );
	$timestamp = time();

	$new_main_image = null;
	$have_set_first_item = false;
	$i = 0;
	foreach ( $images as $image ) {
		if ( $image > 0 ) {
			$wpdb->update(
				    $wpdb->posts,
				    array(
					'menu_order' => $i
				    ),
				    array(
					'ID' => $image
				    ),
				    '%d',
				    '%d'
				);
			$i++;
		}
	}
	$output = wpsc_main_product_image_menu( $product_id );
	echo "image_menu = '';\n\r";
	echo "image_id = '" . $new_main_image . "';\n\r";
	exit();
}
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'rearrange_images') )
	add_action( 'admin_init', 'wpsc_rearrange_images' );

function wpsc_clean_categories() {
	global $wpdb, $wp_rewrite;
	$sql_query = "SELECT `id`, `name`, `active` FROM `" . WPSC_TABLE_PRODUCT_CATEGORIES . "`";
	$sql_data = $wpdb->get_results( $sql_query, ARRAY_A );
	foreach ( (array)$sql_data as $datarow ) {
		if ( $datarow['active'] == 1 ) {
			$tidied_name = strtolower( trim( $datarow['name'] ) );
			$url_name = sanitize_title( $tidied_name );
			$similar_names = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) AS `count`, MAX(REPLACE(`nice-name`, '%s', '')) AS `max_number` FROM `" . WPSC_TABLE_PRODUCT_CATEGORIES . "` WHERE `nice-name` REGEXP '^( " . esc_sql( $url_name ) . " ){1}(\d)*$' AND `id` NOT IN (%d) ", $url_name, $datarow['id'] ), ARRAY_A );
			$extension_number = '';

			if ( $similar_names['count'] > 0 )
			    $extension_number = (int)$similar_names['max_number'] + 2;

			$url_name .= $extension_number;

			$wpdb->update(
				WPSC_TABLE_PRODUCT_CATEGORIES,
				array(
				    'nice-name' => $url_name
				),
				array(
				    'id' => $datarow['id']
				),
				'%s',
				'%d'
			    );

			$updated;

		} else if ( $datarow['active'] == 0 ) {
			$wpdb->update(
				WPSC_TABLE_PRODUCT_CATEGORIES,
				array(
				    'nice-name' => ''
				),
				array(
				    'id' => $datarow['id']
				),
				'%s',
				'%d'
			    );
			$updated;
		}
	}
	$wp_rewrite->flush_rules();
	$sendback = wp_get_referer();

	if ( isset( $updated ) ) {
		$sendback = add_query_arg( 'updated', $updated, $sendback );
	}
	if ( isset( $_SESSION['wpsc_settings_curr_page'] ) ) {
		$sendback = add_query_arg( 'tab', $_SESSION['wpsc_settings_curr_page'], $sendback );
	}

	wp_redirect( $sendback );

	exit();
}
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'clean_categories') )
	add_action( 'admin_init', 'wpsc_clean_categories' );

//change the gateway settings
function wpsc_gateway_settings() {
	//To update options
	if ( isset( $_POST['wpsc_options'] ) ) {
		foreach ( $_POST['wpsc_options'] as $key => $value ) {
			if ( $value != get_option( $key ) ) {
				update_option( $key, $value );
			}
		}
		unset( $_POST['wpsc_options'] );
	}



	if ( isset( $_POST['user_defined_name'] ) && is_array( $_POST['user_defined_name'] ) ) {
		$payment_gateway_names = get_option( 'payment_gateway_names' );

		if ( !is_array( $payment_gateway_names ) ) {
			$payment_gateway_names = array( );
		}
		$payment_gateway_names = array_merge( $payment_gateway_names, (array)$_POST['user_defined_name'] );
		update_option( 'payment_gateway_names', $payment_gateway_names );
	}
	$custom_gateways = get_option( 'custom_gateway_options' );

	$nzshpcrt_gateways = nzshpcrt_get_gateways();
	foreach ( $nzshpcrt_gateways as $gateway ) {
		if ( in_array( $gateway['internalname'], $custom_gateways ) ) {
			if ( isset( $gateway['submit_function'] ) ) {
				call_user_func_array( $gateway['submit_function'], array( ) );
				$changes_made = true;
			}
		}
	}
	if ( (isset( $_POST['payment_gw'] ) && $_POST['payment_gw'] != null ) ) {
		update_option( 'payment_gateway', $_POST['payment_gw'] );
	}
}
if ( isset( $_REQUEST['wpsc_gateway_settings'] ) && ($_REQUEST['wpsc_gateway_settings'] == 'gateway_settings') )
	add_action( 'admin_init', 'wpsc_gateway_settings' );

function wpsc_google_shipping_settings() {
	if ( isset( $_POST['submit'] ) ) {
		foreach ( (array)$_POST['google_shipping'] as $key => $country ) {
			if ( $country == 'on' ) {
				$google_shipping_country[] = $key;
				$updated++;
			}
		}
		update_option( 'google_shipping_country', $google_shipping_country );
		$sendback = wp_get_referer();
		$sendback = remove_query_arg( 'googlecheckoutshipping', $sendback );

		if ( isset( $updated ) ) {
			$sendback = add_query_arg( 'updated', $updated, $sendback );
		}

		wp_redirect( $sendback );
		exit();
	}
}

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'google_shipping_settings') ) {
	add_action( 'admin_init', 'wpsc_google_shipping_settings' );
}

function wpsc_update_variations() {
	$product_id = absint( $_POST["product_id"] );
	$product_type_object = get_post_type_object('wpsc-product');
	if (!current_user_can($product_type_object->cap->edit_post, $product_id))
		return;

	//Setup postdata
	$post_data = array( );
	$post_data['edit_var_val'] = isset( $_POST['edit_var_val'] ) ? $_POST["edit_var_val"] : '';
	$post_data['description'] = isset( $_POST['description'] ) ? $_POST["description"] : '';
	$post_data['additional_description'] = isset( $_POST['additional_description'] ) ? $_POST['additional_description'] : '';
	$post_data['name'] = (!empty($_POST['name']))?$_POST['name']:$_POST["post_title"];

	//Add or delete variations
	wpsc_edit_product_variations( $product_id, $post_data );
	if (defined('DOING_AJAX') && DOING_AJAX) {
		wpsc_admin_product_listing( $product_id );
		die();
	}
}

if ( isset($_POST["edit_var_val"]) )
	add_action( 'admin_init', 'wpsc_update_variations', 50 );
add_action('wp_ajax_wpsc_update_variations', 'wpsc_update_variations', 50 );

function wpsc_delete_variation_set() {
	check_admin_referer( 'delete-variation' );

	if ( is_numeric( $_GET['deleteid'] ) ) {
		$variation_id = absint( $_GET['deleteid'] );

		$variation_set = get_term( $variation_id, 'wpsc-variation', ARRAY_A );


		$variations = get_terms( 'wpsc-variation', array(
					'hide_empty' => 0,
					'parent' => $variation_id
				) );

		foreach ( (array)$variations as $variation ) {
			$return_value = wp_delete_term( $variation->term_id, 'wpsc-variation' );
		}

		if ( !empty( $variation_set ) ) {
			$return_value = wp_delete_term( $variation_set['term_id'], 'wpsc-variation' );
		}
		$deleted = 1;
	}

	$sendback = wp_get_referer();
	if ( isset( $deleted ) ) {
		$sendback = add_query_arg( 'deleted', $deleted, $sendback );
	}
	$sendback = remove_query_arg( array(
				'deleteid',
				'variation_id'
					), $sendback );

	wp_redirect( $sendback );
	exit();
}

function wpsc_force_flush_theme_transients() {
	// Flush transients
	wpsc_flush_theme_transients( true );

	// Bounce back
	$sendback = wp_get_referer();
	wp_redirect( $sendback );

	exit();
}
if ( isset( $_REQUEST['wpsc_flush_theme_transients'] ) && ( $_REQUEST['wpsc_flush_theme_transients'] == 'true' ) )
	add_action( 'admin_init', 'wpsc_force_flush_theme_transients' );

function wpsc_backup_theme() {
	$wp_theme_path = get_stylesheet_directory();
	wpsc_recursive_copy( $wp_theme_path, WPSC_THEME_BACKUP_DIR );
	$_SESSION['wpsc_themes_backup'] = true;
	$sendback = wp_get_referer();
	wp_redirect( $sendback );

	exit();
}
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ( $_REQUEST['wpsc_admin_action'] == 'backup_themes' ) )
	add_action( 'admin_init', 'wpsc_backup_theme' );


function wpsc_delete_coupon(){
	global $wpdb;

	check_admin_referer( 'delete-coupon' );
	$coupon_id = (int)$_GET['delete_id'];

	if(isset($coupon_id)) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM `".WPSC_TABLE_COUPON_CODES."` WHERE `id` = %d LIMIT 1", $coupon_id ) );
			$deleted = 1;
	}
	$sendback = wp_get_referer();
	if ( isset( $deleted ) )
		$sendback = add_query_arg( 'deleted', $deleted, $sendback );

	$sendback = remove_query_arg( array('deleteid',), $sendback );
	wp_redirect( $sendback );
	exit();
}

if ( isset( $_GET['purchase_log_csv'] ) && ( 'true' == $_GET['purchase_log_csv'] ) )
	add_action( 'admin_init', 'wpsc_purchase_log_csv' );

if ( isset( $_REQUEST['ajax'] ) && isset( $_REQUEST['admin'] ) && ($_REQUEST['ajax'] == "true") && ($_REQUEST['admin'] == "true") )
	add_action( 'admin_init', 'wpsc_admin_ajax' );

// Variation set deleting init code starts here
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ( 'wpsc-delete-variation-set' == $_REQUEST['wpsc_admin_action'] ) )
	add_action( 'admin_init', 'wpsc_delete_variation_set' );

//Delete Coupon
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ( 'wpsc-delete-coupon' == $_REQUEST['wpsc_admin_action'] ) )
	add_action( 'admin_init', 'wpsc_delete_coupon' );


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
 * If it doesn't exist, let's create a multi-dimensional associative array
 * that will contain all of the term/price associations
 *
 * @param <type> $variation
 */
function variation_price_field( $variation ) {
	$term_prices = get_option( 'term_prices' );

	if ( is_object( $variation ) )
		$term_id = $variation->term_id;

	if ( empty( $term_prices ) || !is_array( $term_prices ) ) {

		$term_prices = array( );
		if ( isset( $term_id ) ) {
			$term_prices[$term_id] = array( );
			$term_prices[$term_id]["price"] = '';
			$term_prices[$term_id]["checked"] = '';
		}
		add_option( 'term_prices', $term_prices );
	}

	if ( isset( $term_id ) && is_array( $term_prices ) && array_key_exists( $term_id, $term_prices ) )
		$price = esc_attr( $term_prices[$term_id]["price"] );
	else
		$price = '';

	if( !isset( $_GET['action'] ) ) {
	?>
	<div class="form-field">
		<label for="variation_price"><?php _e( 'Variation Price', 'wpsc' ); ?></label>
		<input type="text" name="variation_price" id="variation_price" style="width:50px;" value="<?php echo $price; ?>"><br />
		<span class="description"><?php _e( 'You can list a default price here for this variation.  You can list a regular price (18.99), differential price (+1.99 / -2) or even a percentage-based price (+50% / -25%).', 'wpsc' ); ?></span>
	</div>
	<script type="text/javascript">
		jQuery('#parent option:contains("   ")').remove();
		jQuery('#parent').mousedown(function(){
			jQuery('#parent option:contains("   ")').remove();
		});
	</script>
	<?php
	} else{
	?>
	<tr class="form-field">
            <th scope="row" valign="top">
		<label for="variation_price"><?php _e( 'Variation Price', 'wpsc' ); ?></label>
            </th>
            <td>
		<input type="text" name="variation_price" id="variation_price" style="width:50px;" value="<?php echo $price; ?>"><br />
		<span class="description"><?php _e( 'You can list a default price here for this variation.  You can list a regular price (18.99), differential price (+1.99 / -2) or even a percentage-based price (+50% / -25%).', 'wpsc' ); ?></span>
            </td>
	</tr>
	<?php
	}
}
add_action( 'wpsc-variation_edit_form_fields', 'variation_price_field' );
add_action( 'wpsc-variation_add_form_fields', 'variation_price_field' );

/*
WordPress doesnt let you change the custom post type taxonomy form very easily
Use Jquery to move the set variation (parent) field to the top and add a description
*/
function variation_set_field(){
?>
	<script>
		/* change the text on the variation set from (none) to new variation set*/
		jQuery("#parent option[value='-1']").text("New Variation Set");
		/* Move to the top of the form and add a description */
		jQuery("#tag-name").parent().before( jQuery("#parent").parent().append('<p>Choose the Variation Set you want to add variants to. If your\'e creating a new variation set then select "New Variation Set"</p>') );
		/*
		create a small description about variations below the add variation / set title
		we can then get rid of the big red danger warning
		*/
		( jQuery("div#ajax-response").after('<p>Variations allow you to create options for your products, for example if you\'re selling T-Shirts they will have a size option you can create this as a variation. Size will be the Variation Set name, and it will be a "New Variant Set". You will then create variants (small, medium, large) which will have the "Variation Set" of Size. Once you have made your set you can use the table on the right to manage them (edit, delete). You will be able to order your variants by draging and droping them within their Variation Set.</p>') );
	</script>
<?php
}
add_action( 'wpsc-variation_edit_form_fields', 'variation_set_field' );
add_action( 'wpsc-variation_add_form_fields', 'variation_set_field' );


function category_edit_form(){
?>
	<script type="text/javascript">

	</script>
<?php
}

function variation_price_field_check( $variation ) {

	$term_prices = get_option( 'term_prices' );

	if ( is_array( $term_prices ) && array_key_exists( $variation->term_id, $term_prices ) )
		$checked = ($term_prices[$variation->term_id]["checked"] == 'checked') ? 'checked' : '';
	else
		$checked = ''; ?>

	<tr class="form-field">
		<th scope="row" valign="top"><label for="apply_to_current"><?php _e( 'Apply to current variations?', 'wpsc' ) ?></label></th>
		<td>
			<span class="description"><input type="checkbox" name="apply_to_current" id="apply_to_current" style="width:2%;" <?php echo $checked; ?> /><?php _e( 'By checking this box, the price rule you implement above will be applied to all variations that currently exist.  If you leave it unchecked, it will only apply to products that use this variation created or edited from now on.  Take note, this will apply this rule to <strong>every</strong> product using this variation.  If you need to override it for any reason on a specific product, simply go to that product and change the price.', 'wpsc' ); ?></span>
		</td>
	</tr>
<?php
}
add_action( 'wpsc-variation_edit_form_fields', 'variation_price_field_check' );



/**
 * @todo - Should probably refactor this at some point - very procedural,
 *		   WAY too many foreach loops for my liking :)  But it does the trick
 *
 * @param <type> $term_id
 */
function save_term_prices( $term_id ) {

	// First - Saves options from input
	if ( isset( $_POST['variation_price'] ) || isset( $_POST["apply_to_current"] ) ) {

		$term_prices = get_option( 'term_prices' );

		$term_prices[$term_id]["price"] = $_POST["variation_price"];
		$term_prices[$term_id]["checked"] = (isset( $_POST["apply_to_current"] )) ? "checked" : "unchecked";

		update_option( 'term_prices', $term_prices );
	}

	// Second - If box was checked, let's then check whether or not it was flat, differential, or percentile, then let's apply the pricing to every product appropriately
	if ( isset( $_POST["apply_to_current"] ) ) {

		//Check for flat, percentile or differential
		$var_price_type = '';

		if ( flat_price( $_POST["variation_price"] ) )
			$var_price_type = 'flat';
		elseif ( differential_price( $_POST["variation_price"] ) )
			$var_price_type = 'differential';
		elseif ( percentile_price( $_POST["variation_price"] ) )
			$var_price_type = 'percentile';

		//Now, find all products with this term_id, update their pricing structure (terms returned include only parents at this point, we'll grab relevent children soon)
		$products_to_mod = get_objects_in_term( $term_id, "wpsc-variation" );
		$product_parents = array( );

		foreach ( (array)$products_to_mod as $get_parent ) {

			$post = get_post( $get_parent );

			if ( !$post->post_parent )
				$product_parents[] = $post->ID;
		}

		//Now that we have all parent IDs with this term, we can get the children (only the ones that are also in $products_to_mod, we don't want to apply pricing to ALL kids)

		foreach ( $product_parents as $parent ) {
			$args = array(
				'post_parent' => $parent,
				'post_type' => 'wpsc-product'
			);
			$children = get_children( $args, ARRAY_A );

			foreach ( $children as $childrens ) {
				$parent = $childrens["post_parent"];
				$children_ids[$parent][] = $childrens["ID"];
				$children_ids[$parent] = array_intersect( $children_ids[$parent], $products_to_mod );
			}
		}

		//Got the right kids, let's grab their parent pricing and modify their pricing based on var_price_type

		foreach ( (array)$children_ids as $parents => $kids ) {

			$kids = array_values( $kids );

			foreach ( $kids as $kiddos ) {
				$price = wpsc_determine_variation_price( $kiddos );
				update_product_meta( $kiddos, 'price', $price );
			}
		}
	}
}
add_action( 'edited_wpsc-variation', 'save_term_prices' );
add_action( 'created_wpsc-variation', 'save_term_prices' );

function wpsc_delete_variations( $postid ) {
	$post = get_post( $postid );
	if ( $post->post_type != 'wpsc-product' || $post->post_parent != 0 )
		return;
	$variations = get_posts( array(
		'post_type' => 'wpsc-product',
		'post_parent' => $postid,
		'post_status' => 'any',
		'numberposts' => -1,
	) );

	if ( ! empty( $variations ) )
		foreach ( $variations as $variation ) {
			wp_delete_post( $variation->ID, true );
		}
}
add_action( 'delete_post', 'wpsc_delete_variations' );

/*
Save the variations that have been
created on the products page
*/
function wpsc_add_variant_from_products_page() {
/* This is the parent term / vartiation set we will save this first */
	$variation_set_term = $_POST['variation'];
	$variants[0] = $_POST['variant'];

	/*
	variants can be coma separated so we check for
	these and put them into an array
	*/
	$variants = explode( ',', $variants[0] );
	wp_insert_term( $variation_set_term, 'wpsc-variation', $args = array() );

	/* now get the parent id so we can save all the kids*/
	$parent_term = term_exists( $variation_set_term, 'wpsc-variation' ); // array is returned if taxonomy is given
	$parent_term_id = $parent_term['term_id']; // get numeric term id
	/* if we have a parent and some kids then we will add kids now */
	if( !empty($parent_term_id) && !empty($variants) ){
		foreach( $variants as $variant ){
			wp_insert_term( $variant, 'wpsc-variation', $args = array('parent' => $parent_term_id) );
			/* want to get out the id so we can return it with the response */
			$varient_term = term_exists( $variant, 'wpsc-variation', $parent_term_id );
			$variant_term_id[] = $varient_term['term_id']; // get numeric term id
		}
	}
	$response = new WP_Ajax_Response;
	$response -> add( array(
		'data' 			=> 'success',
		'supplemental' 	=> array(
		'variant_id' 	=> implode(",",$variant_term_id),
		),
	)
	);
	$response -> send();
	exit();
}

add_action( 'wp_ajax_wpsc_add_variant_from_products_page', 'wpsc_add_variant_from_products_page' );

function wpsc_delete_variant_from_products_page(){
	$variant_id = $_POST['variant_id'];
	/* should never be empty but best to check first*/
	if (!empty($variant_id))
		wp_delete_term( $variant_id, 'wpsc-variation');
	exit();
}
add_action( 'wp_ajax_wpsc_delete_variant_from_products_page', 'wpsc_delete_variant_from_products_page' );
