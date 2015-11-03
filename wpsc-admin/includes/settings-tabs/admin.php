<?php

/**
 * The Admin Settings Tab class
 *
 * @package wp-e-commerce
 */

class WPSC_Settings_Tab_Admin extends WPSC_Settings_Tab {
	public function display() {
		?>
			<h3><?php esc_html_e( 'Admin Settings', 'wp-e-commerce' ); ?></h3>
			<table class='wpsc_options form-table'>
				<tr>
					<th scope="row"><?php esc_html_e( 'Max downloads per file', 'wp-e-commerce' ); ?>:	</th>
					<td>
						<input type="number" min="0" size="10" value="<?php echo esc_attr( get_option('max_downloads') ); ?>" name="wpsc_options[max_downloads]" />
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
					<?php esc_html_e( 'Lock downloads to IP address', 'wp-e-commerce' ); ?>:
					</th>
					<td>
						<input type='radio' value='1' name='wpsc_options[wpsc_ip_lock_downloads]' id='wpsc_ip_lock_downloads2' <?php echo $wpsc_ip_lock_downloads1; ?> /> <label for='wpsc_ip_lock_downloads2'><?php _e('Yes', 'wp-e-commerce');?></label>&nbsp;
						<input type='radio' value='0' name='wpsc_options[wpsc_ip_lock_downloads]' id='wpsc_ip_lock_downloads1' <?php echo $wpsc_ip_lock_downloads2; ?> /> <label for='wpsc_ip_lock_downloads1'><?php _e('No', 'wp-e-commerce');?></label><br />
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
					<?php esc_html_e( 'Check MIME types on file uploads', 'wp-e-commerce' ); ?>:
					</th>
					<td>
						<input type='radio' value='0' name='wpsc_options[wpsc_check_mime_types]' id='wpsc_check_mime_types2' <?php echo $wpsc_check_mime_types1; ?> /> <label for='wpsc_check_mime_types2'><?php _e('Yes', 'wp-e-commerce');?></label>&nbsp;
						<input type='radio' value='1' name='wpsc_options[wpsc_check_mime_types]' id='wpsc_check_mime_types1' <?php echo $wpsc_check_mime_types2; ?> /> <label for='wpsc_check_mime_types1'><?php _e('No', 'wp-e-commerce');?></label><br />

						<span class="wpscsmall description">
							<?php esc_html_e( 'Warning: Disabling this exposes your site to greater possibility of malicious files being uploaded, we recommend installing the Fileinfo extension for PHP rather than disabling this.', 'wp-e-commerce' ); ?>
						</span>
					</td>
				</tr>


				<tr>
					<th scope="row">
					<?php esc_html_e( 'Store Admin Email', 'wp-e-commerce' );?>:
					</th>
					<td>
					<input class='text' name='wpsc_options[purch_log_email]' type='text' size='40' value='<?php echo esc_attr( get_option( 'purch_log_email' ) ); ?>' />
					<p class="howto"><?php esc_html_e( 'Admin notifications will be sent here.', 'wp-e-commerce' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
					<?php esc_html_e( 'Terms and Conditions', 'wp-e-commerce' );?>:
					</th>
					<td>
					<textarea name='wpsc_options[terms_and_conditions]' cols='' rows='' style='width: 300px; height: 200px;'><?php echo esc_textarea( get_option( 'terms_and_conditions' ) ); ?></textarea>
					</td>
				</tr>

			</table>
			<h3 class="form_group"><?php esc_html_e( 'Customer Purchase Receipt', 'wp-e-commerce' );?>:</h3>
			<table class='wpsc_options form-table'>
				<tr>
					<th colspan="2"><?php esc_html_e( 'Tags can be used', 'wp-e-commerce' );?>: <?php echo esc_html( '%purchase_id%, %shop_name%, %product_list%, %total_price%, %total_shipping%, %find_us%, %total_tax%' ); ?></th>
				</tr>
				<tr>
					<td class='wpsc_td_note' colspan='2'>
						<span class="wpscsmall description">
						<?php esc_html_e( 'Note: The purchase receipt is the message e-mailed to users after purchasing products from your shop.' , 'wp-e-commerce' ); ?>
						<br />
						<?php esc_html_e( 'Note: You need to have the %product_list% in your purchase receipt in order for digital download links to be emailed to your buyers.' , 'wp-e-commerce' ); ?>
						</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
					<?php echo esc_html_x( 'From Address', 'purchase receipt', 'wp-e-commerce' );?>:
					</th>
					<td>
					<input class='text' name='wpsc_options[return_email]' type='text' size='40' value='<?php echo esc_attr( get_option( 'return_email' ) ); ?>'  />
					</td>
				</tr>

				<tr>
					<th scope="row">
					<?php esc_html_e( 'Sender Name', 'wp-e-commerce' );?>:
					</th>
					<td>
					<input class='text' name='wpsc_options[return_name]' type='text' size='40' value='<?php echo esc_attr( get_option( 'return_name' ) ); ?>'  />
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Message Body', 'wp-e-commerce' ); ?></th>
					<td><textarea name="wpsc_options[wpsc_email_receipt]" cols='' rows=''   style='width: 300px; height: 200px;'><?php echo esc_textarea( get_option( 'wpsc_email_receipt' ) );?></textarea></td>
				</tr>
			</table>

			<h3 class="form_group"><?php esc_html_e( 'Track and Trace settings', 'wp-e-commerce' ); ?>:</h3>
			<table class='wpsc_options form-table'>
				<tr>
					<td class='wpsc_td_note' colspan='2'>
						<span class="wpscsmall description">
						<?php esc_html_e( 'Note: The Tracking Subject, is the subject for The Tracking Message email. The Tracking Message is the message e-mailed to users when you click \'Email buyer\' on the sales log. This option is only available for purchases with the status of \'Job Dispatched\'. Tags you can use in the email message section are %trackid% and %shop_name%' , 'wp-e-commerce' ); ?>
						</span>
					</td>
				</tr>
				<tr>
					<th><strong><?php esc_html_e( 'Tracking Email Subject', 'wp-e-commerce' );?></strong></th>
					<td><input name="wpsc_options[wpsc_trackingid_subject]" type='text' value='<?php echo esc_attr( get_option( 'wpsc_trackingid_subject' ) );?>' /></td>
				</tr>
				<tr>
					<th><strong><?php esc_html_e( 'Tracking Email Message', 'wp-e-commerce' );?></strong></th>
					<td><textarea name="wpsc_options[wpsc_trackingid_message]" cols='' rows=''   style='width: 300px; height: 200px;'><?php echo esc_textarea( get_option( 'wpsc_trackingid_message' ) );?></textarea></td>
				</tr>
			</table>
		<?php
	}
}
