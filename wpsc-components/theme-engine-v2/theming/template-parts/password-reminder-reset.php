<?php
/**
 * The template part for displaying the password reminder reset view.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/password-reminder-reset.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates
 * @version  4.0
 */

if ( wpsc_has_user_messages( 'validation', 'check password reset key' ) ):
		wpsc_user_messages( array(
				'types'   => 'validation',
				'context' => 'check password reset key',
		) );
	?>
	<p><a href="<?php wpsc_password_reminder_url(); ?>" class="wpsc-button"><?php esc_html_e( 'Resend password reset email', 'wp-e-commerce' ); ?></a></p>
<?php else: ?>
	<?php wpsc_user_messages(); ?>
	<?php wpsc_password_reset_form(); ?>
<?php endif; ?>
