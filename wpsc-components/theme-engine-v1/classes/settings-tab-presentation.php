<?php

class WPSC_Settings_Tab_Presentation extends WPSC_Settings_Tab {
	public function __construct() {
		$this->page_title = __( 'General Settings', 'wpsc' );
	}

	public function category_list() {
		global $wpdb;

		$current_default = esc_attr( get_option( 'wpsc_default_category' ) );
		$group_data      = get_terms( 'wpsc_product_category', 'hide_empty=0&parent=0' );
		$categorylist    = "<select name='wpsc_options[wpsc_default_category]'>";

		if ( $current_default == 'all' )
			$selected = "selected='selected'";
		else
			$selected = '';

		$categorylist .= "<option value='all' " . $selected . " >" . __( 'Show All Products', 'wpsc' ) . "</option>";

		if ( $current_default == 'list' )
			$selected = "selected='selected'";
		else
			$selected = '';

		$categorylist .= "<option value='list' " . $selected . " >" . __( 'Show list of product categories', 'wpsc' ) . "</option>";

		$categorylist .= "<optgroup label='" . __( 'Product Categories', 'wpsc' ) . "'>";
		foreach ( $group_data as $group ) {
			$selected = "";
			if ( $current_default == $group->term_id )
				$selected = "selected='selected'";
			else
				$selected = "";

			$categorylist .= "<option value='" . $group->term_id . "' " . $selected . " >" . $group->name . "</option>";
			$category_data = get_terms( 'wpsc_product_category', 'hide_empty=0&parent=' . $group->term_id );
			if ( $category_data != null ) {
				foreach ( $category_data as $category ) {
					if ( $current_default == $category->term_id )
						$selected = "selected='selected'";
					else
						$selected = "";
					$categorylist .= "<option value='" . $category->term_id . "' " . $selected . " >" . $category->name . "</option>";
				}
			}
		}
		$categorylist .= "</optgroup>";
		$categorylist .= "</select>";
		return $categorylist;
	}

	private function theme_metabox(){

		$wpsc_templates    = wpsc_list_product_templates();
		$themes_location   = wpsc_check_theme_location();
		$themes_copied     = false; //Check to see whether themes have been copied to selected Theme Folder
		$themes_backedup   = false; //Check to see whether themes have recently been backedup
		$themes_in_uploads = false; //Check to see whether themes live in the uploads directory

		if ( isset( $_SESSION['wpsc_themes_copied'] ) && ( true == $_SESSION['wpsc_themes_copied'] ) )
			$themes_copied = true;

		if ( isset( $_SESSION['wpsc_themes_backup'] ) && ( true == $_SESSION['wpsc_themes_backup'] ) )
			$themes_backedup = true;

		if ( wpsc_count_themes_in_uploads_directory() > 0 ) {
			$themes_in_uploads = true;

			foreach( (array)$themes_location as $location )
				$new_location[] = str_ireplace( 'wpsc-','', $location );

			$themes_location = $new_location;
		}

		// Used to flush transients - @since 3.8-development
		if ( true === $themes_copied )
			do_action( 'wpsc_move_theme' );

	?>
		<div id="poststuff" class="metabox-holder">
			<div id="themes_and_appearance" class='postbox'>
				<h3 class="hndle"><span><?php esc_html_e( "Advanced Theme Settings", 'wpsc' ); ?></span></h3>
					<div class="inside">
					<?php

					if( isset( $_SESSION['wpsc_theme_empty'] ) && ($_SESSION['wpsc_theme_empty'] == true)  ) {
						?>

							<div class="updated fade below-h2" id="message" style="background-color: rgb(255, 251, 204);">
								<p><?php esc_html_e( 'You did not specify any template files to be moved.', 'wpsc' ); ?></p>
							</div>
						<?php
						$_SESSION['wpsc_theme_empty'] = false;
						$themes_copied = false;
					}
					if ( isset( $_SESSION['wpsc_themes_copied'] ) && ($_SESSION['wpsc_themes_copied'] == true) ) {
						?>
							<div class="updated fade below-h2" id="message" style="background-color: rgb(255, 251, 204);">
								<?php if(in_array(false, $_SESSION['wpsc_themes_copied_results'], true)): ?>
									<p style="color:red;"><?php esc_html_e( 'Error: some files could not be copied. Please make sure that theme folder is writable.', 'wpsc' ); ?></p>
								<?php else: ?>
									<p><?php esc_html_e( 'Thanks, the themes have been copied.', 'wpsc' ); ?></p>
								<?php endif; ?>
							</div>
						<?php
							unset($_SESSION['wpsc_themes_copied']);
							unset($_SESSION['wpsc_themes_copied_results']);
						}
						if ( isset( $_SESSION['wpsc_themes_backup'] ) && ($_SESSION['wpsc_themes_backup'] == true) ) {
						?>
							<div class="updated fade below-h2" id="message" style="background-color: rgb(255, 251, 204);">
								<p><?php _e( 'Thanks, you have made a succesful backup of your theme.  It is located at the URL below.  Please note each backup you create will replace your previous backups.', 'wpsc' ); ?></p>
								<p><?php _e( 'URL:', 'wpsc' ); ?> <?php echo "/" . str_replace( ABSPATH, "", WPSC_THEME_BACKUP_DIR ); ?></p>
							</div>
						<?php
							$_SESSION['wpsc_themes_backup'] = false;
						}
					?>
					<p>
					<?php if(false !== $themes_location)
							//Some themes have been moved to the themes folder
						_e( 'Some Theme files have been moved to your WordPress Theme Folder.', 'wpsc' );
					else
					    _e( 'No Theme files have been moved to your WordPress Theme Folder.', 'wpsc' );

					 ?>

					</p>
					<p>
						<?php _e( 'WP eCommerce provides you the ability to move your theme files to a safe place for theming control.

	If you want to change the look of your site, select the files you want to edit from the list and click the move button. This will copy the template files to your active WordPress theme. ','wpsc' ); ?>
					</p>
					<ul>
					<?php
						foreach($wpsc_templates as $file){
							$id = str_ireplace('.', '_', $file);
							$selected = '';
							if(false !== array_search($file, (array)$themes_location))
								$selected = 'checked="checked"';
							?>
							<li><input type='checkbox' id='<?php echo $id; ?>' <?php echo $selected; ?> value='<?php echo esc_attr( $file ); ?>' name='wpsc_templates_to_port[]' />
							<label for='<?php echo $id; ?>'><?php echo esc_html( $file ); ?></label></li>
					<?php }	 ?>
					 </ul>
					 <p>
					 <?php if(false !== $themes_location){
					 esc_html_e( 'To change the look of certain aspects of your shop, you can edit the moved files that are found here:', 'wpsc' );
					 ?>
					 </p>
					 <p class="howto">	<?php echo get_stylesheet_directory(); ?></p>
					<?php } ?>
					<p><?php
						wp_nonce_field('wpsc_copy_themes');
						?>
						<input type='submit' value='<?php esc_attr_e( 'Move Template Files &rarr;' ); ?>' class="button" name='wpsc_move_themes' />
					</p>
					 <p><?php _e( 'You can create a copy of your WordPress Theme by clicking the backup button bellow. Once copied you can find them here:' ,'wpsc' ); ?></p>
					<p class="howto"><?php echo esc_html( '/wp-content/uploads/wpsc/theme_backup/' ); ?></p>
					<p>
						<?php
						printf( __( '<a href="%s" class="button">Backup Your WordPress Theme</a>', 'wpsc' ), wp_nonce_url( 'admin.php?wpsc_admin_action=backup_themes', 'backup_themes' ) ); ?>
						<br style="clear:both" />
					</p>

					<br style="clear:both" />
					 <p><?php esc_html_e( 'If you have moved your files in some other way i.e FTP, you may need to click the Flush Theme Cache. This will refresh the locations WordPress looks for your templates.' ,'wpsc' ); ?></p>
					<p><?php printf( __( '<a href="%s" class="button">Flush Theme Cache</a>', 'wpsc' ), wp_nonce_url( 'admin.php?wpsc_flush_theme_transients=true', 'wpsc_flush_theme_transients' ) ); ?></p>
					<br style="clear:both" />
					<br style="clear:both" />
					</div>
			</div>
		</div>
	<?php
	}

	public function display() {
		?>
			<div class='product_and_button_settings'>

			<?php $this->theme_metabox(); ?>

			<h3 class="form_group"><?php esc_html_e( 'Button Settings', 'wpsc' ); ?></h3>
			<table class='wpsc_options form-table' style="width:550px">
				<tr>
					<th scope="row"><?php esc_html_e( 'Button Type', 'wpsc' ); ?>:</th>
					<td>
						<?php
						$addtocart_or_buynow = get_option( 'addtocart_or_buynow' );
						$addtocart_or_buynow1 = "";
						$addtocart_or_buynow2 = "";
						switch ( $addtocart_or_buynow ) {
							case 0:
								$addtocart_or_buynow1 = "checked ='checked'";
								break;

							case 1:
								$addtocart_or_buynow2 = "checked ='checked'";
								break;
						}
						?>
						<input type='radio' value='0' name='wpsc_options[addtocart_or_buynow]' id='addtocart_or_buynow1' <?php echo $addtocart_or_buynow1; ?> />
						<label for='addtocart_or_buynow1'><?php esc_html_e( 'Add To Cart', 'wpsc' ); ?></label> &nbsp;<br />
				<?php $selected_gateways = get_option( 'custom_gateway_options' );
					$disable_buy_now = '';
					$message = '';
					if (!in_array( 'wpsc_merchant_paypal_standard', (array)$selected_gateways )){
							$disable_buy_now = 'disabled="disabled"';
							$message = __( 'Buy Now Button only works for Paypal Standard, please activate Paypal Standard to enable this option.','wpsc' );
					} ?>
						<input <?php echo $disable_buy_now; ?> type='radio' value='1' name='wpsc_options[addtocart_or_buynow]' id='addtocart_or_buynow2' <?php echo $addtocart_or_buynow2; ?> />
						<label for='addtocart_or_buynow2'><?php _e( 'Buy Now', 'wpsc' ); ?></label><br />
						<?php echo $message; ?>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Hide "Add to cart" button', 'wpsc' ); ?>:</th>
					<td>
						<?php
						$hide_addtocart_button = get_option( 'hide_addtocart_button' );
						$hide_addtocart_button1 = "";
						$hide_addtocart_button2 = "";
						switch ( $hide_addtocart_button ) {
							case 0:
								$hide_addtocart_button2 = "checked ='checked'";
								break;

							case 1:
								$hide_addtocart_button1 = "checked ='checked'";
								break;
						}
						?>
						<input type='radio' value='1' name='wpsc_options[hide_addtocart_button]' id='hide_addtocart_button1' <?php echo $hide_addtocart_button1; ?> /> 				<label for='hide_addtocart_button1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
						<input type='radio' value='0' name='wpsc_options[hide_addtocart_button]' id='hide_addtocart_button2' <?php echo $hide_addtocart_button2; ?> /> 				<label for='hide_addtocart_button2'><?php _e( 'No', 'wpsc' ); ?></label>
					</td>
				</tr>
			</table>

			<h3 class="form_group"><?php esc_html_e( 'Product Settings', 'wpsc' ); ?></h3>

			<table class='wpsc_options form-table'>

				<tr>
					<th scope="row"><?php esc_html_e( 'Show Product Ratings', 'wpsc' ); ?>:</th>
					<td>
						<?php
						$display_pnp = get_option( 'product_ratings' );
						$product_ratings1 = "";
						$product_ratings2 = "";
						switch ( $display_pnp ) {
							case 0:
								$product_ratings2 = "checked ='checked'";
								break;

							case 1:
								$product_ratings1 = "checked ='checked'";
								break;
						}
						?>
						<input type='radio' value='1' name='wpsc_options[product_ratings]' id='product_ratings1' <?php echo $product_ratings1; ?> /> <label for='product_ratings1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
						<input type='radio' value='0' name='wpsc_options[product_ratings]' id='product_ratings2' <?php echo $product_ratings2; ?> /> <label for='product_ratings2'><?php _e( 'No', 'wpsc' ); ?></label>
					</td>
				</tr>
				<tr>
					<?php
					$list_view_quantity_value1 = '';
					$list_view_quantity_value2 = '';
					if ( get_option( 'list_view_quantity' ) == 1 )
						$list_view_quantity_value1 = 'checked="checked"';
					else
						$list_view_quantity_value2 = 'checked="checked"';
					?>
					<th scope="row">
						<?php esc_html_e( 'Show Stock Availability', 'wpsc' ); ?>:
					</th>
					<td>
						<input type='radio' value='1' name='wpsc_options[list_view_quantity]' id='list_view_quantity1' <?php echo $list_view_quantity_value1; ?> /> <label for='list_view_quantity1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
						 <input type='radio' value='0' name='wpsc_options[list_view_quantity]' id='list_view_quantity2' <?php echo $list_view_quantity_value2; ?> /> <label for='list_view_quantity2'><?php _e( 'No', 'wpsc' ); ?></label> &nbsp;
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Display Fancy Purchase Notifications', 'wpsc' ); ?>:
					</th>
					<td>
						<?php
						$fancy_notifications = get_option( 'fancy_notifications' );
						$fancy_notifications1 = "";
						$fancy_notifications2 = "";
						switch ( $fancy_notifications ) {
							case 0:
								$fancy_notifications2 = "checked ='checked'";
								break;

							case 1:
								$fancy_notifications1 = "checked ='checked'";
								break;
						}
						?>
						<input type='radio' value='1' name='wpsc_options[fancy_notifications]' id='fancy_notifications1' <?php echo $fancy_notifications1; ?> /> <label for='fancy_notifications1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
						<input type='radio' value='0' name='wpsc_options[fancy_notifications]' id='fancy_notifications2' <?php echo $fancy_notifications2; ?> /> <label for='fancy_notifications2'><?php _e( 'No', 'wpsc' ); ?></label>
					</td>
				</tr>


				<tr>
					<th scope="row"><?php esc_html_e( 'Display per item shipping', 'wpsc' ); ?>:</th>
					<td>
						<?php
						$display_pnp = get_option( 'display_pnp' );
						$display_pnp1 = "";
						$display_pnp2 = "";
						switch ( $display_pnp ) {
							case 0:
								$display_pnp2 = "checked ='checked'";
								break;

							case 1:
								$display_pnp1 = "checked ='checked'";
								break;
						}
						?>
						<input type='radio' value='1' name='wpsc_options[display_pnp]' id='display_pnp1' <?php echo $display_pnp1; ?> /> <label for='display_pnp1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
						<input type='radio' value='0' name='wpsc_options[display_pnp]' id='display_pnp2' <?php echo $display_pnp2; ?> /> <label for='display_pnp2'><?php _e( 'No', 'wpsc' ); ?></label>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Disable link in Title', 'wpsc' ); ?>:	</th>
					<td>
						<?php
						$hide_name_link = get_option( 'hide_name_link' );
						$hide_name_link1 = "";
						$hide_name_link2 = "";
						switch ( $hide_name_link ) {
							case 0:
								$hide_name_link2 = "checked ='checked'";
								break;

							case 1:
								$hide_name_link1 = "checked ='checked'";
								break;
						}
						?>
						<input type='radio' value='1' name='wpsc_options[hide_name_link]' id='hide_name_link1' <?php echo $hide_name_link1; ?> />
						<label for='hide_name_link1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
						<input type='radio' value='0' name='wpsc_options[hide_name_link]' id='hide_name_link2' <?php echo $hide_name_link2; ?> />
						<label for='hide_name_link2'><?php _e( 'No', 'wpsc' ); ?></label>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Add quantity field to each product description', 'wpsc' ); ?>:</th>
					<td>
						<?php
						$multi_adding = get_option( 'multi_add' );
						switch ( $multi_adding ) {
							case 1:
								$multi_adding1 = "checked ='checked'";
								break;

							case 0:
								$multi_adding2 = "checked ='checked'";
								break;
						}
						?>
						<input type='radio' value='1' name='wpsc_options[multi_add]' id='multi_adding1' <?php if ( isset( $multi_adding1 ) )
							echo $multi_adding1; ?> />
						<label for='multi_adding1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
						<input type='radio' value='0' name='wpsc_options[multi_add]' id='multi_adding2' <?php if ( isset( $multi_adding2 ) )
							echo $multi_adding2; ?> />
						<label for='multi_adding2'><?php _e( 'No', 'wpsc' ); ?></label>
					</td>
				</tr>
			</table>
		</div>


			<h3 class="form_group"><?php esc_html_e( 'Product Page Settings', 'wpsc' ); ?></h3>
			<table class='wpsc_options form-table'>
				<tr>
					<th scope="row"><?php esc_html_e( 'Product Display', 'wpsc' ); ?>:</th>
					<td>
					<?php
						$display_pnp = get_option( 'product_view' );
						$product_view1 = null;
						$product_view2 = null;
						$product_view3 = null;
						switch ( $display_pnp ) {
							case "grid":
								if ( function_exists( 'product_display_grid' ) ) {
									$product_view3 = "selected ='selected'";
									break;
								}

							case "list":
								if ( function_exists( 'product_display_list' ) ) {
									$product_view2 = "selected ='selected'";
									break;
								}

							default:
								$product_view1 = "selected ='selected'";
								break;
						}

						if ( get_option( 'show_images_only' ) == 1 ) {
							$show_images_only_value = "checked='checked'";
						} else {
							$show_images_only_value = '';
						}
						if ( get_option( 'display_variations' ) == 1 ) {
							$display_variations = "checked='checked'";
						} else {
							$display_variations = '';
						}
						if ( get_option( 'display_description' ) == 1 ) {
							$display_description = "checked='checked'";
						} else {
							$display_description = '';
						}
						if ( get_option( 'display_addtocart' ) == 1 ) {
							$display_addtocart = "checked='checked'";
						} else {
							$display_addtocart = '';
						}
						if ( get_option( 'display_moredetails' ) == 1 ) {
							$display_moredetails = "checked='checked'";
						} else {
							$display_moredetails = '';
						}
					?>
					<select name='wpsc_options[product_view]'>
						<option value='default' <?php echo $product_view1; ?>><?php esc_html_e( 'Default View', 'wpsc' ); ?></option>
<?php
						if ( function_exists( 'product_display_list' ) ) {
?>
							<option value='list' <?php echo $product_view2; ?>><?php esc_html_e( 'List View', 'wpsc' ); ?></option>
<?php
						} else {
?>
							<option value='list' disabled='disabled' <?php echo $product_view2; ?>><?php esc_html_e( 'List View', 'wpsc' ); ?></option>
						<?php
						}

						if ( function_exists( 'product_display_grid' ) ) {
						?>
							<option value='grid' <?php echo $product_view3; ?>><?php esc_html_e( 'Grid View', 'wpsc' ); ?></option>
						<?php
						} else {
						?>
							<option value='grid' disabled='disabled' <?php echo $product_view3; ?>><?php esc_html_e( 'Grid View', 'wpsc' ); ?></option>
						<?php
						}
						?>
						</select>
					<?php
						if ( ! function_exists( 'product_display_grid' ) ) {
					?>
					<a href='http://wpecommerce.org/store/premium-plugins/'><?php esc_html_e( 'Purchase unavailable options', 'wpsc' ); ?></a>
					<?php
						}
					?>
					</td>
				</tr>

				<tr id="wpsc-grid-settings">
					<th scope="row"><?php esc_html_e( 'Grid view settings:', 'wpsc' ) ?></th>
					<td>
						<input type='number' min="0" name='wpsc_options[grid_number_per_row]' id='grid_number_per_row' size='2' value='<?php esc_attr_e( get_option( 'grid_number_per_row' ) ); ?>' class='small-text' />
						<label for='grid_number_per_row'><?php esc_html_e( 'Products Per Row', 'wpsc' ); ?></label><br />

						<input type='hidden' value='0' name='wpsc_options[show_images_only]' />
						<input type='checkbox' value='1' name='wpsc_options[show_images_only]' id='wpsc-show-images-only' <?php echo $show_images_only_value; ?> />
						<label for='wpsc-show-images-only'><?php esc_html_e( 'Show images only', 'wpsc' ); ?></label><br />

						<input type='hidden' value='0' name='wpsc_options[display_variations]' />
						<input type='checkbox' value='1' name='wpsc_options[display_variations]' id='wpsc-display-variations' <?php echo $display_variations; ?> />
						<label for='wpsc-display-variations'><?php esc_html_e( 'Display Variations', 'wpsc' ); ?></label><br />

						<input type='hidden' value='0' name='wpsc_options[display_description]' />
						<input type='checkbox' value='1' name='wpsc_options[display_description]' id='wpsc-display-description' <?php echo $display_description; ?> />
						<label for='wpsc-display-description'><?php esc_html_e( 'Display Description', 'wpsc' ); ?></label><br />

						<input type='hidden' value='0' name='wpsc_options[display_addtocart]' />
						<input type='checkbox' value='1' name='wpsc_options[display_addtocart]' id='wpsc-display-add-to-cart' <?php echo $display_addtocart; ?> />
						<label for='wpsc-display-add-to-cart'><?php esc_html_e( 'Display "Add To Cart" Button', 'wpsc' ); ?></label><br />

						<input type='hidden' value='0' name='wpsc_options[display_moredetails]' />
						<input type='checkbox' value='1' name='wpsc_options[display_moredetails]' id='wpsc-display-more-details' <?php echo $display_moredetails; ?> />
						<label for='wpsc-display-more-details'><?php esc_html_e( 'Display "More Details" Button', 'wpsc' ); ?></label>
					</td>
				</tr>

					<?php
						$selected1 = $selected2 = '';
						if(get_option('wpsc_display_categories'))
							$selected1 = 'checked="checked"';
						else
							$selected2 = 'checked="checked"';
					?>

				<tr>
					<th scope="row"><?php esc_html_e( 'Show list of categories', 'wpsc' ); ?>:</th>
					<td>
						<input type='radio' value='1' name='wpsc_options[wpsc_display_categories]' id='display_categories2' <?php echo $selected1; ?> />
						<label for='display_categories2'><?php _e( 'Yes', 'wpsc' ); ?></label>
						<input type='radio' value='0' name='wpsc_options[wpsc_display_categories]' id='display_categories1' <?php echo $selected2; ?> />
						<label for='display_categories1'><?php _e( 'No', 'wpsc' ); ?></label><br />
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Select what product category you want to display on the products page', 'wpsc' ); ?>:</th>
					<td>
						<?php echo $this->category_list(); ?>
					</td>
				</tr>
			<?php
						$wpsc_sort_by = get_option( 'wpsc_sort_by' );
						switch ( $wpsc_sort_by ) {
							case 'name':
								$wpsc_sort_by1 = "selected ='selected'";
								break;

							case 'price':
								$wpsc_sort_by2 = "selected ='selected'";
								break;

							case 'dragndrop':
								$wpsc_sort_by4 = "selected='selected'";
								break;

							case 'id':
							default:
								$wpsc_sort_by3 = "selected ='selected'";
								break;
						}
			?>
				<tr>
					<th scope="row">
						<?php _e( 'Sort Product By', 'wpsc' ); ?>:
					</th>
					<td>
						<select name='wpsc_options[wpsc_sort_by]'>
							<option <?php if ( isset( $wpsc_sort_by1 ) )
							echo $wpsc_sort_by1; ?> value='name'><?php esc_html_e( 'Name', 'wpsc' ); ?></option>
							<option <?php if ( isset( $wpsc_sort_by2 ) )
							echo $wpsc_sort_by2; ?> value='price'><?php esc_html_e( 'Price', 'wpsc' ); ?></option>
							<option <?php if ( isset( $wpsc_sort_by4 ) )
							echo $wpsc_sort_by4; ?> value='dragndrop'><?php esc_html_e( 'Drag &amp; Drop', 'wpsc' ); ?></option>
							<option <?php if ( isset( $wpsc_sort_by3 ) )
							echo $wpsc_sort_by3; ?> value='id'><?php esc_html_e( 'Time Uploaded', 'wpsc' ); ?></option>
						</select>

						 <select name="wpsc_options[wpsc_product_order]">
						 	<option value="ASC" <?php selected( get_option( 'wpsc_product_order' ), 'ASC' ); ?>><?php _ex( 'Ascending', 'product order setting', 'wpsc' ); ?></option>
						 	<option value="DESC"  <?php selected( get_option( 'wpsc_product_order' ), 'DESC' ); ?>><?php _ex( 'Descending', 'product order setting', 'wpsc' ); ?></option>
						 </select>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Show Breadcrumbs', 'wpsc' ); ?>:</th>
					<td>
					<?php
						$show_breadcrumbs = get_option( 'show_breadcrumbs' );
						$show_breadcrumbs1 = "";
						$show_breadcrumbs2 = "";
						switch ( $show_breadcrumbs ) {
							case 0:
								$show_breadcrumbs2 = "checked ='checked'";
								break;

							case 1:
								$show_breadcrumbs1 = "checked ='checked'";
								break;
						}
					?>
						<input type='radio' value='1' name='wpsc_options[show_breadcrumbs]' id='show_breadcrumbs1' <?php echo $show_breadcrumbs1; ?> /> <label for='show_breadcrumbs1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
						<input type='radio' value='0' name='wpsc_options[show_breadcrumbs]' id='show_breadcrumbs2' <?php echo $show_breadcrumbs2; ?> /> <label for='show_breadcrumbs2'><?php _e( 'No', 'wpsc' ); ?></label>
					</td>
				</tr>



				<tr>
					<th scope="row">
					<?php esc_html_e( 'Product Groups/Products Display', 'wpsc' ); ?>:
					</th>
					<td>
					<?php
						$display_pnp = get_option( 'catsprods_display_type' );
						$catsprods_display_type1 = "";
						$catsprods_display_type2 = "";
						switch ( $display_pnp ) {
							case 0:
								$catsprods_display_type1 = "checked ='checked'";
								break;

							case 1:
								$catsprods_display_type2 = "checked ='checked'";
								break;
						}
					?>
								<input type='radio' value='0' name='wpsc_options[catsprods_display_type]' id='catsprods_display_type1' <?php echo $catsprods_display_type1; ?> /> <label for='catsprods_display_type1'><?php _e( 'Product Groups Only (All products displayed)', 'wpsc' ); ?></label><br/>
								<input type='radio' value='1' name='wpsc_options[catsprods_display_type]' id='catsprods_display_type2' <?php echo $catsprods_display_type2; ?> /> <label for='catsprods_display_type2'><?php _e( 'Sliding Product Groups (1 product per page)', 'wpsc' ); ?></label>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<?php esc_html_e( 'Show Subcategory Products in Parent Category', 'wpsc' ); ?>:
							</th>
							<td>
								<?php
								$show_subcatsprods_in_cat = get_option( 'show_subcatsprods_in_cat' );
								$show_subcatsprods_in_cat_on = '';
								$show_subcatsprods_in_cat_off = '';
								switch ( $show_subcatsprods_in_cat ) {
									case 1:
										$show_subcatsprods_in_cat_on = 'checked="checked"';
										break;
									case 0:
										$show_subcatsprods_in_cat_off = 'checked="checked"';
										break;
								}
								?>
								<input type="radio" value="1" name="wpsc_options[show_subcatsprods_in_cat]" id="show_subcatsprods_in_cat_on" <?php echo $show_subcatsprods_in_cat_on; ?> /> <label for="show_subcatsprods_in_cat_on"><?php echo __( 'Yes', 'wpsc' ); ?></label> &nbsp;
								<input type="radio" value="0" name="wpsc_options[show_subcatsprods_in_cat]" id="show_subcatsprods_in_cat_off" <?php echo $show_subcatsprods_in_cat_off; ?> /> <label for="show_subcatsprods_in_cat_off"><?php echo __( 'No', 'wpsc' ); ?></label>
							</td>
						</tr>

					<?php
						if ( function_exists( 'gold_shpcrt_search_form' ) ) {
					?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Show Search', 'wpsc' ); ?>:</th>
						<td>
					<?php
							$display_pnp = get_option( 'show_search' );
							$show_search1 = "";
							$show_search2 = "";
							switch ( $display_pnp ) {
								case 0:
									$show_search2 = "checked ='checked'";
									break;

								case 1:
									$show_search1 = "checked ='checked'";
									break;
							}

							$display_advanced_search = get_option( 'show_advanced_search' );
							$show_advanced_search = "";
							if ( $display_advanced_search == 1 ) {
								$show_advanced_search = "checked ='checked'";
							}

							$display_live_search = get_option( 'show_live_search' );
							if ( $display_live_search == 1 ) {
								$show_live_search = "checked ='checked'";
							} else {
								$show_live_search = "";
							}

							if ( $show_search1 != "checked ='checked'" ) {
								$dis = "style='display:none;'";
							} else {
								$dis = "";
							}

							$embed_live_search_results = get_option( 'embed_live_search_results', '0' ) == '1' ? ' checked="checked"' : '';
					?>
						<input type='radio' onclick='jQuery("#wpsc_advanced_search").show()' value='1' name='wpsc_options[show_search]' id='show_search1' <?php echo $show_search1; ?> /> <label for='show_search1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
						<input type='radio' onclick='jQuery("#wpsc_advanced_search").hide()' value='0' name='wpsc_options[show_search]' id='show_search2' <?php echo $show_search2; ?> /> <label for='show_search2'><?php _e( 'No', 'wpsc' ); ?></label>

						<div <?php echo $dis; ?> id='wpsc_advanced_search'>
							<input  type='hidden' name='wpsc_options[show_advanced_search]' value='0' />
							<input  type='checkbox' name='wpsc_options[show_advanced_search]' id='show_advanced_search' <?php echo $show_advanced_search; ?>  value='1' />
							<?php esc_html_e( 'Show Advanced Search', 'wpsc' ); ?><br />
							<input type='hidden' name='wpsc_options[show_live_search]' value='0' />
							<input type='checkbox' name='wpsc_options[show_live_search]' id='show_live_search' <?php echo $show_live_search; ?> value='1' />
							<?php esc_html_e( 'Use Live Search', 'wpsc' ); ?><br />
							<input type='hidden' name='wpsc_options[embed_live_search_results]' value='0' />
							<input type='checkbox' name='wpsc_options[embed_live_search_results]' id='embed_live_search_results'<?php echo $embed_live_search_results; ?> value='1' />
							<?php esc_html_e( 'Dynamically replace search results into product list', 'wpsc' ); ?>
						</div>
					</td>
				</tr>
<?php
						}
?>


				<tr>
					<th scope="row"><?php esc_html_e( 'Replace Page Title With Product/Category Name', 'wpsc' ); ?>:</th>
					<td>
					<?php
						$wpsc_replace_page_title = get_option( 'wpsc_replace_page_title' );
						$wpsc_replace_page_title1 = "";
						$wpsc_replace_page_title2 = "";
						switch ( $wpsc_replace_page_title ) {
							case 0:
								$wpsc_replace_page_title2 = "checked ='checked'";
								break;

							case 1:
								$wpsc_replace_page_title1 = "checked ='checked'";
								break;
						}
					?>
						<input type='radio' value='1' name='wpsc_options[wpsc_replace_page_title]' id='wpsc_replace_page_title1' <?php echo $wpsc_replace_page_title1; ?> /> <label for='wpsc_replace_page_title1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
						<input type='radio' value='0' name='wpsc_options[wpsc_replace_page_title]' id='wpsc_replace_page_title2' <?php echo $wpsc_replace_page_title2; ?> /> <label for='wpsc_replace_page_title2'><?php _e( 'No', 'wpsc' ); ?></label>
					</td>
				</tr>
					<tr>
					<th scope="row"><?php esc_html_e( 'Display Featured Product above Product Pages', 'wpsc' ); ?>:</th>
					<td>
					<?php
						$wpsc_hide_featured_products = get_option( 'wpsc_hide_featured_products' );
						$wpsc_hide_featured_products1 = "";
						$wpsc_hide_featured_products2 = "";
						switch ( $wpsc_hide_featured_products ) {
							case 0:
								$wpsc_hide_featured_products2 = "checked ='checked'";
								break;

							case 1:
							default:
								$wpsc_hide_featured_products1 = "checked ='checked'";
								break;
						}
					?>
						<input type='radio' value='1' name='wpsc_options[wpsc_hide_featured_products]' id='wpsc_hide_featured_products1' <?php echo $wpsc_hide_featured_products1; ?> /> <label for='wpsc_hide_featured_products1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
						<input type='radio' value='0' name='wpsc_options[wpsc_hide_featured_products]' id='wpsc_hide_featured_products2' <?php echo $wpsc_hide_featured_products2; ?> /> <label for='wpsc_hide_featured_products2'><?php _e( 'No', 'wpsc' ); ?></label>
					</td>
				</tr>
			</table>

			<h3 class="form_group"><?php esc_html_e( 'Shopping Cart Settings', 'wpsc' ); ?></h3>
			<table class='wpsc_options form-table'>
				<tr>
					<th scope="row"><?php esc_html_e( 'Cart Location', 'wpsc' ); ?>:</th>
					<td>
					<?php
						$cart_location = get_option( 'cart_location' );
						$cart2 = "";
						$cart3 = "";
						$cart4 = "";
						$cart5 = "";
						switch ( $cart_location ) {
							case 2:
								$cart2 = "checked ='checked'";
								break;

							case 3:
								$cart3 = "checked ='checked'";
								break;

							case 4:
								$cart4 = "checked ='checked'";
								break;

							case 5:
								$cart5 = "checked ='checked'";
								break;
						}
?>
						<input type='radio' onclick='hideelement1("dropshop_option", this.value)' value='2' name='wpsc_options[cart_location]' id='cart2' <?php echo $cart2; ?> /> <label for='cart2'><?php esc_html_e( 'Page', 'wpsc' ); ?></label> &nbsp;
<?php
						if ( function_exists( 'wp_register_sidebar_widget' ) ) {
?>
							<input type='radio' value='4' onclick='hideelement1("dropshop_option", this.value)' name='wpsc_options[cart_location]' id='cart4' <?php echo $cart4; ?> /> <label for='cart4'><?php esc_html_e( 'Widget', 'wpsc' ); ?></label> &nbsp;
					<?php
						} else {
					?>
							<input type='radio'  disabled='disabled' value='4' name='wpsc_options[cart_location]' id='cart4' alt='<?php esc_attr_e( 'You need to enable the widgets plugin to use this', 'wpsc' ); ?>' title='<?php esc_attr_e( 'You need to enable the widgets plugin to use this', 'wpsc' ); ?>' <?php echo $cart4; ?> /> <label style='color: #666666;' for='cart4' title='<?php esc_attr_e( 'You need to enable the widgets plugin to use this', 'wpsc' ); ?>'><?php esc_html_e( 'Widget', 'wpsc' ); ?></label> &nbsp;
					<?php
						}

						if ( function_exists( 'drag_and_drop_cart_ajax' ) ) {
					?>
							<input type='radio' onclick='hideelement1("dropshop_option", this.value)' value='5' name='wpsc_options[cart_location]' id='cart5' <?php echo $cart5; ?> /> <label for='cart5'><?php esc_html_e( 'DropShop', 'wpsc' ); ?></label> &nbsp;
<?php
						} else {
?>
							<input type='radio' disabled='disabled' value='5' name='wpsc_options[cart_location]' id='cart5' alt='<?php esc_attr_e( 'You need to enable the widgets plugin to use this', 'wpsc' ); ?>' title='<?php esc_attr_e( 'You need to install the Gold and DropShop extentions to use this', 'wpsc' ); ?>' <?php if ( isset( $cart5 ) )
								echo $cart5; ?> /> <label style='color: #666666;' for='cart5' title='<?php esc_attr_e( 'You need to install the Gold and DropShop extentions to use this', 'wpsc' ); ?>'><?php esc_html_e( 'DropShop', 'wpsc' ); ?></label> &nbsp;
<?php
						}
?>
						<input type='radio' onclick='hideelement1("dropshop_option", this.value)' value='3' name='wpsc_options[cart_location]' id='cart3' <?php if ( isset( $cart3 ) )
							echo $cart3; ?> /> <label for='cart3'><?php esc_html_e( 'Manual', 'wpsc' ); ?> <span style='font-size: 7pt;'><?php echo esc_html( '(PHP code: <?php echo wpsc_shopping_cart(); ?> )')?></span></label>
						<div  style='display: <?php if ( !empty( $cart5 ) ) {
							echo "block";
						} else {
							echo "none";
						} ?>;'  id='dropshop_option'>
							<p>
								<input type="radio" id="drop1" value="all" <?php if ( get_option( 'dropshop_display' ) == 'all' ) {
							echo "checked='checked'";
						} ?> name="wpsc_options[dropshop_display]" /><label for="drop1"><?php esc_html_e( 'Show Dropshop on every page', 'wpsc' ); ?></label>
								<input type="radio" id="drop2" value="product" <?php if ( get_option( 'dropshop_display' ) == 'product' ) {
							echo "checked='checked'";
						} ?> name="wpsc_options[dropshop_display]"/><label for="drop2"><?php esc_html_e( 'Show Dropshop only on product page', 'wpsc' ); ?></label>
							</p>
							<p>
								<input type="radio" id="wpsc_dropshop_theme1" value="light" <?php if ( get_option( 'wpsc_dropshop_theme' ) != 'dark' ) {
							echo "checked='checked'";
						} ?> name="wpsc_options[wpsc_dropshop_theme]" /><label for="wpsc_dropshop_theme1"><?php esc_html_e( 'Use light Dropshop style', 'wpsc' ); ?></label>
								<input type="radio" id="wpsc_dropshop_theme2" value="dark" <?php if ( get_option( 'wpsc_dropshop_theme' ) == 'dark' ) {
							echo "checked='checked'";
						} ?> name="wpsc_options[wpsc_dropshop_theme]"/><label for="wpsc_dropshop_theme2"><?php esc_html_e( 'Use dark Dropshop style', 'wpsc' ); ?></label>
								<input type="radio" id="wpsc_dropshop_theme3" value="craftyc" <?php if ( get_option( 'wpsc_dropshop_theme' ) == 'craftyc' ) {
							echo "checked='checked'";
						} ?> name="wpsc_options[wpsc_dropshop_theme]"/><label for="wpsc_dropshop_theme2"><?php esc_html_e( 'Crafty', 'wpsc' ); ?></label>

							</p>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<?php esc_html_e( 'Display "+ Postage & Tax"', 'wpsc' ); ?>:
					</th>
					<td>
<?php
						$add_plustax = get_option( 'add_plustax' );
						$add_plustax1 = "";
						$add_plustax2 = "";
						switch ( $add_plustax ) {
							case 0:
								$add_plustax2 = "checked ='checked'";
								break;

							case 1:
								$add_plustax1 = "checked ='checked'";
								break;
						}
?>
						<input type='radio' value='1' name='wpsc_options[add_plustax]' id='add_plustax1' <?php echo $add_plustax1; ?> /> <label for='add_plustax1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
						<input type='radio' value='0' name='wpsc_options[add_plustax]' id='add_plustax2' <?php echo $add_plustax2; ?> /> <label for='add_plustax2'><?php _e( 'No', 'wpsc' ); ?></label>
					</td>
				</tr>
			</table>

			<h3 class="form_group"><?php esc_html_e( 'Product Category Settings', 'wpsc' ); ?></h3>
			<table class='wpsc_options form-table'>

				<tr>
					<th scope="row"><?php esc_html_e( 'Show Product Category Description', 'wpsc' ); ?>:</th>
					<td>
<?php
						$wpsc_category_description = get_option( 'wpsc_category_description' );
						$wpsc_category_description1 = "";
						$wpsc_category_description2 = "";
						switch ( $wpsc_category_description ) {
							case '1':
								$wpsc_category_description1 = "checked ='checked'";
								break;

							case '0':
							default:
								$wpsc_category_description2 = "checked ='checked'";
								break;
						}
?>
						<input type='radio' value='1' name='wpsc_options[wpsc_category_description]' id='wpsc_category_description1' <?php echo $wpsc_category_description1; ?> /> <label for='wpsc_category_description1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
						<input type='radio' value='0' name='wpsc_options[wpsc_category_description]' id='wpsc_category_description2' <?php echo $wpsc_category_description2; ?> /> <label for='wpsc_category_description2'><?php _e( 'No', 'wpsc' ); ?></label>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<?php esc_html_e( 'Show Product Category Thumbnails', 'wpsc' ); ?>:
					</th>
					<td>
<?php
						$show_category_thumbnails = get_option( 'show_category_thumbnails' );
						$show_category_thumbnails1 = "";
						$show_category_thumbnails2 = "";
						switch ( $show_category_thumbnails ) {
							case 0:
								$show_category_thumbnails2 = "checked ='checked'";
								break;

							case 1:
								$show_category_thumbnails1 = "checked ='checked'";
								break;
						}
?>
						<input type='radio' value='1' name='wpsc_options[show_category_thumbnails]' id='show_category_thumbnails1' <?php echo $show_category_thumbnails1; ?> /> <label for='show_category_thumbnails1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
						<input type='radio' value='0' name='wpsc_options[show_category_thumbnails]' id='show_category_thumbnails2' <?php echo $show_category_thumbnails2; ?> /> <label for='show_category_thumbnails2'><?php _e( 'No', 'wpsc' ); ?></label>
					</td>
				</tr>

				<!-- // Adrian - options for displaying number of products per category -->

				<tr>
					<th scope="row">
						<?php esc_html_e( 'Show Product Count per Product Category', 'wpsc' ); ?>:
					</th>
					<td>
<?php
						$display_pnp = get_option( 'show_category_count' );
						$show_category_count1 = "";
						$show_category_count2 = "";
						switch ( $display_pnp ) {
							case 0:
								$show_category_count2 = "checked ='checked'";
								break;

							case 1:
								$show_category_count1 = "checked ='checked'";
								break;
						}
?>
						<input type='radio' value='1' name='wpsc_options[show_category_count]' id='show_category_count1' <?php echo $show_category_count1; ?> /> <label for='show_category_count1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
						<input type='radio' value='0' name='wpsc_options[show_category_count]' id='show_category_count2' <?php echo $show_category_count2; ?> /> <label for='show_category_count2'><?php _e( 'No', 'wpsc' ); ?></label>
					</td>
				</tr>

				<!-- // Adrian - options for displaying category display type -->

				<tr>
					<th scope="row"><?php esc_html_e( 'Use Category Grid View', 'wpsc' ); ?>:</th>
					<td>
<?php
						$wpsc_category_grid_view = get_option( 'wpsc_category_grid_view' );
						$wpsc_category_grid_view1 = "";
						$wpsc_category_grid_view2 = "";
						switch ( $wpsc_category_grid_view ) {
							case '1':
								$wpsc_category_grid_view1 = "checked ='checked'";
								break;

							case '0':
							default:
								$wpsc_category_grid_view2 = "checked ='checked'";
								break;
						}
?>
						<input type='radio' value='1' name='wpsc_options[wpsc_category_grid_view]' id='wpsc_category_grid_view1' <?php echo $wpsc_category_grid_view1; ?> /> <label for='wpsc_category_grid_view1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
						<input type='radio' value='0' name='wpsc_options[wpsc_category_grid_view]' id='wpsc_category_grid_view2' <?php echo $wpsc_category_grid_view2; ?> /> <label for='wpsc_category_grid_view2'><?php _e( 'No', 'wpsc' ); ?></label>
					</td>
				</tr>
			</table>


			<h3 class="form_group"><a name='thumb_settings'><?php esc_html_e( 'Thumbnail Settings', 'wpsc' ); ?></a></h3>
			<p><em><?php esc_html_e( 'Note: Anytime you update any of the thumbnail settings, WPeC will automatically resize all of your thumbnails for you.  Depending on how many images you have, this could take awhile.', 'wpsc' ); ?></em></p>
			<table class='wpsc_options form-table'>
				<?php if ( function_exists( "getimagesize" ) ) { ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default Product Thumbnail Size', 'wpsc' ); ?>:</th>
						<td>
							<fieldset class="wpsc-width-height-fields">
								<label for="image_width"><?php esc_html_e( 'Width', 'wpsc' ); ?></label>
								<input name="wpsc_options[product_image_width]" type="number" step="1" min="0" id="product_image_width" value="<?php esc_attr_e( get_option( 'product_image_width' ) ); ?>" class="small-text">
								<label for="large_size_h"><?php esc_html_e( 'Height', 'wpsc' ); ?></label>
								<input name="wpsc_options[product_image_height]" type="number" step="1" min="0" id="product_image_height" value="<?php esc_attr_e( get_option( 'product_image_height' ) ); ?>" class="small-text">
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Default Product Category Thumbnail Size', 'wpsc' ); ?>:
						</th>
						<td>
							<fieldset class="wpsc-width-height-fields">
								<label for="image_width"><?php esc_html_e( 'Width', 'wpsc' ); ?></label>
								<input name="wpsc_options[category_image_width]" type="number" step="1" min="0" id="category_image_width" value="<?php esc_attr_e( get_option( 'category_image_width' ) ); ?>" class="small-text">
								<label for="large_size_h"><?php esc_html_e( 'Height', 'wpsc' ); ?></label>
								<input name="wpsc_options[category_image_height]" type="number" step="1" min="0" id="category_image_height" value="<?php esc_attr_e( get_option( 'category_image_height' ) ); ?>" class="small-text">
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Single Product Image Size', 'wpsc' ); ?>:
						</th>
						<td>
							<fieldset class="wpsc-width-height-fields">
								<label for="image_width"><?php esc_html_e( 'Width', 'wpsc' ); ?></label>
								<input name="wpsc_options[single_view_image_width]" type="number" step="1" min="0" id="single_view_image_width" value="<?php esc_attr_e( get_option( 'single_view_image_width' ) ); ?>" class="small-text">
								<label for="large_size_h"><?php esc_html_e( 'Height', 'wpsc' ); ?></label>
								<input name="wpsc_options[single_view_image_height]" type="number" step="1" min="0" id="single_view_image_height" value="<?php esc_attr_e( get_option( 'single_view_image_height' ) ); ?>" class="small-text">
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
						<?php
							$cropthumbs = get_option( 'wpsc_crop_thumbnails' );
							$crop1 = "";
							$crop2 = "";
							switch ( $cropthumbs ) {
								case 0:
									$crop2 = "checked ='checked'";
									break;

								case 1:
									$crop1 = "checked ='checked'";
									break;
							}
?>
									<?php esc_html_e( 'Crop Thumbnails', 'wpsc' ); ?>:
								</th>
								<td>
									<input type='radio' value='1' name='wpsc_options[wpsc_crop_thumbnails]' id='wpsc_crop_thumbnails1' <?php echo $crop1; ?> /> <label for='crop1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
									<input type='radio' value='0' name='wpsc_options[wpsc_crop_thumbnails]' id='wpsc_crop_thumbnails2' <?php echo $crop2; ?> /> <label for='crop2'><?php _e( 'No', 'wpsc' ); ?></label><br />
									<?php esc_html_e( 'Choosing "Yes" means that thumbnails are cropped to exact dimensions (normally thumbnails are proportional)', 'wpsc' ); ?>
								</td>
							</tr>
					<?php
						}
					?>

				<tr>
					<th scope="row"><?php esc_html_e( 'Show Thumbnails', 'wpsc' ); ?>:</th>
					<td>
<?php
						$show_thumbnails = get_option( 'show_thumbnails' );
						$show_thumbnails1 = "";
						$show_thumbnails2 = "";
						switch ( $show_thumbnails ) {
							case 0:
								$show_thumbnails2 = "checked ='checked'";
								break;

							case 1:
								$show_thumbnails1 = "checked ='checked'";
								break;
						}
?>
						<input type='radio' value='1' name='wpsc_options[show_thumbnails]' id='show_thumbnails1' <?php echo $show_thumbnails1; ?> /> <label for='show_thumbnails1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
						<input type='radio' value='0' name='wpsc_options[show_thumbnails]' id='show_thumbnails2' <?php echo $show_thumbnails2; ?> /> <label for='show_thumbnails2'><?php _e( 'No', 'wpsc' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Use Lightbox Effect for product images', 'wpsc' ); ?>:</th>
					<td>
					<?php
						$show_thumbnails_thickbox = get_option( 'show_thumbnails_thickbox' );
						$show_thumbnails_thickbox1 = "";
						$show_thumbnails_thickbox2 = "";
						switch ( $show_thumbnails_thickbox ) {
							case 0:
								$show_thumbnails_thickbox2 = "checked ='checked'";
								break;

							case 1:
								$show_thumbnails_thickbox1 = "checked ='checked'";
								break;
						}
					?>
						<input type='radio' value='1' name='wpsc_options[show_thumbnails_thickbox]' id='show_thumbnails_thickbox1' <?php echo $show_thumbnails_thickbox1; ?> /> <label for='show_thumbnails_thickbox1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
						<input type='radio' value='0' name='wpsc_options[show_thumbnails_thickbox]' id='show_thumbnails_thickbox2' <?php echo $show_thumbnails_thickbox2; ?> /> <label for='show_thumbnails_thickbox2'><?php _e( 'No', 'wpsc' ); ?></label><br />
					<?php esc_html_e( 'Using lightbox means that when clicking on a product image, a larger version will be displayed in a "lightbox" style window. If you are using a plugin such as Shutter Reloaded, you may want to disable lightbox.', 'wpsc' ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Lightbox script to use', 'wpsc' ); ?>:</th>
					<td>
					<?php
						$wpsc_lightbox = get_option( 'wpsc_lightbox', 'thickbox' );
						$wpsc_lightbox_thickbox1 = "";
						$wpsc_lightbox_thickbox2 = "";
						if( $wpsc_lightbox == "thickbox" )
							$wpsc_lightbox_thickbox2 = "checked ='checked'";
						if( $wpsc_lightbox == "colorbox" )
							$wpsc_lightbox_thickbox1 = "checked ='checked'";
					?>
						<input type='radio' value='colorbox' name='wpsc_options[wpsc_lightbox]' id='wpsc_lightbox_thickbox1' <?php echo $wpsc_lightbox_thickbox1; ?> /> <label for='show_thumbnails_thickbox1'><?php _e( 'Colorbox', 'wpsc' ); ?></label> &nbsp;
						<input type='radio' value='thickbox' name='wpsc_options[wpsc_lightbox]' id='wpsc_lightbox_thickbox2' <?php echo $wpsc_lightbox_thickbox2; ?> /> <label for='show_thumbnails_thickbox2'><?php _e( 'Thickbox', 'wpsc' ); ?></label><br />
					</td>
				</tr>

					<?php
						if ( function_exists( 'gold_shpcrt_display_gallery' ) ) {
					?>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Show Thumbnail Gallery', 'wpsc' ); ?>:
						</th>
						<td>
<?php
							$display_pnp = get_option( 'show_gallery' );
							$show_gallery1 = "";
							$show_gallery2 = "";
							switch ( $display_pnp ) {
								case 0:
									$show_gallery2 = "checked ='checked'";
									break;

								case 1:
									$show_gallery1 = "checked ='checked'";
									break;
							}
?>
									<input type='radio' value='1' name='wpsc_options[show_gallery]' id='show_gallery1' <?php echo $show_gallery1; ?> /> <label for='show_gallery1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
									<input type='radio' value='0' name='wpsc_options[show_gallery]' id='show_gallery2' <?php echo $show_gallery2; ?> /> <label for='show_gallery2'><?php _e( 'No', 'wpsc' ); ?></label>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<?php esc_html_e( 'Gallery Thumbnail Image Size', 'wpsc' ); ?>:
								</th>
								<td>
									<?php esc_html_e( 'Width', 'wpsc' ); ?>:<input type='text' size='6' name='wpsc_options[wpsc_gallery_image_width]' value='<?php esc_attr_e( get_option( 'wpsc_gallery_image_width' ) ); ?>' />
									<?php esc_html_e( 'Height', 'wpsc' ); ?>:<input type='text' size='6' name='wpsc_options[wpsc_gallery_image_height]' value='<?php esc_attr_e( get_option( 'wpsc_gallery_image_height' ) ); ?>' /><br />

								</td>
							</tr>

					<?php
						}
					?>
						</table>


			<h3 class="form_group"><?php esc_html_e( 'Pagination Settings', 'wpsc' ); ?></h3>
			<table class='wpsc_options form-table'>
				<tr>
					<th scope="row">
					<?php esc_html_e( 'Use Pagination', 'wpsc' ); ?>:
					</th>
					<td>
<?php
						$use_pagination = get_option( 'use_pagination' );
						$use_pagination1 = "";
						$use_pagination2 = "";
						switch ( $use_pagination ) {
							case 0:
								$use_pagination2 = "checked ='checked'";
								$page_count_display_state = 'style=\'display: none;\'';
								break;

							case 1:
								$use_pagination1 = "checked ='checked'";
								$page_count_display_state = '';
								break;
						}
?>
						<input onclick='jQuery("#wpsc_products_per_page").show()'  type='radio' value='1' name='wpsc_options[use_pagination]' id='use_pagination1' <?php echo $use_pagination1; ?> /> <label for='use_pagination1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
						<input onclick='jQuery("#wpsc_products_per_page").hide()' type='radio' value='0' name='wpsc_options[use_pagination]' id='use_pagination2' <?php echo $use_pagination2; ?> /> <label for='use_pagination2'><?php _e( 'No', 'wpsc' ); ?></label><br />
						<div id='wpsc_products_per_page' <?php echo $page_count_display_state; ?> >
							<input type='text' size='6' name='wpsc_options[wpsc_products_per_page]' value='<?php echo get_option( 'wpsc_products_per_page' ); ?>' /> <?php _e( 'number of products to show per page', 'wpsc' ); ?>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row">
					<?php esc_html_e( 'Page Number position', 'wpsc' ); ?>:
					</th>
					<td>
						<input type='radio' value='1' name='wpsc_options[wpsc_page_number_position]' id='wpsc_page_number_position1' <?php if ( get_option( 'wpsc_page_number_position' ) == 1 ) {
							echo "checked='checked'";
						} ?> />&nbsp;<label for='wpsc_page_number_position1'><?php esc_html_e( 'Top', 'wpsc' ); ?></label> &nbsp;
						<input type='radio' value='2' name='wpsc_options[wpsc_page_number_position]' id='wpsc_page_number_position2' <?php if ( get_option( 'wpsc_page_number_position' ) == 2 ) {
							echo "checked='checked'";
						} ?> />&nbsp;<label for='wpsc_page_number_position2'><?php esc_html_e( 'Bottom', 'wpsc' ); ?></label>&nbsp;
						<input type='radio' value='3' name='wpsc_options[wpsc_page_number_position]' id='wpsc_page_number_position3' <?php if ( get_option( 'wpsc_page_number_position' ) == 3 ) {
							echo "checked='checked'";
						} ?> />&nbsp;<label for='wpsc_page_number_position3'><?php esc_html_e( 'Both', 'wpsc' ); ?></label>
						<br />
					</td>
				</tr>
			</table>


			<h3 class="form_group"><?php esc_html_e( 'Comment Settings', 'wpsc' ); ?></h3>
			<table class='wpsc_options form-table'>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Use IntenseDebate Comments', 'wpsc' ); ?>:
						<a href="http://intensedebate.com/" title="<?php esc_attr_e( 'IntenseDebate comments enhance and encourage conversation on your blog or website', 'wpsc' ); ?>" target="_blank"><img src="<?php echo WPSC_CORE_IMAGES_URL; ?>/intensedebate-logo.png" alt="<?php esc_attr_e( 'intensedebate-logo', 'wpsc' ); ?>" title="<?php esc_attr_e( 'IntenseDebate', 'wpsc' ); ?>" /></a>
					</th>
					<td>
<?php
						$enable_comments = get_option( 'wpsc_enable_comments' );
						$enable_comments1 = "";
						$enable_comments2 = "";
						switch ( $enable_comments ) {
							case 1:
								$enable_comments1 = "checked ='checked'";
								$intense_debate_account_id_display_state = '';
								break;

							default:
							case 0:
								$enable_comments2 = "checked ='checked'";
								$intense_debate_account_id_display_state = 'style=\'display: none;\'';
								break;
						}
?>
					<input onclick='jQuery("#wpsc_enable_comments,.wpsc_comments_details").show()'  type='radio' value='1' name='wpsc_options[wpsc_enable_comments]' id='wpsc_enable_comments1' <?php echo $enable_comments1; ?> /> <label for='wpsc_enable_comments1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
					<input onclick='jQuery("#wpsc_enable_comments,.wpsc_comments_details").hide()' type='radio' value='0' name='wpsc_options[wpsc_enable_comments]' id='wpsc_enable_comments2' <?php echo $enable_comments2; ?> /> <label for='wpsc_enable_comments1'><?php _e( 'No', 'wpsc' ); ?></label><br />
					<div id='wpsc_enable_comments' <?php echo $intense_debate_account_id_display_state; ?> >
						<?php esc_html_e( 'IntenseDebate Account ID', 'wpsc' ); ?>:<br/>
						<input type='text' size='30' name='wpsc_options[wpsc_intense_debate_account_id]' value='<?php esc_attr_e( get_option( 'wpsc_intense_debate_account_id' ) ); ?>' /><br/>
						<small><a href='http://intensedebate.com/sitekey/' title='<?php esc_attr_e( 'Help on finding the Account ID', 'wpsc' ); ?>'><?php _e( 'Help on finding the Account ID', 'wpsc' ); ?></a></small>
					</div>
				</td>
			</tr>

			<tr>

				<th scope="row">
					<div class='wpsc_comments_details' <?php echo $intense_debate_account_id_display_state ?> >
						<?php esc_html_e( 'By Default Display Comments on', 'wpsc' ); ?>:
					</div>
				</th>
				<td>
					<div class='wpsc_comments_details' <?php echo $intense_debate_account_id_display_state ?> >
									<input type='radio' value='1' name='wpsc_options[wpsc_comments_which_products]' id='wpsc_comments_which_products1' <?php if ( get_option( 'wpsc_comments_which_products' ) == 1 || !get_option( 'wpsc_comments_which_products' ) ) {
				echo "checked='checked'";
			} ?> /><label for='wpsc_comments_which_products1'><?php esc_html_e( 'All Products', 'wpsc' ); ?></label>&nbsp;
									<input type='radio' value='2' name='wpsc_options[wpsc_comments_which_products]' id='wpsc_comments_which_products2' <?php if ( get_option( 'wpsc_comments_which_products' ) == 2 ) {
				echo "checked='checked'";
			} ?> /><label for='wpsc_comments_which_products2'><?php esc_html_e( 'Per Product', 'wpsc' ); ?></label>&nbsp;
						<br />
					</div>
				</td>

			</tr>
		</table>
		<?php
	}
}