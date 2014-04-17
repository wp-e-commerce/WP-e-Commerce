<div class="wrap" id="coupon_data">
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
							<input name="add_discount" id="add-coupon-code" type="number" class="small-text" min="0" />
							<span class="description"><?php _e( 'The discount amount', 'wpsc' ); ?></span>
						</td>
					</tr>

					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="add_discount_type"><?php _e( 'Discount Type', 'wpsc' ); ?></label>
						</th>
						<td>
							<select name='add_discount_type' id='add_discount_type'>
								<option value='0'><?php _e( 'Fixed Amount', 'wpsc' ); ?></option>
								<option value='1'><?php _e( 'Percentage', 'wpsc' ); ?></option>
								<option value='2'><?php _e( 'Free shipping', 'wpsc' ); ?></option>
							</select>
							<p class="description"><?php _e( 'The discount type', 'wpsc' ); ?></p>
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
							<?php _e( 'Active', 'wpsc' ); ?>
						</th>
						<td>
							<input type='hidden' value='0' name='add_active' />
							<input type="checkbox" value='1' checked='checked' name='add_active' id="add_active" />
							<label for="add_active"><?php _e( 'Activate coupon on creation.', 'wpsc' ) ?></label>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<?php _e( 'Use Once', 'wpsc' ); ?>
						</th>
						<td>
							<input type='hidden' value='0' name='add_use-once' />
							<input type='checkbox' value='1' name='add_use-once' id="add_use-once" />
							<label for="add_use-once"><?php _e( 'Deactivate coupon after it has been used.', 'wpsc' ) ?></label>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<?php _e( 'Apply On All Products', 'wpsc' ); ?>
						</th>
						<td>
							</span><input type='hidden' value='0' name='add_every_product' />
							<input type="checkbox" value="1" name='add_every_product' id="add_every-product"/>
							<label for="add_every-product"><?php _e( 'This coupon affects each product at checkout.', 'wpsc' ) ?></label>
						</td>
					</tr>

					<tr class="form-field coupon-conditions">
						<th scope="row" valign="top">
							<label><strong><?php _e( 'Conditions', 'wpsc' ); ?></strong></label>
						</th>
						<td>
							<input type="hidden" name="rules[operator][]" value="" />
							<div class='coupon-condition'>
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
									<?php echo apply_filters( 'wpsc_coupon_rule_logic_options', '' ); ?>
								</select>

								<input type="text" name="rules[value][]" style="width: 150px;"/>
								<a title="<?php esc_attr_e( 'Delete condition', 'wpsc' ); ?>" class="button-secondary wpsc-button-round wpsc-button-minus" href="#"><?php echo _x( '&ndash;', 'delete item', 'wpsc' ); ?></a>
								<a title="<?php esc_attr_e( 'Add condition', 'wpsc' ); ?>" class="button-secondary wpsc-button-round wpsc-button-plus" href="#"><?php echo _x( '+', 'add item', 'wpsc' ); ?></a>
							</div>
						</td>
					</tr>

				</tbody>
			</table>

			<?php submit_button( __( 'Add Coupon', 'wpsc' ), 'primary', 'add_coupon' ); ?>

		</form>
	</div>
</div><!--end .wrap-->
