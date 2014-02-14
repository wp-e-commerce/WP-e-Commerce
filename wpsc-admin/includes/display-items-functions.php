<?php
/**
 * WPSC Product form generation functions
 *
 * @package wp-e-commerce
 * @since 3.7
 */

global $wpsc_product_defaults;
$wpsc_product_defaults = array(
	'id' => '0',
	'name' => '',
	'description' => '',
	'additional_description' => '',
	'price' => '0.00',
	'weight' => '0',
	'weight_unit' => 'pound',
	'pnp' => '0.00',
	'international_pnp' => '0.00',
	'file' => '0',
	'image' => '',
	'category' => '0',
	'brand' => '0',
	'quantity_limited' => '0',
	'quantity' => '0',
	'special' => '0',
	'special_price' => 0.00,
	'display_frontpage' => '0',
	'notax' => '0',
	'publish' => '1',
	'active' => '1',
	'donation' => '0',
	'no_shipping' => '0',
	'thumbnail_image' => '',
	'thumbnail_state' => '1',
	'meta' =>
	array(
		'external_link' => NULL,
		'external_link_text' => NULL,
		'external_link_target' => NULL,
		'merchant_notes' => NULL,
		'sku' => NULL,
		'engrave' => '0',
		'can_have_uploaded_image' => '0',
		'table_rate_price' =>
		array(
			'quantity' =>
			array(
				0 => '',
			),
			'table_price' =>
			array(
				0 => '',
			),
		),
	),
);
add_action( 'admin_head', 'wpsc_css_header' );

function wpsc_redirect_variation_update( $location, $post_id ) {
	global $post;
	if ( $post->post_parent > 0 && 'wpsc-product' == $post->post_type )
		wp_redirect( admin_url( 'post.php?post='.$post->post_parent.'&action=edit' ) );
	else
		return $location;

}

add_filter( 'redirect_post_location', 'wpsc_redirect_variation_update', 10, 2 );
function wpsc_css_header() {
	global $post_type;
?>
	<style type="text/css">
	<?php if ( isset( $_GET['post_type'] ) && ( 'wpsc-product' == $_GET['post_type'] ) || ( !empty( $post_type ) && 'wpsc-product' == $post_type ) ) : ?>
	#icon-edit { background:transparent url('<?php echo WPSC_CORE_IMAGES_URL.'/icon32.png';?>') no-repeat; }
	<?php endif; ?>
        </style>
        <?php
}

function wpsc_price_control_forms() {
	global $post, $wpdb, $variations_processor, $wpsc_product_defaults;
	$product_data = get_post_custom( $post->ID );
	$product_data['meta'] = maybe_unserialize( $product_data );

	foreach ( $product_data['meta'] as $meta_key => $meta_value )
		$product_data['meta'][$meta_key] = $meta_value[0];



	$product_meta = array();
	if ( !empty( $product_data["_wpsc_product_metadata"] ) )
		$product_meta = maybe_unserialize( $product_data["_wpsc_product_metadata"][0] );

	if ( isset( $product_data['meta']['_wpsc_currency'] ) )
		$product_alt_currency = maybe_unserialize( $product_data['meta']['_wpsc_currency'] );

	if ( !isset( $product_data['meta']['_wpsc_table_rate_price'] ) ) {
		$product_data['meta']['_wpsc_table_rate_price'] = $wpsc_product_defaults['meta']['table_rate_price'];
	}
	if ( isset( $product_meta['_wpsc_table_rate_price'] ) ) {
		$product_meta['table_rate_price']['state'] = 1;
		$product_meta['table_rate_price'] += $product_meta['_wpsc_table_rate_price'];
		$product_data['meta']['_wpsc_table_rate_price'] = $product_meta['_wpsc_table_rate_price'];
	}


	if ( !isset( $product_data['meta']['_wpsc_is_donation'] ) )
		$product_data['meta']['_wpsc_is_donation'] = $wpsc_product_defaults['donation'];

	if ( !isset( $product_meta['table_rate_price']['state'] ) )
		$product_meta['table_rate_price']['state'] = null;

	if ( !isset( $product_meta['table_rate_price']['quantity'] ) )
		$product_meta['table_rate_price']['quantity'] = $wpsc_product_defaults['meta']['table_rate_price']['quantity'][0];

	if ( !isset( $product_data['meta']['_wpsc_price'] ) )
		$product_data['meta']['_wpsc_price'] = $wpsc_product_defaults['price'];

	if ( !isset( $product_data['special'] ) )
		$product_data['special'] = $wpsc_product_defaults['special'];

	if ( !isset( $product_data['meta']['_wpsc_special_price'] ) )
		$product_data['meta']['_wpsc_special_price'] = $wpsc_product_defaults['special_price'];

	$product_data['meta']['_wpsc_special_price'] = wpsc_format_number(
		$product_data['meta']['_wpsc_special_price']
	);

	if ( ! isset( $product_data['meta']['_wpsc_price'] ) )
		$product_data['meta']['_wpsc_price'] = 0;

	$product_data['meta']['_wpsc_price'] = wpsc_format_number(
		$product_data['meta']['_wpsc_price']
	);

	$currency_data = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_CURRENCY_LIST . "` ORDER BY `country` ASC", ARRAY_A );

	/* Get country name and symbol */
	$currency_type = get_option( 'currency_type' );
	$country = new WPSC_Country( $currency_type );

	$ct_code = $country->get( 'code' );		// Country name
	$ct_symb = $country->get( 'symbol' );	// Country symbol

	$price 		= $product_data['meta']['_wpsc_price'];
	$sale_price = $product_data['meta']['_wpsc_special_price'];

	$wp_38 = version_compare( $GLOBALS['wp_version'], '3.8', '>=' );

	$currency_delete_class = $wp_38 ? ' dashicons dashicons-dismiss' : '';
	$currency_delete_text  = $wp_38 ? '' : 'x';
?>
		<em id="wpsc_product_price_metabox_live_title" class="wpsc_metabox_live_title">
			<p>&nbsp;<?php esc_html_e($ct_symb); ?><span><?php esc_html_e($sale_price) ?></span></p>
			<del><?php esc_html_e($ct_symb); ?><span><?php esc_html_e($price) ?></span></del>
		</em>
        <input type="hidden" id="parent_post" name="parent_post" value="<?php echo $post->post_parent; ?>" />
        <?php /* Lots of tedious work is avoided with this little line. */ ?>
        <input type="hidden" id="product_id" name="product_id" value="<?php echo $post->ID; ?>" />

    	<?php /* Check product if a product has variations */ ?>
    	<?php if ( wpsc_product_has_children( $post->ID ) ) : ?>
    		<?php $price = wpsc_product_variation_price_from( $post->ID ); ?>
			<p style="margin-top: 6px;"><?php echo sprintf( __( 'This product has variations. To edit the price please use the <a href="%s">Variation Controls</a>.' , 'wpsc'  ), '#wpsc_product_variation_forms' ); ?></p>
			<p><?php printf( __( 'Price: %s and above.' , 'wpsc' ) , $price ); ?></p>
		<?php else: ?>

    	<div class='wpsc_floatleft' style="width:85px;">
    		<label><?php esc_html_e( 'Price '. $ct_code .' ' . $ct_symb, 'wpsc' ); ?></label>
			<input 	id = "wpsc_price"
					type="number" size='10'
					min="0" step="0.10"
					name="meta[_wpsc_price]"
					style="width:80px;"
					value="<?php echo esc_attr( $product_data['meta']['_wpsc_price'] );  ?>"
					onChange="wpsc_update_price_live_preview()" />
		</div>

		<div 	class='wpsc_floatleft'
				style='display:<?php if ( ( $product_data['special'] == 1 ) ? 'block' : 'none'
	); ?>; width:85px; margin-left:30px;'>
			<label for='add_form_special'><?php esc_html_e( 'Sale Price '.$ct_code.' '.$ct_symb, 'wpsc' ); ?></label>
			<input 	id = "wpsc_sale_price"
					type="number" size='10'
					min="0" step="0.10"
					style="width:80px;"
					value="<?php echo esc_attr( $product_data['meta']['_wpsc_special_price'] ); ?>"
					name='meta[_wpsc_special_price]'
					onChange="wpsc_update_price_live_preview()" />
		</div>

		<div class="wpsc-currency-layers">
			<table>
				<thead>
					<tr>
						<th class="type" colspan="2"><?php esc_html_e( 'Alternative Currencies:', 'wpsc' ); ?></th>
						<th class="price"><?php esc_html_e( 'Price:', 'wpsc' ); ?></th>
					<tr>
				</thead>
				<tbody>
					<?php
					if ( isset( $product_alt_currency ) && is_array( $product_alt_currency ) ) :
						$i = 0;
						foreach ( $product_alt_currency as $iso => $alt_price ) :
							$i++;
							?>
							<tr class="wpsc_additional_currency">
								<td class="remove"><a href="#" class="wpsc_delete_currency_layer<?php echo $currency_delete_class; ?>" rel="<?php echo $iso; ?>"><?php if ( ! $wp_38 ) : ?><img src="<?php echo WPSC_CORE_IMAGES_URL; ?>/cross.png" /><?php endif; ?></a></td>
								<td>
									<select name="newCurrency[]" class="newCurrency">
										<?php foreach ( $currency_data as $currency ) : ?>
											<option value="<?php echo $currency['id']; ?>" <?php selected( $iso, $currency['isocode'] ); ?>>
												<?php echo htmlspecialchars( $currency['country'] ); ?> (<?php echo $currency['currency']; ?>)
											</option>
										<?php endforeach; ?>
									</select>
								</td>
								<td><input type="number" min="0" step="0.1" class="newCurrPrice text" size="8" name="newCurrPrice[]" value="<?php echo $alt_price; ?>" /></td>
							</tr>
							<?php
						endforeach;
					endif;
					?>
					<tr id="wpsc_currency_row_template" class="template hidden">
						<td class="remove"><a href="#" class="wpsc_delete_currency_layer<?php echo $currency_delete_class; ?>"><?php echo $currency_delete_text; ?></a></td>
						<td>
							<select name="newCurrency[]" class="newCurrency">
								<?php foreach ( (array)$currency_data as $currency ) { ?>
									<option value="<?php echo $currency['id']; ?>">
										<?php echo esc_html( $currency['country'] ); ?>
									</option>
								<?php } ?>
							</select>
						</td>
						<td><input type="number" min="0" step="0.1" class="newCurrPrice text" size="8" name="newCurrPrice[]" value="0.00" /></td>
					</tr>
				</tbody>
			</table>
			<a href="#wpsc_currency_row_template" class="button button-small wpsc_add_new_currency"><?php esc_html_e( 'Add a Currency Option', 'wpsc' ); ?></a>
		</div>

		<div class="wpsc-quantity-discounts">
			<table>
				<thead>
					<tr>
						<th class="qty" colspan="2"><?php esc_html_e( 'Quantity:', 'wpsc' ); ?></th>
						<th class="curr"><span class="hidden"><?php esc_html_e( 'Currency:', 'wpsc' ); ?><span></th>
						<th class="price"><?php esc_html_e( 'Price:', 'wpsc' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					if ( count( $product_meta['table_rate_price']['quantity'] ) > 0 ) {
						foreach ( (array)$product_meta['table_rate_price']['quantity'] as $key => $quantity ) {
							if ( $quantity != '' ) {
								$table_price = number_format( $product_meta['table_rate_price']['table_price'][ $key ], 2, '.', '' );
								?>
								<tr>
									<td class="remove"><a href="#" class="remove_line<?php echo $currency_delete_class; ?>"><?php echo $currency_delete_text; ?></a></td>
									<td class="qty">
										<input type="text" size="5" value="<?php echo $quantity; ?>" name="table_rate_price[quantity][]" />
										<span class="description"><?php esc_html_e( 'and above', 'wpsc' ); ?></span>
									</td>
									<td class="curr"><?php echo $ct_code . ' ' . $ct_symb; ?></td>
									<td><input type="number" size="10" min="0" step="0.1" class="newCurrPrice text" value="<?php echo $table_price; ?>" name="table_rate_price[table_price][]" /></td>
								</tr>
								<?php
							}
						}
					}
					?>
					<tr id="wpsc_quantity_discount_row_template" class="template hidden">
						<td class="remove"><a href="#" class="remove_line<?php echo $currency_delete_class; ?>"><?php echo $currency_delete_text; ?></a></td>
						<td class="qty">
							<input type="number" size="5" min="0" step="1" value="0" name="table_rate_price[quantity][]" />
							<?php esc_html_e( '+', 'wpsc' ); ?>
						</td>
						<td class="curr"><?php echo $ct_code . ' ' . $ct_symb; ?></td>
						<td><input type="number" size="10" min="0" step="0.1" class="newCurrPrice text" value="0" name="table_rate_price[table_price][]" /></td>
					</tr>
				</tbody>
			</table>
			<a href="#wpsc_quantity_discount_row_template" class="add_level button button-small"><?php esc_html_e( 'Add a Quantity Discount', 'wpsc' ); ?></a>
		</div>

		<input id="add_form_donation" type="checkbox" name="meta[_wpsc_is_donation]" value="yes" <?php checked( $product_data['meta']['_wpsc_is_donation'], 1 ); ?> />
		<label for="add_form_donation"><?php _e( 'Purchase is a donation.', 'wpsc' ) ?></label>

				<?php endif; ?>
<?php
}
function wpsc_stock_control_forms() {
	global $post, $wpdb, $variations_processor, $wpsc_product_defaults;

	$product_data = get_post_custom( $post->ID );
	$product_data['meta'] = maybe_unserialize( $product_data );

	foreach ( $product_data['meta'] as $meta_key => $meta_value )
		$product_data['meta'][$meta_key] = $meta_value[0];

	$product_meta = array();
	if ( !empty( $product_data["_wpsc_product_metadata"] ) )
		$product_meta = maybe_unserialize( $product_data["_wpsc_product_metadata"][0] );

	// this is to make sure after upgrading to 3.8.9, products will have
	// "notify_when_none_left" enabled by default if "unpublish_when_none_left"
	// is enabled.
	if ( !isset( $product_meta['notify_when_none_left'] ) ) {
		$product_meta['notify_when_none_left'] = 0;
		if ( ! empty( $product_meta['unpublish_when_none_left'] ) )
			$product_meta['notify_when_none_left'] = 1;
	}

	if ( !isset( $product_meta['unpublish_when_none_left'] ) )
		$product_meta['unpublish_when_none_left'] = '';

	// Display live title if stock is set
	if ( isset( $product_data['meta']['_wpsc_stock'] ) && is_numeric( $product_data['meta']['_wpsc_stock'] )){
		$live_title = '<em id="wpsc_product_stock_metabox_live_title" class="wpsc_metabox_live_title">';
			$live_title .= '<p><span>'.$product_data['meta']['_wpsc_stock'].'</span> in stock</p>';
		$live_title .= '</em>';

		echo $live_title;
	}

	if ( ! empty( $product_meta['unpublish_when_none_left'] ) && ! isset( $product_meta['notify_when_none_left'] ) )
?>
        <label for="wpsc_sku"><abbr title="<?php esc_attr_e( 'Stock Keeping Unit', 'wpsc' ); ?>"><?php esc_html_e( 'SKU:', 'wpsc' ); ?></abbr></label>
<?php
	if ( !isset( $product_data['meta']['_wpsc_sku'] ) )
		$product_data['meta']['_wpsc_sku'] = $wpsc_product_defaults['meta']['sku']; ?><br />
			<input size='32' type='text' class='text' id="wpsc_sku" name='meta[_wpsc_sku]' value='<?php echo esc_html( $product_data['meta']['_wpsc_sku'] ); ?>' />
			<br style="clear:both" />
			<?php
	if ( !isset( $product_data['meta']['_wpsc_stock'] ) )
		$product_data['meta']['_wpsc_stock'] = ''; ?>
			<br /><input class='limited_stock_checkbox' id='add_form_quantity_limited' type='checkbox' value='yes' <?php if ( is_numeric( $product_data['meta']['_wpsc_stock'] ) ) echo 'checked="checked"'; else echo ''; ?> name='meta[_wpsc_limited_stock]' />
			<label for='add_form_quantity_limited' class='small'><?php esc_html_e( 'Product has limited stock', 'wpsc' ); ?></label>
			<?php
	if ( $post->ID > 0 ) {
		if ( is_numeric( $product_data['meta']['_wpsc_stock'] ) ) {?>
					<div class='edit_stock' style='display: block;'> <?php
		} else { ?>
					<div class='edit_stock' style='display: none;'><?php
		} ?>
					<?php if ( wpsc_product_has_children( $post->ID ) ) : ?>
			    		<?php $stock = wpsc_variations_stock_remaining( $post->ID ); ?>
						<p><?php echo sprintf( __( 'This product has variations. To edit the quantity please use the <a href="%s">Variation Controls</a> below.' , 'wpsc' ), '#wpsc_product_variation_forms' ); ?></p>
						<p><?php printf( _n( "%s variant item in stock.", "%s variant items in stock.", $stock, 'wpsc' ), $stock ); ?></p>
					<?php else: ?>
						<div style="margin-bottom:20px;">
							<label for="stock_limit_quantity"><?php esc_html_e( 'Quantity in stock', 'wpsc' ); ?></label>
							<input 	type='number' min="0" step="1" style="width:80px; margin-left:50px;"
									id="stock_limit_quantity" name='meta[_wpsc_stock]'
									size='3' value='<?php echo $product_data['meta']['_wpsc_stock']; ?>'
									class='stock_limit_quantity'
									onChange="wpsc_push_v2t(this, '#wpsc_product_stock_metabox_live_title>p>span')" />
						</div>

						<?php
							$remaining_quantity = wpsc_get_remaining_quantity( $post->ID );
							$reserved_quantity = $product_data['meta']['_wpsc_stock'] - $remaining_quantity;
							if($reserved_quantity) {
								echo '<p><em>';
								printf(_n('%s of them is reserved for pending or recently completed orders.',
										  '%s of them are reserved for pending or recently completed orders.',
										  $reserved_quantity, 'wpsc'), $reserved_quantity);

								echo '</em></p>';
							}
						?>
					<?php endif; ?>

						<p><?php esc_html_e( 'When stock reduces to zero:', 'wpsc' ); ?></p>
						<div class='notify_when_none_left'>
							<input 	type='checkbox' id="notify_when_oos"
									name='meta[_wpsc_product_metadata][notify_when_none_left]'
									class='notify_when_oos'<?php checked( $product_meta['notify_when_none_left'] ); ?> />
							<label for="notify_when_oos"><?php esc_html_e( 'Notify site owner via email', 'wpsc' ); ?></label>
						</div>
						<div class='unpublish_when_none_left'>
							<input 	type='checkbox' id="unpublish_when_oos"
									name='meta[_wpsc_product_metadata][unpublish_when_none_left]'
									class='unpublish_when_oos'<?php checked( $product_meta['unpublish_when_none_left'] ); ?> />
							<label for="unpublish_when_oos"><?php esc_html_e( 'Unpublish product from website', 'wpsc' ); ?></label>

						</div>
				</div> <?php
	} else { ?>
				<div style='display: none;' class='edit_stock'>
					 <?php esc_html_e( 'Stock Qty', 'wpsc' ); ?><input type='text' name='meta[_wpsc_stock]' value='0' size='10' />
					<div style='font-size:9px; padding:5px;'>
						<input type='checkbox' class='notify_when_oos' name='meta[_wpsc_product_metadata][notify_when_none_left]' /> <?php esc_html_e( 'Email site owner if this Product runs out of stock', 'wpsc' ); ?>
						<input type='checkbox' class='unpublish_when_oos' name='meta[_wpsc_product_metadata][unpublish_when_none_left]' /> <?php esc_html_e( 'Set status to Unpublished if this Product runs out of stock', 'wpsc' ); ?>
					</div>
				</div><?php
	}
?>
<?php
}
function wpsc_product_taxes_forms() {
	global $post, $wpdb, $wpsc_product_defaults;
	$product_data = get_post_custom( $post->ID );

	$product_data['meta'] = $product_meta = array();
	if ( !empty( $product_data['_wpsc_product_metadata'] ) )
		$product_data['meta'] = $product_meta = maybe_unserialize( $product_data['_wpsc_product_metadata'][0] );

	if ( !isset( $product_data['meta']['_wpsc_custom_tax'] ) )
		$product_data['meta']['_wpsc_custom_tax'] = '';
	$custom_tax = $product_data['meta']['_wpsc_custom_tax'];


	if ( !isset( $product_meta['custom_tax'] ) ) {
		$product_meta['custom_tax'] = 0.00;
	}

	//Add New WPEC-Taxes Bands Here
	$wpec_taxes_controller = new wpec_taxes_controller();

	//display tax bands
	$band_select_settings = array(
		'id' => 'wpec_taxes_band',
		'name' => 'meta[_wpsc_product_metadata][wpec_taxes_band]',
		'label' => __( 'Custom Tax Band', 'wpsc' )
	);
	$wpec_taxes_band = '';
	if ( isset( $product_meta['wpec_taxes_band'] ) ) {
		$band = $wpec_taxes_controller->wpec_taxes->wpec_taxes_get_band_from_index( $product_meta['wpec_taxes_band'] );
		$wpec_taxes_band = array( 'index'=>$band['index'], 'name'=>$band['name'] );
	}

	$taxable_checkbox_settings = array(
		'type' => 'checkbox',
		'id' => 'wpec_taxes_taxable',
		'name' => 'meta[_wpsc_product_metadata][wpec_taxes_taxable]',
		'label' => __( 'Product is exempt from taxation.', 'wpsc' )
	);

	if ( isset( $product_meta['wpec_taxes_taxable'] ) && 'on' == $product_meta['wpec_taxes_taxable'] ) {
		$taxable_checkbox_settings['checked'] = 'checked';
	}

	//add taxable amount only for exclusive tax
	if ( !$wpec_taxes_controller->wpec_taxes_isincluded() ) {
		$taxable_amount_input_settings = array(
			'id' => 'wpec_taxes_taxable_amount',
			'name' => 'meta[_wpsc_product_metadata][wpec_taxes_taxable_amount]',
			'label' => __( 'Taxable Amount', 'wpsc' ),
			'description' => __( 'Taxable amount in your currency, not percentage of price.', 'wpsc' ),
		);

		if ( isset( $product_meta['wpec_taxes_taxable_amount'] ) ) {
			$taxable_amount_input_settings['value'] = $product_meta['wpec_taxes_taxable_amount'];

			if ( ! empty( $product_meta['wpec_taxes_taxable_amount'] ) )
				$taxable_amount_input_settings['value'] = wpsc_format_number(
					$taxable_amount_input_settings['value']
				);
		}
	}// if

	$output = '<a name="wpsc_tax"></a>';
	$output .= '<p>'.$wpec_taxes_controller->wpec_taxes_build_input( $taxable_checkbox_settings ).'</p>';
	$output .= '<p>'.$wpec_taxes_controller->wpec_taxes_display_tax_bands( $band_select_settings, $wpec_taxes_band ).'</p>';
	$output .= '<p>';
		$output .= 	( !$wpec_taxes_controller->wpec_taxes_isincluded() ) ? $wpec_taxes_controller->wpec_taxes_build_input( $taxable_amount_input_settings ) : '';
	$output .= '</p>';

	echo $output;
}

function wpsc_product_variation_forms() {
	?>
	<iframe src="<?php echo _wpsc_get_product_variation_form_url(); ?>"></iframe>
	<?php
}

function _wpsc_get_product_variation_form_url( $id = false ) {
	if ( ! $id )
		$id = get_the_ID();
	return admin_url( 'admin-ajax.php?action=wpsc_product_variations_table&product_id=' . $id . '&_wpnonce=' . wp_create_nonce( 'wpsc_product_variations_table' ) );
}

function wpsc_product_shipping_forms_metabox() {
	wpsc_product_shipping_forms();
}

/**
 * Dimension Units
 *
 * @since   3.8.13
 *
 * @return  array  List of valid dimension units.
 */
function wpsc_dimension_units() {
	return array(
		'in'    => __( 'inches', 'wpsc' ),
		'cm'    => __( 'cm', 'wpsc' ),
		'meter' => __( 'meters', 'wpsc' )
	);
}

/**
 * Weight Units
 *
 * @since   3.8.13
 *
 * @return  array  List of valid weight units.
 */
function wpsc_weight_units() {
	return array(
		'pound'    => __( 'pounds', 'wpsc' ),
		'ounce'    => __( 'ounces', 'wpsc' ),
		'gram'     => __( 'grams', 'wpsc' ),
		'kilogram' => __( 'kilograms', 'wpsc' )
	);
}

/**
 * Weight Unit Display
 *
 * Returns a weight unit abbreviation for display.
 *
 * @since   3.8.13
 *
 * @param   string  $unit  Weight unit.
 * @return  string         Weight unit string.
 */
function wpsc_weight_unit_display( $unit ) {
	switch ( $unit ) {
		case 'pound' :
			return __( ' lbs.', 'wpsc' );
		case 'ounce' :
			return __( ' oz.', 'wpsc' );
		case 'gram' :
			return __( ' g', 'wpsc' );
		case 'kilograms' :
		case 'kilogram' :
			return __( ' kgs.', 'wpsc' );
	}
	return '';
}

/**
 * Validate Dimension Unit
 *
 * Returns a valid dimensions unit.
 * If the unit is not set or invalid it will be filtered using 'wpsc_default_dimension_unit'
 * so that an alternative default unit can be set.
 *
 * @since   3.8.13
 *
 * @param   string  $unit  Dimension unit.
 * @return  string         Dimension unit string.
 *
 * @uses    wpsc_default_dimension_unit
 */
function wpsc_validate_dimension_unit( $unit = '' ) {
	$default_unit = apply_filters( 'wpsc_default_dimension_unit', $unit );
	if ( empty( $unit ) && array_key_exists( $default_unit, wpsc_dimension_units() ) )
		$unit = $default_unit;
	return $unit;
}

/**
 * Validate Weight Unit
 *
 * Returns a valid weight unit.
 * If the unit is not set or invalid it will be filtered using 'wpsc_default_weight_unit'
 * so that an alternative default unit can be set.
 *
 * @since   3.8.13
 *
 * @param   string  $unit  Weight unit.
 * @return  string         Weight unit string.
 *
 * @uses    wpsc_default_weight_unit
 */
function wpsc_validate_weight_unit( $unit = '' ) {
	$default_unit = apply_filters( 'wpsc_default_weight_unit', $unit );
	if ( empty( $unit ) && array_key_exists( $default_unit, wpsc_weight_units() ) )
		$unit = $default_unit;
	return $unit;
}

/**
 * Product Shipping Forms
 *
 * @uses  wpsc_validate_weight_unit()
 * @uses  wpsc_validate_dimension_unit()
 */
function wpsc_product_shipping_forms( $product = false, $field_name_prefix = 'meta[_wpsc_product_metadata]', $bulk = false ) {
	if ( ! $product )
		$product_id = get_the_ID();
	else
		$product_id = $product->ID;

	$meta = get_post_meta( $product_id, '_wpsc_product_metadata', true );
	if ( ! is_array( $meta ) )
		$meta = array();

	$defaults = array(
		'weight'            => '',
		'weight_unit'       => wpsc_validate_weight_unit(),
		'demension_unit'    => wpsc_validate_dimension_unit(),
		'dimensions'        => array(),
		'shipping'          => array(),
		'no_shipping'       => '',
		'display_weight_as' => '',
	);
	$dimensions_defaults = array(
		'height' => 0,
		'width'  => 0,
		'length' => 0,
	);
	$shipping_defaults = array(
		'local'         => '',
		'international' => '',
	);
	$meta = array_merge( $defaults, $meta );
	$meta['dimensions'] = array_merge( $dimensions_defaults, $meta['dimensions'] );
	$meta['shipping'] = array_merge( $shipping_defaults, $meta['shipping'] );

	extract( $meta, EXTR_SKIP );

	foreach ( $shipping as $key => &$val ) {
		$val = wpsc_format_number( $val );
  	}

	$weight = wpsc_convert_weight( $weight, 'pound', $weight_unit );

	$dimension_units = wpsc_dimension_units();
	$weight_units = wpsc_weight_units();

	// Why we need this????
	$measurements = $dimensions;
	$measurements['weight'] = $weight;
	$measurements['weight_unit'] = $weight_unit;
	// End why

?>
	<div class="wpsc-stock-editor<?php if ( $bulk ) echo ' wpsc-bulk-edit' ?>">
		<p class="wpsc-form-field">
			<input type="checkbox" name="<?php echo $field_name_prefix ?>[no_shipping]" value="1" <?php checked( $no_shipping && ! $bulk ); ?>>
			<label><?php esc_html_e( 'Product will be shipped to customer', 'wpsc' ); ?></label>
		</p>

		<div class="wpsc-product-shipping-section wpsc-product-shipping-weight-dimensions">
			<p><strong><?php esc_html_e( 'Calculate Shipping Costs based on measurements', 'wpsc' ); ?></strong></p>

			<!-- WEIGHT INPUT -->
			<p class="wpsc-form-field">
				<?php if ( $bulk ) : ?>
					<input class="wpsc-bulk-edit-fields" type="checkbox" name="wpsc_bulk_edit[fields][measurements][weight]" value="1" />
				<?php endif; ?>
				<label for="wpsc-product-shipping-weight"><?php echo esc_html_e( 'Weight', 'wpsc' ); ?></label>
				<span class="wpsc-product-shipping-input">
					<input type="number" min="0" step="0.1" id="wpsc-product-shipping-weight" name="<?php echo $field_name_prefix; ?>[weight]" value="<?php if ( ! $bulk ) echo esc_attr( wpsc_format_number( $weight ) ); ?>" />
					<select id="wpsc-product-shipping-weight-unit" name="<?php echo $field_name_prefix; ?>[weight_unit]">
							<?php foreach ( $weight_units as $unit => $unit_label ): ?>
								<option value="<?php echo $unit; ?>" <?php if ( ! $bulk ) selected( $unit, $measurements['weight_unit'] ); ?>><?php echo esc_html( $unit_label ); ?></option>
							<?php endforeach; ?>
						</select>
				</span>
			</p>
			<!-- END WEIGHT INPUT -->

			<!-- DIMENSIONS INPUT -->
			<p class="wpsc-form-field">
				<?php if ( $bulk ) : ?>
					<input class="wpsc-bulk-edit-fields" type="checkbox" name="wpsc_bulk_edit[fields][measurements][dimensions]" value="1" />
				<?php endif; ?>
				<label for="wpsc-product-shipping-weight"><?php echo esc_html_e( 'Dimensions', 'wpsc' ); ?></label>
				<span class="wpsc-product-shipping-input">
					<input type="number" min="0" step="0.1" placeholder="L" id="wpsc-product-shipping-length" name="<?php echo $field_name_prefix; ?>[dimensions][length]" value="<?php if ( !$bulk && $dimensions['length']>0 ) echo esc_attr( wpsc_format_number( $dimensions['length'] ) ); ?>" />&nbsp;&times;&nbsp;
					<input type="number" min="0" step="0.1" placeholder="W" id="wpsc-product-shipping-width" name="<?php echo $field_name_prefix; ?>[dimensions][width]" value="<?php if ( !$bulk && $dimensions['width']>0 ) echo esc_attr( wpsc_format_number( $dimensions['width'] ) ); ?>" />&nbsp;&times;&nbsp;
					<input type="number" min="0" step="0.1" placeholder="H" id="wpsc-product-shipping-height" name="<?php echo $field_name_prefix; ?>[dimensions][height]" value="<?php if ( !$bulk && $dimensions['height']>0 ) echo esc_attr( wpsc_format_number( $dimensions['height'] ) ); ?>" />
					<select id="wpsc-product-shipping-dimensions-unit" name="<?php echo $field_name_prefix; ?>[dimension_unit]">
						<?php foreach ( $dimension_units as $unit => $unit_label ): ?>
							<option value="<?php echo $unit; ?>" <?php if ( ! $bulk && isset( $meta['dimension_unit'] ) ) selected( $unit, $meta['dimension_unit'] ); // Dirty code ?>><?php echo esc_html( $unit_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</span>
			</p>
			<!-- END DEMENSION INPUT -->

		</div>

		<?php
			$currency_type = get_option( 'currency_type' );
			$country = new WPSC_Country( $currency_type );

			$ct_symb = $country->get( 'symbol' );
		?>

		<div class="wpsc-product-shipping-section wpsc-product-shipping-flat-rate">
			<p><strong><?php esc_html_e( 'Flat Rate Settings', 'wpsc' ); ?></strong></p>
			<p class="wpsc-form-field">
				<?php if ( $bulk ): ?>
					<input class="wpsc-bulk-edit-fields" type="checkbox" name="wpsc_bulk_edit[fields][shipping][local]" value="1" />
				<?php endif; ?>
				<label for="wpsc-product-shipping-flatrate-local"><?php esc_html_e( 'Local Shipping Fee', 'wpsc' ); ?></label>
				<span>
					<?php echo $ct_symb; ?>
					<input type="number" min="0" step="0.1" id="wpsc-product-shipping-flatrate-local" name="<?php echo $field_name_prefix; ?>[shipping][local]" value="<?php if ( ! $bulk ) echo $shipping['local']; ?>"  />
				</span>
			</p>
			<p class="wpsc-form-field">
				<?php if ( $bulk ): ?>
					<input class="wpsc-bulk-edit-fields" type="checkbox" name="wpsc_bulk_edit[fields][shipping][international]" value="1" />
				<?php endif; ?>
				<label for="wpsc-product-shipping-flatrate-international"><?php esc_html_e( 'International Shipping Fee', 'wpsc' ); ?></label>
				<span>
					<?php echo $ct_symb; ?>
					<input type="number" min="0" step="0.1" id="wpsc-product-shipping-flatrate-international" name="<?php echo $field_name_prefix; ?>[shipping][international]" value="<?php if ( ! $bulk ) echo $shipping['international']; ?>"  />
				</span>
			</p>
		</div>
	</div>
<?php
}

// aka custom meta form
function wpsc_product_advanced_forms() {
	global $post, $wpdb, $variations_processor, $wpsc_product_defaults;
	$product_data = get_post_custom( $post->ID );

	$product_data['meta'] = $product_meta = array();
	if ( !empty( $product_data['_wpsc_product_metadata'] ) )
		$product_data['meta'] = $product_meta = maybe_unserialize( $product_data['_wpsc_product_metadata'][0] );

	$delete_nonce = _wpsc_create_ajax_nonce( 'remove_product_meta' );

	$custom_fields = $wpdb->get_results( "
		SELECT
			`meta_id`, `meta_key`, `meta_value`
		FROM
			`{$wpdb->postmeta}`
		WHERE
			`post_id` = {$post->ID}
		AND
			`meta_key` NOT LIKE '\_%'
		ORDER BY
			LOWER(meta_key)", ARRAY_A
	);
	if( !isset( $product_meta['engraved'] ) )
		$product_meta['engraved'] = '';

	if( !isset( $product_meta['can_have_uploaded_image'] ) )
		$product_meta['can_have_uploaded_image'] = '';

	$output = '<table id="wpsc_product_meta_table" class="wp-list-table widefat posts">';
		$output .= '<thead>';
			$output .= '<tr>';
				$output .= '<th id="wpsc_custom_meta_name_th">' . _x( 'Name', 'Product meta UI', 'wpsc' ) . '</th>';
				$output .= '<th id="wpsc_custom_meta_value_th">' . _x( 'Value', 'Product meta UI', 'wpsc' ) . '</th>';
				$output .= '<th id="wpsc_custom_meta_action_th">' . _x( 'Action', 'Product meta UI', 'wpsc' ) . '</th>';
			$output .= '</tr>';
		$output .= '</thead>';
		$output .= '<tfoot>';
			$output .= '<tr>';
				$output .= '<th>' . _x( 'Name', 'Product meta UI', 'wpsc' ) . '</th>';
				$output .= '<th>' . _x( 'Value', 'Product meta UI', 'wpsc' ) . '</th>';
				$output .= '<th>' . _x( 'Action', 'Product meta UI', 'wpsc' ) . '</th>';
			$output .= '</tr>';
		$output .= '</tfood>';

		$output .= '<tbody>';

		// Display all available metadata
		$alternate = false;
		foreach ( (array)$custom_fields as $custom_field ) {
			$i = $custom_field['meta_id'];
			$alternate = !$alternate;

			$output .= '<tr'. ($alternate ? ' class="alternate"' : '') .'>';
				$output .= '<td><input type="text" value="'.$custom_field['meta_key'].'" name="custom_meta['.$i.'][name]" id="custom_meta_name_'.$i.'"></input></td>';
				$output .= '<td><input type="text" value="'.esc_html($custom_field['meta_value']).'" name="custom_meta['.$i.'][value]" id="custom_meta_value_'.$i.'"></input></td>';
				$output .= '<td><a href="#" data-nonce="'.esc_attr( $delete_nonce ).'" class="wpsc_remove_meta" onclick="wpsc_remove_custom_meta(this,'.$i.')">'.esc_html( 'Delete', 'wpsc' ).'</a></td>';
			$output .= '</tr>';
		}

			// Template for new metadata input
			$output .= '<tr id="wpsc_new_meta_template">';
				$output .= '<td><input type="text" name="new_custom_meta[name][]"  value=""></input></td>';
				$output .= '<td><input type="text" name="new_custom_meta[value][]" value=""></input></td>';
				$output .= '<td><a href="#" class="wpsc_remove_meta" onclick="wpsc_remove_empty_meta(this)">'.esc_html( 'Delete', 'wpsc' ).'</a></td>';
			$output .= '</tr>';

		$output .= '</tbody>';
	$output .= '</table>';

	$output .= '<a href="#" class="add_more_meta  button button-small" id="wpsc_add_custom_meta">'.esc_html( '+ Add Custom Meta', 'wpsc' ).'</a>';


	echo $output;
	return;
}


function wpsc_product_external_link_forms() {

	global $post, $wpdb, $variations_processor, $wpsc_product_defaults;
	$product_data = get_post_custom( $post->ID );

	$product_data['meta'] = $product_meta = array();
	if ( !empty( $product_data['_wpsc_product_metadata'] ) )
		$product_data['meta'] = $product_meta = maybe_unserialize( $product_data['_wpsc_product_metadata'][0] );

	// Get External Link Values
	$external_link_value        = isset( $product_meta['external_link'] ) ? $product_meta['external_link'] : '';
	$external_link_text_value   = isset( $product_meta['external_link_text'] ) ? $product_meta['external_link_text'] : '';
	$external_link_target_value = isset( $product_meta['external_link_target'] ) ? $product_meta['external_link_target'] : '';
	$external_link_target_value_selected[$external_link_target_value] = ' selected="selected"';
	if ( !isset( $external_link_target_value_selected['_self'] ) ) $external_link_target_value_selected['_self'] = '';
	if ( !isset( $external_link_target_value_selected['_blank'] ) ) $external_link_target_value_selected['_blank'] = '';

?>
        <table class="form-table" style="width: 100%;" cellspacing="2" cellpadding="5">
            <tbody>
                <tr class="form-field">
                    <th valign="top" scope="row"><label for="external_link"><?php esc_html_e( 'URL', 'wpsc' ); ?></label></th>
                    <td><input type="text" name="meta[_wpsc_product_metadata][external_link]" id="external_link" value="<?php esc_attr_e( $external_link_value ); ?>" size="50" style="width: 95%" placeholder="http://"></td>
                </tr>
                <tr class="form-field">
                    <th valign="top" scope="row"><label for="external_link_text"><?php esc_html_e( 'Label', 'wpsc' ); ?></label></th>
                    <td><input type="text" name="meta[_wpsc_product_metadata][external_link_text]" id="external_link_text" value="<?php esc_attr_e( $external_link_text_value ); ?>" size="50" style="width: 95%" placeholder="Buy Now"></td>
                </tr>
                <tr class="form-field">
                     <th valign="top" scope="row"><label for="external_link_target"><?php esc_html_e( 'Target', 'wpsc' ); ?></label></th>
                    <td id="external_link_target">
                    	<input type="radio" name="meta[_wpsc_product_metadata][external_link_target]" value="">
                    	<span><?php _ex( 'Default (set by theme)', 'External product link target', 'wpsc' ); ?></span>

                    	<input type="radio" name="meta[_wpsc_product_metadata][external_link_target]" value="_self" <?php  echo $external_link_target_value_selected['_self'] ; ?>>
                    	<span><?php esc_html_e( 'Force open in same window', 'wpsc' ); ?></span>

                    	<input type="radio" name="meta[_wpsc_product_metadata][external_link_target]" value="_blank" <?php echo $external_link_target_value_selected['_blank'] ; ?>>
                    	<span><?php esc_html_e( 'Force open in new window', 'wpsc' ); ?></span>
                    </td>
                </tr>
            </tbody>
        </table>
        <em><?php esc_html_e('This option overrides the "Buy Now" and "Add to Cart" buttons, replacing them with the link you describe here.', 'wpsc'); ?></em>
<?php
}
function wpsc_additional_desc() {
?>
    <textarea name='additional_description' id='additional_description' cols='40' rows='5' ><?php echo esc_textarea( get_post_field( 'post_excerpt', get_the_ID() ) ); ?></textarea>
    <em>Short Descriptions are optional hand-crafted summaries of your content that can be used in your theme.</em>
<?php

}

function wpsc_product_gallery( $post ) {
	$upload_iframe_src = esc_url( get_upload_iframe_src( 'image', $post->ID ) );

	$photos = wpsc_get_product_gallery($post->ID);

	$output = '<div id="wpsc_product_gallery">';
		$output .= '<ul>';

		foreach ($photos as $photo) {
			$output .= '<li>';
				$output .= '<img src="'.wp_get_attachment_thumb_url($photo->ID).'">';
			$output .= '</li>';
		}

		$output .= '</ul>';
		$output .= '<div class="clear"></div>';
	$output .= '</div>';

	$output .= '<p class="hide-if-no-js">';
		$output .= '<a class="button button-small thickbox" title="'.esc_attr('Manage Product Image Gallery...', 'wpsc').'" href="'.$upload_iframe_src.'" id="wpsc-manage-product-gallery">';
			$output .= esc_html('Manage Product Image Gallery...', 'wpsc');
		$output .= '</a>';
	$output .= '</p>';

	echo $output;
}

function wpsc_product_download_forms() {
	global $post, $wpdb, $wpsc_product_defaults;
	$product_data = get_post_custom( $post->ID );
	$output = '';
	$product_data['meta'] = $product_meta = array();
	if ( !empty( $product_data['_wpsc_product_metadata'] ) )
		$product_data['meta'] = $product_meta = maybe_unserialize( $product_data['_wpsc_product_metadata'][0] );

	$upload_max = wpsc_get_max_upload_size();
?>
	<?php echo wpsc_select_product_file( $post->ID ); ?>

	<a href="admin.php?wpsc_admin_action=product_files_existing&amp;product_id=<?php echo $post->ID; ?>" class="thickbox button button-small" title="<?php echo esc_attr( sprintf( __( 'Select all downloadable files for %s', 'wpsc' ), $post->post_title ) ); ?>"><?php esc_html_e( 'Add existing files...', 'wpsc' ); ?></a>

	<div class="wpsc_fileUpload button button-small">
		<span><?php esc_html_e('Upload new file...','wpsc'); ?></span>
		<input type='file' name='file' class="button button-small" value='' onchange="wpsc_push_v2t(this, '#wpsc_fileupload_path')" />
	</div>
	<em id="wpsc_fileupload_path"></em>

<?php
	if ( function_exists( "make_mp3_preview" ) || function_exists( "wpsc_media_player" ) ) {
?>
            <br />
            <h4><?php esc_html_e( 'Select an MP3 file to upload as a preview', 'wpsc' ) ?></h4>
            <input type='file' name='preview_file' value='' /><br />

            <h4><?php esc_html_e( 'Your preview for this product', 'wpsc' ) ?>:</h4>

	         <?php
				$args = array(
					'post_type'   => 'wpsc-preview-file',
					'post_parent' => $post->ID,
					'numberposts' => -1,
					'post_status' => 'all'
				);

			$preview_files = (array)get_posts( $args );

			foreach ($preview_files as $preview)
				echo $preview->post_title . '<br />';
			?>
            <br />
        <?php
	}
	$output = apply_filters( 'wpsc_downloads_metabox', $output );
}

function wpsc_product_personalization_forms(){
?>
	<ul id="wpsc_product_personalization_option">
		<li>
			<input type='hidden' name='meta[_wpsc_product_metadata][engraved]' value='0' />
			<input type='checkbox' name='meta[_wpsc_product_metadata][engraved]' <?php if ( isset( $product_meta['engraved'] ) ) checked( $product_meta['engraved'], '1' ); ?> id='add_engrave_text' />
			<label for='add_engrave_text'><?php esc_html_e( 'Users can personalize this product by leaving a message on single product page', 'wpsc' ); ?></label>
		</li>
		<li>
			<input type='hidden' name='meta[_wpsc_product_metadata][can_have_uploaded_image]' value='0' />
			<input type='checkbox' name='meta[_wpsc_product_metadata][can_have_uploaded_image]' <?php if ( isset( $product_meta['can_have_uploaded_image'] ) ) checked( $product_meta['can_have_uploaded_image'], '1' ); ?> id='can_have_uploaded_image' />
			<label for='can_have_uploaded_image'> <?php esc_html_e( 'Users can upload images on single product page to purchase logs.', 'wpsc' ); ?> </label>
		</li>
	</ul>
	<em><?php _e( "Form fields for the customer to personalize this product will be shown on it's single product page.", 'wpsc' ); ?></em>
<?php
}

function wpsc_product_delivery_forms(){
?>
	<em id="wpsc_product_delivery_metabox_live_title" class="wpsc_metabox_live_title">
		<p></p>
	</em>

	<div id="wpsc_product_delivery_forms" class="categorydiv wpsc-categorydiv">
		<ul id="wpsc_product_delivery_tabs" class="category-tabs">
			<li class="tabs"><a href="#wpsc_product_delivery-shipping">Shipping</a></li>
			<li><a href="#wpsc_product_delivery-download">Download</a></li>
			<li><a href="#wpsc_product_delivery-external_link">External Link</a></li>
		</ul>

		<div id="wpsc_product_delivery-shipping" class="tabs-panel" style="display: block;">
			<?php wpsc_product_shipping_forms(); ?>
		</div>

		<div id="wpsc_product_delivery-download" class="tabs-panel" style="display: none;">
			<?php wpsc_product_download_forms(); ?>
		</div>

		<div id="wpsc_product_delivery-external_link" class="tabs-panel" style="display: none;">
			<?php wpsc_product_external_link_forms(); ?>
		</div>
	</div>
<?php
}

function wpsc_product_details_forms(){
?>
	<em id="wpsc_product_details_metabox_live_title" class="wpsc_metabox_live_title">
		<p></p>
	</em>

	<div id="wpsc_product_details_forms" class="categorydiv wpsc-categorydiv">
		<ul id="wpsc_product_details_tabs"  class="category-tabs">
			<li class="tabs"><a href="#wpsc_product_details-image">Image Gallery</a></li>
			<li><a href="#wpsc_product_details-desc">Short Description</a></li>
			<li><a href="#wpsc_product_details-personalization">Personalization</a></li>
			<li><a href="#wpsc_product_details-meta">Metadata</a></li>
		</ul>

		<div id="wpsc_product_details-image" class="tabs-panel" style="display: block;">
			<?php global $post; ?>
			<?php wpsc_product_gallery($post); ?>
		</div>

		<div id="wpsc_product_details-desc" class="tabs-panel" style="display: none;">
			<?php wpsc_additional_desc(); ?>
		</div>

		<div id="wpsc_product_details-personalization" class="tabs-panel" style="display: none;">
			<?php wpsc_product_personalization_forms(); ?>
		</div>

		<div id="wpsc_product_details-meta" class="tabs-panel" style="display: none;">
			<?php wpsc_product_advanced_forms(); ?>
		</div>
	</div>
<?php
}

/**
 * Adding function to change text for media buttons
 */
function change_context( $context ) {
	$current_screen = get_current_screen();

	if ( $current_screen->id != 'wpsc-product' )
		return $context;
	return __( 'Upload Image%s', 'wpsc' );
}
function change_link( $link ) {
	global $post_ID;
	$current_screen = get_current_screen();
	if ( $current_screen && $current_screen->id != 'wpsc-product' )
		return $link;

	$uploading_iframe_ID = $post_ID;
	$media_upload_iframe_src = "media-upload.php?post_id=$uploading_iframe_ID";

	return $media_upload_iframe_src . "&amp;type=image&parent_page=wpsc-edit-products";
}
function wpsc_form_multipart_encoding() {
	echo ' enctype="multipart/form-data"';
}

add_action( 'post_edit_form_tag', 'wpsc_form_multipart_encoding' );

if ( version_compare( get_bloginfo( 'version' ), '3.5', '<' ) ) {
	add_filter( 'media_buttons_context', 'change_context' );
	add_filter( 'image_upload_iframe_src', "change_link" );
}

/*
* Modifications to Media Gallery
*/

add_filter( 'attachment_fields_to_edit', 'wpsc_attachment_fields', 11, 2 );
add_filter( 'attachment_fields_to_save', 'wpsc_save_attachment_fields', 9, 2 );
add_filter( 'gettext_with_context', 'wpsc_filter_gettex_with_context', 12, 4);

/*
 * This filter overrides string with context translations
 *
 * @param $translation The current translation
 * @param $text The text being translated
 * @param $context The domain for the translation
 * @param $domain The domain for the translation
 * @return string The translated / filtered text.
 */
function wpsc_filter_gettex_with_context( $translation, $text, $context, $domain ) {

	if ( 'Taxonomy Parent' == $context && 'Parent' == $text && isset($_GET['taxonomy']) && 'wpsc-variation' == $_GET['taxonomy'] ) {
		$translations = get_translations_for_domain( $domain );
		return $translations->translate( 'Variation Set', 'wpsc' );
		//this will never happen, this is here only for gettext to pick up the translation
		return __( 'Variation Set', 'wpsc' );
	}
	return $translation;
}

function wpsc_attachment_fields( $form_fields, $post ) {
	$out = '';

	if( isset( $_REQUEST["post_id"] ) )
		$parent_post = get_post( absint( $_REQUEST["post_id"] ) );
	else
		$parent_post = get_post( $post->post_parent );

	// check if post is set before accessing
	if ( isset( $parent_post ) && $parent_post->post_type == "wpsc-product" ) {

		//Unfortunate hack, as I'm not sure why the From Computer tab doesn't process filters the same way the Gallery does
		ob_start();
		echo '
<script type="text/javascript">

	jQuery(function(){

		jQuery("a.wp-post-thumbnail").each(function(){
			var product_image = jQuery(this).text();
			if (product_image == "' . esc_js( __( 'Use as featured image' ) ) . '") {
				jQuery(this).text("' . esc_js( __('Use as Product Thumbnail', 'wpsc') ) . '");
			}
		});

		var trash = jQuery("#media-upload a.del-link").text();

		if (trash == "' . esc_js( __( 'Delete' ) ) . '") {
			jQuery("#media-upload a.del-link").text("' . esc_js( __( 'Trash' ) ) . '");
		}


		});

</script>';
		$out .= ob_get_clean();

		$size_names = array( 'small-product-thumbnail' => __( 'Default Product Thumbnail Size', 'wpsc' ), 'medium-single-product' => __( 'Single Product Image Size', 'wpsc' ), 'full' => __( 'Full Size', 'wpsc' ) );

		$check = get_post_meta( $post->ID, '_wpsc_selected_image_size', true );
		if ( !$check )
			$check = 'medium-single-product';

		$current_size = image_get_intermediate_size( $post->ID, $check );
		$settings_width = get_option( 'single_view_image_width' );
		$settings_height = get_option( 'single_view_image_height' );

		// regenerate size metadata in case it's missing
		if ( ! $check || ( $current_size['width'] != $settings_width && $current_size['height'] != $settings_height ) ) {
			_wpsc_regenerate_thumbnail_size( $post->ID, $check );
		}

		//This loop attaches the custom thumbnail/single image sizes to this page
		foreach ( $size_names as $size => $name ) {
			$downsize = image_downsize( $post->ID, $size );
			// is this size selectable?
			$enabled = ( $downsize[3] || 'full' == $size );
			$css_id = "image-size-{$size}-{$post->ID}";
			// if this size is the default but that's not available, don't select it

			$html = "<div class='image-size-item'><input type='radio' " . disabled( $enabled, false, false ) . "name='attachments[$post->ID][wpsc_image_size]' id='{$css_id}' value='{$size}' " . checked( $size, $check, false ) . " />";

			$html .= "<label for='{$css_id}'>$name</label>";
			// only show the dimensions if that choice is available
			if ( $enabled )
				$html .= " <label for='{$css_id}' class='help'>" . sprintf( __( "(%d&nbsp;&times;&nbsp;%d)", 'wpsc' ), $downsize[1], $downsize[2] ). "</label>";

			$html .= '</div>';

			$out .= $html;
		}

		unset( $form_fields['post_excerpt'], $form_fields['image_url'], $form_fields['post_content'], $form_fields['post_title'], $form_fields['url'], $form_fields['align'], $form_fields['image_alt']['helps'], $form_fields["image-size"] );
		$form_fields['image_alt']['helps'] =  __( 'Alt text for the product image, e.g. &#8220;Rockstar T-Shirt&#8221;', 'wpsc' );

		$form_fields["wpsc_image_size"] = array(
			'label' => __( 'Single Product Page Thumbnail:', 'wpsc' ),
			'input' => 'html',
			'html'  => $out,
			'helps' => "<span style='text-align:left; clear:both; display:block; padding-top:3px;'>" . __( 'This is the Thumbnail size that will be displayed on the Single Product page. You can change the default sizes under your store settings', 'wpsc' ) . "</span>"
		);

		//This is for the custom thumbnail size.

		$custom_thumb_size_w = get_post_meta( $post->ID, "_wpsc_custom_thumb_w", true );
		$custom_thumb_size_h = get_post_meta( $post->ID, "_wpsc_custom_thumb_h", true );
		$custom_thumb_html = "

			<input style='width:50px; text-align:center' type='text' name='attachments[{$post->ID}][wpsc_custom_thumb_w]' value='{$custom_thumb_size_w}' /> X <input style='width:50px; text-align:center' type='text' name='attachments[{$post->ID}][wpsc_custom_thumb_h]' value='{$custom_thumb_size_h}' />

		";
		$form_fields["wpsc_custom_thumb"] = array(
			"label" => __( 'Products Page Thumbnail Size:', 'wpsc' ),
			"input" => "html", // this is default if "input" is omitted
			"helps" => "<span style='text-align:left; clear:both; display:block; padding-top:3px;'>" . __( 'Custom thumbnail size for this image on the main Product Page', 'wpsc') . "</span>",
			"html" => $custom_thumb_html
		);
	}
	return $form_fields;

}
function wpsc_save_attachment_fields( $post, $attachment ) {
	if ( isset  ( $attachment['wpsc_custom_thumb_w'] ) )
		update_post_meta( $post['ID'], '_wpsc_custom_thumb_w', $attachment['wpsc_custom_thumb_w'] );

	if ( isset  ( $attachment['wpsc_custom_thumb_h'] ) )
		update_post_meta( $post['ID'], '_wpsc_custom_thumb_h', $attachment['wpsc_custom_thumb_h'] );

	if ( isset  ( $attachment['wpsc_image_size'] ) )
		update_post_meta( $post['ID'], '_wpsc_selected_image_size', $attachment['wpsc_image_size'] );

	return $post;
}

/**
 * wpsc_save_quickedit_box function
 * Saves input for the various meta in the quick edit boxes
 *
 * @todo UI
 * @todo Data validation / sanitization / security
 * @todo AJAX should probably return weight unit
 * @return $post_id (int) Post ID
 */

function wpsc_save_quickedit_box( $post_id ) {
	global $doaction;

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! defined( 'DOING_AJAX' ) || ! DOING_AJAX || get_post_type( $post_id ) != 'wpsc-product' )
		return;

	$bulk = isset( $doaction ) && $doaction =='edit';

	$custom_fields = array(
		'weight' => 'product_metadata',
		'stock' => 'stock',
		'price' => 'price',
		'sale_price' => 'special_price',
		'sku' => 'sku',
	);

        $args = array(
                        'post_parent' => $post_id,
                        'post_type' => 'wpsc-product',
                        'post_status' => 'inherit'
                        );
        $children = get_children($args);
	$is_parent = (bool)$children;
	foreach ( $custom_fields as $post_key => $meta_key ) {
		$overideVariant = isset($_REQUEST[$post_key.'_variant']) && $_REQUEST[$post_key.'_variant'] == 'on';
		// don't update if we're bulk updating and the field is left blank, or if the product has children and the field is one of those fields defined below (unles overridden)
		if ( ! isset( $_REQUEST[$post_key] ) || ( $bulk && empty( $_REQUEST[$post_key] ) ) ||
		( $is_parent && in_array( $post_key, array( 'weight', 'stock', 'price', 'special_price' )) && !$overideVariant ) ){
			continue;
		}

		if($is_parent && count($children) >0){
			$products = $children;
		}else{
			$products = array($post_id);
		}

		foreach($products as $product){
			$value = $_REQUEST[$post_key];
			if($is_parent) $post_id = $product->ID;
			else $post_id = $product;
			switch ( $post_key ) {
				case 'weight':
					$product_meta = get_post_meta( $post_id, '_wpsc_product_metadata', true );
					if ( ! is_array( $product_meta ) )
						$product_meta = array();
					// draft products don't have product metadata set yet
					$weight_unit = isset( $product_meta["weight_unit"] ) ? $product_meta["weight_unit"] : 'pound';
					$weight = wpsc_convert_weight( $value, $weight_unit, "pound", true );

					if ( isset( $product_meta["weight"] ) )
						unset( $product_meta["weight"] );

					$product_meta["weight"] = $weight;

					$value = $product_meta;
					break;

				case 'stock':
					if ( ! is_numeric( $value ) )
						$value = '';
					break;

				case 'sku':
					if ( $value == __( 'N/A', 'wpsc' ) )
						$value = '';
					break;
			}

			update_post_meta( $post_id, "_wpsc_{$meta_key}", $value );
		}
	}

	return $post_id;
}

/**
 * wpsc_quick_edit_boxes function
 * Creates inputs for the various meta in the quick edit boxes.
 *
 * @todo UI
 * @internal The post_id cannot be accessed here because this gets output at the very end
 *           of the editor form, and injected within relevant rows using javascript.
 */

function wpsc_quick_edit_boxes( $col_name, $_screen_post_type = null ) {
	// Avoid outputting this on term edit screens.
	// See http://core.trac.wordpress.org/ticket/16392#comment:9
	if ( current_filter() == 'quick_edit_custom_box' && $_screen_post_type == 'edit-tags' )
		return;
?>

<fieldset class="inline-edit-col-left wpsc-cols">
    <div class="inline-edit-col">
        <div class="inline-edit-group">
<?php
	switch ( $col_name ) :
	case 'SKU' :
?>
            <label style="max-width: 85%" class="alignleft">
                <span class="checkbox-title wpsc-quick-edit"><?php esc_html_e( 'SKU:', 'wpsc' ); ?> </span>
                <input type="text" name="sku" class="wpsc_ie_sku" />
				<input type="checkbox" name="sku_variant"> <span><?php esc_html_e( 'Update Variants', 'wpsc');?></span>

            </label>
            <?php
	break;
case 'weight' :
?>
            <label style="max-width: 85%" class="alignleft">
                <span class="checkbox-title wpsc-quick-edit"><?php esc_html_e( 'Weight:', 'wpsc' ); ?> </span>
                <input type="text" name="weight" class="wpsc_ie_weight" />
				<input type="checkbox" name="weight_variant"> <span><?php esc_html_e( 'Update Variants', 'wpsc');?></span>
            </label>
            <?php
	break;
case 'stock' :
?>
            <label style="max-width: 85%" class="alignleft">
                <span class="checkbox-title wpsc-quick-edit"><?php esc_html_e( 'Stock:', 'wpsc' ); ?> </span>
                <input type="text" name="stock" class="wpsc_ie_stock" />
				<input type="checkbox" name="stock_variant"> <span><?php esc_html_e( 'Update Variants', 'wpsc');?></span>
            </label>
            <?php
	break;
case 'price' :
?>
            <label style="max-width: 85%" class="alignleft">
                <span class="checkbox-title wpsc-quick-edit"><?php esc_html_e( 'Price:', 'wpsc' ); ?> </span>
                <input type="text" name="price" class="wpsc_ie_price" />
				<input type="checkbox" name="price_variant"> <span><?php esc_html_e( 'Update Variants', 'wpsc');?></span>
            </label>
            <?php
	break;
case 'sale_price' :
?>
            <label style="max-width: 85%" class="alignleft">
                <span class="checkbox-title wpsc-quick-edit"><?php esc_html_e( 'Sale Price:', 'wpsc' ); ?> </span>
                <input type="text" name="sale_price" class="wpsc_ie_sale_price" />
				<input type="checkbox" name="sale_price_variant"> <span><?php esc_html_e( 'Update Variants', 'wpsc');?></span>
            </label>
            <?php
	break;
	endswitch;
?>
         </div>
    </div>
</fieldset>
<?php
}

add_action( 'quick_edit_custom_box', 'wpsc_quick_edit_boxes', 10, 2 );
add_action( 'bulk_edit_custom_box', 'wpsc_quick_edit_boxes', 10, 2 );
add_action( 'save_post', 'wpsc_save_quickedit_box' );

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
		<label for="variation_price"><?php esc_html_e( 'Variation Price', 'wpsc' ); ?></label>
		<input type="text" name="variation_price" id="variation_price" style="width:50px;" value="<?php echo $price; ?>"><br />
		<span class="description"><?php esc_html_e( 'You can list a default price here for this variation.  You can list a regular price (18.99), differential price (+1.99 / -2) or even a percentage-based price (+50% / -25%).', 'wpsc' ); ?></span>
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
		<label for="variation_price"><?php esc_html_e( 'Variation Price', 'wpsc' ); ?></label>
            </th>
            <td>
		<input type="text" name="variation_price" id="variation_price" style="width:50px;" value="<?php echo $price; ?>"><br />
		<span class="description"><?php esc_html_e( 'You can list a default price here for this variation.  You can list a regular price (18.99), differential price (+1.99 / -2) or even a percentage-based price (+50% / -25%).', 'wpsc' ); ?></span>
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
		<th scope="row" valign="top"><label for="apply_to_current"><?php esc_html_e( 'Apply to current variations?', 'wpsc' ) ?></label></th>
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
add_action( 'before_delete_post', 'wpsc_delete_variations' );
