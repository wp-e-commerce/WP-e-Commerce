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
	if ( _wpsc_is_merchant_v2_active() ) {
		return $list;
	}

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

	if ( ! empty( $active_gateways ) ) {
		return $active_gateways[0];
	}

	return $value;
}

add_action(
	'wpsc_submit_checkout_gateway',
	'_wpsc_action_merchant_v3_submit_checkout',
	10,
	2
);

function _wpsc_action_merchant_v3_submit_checkout( $gateway_id, $log ) {
	if ( ! wpsc_is_payment_gateway_registered( $gateway_id ) ) {
		return;
	}

	$gateway = wpsc_get_payment_gateway( $gateway_id );
	$gateway->set_purchase_log( $log );
	$gateway->process();
}

// This is experimental.
function _wpsc_filter_merchant_v3_payment_method_form_fields( $fields ) {
	$selected_value =   isset( $_POST['wpsc_payment_method'] )
	                   ? $_POST['wpsc_payment_method']
	                   : '';

	if ( empty( $selected_value ) ) {
		$current_purchase_log_id = wpsc_get_customer_meta( 'current_purchase_log_id' );
		$purchase_log            = new WPSC_Purchase_Log( $current_purchase_log_id );
		$selected_value          = $purchase_log->get( 'gateway' );
	}

	foreach ( WPSC_Payment_Gateways::get_active_gateways() as $gateway_name ) {
		$gateway = (object) WPSC_Payment_Gateways::get_meta( $gateway_name );
		$title = $gateway->name;
		if ( ! empty( $gateway->image ) ) {
			$title .= ' <img src="' . $gateway->image . '" alt="' . $gateway->name . '" />';
		}

		if ( ! empty( $gateway->mark ) ) {
			$title = $gateway->mark;
		}

		$field = array(
			'title'   => $title,
			'type'    => 'radio',
			'value'   => $gateway->internalname,
			'name'    => 'wpsc_payment_method',
			'checked' => $selected_value == $gateway->internalname,
		);

		$fields[] = $field;
	}

	// check the first payment gateway by default
	if ( empty( $selected_value ) )
		$fields[0]['checked'] = true;

	return $fields;
}

add_filter(
	'wpsc_payment_method_form_fields',
	'_wpsc_filter_merchant_v3_payment_method_form_fields'
);
