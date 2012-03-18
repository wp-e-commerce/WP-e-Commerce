<article>
	<header class="page-header">
		<h1 class="page-title">
			<?php esc_html_e( 'Log in', 'wpsc' ); ?>
		</h1>
	</header>
	<?php wpsc_user_messages(); ?>
	<?php wpsc_get_template_part( 'form-login' ); ?>
	<div class="wpsc-create-account-offer">
		<p><?php esc_html_e( 'Register with us to enjoy personalized services, such as:', 'wpsc' ); ?></p>
		<ul>
			<li><?php esc_html_e( 'Online order status', 'wpsc' ); ?></li>
			<li><?php esc_html_e( 'Faster checkout process', 'wpsc' ); ?></li>
			<li><?php esc_html_e( 'Order history', 'wpsc' ); ?></li>
		</ul>
		<p>
			<a href="<?php wpsc_register_url(); ?>"><?php esc_html_e( 'Create your account now', 'wpsc' ); ?></a>
		</p>
	</div>
</article>