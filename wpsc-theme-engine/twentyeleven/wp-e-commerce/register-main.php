<article>
	<header class="page-header">
		<h1 class="page-title">
			<?php esc_html_e( 'Create an account', 'wpsc' ); ?>
		</h1>
	</header>
	<?php wpsc_user_messages(); ?>
	<?php if ( wpsc_has_user_messages( 'success' ) ): ?>
		<p>
			<a href="<?php wpsc_login_url(); ?>"><?php esc_html_e( 'Log in', 'wpsc' ); ?></a><br />
			<a href="<?php wpsc_catalog_url(); ?>"><?php esc_html_e( 'Keep shopping', 'wpsc' ); ?></a>
		</p>
	<?php else: ?>
		<?php wpsc_get_template_part( 'form-register' ); ?>
	<?php endif; ?>
</article>