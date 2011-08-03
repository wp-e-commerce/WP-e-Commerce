<?php
function wpsc_options_checkout(){
	global $wpdb;
	$form_types = get_option('wpsc_checkout_form_fields');
	$unique_names = get_option('wpsc_checkout_unique_names');

	do_action('wpsc_checkout_form_fields_page');
	$columns = array(
		'drag' => __('Drag', 'wpsc'),
		'name' => __('Name', 'wpsc'),
		'type' => __('Type', 'wpsc'),
		'unique_names' => __('Unique Names', 'wpsc'),
		'mandatory' => __('Mandatory', 'wpsc'),
		'trash' => __('Trash', 'wpsc'),
	);
	register_column_headers('display-checkout-list', $columns);	

	
	?>

<form name='cart_options' id='cart_options' method='post' action='' class='wpsc_form_track'>
	<div class="wrap">
		<?php 
		/* wpsc_setting_page_update_notification displays the wordpress styled notifications */
		wpsc_settings_page_update_notification(); ?>

		<div class='metabox-holder' style='width:95%;'>
			<div class='postbox'>
			<input type='hidden' name='checkout_submits' value='true' />
			<h3 class='hndle'><?php _e( 'Misc Checkout Options' , 'wpsc' ); ?></h3>
			<div class='inside'>
			<table>
			<tr>
				<td><?php _e('Users must register before checking out', 'wpsc'); ?>:</td>
				<td>
					<?php
						$require_register = esc_attr( get_option('require_register') );
						$require_register1 = "";
						$require_register2 = "";
						switch($require_register) {
							case 0:
							$require_register2 = "checked ='checked'";
							break;
    			
							case 1:
							$require_register1 = "checked ='checked'";
							break;
						}
		        ?>
						<input type='radio' value='1' name='wpsc_options[require_register]' id='require_register1' <?php echo $require_register1; ?> /> 					<label for='require_register1'><?php _e('Yes', 'wpsc');?></label> &nbsp;
						<input type='radio' value='0' name='wpsc_options[require_register]' id='require_register2' <?php echo $require_register2; ?> /> 					<label for='require_register2'><?php _e('No', 'wpsc');?></label>
					</td>
					<td>
						<a title='<?php _e('If yes then you must also turn on the wordpress option "Any one can register"', 'wpsc');?>' class='flag_email' href='#' ><img src='<?php echo WPSC_CORE_IMAGES_URL; ?>/help.png' alt='' /> </a>
					</td>
     		</tr>
	  
			<tr>
						<?php
					$shippingBilling = get_option('shippingsameasbilling');
					$shippingBilling1 = $shippingBilling2 = '';
					switch($shippingBilling) {
						case 1:
						$shippingBilling1 = "checked ='checked'";
						break;
						
						case 0:
						$shippingBilling2 = "checked ='checked'";
						break;
					}
				?>
				<td scope="row"><?php _e('Enable Shipping Same as Billing Option: ', 'wpsc'); ?>:</td>
				<td>
				<input type='radio' value='1' name='wpsc_options[shippingsameasbilling]' id='shippingsameasbilling1' <?php if (!empty($shippingBilling1)) echo $shippingBilling1; ?> /> 
				<label for='shippingsameasbilling1'><?php _e('Yes', 'wpsc');?></label> &nbsp;
				<input type='radio' value='0' name='wpsc_options[shippingsameasbilling]' id='shippingsameasbilling2' <?php if (!empty($shippingBilling2)) echo $shippingBilling2; ?> /> 
				<label for='shippingsameasbilling2'><?php _e('No', 'wpsc');?></label>
				</td>
				
			</tr>
			<tr>
				<td><?php _e('Force users to use SSL', 'wpsc'); ?>:</td>
				<td>
				<?php
					$wpsc_force_ssl = esc_attr( get_option('wpsc_force_ssl') );
					$wpsc_force_ssl1 = "";
					$wpsc_force_ssl2 = "";
					switch($wpsc_force_ssl) {
						case 0:
						$wpsc_force_ssl2 = "checked ='checked'";
						break;
				
						case 1:
						$wpsc_force_ssl1 = "checked ='checked'";
						break;
					}
				        ?>
					<input type='radio' value='1' name='wpsc_options[wpsc_force_ssl]' id='wpsc_force_ssl1' <?php echo $wpsc_force_ssl1; ?> /> 					<label for='wpsc_force_ssl1'><?php _e('Yes', 'wpsc');?></label> &nbsp;
					<input type='radio' value='0' name='wpsc_options[wpsc_force_ssl]' id='wpsc_force_ssl2' <?php echo $wpsc_force_ssl2; ?> /> 					<label for='wpsc_force_ssl2'><?php _e('No', 'wpsc');?></label>
				</td>
				<td>
					<a title='<?php _e('This can cause warnings for your users if you do not have a properly configured SSL certificate', 'wpsc');?>' class='flag_email' href='#' ><img src='<?php echo WPSC_CORE_IMAGES_URL; ?>/help.png' alt='' /> </a>
				</td>
			</tr>
			<?php do_action('wpsc_checkout_settings_page'); ?>
			</table>
		</div>
		</div>
		</div>
			<h3><?php _e('Form Fields', 'wpsc'); ?></h3>
  			<p><?php _e('Here you can customise the forms to be displayed in your checkout page. The checkout page is where you collect important user information that will show up in your purchase logs i.e. the buyers address, and name...', 'wpsc');?></p>
  			
				<p>
					<label for='wpsc_form_set'><?php _e('Select a Form Set' , 'wpsc'); ?>:</label>
					<select id='wpsc_form_set' name='wpsc_form_set'>
					<?php
						$checkout_sets = get_option('wpsc_checkout_form_sets');
						foreach((array)$checkout_sets as $key => $value) {
							$selected_state = "";
							if(isset($_GET['checkout-set']) && $_GET['checkout-set'] == $key) {
								$selected_state = "selected='selected'";
							}
							echo "<option {$selected_state} value='{$key}'>".esc_attr( stripslashes( $value ) )."</option>";
						}
					?>
					</select>
					<input type='submit' value='Filter' name='wpsc_checkout_set_filter' class='button-secondary' />
					<a href='#' class='add_new_form_set'><?php _e("+ Add New Form Set", 'wpsc'); ?></a>
				</p>
				
				<p class='add_new_form_set_forms'>
					<label><?php _e("Add new Form Set",'wpsc'); ?>: <input type="text" value="" name="new_form_set" /></label>
					<input type="submit" value="<?php _e('Add', 'wpsc'); ?>" class="button-secondary" id="formset-add-sumbit"/>
				</p>
				
				<?php
				if(!isset($_GET['checkout-set']) || ($_GET['checkout-set'] == 0)) {
					$form_sql = "SELECT * FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `active` = '1' AND `checkout_set` IN ('0', '') ORDER BY `checkout_order`;";
				} else {
					$filter = $wpdb->escape($_GET['checkout-set']);
					$form_sql = "SELECT * FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `active` = '1' AND `checkout_set` IN ('".$filter."') ORDER BY `checkout_order`;";
				}
				$email_form_field = $wpdb->get_row("SELECT `id` FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `type` IN ('email') AND `active` = '1' ORDER BY `checkout_order` ASC LIMIT 1",ARRAY_A);
			  
  		 		
			  $form_data = $wpdb->get_results($form_sql,ARRAY_A);
			  if (!isset($_GET['checkout-set'])) $_GET['checkout-set'] = '';
			  
			  $selected_checkout_set = esc_attr($_GET['checkout-set']);
  			echo "<input type='hidden' name='selected_form_set' value='".$selected_checkout_set."' />";
  			?>
			<table id="wpsc_checkout_list" class="widefat page fixed"  cellspacing="0">
			<thead>
				<tr>
					<?php print_column_headers('display-checkout-list'); ?>
				</tr>
			</thead>
		
			<tfoot>
				<tr>
					<?php print_column_headers('display-checkout-list', false); ?>
				</tr>
			</tfoot>
		
			<tbody id='wpsc_checkout_list_body'>
			<?php
					foreach((array)$form_data as $form_field) {
			    echo "<tr id='checkout_".$form_field['id']."' class='checkout_form_field'>\n\r";
			    echo '<td class="drag"><a href="" onclick="return false;" title="' . __('Click and Drag to Order Checkout Fields', 'wpsc') . '"><img src="' . WPSC_CORE_IMAGES_URL . '/roll-over-drag.jpg" alt="roll-over-drag" /></a></td>';
			    echo "<td class='namecol'><input type='text' name='form_name[".$form_field['id']."]' value='".esc_attr(htmlentities(stripslashes($form_field['name']), ENT_QUOTES, "UTF-8"))."' /></td>";
			    
			    echo "      <td class='typecol'>";
			    echo "<select class='wpsc_checkout_selectboxes' name='form_type[".$form_field['id']."]'>";
			    foreach($form_types as $form_type_name => $form_type) {
			      $selected = '';
			      if($form_type === $form_field['type']) {
			        $selected = "selected='selected'";
			      }
			      echo "<option value='".$form_type."' ".$selected.">" . $form_type_name . "</option>";
			    }
			 
			    echo "</select>";
			   if(in_array($form_field['type'], array('select','radio','checkbox'))){
			    	   echo "<a class='wpsc_edit_checkout_options' rel='form_options[".$form_field['id']."]' href=''>" . __('more options', 'wpsc') . "</a>";			   
			    }
			    echo "</td>";
			    $checked = "";
			    echo "<td><select name='unique_names[".$form_field['id']."]'>";
			    echo "<option value='-1'>" . __('Select a Unique Name', 'wpsc') . "</option>";
			    foreach($unique_names as $unique_name){
			       $selected = "";
			      if($unique_name == $form_field['unique_name']) {
			        $selected = "selected='selected'";
			      }
			    	echo "<option ".$selected." value='".$unique_name."'>".$unique_name."</option>";
			    }
			    echo "</select></td>";
			    if($form_field['mandatory']) {
			      $checked = "checked='checked'";
			    }
			    echo "      <td class='mandatorycol'><input $checked type='checkbox' name='form_mandatory[".$form_field['id']."]' value='1' /></td>";
			   
			    
			    echo "      <td><a class='image_link' href='#' onclick='return remove_form_field(\"checkout_".$form_field['id']."\",".$form_field['id'].");'><img src='" . WPSC_CORE_IMAGES_URL . "/trash.gif' alt='".__('Delete', 'wpsc')."' title='".__('Delete', 'wpsc')."' /></a>";
		   
			    if($email_form_field['id'] == $form_field['id']) {
			      echo "<a title='".__('This will be the Email address that the Purchase Reciept is sent to.', 'wpsc')."' class='flag_email' href='#' ><img src='" . WPSC_CORE_IMAGES_URL . "/help.png' alt='' /> </a>";
			    }
			    echo "</td>";
			    
			    echo "
			    </tr>";
			 
			    }
			    ?>

			</tbody>
			</table>
		 <?php ?>
	<p>
        <input type='hidden' name='wpsc_admin_action' value='checkout_settings' />
        
				<?php wp_nonce_field('update-options', 'wpsc-update-options'); ?>
        <input class='button-primary' type='submit' name='submit' value='<?php _e('Save Changes', 'wpsc');?>' />
        <a href='#' onclick='return add_form_field();'><?php _e('Add New Form Field', 'wpsc');?></a></p>
        <div id="checkout_message">Note: Any new form fields will appear in your sales logs and on your checkout page but this data will not be sent to the payment gateway.</div>
</div>
</form>
		   <?php
  }
  ?>