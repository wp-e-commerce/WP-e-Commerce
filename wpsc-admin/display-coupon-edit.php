<?php

// die if accessed directly
if( !defined( 'ABSPATH' ) )
	die();

$coupon_id = absint( $_GET['coupon'] );
$coupon    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE `id` = %d", $coupon_id ), ARRAY_A );
?>
<div class="wrap" id="coupon_data">
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
							<input name="edit_coupon_amount" id="edit_coupon_amount" type="number" value="<?php esc_attr_e( $coupon['value'] ); ?>" class="small-text" min="0" />
							<span class="description"><?php _e( 'The discount amount', 'wpsc' ); ?></span>
						</td>
					</tr>

					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="edit_discount_type"><?php _e( 'Discount Type', 'wpsc' ); ?></label>
						</th>
						<td>
							<?php $type = absint( $coupon['is-percentage'] ); ?>
							<select name='edit_discount_type' id='edit_discount_type'>
								<option value='0'<?php selected( 0, $type ); ?>><?php _e( 'Fixed Amount', 'wpsc' ); ?></option>
								<option value='1'<?php selected( 1, $type ); ?>><?php _e( 'Percentage', 'wpsc' ); ?></option>
								<option value='2'<?php selected( 2, $type ); ?>><?php _e( 'Free shipping', 'wpsc' ); ?></option>
							</select>
							<p class="description"><?php _e( 'The discount type', 'wpsc' ); ?></p>

							<?php $display = $type == 2 ? '' : 'style="display:none;"'; ?>
						</td>
					</tr>

					<tr class="form-field">
						<th scope="row" valign="top">
							<label for="edit_coupon_start"><?php _e( 'Start and End', 'wpsc' ); ?></label>
						</th>
						<td>
							<?php
							$start = $coupon['start']  == '0000-00-00 00:00:00' ? '' : get_date_from_gmt( $coupon['start'], 'Y-m-d' );
							$end   = $coupon['expiry'] == '0000-00-00 00:00:00' ? '' : get_date_from_gmt( $coupon['expiry'], 'Y-m-d' );
							?>
							<span class="description"><?php _e( 'Start: ', 'wpsc' ); ?></span>
							<input name="edit_coupon_start" id="edit_coupon_start" type="text" value="<?php esc_attr_e( $start ); ?>" class="regular-text pickdate" style="width: 100px"/>
							<span class="description"><?php _e( 'End: ', 'wpsc' ); ?></span>
							<input name="edit_coupon_end" id="edit_coupon_end" type="text" value="<?php esc_attr_e( $end ); ?>" class="regular-text pickdate" style="width: 100px"/>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<?php _e( 'Active', 'wpsc' ); ?>
						</th>
						<td>
							<input type='hidden' value='0' name='edit_coupon_active' />
							<input type="checkbox" value='1'<?php checked( 1, $coupon['active'] ); ?> name='edit_coupon_active' id="edit_coupon_active" />
							<label for="edit_coupon_active"><?php _e( 'Is this coupon active?', 'wpsc' ) ?></label>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<?php _e( 'Use Once', 'wpsc' ); ?>
						</th>
						<td>
							<input type='hidden' value='0' name='edit_coupon_use_once' />
							<input type='checkbox' value='1'<?php checked( 1, $coupon['use-once'] ); ?> name='edit_coupon_use_once' id="edit_coupon_use_once" />
							<label for="edit_coupon_use_once"><?php _e( 'Deactivate coupon after it has been used.', 'wpsc' ) ?></label>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<?php _e( 'Apply On All Products', 'wpsc' ); ?>
						</th>
						<td>
							</span><input type='hidden' value='0' name='edit_coupon_every_product' />
							<input type="checkbox" value="1"<?php checked( 1, $coupon['every_product'] ); ?> name='edit_coupon_every_product' id="edit-coupon-every-product"/>
							<label for="edit-coupon-every-product"><?php _e( 'This coupon affects each product at checkout.', 'wpsc' ) ?></label>
						</td>
					</tr>

					<tr class="form-field coupon-conditions">
						<th scope="row" valign="top">
							<label><strong><?php _e( 'Conditions', 'wpsc' ); ?></strong></label>
						</th>
						<td>
							<input type="hidden" name="rules[operator][]" value="" />
							<?php
							$conditions = maybe_unserialize( $coupon['condition'] );

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
										<option value="and"<?php selected( 'and', $condition["operator"] ); ?>><?php _ex( 'AND', 'Coupon comparison logic', 'wpsc' );?></option>
										<option value="or"<?php  selected( 'or' , $condition["operator"] ); ?>><?php _ex( 'OR' , 'Coupon comparison logic', 'wpsc' );?></option>
									</select>
								<?php endif; ?>
									<select class="ruleprops" name="rules[property][]">
										<option value="item_name"<?php selected( 'item_name', $condition['property'] ); ?> rel="order"><?php _e( 'Item name', 'wpsc' ); ?></option>
										<option value="item_quantity"<?php selected( 'item_quantity', $condition['property'] ); ?> rel="order"><?php _e( 'Item quantity', 'wpsc' ); ?></option>
										<option value="total_quantity"<?php selected( 'total_quantity', $condition['property'] ); ?> rel="order"><?php _e( 'Total quantity', 'wpsc' ); ?></option>
										<option value="subtotal_amount"<?php selected( 'subtotal_amount', $condition['property'] ); ?> rel="order"><?php _e( 'Subtotal amount', 'wpsc' ); ?></option>
										<?php do_action( 'wpsc_coupon_rule_property_options' ); ?>
									</select>

									<select name="rules[logic][]">
										<option value="equal"<?php selected( 'equal', $condition['logic'] ); ?>><?php _e( 'Is equal to', 'wpsc' ); ?></option>
										<option value="greater"<?php selected( 'greater', $condition['logic'] ); ?>><?php _e( 'Is greater than', 'wpsc' ); ?></option>
										<option value="less"<?php selected( 'less', $condition['logic'] ); ?>><?php _e( 'Is less than', 'wpsc' ); ?></option>
										<option value="contains"<?php selected( 'contains', $condition['logic'] ); ?>><?php _e( 'Contains', 'wpsc' ); ?></option>
										<option value="not_contain"<?php selected( 'not_contain', $condition['logic'] ); ?>><?php _e( 'Does not contain', 'wpsc' ); ?></option>
										<option value="begins"<?php selected( 'begins', $condition['logic'] ); ?>><?php _e( 'Begins with', 'wpsc' ); ?></option>
										<option value="ends"<?php selected( 'ends', $condition['logic'] ); ?>><?php _e( 'Ends with', 'wpsc' ); ?></option>
										<option value="category"<?php selected( 'category', $condition['logic'] ); ?>><?php _e( 'In Category', 'wpsc' ); ?></option>
									</select>

									<input type="text" name="rules[value][]" value="<?php esc_attr_e( $condition['value'] ); ?>" style="width: 150px;"/>
									<a title="<?php esc_attr_e( 'Delete condition', 'wpsc' ); ?>" class="button-secondary wpsc-button-round wpsc-button-minus" href="#"><?php echo _x( '&ndash;', 'delete item', 'wpsc' ); ?></a>
									<a title="<?php esc_attr_e( 'Add condition', 'wpsc' ); ?>" class="button-secondary wpsc-button-round wpsc-button-plus" href="#"><?php echo _x( '+', 'add item', 'wpsc' ); ?></a>
								</div>
							<?php endforeach; ?>
						</td>
					</tr>

				</tbody>
			</table>
			<input type="hidden" name="coupon_id" value="<?php echo esc_attr( $coupon_id ); ?>"/>
			<input type="hidden" name="edit_coupon_is_used" value="<?php echo esc_attr( $coupon['is-used'] ); ?>"/>
			<input type="hidden" name="is_edit_coupon" value="true"/>
			<?php submit_button( __( 'Update Coupon', 'wpsc' ), 'primary' ); ?>

		</form>
	</div>
</div><!--end .wrap-->
