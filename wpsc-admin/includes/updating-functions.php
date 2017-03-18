<?php
/**
 * WP eCommerce database updating functions
 *
 * @package wp-e-commerce
 * @since 3.8
 */

class WPSC_Update {
	private static $instance;
	private $timeout;
	private $script_start;
	private $stages;

	public static function get_instance() {
		if ( empty( self::$instance ) )
			self::$instance = new WPSC_Update();

		return self::$instance;
	}

	private function __construct() {
		$this->timeout = ini_get( 'max_execution_time' );
		$this->script_start = time();

		if ( ! $this->stages = get_transient( 'wpsc_update_progress' ) ) {
			$this->stages = array();
		}
	}

	public function clean_up() {
		delete_transient( 'wpsc_update_progress' );
		delete_transient( 'wpsc_update_product_offset' );
		delete_transient( 'wpsc_update_variation_comb_offset' );
		delete_transient( 'wpsc_update_current_product' );
		delete_transient( 'wpsc_update_current_child_products' );
	}

	public function check_timeout() {
		$safety = 2; // refresh page within 2 seconds of PHP max execution time limit
		$wiggle_room = $this->timeout - $safety;

		$terminate = time() - $this->script_start >= $wiggle_room;

		if ( $terminate ) {
			do_action( 'wpsc_update_timeout_terminate' );
			$location = remove_query_arg( array( 'start_over', 'eta', 'current_percent' ) );
			$location = add_query_arg( 'run_updates', 1, $location );
			$location = esc_url_raw( apply_filters( 'wpsc_update_terminate_location', $location ) );
			?>
			<script type="text/javascript">
				location.href = "<?php echo $location; ?>"
			</script>
			<?php
			exit;
		}
	}

	public function run( $function, $message = '' ) {

		if ( $message ) {
			echo "<p>{$message}</p>";
		}

		if ( empty( $this->stages[$function] ) ) {
			call_user_func( 'wpsc_' . $function );
			$this->stages[ $function ] = true;
			set_transient( 'wpsc_update_progress', $this->stages, WEEK_IN_SECONDS );
		}
	}
}

class WPSC_Update_Progress {
	private $milestone;
	private $start;
	private $count;
	private $current_percent = 0;
	private $total;
	private $eta;
	private $i;

	public function __construct( $total ) {
		$this->total = $total;
		$this->milestone = $this->start = time();
		if ( ! empty( $_REQUEST['current_percent'] ) )
			$this->current_percent = (int) $_REQUEST['current_percent'];

		add_filter( 'wpsc_update_terminate_location', array( $this, 'filter_terminate_location' ) );

		echo '<div class="wpsc-progress-bar">';
		if ( ! empty( $_REQUEST['start_over'] ) )
			return;

		if ( isset( $_REQUEST['current_percent'] ) ) {
			echo "<div class='block' style='width:" . absint( $_REQUEST['current_percent'] ) . "%;'>&nbsp;</div>";
		}

		if ( isset( $_REQUEST['eta'] ) ) {
			$this->eta = (int) $_REQUEST['eta'];
			$this->print_eta();
		}

		if ( isset( $_REQUEST['i'] ) )
			echo "<span>" . absint( $_REQUEST['i'] ) . "/{$this->total}</span>";
	}

	public function filter_terminate_location( $location ) {
		$location = add_query_arg( array(
			'current_percent' => $this->current_percent,
			'i' => $this->i,
		), $location );
		if ( $this->eta !== null )
			$location = add_query_arg( 'eta', $this->eta, $location );
		else
			$location = remove_query_arg( 'eta', $location );
		return esc_url_raw( $location );
	}

	private function print_eta() {
		echo '<div class="eta">';
		_e( 'Estimated time left:', 'wp-e-commerce' );
		echo ' ';
		if ( $this->eta == 0 )
			_e( 'Under a minute', 'wp-e-commerce' );
		else
			printf( _n( '%d minute', '%d minutes', $this->eta, 'wp-e-commerce' ), $this->eta );
		echo '</div>';
	}

	public function update( $i ) {
		if ( empty( $this->count ) )
			$this->count = $i;

		$this->i = $i;
		$now = time();
		$percent = min( floor( $i * 100 / $this->total ), 100 );

		if ( $percent != $this->current_percent ) {
			echo "<div class='block' style='width:{$percent}%;'>&nbsp;</div>";
			$this->current_percent = $percent;
		}

		echo "<span>{$i}/{$this->total}</span>";

		if ( $now - $this->milestone >= 5 ) {
			$processed = $i - $this->count + 1;
			$this->eta = floor( ( $this->total - $i ) * ( $now - $this->start ) / ( $processed * 60 ) );
			$this->print_eta();
			$this->milestone = $now;
		}

		if ( $percent == 100 ) {
			remove_filter( 'wpsc_update_terminate_location', array( $this, 'filter_terminate_location' ) );
			echo '<div class="eta">' . _x( 'Done!', 'Update routine completed', 'wp-e-commerce' ) . '</div>';
			echo '</div>';
		}
	}
}

function wpsc_update_step( $i, $total ) {
	static $current;
	static $milestone;
	static $start;
	static $count;
	static $current_percent;

	$now = time();

	if ( $current != $total ) {
		$current = $total;
		$milestone = $start = $now;
		$count = $i;
	}

	$percent = min( round( $i * 100 / $total, 2 ), 100 );

	if ( floor( $percent ) != $current_percent ) {
		echo "<div class='block' style='width:{$percent}%;'>&nbsp;</div>";
		$current_percent = floor( $percent );
	}

	if ( $now - $milestone == 5 ) {
		$processed = $i - $count + 1;
		$eta = floor( ( $total - $i ) * ( $now - $start ) / ( $processed * 60 ) );
		echo '<div class="eta">';
		_e( 'Estimated time left:', 'wp-e-commerce' );
		echo ' ';
		if ( $eta == 0 )
			_e( 'Under a minute', 'wp-e-commerce' );
		else
			printf( _n( '%d minute', '%d minutes', $eta, 'wp-e-commerce' ), $eta );
		echo '</div>';
		$milestone = $now;
	}
}

function wpsc_update_purchase_logs() {
	global $wpdb;

	// bump all purchase log status
	$wpdb->query( "UPDATE " . WPSC_TABLE_PURCHASE_LOGS . " SET processed = processed + 1, plugin_version = '" . WPSC_VERSION . "' WHERE plugin_version IN ('3.6', '3.7') " );
}

/**
 * wpsc_convert_category_groups function.
 *
 * @access public
 * @return void
 */
function wpsc_convert_category_groups() {
	global $wpdb, $user_ID;
	$wpsc_update = WPSC_Update::get_instance();

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
		$wpsc_update->check_timeout();

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

		if(! isset( $new_category ) || !is_wp_error($new_category))
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
		$categorisation = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `".WPSC_TABLE_PRODUCT_CATEGORIES."` WHERE `active` IN ('1') AND `group_id` IN (%d) AND `category_parent` IN (%d)", $group_id, $old_parent_category ) );
	} else {
		$categorisation = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `".WPSC_TABLE_PRODUCT_CATEGORIES."` WHERE `active` IN ('1') AND `group_id` IN (%d) AND `category_parent` IN (0)", $group_id ) );
	}
	$wpsc_update = WPSC_Update::get_instance();

	if($categorisation > 0) {

		foreach((array)$categorisation as $category) {
			$wpsc_update->check_timeout();
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
	$wpsc_update = WPSC_Update::get_instance();

	foreach((array)$variation_sets as $variation_set) {
		$wpsc_update->check_timeout();
		$variation_set_id = wpsc_get_meta($variation_set->id, 'variation_set_id', 'wpsc_variation_set');

		if(!is_numeric($variation_set_id) || ( $variation_set_id < 1)) {
			$slug = sanitize_title( $variation_set->name );
			$dummy_term = (object) array(
				'taxonomy' => 'wpsc-variation',
				'parent'   => 0,
			);
			$slug = wp_unique_term_slug( $slug, $dummy_term );
			$new_variation_set = wp_insert_term( $variation_set->name, 'wpsc-variation',array('parent' => 0, 'slug' => $slug ) );
			if( ! is_wp_error( $new_variation_set ) )
				$variation_set_id = $new_variation_set['term_id'];
		}

		if( ! empty( $variation_set_id ) && is_numeric($variation_set_id)) {
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
	$wpsc_update = WPSC_Update::get_instance();
	if ( ! empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	if ( ! empty($wpdb->collate) )
		$charset_collate .= " COLLATE $wpdb->collate";

	if ( ! $offset = get_transient( 'wpsc_update_product_offset' ) )
		$offset = 0;
	$limit = 90;
	$sql = "
		SELECT * FROM " . WPSC_TABLE_PRODUCT_LIST . "
		WHERE active = '1'
		LIMIT %d, %d
	";
	$post_created = get_transient( 'wpsc_update_current_product' );
	$total = $wpdb->get_var( "SELECT COUNT(*) FROM " . WPSC_TABLE_PRODUCT_LIST . " WHERE active='1'" );
	$progress = new WPSC_Update_Progress( $total );

	while (true) {
		$product_data = $wpdb->get_results( $wpdb->prepare( $sql, $offset, $limit ), ARRAY_A );
		$i = $offset;

		if ( empty( $product_data ) )
			break;

		foreach((array)$product_data as $product) {
			$wpsc_update->check_timeout( '</div>' );

			$post_id = 0;

			// if a product is in the middle of being converted
			if ( $post_created && ! empty( $post_created['original_id'] ) && $post_created['original_id'] == $product['id'] )
				$post_id = $post_created['post_id'];

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
					'post_name' => $product['name']
				);

				$product['order'] = $wpdb->get_var( $wpdb->prepare( "
					SELECT `order` FROM " . WPSC_TABLE_PRODUCT_ORDER . "
					WHERE product_id = %d
				", $product['id'] ) );

				$product_post_values['menu_order'] = $product['order'];

				$post_id = wp_insert_post($product_post_values);
				$post_created = array(
					'original_id' => $product['id'],
					'post_id' => $post_id,
				);
				set_transient( 'wpsc_update_current_product', $post_created, 604800 );
			}
			$product_meta_sql = $wpdb->prepare( "
				SELECT 	IF( ( `custom` != 1	),
						CONCAT( '_wpsc_', `meta_key` ) ,
					`meta_key`
					) AS `meta_key`,
					`meta_value`
				FROM `".WPSC_TABLE_PRODUCTMETA."`
				WHERE `product_id` = %d
				AND `meta_value` != ''", $product['id'] );

			$product_meta = $wpdb->get_results( $product_meta_sql, ARRAY_A );

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
			$post_data['_wpsc_special_price'] = $post_data['_wpsc_price'] - (float)$product['special_price']; // special price get stored in a weird way in 3.7.x
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
				$post_data['_wpsc_product_metadata']['notify_when_none_left'] = (int)(bool)$post_data['meta']['_wpsc_product_metadata']['notify_when_none_left'];
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
			$image_data_sql = $wpdb->prepare( "SELECT * FROM `".WPSC_TABLE_PRODUCT_IMAGES."` WHERE `product_id` = %d ORDER BY `image_order` ASC", $product['id'] );
			$image_data = $wpdb->get_results( $image_data_sql, ARRAY_A );
			foreach((array)$image_data as $image_row) {
				$wpsc_update->check_timeout( '</div>' );
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
				$attachment_id_sql = $wpdb->prepare( "SELECT `ID` FROM `{$wpdb->posts}` WHERE `post_title` = %s AND `post_parent` = %d LIMIT 1", $image_name, $post_id );
				$attachment_id = (int)$wpdb->get_var( $attachment_id_sql );

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

				update_attached_file( $attachment_id, $new_path );
				wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $new_path ) );
			}
			$i ++;
			$progress->update( $i );
			set_transient( 'wpsc_update_product_offset', $i, 604800 );
		}

		$offset += $limit;
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
	$wpsc_update = WPSC_Update::get_instance();
	remove_filter( 'get_terms', 'wpsc_get_terms_category_sort_filter' );
	if ( ! $offset = get_transient( 'wpsc_update_variation_comb_offset' ) )
		$offset = 0;
	$limit = 150;
	wp_defer_term_counting( true );
	$sql = "SELECT * FROM {$wpdb->posts} WHERE post_type = 'wpsc-product' AND post_parent = 0 LIMIT %d, %d";

	$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wpsc-product' AND post_parent = 0" );
	$progress = new WPSC_Update_Progress( $total );

	while ( true ) {
		// get the posts
		// I use a direct SQL query here because the get_posts function sometimes does not function for a reason that is not clear.
		$posts = $wpdb->get_results( $wpdb->prepare( $sql, $offset, $limit ) );
		$i = $offset;
		if ( empty( $posts ) )
			break;

		foreach((array)$posts as $post) {
			if ( ! $child_products = get_transient( 'wpsc_update_current_child_products' ) )
				$child_products = array();

			$wpsc_update->check_timeout();
			$base_product_terms = array();
			//create a post template
			$child_product_template = array(
				'post_author' => $user_ID,
				'post_content' => $post->post_content,
				'post_excerpt' => $post->post_excerpt,
				'post_title' => $post->post_title,
				'post_status' => 'inherit',
				'post_type' => "wpsc-product",
				'post_name' => $post->post_title,
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
				$i++;
				$progress->update( $i );
				set_transient( 'wpsc_update_variation_comb_offset', $i, 604800 );
				continue;
			}

			$variation_set_id_sql = "SELECT meta_value FROM " . WPSC_TABLE_META . " WHERE object_type='wpsc_variation_set' AND object_id IN (" . implode( ',', $variation_set_associations ) . ") AND meta_key = 'variation_set_id'";

			$variation_set_terms = $wpdb->get_col( $variation_set_id_sql );

			$variation_associations_sql = "SELECT meta_value FROM " . WPSC_TABLE_META . " WHERE object_type='wpsc_variation' AND object_id IN (" . implode( ',', $variation_associations ) . ") AND meta_key = 'variation_id'";

			$variation_associations_terms = $wpdb->get_col( $variation_associations_sql );

			$base_product_terms = array_merge( $base_product_terms, $variation_set_terms, $variation_associations_terms );

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
				$wpsc_update->check_timeout();
				// initialize the requisite arrays to empty
				$variation_ids = array();
				$term_data = array(
					'ids' => array(),
					'slugs' => array(),
					'names' => array(),
				);
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
					$key = md5( $post->ID . ':' . count( $term_data['ids'] ) . ':' . implode(',', $term_data['ids'] ) );
					$child_product_id = false;

					if ( ! empty( $child_products[$key] ) )
						$child_product_id = $child_products[$key];

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

					// Parts of the code (eg wpsc_product_variation_price_from() make the assumption that these meta keys exist
 					$post_data['_wpsc_special_price'] = 0;
 					$post_data['_wpsc_sku'] = '';

					$already_exists = true;

					if ( ! empty( $selected_post ) && $selected_post->ID != $child_product_id ) {
						$child_product_id = $selected_post->ID;
					} elseif ( empty( $child_product_id ) ) {
						$child_product_id = wp_insert_post( $product_values );
						$already_exists = false;
					}

					if($child_product_id > 0) {

						foreach($post_data as $meta_key => $meta_value) {
							// prefix all meta keys with _wpsc_
							update_post_meta($child_product_id, $meta_key, $meta_value);
						}


						wp_set_object_terms($child_product_id, $term_data['slugs'], 'wpsc-variation');
						if ( ! $already_exists ) {
							$child_products[$key] = $child_product_id;
							set_transient( 'wpsc_update_current_child_products', $child_products, 604800 );
						}
					}

					unset($term_data);
				}

			}
			$i++;
			$progress->update( $i );
			set_transient( 'wpsc_update_variation_comb_offset', $i, 604800 );
			delete_transient( 'wpsc_update_current_child_products' );
		}

		$offset += $limit;

	}
	delete_option("wpsc-variation_children");
	_get_term_hierarchy('wpsc-variation');
	delete_option("wpsc_product_category_children");
	_get_term_hierarchy('wpsc_product_category');
}

function wpsc_update_files() {
	global $wpdb, $user_ID;
	$product_files = $wpdb->get_results("SELECT * FROM ".WPSC_TABLE_PRODUCT_FILES."");
	$wpsc_update = WPSC_Update::get_instance();

	foreach($product_files as $product_file) {
		$wpsc_update->check_timeout();
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
		echo 'Could not run query: ' . $wpdb->last_error;
		exit;
	}
	foreach($result as $row_key=>$value) {
		$has_taxes = ($value["Field"] == "wpec_taxes_total" || $value["Field"] == "wpec_taxes_rate") ? true: false;
	}
	if (!$has_taxes) {
		$add_fields = $wpdb->query( "ALTER TABLE ".WPSC_TABLE_PURCHASE_LOGS." ADD wpec_taxes_total decimal(11,2)" );
		$add_fields = $wpdb->query( "ALTER TABLE ".WPSC_TABLE_PURCHASE_LOGS." ADD wpec_taxes_rate decimal(11,2)" );
	}
}
/*
 * The Old Get Product Meta for 3.7 Tables used in converting Products to Posts
 */

function old_get_product_meta($product_id, $key, $single = false) {
	global $wpdb, $post_meta_cache, $blog_id;
	$product_id = (int)$product_id;
	$meta_values = false;
	if($product_id > 0) {
		$meta_id = $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `".WPSC_TABLE_PRODUCTMETA."` WHERE `meta_key` IN(%s) AND `product_id` = %d LIMIT 1", $key, $product_id ) );
		//exit($meta_id);
		if(is_numeric($meta_id) && ($meta_id > 0)) {
			if($single != false) {
				$meta_values = maybe_unserialize($wpdb->get_var("SELECT `meta_value` FROM `".WPSC_TABLE_PRODUCTMETA."` WHERE `meta_key` IN('$key') AND `product_id` = '$product_id' LIMIT 1"));
			} else {
				$meta_values = $wpdb->get_col( $wpdb->prepare( "SELECT `meta_value` FROM `".WPSC_TABLE_PRODUCTMETA."` WHERE `meta_key` IN(%s) AND `product_id` = %d", $key, $product_id ) );
				$meta_values = array_map('maybe_unserialize', $meta_values);
			}
		}
	}
	if (is_array($meta_values) && (count($meta_values) == 1)) {
		return array_pop($meta_values);
	} else {
		return $meta_values;
	}
}
?>
