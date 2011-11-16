<?php

class WPSC_Settings_Tab_Admin extends WPSC_Settings_Tab
{
	public function display() {
		?>
			<h3><?php _e('Admin Settings', 'wpsc'); ?></h3>
			<table class='wpsc_options form-table'>
				<tr>
					<th scope="row"><?php _e('Max downloads per file', 'wpsc');?>:	</th>
					<td>
						<input type='text' size='10' value='<?php esc_attr_e( get_option('max_downloads') ); ?>' name='wpsc_options[max_downloads]' />
					</td>
				</tr>
				<?php
				$wpsc_ip_lock_downloads1 = "";
				$wpsc_ip_lock_downloads2 = "";
				switch( esc_attr( get_option('wpsc_ip_lock_downloads') ) ) {
					case 1:
					$wpsc_ip_lock_downloads1 = "checked ='checked'";
					break;

					case 0:
					default:
					$wpsc_ip_lock_downloads2 = "checked ='checked'";
					break;
				}

				?>
				<tr>
					<th scope="row">
					<?php _e('Lock downloads to IP address', 'wpsc');?>:
					</th>
					<td>
						<input type='radio' value='1' name='wpsc_options[wpsc_ip_lock_downloads]' id='wpsc_ip_lock_downloads2' <?php echo $wpsc_ip_lock_downloads1; ?> /> <label for='wpsc_ip_lock_downloads2'><?php _e('Yes', 'wpsc');?></label>&nbsp;
						<input type='radio' value='0' name='wpsc_options[wpsc_ip_lock_downloads]' id='wpsc_ip_lock_downloads1' <?php echo $wpsc_ip_lock_downloads2; ?> /> <label for='wpsc_ip_lock_downloads1'><?php _e('No', 'wpsc');?></label><br />
					</td>
				</tr>


				<?php
				$wpsc_check_mime_types1 = "";
				$wpsc_check_mime_types2 = "";
				switch( esc_attr( get_option('wpsc_check_mime_types') ) ) {
					case 1:
					$wpsc_check_mime_types2 = "checked ='checked'";
					break;

					case 0:
					default:
					$wpsc_check_mime_types1 = "checked ='checked'";
					break;
				}

				?>
				<tr>
					<th scope="row">
					<?php _e('Check MIME types on file uploads', 'wpsc');?>:
					</th>
					<td>
						<input type='radio' value='0' name='wpsc_options[wpsc_check_mime_types]' id='wpsc_check_mime_types2' <?php echo $wpsc_check_mime_types1; ?> /> <label for='wpsc_check_mime_types2'><?php _e('Yes', 'wpsc');?></label>&nbsp;
						<input type='radio' value='1' name='wpsc_options[wpsc_check_mime_types]' id='wpsc_check_mime_types1' <?php echo $wpsc_check_mime_types2; ?> /> <label for='wpsc_check_mime_types1'><?php _e('No', 'wpsc');?></label><br />

						<span class="wpscsmall description">
							<?php _e('Warning: Disabling this exposes your site to greater possibility of malicious files being uploaded, we recommend installing the Fileinfo extention for PHP rather than disabling this.', 'wpsc'); ?>
						</span>
					</td>
				</tr>


				<tr>
					<th scope="row">
					<?php _e('Purchase Log Email', 'wpsc');?>:
					</th>
					<td>
					<input class='text' name='wpsc_options[purch_log_email]' type='text' size='40' value='<?php esc_attr_e( get_option('purch_log_email') ); ?>' />
					</td>
				</tr>
				<tr>
					<th scope="row">
					<?php _e('Purchase Receipt - Reply Address', 'wpsc');?>:
					</th>
					<td>
					<input class='text' name='wpsc_options[return_email]' type='text' size='40' value='<?php esc_attr_e( get_option('return_email') ); ?>'  />
					</td>
				</tr>

				<tr>
					<th scope="row">
					<?php  _e('Purchase Receipt - Reply Name', 'wpsc');?>:
					</th>
					<td>
					<input class='text' name='wpsc_options[return_name]' type='text' size='40' value='<?php esc_attr_e( get_option('return_name') ); ?>'  />
					</td>
				</tr>

				<tr>
					<th scope="row">
					<?php _e('Terms and Conditions', 'wpsc');?>:
					</th>
					<td>
					<textarea name='wpsc_options[terms_and_conditions]' cols='' rows='' style='width: 300px; height: 200px;'><?php esc_attr_e(stripslashes(get_option('terms_and_conditions') ) ); ?></textarea>
					</td>
				</tr>

			</table>
			<h3 class="form_group"><?php _e('Custom Messages', 'wpsc');?>:</h3>
			<table class='wpsc_options form-table'>
				<tr>
					<th colspan="2"><?php _e('Tags can be used', 'wpsc');?>: %purchase_id%, %shop_name%,<!-- %order_status%,--> %product_list%, %total_price%, %total_shipping%, %find_us%, %total_tax%</th>
				</tr>
				<tr>
					<td class='wpsc_td_note' colspan='2'>
						<span class="wpscsmall description">
						<?php _e('Note: The purchase receipt is the message e-mailed to users after purchasing products from your shop.' , 'wpsc'); ?>
						<br />
						<?php _e('Note: You need to have the %product_list% in your purchase receipt in order for digital download links to be emailed to your buyers.' , 'wpsc'); ?>
						</span>
					</td>
				</tr>
				<tr>
					<th><strong><?php _e('Purchase Receipt', 'wpsc');?></strong></th>
					<td><textarea name="wpsc_options[wpsc_email_receipt]" cols='' rows=''   style='width: 300px; height: 200px;'><?php esc_attr_e( stripslashes(get_option('wpsc_email_receipt') ) );?></textarea></td>
				</tr>
				<tr>
					<td class='wpsc_td_note' colspan='2'>
						<span class="wpscsmall description">
						<?php _e('Note: The Admin Report is the email sent to the e-mail address set above as soon as someone successfully buys a product.' , 'wpsc'); ?>
						</span>
					</td>
				</tr>
				<tr>
					<th><strong><?php _e('Admin Report', 'wpsc');?></strong></th>
					<td><textarea name="wpsc_options[wpsc_email_admin]" cols='' rows='' style='width: 300px; height: 200px;'><?php esc_attr_e( stripslashes(get_option('wpsc_email_admin') ) );?></textarea></td>
				</tr>
			</table>

			<h3 class="form_group"><?php _e("Track and Trace settings", 'wpsc');?>:</h3>
			<table class='wpsc_options form-table'>
				<tr>
					<td class='wpsc_td_note' colspan='2'>
						<span class="wpscsmall description">
						<?php _e('Note: The Tracking Subject, is the subject for The Tracking Message email. The Tracking Message is the message e-mailed to users when you click \'Email buyer\' on the sales log. This option is only available for purchases with the status of \'Job Dispatched\'. Tags you can use in the email message section are %trackid% and %shop_name%' , 'wpsc'); ?>
						</span>
					</td>
				</tr>
				<tr>
					<th><strong><?php _e('Tracking Email Subject', 'wpsc');?></strong></th>
					<td><input name="wpsc_options[wpsc_trackingid_subject]" type='text' value='<?php esc_attr_e( stripslashes(get_option('wpsc_trackingid_subject') ) );?>' /></td>
				</tr>
				<tr>
					<th><strong><?php _e('Tracking Email Message', 'wpsc');?></strong></th>
					<td><textarea name="wpsc_options[wpsc_trackingid_message]" cols='' rows=''   style='width: 300px; height: 200px;'><?php esc_attr_e( stripslashes(get_option('wpsc_trackingid_message') ) );?></textarea></td>
				</tr>
			</table>
		<?php
	}
}