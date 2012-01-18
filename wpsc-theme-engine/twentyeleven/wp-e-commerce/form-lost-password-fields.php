<p>
	<label for="wpsc-lost-password-username"><?php _e( 'Username or E-mail', 'wpsc' ); ?></label><br />
	<input type="text" id="wpsc-lost-password-username" name="username" value="<?php echo esc_attr( wpsc_submitted_value( 'username' ) ); ?>" />
</p>
<p>
	<input type="hidden" name="action" value="new_password" />
	<?php wpsc_lost_password_button(); ?>
</p>