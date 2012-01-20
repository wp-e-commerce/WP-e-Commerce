<div id="wpsc-password-reminder-form">
	<p>
		<?php esc_html_e( 'Please enter the username or email address you used to create your account and you will receive a link to create a new password via email shortly.', 'wpsc' ); ?>
	</p>
	<?php wpsc_password_reminder_form_open(); ?>
	<?php wpsc_password_reminder_form_fields(); ?>
	<?php wpsc_password_reminder_form_close(); ?>

	<p>
		<a href="<?php wpsc_login_url(); ?>"><?php esc_html_e( 'Return to login', 'wpsc' ); ?></a><br />
		<a href="<?php wpsc_register_url(); ?>"><?php esc_html_e( 'Create a new account', 'wpsc' ); ?></a>
	</p>
</div>