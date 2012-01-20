<p>
	<label for="wpsc-password-reminder-reset-pass1"><?php _e( 'New password', 'wpsc' ); ?></label><br />
	<input type="password" id="wpsc-password-reminder-reset-pass1" name="pass1" value="<?php echo esc_attr( wpsc_submitted_value( 'pass1' ) ); ?>" />
</p>
<p>
	<label for="wpsc-password-reminder-reset-pass2"><?php _e( 'Confirm new password', 'wpsc' ); ?></label><br />
	<input type="password" id="wpsc-password-reminder-reset-pass2" name="pass2" value="<?php echo esc_attr( wpsc_submitted_value( 'pass2' ) ); ?>" />
</p>