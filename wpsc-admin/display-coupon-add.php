<div class="wrap" id+"coupon_data">
	<div id="add_coupon_box">
		<h2><?php _e( 'Add Coupon', 'wpsc' ); ?></h2>

		<script type='text/javascript'>
			jQuery(".pickdate").datepicker();
			/* jQuery datepicker selector */
			if (typeof jQuery('.pickdate').datepicker != "undefined") {
				jQuery('.pickdate').datepicker({ dateFormat: 'yy-mm-dd' });
			}
		</script>
		<form name='add_coupon' method="post" action="<?php echo admin_url( 'edit.php?post_type=wpsc-product&page=wpsc-edit-coupons' ); ?>">
			<table class="form-table">
				<tbody>

					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="add_coupon_code"><?php _e( 'Coupon Code', 'wpsc' ); ?></label>
						</th>
						<td>
							<input name="add_coupon_code" id="add_coupon_code" type="text" style="width: 300px;"/>
							<p class="description"><?php _e( 'The code entered to receive the discount', 'wpsc' ); ?></p>
						</td>
					</tr>

					<tr class="form-field" id="discount_amount">
						<th scope="row" valign="top">
							<label for="add_discount"><?php _e( 'Discount', 'wpsc' ); ?></label>
						</th>
						<td>
							<input name="add_discount" id="add_discount" type="number" class="small-text"/>
							<span class="description"><?php _e( 'The discount amount', 'wpsc' ); ?></span>
						</td>
					</tr>

					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="add_discount_type"><?php _e( 'Discount Type', 'wpsc' ); ?></label>
						</th>
						<td>
							<?php
							global $wpdb;
							$currency_data = $wpdb->get_row( "SELECT `symbol`,`symbol_html`,`code` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id`='" . esc_attr( get_option( 'currency_type' ) ) . "' LIMIT 1", ARRAY_A );
							$currency_sign = ! empty( $currency_data['symbol'] ) ? $currency_data['symbol_html'] : $currency_data['code'];
							?>
							<select name='add_discount_type' id='add_discount_type' onchange = 'show_shipping_options();'>
								<option value='0'><?php esc_html_e( $currency_sign ); ?></option>
								<option value='1'>%</option>
								<option value='2'><?php _e( 'Free shipping', 'wpsc' ); ?></option>
							</select>
							<p class="description"><?php _e( 'The discount type', 'wpsc' ); ?></p>

							<div id="free_shipping_options" style="display:none;">

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
							<label for="add_start"><?php _e( 'Start and End', 'wpsc' ); ?></label>
						</th>
						<td>
							<span class="description"><?php _e( 'Start: ', 'wpsc' ); ?></span>
							<input name="add_start" id="add_start" type="text" class="regular-text pickdate" style="width: 100px"/>
							<span class="description"><?php _e( 'End: ', 'wpsc' ); ?></span>
							<input name="add_end" id="add_end" type="text" class="regular-text pickdate" style="width: 100px"/>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<label for="add_active"><?php _e( 'Active', 'wpsc' ); ?></label>
						</th>
						<td>
							<input type='hidden' value='0' name='add_active' />
							<input type="checkbox" value='1' checked='checked' name='add_active' id="add_active" />
							<span><?php _e( 'Activate coupon on creation.', 'wpsc' ) ?></span>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<label for="add_use-once"><?php _e( 'Use Once', 'wpsc' ); ?></label>
						</th>
						<td>
							<input type='hidden' value='0' name='add_use-once' />
							<input type='checkbox' value='1' name='add_use-once' id="add_use-once" />
							<span><?php _e( 'Deactivate coupon after it has been used.', 'wpsc' ) ?></span>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<label for="add_use-x-times"><?php _e( 'Apply On All Products', 'wpsc' ); ?></label>
						</th>
						<td>
							</span><input type='hidden' value='0' name='add_every_product' />
							<input type="checkbox" value="1" name='add_every_product'/>
							<span class='description'><?php _e( 'This coupon affects each product at checkout.', 'wpsc' ) ?></span>
						</td>
					</tr>

					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="add_use-x-times"><?php _e( 'Max Use', 'wpsc' ); ?></label>
						</th>
						<td>
							<input type='hidden' value='0' name='add_use-x-times' />
							<input type='number' size='4' value='' name='add_use-x-times' class="small-text" />
							<span class='description'><?php _e( 'Set the amount of times the coupon can be used.', 'wpsc' ) ?></span>
						</td>
					</tr>

					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="add_use-x-times"><strong><?php _e( 'Conditions', 'wpsc' ); ?></strong></label>
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
										function add_another_property(this_button){
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
											var discount_type = document.getElementById("add_discount_type").value;
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
							<a class="wpsc_coupons_condition_add button-secondary" onclick="add_another_property(jQuery(this));">
								<?php _e( 'Add New Condition', 'wpsc' ); ?>
							</a>

						</td>
					</tr>

				</tbody>
			</table>

			<?php submit_button( __( 'Add Coupon', 'wpsc' ), 'primary', 'add_coupon' ); ?>

		</form>
	</div>
</div><!--end .wrap-->