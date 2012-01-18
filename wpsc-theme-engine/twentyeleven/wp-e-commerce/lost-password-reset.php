<article>
	<header class="page-header">
		<h1 class="page-title">
			<?php esc_html_e( 'Password Reminder', 'wpsc' ); ?>
		</h1>
	</header>

	<?php if ( wpsc_has_user_messages( 'validation', 'check password reset key' ) ): ?>
		<?php
			wpsc_user_messages( array(
					'types'   => 'validation',
					'context' => 'check password reset key',
			) );
		?>
	<?php else: ?>
		<?php wpsc_user_messages(); ?>
		<?php wpsc_get_template_part( 'form-lost-password-reset' ); ?>
	<?php endif; ?>
</article>