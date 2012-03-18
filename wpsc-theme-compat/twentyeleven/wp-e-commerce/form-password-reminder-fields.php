<p>
	<label for="wpsc-password-reminder-username"><?php _e( 'Username or E-mail', 'wpsc' ); ?></label><br />
	<input type="text" id="wpsc-password-reminder-username" name="username" value="<?php echo esc_attr( wpsc_submitted_value( 'username' ) ); ?>" />
</p>
<p>
	<input type="hidden" name="action" value="new_password" />
	<?php wpsc_password_reminder_button(); ?>
</p>