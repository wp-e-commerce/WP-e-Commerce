<?php
/**
 * The template part for displaying the default (orders) view in the customer account.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/customer-account-index.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates
 * @version  4.0
 */
?>

<div class="wpsc-customer-account-index">
	<?php wpsc_customer_account_tabs(); ?>
	<?php wpsc_customer_orders_statuses(); ?>
	<?php wpsc_customer_orders_pagination(); ?>
	<?php wpsc_customer_orders_list(); ?>
	<?php wpsc_customer_orders_pagination(); ?>
</div>