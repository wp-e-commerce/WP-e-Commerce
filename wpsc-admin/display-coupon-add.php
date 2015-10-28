<div class="wrap" id="coupon_data">
	<div id="add_coupon_box">
		<h2><?php _e( 'Add Coupon', 'wp-e-commerce' ); ?></h2>
		<form name='add_coupon' method="post" action="<?php echo admin_url( 'edit.php?post_type=wpsc-product&page=wpsc-edit-coupons' ); ?>">
			<table class="form-table">
				<tbody>

					<?php do_action( 'wpsc_coupon_add_top' ); ?>

					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="add_coupon_code"><?php _e( 'Coupon Code', 'wp-e-commerce' ); ?></label>
						</th>
						<td>
							<input name="add_coupon_code" id="add_coupon_code" type="text" style="width: 300px;"/>
							<p class="description"><?php _e( 'The code entered to receive the discount', 'wp-e-commerce' ); ?></p>
						</td>
					</tr>

					<tr class="form-field" id="discount_amount">
						<th scope="row" valign="top">
							<label for="add-coupon-code"><?php _e( 'Discount', 'wp-e-commerce' ); ?></label>
						</th>
						<td>
							<input name="add_discount" step=".01" id="add-coupon-code" type="number" class="small-text" min="0" style="width: 300px" />
							<p class="description"><?php _e( 'The discount amount', 'wp-e-commerce' ); ?></p>
						</td>
					</tr>

					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="add_discount_type"><?php _e( 'Discount Type', 'wp-e-commerce' ); ?></label>
						</th>
						<td>
							<select name='add_discount_type' id='add_discount_type'>
								<option value='0'><?php _e( 'Fixed Amount', 'wp-e-commerce' ); ?></option>
								<option value='1'><?php _e( 'Percentage', 'wp-e-commerce' ); ?></option>
								<option value='2'><?php _e( 'Free shipping', 'wp-e-commerce' ); ?></option>
							</select>
							<p class="description"><?php _e( 'The discount type', 'wp-e-commerce' ); ?></p>
						</td>
					</tr>

					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="add_start"><?php _e( 'Start and End', 'wp-e-commerce' ); ?></label>
						</th>
						<td>
							<span class="description"><?php _e( 'Start: ', 'wp-e-commerce' ); ?></span>
							<input name="add_start" id="add_start" type="text" class="regular-text pickdate" style="width: 100px"/>
							<span class="description"><?php _e( 'End: ', 'wp-e-commerce' ); ?></span>
							<input name="add_end" id="add_end" type="text" class="regular-text pickdate" style="width: 100px"/>
							<p class="description"><?php _e( 'If date fields are left empty, there will be no expiration on this coupon.', 'wp-e-commerce' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<?php _e( 'Active', 'wp-e-commerce' ); ?>
						</th>
						<td>
							<input type='hidden' value='0' name='add_active' />
							<input type="checkbox" value='1' checked='checked' name='add_active' id="add_active" />
							<label for="add_active"><?php _e( 'Activate coupon on creation.', 'wp-e-commerce' ) ?></label>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<?php _e( 'Use Once', 'wp-e-commerce' ); ?>
						</th>
						<td>
							<input type='hidden' value='0' name='add_use-once' />
							<input type='checkbox' value='1' name='add_use-once' id="add_use-once" />
							<label for="add_use-once"><?php _e( 'Deactivate coupon after it has been used.', 'wp-e-commerce' ) ?></label>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<?php _e( 'Apply On All Products', 'wp-e-commerce' ); ?>
						</th>
						<td>
							</span><input type='hidden' value='0' name='add_every_product' />
							<input type="checkbox" value="1" name='add_every_product' id="add_every-product"/>
							<label for="add_every-product"><?php _e( 'This coupon affects each product at checkout.', 'wp-e-commerce' ) ?></label>
						</td>
					</tr>

					<tr class="form-field coupon-conditions">
						<th scope="row" valign="top">
							<label><strong><?php _e( 'Conditions', 'wp-e-commerce' ); ?></strong></label>
						</th>
						<td>
							<input type="hidden" name="rules[operator][]" value="" />
							<div class='coupon-condition'>
								<select class="ruleprops" name="rules[property][]">
									<option value="item_name" rel="order"><?php _e( 'Item name', 'wp-e-commerce' ); ?></option>
									<option value="item_quantity" rel="order"><?php _e( 'Item quantity', 'wp-e-commerce' ); ?></option>
									<option value="total_quantity" rel="order"><?php _e( 'Total quantity', 'wp-e-commerce' ); ?></option>
									<option value="subtotal_amount" rel="order"><?php _e( 'Subtotal amount', 'wp-e-commerce' ); ?></option>
									<?php echo apply_filters( 'wpsc_coupon_rule_property_options', '' ); ?>
								</select>

								<select name="rules[logic][]">
									<option value="equal"><?php _e( 'Is equal to', 'wp-e-commerce' ); ?></option>
									<option value="greater"><?php _e( 'Is greater than', 'wp-e-commerce' ); ?></option>
									<option value="less"><?php _e( 'Is less than', 'wp-e-commerce' ); ?></option>
									<option value="contains"><?php _e( 'Contains', 'wp-e-commerce' ); ?></option>
									<option value="not_contain"><?php _e( 'Does not contain', 'wp-e-commerce' ); ?></option>
									<option value="begins"><?php _e( 'Begins with', 'wp-e-commerce' ); ?></option>
									<option value="ends"><?php _e( 'Ends with', 'wp-e-commerce' ); ?></option>
									<option value="category"><?php _e( 'In Category', 'wp-e-commerce' ); ?></option>
									<?php echo apply_filters( 'wpsc_coupon_rule_logic_options', '' ); ?>
								</select>

								<input type="text" name="rules[value][]" style="width: 150px;"/>
								<a title="<?php esc_attr_e( 'Delete condition', 'wp-e-commerce' ); ?>" class="button-secondary wpsc-button-round wpsc-button-minus" href="#"><?php echo _x( '&ndash;', 'delete item', 'wp-e-commerce' ); ?></a>
								<a title="<?php esc_attr_e( 'Add condition', 'wp-e-commerce' ); ?>" class="button-secondary wpsc-button-round wpsc-button-plus" href="#"><?php echo _x( '+', 'add item', 'wp-e-commerce' ); ?></a>
							</div>
						</td>
					</tr>

					<?php do_action( 'wpsc_coupon_add_bottom' ); ?>

				</tbody>
			</table>
			<?php wp_nonce_field( 'wpsc_coupon', 'wpsc-coupon-add' ); ?>
			<?php submit_button( __( 'Add Coupon', 'wp-e-commerce' ), 'primary', 'add_coupon' ); ?>

		</form>
	</div>
</div><!--end .wrap-->
