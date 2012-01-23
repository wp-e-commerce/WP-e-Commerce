<p>
	<label for="wpsc-register-username"><?php _e( 'Username', 'wpsc' ); ?></label><br />
	<input type="text" id="wpsc-register-username" name="username" value="<?php echo esc_attr( wpsc_submitted_value( 'username' ) ); ?>" />
</p>
<p>
	<label for="wpsc-register-email"><?php _e( 'E-mail', 'wpsc' ); ?></label><br />
	<input type="text" id="wpsc-register-email" name="email" value="<?php echo esc_attr( wpsc_submitted_value( 'email' ) ); ?>" /><br />
	<small><?php esc_html_e( 'A password will be e-mailed to you.', 'wpsc' ); ?></small>
</p>
<p>
	<input type="hidden" name="action" value="register" />
	<?php wpsc_register_button(); ?>
</p>