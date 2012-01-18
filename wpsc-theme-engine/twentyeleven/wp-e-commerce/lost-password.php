<article>
	<header class="page-header">
		<h1 class="page-title">
			<?php esc_html_e( 'Password Reminder', 'wpsc' ); ?>
		</h1>
	</header>
	<?php wpsc_user_messages(); ?>
	<?php wpsc_get_template_part( 'form-lost-password' ); ?>
</article>