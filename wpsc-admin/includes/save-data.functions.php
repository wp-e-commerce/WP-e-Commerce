<?php
/**
 * This file used for adding fields to the products category taxonomy page and saving those values correctly :)
 *
 * @package wp-e-commerce
 * @since 3.8
 * @todo UI needs a lot of loving - lots of padding issues, if we have these boxes, they should be sortable, closable, hidable, etc.
 */
function wpsc_ajax_set_variation_order(){
	global $wpdb;
	$sort_order = $_POST['sort_order'];
	$parent_id  = $_POST['parent_id'];

	$result = true;
	foreach( $sort_order as $key=>$value ){
		if ( empty( $value ) )
			continue;

		$value = preg_replace( '/[^0-9]/', '', $value );

		if( ! wpsc_update_meta( $value, 'sort_order', $key, 'wpsc_variation' ) )
			$result = false;
	}
}

/**
 * WP eCommerce edit and add product category page functions
 *
 * These are the main WPSC Admin functions
 *
 * @package wp-e-commerce
 * @since 3.7
 */

function wpsc_ajax_set_category_order(){
	global $wpdb;
	$sort_order = $_POST['sort_order'];
	$parent_id  = $_POST['parent_id'];

	$result = true;
	foreach ( $sort_order as $key=>$value ){
		if ( empty( $value ) )
			continue;

		$value = preg_replace( '/[^0-9]/', '', $value );

		if ( ! wpsc_update_meta( $value, 'sort_order', $key, 'wpsc_category' ) )
			$result = false;
	}
}

add_filter( 'manage_edit-wpsc_product_category_columns', 'wpsc_custom_category_columns' );
add_filter( 'manage_wpsc_product_category_custom_column', 'wpsc_custom_category_column_data', 10, 3);
add_action( 'wpsc_product_category_add_form_fields', 'wpsc_admin_category_forms_add' ); // After left-col
add_action( 'wpsc_product_category_edit_form_fields', 'wpsc_admin_category_forms_edit' ); // After left-col
add_action( 'created_wpsc_product_category', 'wpsc_save_category_set', 10 , 2 ); //After created
add_action( 'edited_wpsc_product_category', 'wpsc_save_category_set', 10 , 2 ); //After saved

/**
 * wpsc_custom_category_columns
 * Adds images column to category column.
 * @internal Don't feel handle column is necessary, but you would add it here if you wanted to
 * @param (array) columns | Array of columns assigned to this taxonomy
 * @return (array) columns | Modified array of columns
 */

function wpsc_custom_category_columns( $columns ) {
	// Doing it this funny way to ensure that image stays in far left, even if other items are added via plugin.
	unset( $columns["cb"] );

	$custom_array = array(
		'cb' => '<input type="checkbox" />',
		'image' => __( 'Image', 'wpsc' )
	);

	$columns = array_merge( $custom_array, $columns );

	return $columns;
}
/**
 * wpsc_custom_category_column_data
 * Adds images to the custom category column
 * @param (array) column_name | column name
 * @return nada
 */

function wpsc_custom_category_column_data( $string, $column_name, $term_id ) {
   global $wpdb;

  $image = wpsc_get_categorymeta( $term_id, 'image' );
  $name = get_term_by( 'id', $term_id, 'wpsc_product_category' );
  $name = $name->name;

  if ( ! empty( $image ) )
	  $image = "<img src='" . WPSC_CATEGORY_URL . $image . "' title='" . esc_attr( $name ) . "' alt='" . esc_attr( $name ) . "' width='30' height='30' />";
   else
	  $image = "<img src='" . WPSC_CORE_IMAGES_URL . "/no-image-uploaded.gif' title='" . esc_attr( $name ) . "' alt='" . esc_attr( $name ) . "' width='30' height='30' />";

	return $image;

}

/**
 * wpsc_admin_get_category_array
 * Recursively step through the categories and return it in a clean multi demensional array
 * for use in other list functions
 * @param int $parent_id
 */
function wpsc_admin_get_category_array( $parent_id = null ){
	global $wpdb;

	$orderedList = array();

	if ( ! isset( $parent_id ) )
		$parent_id = 0;

	$category_list = get_terms( 'wpsc_product_category', 'hide_empty=0&parent=' . $parent_id );

	if ( ! is_array( $category_list ) ) {
		return false;
	}

	foreach ( $category_list as $category ) {
		$category_order = wpsc_get_categorymeta( $category->term_id, 'order' );
		$category_image = wpsc_get_categorymeta( $category->term_id, 'image' );

		if ( ! isset( $category_order ) || $category_order == 0 )
			$category_order = ( count( $orderedList ) + 1 );
		print "<!-- setting category Order number to " . $category_order . "-->";
		$orderedList[$category_order]['id'] = $category->term_id;
		$orderedList[$category_order]['name'] = $category->name;
		$orderedList[$category_order]['image'] = $category_image;
		$orderedList[$category_order]['parent_id'] = $parent_id;
		$orderedList[$category_order]['children'] = wpsc_admin_get_category_array( $category->term_id );
	}

	ksort( $orderedList );
	return $orderedList;
}

/**
 * wpsc_admin_category_group_list, prints the left hand side of the add categories page
 * nothing returned
 */
function wpsc_admin_category_forms_add() {
	global $wpdb;
	$category_value_count = 0;
	$display_type = isset( $category['display_type'] ) ? $category['display_type'] : '';
	?>

	<h3><?php esc_html_e('Advanced Store Settings', 'wpsc'); ?></h3>
	<h4><?php esc_html_e('Presentation Settings', 'wpsc'); ?></h4>
	<p class='description'><?php esc_html_e( 'These settings override the general presentation settings found in Settings &gt; Store &gt; Presentation.', 'wpsc' ); ?></p>
	<div class="form-field">
		<label for='image'><?php esc_html_e( 'Category Image' , 'wpsc' ); ?></label>
		<input type='file' name='image' value='' />
	</div>
	<div class="form-field">
		<label for='display_type'><?php esc_html_e( 'Product Display', 'wpsc' ); ?></label>
		<select name='display_type'>
			<option value='default'<?php checked( $display_type, 'default' ); ?>><?php esc_html_e('Default View', 'wpsc'); ?></option>
			<option value='list'<?php disabled( _wpsc_is_display_type_supported( 'list' ), false ); ?><?php checked( $display_type, 'list' ); ?>><?php esc_html_e('List View', 'wpsc'); ?></option>
			<option value='grid'<?php disabled( _wpsc_is_display_type_supported( 'grid' ), false ); ?><?php checked( $display_type, 'grid' ); ?>><?php esc_html_e('Grid View', 'wpsc'); ?></option>
		</select>
	</div>
	<?php if ( function_exists( "getimagesize" ) ) : ?>
		<div class="form-field">
			<?php esc_html_e( 'Thumbnail&nbsp;Size', 'wpsc' ); ?>
			<table>
				<tr>
					<th>
						<label for="image_width"><?php esc_html_e( 'Width', 'wpsc' ); ?></label>
					</th>
					<td>
						<input type='text' value='<?php if (isset($category['image_width'])) echo $category['image_width']; ?>' name='image_width' size='6' style="width: 6em;" />
					</td>
				</tr>
				<tr>
					<th>
						<label for="image_height"><?php esc_html_e( 'Height', 'wpsc' ); ?></label>
					</th>
					<td>
						<input type='text' value='<?php if (isset($category['image_height'])) echo $category['image_height']; ?>' name='image_height' size='6' style="width: 6em;" />
					</td>
				</tr>
			</table>
		</div>
	<?php endif;?>

	<!-- START OF TARGET MARKET SELECTION -->
	<?php
		$category_id = '';
		if ( isset( $_GET["tag_ID"] ) )
			$category_id = $_GET["tag_ID"];

		$countrylist = $wpdb->get_results("SELECT id,country,visible FROM `" . WPSC_TABLE_CURRENCY_LIST . "` ORDER BY country ASC ", ARRAY_A);
		$selectedCountries = wpsc_get_meta( $category_id, 'target_market', 'wpsc_category' );
	?>
	<h4><?php esc_html_e( 'Restrict to Target Markets', 'wpsc' )?></h4>
	<div class='form-field'>
		<?php if( @extension_loaded( 'suhosin' ) ) : ?>
			<em><?php esc_html__( "The Target Markets feature has been disabled because you have the Suhosin PHP extension installed on this server. If you need to use the Target Markets feature then disable the suhosin extension, if you can not do this, you will need to contact your hosting provider.", 'wpsc' ); ?></em>
		<?php else: ?>
			<div class='multiple-select-container'>
				<span><?php esc_html_e( 'Select', 'wpsc' ); ?> <a href='' class='wpsc_select_all'><?php esc_html_e( 'All', 'wpsc' ); ?></a>&nbsp; <a href='' class='wpsc_select_none'><?php esc_html_e( 'None', 'wpsc' ); ?></a></span><br />
				<div id='resizeable' class='ui-widget-content multiple-select'>
					<?php foreach( $countrylist as $country ): ?>
						<?php if ( in_array( $country['id'], (array)$selectedCountries ) ): ?>
							<input type='checkbox' name='countrylist2[]' id='countrylist2-<?php echo $country['id']; ?>' value='<?php echo $country['id']; ?>' checked='<?php echo $country['visible']; ?>' />
							<label for="countrylist2-<?php echo $country['id']; ?>"><?php esc_html_e( $country['country'] ); ?></label><br />
						<?php else: ?>
							<input type='checkbox' name='countrylist2[]' id='countrylist2-<?php echo $country['id']; ?>' value='<?php echo $country['id']; ?>'  />
							<label for="countrylist2-<?php echo $country['id']; ?>"><?php esc_html_e( $country['country'] ); ?></label><br />
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</div>
			<span class='wpscsmall description'><?php esc_html_e( 'Select the markets you are selling this category to.', 'wpsc' ); ?><span>
		<?php endif; ?>
	</div>

	<!-- Checkout settings -->
	<h4><?php esc_html_e( 'Checkout Settings', 'wpsc' ); ?></h4>

	<?php
		if ( ! isset( $category['term_id'] ) ) $category['term_id'] = '';
			$used_additonal_form_set = wpsc_get_categorymeta( $category['term_id'], 'use_additional_form_set' );
	?>
	<div class='form-field'>
		<label for="use_additional_form_set"><?php esc_html_e( 'Category requires additional checkout form fields', 'wpsc' ); ?></label>
		<select name='use_additional_form_set'>
			<option value=''><?php esc_html_e( 'None', 'wpsc' ); ?></option>
			<?php
				$checkout_sets = get_option( 'wpsc_checkout_form_sets' );
				unset( $checkout_sets[0] );

				foreach ( (array)$checkout_sets as $key => $value ) {
					$selected_state = "";
					if ( $used_additonal_form_set == $key )
						$selected_state = "selected='selected'";
					?>
					<option <?php echo $selected_state; ?> value='<?php echo $key; ?>'><?php echo esc_html( $value ); ?></option>
					<?php
				}
			?>
		</select>
	</div>

	<?php $uses_billing_address = (bool)wpsc_get_categorymeta( $category['term_id'], 'uses_billing_address' ); ?>
	<div>
		<label><?php esc_html_e( 'Address to calculate shipping with', 'wpsc' ); ?></label>
		<label><input type='radio' value='1' name='uses_billing_address' <?php echo ( ( $uses_billing_address == true ) ? "checked='checked'" : "" ); ?> /> <?php esc_html_e( 'Billing Address', 'wpsc' ); ?></label>
		<label><input type='radio' value='0' name='uses_billing_address' <?php echo ( ( $uses_billing_address != true ) ? "checked='checked'" : "" ); ?> /> <?php esc_html_e( 'Default Setting', 'wpsc' ); ?></label>
		<p class='description'><?php esc_html_e( 'Products in this category will use the address specified to calculate shipping costs.', 'wpsc' ); ?></p>
	</div>

	<table class="category_forms">
		<tr>

		</tr>
	</table>
	<?php
}

/**
 * Check whether a display type (such as grid, list) is supported.
 *
 * @since  3.8.9
 * @access private
 * @param  string $display_type Display type
 * @return bool                 Return true if display type is supported.
 */
function _wpsc_is_display_type_supported( $display_type ) {
	$callback = 'product_display_' . $display_type;
	return function_exists( $callback );
}

function wpsc_admin_category_forms_edit() {
	global $wpdb;

	$category_value_count = 0;
	$category_name = '';
	$category = array();

	$category_id = absint( $_REQUEST["tag_ID"] );
	$category = get_term( $category_id, 'wpsc_product_category', ARRAY_A );
	$category['nice-name']               = wpsc_get_categorymeta( $category['term_id'], 'nice-name' );
	$category['description']             = wpsc_get_categorymeta( $category['term_id'], 'description' );
	$category['image']                   = wpsc_get_categorymeta( $category['term_id'], 'image' );
	$category['fee']                     = wpsc_get_categorymeta( $category['term_id'], 'fee' );
	$category['active']                  = wpsc_get_categorymeta( $category['term_id'], 'active' );
	$category['order']                   = wpsc_get_categorymeta( $category['term_id'], 'order' );
	$category['display_type']            = wpsc_get_categorymeta( $category['term_id'], 'display_type' );
	$category['image_height']            = wpsc_get_categorymeta( $category['term_id'], 'image_height' );
	$category['image_width']             = wpsc_get_categorymeta( $category['term_id'], 'image_width' );
	$category['use_additional_form_set'] = wpsc_get_categorymeta( $category['term_id'], 'use_additional_form_set' );

	?>

	<tr>
		<td colspan="2">
			<h3><?php esc_html_e( 'Advanced Store Settings', 'wpsc' ); ?></h3>
			<h4><?php esc_html_e( 'Shortcodes and Template Tags', 'wpsc' ); ?></h4>
			<p class='description'><?php esc_html_e( 'These settings override the general presentation settings found in Settings &gt; Store &gt; Presentation.', 'wpsc' ); ?></p>
		</td>
	</tr>


	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="display_type"><?php esc_html_e( 'Catalog View', 'wpsc' ); ?></label>
		</th>
		<td>
			<?php
				$display_type = isset( $category['display_type'] ) ? $category['display_type'] : '';
			?>
			<select name='display_type'>
				<option value='default'<?php checked( $display_type, 'default' ); ?>><?php esc_html_e( 'Default View', 'wpsc' ); ?></option>
				<option value='list'<?php disabled( _wpsc_is_display_type_supported( 'list' ), false ); ?><?php checked( $display_type, 'list' ); ?>><?php esc_html_e('List View', 'wpsc'); ?></option>
				<option value='grid' <?php disabled( _wpsc_is_display_type_supported( 'grid' ), false ); ?><?php checked( $display_type, 'grid' ); ?>><?php esc_html_e( 'Grid View', 'wpsc' ); ?></option>
			</select><br />
		</td>
	</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="image"><?php esc_html_e( 'Category Image', 'wpsc' ); ?></label>
			</th>
			<td>
				<input type='file' name='image' value='' /><br />
				<label><input type='checkbox' name='deleteimage' class="wpsc_cat_box" value='1' /><?php esc_html_e( 'Delete Image', 'wpsc' ); ?></label><br/>
				<span class="description"><?php esc_html_e( 'You can set an image for the category here.  If one exists, check the box to delete.', 'wpsc' ); ?></span>
			</td>
	</tr>
	<?php if ( function_exists( "getimagesize" ) ) : ?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="image"><?php esc_html_e( 'Thumbnail Size', 'wpsc' ); ?></label>
			</th>
			<td>
				<?php esc_html_e( 'Width', 'wpsc' ); ?> <input type='text' class="wpsc_cat_image_size" value='<?php if ( isset( $category['image_width'] ) ) echo $category['image_width']; ?>' name='image_width' size='6' />
				<?php esc_html_e( 'Height', 'wpsc' ); ?> <input type='text' class="wpsc_cat_image_size" value='<?php if ( isset( $category['image_height'] ) ) echo $category['image_height']; ?>' name='image_height' size='6' /><br/>
			</td>
		</tr>
	<?php endif; // 'getimagesize' condition ?>


	<tr>
		<td colspan="2"><h4><?php esc_html_e( 'Shortcodes and Template Tags', 'wpsc' ); ?></h4></td>
	</tr>

	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="image"><?php esc_html_e( 'Display Category Shortcode', 'wpsc' ); ?></label>
		</th>
		<td>
			<code>[wpsc_products category_url_name='<?php echo $category["slug"]; ?>']</code><br />
			<span class="description"><?php esc_html_e( 'Shortcodes are used to display a particular category or group within any WordPress page or post.', 'wpsc' ); ?></span>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="image"><?php esc_html_e( 'Display Category Template Tag', 'wpsc' ); ?></label>
		</th>
		<td>
			<code>&lt;?php echo wpsc_display_products_page( array( 'category_url_name' => '<?php echo $category["slug"]; ?>' ) ); ?&gt;</code><br />
			<span class="description"><?php esc_html_e( 'Template tags are used to display a particular category or group within your theme / template.', 'wpsc' ); ?></span>
		</td>
	</tr>

	<!-- START OF TARGET MARKET SELECTION -->

	<tr>
		<td colspan="2">
			<h4><?php esc_html_e( 'Target Market Restrictions', 'wpsc' ); ?></h4>
		</td>
	</tr>
	<?php
		$countrylist = $wpdb->get_results( "SELECT id,country,visible FROM `".WPSC_TABLE_CURRENCY_LIST."` ORDER BY country ASC ",ARRAY_A );
		$selectedCountries = wpsc_get_meta( $category_id,'target_market','wpsc_category' );
	?>
	<tr>
		<th scope="row" valign="top">
			<label for="image"><?php esc_html_e( 'Target Markets', 'wpsc' ); ?></label>
		</th>
		<td>
			<?php if ( wpsc_is_suhosin_enabled() ) : ?>
				<em><?php esc_html_e( 'The Target Markets feature has been disabled because you have the Suhosin PHP extension installed on this server. If you need to use the Target Markets feature, then disable the suhosin extension. If you can not do this, you will need to contact your hosting provider.','wpsc' ); ?></em>
			<?php else : ?>
				<span><?php esc_html_e( 'Select', 'wpsc' ); ?>: <a href='' class='wpsc_select_all'><?php esc_html_e( 'All', 'wpsc' ); ?></a>&nbsp; <a href='' class='wpsc_select_none'><?php esc_html_e( 'None', 'wpsc' ); ?></a></span><br />
				<div id='resizeable' class='ui-widget-content multiple-select'>
					<?php foreach( $countrylist as $country ) {
						if ( in_array( $country['id'], (array)$selectedCountries ) ) {
							?>
							<input type='checkbox' name='countrylist2[]' id='countrylist2-<?php echo $country['id']; ?>' value='<?php echo $country['id']; ?>' checked='<?php echo $country['visible']; ?>' />
							<label for="countrylist2-<?php echo $country['id']; ?>"><?php esc_html_e( $country['country'] ); ?></label><br />
							<?php
						} else {
							?>
							<input type='checkbox' name='countrylist2[]' id='countrylist2-<?php echo $country['id']; ?>' value='<?php echo $country['id']; ?>'  />
							<label for="countrylist2-<?php echo $country['id']; ?>"><?php esc_html_e( $country['country'] ); ?></label><br />
							<?php
						}
					} ?>
				</div>
			<?php endif; ?><br />
			<span class="description"><?php esc_html_e( 'Select the markets you are selling this category to.', 'wpsc' ); ?></span>
		</td>
	</tr>

	<!-- Checkout settings -->

	<tr>
		<td colspan="2">
			<h4><?php esc_html_e( 'Checkout Settings', 'wpsc' ); ?></h4>
		</td>
	</tr>
	<?php
		if ( !isset( $category['term_id'] ) )
			$category['term_id'] = '';

		$used_additonal_form_set = wpsc_get_categorymeta( $category['term_id'], 'use_additional_form_set' );
		$checkout_sets = get_option('wpsc_checkout_form_sets');
		unset($checkout_sets[0]);
		$uses_billing_address = (bool)wpsc_get_categorymeta( $category['term_id'], 'uses_billing_address' );
	?>
	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="image"><?php esc_html_e( 'Category requires additional checkout form fields', 'wpsc' ); ?></label>
		</th>
		<td>
			<select name='use_additional_form_set'>
				<option value=''><?php esc_html_e( 'None', 'wpsc' ); ?></option>
				<?php
					foreach( (array) $checkout_sets as $key => $value ) {
						$selected_state = "";
						if ( $used_additonal_form_set == $key ) {
							$selected_state = "selected='selected'";
						} ?>
						<option <?php echo $selected_state; ?> value='<?php echo $key; ?>'><?php echo esc_html( $value ); ?></option>
						<?php
					}
				?>
			</select>
		</td>
	</tr>

	<tr class="form-field">
		<th scope="row" valign="top">
			<label><?php esc_html_e( 'Address to calculate shipping with', 'wpsc' ); ?></label>
		</th>
		<td>
			<label><input type='radio' class='wpsc_cat_box'  value='0' name='uses_billing_address' <?php echo ( ( $uses_billing_address != true ) ? "checked='checked'" : "" ); ?> /> <?php esc_html_e( 'Default Setting', 'wpsc' ); ?></label>
			<label><input type='radio' class='wpsc_cat_box' value='1' name='uses_billing_address' <?php echo ( ( $uses_billing_address == true ) ? "checked='checked'" : "" ); ?> /> <?php esc_html_e( 'Billing Address', 'wpsc' ); ?></label>
			<p class='description'><?php esc_html_e( 'Products in this category will use the address specified to calculate shipping costs.', 'wpsc' ); ?></p>
		</td>
	</tr>

	<?php
}

/**
 * wpsc_save_category_set, Saves the category set data
 * @param nothing
 * @return nothing
 */
function wpsc_save_category_set( $category_id, $tt_id ) {
	global $wpdb;

	if ( ! empty( $_POST ) ) {
		/* Image Processing Code*/
		if ( ! empty( $_FILES['image'] ) && preg_match( "/\.(gif|jp(e)*g|png){1}$/i", $_FILES['image']['name'] ) ) {
			if ( function_exists( "getimagesize" ) ) {
				if ( isset( $_POST['width'] ) && ( (int) $_POST['width'] > 10 && (int) $_POST['width'] < 512 ) && ( (int)$_POST['height'] > 10 && (int)$_POST['height'] < 512 ) ) {
					$width = (int) $_POST['width'];
					$height = (int) $_POST['height'];
					image_processing( $_FILES['image']['tmp_name'], ( WPSC_CATEGORY_DIR.$_FILES['image']['name'] ), $width, $height, 'image' );
				} else {
					image_processing( $_FILES['image']['tmp_name'], ( WPSC_CATEGORY_DIR.$_FILES['image']['name'] ), null, null, 'image' );
				}
				$image = esc_sql( $_FILES['image']['name'] );
			} else {
				$new_image_path = ( WPSC_CATEGORY_DIR.basename($_FILES['image']['name'] ) );
				move_uploaded_file( $_FILES['image']['tmp_name'], $new_image_path );
				$stat = stat( dirname( $new_image_path ) );
				$perms = $stat['mode'] & 0000666;
				@ chmod( $new_image_path, $perms );
				$image = esc_sql( $_FILES['image']['name'] );
			}
		} else {
			$image = '';
		}
		//Good to here
		if ( isset( $_POST['tag_ID'] ) ) {
			//Editing
			$category_id = $_POST['tag_ID'];
			$category = get_term_by( 'id', $category_id, 'wpsc_product_category' );
			$url_name = $category->slug;

		}
		if ( isset( $_POST['deleteimage'] ) && $_POST['deleteimage'] == 1 ) {
			wpsc_delete_categorymeta( $category_id, 'image' );
		} else if ( $image != '' ) {
			wpsc_update_categorymeta( $category_id, 'image', $image );
		}

		if ( ! empty( $_POST['height'] ) && is_numeric( $_POST['height'] ) && ! empty( $_POST['width'] ) && is_numeric( $_POST['width'] ) && $image == null ) {
			$imagedata = wpsc_get_categorymeta( $category_id, 'image' );
			if ( $imagedata != null ) {
				$height = $_POST['height'];
				$width = $_POST['width'];
				$imagepath = WPSC_CATEGORY_DIR . $imagedata;
				$image_output = WPSC_CATEGORY_DIR . $imagedata;
				image_processing( $imagepath, $image_output, $width, $height );
			}
		}

		wpsc_update_categorymeta( $category_id, 'fee', '0' );
		wpsc_update_categorymeta( $category_id, 'active', '1' );
		wpsc_update_categorymeta( $category_id, 'order', '0' );

		if ( isset( $_POST['display_type'] ) )
			wpsc_update_categorymeta( $category_id, 'display_type', esc_sql( stripslashes( $_POST['display_type'] ) ) );

		if ( isset( $_POST['image_height'] ) )
			wpsc_update_categorymeta( $category_id, 'image_height', absint( $_POST['image_height'] ) );

		if ( isset( $_POST['image_width'] ) )
			wpsc_update_categorymeta( $category_id, 'image_width', absint($_POST['image_width'] ) );

		if ( ! empty( $_POST['use_additional_form_set'] ) ) {
			wpsc_update_categorymeta( $category_id, 'use_additional_form_set', $_POST['use_additional_form_set'] );
			//exit('<pre>'.print_r($_POST,1).'</pre>');
		} else {
			wpsc_delete_categorymeta( $category_id, 'use_additional_form_set' );
		}

		if ( ! empty( $_POST['uses_billing_address'] ) ) {
			wpsc_update_categorymeta( $category_id, 'uses_billing_address', 1 );
			$uses_additional_forms = true;
		} else {
			wpsc_update_categorymeta( $category_id, 'uses_billing_address', 0 );
			$uses_additional_forms = false;
		}

		if ( ! empty( $_POST['countrylist2'] ) && ( $category_id > 0 ) ) {
			$AllSelected = false;
			$countryList = $wpdb->get_col( "SELECT `id` FROM  `" . WPSC_TABLE_CURRENCY_LIST . "`" );

			if ( $AllSelected != true ){
				$unselectedCountries = array_diff( $countryList, $_POST['countrylist2'] );
				//find the countries that are selected
				$selectedCountries = array_intersect( $countryList, $_POST['countrylist2'] );
				wpsc_update_categorymeta( $category_id, 'target_market', $selectedCountries );
			}

		} elseif ( ! isset( $_POST['countrylist2'] ) ){
			wpsc_update_categorymeta( $category_id,   'target_market', '' );
			$AllSelected = true;
		}

	}
}


?>
