<?php

// Die if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

$coupon = new WPSC_Coupon( $_GET['coupon'] );

?>
<div class="wrap" id="coupon_data">
	<div id="edit_coupon_box">
		<h2><?php _e( 'Edit Coupon', 'wp-e-commerce' ); ?></h2>

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

					<?php do_action( 'wpsc_coupon_edit_top', $coupon->get( 'id' ), $coupon->get_data() ); ?>

					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="edit_coupon_code"><?php _e( 'Coupon Code', 'wp-e-commerce' ); ?></label>
						</th>
						<td>
							<input name="edit_coupon_code" id="edit_coupon_code" type="text" value="<?php echo esc_attr( $coupon->get( 'coupon_code' ) ); ?>" style="width: 300px;"/>
							<p class="description"><?php _e( 'The code entered to receive the discount', 'wp-e-commerce' ); ?></p>
						</td>
					</tr>

					<tr class="form-field" id="discount_amount">
						<th scope="row" valign="top">
							<label for="edit_coupon_amount"><?php _e( 'Discount', 'wp-e-commerce' ); ?></label>
						</th>
						<td>
							<input name="edit_coupon_amount" id="edit_coupon_amount" type="number" step=".01" value="<?php echo esc_attr( $coupon->get( 'value' ) ); ?>" class="small-text" min="0" style="width: 300px" />
							<p class="description"><?php _e( 'The discount amount', 'wp-e-commerce' ); ?></p>
						</td>
					</tr>

					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="edit_discount_type"><?php _e( 'Discount Type', 'wp-e-commerce' ); ?></label>
						</th>
						<td>
							<?php $type = absint( $coupon->get( 'is-percentage' ) ); ?>
							<select name='edit_discount_type' id='edit_discount_type'>
								<option value='0'<?php selected( 0, $type ); ?>><?php _e( 'Fixed Amount', 'wp-e-commerce' ); ?></option>
								<option value='1'<?php selected( 1, $type ); ?>><?php _e( 'Percentage', 'wp-e-commerce' ); ?></option>
								<option value='2'<?php selected( 2, $type ); ?>><?php _e( 'Free shipping', 'wp-e-commerce' ); ?></option>
							</select>
							<p class="description"><?php _e( 'The discount type', 'wp-e-commerce' ); ?></p>

							<?php $display = $type == 2 ? '' : 'style="display:none;"'; ?>
						</td>
					</tr>

					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="edit_coupon_start"><?php _e( 'Start and End', 'wp-e-commerce' ); ?></label>
						</th>
						<td>
							<?php
								$start = $coupon->get( 'start' )  == '0000-00-00 00:00:00' ? '' : get_date_from_gmt( $coupon->get( 'start' ), 'Y-m-d' );
								$end   = $coupon->get( 'expiry' ) == '0000-00-00 00:00:00' ? '' : get_date_from_gmt( $coupon->get( 'expiry' ), 'Y-m-d' );
							?>
							<span class="description"><?php _e( 'Start: ', 'wp-e-commerce' ); ?></span>
							<input name="edit_coupon_start" id="edit_coupon_start" type="text" value="<?php echo esc_attr( $start ); ?>" class="regular-text pickdate" style="width: 100px"/>
							<span class="description"><?php _e( 'End: ', 'wp-e-commerce' ); ?></span>
							<input name="edit_coupon_end" id="edit_coupon_end" type="text" value="<?php echo esc_attr( $end ); ?>" class="regular-text pickdate" style="width: 100px"/>
							<p class="description"><?php _e( 'If date fields are left empty, there will be no expiration on this coupon.', 'wp-e-commerce' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<?php _e( 'Active', 'wp-e-commerce' ); ?>
						</th>
						<td>
							<input type='hidden' value='0' name='edit_coupon_active' />
							<input type="checkbox" value='1'<?php checked( 1, $coupon->get( 'active' ) ); ?> name='edit_coupon_active' id="edit_coupon_active" />
							<label for="edit_coupon_active"><?php _e( 'Is this coupon active?', 'wp-e-commerce' ) ?></label>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<?php _e( 'Use Once', 'wp-e-commerce' ); ?>
						</th>
						<td>
							<input type='hidden' value='0' name='edit_coupon_use_once' />
							<input type='checkbox' value='1'<?php checked( 1, $coupon->get( 'use-once' ) ); ?> name='edit_coupon_use_once' id="edit_coupon_use_once" />
							<label for="edit_coupon_use_once"><?php _e( 'Deactivate coupon after it has been used.', 'wp-e-commerce' ) ?></label>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<?php _e( 'Apply On All Products', 'wp-e-commerce' ); ?>
						</th>
						<td>
							</span><input type='hidden' value='0' name='edit_coupon_every_product' />
							<input type="checkbox" value="1"<?php checked( 1, $coupon->get( 'every_product' ) ); ?> name='edit_coupon_every_product' id="edit-coupon-every-product"/>
							<label for="edit-coupon-every-product"><?php _e( 'This coupon affects each product at checkout.', 'wp-e-commerce' ) ?></label>
						</td>
					</tr>

					<tr class="form-field coupon-conditions">
						<th scope="row" valign="top">
							<label><strong><?php _e( 'Conditions', 'wp-e-commerce' ); ?></strong></label>
						</th>
						<td>
							<input type="hidden" name="rules[operator][]" value="" />
							<?php
							$conditions = maybe_unserialize( $coupon->get( 'condition' ) );

							if ( empty( $conditions ) )
								$conditions = array(
									array(
										'property' => '',
										'logic'    => '',
										'value'    => '',
									)
								);
							foreach ( $conditions as $key => $condition ) :
								?>
								<div class='coupon-condition'>
								<?php
									if ( isset( $condition["operator"] ) && ! empty( $condition["operator"] ) ) :
								?>
									<select name="rules[operator][]">
										<option value="and"<?php selected( 'and', $condition["operator"] ); ?>><?php _ex( 'AND', 'Coupon comparison logic', 'wp-e-commerce' );?></option>
										<option value="or"<?php  selected( 'or' , $condition["operator"] ); ?>><?php _ex( 'OR' , 'Coupon comparison logic', 'wp-e-commerce' );?></option>
									</select>
								<?php endif; ?>
									<select class="ruleprops" name="rules[property][]">
										<option value="item_name"<?php selected( 'item_name', $condition['property'] ); ?> rel="order"><?php _e( 'Item name', 'wp-e-commerce' ); ?></option>
										<option value="item_quantity"<?php selected( 'item_quantity', $condition['property'] ); ?> rel="order"><?php _e( 'Item quantity', 'wp-e-commerce' ); ?></option>
										<option value="total_quantity"<?php selected( 'total_quantity', $condition['property'] ); ?> rel="order"><?php _e( 'Total quantity', 'wp-e-commerce' ); ?></option>
										<option value="subtotal_amount"<?php selected( 'subtotal_amount', $condition['property'] ); ?> rel="order"><?php _e( 'Subtotal amount', 'wp-e-commerce' ); ?></option>
										<?php do_action( 'wpsc_coupon_rule_property_options', $condition['property']  ); ?>
									</select>

									<select name="rules[logic][]">
										<option value="equal"<?php selected( 'equal', $condition['logic'] ); ?>><?php _e( 'Is equal to', 'wp-e-commerce' ); ?></option>
										<option value="greater"<?php selected( 'greater', $condition['logic'] ); ?>><?php _e( 'Is greater than', 'wp-e-commerce' ); ?></option>
										<option value="less"<?php selected( 'less', $condition['logic'] ); ?>><?php _e( 'Is less than', 'wp-e-commerce' ); ?></option>
										<option value="contains"<?php selected( 'contains', $condition['logic'] ); ?>><?php _e( 'Contains', 'wp-e-commerce' ); ?></option>
										<option value="not_contain"<?php selected( 'not_contain', $condition['logic'] ); ?>><?php _e( 'Does not contain', 'wp-e-commerce' ); ?></option>
										<option value="begins"<?php selected( 'begins', $condition['logic'] ); ?>><?php _e( 'Begins with', 'wp-e-commerce' ); ?></option>
										<option value="ends"<?php selected( 'ends', $condition['logic'] ); ?>><?php _e( 'Ends with', 'wp-e-commerce' ); ?></option>
										<option value="category"<?php selected( 'category', $condition['logic'] ); ?>><?php _e( 'In Category', 'wp-e-commerce' ); ?></option>
									</select>

									<input type="text" name="rules[value][]" value="<?php echo esc_attr( $condition['value'] ); ?>" style="width: 150px;"/>
									<a title="<?php esc_attr_e( 'Delete condition', 'wp-e-commerce' ); ?>" class="button-secondary wpsc-button-round wpsc-button-minus" href="#"><?php echo _x( '&ndash;', 'delete item', 'wp-e-commerce' ); ?></a>
									<a title="<?php esc_attr_e( 'Add condition', 'wp-e-commerce' ); ?>" class="button-secondary wpsc-button-round wpsc-button-plus" href="#"><?php echo _x( '+', 'add item', 'wp-e-commerce' ); ?></a>
								</div>
							<?php endforeach; ?>
						</td>
					</tr>

					<?php do_action( 'wpsc_coupon_edit_bottom', $coupon->get( 'id' ), $coupon->get_data() ); ?>

				</tbody>
			</table>
			<input type="hidden" name="coupon_id" value="<?php echo esc_attr( $coupon->get( 'id' ) ); ?>"/>
			<input type="hidden" name="edit_coupon_is_used" value="<?php echo esc_attr( $coupon->get( 'is-used' ) ); ?>"/>
			<input type="hidden" name="is_edit_coupon" value="true" />

			<?php wp_nonce_field( 'wpsc_coupon', 'wpsc-coupon-edit' ); ?>
			<?php submit_button( __( 'Update Coupon', 'wp-e-commerce' ), 'primary' ); ?>

		</form>
	</div>
</div><!--end .wrap-->
