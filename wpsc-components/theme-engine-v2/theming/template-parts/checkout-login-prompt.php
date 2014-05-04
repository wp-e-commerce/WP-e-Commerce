<?php wpsc_user_messages(); ?>
<div class="wpsc-create-account-offer">
	<p><strong><?php esc_html_e( 'New Customers', 'wpsc' ); ?></strong></p>
	<p><?php esc_html_e( 'You are not required to have an account to proceed.', 'wpsc' ); ?></p>
	<p><?php esc_html_e( 'At the end of the checkout process, you will have an opportunity to create an account with us to enjoy our personalized services.', 'wpsc' ); ?></p>
	<a class="wpsc-button wpsc-button-primary" href="<?php wpsc_checkout_url( 'shipping-and-billing' ); ?>"><?php esc_html_e( 'Continue as Guest', 'wpsc' ); ?></a>
</div>
<div class="wpsc-login-form-wrapper">
	<p><strong><?php esc_html_e( 'Returning Customers', 'wpsc' ); ?></strong></p>
	<p><?php esc_html_e( 'If you already have an account, please sign in to speed up your checkout process.', 'wpsc' ) ?></p>
	<?php wpsc_login_form(); ?>
</div>
<br class="clear" />