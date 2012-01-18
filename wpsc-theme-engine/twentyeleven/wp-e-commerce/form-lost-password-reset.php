<div id="wpsc-lost-password-reset-form">
	<?php wpsc_lost_password_reset_form_open(); ?>
	<div class="wpsc-lost-password-reset-form-fields">
		<?php wpsc_lost_password_reset_form_fields(); ?>
	</div>
	<p>
		<?php wpsc_reset_password_button(); ?>
	</p>
	<?php wpsc_lost_password_reset_form_close(); ?>

	<p>
		<a href="<?php wpsc_login_url(); ?>"><?php esc_html_e( 'Log in', 'wpsc' ); ?></a><br />
		<a href="<?php wpsc_register_url(); ?>"><?php esc_html_e( 'Create a new account', 'wpsc' ); ?></a>
	</p>
</div>