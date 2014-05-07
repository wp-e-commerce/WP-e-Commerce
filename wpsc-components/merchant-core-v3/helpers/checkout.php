<?php

add_action(
	'wpsc_before_shopping_cart_page',
	'_wpsc_action_merchant_v3_before_shopping_cart',
	1
);

function _wpsc_action_merchant_v3_before_shopping_cart() {
	// see if merchant api v2 is still supported, if it is, then it's highly
	// likely that the theme depends on api v2's $wpsc_gateways loop object
	if ( _wpsc_is_merchant_v2_active() ) {
		add_filter(
			'wpsc_merchant_v2_gateway_loop_items',
			'_wpsc_filter_merchant_v3_gateway_loop_items'
		);

		return;
	}

	add_filter(
		'wpsc_get_gateway_list',
		'_wpsc_filter_merchant_v3_get_gateway_list'
	);

	add_filter(
		'wpsc_gateway_count',
		'_wpsc_filter_merchant_v3_gateway_count'
	);
}

function _wpsc_filter_merchant_v3_gateway_count( $count ) {
	$count += count( WPSC_Payment_Gateways::get_active_gateways() );
	return $count;
}

function _wpsc_filter_merchant_v3_gateway_loop_items( $gateways ) {
	foreach ( WPSC_Payment_Gateways::get_active_gateways() as $gateway_name ) {
		$gateways[] = WPSC_Payment_Gateways::get_meta( $gateway_name );
	}

	return $gateways;
}

function _wpsc_filter_merchant_v3_get_gateway_list( $list ) {
	// if merchant api v2 is not being active, proceed to output the gateway list
	if ( _wpsc_is_merchant_v2_active( $list ) )
		return $list;

	$active_gateways = WPSC_Payment_Gateways::get_active_gateways();
	$selected_gateway = wpsc_get_customer_meta( 'selected_gateway' );
	if ( ! $selected_gateway )
		$selected_gateway = $active_gateways[0];

	ob_start();
	foreach ( $active_gateways as $gateway_name ) {
		$meta = WPSC_Payment_Gateways::get_meta( $gateway_name );
		?>
		<div class="custom_gateway">
			 <label><input type="radio" value="<?php echo esc_attr( $gateway_name ); ?>" <?php checked( $gateway_name, $selected_gateway ); ?> name="custom_gateway" class="custom_gateway"/><?php echo esc_html( $meta['name'] ); ?>
				<?php if ( ! empty( $meta['image']) ): ?>
				<img src="<?php echo esc_url( $meta['image'] ); ?>" alt="<?php echo esc_html( $meta['name'] ); ?>" style="position:relative; top:5px;" />
				<?php endif; ?>
			 </label>
	   </div>
		<?php
	}
	$list .= ob_get_clean();

	return $list;
}

add_filter(
	'wpsc_gateway_hidden_field_value',
	'_wpsc_filter_merchant_v3_gateway_hidden_field_value'
);

function _wpsc_filter_merchant_v3_gateway_hidden_field_value( $value ) {
	$active_gateways = WPSC_Payment_Gateways::get_active_gateways();
	if ( ! empty( $active_gateways ) )
		return $active_gateways[0];

	return $value;
}

add_action(
	'wpsc_submit_checkout_gateway',
	'_wpsc_action_merchant_v3_submit_checkout',
	10,
	2
);

function _wpsc_action_merchant_v3_submit_checkout( $gateway_id, $log ) {
	if ( ! wpsc_is_payment_gateway_registered( $gateway_id ) )
		return;

	$gateway = wpsc_get_payment_gateway( $gateway_id );
	$gateway->set_purchase_log( $log );
	$gateway->process();
}