<?php

// die if accessed directly
if( !defined( 'ABSPATH' ) )
	die();

$coupon_id = absint( $_GET['coupon'] );
$coupon    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE `id` = %d", $coupon_id ), ARRAY_A );
echo '<pre>'; print_r( $coupon ); echo '</pre>';
?>
<div class="wrap" id+"coupon_data">
	<div id="edit_coupon_box">
		<h2><?php _e( 'Edit Coupon', 'wpsc' ); ?></h2>

		<script type='text/javascript'>
			jQuery(".pickdate").datepicker();
			/* jQuery datepicker selector */
			if (typeof jQuery('.pickdate').datepicker != "undefined") {
				jQuery('.pickdate').datepicker({ dateFormat: 'yy-mm-dd' });
			}
		</script>
		<form name='edit_coupon' method="post" action="<?php echo admin_url( 'edit.php?post_type=wpsc-product&page=wpsc-edit-coupons' ); ?>">
			<table class="form-table">
				<tbody>

					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="edit_coupon_code"><?php _e( 'Coupon Code', 'wpsc' ); ?></label>
						</th>
						<td>
							<input name="edit_coupon_code" id="edit_coupon_code" type="text" value="<?php esc_attr_e( $coupon['coupon_code'] ); ?>" style="width: 300px;"/>
							<p class="description"><?php _e( 'The code entered to receive the discount', 'wpsc' ); ?></p>
						</td>
					</tr>

					<tr class="form-field" id="discount_amount">
						<th scope="row" valign="top">
							<label for="edit_coupon_amount"><?php _e( 'Discount', 'wpsc' ); ?></label>
						</th>
						<td>
							<input name="edit_coupon_amount" id="edit_coupon_amount" type="number" value="<?php esc_attr_e( $coupon['value'] ); ?>" class="small-text"/>
							<span class="description"><?php _e( 'The discount amount', 'wpsc' ); ?></span>
						</td>
					</tr>

					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="edit_discount_type"><?php _e( 'Discount Type', 'wpsc' ); ?></label>
						</th>
						<td>
							<?php $type = absint( $coupon['is-percentage'] ); ?>
							<select name='edit_discount_type' id='edit_discount_type' onchange='show_shipping_options();'>
								<option value='0'<?php selected( 0, $type ); ?>>$</option>
								<option value='1'<?php selected( 1, $type ); ?>>%</option>
								<option value='2'<?php selected( 2, $type ); ?>><?php _e( 'Free shipping', 'wpsc' ); ?></option>
							</select>
							<p class="description"><?php _e( 'The discount type', 'wpsc' ); ?></p>

							<?php $display = $type == 2 ? '' : 'style="display:none;"'; ?>
							<div id="free_shipping_options" <?php echo $display; ?>>

								<select name='free_shipping_options[discount_country]' id='coupon_country_list' onchange='show_region_list();'>
									<option value='' ><?php _e( 'All Countries and Regions', 'wpsc' ); ?></option>
									<?php echo country_list(); ?>
								</select>

								<span id='discount_options_country'>
								<?php
								//i dont think we need this cu we need to do an ajax request to generate this list
								//based on the country chosen probably need the span place holder tho
								$region_list = $wpdb->get_results( $wpdb->prepare( "SELECT `" . WPSC_TABLE_REGION_TAX . "`.* FROM `" . WPSC_TABLE_REGION_TAX . "`, `" . WPSC_TABLE_CURRENCY_LIST . "`  WHERE `" . WPSC_TABLE_CURRENCY_LIST . "`.`isocode` IN(%s) AND `" . WPSC_TABLE_CURRENCY_LIST . "`.`id` = `" . WPSC_TABLE_REGION_TAX . "`.`country_id`", get_option( $free_shipping_country ) ), ARRAY_A );
								if ( !empty( $region_list ) ) { ?>

									<select name='free_shipping_options[discount_region]'>
									<?php
										foreach ( $region_list as $region ) {
											if ( esc_attr( $free_shipping_region ) == $region['id'] ) {
												$selected = "selected='selected'";
											} else {
												$selected = "";
											}
										?>
										<option value='<?php echo $region['id']; ?>' <?php echo $selected; ?> ><?php echo esc_attr( $region['name'] ); ?></option> <?php
										}
									?>
									</select>
							<?php } ?>
							</span>

							</div>

						</td>
					</tr>

					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="edit_start"><?php _e( 'Start and End', 'wpsc' ); ?></label>
						</th>
						<td>
							<?php
							$start = $coupon['start']  == '0000-00-00 00:00:00' ? '' : $coupon['start'];
							$end   = $coupon['expiry'] == '0000-00-00 00:00:00' ? '' : $coupon['expiry'];
							?>
							<span class="description"><?php _e( 'Start: ', 'wpsc' ); ?></span>
							<input name="edit_start" id="edit_start" type="text" value="<?php esc_attr_e( $start ); ?>" class="regular-text pickdate" style="width: 100px"/>
							<span class="description"><?php _e( 'End: ', 'wpsc' ); ?></span>
							<input name="edit_end" id="edit_end" type="text" value="<?php esc_attr_e( $end ); ?>" class="regular-text pickdate" style="width: 100px"/>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<label for="edit_active"><?php _e( 'Active', 'wpsc' ); ?></label>
						</th>
						<td>
							<input type='hidden' value='0' name='edit_active' />
							<input type="checkbox" value='1'<?php checked( 1, $coupon['active'] ); ?> name='edit_active' id="edit_active" />
							<span><?php _e( 'Is this coupon active?', 'wpsc' ) ?></span>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<label for="edit_use-once"><?php _e( 'Use Once', 'wpsc' ); ?></label>
						</th>
						<td>
							<input type='hidden' value='0' name='edit_use-once' />
							<input type='checkbox' value='1'<?php checked( 1, $coupon['use-once'] ); ?> name='edit_use-once' id="edit_use-once" />
							<span><?php _e( 'Deactivate coupon after it has been used.', 'wpsc' ) ?></span>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<label for="edit_use-x-times"><?php _e( 'Apply On All Products', 'wpsc' ); ?></label>
						</th>
						<td>
							</span><input type='hidden' value='0' name='edit_every_product' />
							<input type="checkbox" value="1"<?php checked( 1, $coupon['every_product'] ); ?> name='edit_every_product'/>
							<span class='description'><?php _e( 'This coupon affects each product at checkout.', 'wpsc' ) ?></span>
						</td>
					</tr>

					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="edit_use-x-times"><?php _e( 'Max Use', 'wpsc' ); ?></label>
						</th>
						<td>
							<input type='hidden' value='0' name='edit_use-x-times' />
							<input type='number' size='4' value='<?php esc_attr_e( absint( $coupon['use-x-times'] ) ); ?>' name='edit_use-x-times' class="small-text" />
							<span class='description'><?php _e( 'Set the amount of times the coupon can be used.', 'wpsc' ) ?></span>
						</td>
					</tr>

					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="edit_use-x-times"><strong><?php _e( 'Conditions', 'wpsc' ); ?></strong></label>
						</th>
						<td>
							<div class='coupon_condition' >
								<div class='first_condition'>
									<select class="ruleprops" name="rules[property][]">
										<option value="item_name" rel="order"><?php _e( 'Item name', 'wpsc' ); ?></option>
										<option value="item_quantity" rel="order"><?php _e( 'Item quantity', 'wpsc' ); ?></option>
										<option value="total_quantity" rel="order"><?php _e( 'Total quantity', 'wpsc' ); ?></option>
										<option value="subtotal_amount" rel="order"><?php _e( 'Subtotal amount', 'wpsc' ); ?></option>
										<?php echo apply_filters( 'wpsc_coupon_rule_property_options', '' ); ?>
									</select>

									<select name="rules[logic][]">
										<option value="equal"><?php _e( 'Is equal to', 'wpsc' ); ?></option>
										<option value="greater"><?php _e( 'Is greater than', 'wpsc' ); ?></option>
										<option value="less"><?php _e( 'Is less than', 'wpsc' ); ?></option>
										<option value="contains"><?php _e( 'Contains', 'wpsc' ); ?></option>
										<option value="not_contain"><?php _e( 'Does not contain', 'wpsc' ); ?></option>
										<option value="begins"><?php _e( 'Begins with', 'wpsc' ); ?></option>
										<option value="ends"><?php _e( 'Ends with', 'wpsc' ); ?></option>
										<option value="category"><?php _e( 'In Category', 'wpsc' ); ?></option>
									</select>

									<input type="text" name="rules[value][]" style="width: 300px;"/>

									<script>
										var coupon_number=1;
										function edit_another_property(this_button){
											var new_property='<div class="coupon_condition">\n'+
												'<select class="ruleprops" name="rules[property][]"> \n'+
												'<option value="item_name" rel="order">Item name</option> \n'+
												'<option value="item_quantity" rel="order">Item quantity</option>\n'+
												'<option value="total_quantity" rel="order">Total quantity</option>\n'+
												'<option value="subtotal_amount" rel="order">Subtotal amount</option>\n'+
												'<?php echo apply_filters( 'wpsc_coupon_rule_property_options', '' ); ?>'+
												'</select> \n'+
												'<select name="rules[logic][]"> \n'+
												'<option value="equal">Is equal to</option> \n'+
												'<option value="greater">Is greater than</option> \n'+
												'<option value="less">Is less than</option> \n'+
												'<option value="contains">Contains</option> \n'+
												'<option value="not_contain">Does not contain</option> \n'+
												'<option value="begins">Begins with</option> \n'+
												'<option value="ends">Ends with</option> \n'+
												'</select> \n'+
												'<span> \n'+
												'<input type="text" name="rules[value][]" style="width:300px"/> \n'+
												'</span>  \n'+
												'<img height="16" width="16" class="delete" alt="Delete" src="<?php echo WPSC_CORE_IMAGES_URL; ?>/cross.png" onclick="jQuery(this).parent().remove();"/>\n' +
												'</div> ';

											jQuery('.coupon_condition :first').after(new_property);
											coupon_number++;
										}

										//displays the free shipping options
										function show_shipping_options() {
											var discount_type = document.getElementById("edit_discount_type").value;
											if (discount_type == "2") {
												document.getElementById("free_shipping_options").style.display='block';
												document.getElementById("discount_amount").style.display='none';
											}else{
												document.getElementById("free_shipping_options").style.display='none';
												document.getElementById("discount_amount").style.display='table-row';
											}
										}

										//need to send the selected country off via ajax to return the region select box for that country
										function show_region_list(){
											var country_id = document.getElementById("coupon_country_list").value;
										}

									</script>
								</div>
							</div><br/>
							<a class="wpsc_coupons_condition_add button-secondary" onclick="edit_another_property(jQuery(this));">
								<?php _e( 'Add New Condition', 'wpsc' ); ?>
							</a>

						</td>
					</tr>

				</tbody>
			</table>
			<input type="hidden" name="coupon_id" value="<?php echo esc_attr( $coupon_id ); ?>"/>
			<input type="hidden" name="is_edit_coupon" value="true"/>
			<?php submit_button( __( 'Update Coupon', 'wpsc' ), 'primary' ); ?>

		</form>
	</div>
</div><!--end .wrap-->