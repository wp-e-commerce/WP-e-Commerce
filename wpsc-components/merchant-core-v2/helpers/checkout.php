<?php

add_filter( 'wpsc_get_gateway_list', '_wpsc_filter_merchant_v2_get_gateway_list' );

function _wpsc_filter_merchant_v2_get_gateway_list() {
	ob_start();
	while (wpsc_have_gateways()) : wpsc_the_gateway(); ?>
		<div class="custom_gateway">
			<label><input type="radio" value="<?php echo wpsc_gateway_internal_name();?>" <?php echo wpsc_gateway_is_checked(); ?> name="custom_gateway" class="custom_gateway"/><?php echo wpsc_gateway_name(); ?>
				<?php if( wpsc_show_gateway_image() ): ?>
				<img src="<?php echo wpsc_gateway_image_url(); ?>" alt="<?php echo wpsc_gateway_name(); ?>" style="position:relative; top:5px;" />
				<?php endif; ?>
			</label>

			<?php if(wpsc_gateway_form_fields()): ?>
				<table class='wpsc_checkout_table <?php echo wpsc_gateway_form_field_style();?>'>
					<?php echo wpsc_gateway_form_fields();?>
				</table>
			<?php endif; ?>
		</div>
	<?php endwhile;
	return ob_get_clean();
}

add_filter( 'wpsc_gateway_count', '_wpsc_filter_merchant_v2_gateway_count' );

function _wpsc_filter_merchant_v2_gateway_count( $count ) {
	global $wpsc_gateway;
	$count += $wpsc_gateway->gateway_count;
	return $count;
}

add_filter(
	'wpsc_gateway_hidden_field_value',
	'_wpsc_filter_merchant_v2_gateway_hidden_field_value'
);

function _wpsc_filter_merchant_v2_gateway_hidden_field_value( $value ) {
	global $wpsc_gateway;
	if ( wpsc_have_gateways() ) {
		wpsc_the_gateway();
		$value = $wpsc_gateway->gateway['internalname'];
	}
	$wpsc_gateway->rewind_gateways();

	return $value;
}

add_action(
	'wpsc_after_gateway_hidden_field',
	'_wpsc_filter_merchant_v2_after_gateway_hidden_field'
);

function _wpsc_filter_merchant_v2_after_gateway_hidden_field() {
	if ( wpsc_have_gateways() ) {
		wpsc_the_gateway();
		if ( wpsc_gateway_form_fields() ) : ?>
			<table class='wpsc_checkout_table <?php echo wpsc_gateway_form_field_style();?>'>
				<?php echo wpsc_gateway_form_fields(); ?>
			</table>
		<?php endif;
	}
}

add_action(
	'wpsc_submit_checkout_gateway',
	'_wpsc_action_merchant_v2_submit_checkout',
	10,
	2
);

function _wpsc_action_merchant_v2_submit_checkout( $gateway, $purchase_log ) {
	global $wpsc_gateways;

	if ( empty( $wpsc_gateways[$gateway] ) )
		return;

	// submit to gateway
	$current_gateway_data = &$wpsc_gateways[$gateway];
	if ( isset( $current_gateway_data['api_version'] ) && $current_gateway_data['api_version'] >= 2.0 ) {
		$merchant_instance = new $current_gateway_data['class_name']( $purchase_log->get( 'id' ) );
		$merchant_instance->construct_value_array();
		do_action_ref_array( 'wpsc_pre_submit_gateway', array( &$merchant_instance ) );
		$merchant_instance->submit();
	} elseif ( ($current_gateway_data['internalname'] == $gateway) && ($current_gateway_data['internalname'] != 'google') ) {
		if ( get_option( 'permalink_structure' ) != '' )
			$separator = "?";
		else
			$separator = "&";
		$gateway_used = $current_gateway_data['internalname'];
		$purchase_log->set( 'gateway', $gateway_used );
		$purchase_log->save();
		$current_gateway_data['function']( $separator, $purchase_log->get( 'sessionid' ) );
	} elseif ( $current_gateway_data['internalname'] == 'google' ) {
		$gateway_used = $current_gateway_data['internalname'];
		$purchase_log->set( 'gateway', $gateway_used );
		wpsc_update_customer_meta( 'google_checkout', 'google' );
		wp_redirect(get_option( 'shopping_cart_url' ));
		exit;
	}
}
