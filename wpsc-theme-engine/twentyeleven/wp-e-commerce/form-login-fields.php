<p>
	<label for="wpsc-login-username"><?php _e( 'Username', 'wpsc' ); ?></label><br />
	<input type="text" id="wpsc-login-username" name="username" value="<?php echo esc_attr( wpsc_submitted_value( 'username' ) ); ?>" />
</p>
<p>
	<label for="wpsc-login-password"><?php _e( 'Password', 'wpsc' ); ?></label><br />
	<input type="password" id="wpsc-login-password" name="password" /><br />
	<small><a class="wpsc-lost-password-link" href="<?php wpsc_login_url( 'lost' ); ?>"><?php esc_html_e( 'Lost your password?', 'wpsc' ); ?></a></small>
</p>
<p>
	<label for="wpsc-login-remember"><input type="checkbox" name="remember" id="wpsc-login-remember" value="1" <?php wpsc_checked( 'remember' ); ?> /><?php esc_html_e( 'Remember Me', 'wpsc' ); ?></label>
</p>
<p>
	<input type="hidden" name="action" value="login" />
	<?php wpsc_login_button(); ?>
</p>