<?php
/**
 * The template part for displaying the customer settings view in the customer account.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/customer-account-account.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates
 * @version  4.0
 */
?>

<div class="wpsc-customer-account-settings">
	<?php wpsc_customer_account_tabs(); ?>
	<?php wpsc_user_messages(); ?>
	<div class="wpsc-customer-account-settings-form">
		<?php wpsc_customer_settings_form(); ?>
	</div>
</div>