<?php
function coupon_edit_form($coupon) {

$conditions = maybe_unserialize($coupon['condition']);

  $start_timestamp = strtotime($coupon['start']);
  $end_timestamp = strtotime($coupon['expiry']);
  $id = $coupon['id'];
  $output = '';
  $output .= "<form name='edit_coupon' id='".$coupon['coupon_code']."' method='post' action=''>\n\r";
  $output .= "<table class='add-coupon'>\n\r";
  $output .= " <tr>\n\r";
  $output .= "   <th>".esc_html__('Coupon Code', 'wpsc')."</th>\n\r";
  $output .= "   <th>".esc_html__('Discount', 'wpsc')."</th>\n\r";
  $output .= "   <th>".esc_html__('Start', 'wpsc')."</th>\n\r";
  $output .= "   <th>".esc_html__('Expiry', 'wpsc')."</th>\n\r";
  $output .= "   <th>".esc_html__('Use Once', 'wpsc')."</th>\n\r";
  $output .= "   <th>".esc_html__('Active', 'wpsc')."</th>\n\r";
	$output .= "   <th>".esc_html__('Apply On All Products', 'wpsc')."</th>\n\r";
  $output .= "   <th></th>\n\r";
  $output .= " </tr>\n\r";
  $output .= " <tr>\n\r";
  $output .= "  <td>\n\r";
  $output .= "   <input type='hidden' value='true' name='is_edit_coupon' />\n\r";
  $output .= "   <input type='text' size='11' value='".$coupon['coupon_code']."' name='edit_coupon[".$id."][coupon_code]' />\n\r";
  $output .= "  </td>\n\r";
  $output .= "  <td>\n\r";

  $output .= "   <input type='text' size='3' value='".$coupon['value']."'  name=edit_coupon[".$id."][value]' />";
  $output .= "   <select name='edit_coupon[".$id."][is-percentage]'>";
  $output .= "     <option value='0' ".(($coupon['is-percentage'] == 0) ? "selected='true'" : '')." >$</option>\n\r";//
  $output .= "     <option value='1' ".(($coupon['is-percentage'] == 1) ? "selected='true'" : '')." >%</option>\n\r";
  $output .= "     <option value='2' ".(($coupon['is-percentage'] == 2) ? "selected='true'" : '')." >" . esc_html__( 'Free shipping', 'wpsc' ) . "</option>\n\r";
  $output .= "   </select>\n\r";
  $output .= "  </td>\n\r";
  $output .= "  <td>\n\r";
  $coupon_start = get_date_from_gmt( $coupon['start'], 'Y-m-d' );
  $output .= "<input type='text' class='pickdate' size='11' name='edit_coupon[".$id."][start]' value='{$coupon_start}'>";
  $output .= "  </td>\n\r";
  $output .= "  <td>\n\r";
  $coupon_expiry = get_date_from_gmt( $coupon['expiry'], 'Y-m-d' );
  $output .= "<input type='text' class='pickdate' size='11' name='edit_coupon[".$id."][expiry]' value='{$coupon_expiry}'>";
  $output .= "  </td>\n\r";
  $output .= "  <td>\n\r";
  $output .= "   <input type='hidden' value='0' name='edit_coupon[".$id."][use-once]' />\n\r";
  $output .= "   <input type='checkbox' value='1' ".(($coupon['use-once'] == 1) ? "checked='checked'" : '')." name='edit_coupon[".$id."][use-once]' />\n\r";
  $output .= "  </td>\n\r";
  $output .= "  <td>\n\r";
  $output .= "   <input type='hidden' value='0' name='edit_coupon[".$id."][active]' />\n\r";
  $output .= "   <input type='checkbox' value='1' ".(($coupon['active'] == 1) ? "checked='checked'" : '')." name='edit_coupon[".$id."][active]' />\n\r";
  $output .= "  </td>\n\r";
  $output .= "  <td>\n\r";
  $output .= "   <input type='hidden' value='0' name='edit_coupon[".$id."][add_every_product]' />\n\r";
  $output .= "   <input type='checkbox' value='1' ".(($coupon['every_product'] == 1) ? "checked='checked'" : '')." name='edit_coupon[".$id."][add_every_product]' />\n\r";
  $output .= "  </td>\n\r";
  $output .= "  <td>\n\r";
  $output .= "   <input type='hidden' value='".$id."' name='edit_coupon[".$id."][id]' />\n\r";
  $output .= "  </td>\n\r";
  $output .= " </tr>\n\r";

  if($conditions != null){
	  $output .= "<tr>";
	  $output .= "<th>";
	  $output .= esc_html__("Conditions", 'wpsc');
	  $output .= "</th>";
	  $output .= "</tr>";
	  $output .= "<th>";
	  $output .= esc_html__("Delete", 'wpsc');
	  $output .= "</th>";
	  $output .= "<th>";
	  $output .= esc_html__("Property", 'wpsc');
	  $output .= "</th>";
	  $output .= "<th>";
	  $output .= esc_html__("Logic", 'wpsc');
	  $output .= "</th>";
	  $output .= "<th>";
	  $output .= esc_html__("Value", 'wpsc');
	  $output .= "</th>";
	  $output .= " </tr>\n\r";

	  foreach ($conditions as $i => $condition){
		  $output .= "<tr>";
		  $output .= "<td>";
		  $output .= "<input type='hidden' name='coupon_id' value='".$id."' />";
		  $output .= "<input type='submit' id='delete_condition".$i."' style='display:none;' value='".$i."' name='delete_condition' />";
		  $output .= "<span style='cursor:pointer;' class='delete_button' onclick='jQuery(\"#delete_condition".$i."\").click()'>" . esc_html__('Delete', 'wpsc' ) . "</span>";
		  $output .= "</td>";
		  $output .= "<td>";
		  $output .= $condition['property'];
		  $output .= "</td>";
		  $output .= "<td>";
		  $output .= $condition['logic'];
		  $output .= "</td>";
		  $output .= "<td>";
		  $output .= $condition['value'];
		  $output .= "</td>";
		  $output .= "</tr>";
		  $i++;
	  }
	  $output .=	wpsc_coupons_conditions( $id);
  }elseif($conditions == null){
  	$output .=	wpsc_coupons_conditions( $id);

  }
  $output .= "</table>\n\r";
  $output .= "</form>\n\r";
  echo $output;
  return $output;
  }
function wpsc_coupons_conditions($id){
?>

<?php

$output ='
<input type="hidden" name="coupon_id" value="'.$id.'" />
<tr><td colspan="3"><b>' . esc_html__( 'Add Conditions', 'wpsc') . '</b></td></tr>
<tr><td colspan="6">
	<div class="coupon_condition">
		<div>
			<select class="ruleprops" name="rules[property][]">
				<option value="item_name" rel="order">' . esc_html__( 'Item name', 'wpsc') . '</option>
				<option value="item_quantity" rel="order">' . esc_html__( 'Item quantity', 'wpsc') . '</option>
				<option value="total_quantity" rel="order">' . esc_html__( 'Total quantity', 'wpsc') . '</option>
				<option value="subtotal_amount" rel="order">' . esc_html__( 'Subtotal amount', 'wpsc') . '</option>
				' . apply_filters( 'wpsc_coupon_rule_property_options', '' ) . '
			</select>
			<select name="rules[logic][]">
				<option value="equal">' . esc_html__( 'Is equal to', 'wpsc') . '</option>
				<option value="greater">' . esc_html__( 'Is greater than', 'wpsc') . '</option>
				<option value="less">' . esc_html__( 'Is less than', 'wpsc') . '</option>
				<option value="contains">' . esc_html__( 'Contains', 'wpsc') . '</option>
				<option value="not_contain">' . esc_html__( 'Does not contain', 'wpsc') . '</option>
				<option value="begins">' . esc_html__( 'Begins with', 'wpsc') . '</option>
				<option value="ends">' . esc_html__( 'Ends with', 'wpsc') . '</option>
			</select>
			<span>
				<input type="text" name="rules[value][]"/>
			</span>


		</div>
	</div>
	</td>
	<td>
	</td>
	<td colspan="3">
		<input type="submit" value="'.esc_attr__("Update Coupon", "wpsc").'" class="button-primary" name="edit_coupon['.$id.'][submit_coupon]" />';

 		$nonced_url = admin_url( 'admin.php' );
 		$nonced_url = add_query_arg(
 			array(
				'wpsc_admin_action' => 'wpsc-delete-coupon',
				'delete_id'         => $id,
				'_wp_http_referer'  => urlencode( admin_url( 'edit.php?post_type=wpsc-product&page=wpsc-edit-coupons' ) ),
 			),
 			$nonced_url
 		);
 		$nonced_url = wp_nonce_url( $nonced_url, 'delete-coupon' );

		$output.= "<a class='delete_button' style='text-decoration:none;' href=" .$nonced_url.">" . esc_html__( 'Delete', 'wpsc' ) . "</a>";

 	$output.='
 	</td>
</tr>
';
return $output;

}
function setting_button(){
	$next_url	= 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']."?page=wpsc-edit-products";

// 	$output.="<div><img src='" . plugins_url( WPSC_DIR_NAME . '/images/settings_button.jpg' ) . "' onclick='display_settings_button()'>";
	$output.="<div style='float: right; margin-top: 0px; position: relative;'> | <a href='#' onclick='display_settings_button(); return false;' style='text-decoration: underline;'>".esc_html__('Settings', 'wpsc')." &raquo;</a>";
	$output.="<span id='settings_button' style='width:180px;background-color:#f1f1f1;position:absolute; right: 10px; border:1px solid black; display:none;'>";
	$output.="<ul class='settings_button'>";

	$output.="<li><a href='admin.php?page=wpsc-settings'>".__('Shop Settings', 'wpsc')."</a></li>";
	$output.="<li><a href='admin.php?page=wpsc-settings&amp;tab=gateway'>".__('Money and Payment', 'wpsc')."</a></li>";
	$output.="<li><a href='admin.php?page=wpsc-settings&amp;tab=checkout'>".__('Checkout Page Settings', 'wpsc')."</a></li>";
	$output.="</ul>";
	$output.="</span>&emsp;&emsp;</div>";

	return $output;
}

function wpsc_right_now() {
	global $wpdb;
	$year = date("Y");
	$month = date("m");
	$start_timestamp = mktime(0, 0, 0, $month, 1, $year);
	$end_timestamp = mktime(0, 0, 0, ($month+1), 0, $year);
	$product_count = $wpdb->get_var("SELECT COUNT(*)
		FROM `".$wpdb->posts."`
		WHERE `post_status` = 'publish'
		AND `post_type` = 'wpsc-product'"
	);
	$group_count = count(get_terms("wpsc_product_category"));
	$sales_count = $wpdb->get_var("SELECT COUNT(*) FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `date` BETWEEN '".$start_timestamp."' AND '".$end_timestamp."'");
	$monthtotal = wpsc_currency_display( admin_display_total_price( $start_timestamp,$end_timestamp ) );
	$overaltotal = wpsc_currency_display( admin_display_total_price() );
	$variation_count = count(get_terms("wpsc-variation", array('parent' => 0)));
	$pending_sales = $wpdb->get_var("SELECT COUNT(*) FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `processed` IN ('1','2')");
	$accept_sales = $wpdb->get_var("SELECT COUNT(*) FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `processed` IN ('3' ,'4', '5')");
	$theme = get_option('wpsc_selected_theme');
	?>
	<div class='table'>
		<p class='sub'><?php esc_html_e( 'At a Glance', 'wpsc' ); ?></p>
		<table style='border-top:1px solid #ececec;'>
			<tr class='first'>
				<td class='first b'>
					<?php echo $product_count; ?>
				</td>
				<td class='t'>
					<?php echo _nx( 'Product', 'Products', $product_count, 'dashboard widget', 'wpsc' ); ?>
				</td>
				<td class='b'>
					<?php echo $sales_count; ?>
				</td>
				<td class='last'>
					<?php echo _nx( 'Sale', 'Sales', $sales_count, 'dashboard widget', 'wpsc' ); ?>
				</td>
			</tr>
			<tr>
				<td class='first b'>
					<?php echo $group_count; ?>
				</td>
				<td class='t'>
					<?php echo _nx( 'Category', 'Categories', $group_count, 'dashboard widget', 'wpsc' ); ?>
				</td>
				<td class='b'>
					<?php echo $pending_sales; ?>
				</td>
				<td class='last t waiting'>
					<?php echo _n( 'Pending sale', 'Pending sales', $pending_sales, 'wpsc' ); ?>
				</td>
			</tr>
			<tr>
				<td class='first b'>
					<?php echo $variation_count; ?>
				</td>
				<td class='t'>
					<?php echo _nx( 'Variation', 'Variations', $variation_count, 'dashboard widget', 'wpsc' ); ?>
				</td>
				<td class='b'>
					<?php echo $accept_sales; ?>
				</td>
				<td class='last t approved'>
					<?php echo _n( 'Closed sale', 'Closed sales', $accept_sales, 'wpsc'); ?>
				</td>
			</tr>
		</table>
	</div>
	<?php
}

function wpsc_product_item_row() {
}
