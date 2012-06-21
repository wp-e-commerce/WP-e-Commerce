<?php wpsc_user_messages(); ?>
<?php if ( wpsc_has_user_messages( 'success' ) ): ?>
	<p>
		<a class="wpsc-button" href="<?php wpsc_login_url(); ?>"><?php esc_html_e( 'Log in', 'wpsc' ); ?></a><br />
		<a class="wpsc-button" href="<?php wpsc_catalog_url(); ?>"><?php esc_html_e( 'Keep shopping', 'wpsc' ); ?></a>
	</p>
<?php else: ?>
	<?php wpsc_register_form(); ?>
<?php endif; ?>