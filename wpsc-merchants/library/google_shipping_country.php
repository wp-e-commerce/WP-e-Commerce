<?php
global $wpdb;

?>

<div class="wrap">
<h2><?php _e('Google Shipping Country', 'wpsc');?></h2>
<form action='' method='post'>
<?php
	$google_shipping_country = get_option("google_shipping_country");
	$country_data = $wpdb->get_results("SELECT * FROM `".WPSC_TABLE_CURRENCY_LIST."` ORDER BY `country` ASC",ARRAY_A);
	$country_data = array_chunk($country_data, 50);
	echo "<table>\n\r";
	echo "<tr>\n\r";
	foreach($country_data as $country_col)
	{
		echo "<td style='vertical-align: top; padding-right: 3em;'>\n\r";
		echo "<table>\n\r";
		foreach($country_col as $country) {
			if (in_array($country['id'], (array)$google_shipping_country)) {
				$checked="checked='checked'";
			} else {
				$checked="";
			}
			echo "  <tr>\n\r";
			echo "    <td><input $checked type='checkbox' id='google_shipping_".$country['id']."' name='google_shipping[".$country['id']."]'/></td>\n\r";
			if (!isset($base_country)) $base_country = '';
			if($country['id'] == $base_country){
				echo "    <td><label for='google_shipping_".$country['id']."' style='text-decoration: underline;'>".$country['country'].":</label></td>\n\r";
			} else {
				echo "    <td><label for='google_shipping_".$country['id']."'>".$country['country']."</label></td>\n\r";
			}

			echo "  </tr>\n\r";
		}
		echo "</table>\n\r";
		echo "    </td>\n\r";
	}
	echo "  </tr>\n\r";
	echo "</table>\n\r";
?>
	<a style="cursor:pointer;" onclick="jQuery('input[type=\'checkbox\']').each(function() {this.checked = true; });">Select All</a>&emsp; <a style="cursor:pointer;" onclick="jQuery('input[type=\'checkbox\']').each(function() {this.checked = false; });">Unselect All</a><br /><br />
	<input type='hidden' name='wpsc_admin_action' value='google_shipping_settings' />
		<input class='button-secondary' type='submit' name='submit' value='<?php _e('Save Changes', 'wpsc');?>' /> <a href='?page=<?php echo esc_attr( $_GET['page'] )?>'>Go Back</a>
	</form>
</div>