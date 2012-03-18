<article>
	<header class="page-header">
		<h1 class="page-title">
			<?php esc_html_e( 'Checkout', 'wpsc' ); ?>
		</h1>
	</header>

	<div class="wpsc-checkout-steps">
		<section class="wpsc-checkout-step wpsc-checkout-details active">
			<h1>1. <?php _e( 'Customer Details', 'wpsc' ); ?></h1>
			<?php wpsc_user_messages(); ?>
			<?php wpsc_checkout_details_form_open(); ?>
				<div class="form">
					<?php wpsc_checkout_details_form_fields(); ?>
				</div>
				<div class="actions">
					<?php wpsc_checkout_submit_button(); ?>
				</div>
			<?php wpsc_checkout_details_form_close(); ?>
		</section>

		<section class="wpsc-checkout-step wpsc-checkout-details disabled">
			<h1>2. <?php _e( 'Payment and Delivery', 'wpsc' ); ?></h1>
		</section>

		<section class="wpsc-checkout-step wpsc-checkout-details disabled">
			<h1>3. <?php _e( 'Order Review', 'wpsc' ); ?></h1>
		</section>
	</div>
</article>