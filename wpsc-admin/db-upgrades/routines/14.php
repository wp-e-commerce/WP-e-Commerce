<?php
/**
 * Control database upgrade to version 14
*
* @access private
* @since 3.8.14.1
*
*/
function _wpsc_db_upgrade_14() {
	_wpsc_nag_user_to_verify_checkout_state_fields();
}

/**
 * add the county region label to the uk
 *
 * @access private
 * @since 3.8.14.1
 */
function _wpsc_nag_user_to_verify_checkout_state_fields() {
	wpsc_admin_nag(
		__(
			'WP-e-Commerce has been updated, please confirm the checkout field display
			settings are correct for your store.<br><br><i>The visibility of the checkout billing and shipping
			drop downs that show states and provinces is now controlled by the "billingstate" and "shippingstate"
			options set in the <b>Store Settings</b> on the  <b>Checkout</b> tab.  Prior versions used
			the "billingcountry" and "shippingcountry" settings to control the visibility of the drop downs.</i>',
			'wpsc'
		)
	);
}
