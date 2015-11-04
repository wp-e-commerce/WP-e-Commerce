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
	foreach( $sort_order as $key => $value ) {

		if ( empty( $value ) ) {
			continue;
		}

		$value = preg_replace( '/[^0-9]/', '', $value );

		if ( ! wpsc_update_meta( $value, 'sort_order', absint( $key ), 'wpsc_variation' ) ) {
			$result = false;
		}
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
		'image' => __( 'Image', 'wp-e-commerce' )
	);

	$columns = array_merge( $custom_array, $columns );

	return $columns;
}

/**
 * Custom Category Column Data
 *
 * Adds images to the custom category column.
 *
 * @param   string  $string       Column output.
 * @param   string  $column_name  Column name.
 * @param   string  $term_id      Term ID.
 * @return  string                Updated column output.
 */
function wpsc_custom_category_column_data( $string, $column_name, $term_id ) {
	if ( 'image' == $column_name ) {
		$term = get_term_by( 'id', $term_id, 'wpsc_product_category' );
		$image = wpsc_get_categorymeta( $term_id, 'image' );
		$noimage = defined( 'WPSC_CORE_THEME_URL' ) ? WPSC_CORE_THEME_URL . '/wpsc-images/noimage.png' : WPSC_TE_V2_URL . '/theming/assets/images/noimage.png';

		$format = '<img src="%s" title="%s" alt="%2$s" width="30" height="30" />';
		if ( ! empty( $image ) ) {
			$string = sprintf( $format, WPSC_CATEGORY_URL . $image, esc_attr( $term->name ) );
		} else {
			$string = sprintf( $format, $noimage, esc_attr( $term->name ) );
		}
	}
	return $string;
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

	<h3><?php esc_html_e('Advanced Store Settings', 'wp-e-commerce'); ?></h3>
	<h4><?php esc_html_e('Presentation Settings', 'wp-e-commerce'); ?></h4>
	<p class='description'><?php esc_html_e( 'These settings override the general presentation settings found in Settings &gt; Store &gt; Presentation.', 'wp-e-commerce' ); ?></p>
	<div style="margin: 15px 0 15px 0">
		<label for='image'><?php esc_html_e( 'Category Image' , 'wp-e-commerce' ); ?></label>
		<input type='file' name='image' value='' />
	</div>
	<div class="form-field">
		<label for='display_type'><?php esc_html_e( 'Product Display', 'wp-e-commerce' ); ?></label>
		<select name='display_type'>
			<option value='default'<?php checked( $display_type, 'default' ); ?>><?php esc_html_e('Default View', 'wp-e-commerce'); ?></option>
			<option value='list'<?php disabled( _wpsc_is_display_type_supported( 'list' ), false ); ?><?php checked( $display_type, 'list' ); ?>><?php esc_html_e('List View', 'wp-e-commerce'); ?></option>
			<option value='grid'<?php disabled( _wpsc_is_display_type_supported( 'grid' ), false ); ?><?php checked( $display_type, 'grid' ); ?>><?php esc_html_e('Grid View', 'wp-e-commerce'); ?></option>
		</select>
	</div>
	<?php if ( function_exists( "getimagesize" ) ) : ?>
		<div class="form-field">
			<?php esc_html_e( 'Thumbnail Size', 'wp-e-commerce' ); ?>
			<fieldset class="wpsc-width-height-fields">
				<legend class="screen-reader-text"><span><?php esc_html_e( 'Thumbnail Size', 'wp-e-commerce' ); ?></span></legend>
				<label for="image_width"><?php esc_html_e( 'Width', 'wp-e-commerce' ); ?></label>
				<input name="image_width" type="number" step="1" min="0" id="image_width" value="<?php if ( isset( $category['image_width'] ) ) echo esc_attr( $category['image_width'] ); ?>" class="small-text" style="width: 70px">
				<label for="large_size_h"><?php esc_html_e( 'Height', 'wp-e-commerce' ); ?></label>
				<input name="image_height" type="number" step="1" min="0" id="image_height" value="<?php if ( isset( $category['image_height'] ) ) echo esc_attr( $category['image_height'] ); ?>" class="small-text" style="width: 70px">
			</fieldset>
		</div>
	<?php endif;?>

	<!-- START OF TARGET MARKET SELECTION -->
	<?php

		$category_id = '';

		if ( isset( $_GET['tag_ID'] ) ) {
			$category_id = absint( $_GET['tag_ID'] );
		}

		$countrylist       = WPSC_Countries::get_countries_array( true, true );
		$selectedCountries = wpsc_get_meta( $category_id, 'target_market', 'wpsc_category' );
	?>
	<h4><?php esc_html_e( 'Restrict to Target Markets', 'wp-e-commerce' )?></h4>
	<div class='form-field'>
		<?php if ( wpsc_is_suhosin_enabled() ) : ?>
			<em><?php esc_html_e( "The Target Markets feature has been disabled because you have the Suhosin PHP extension installed on this server. If you need to use the Target Markets feature, then disable the suhosin extension. If you can not do this, you will need to contact your hosting provider.", 'wp-e-commerce' ); ?></em>
		<?php else: ?>
			<div class='multiple-select-container'>
				<span><?php esc_html_e( 'Select', 'wp-e-commerce' ); ?> <a href='' class='wpsc_select_all'><?php esc_html_e( 'All', 'wp-e-commerce' ); ?></a>&nbsp; <a href='' class='wpsc_select_none'><?php esc_html_e( 'None', 'wp-e-commerce' ); ?></a></span><br />
				<div id='resizeable' class='ui-widget-content multiple-select'>
					<?php foreach( $countrylist as $country ): ?>
						<?php if ( in_array( $country['id'], (array)$selectedCountries ) ): ?>
							<input type='checkbox' name='countrylist2[]' id='countrylist2-<?php echo $country['id']; ?>' value='<?php echo $country['id']; ?>' checked='<?php echo $country['visible']; ?>' />
							<label for="countrylist2-<?php echo $country['id']; ?>"><?php echo esc_html( $country['country'] ); ?></label><br />
						<?php else: ?>
							<input type='checkbox' name='countrylist2[]' id='countrylist2-<?php echo $country['id']; ?>' value='<?php echo $country['id']; ?>'  />
							<label for="countrylist2-<?php echo $country['id']; ?>"><?php echo esc_html( $country['country'] ); ?></label><br />
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</div>
			<span class='wpscsmall description'><?php esc_html_e( 'Select the markets you are selling this category to.', 'wp-e-commerce' ); ?><span>
		<?php endif; ?>
	</div>

	<!-- Checkout settings -->
	<h4><?php esc_html_e( 'Checkout Settings', 'wp-e-commerce' ); ?></h4>

	<?php
		if ( ! isset( $category['term_id'] ) ) $category['term_id'] = '';
			$used_additonal_form_set = wpsc_get_categorymeta( $category['term_id'], 'use_additional_form_set' );
	?>
	<div class='form-field'>
		<label for="use_additional_form_set"><?php esc_html_e( 'Category requires additional checkout form fields', 'wp-e-commerce' ); ?></label>
		<select name='use_additional_form_set'>
			<option value=''><?php esc_html_e( 'None', 'wp-e-commerce' ); ?></option>
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
		<label><?php esc_html_e( 'Address to calculate shipping with', 'wp-e-commerce' ); ?></label>
		<label><input type="radio" value="0" name="uses_billing_address" <?php checked( $uses_billing_address, 0 ); ?> /> <?php esc_html_e( 'Shipping Address (default)', 'wp-e-commerce' ); ?></label>
		<label><input type="radio" value="1" name="uses_billing_address" <?php checked( $uses_billing_address, 1 ); ?> /> <?php esc_html_e( 'Billing Address', 'wp-e-commerce' ); ?></label>
		<p class='description'><?php esc_html_e( 'Products in this category will use the address specified to calculate shipping costs.', 'wp-e-commerce' ); ?></p>
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
			<h3><?php esc_html_e( 'Advanced Store Settings', 'wp-e-commerce' ); ?></h3>
			<h4><?php esc_html_e( 'Shortcodes and Template Tags', 'wp-e-commerce' ); ?></h4>
			<p class='description'><?php esc_html_e( 'These settings override the general presentation settings found in Settings &gt; Store &gt; Presentation.', 'wp-e-commerce' ); ?></p>
		</td>
	</tr>


	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="display_type"><?php esc_html_e( 'Catalog View', 'wp-e-commerce' ); ?></label>
		</th>
		<td>
			<?php
				$display_type = isset( $category['display_type'] ) ? $category['display_type'] : '';
			?>
			<select name='display_type'>
				<option value='default'<?php selected( $display_type, 'default' ); ?>><?php esc_html_e( 'Default View', 'wp-e-commerce' ); ?></option>
				<option value='list'<?php disabled( _wpsc_is_display_type_supported( 'list' ), false ); ?><?php selected( $display_type, 'list' ); ?>><?php esc_html_e('List View', 'wp-e-commerce'); ?></option>
				<option value='grid' <?php disabled( _wpsc_is_display_type_supported( 'grid' ), false ); ?><?php selected( $display_type, 'grid' ); ?>><?php esc_html_e( 'Grid View', 'wp-e-commerce' ); ?></option>
			</select><br />
		</td>
	</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="image"><?php esc_html_e( 'Category Image', 'wp-e-commerce' ); ?></label>
			</th>
			<td>
				<?php
				$category_image = wpsc_category_image( $category['term_id'] );
				if ( $category_image )
					echo '<p><img src=' . esc_url( $category_image ) . ' alt="' . esc_attr( $category['name'] ) . '" title="' . esc_attr( $category['name'] ) . '" class="wpsc_category_image" /></p>';
				?>
				<input type='file' name='image' value='' /><br />
				<label><input type='checkbox' name='deleteimage' class="wpsc_cat_box" value='1' /><?php esc_html_e( 'Delete Image', 'wp-e-commerce' ); ?></label><br/>
				<span class="description"><?php esc_html_e( 'You can set an image for the category here.  If one exists, check the box to delete.', 'wp-e-commerce' ); ?></span>
			</td>
	</tr>
	<?php if ( function_exists( "getimagesize" ) ) : ?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="image"><?php esc_html_e( 'Thumbnail Size', 'wp-e-commerce' ); ?></label>
			</th>
			<td>
				<fieldset class="wpsc-width-height-fields">
					<legend class="screen-reader-text"><span><?php esc_html_e( 'Thumbnail Size', 'wp-e-commerce' ); ?></span></legend>
					<label for="image_width"><?php esc_html_e( 'Width', 'wp-e-commerce' ); ?></label>
					<input name="image_width" type="number" step="1" min="0" id="image_width" value="<?php if ( isset( $category['image_width'] ) ) echo esc_attr( $category['image_width'] ); ?>" class="small-text">
					<label for="large_size_h"><?php esc_html_e( 'Height', 'wp-e-commerce' ); ?></label>
					<input name="image_height" type="number" step="1" min="0" id="image_height" value="<?php if ( isset( $category['image_height'] ) ) echo esc_attr( $category['image_height'] ); ?>" class="small-text">
				</fieldset>
			</td>
		</tr>
	<?php endif; // 'getimagesize' condition ?>


	<tr>
		<td colspan="2"><h4><?php esc_html_e( 'Shortcodes and Template Tags', 'wp-e-commerce' ); ?></h4></td>
	</tr>

	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="image"><?php esc_html_e( 'Display Category Shortcode', 'wp-e-commerce' ); ?></label>
		</th>
		<td>
			<code>[wpsc_products category_url_name='<?php echo $category["slug"]; ?>']</code><br />
			<span class="description"><?php esc_html_e( 'Shortcodes are used to display a particular category or group within any WordPress page or post.', 'wp-e-commerce' ); ?></span>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="image"><?php esc_html_e( 'Display Category Template Tag', 'wp-e-commerce' ); ?></label>
		</th>
		<td>
			<code>&lt;?php echo wpsc_display_products_page( array( 'category_url_name' => '<?php echo $category["slug"]; ?>' ) ); ?&gt;</code><br />
			<span class="description"><?php esc_html_e( 'Template tags are used to display a particular category or group within your theme / template.', 'wp-e-commerce' ); ?></span>
		</td>
	</tr>

	<!-- START OF TARGET MARKET SELECTION -->

	<tr>
		<td colspan="2">
			<h4><?php esc_html_e( 'Target Market Restrictions', 'wp-e-commerce' ); ?></h4>
		</td>
	</tr>
	<?php
		$countrylist = WPSC_Countries::get_countries_array( true, true );
		$selectedCountries = wpsc_get_meta( $category_id,'target_market','wpsc_category' );
	?>
	<tr>
		<th scope="row" valign="top">
			<label for="image"><?php esc_html_e( 'Target Markets', 'wp-e-commerce' ); ?></label>
		</th>
		<td>
			<?php if ( wpsc_is_suhosin_enabled() ) : ?>
				<em><?php esc_html_e( 'The Target Markets feature has been disabled because you have the Suhosin PHP extension installed on this server. If you need to use the Target Markets feature, then disable the suhosin extension. If you can not do this, you will need to contact your hosting provider.','wp-e-commerce' ); ?></em>
			<?php else : ?>
				<span><?php esc_html_e( 'Select', 'wp-e-commerce' ); ?>: <a href='' class='wpsc_select_all'><?php esc_html_e( 'All', 'wp-e-commerce' ); ?></a>&nbsp; <a href='' class='wpsc_select_none'><?php esc_html_e( 'None', 'wp-e-commerce' ); ?></a></span><br />
				<div id='resizeable' class='ui-widget-content multiple-select'>
					<?php foreach( $countrylist as $country ) {
						if ( in_array( $country['id'], (array)$selectedCountries ) ) {
							?>
							<input type='checkbox' name='countrylist2[]' id='countrylist2-<?php echo $country['id']; ?>' value='<?php echo $country['id']; ?>' checked='<?php echo $country['visible']; ?>' />
							<label for="countrylist2-<?php echo $country['id']; ?>"><?php echo esc_html( $country['country'] ); ?></label><br />
							<?php
						} else {
							?>
							<input type='checkbox' name='countrylist2[]' id='countrylist2-<?php echo $country['id']; ?>' value='<?php echo $country['id']; ?>'  />
							<label for="countrylist2-<?php echo $country['id']; ?>"><?php echo esc_html( $country['country'] ); ?></label><br />
							<?php
						}
					} ?>
				</div>
			<?php endif; ?><br />
			<span class="description"><?php esc_html_e( 'Select the markets you are selling this category to.', 'wp-e-commerce' ); ?></span>
		</td>
	</tr>

	<!-- Checkout settings -->

	<tr>
		<td colspan="2">
			<h4><?php esc_html_e( 'Checkout Settings', 'wp-e-commerce' ); ?></h4>
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
			<label for="image"><?php esc_html_e( 'Category requires additional checkout form fields', 'wp-e-commerce' ); ?></label>
		</th>
		<td>
			<select name='use_additional_form_set'>
				<option value=''><?php esc_html_e( 'None', 'wp-e-commerce' ); ?></option>
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
			<label><?php esc_html_e( 'Address to calculate shipping with', 'wp-e-commerce' ); ?></label>
		</th>
		<td>
			<label><input type="radio" class="wpsc_cat_box" value="0" name="uses_billing_address" <?php echo ( ( $uses_billing_address != true ) ? 'checked="checked"' : '' ); ?> /> <?php esc_html_e( 'Shipping Address (default)', 'wp-e-commerce' ); ?></label><br />
			<label><input type="radio" class="wpsc_cat_box" value="1" name="uses_billing_address" <?php echo ( ( $uses_billing_address == true ) ? 'checked="checked"' : '' ); ?> /> <?php esc_html_e( 'Billing Address', 'wp-e-commerce' ); ?></label>
			<p class='description'><?php esc_html_e( 'Products in this category will use the address specified to calculate shipping costs.', 'wp-e-commerce' ); ?></p>
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
			$category_id = (int) $_POST['tag_ID'];
			$category    = get_term_by( 'id', $category_id, 'wpsc_product_category' );
			$url_name    = $category->slug;

		}
		if ( isset( $_POST['deleteimage'] ) && $_POST['deleteimage'] == 1 ) {
			wpsc_delete_categorymeta( $category_id, 'image' );
		} else if ( $image != '' ) {
			wpsc_update_categorymeta( $category_id, 'image', $image );
		}

		if ( ! empty( $_POST['height'] ) && is_numeric( $_POST['height'] ) && ! empty( $_POST['width'] ) && is_numeric( $_POST['width'] ) && $image == null ) {
			$imagedata = wpsc_get_categorymeta( $category_id, 'image' );
			if ( $imagedata != null ) {
				$height       = (int) $_POST['height'];
				$width        = (int) $_POST['width'];
				$imagepath    = WPSC_CATEGORY_DIR . $imagedata;
				$image_output = WPSC_CATEGORY_DIR . $imagedata;
				image_processing( $imagepath, $image_output, $width, $height );
			}
		}

		wpsc_update_categorymeta( $category_id, 'fee', '0' );
		wpsc_update_categorymeta( $category_id, 'active', '1' );
		wpsc_update_categorymeta( $category_id, 'order', '0' );

		if ( isset( $_POST['display_type'] ) ) {
			wpsc_update_categorymeta( $category_id, 'display_type', esc_sql( stripslashes( $_POST['display_type'] ) ) );
		}

		if ( isset( $_POST['image_height'] ) ) {
			wpsc_update_categorymeta( $category_id, 'image_height', (int) $_POST['image_height'] );
		}

		if ( isset( $_POST['image_width'] ) ) {
			wpsc_update_categorymeta( $category_id, 'image_width', (int) $_POST['image_width'] );
		}

		if ( ! empty( $_POST['use_additional_form_set'] ) ) {
			wpsc_update_categorymeta( $category_id, 'use_additional_form_set', absint( $_POST['use_additional_form_set'] ) );
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
				$posted_countries    = array_map( 'intval', $_POST['countrylist2'] );
				$unselectedCountries = array_diff( $countryList, $posted_countries );
				//find the countries that are selected
				$selectedCountries = array_intersect( $countryList, $posted_countries );
				wpsc_update_categorymeta( $category_id, 'target_market', $selectedCountries );
			}

		} elseif ( ! isset( $_POST['countrylist2'] ) ){
			wpsc_update_categorymeta( $category_id,   'target_market', '' );
			$AllSelected = true;
		}

	}
}


?>
