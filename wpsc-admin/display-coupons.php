<?php

function wpsc_display_coupons_page() {
	global $wpdb;
	if ( isset( $_POST ) && is_array( $_POST ) && !empty( $_POST ) ) {

		if ( isset( $_POST['add_coupon'] ) && ($_POST['add_coupon'] == 'true') && (!isset( $_POST['is_edit_coupon'] ) || !($_POST['is_edit_coupon'] == 'true')) ) {

			$coupon_code   = $_POST['add_coupon_code'];
			$discount      = (double)$_POST['add_discount'];
			$discount_type = (int)$_POST['add_discount_type'];
			$use_once      = (int)(bool)$_POST['add_use-once'];
			$every_product = (int)(bool)$_POST['add_every_product'];
			$is_active     = (int)(bool)$_POST['add_active'];
			$start_date    = date( 'Y-m-d', strtotime( $_POST['add_start'] ) ) . " 00:00:00";
			$end_date      = date( 'Y-m-d', strtotime( $_POST['add_end'] ) ) . " 00:00:00";
			$rules         = $_POST['rules'];

			foreach ( $rules as $key => $rule ) {
				foreach ( $rule as $k => $r ) {
					$new_rule[$k][$key] = $r;
				}
			}

			foreach ( $new_rule as $key => $rule ) {
				if ( '' == $rule['value'] ) {
					unset( $new_rule[$key] );
				}
			}

			$insert = $wpdb->insert(
				    WPSC_TABLE_COUPON_CODES,
				    array(
						'coupon_code' => $coupon_code,
						'value' => $discount,
						'is-percentage' => $discount_type,
						'use-once' => $use_once,
						'is-used' => 0,
						'active' => $is_active,
						'every_product' => $every_product,
						'start' => $start_date,
						'expiry' => $end_date,
						'condition' => serialize( $new_rule )
				    ),
				    array(
						'%s',
						'%f',
						'%d',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s'
				    )
				);
			if ( $insert )
			    echo "<div class='updated'><p align='center'>" . esc_html__( 'Thanks, the coupon has been added.', 'wpsc' ) . "</p></div>";

		}

		if ( isset( $_POST['is_edit_coupon'] ) && ($_POST['is_edit_coupon'] == 'true') && !(isset( $_POST['delete_condition'] )) && !(isset( $_POST['submit_condition'] )) ) {

			foreach ( (array)$_POST['edit_coupon'] as $coupon_id => $coupon_data ) {

				$coupon_id             = (int)$coupon_id;
				$coupon_data['start']  = get_gmt_from_date( $coupon_data['start'] . " 00:00:00" );
				$coupon_data['expiry'] = get_gmt_from_date( $coupon_data['expiry'] . " 23:59:59" );
				$check_values          = $wpdb->get_row( $wpdb->prepare( "SELECT `id`, `coupon_code`, `value`, `is-percentage`, `use-once`, `active`, `start`, `expiry`,`every_product` FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE `id` = %d", $coupon_id ), ARRAY_A );

				// Sort both arrays to make sure that if they contain the same stuff,
				// that they will compare to be the same, may not need to do this, but what the heck
				if ( $check_values != null )
					ksort( $check_values );

				ksort( $coupon_data );

				if ( $check_values != $coupon_data ) {

					$insert_array = array();

					foreach ( $coupon_data as $coupon_key => $coupon_value ) {
						if ( ($coupon_key == "submit_coupon") || ($coupon_key == "delete_coupon") )
							continue;

						if ( isset( $check_values[$coupon_key] ) && $coupon_value != $check_values[$coupon_key] )
							$insert_array[] = "`$coupon_key` = '$coupon_value'";

					}

					if ( isset( $check_values['every_product'] ) && $coupon_data['add_every_product'] != $check_values['every_product'] )
						$insert_array[] = "`every_product` = '$coupon_data[add_every_product]'";

					if ( count( $insert_array ) > 0 )
					    $wpdb->query( $wpdb->prepare( "UPDATE `" . WPSC_TABLE_COUPON_CODES . "` SET " . implode( ", ", $insert_array ) . " WHERE `id` = %d LIMIT 1;", $coupon_id ) );

					unset( $insert_array );
					$rules = $_POST['rules'];

					foreach ( (array)$rules as $key => $rule ) {
						foreach ( $rule as $k => $r ) {
							$new_rule[$k][$key] = $r;
						}
					}

					foreach ( (array)$new_rule as $key => $rule ) {
						if ( $rule['value'] == '' ) {
							unset( $new_rule[$key] );
						}
					}

					$conditions = $wpdb->get_var( $wpdb->prepare( "SELECT `condition` FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE `id` = %d LIMIT 1", $_POST['coupon_id'] ) );
					$conditions = unserialize( $conditions );
					$new_cond = array();

					if ( $_POST['rules']['value'][0] != '' ) {
						$new_cond['property'] = $_POST['rules']['property'][0];
						$new_cond['logic'] = $_POST['rules']['logic'][0];
						$new_cond['value'] = $_POST['rules']['value'][0];
						$conditions [] = $new_cond;
					}

					$wpdb->update(
						    WPSC_TABLE_COUPON_CODES,
						    array(
							'condition' => serialize( $conditions ),

						    ),
						    array(
							'id' => $_POST['coupon_id']
						    ),
						    '%s',
						    '%d'
						);
				}
			}
		}

		if ( isset( $_POST['delete_condition'] ) ) {

			$conditions = $wpdb->get_var( $wpdb->prepare( "SELECT `condition` FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE `id` = %d LIMIT 1", $_POST['coupon_id'] ) );
			$conditions = unserialize( $conditions );

			unset( $conditions[(int)$_POST['delete_condition']] );

			$wpdb->update(
				WPSC_TABLE_COUPON_CODES,
				array(
				    'condition' => serialize( $conditions ),

				),
				array(
				    'id' => $_POST['coupon_id']
				),
				'%s',
				'%d'
			    );
		}

		if ( isset( $_POST['submit_condition'] ) ) {
			$conditions = $wpdb->get_var( "SELECT `condition` FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE `id` = '" . (int)$_POST['coupon_id'] . "' LIMIT 1" );
			$conditions = unserialize( $conditions );

			$new_cond             = array();
			$new_cond['property'] = $_POST['rules']['property'][0];
			$new_cond['logic']    = $_POST['rules']['logic'][0];
			$new_cond['value']    = $_POST['rules']['value'][0];
			$conditions[]         = $new_cond;

			$wpdb->update(
				    WPSC_TABLE_COUPON_CODES,
				    array(
					'condition' => serialize( $conditions )
				    ),
				    array(
					'id' => $_POST['coupon_id']
				    ),
				    '%s',
				    '%d'
				);

		}
	}

	$currency_data = $wpdb->get_row( "SELECT `symbol`,`symbol_html`,`code` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id`='" . esc_attr( get_option( 'currency_type' ) ) . "' LIMIT 1", ARRAY_A );
	$currency_sign = ! empty( $currency_data['symbol'] ) ? $currency_data['symbol_html'] : $currency_data['code'];

	?>

	<script type='text/javascript'>
		jQuery(".pickdate").datepicker();
		/* jQuery datepicker selector */
		if (typeof jQuery('.pickdate').datepicker != "undefined") {
			jQuery('.pickdate').datepicker({ dateFormat: 'yy-mm-dd' });
		}
	</script>

	<div class="wrap">
		<h2>
			<?php esc_html_e( 'Coupons', 'wpsc' ); ?>
			<a href="#" id="add_coupon_box_link" class="add_item_link button add-new-h2" onClick="return show_status_box( 'add_coupon_box', 'add_coupon_box_link' );">
				<?php esc_html_e( 'Add New', 'wpsc' ); ?>
			</a>
		</h2>

		<table style="width: 100%;">
			<tr>
				<td id="coupon_data">
					<div id='add_coupon_box' class='modify_coupon' >
						<form name='add_coupon' method='post' action=''>
							<table class='add-coupon' >
								<tr>
									<th><?php esc_html_e( 'Coupon Code', 'wpsc' ); ?></th>
									<th><?php esc_html_e( 'Discount', 'wpsc' ); ?></th>
									<th><?php esc_html_e( 'Start', 'wpsc' ); ?></th>
									<th><?php esc_html_e( 'Expiry', 'wpsc' ); ?></th>
								</tr>
								<tr>
									<td>
										<input type='text' value='' name='add_coupon_code' />
									</td>
									<td>
										<input type='text' value='' size='3' name='add_discount' />
										<select name='add_discount_type'>
											<option value='0' ><?php echo esc_html( $currency_sign ) ?></option>
											<option value='1' ><?php _ex( '%', 'Percentage sign as discount type in coupons page', 'wpsc' ); ?></option>
											<option value='2' ><?php esc_html_e( 'Free shipping', 'wpsc' ); ?></option>
										</select>
									</td>
									<td>
										<input type='text' class='pickdate' size='11' value="<?php echo date('Y-m-d'); ?>" name='add_start' />
									</td>
									<td>
										<input type='text' class='pickdate' size='11' name='add_end' value="<?php echo (date('Y')+1) . date('-m-d') ; ?>">
									</td>
									<td>
										<input type='hidden' value='true' name='add_coupon' />
										<input type='submit' value='<?php esc_attr_e( 'Add Coupon', 'wpsc' ); ?>' name='submit_coupon' class='button-primary' />
									</td>
								</tr>

								<tr>
									<td colspan='3' scope="row">
										<p>
											<span class='input_label'><?php esc_html_e( 'Active', 'wpsc' ); ?></span><input type='hidden' value='0' name='add_active' />
											<input type='checkbox' value='1' checked='checked' name='add_active' />
											<span class='description'><?php esc_html_e( 'Activate coupon on creation.', 'wpsc' ) ?></span>
										</p>
									</td>
								</tr>

								<tr>
									<td colspan='3' scope="row">
										<p>
											<span class='input_label'><?php esc_html_e( 'Use Once', 'wpsc' ); ?></span><input type='hidden' value='0' name='add_use-once' />
											<input type='checkbox' value='1' name='add_use-once' />
											<span class='description'><?php esc_html_e( 'Deactivate coupon after it has been used.', 'wpsc' ) ?></span>
										</p>
									</td>
								</tr>

								<tr>
									<td colspan='3' scope="row">
										<p>
											<span class='input_label'><?php esc_html_e( 'Apply On All Products', 'wpsc' ); ?></span><input type='hidden' value='0' name='add_every_product' />
											<input type="checkbox" value="1" name='add_every_product'/>
											<span class='description'><?php esc_html_e( 'This coupon affects each product at checkout.', 'wpsc' ) ?></span>
										</p>
									</td>
								</tr>

								<tr><td colspan='3'><span id='table_header'><?php esc_html_e( 'Conditions', 'wpsc' ); ?></span></td></tr>
								<tr>
									<td colspan="8">
									<div class='coupon_condition' >
										<div class='first_condition'>
											<select class="ruleprops" name="rules[property][]">
												<option value="item_name" rel="order"><?php esc_html_e( 'Item name', 'wpsc' ); ?></option>
												<option value="item_quantity" rel="order"><?php esc_html_e( 'Item quantity', 'wpsc' ); ?></option>
												<option value="total_quantity" rel="order"><?php esc_html_e( 'Total quantity', 'wpsc' ); ?></option>
												<option value="subtotal_amount" rel="order"><?php esc_html_e( 'Subtotal amount', 'wpsc' ); ?></option>
												<?php echo apply_filters( 'wpsc_coupon_rule_property_options', '' ); ?>
											</select>

											<select name="rules[logic][]">
												<option value="equal"><?php esc_html_e( 'Is equal to', 'wpsc' ); ?></option>
												<option value="greater"><?php esc_html_e( 'Is greater than', 'wpsc' ); ?></option>
												<option value="less"><?php esc_html_e( 'Is less than', 'wpsc' ); ?></option>
												<option value="contains"><?php esc_html_e( 'Contains', 'wpsc' ); ?></option>
												<option value="not_contain"><?php esc_html_e( 'Does not contain', 'wpsc' ); ?></option>
												<option value="begins"><?php esc_html_e( 'Begins with', 'wpsc' ); ?></option>
												<option value="ends"><?php esc_html_e( 'Ends with', 'wpsc' ); ?></option>
												<option value="category"><?php esc_html_e( 'In Category', 'wpsc' ); ?></option>
											</select>

											<span><input type="text" name="rules[value][]"/></span>
											<script>
												var coupon_number=1;
												function add_another_property(this_button){
													var new_property='<div class="coupon_condition">\n'+
														'<div> \n'+
														'<select class="ruleprops" name="rules[property][]"> \n'+
														'<option value="item_name" rel="order"><?php echo esc_js( __( 'Item name', 'wpsc' ) ); ?></option> \n'+
														'<option value="item_quantity" rel="order"><?php echo esc_js( __( 'Item quantity', 'wpsc' ) ); ?></option>\n'+
														'<option value="total_quantity" rel="order"><?php echo esc_js( __( 'Total quantity', 'wpsc' ) ); ?></option>\n'+
														'<option value="subtotal_amount" rel="order"><?php echo esc_js( __( 'Subtotal amount', 'wpsc' ) ); ?></option>\n'+
														'<?php echo apply_filters( 'wpsc_coupon_rule_property_options', '' ); ?>'+
														'</select> \n'+
														'<select name="rules[logic][]"> \n'+
														'<option value="equal"><?php echo esc_js( __( 'Is equal to', 'wpsc' ) ); ?></option> \n'+
														'<option value="greater"><?php echo esc_js( __( 'Is greater than', 'wpsc' ) ); ?></option> \n'+
														'<option value="less"><?php echo esc_js( __( 'Is less than', 'wpsc' ) ); ?></option> \n'+
														'<option value="contains"><?php echo esc_js( __( 'Contains', 'wpsc' ) ); ?></option> \n'+
														'<option value="not_contain"><?php echo esc_js( __( 'Does not contain', 'wpsc' ) ); ?></option> \n'+
														'<option value="begins"><?php echo esc_js( __( 'Begins with', 'wpsc' ) ); ?></option> \n'+
														'<option value="ends"><?php echo esc_js( __( 'Ends with', 'wpsc' ) ); ?></option> \n'+
														'</select> \n'+
														'<span> \n'+
														'<input type="text" name="rules[value][]"/> \n'+
														'</span>  \n'+
														'<img height="16" width="16" class="delete" alt="<?php esc_attr_e( 'Delete', 'wpsc' ); ?>" src="<?php echo WPSC_CORE_IMAGES_URL; ?>/cross.png" onclick="jQuery(this).parent().remove();"/></div> \n'+
														'</div> ';

													jQuery('#coupon_data .coupon_condition :last').after(new_property);
													coupon_number++;
												}
											</script>
										</div>
									</div>
								</tr>

								<tr>
									<td>
										<a class="wpsc_coupons_condition_add" onclick="add_another_property(jQuery(this));">
											<?php esc_html_e( 'Add New Condition', 'wpsc' ); ?>
										</a>
									</td>
								</tr>
							</table>
						</form>
					</div>
				</td>
			</tr>
		</table>

		<?php
			$columns = array(
				'coupon_code'    => __( 'Coupon Code', 'wpsc' ),
				'discount'       => __( 'Discount', 'wpsc' ),
				'start'          => __( 'Start', 'wpsc' ),
				'expiry'         => __( 'Expiry', 'wpsc' ),
				'active'         => __( 'Active', 'wpsc' ),
				'apply_on_prods' => __( 'Apply On All Products', 'wpsc' ),
				'edit'           => __( 'Edit', 'wpsc' )
			);
			register_column_headers( 'display-coupon-details', $columns );
		?>

		<table class="coupon-list widefat" cellspacing="0">
			<thead>
				<tr>
					<?php print_column_headers( 'display-coupon-details' ); ?>
				</tr>
			</thead>

			<tfoot>
				<tr>
					<?php print_column_headers( 'display-coupon-details', false ); ?>
				</tr>
			</tfoot>

			<tbody>
				<?php
					$i = 0;
					$coupon_data = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_COUPON_CODES . "` ", ARRAY_A );

					foreach ( (array)$coupon_data as $coupon ) {
						$alternate = "";
						$i++;
						if ( ($i % 2) != 0 ) {
							$alternate = "class='alt'";
						}

						$start = get_date_from_gmt( $coupon['start'], 'd/m/Y' );
						$expiry = get_date_from_gmt( $coupon['expiry'], 'd/m/Y' );

						echo "<tr $alternate>\n\r";

						echo "    <td>\n\r";
						echo esc_attr( $coupon['coupon_code'] );
						echo "    </td>\n\r";

						echo "    <td>\n\r";
						if ( $coupon['is-percentage'] == 1 )
							echo esc_attr( $coupon['value'] ) . "%";

						else if ( $coupon['is-percentage'] == 2 )
							_e( 'Free Shipping', 'wpsc' );

						else
							echo wpsc_currency_display( esc_attr( $coupon['value'] ) );

						echo "    </td>\n\r";

						echo "    <td>\n\r";
						echo $start;
						echo "    </td>\n\r";

						echo "    <td>\n\r";
						echo $expiry;
						echo "    </td>\n\r";

						echo "    <td>\n\r";
						switch ( $coupon['active'] ) {
							case 1:
								echo "<img src='" . WPSC_CORE_IMAGES_URL . "/yes_stock.gif' alt='' title='' />";
								break;

							case 0: default:
								echo "<img src='" . WPSC_CORE_IMAGES_URL . "/no_stock.gif' alt='' title='' />";
								break;
						}
						echo "    </td>\n\r";

						echo "    <td>\n\r";
						switch ( $coupon['every_product'] ) {
							case 1:
								echo "<img src='" . WPSC_CORE_IMAGES_URL . "/yes_stock.gif' alt='' title='' />";
								break;

							case 0: default:
								echo "<img src='" . WPSC_CORE_IMAGES_URL . "/no_stock.gif' alt='' title='' />";
								break;
						}

						echo "    </td>\n\r";
						echo "    <td>\n\r";
						echo "<a title='" . esc_attr( $coupon['coupon_code'] ). "' href='#' rel='" . $coupon['id'] . "' class='wpsc_edit_coupon'  >" . esc_html__( 'Edit', 'wpsc' ) . "</a>";
						echo "    </td>\n\r";
						echo "  </tr>\n\r";
						echo "  <tr class='coupon_edit'>\n\r";
						echo "    <td colspan='7' style='padding-left:0px;'>\n\r";
						echo "      <div id='coupon_box_" . $coupon['id'] . "' class='displaynone modify_coupon' >\n\r";
						coupon_edit_form( $coupon );
						echo "      </div>\n\r";
						echo "    </td>\n\r";
						echo "  </tr>\n\r";
					}
				?>
			</tbody>
		</table>

		<p style='margin: 10px 0px 5px 0px;'>
			<?php _e( '<strong>Note:</strong> Due to a current PayPal limitation, when a purchase is made using a coupon we cannot send a detailed list of items through for processing. Instead we send the total amount of the purchase so the customer will see your shop name and the total within PayPal.', 'wpsc' ); ?>
		</p>

	</div>

<?php

}

?>