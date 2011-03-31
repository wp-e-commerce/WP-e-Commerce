<?php 
	global $wpdb;
	$numChanged = 0;
	$numQueries = 0;
	$purchlog =  "SELECT DISTINCT id FROM `".WPSC_TABLE_PURCHASE_LOGS."` LIMIT 1";
	$id = $wpdb->get_var($purchlog);
	$usersql = "SELECT DISTINCT `".WPSC_TABLE_SUBMITED_FORM_DATA."`.value, `".WPSC_TABLE_CHECKOUT_FORMS."`.* FROM `".WPSC_TABLE_CHECKOUT_FORMS."` LEFT JOIN `".WPSC_TABLE_SUBMITED_FORM_DATA."` ON `".WPSC_TABLE_CHECKOUT_FORMS."`.id = `".WPSC_TABLE_SUBMITED_FORM_DATA."`.`form_id` WHERE `".WPSC_TABLE_SUBMITED_FORM_DATA."`.log_id=".$id." ORDER BY `".WPSC_TABLE_CHECKOUT_FORMS."`.`checkout_order`" ;
	$formfields = $wpdb->get_results($usersql);
	
	
	
	if(count($formfields) < 1){
		$usersql = "SELECT DISTINCT  `".WPSC_TABLE_CHECKOUT_FORMS."`.* FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `type` != 'heading'";
		$formfields = $wpdb->get_results($usersql);
	
	}	
if(isset($_POST)){
	foreach($_POST as $key=>$value){
		if($value != '-1'){
			$sql = "UPDATE  `".WPSC_TABLE_CHECKOUT_FORMS."` SET `unique_name`='".$value."' WHERE id=".$key;
			$complete = $wpdb->query($sql);
		}
		$numChaged++;
		$numQueries ++;
	}
	
	$sql = "UPDATE `".WPSC_TABLE_CHECKOUT_FORMS."` SET `unique_name`='delivertoafriend' WHERE `name` = '2. Shipping details'";
	$wpdb->query($sql);
	
	add_option('wpsc_purchaselogs_fixed',true);
}
function wpsc_select_options_purchlogs_fix($id){
	?>
	<select name='<?php echo $id; ?>'>
		<option value='-1'><?php _e( 'Select an Option', 'wpsc' ); ?>'</option>
		<option value='billingfirstname'><?php _e( 'Billing First Name', 'wpsc' ); ?></option>
		<option value='billinglastname'><?php _e( 'Billing Last Name', 'wpsc' ); ?></option>
		<option value='billingaddress'><?php _e( 'Billing Address', 'wpsc' ); ?></option>
		<option value='billingcity'><?php _e( 'Billing City', 'wpsc' ); ?></option>
		<option value='billingstate'><?php _e( 'Billing State', 'wpsc' ); ?></option>
		<option value='billingcountry'><?php _e( 'Billing Country', 'wpsc' ); ?></option>
		<option value='billingemail'><?php _e( 'Billing Email', 'wpsc' ); ?></option>
		<option value='billingphone'><?php _e( 'Billing Phone', 'wpsc' ); ?></option>
		<option value='billingpostcode'><?php _e( 'Billing Post Code', 'wpsc' ); ?></option>
		<option value='shippingfirstname'><?php _e( 'Shipping First Name', 'wpsc' ); ?></option>
		<option value='shippinglastname'><?php _e( 'Shipping Last Name', 'wpsc' ); ?></option>		
		<option value='shippingaddress'><?php _e( 'Shipping Address', 'wpsc' ); ?></option>
		<option value='shippingcity'><?php _e( 'Shipping City', 'wpsc' ); ?></option>
		<option value='shippingstate'><?php _e( 'Shipping State', 'wpsc' ); ?></option>
		<option value='shippingcountry'><?php _e( 'Shipping Country', 'wpsc' ); ?></option>
		<option value='shippingpostcode'><?php _e( 'Shipping Post Code', 'wpsc' ); ?></option>

	</select> 
	<?php
}
?>

<div class='wrap'>
	
			<?php if ( $numChanged != 0 && $numQueries != 0 ) {
				echo '<div id="message" class="updated fade"><p>';
				_e( 'Check Out Form Fields updated.', 'wpsc' );
				echo '</p></div>';
			}
	
			?>
			
<h2><?php echo esc_html( __('Sales Upgrade Fix', 'wpsc') ); ?> </h2>
<p><?php _e('Upgrading to WP e-Commerce 3.7 and later requires you to run this fix once.The following Boxes corresponds to the form fields in your current checkout page.  All you have to do is select from the drop-down menu box what each of the following fields represent. Sorry for any inconvenience caused, but we\'re sure you\'ll agree that the new purchase logs are worth this minor hassle.', 'wpsc'); ?> </p>

<div class="metabox-holder" style="width:700px">
<form action='' method='post'>

	<?php
	
	$duplicate = array();
	foreach($formfields as $fields){
		if(!in_array($fields->name,$duplicate) && $fields->name != 'State'){
		echo '<div class="postbox" style="width:70%">';
		echo '<h3 class="handle">Billing '.$fields->name.'</h3>';
		echo '<div class="inside" style="padding:20px;">';
		echo '<label style="width:120px;float:left;" for="'.$fields->id.'">'.$fields->value.'</label>';
		echo wpsc_select_options_purchlogs_fix($fields->id);
		echo '</div>';
		echo '</div>';
		$duplicate[] = $fields->name;
		}else{
		echo '<div class="postbox" style="width:70%">';
		echo '<h3 class="handle">Shipping '.$fields->name.'</h3>';
		echo '<div class="inside" style="padding:20px;">';
		echo '<label style="width:120px;float:left;" for="'.$fields->id.'">'.$fields->value.'</label>';
		echo wpsc_select_options_purchlogs_fix($fields->id);
		echo '</div>';
		echo '</div>';
		
		}
		
	}
	?>
	<input type='submit' value='<?php _e('Apply', 'wpsc'); ?>' class='button-secondary action' />
</form>
</div>
</div>