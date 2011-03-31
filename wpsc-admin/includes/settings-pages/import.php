<?php

/**
 * This file handles the standard importing of products through a csv file upload. Access this page via WP-admin Settings>Import
 * @package WP e-Commerce
 */
function wpsc_options_import() {
	global $wpdb;
?>
	<form name='cart_options' enctype='multipart/form-data' id='cart_options' method='post' action='<?php echo 'admin.php?page=wpsc-settings&tab=import'; ?>' class='wpsc_form_track'>
		<div class="wrap">
<?php _e( '<p>You can import your products from a comma delimited text file.</p><p>An example of a csv import file would look like this: </p><p>Description, Additional Description, Product Name, Price, SKU, weight, weight unit, stock quantity, is limited quantity</p>', 'wpsc' ); ?>

<?php wp_nonce_field( 'update-options', 'wpsc-update-options' ); ?>
		<input type='hidden' name='MAX_FILE_SIZE' value='5000000' />
		<input type='file' name='csv_file' />
		<input type='submit' value='Import' class='button-primary'>
<?php
		if ( isset( $_FILES['csv_file']['name'] ) && ($_FILES['csv_file']['name'] != '') ) {
			ini_set( "auto_detect_line_endings", 1 );
			$file = $_FILES['csv_file'];
			if ( move_uploaded_file( $file['tmp_name'], WPSC_FILE_DIR . $file['name'] ) ) {
				$content = file_get_contents( WPSC_FILE_DIR . $file['name'] );
				$handle = @fopen( WPSC_FILE_DIR . $file['name'], 'r' );
				while ( ($csv_data = @fgetcsv( $handle, filesize( $handle ), "," )) !== false ) {
					$fields = count( $csv_data );
					for ( $i = 0; $i < $fields; $i++ ) {
						if ( !is_array( $data1[$i] ) ) {
							$data1[$i] = array( );
						}
						array_push( $data1[$i], $csv_data[$i] );
					}
				}

				$_SESSION['cvs_data'] = $data1;
				$categories = get_terms( 'wpsc_product_category', 'hide_empty=0&parent=' . $category_id );
?>

				<p><?php _e( 'For each column, select the field it corresponds to in \'Belongs to\'. You can upload as many products as you like.', 'wpsc' ); ?></p>
				<div class='metabox-holder' style='width:90%'>
					<input type='hidden' name='csv_action' value='import'>
					
					<div style='width:100%;' class='postbox'>
						<h3 class='hndle'><?php _e('Product Status' , 'wpsc' ); ?></h3>
						<div class='inside'>
							<table>
								<tr><td style='width:80%;'>
							<?php _e( 'Select if you would like to import your products in as Drafts or Publish them right away.' , 'wpsc' ); ?>
								<br />
								</td><td>
									<select name='post_status'>
										<option value='publish'><?php _e('Publish', 'wpsc'); ?></option>
										<option value='draft'><?php _e('Draft', 'wpsc'); ?></option>
									</select>
								</td></tr>
							</table>
						</div>
					</div>

<?php
				foreach ( (array)$data1 as $key => $datum ) {
?>
					<div style='width:100%;' class='postbox'>
						<h3 class='hndle'><?php printf(__('Column (%s)', 'wpsc'), ($key + 1)); ?></h3>
						<div class='inside'>
							<table>
								<tr><td style='width:80%;'>
										<input type='hidden' name='column[]' value='<?php echo $key + 1; ?>'>
								<?php
								foreach ( $datum as $column ) {
									echo $column;
									break;
								} ?>
								<br />
							</td><td>
								<select  name='value_name[]'>
									<!-- /* 		These are the current fields that can be imported with products, to add additional fields add more <option> to this dorpdown list */ -->
									<option value='name'><?php _e('Product Name', 'wpsc'); ?></option>
									<option value='description'><?php _e('Description', 'wpsc'); ?></option>
									<option value='additional_description'><?php _e('Additional Description', 'wpsc'); ?></option>
									<option value='price'><?php _e('Price', 'wpsc'); ?></option>
									<option value='sku'><?php _e('SKU', 'wpsc'); ?></option>
									<option value='weight'><?php _e('Weight', 'wpsc'); ?></option>
									<option value='weight_unit'><?php _e('Weight Unit', 'wpsc'); ?></option>
									<option value='quantity'><?php _e('Stock Quantity', 'wpsc'); ?></option>
									<option value='quantity_limited'><?php _e('Stock Quantity Limit', 'wpsc'); ?></option>
								</select>
							</td></tr>
					</table>
				</div>
			</div>
<?php } ?>
			<label for='category'><?php _e('Please select a category you would like to place all products from this CSV into' , 'wpsc' ); ?>:</label>
			<select id='category' name='category'>
<?php
			foreach ( $categories as $category ) {
				echo '<option value="' . $category->term_id . '">' . $category->name . '</option>';
			}
?>
			</select>
			<input type='submit' value='Import' class='button-primary'>
		</div>
<?php
		} else {
			echo "<br /><br />" . __('There was an error while uploading your csv file.', 'wpsc');
		}
	}
	if ( isset( $_POST['csv_action'] ) && ('import' == $_POST['csv_action']) ) {
		global $wpdb;
		$cvs_data = $_SESSION['cvs_data'];
		$column_data = $_POST['column'];
		$value_data = $_POST['value_name'];
		
		$status = esc_attr($_POST['post_status']);
		
		$name = array( );
		foreach ( $value_data as $key => $value ) {

			$cvs_data2[$value] = $cvs_data[$key];
		}
		$num = count( $cvs_data2['name'] );

		for ( $i = 0; $i < $num; $i++ ) {
			$product_columns = array(
				'post_title' => esc_attr( $cvs_data2['name'][$i] ),
				'content' => esc_attr( $cvs_data2['description'][$i] ),
				'additional_description' => esc_attr( $cvs_data2['additional_description'][$i] ),
				'price' => esc_attr( str_replace( '$', '', $cvs_data2['price'][$i] ) ),
				'weight' => esc_attr( $cvs_data2['weight'][$i] ),
				'weight_unit' => esc_attr( $cvs_data2['weight_unit'][$i] ),
				'pnp' => null,
				'international_pnp' => null,
				'file' => null,
				'image' => '0',
				'quantity_limited' => esc_attr( $cvs_data2['quantity_limited'][$i] ),
				'quantity' => esc_attr( $cvs_data2['quantity'][$i] ),
				'special' => null,
				'special_price' => null,
				'display_frontpage' => null,
				'notax' => null,
				'active' => null,
				'donation' => null,
				'no_shipping' => null,
				'thumbnail_image' => null,
				'thumbnail_state' => null,
				'meta' => array(
					'_wpsc_price' => esc_attr( str_replace( '$', '', $cvs_data2['price'][$i] ) ),
					'_wpsc_sku' => esc_attr( $cvs_data2['sku'][$i] ),
					'_wpsc_stock' => esc_attr( $cvs_data2['quantity'][$i] ),
					'_wpsc_limited_stock' => esc_attr( $cvs_data2['quantity_limited'][$i] ),
					'_wpsc_product_metadata' => array(
						'weight' => esc_attr( $cvs_data2['weight'][$i] ),
						'weight_unit' => esc_attr( $cvs_data2['weight_unit'][$i] ),
					)
				)
			);
			$product_columns = wpsc_sanitise_product_forms( $product_columns );
			// status needs to be set here because wpsc_sanitise_product_forms overwrites it :/
			$product_columns['post_status'] = $status;
			$product_id = wpsc_insert_product( $product_columns );
			wp_set_object_terms( $product_id , array( (int)$_POST['category'] ) , 'wpsc_product_category' );
		}
		echo "<br /><br />". sprintf(__("Success, your <a href='%s'>products</a> have been upload.", "wpsc"), admin_url('edit.php?post_type=wpsc-product'));
	}
?>
		</div>
	</form>
<?php
}
?>
