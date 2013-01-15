<div class="wrap" id+"coupon_data">
	<div id="add_coupon_box">
		<h2><?php _e( 'Add Coupon', 'wpsc' ); ?></h2>
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
							<label for="add-coupon-code"><?php _e( 'Discount', 'wpsc' ); ?></label>
						</th>
						<td>
							<input name="add_discount" id="add-coupon-code" type="number" class="small-text"/>
							<span class="description"><?php _e( 'The discount amount', 'wpsc' ); ?></span>
						</td>
					</tr>

					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="add_discount_type"><?php _e( 'Discount Type', 'wpsc' ); ?></label>
						</th>
						<td>
							<select name='add_discount_type' id='add_discount_type' onchange = 'show_shipping_options();'>
								<option value='0'><?php _e( 'Fixed Amount', 'wpsc' ); ?></option>
								<option value='1'><?php _e( 'Percentage', 'wpsc' ); ?></option>
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