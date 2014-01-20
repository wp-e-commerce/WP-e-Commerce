<?php if ( wpsc_has_user_messages( 'validation', 'check password reset key' ) ): ?>
	<?php
		wpsc_user_messages( array(
				'types'   => 'validation',
				'context' => 'check password reset key',
		) );
	?>
	<p><a href="<?php wpsc_password_reminder_url(); ?>" class="wpsc-button"><?php esc_html_e( 'Resend password reset email', 'wpsc' ); ?></a></p>
<?php else: ?>
	<?php wpsc_user_messages(); ?>
	<?php wpsc_password_reset_form(); ?>
<?php endif; ?>
