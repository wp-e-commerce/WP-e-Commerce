<?php

/**
 * WP eCommerce display functions
 *
 * These are functions for the wp-eCommerce themngine, template tags and shortcodes
 *
 * @package wp-e-commerce
 * @since 3.7
 */

/**
 * wpsc buy now button code products function
 * Sorry about the ugly code, this is just to get the functionality back, buy now will soon be overhauled, and this function will then be completely different
 * @return string - html displaying one or more products
 */
function wpsc_buy_now_button( $product_id, $replaced_shortcode = false ) {
	$product = get_post( $product_id );
	$supported_gateways = array('wpsc_merchant_paypal_standard','paypal_multiple');
	$selected_gateways = get_option( 'custom_gateway_options' );
	if ( in_array( 'wpsc_merchant_paypal_standard', (array)$selected_gateways ) ) {
		if ( $product_id > 0 ) {
			$post_meta = get_post_meta( $product_id, '_wpsc_product_metadata', true );
			$shipping = $post_meta['shipping']['local'];
			$price = get_post_meta( $product_id, '_wpsc_price', true );
			$special_price = get_post_meta( $product_id, '_wpsc_special_price', true );
			if ( $special_price )
				$price = $special_price;
			if ( wpsc_uses_shipping ( ) ) {
				$handling = get_option( 'base_local_shipping' );
			} else {
				$handling = $shipping;
			}
			$output .= "<form onsubmit='log_paypal_buynow(this)' target='paypal' action='" . get_option( 'paypal_multiple_url' ) . "' method='post' />
				<input type='hidden' name='business' value='" . get_option( 'paypal_multiple_business' ) . "' />
				<input type='hidden' name='cmd' value='_xclick' />
				<input type='hidden' name='item_name' value='" . $product->post_title . "' />
				<input type='hidden' id='item_number' name='item_number' value='" . $product_id . "' />
				<input type='hidden' id='amount' name='amount' value='" . ($price) . "' />
				<input type='hidden' id='unit' name='unit' value='" . $price . "' />
				<input type='hidden' id='shipping' name='ship11' value='" . $shipping . "' />
				<input type='hidden' name='handling' value='" . $handling . "' />
				<input type='hidden' name='currency_code' value='" . get_option( 'paypal_curcode' ) . "' />";
			if ( get_option( 'multi_add' ) == 1 ) {
				$output .="<label for='quantity'>" . __( 'Quantity', 'wpsc' ) . "</label>";
				$output .="<input type='text' size='4' id='quantity' name='quantity' value='' /><br />";
			} else {
				$output .="<input type='hidden' name='undefined_quantity' value='0' />";
			}
			$output .="<input type='image' name='submit' border='0' src='https://www.paypal.com/en_US/i/btn/btn_buynow_LG.gif' alt='PayPal - The safer, easier way to pay online' />
				<img alt='' border='0' width='1' height='1' src='https://www.paypal.com/en_US/i/scr/pixel.gif' />
			</form>\n\r";
		}
	}
	if ( $replaced_shortcode == true ) {
		return $output;
	} else {
		echo $output;
	}
}

/**
 * Get the URL of the loading animation image.
 * Can be filtered using the wpsc_loading_animation_url filter.
 */
function wpsc_loading_animation_url() {
	return apply_filters( 'wpsc_loading_animation_url', WPSC_CORE_THEME_URL . 'wpsc-images/indicator.gif' );
}

function fancy_notifications() {
	return wpsc_fancy_notifications( true );
}
function wpsc_fancy_notifications( $return = false ) {
	static $already_output = false;

	if ( $already_output )
		return '';

	$output = "";
	if ( get_option( 'fancy_notifications' ) == 1 ) {
		$output = "";
		$output .= "<div id='fancy_notification'>\n\r";
		$output .= "  <div id='loading_animation'>\n\r";
		$output .= '<img id="fancy_notificationimage" title="Loading" alt="Loading" src="' . wpsc_loading_animation_url() . '" />' . __( 'Updating', 'wpsc' ) . "...\n\r";
		$output .= "  </div>\n\r";
		$output .= "  <div id='fancy_notification_content'>\n\r";
		$output .= "  </div>\n\r";
		$output .= "</div>\n\r";
	}

	$already_output = true;

	if ( $return )
		return $output;
	else
		echo $output;
}
add_action( 'wpsc_theme_footer', 'wpsc_fancy_notifications' );

function fancy_notification_content( $cart_messages ) {
	$siteurl = get_option( 'siteurl' );
	$output = '';
	foreach ( (array)$cart_messages as $cart_message ) {
		$output .= "<span>" . $cart_message . "</span><br />";
	}
	$output .= "<a href='" . get_option( 'shopping_cart_url' ) . "' class='go_to_checkout'>" . __( 'Go to Checkout', 'wpsc' ) . "</a>";
	$output .= "<a href='#' onclick='jQuery(\"#fancy_notification\").css(\"display\", \"none\"); return false;' class='continue_shopping'>" . __( 'Continue Shopping', 'wpsc' ) . "</a>";
	return $output;
}

/*
 * wpsc product url function, gets the URL of a product,
 * Deprecated, all parameters past the first unused. use get_permalink
 */

function wpsc_product_url( $product_id, $category_id = null, $escape = true ) {
	$post = get_post($product_id);
	if ( isset($post->post_parent) && $post->post_parent > 0) {
		return get_permalink($post->post_parent);
	} else {
		return get_permalink($product_id);
	}
}

function external_link( $product_id ) {
	$link = get_product_meta( $product_id, 'external_link', true );
	if ( !stristr( $link, 'http://' ) ) {
		$link = 'http://' . $link;
	}
	$target = wpsc_product_external_link_target( $product_id );
	$output .= "<input class='wpsc_buy_button' type='button' value='" . wpsc_product_external_link_text( $product_id, __( 'Buy Now', 'wpsc' ) ) . "' onclick='return gotoexternallink(\"$link\", \"$target\")'>";
	return $output;
}

/* 19-02-09
 * add cart button function used for php template tags and shortcodes
 */

function wpsc_add_to_cart_button( $product_id, $return = false ) {
	global $wpdb, $wpsc_variations;
	$output = '';
	if ( $product_id > 0 ) {
		// grab the variation form fields here
		$wpsc_variations = new wpsc_variations( $product_id );
		if ( $return )
			ob_start();
		?>
			<div class='wpsc-add-to-cart-button'>
				<form class='wpsc-add-to-cart-button-form' id='product_<?php echo esc_attr( $product_id ) ?>' action='' method='post'>
					<?php do_action( 'wpsc_add_to_cart_button_form_begin' ); ?>
					<div class='wpsc_variation_forms'>
						<?php while ( wpsc_have_variation_groups() ) : wpsc_the_variation_group(); ?>
							<p>
								<label for='<?php echo wpsc_vargrp_form_id(); ?>'><?php echo esc_html( wpsc_the_vargrp_name() ) ?>:</label>
								<select class='wpsc_select_variation' name='variation[<?php echo wpsc_vargrp_id(); ?>]' id='<?php echo wpsc_vargrp_form_id(); ?>'>
									<?php while ( wpsc_have_variations() ): wpsc_the_variation(); ?>
										<option value='<?php echo wpsc_the_variation_id(); ?>' <?php echo wpsc_the_variation_out_of_stock(); ?>><?php echo esc_html( wpsc_the_variation_name() ); ?></option>
									<?php endwhile; ?>
								</select>
							</p>
						<?php endwhile; ?>
					</div>
					<input type='hidden' name='wpsc_ajax_action' value='add_to_cart' />
					<input type='hidden' name='product_id' value='<?php echo $product_id; ?>' />
					<input type='submit' id='product_<?php echo $product_id; ?>_submit_button' class='wpsc_buy_button' name='Buy' value='<?php echo __( 'Add To Cart', 'wpsc' ); ?>'  />
					<?php do_action( 'wpsc_add_to_cart_button_form_end' ); ?>
				</form>
			</div>
		<?php

		if ( $return )
			return ob_get_clean();
	}
}

/**
 * wpsc_refresh_page_urls( $content )
 *
 * Refresh page urls when permalinks are turned on or altered
 *
 * @global object $wpdb
 * @param string $content
 * @return string
 */
function wpsc_refresh_page_urls( $content ) {
	global $wpdb;

	$wpsc_pageurl_option['product_list_url'] = '[productspage]';
	$wpsc_pageurl_option['shopping_cart_url'] = '[shoppingcart]';
	$check_chekout = $wpdb->get_var( "SELECT `guid` FROM `{$wpdb->posts}` WHERE `post_content` LIKE '%[checkout]%' AND `post_type` NOT IN('revision') LIMIT 1" );

	if ( $check_chekout != null )
		$wpsc_pageurl_option['checkout_url'] = '[checkout]';
	else
		$wpsc_pageurl_option['checkout_url'] = '[checkout]';

	$wpsc_pageurl_option['transact_url'] = '[transactionresults]';
	$wpsc_pageurl_option['user_account_url'] = '[userlog]';
	$changes_made = false;
	foreach ( $wpsc_pageurl_option as $option_key => $page_string ) {
		$post_id = $wpdb->get_var( "SELECT `ID` FROM `{$wpdb->posts}` WHERE `post_type` IN('page','post') AND `post_content` LIKE '%$page_string%' AND `post_type` NOT IN('revision') LIMIT 1" );
		$the_new_link = _get_page_link( $post_id );

		if ( stristr( get_option( $option_key ), "https://" ) )
			$the_new_link = str_replace( 'http://', "https://", $the_new_link );

		update_option( $option_key, $the_new_link );
	}
	return $content;
}

add_filter( 'mod_rewrite_rules', 'wpsc_refresh_page_urls' );

function wpsc_obtain_the_description() {
	global $wpdb, $wp_query, $wpsc_title_data;
	$output = null;

	if ( is_numeric( $wp_query->query_vars['category_id'] ) ) {
		$category_id = $wp_query->query_vars['category_id'];
	} else if ( $_GET['category'] ) {
		$category_id = absint( $_GET['category'] );
	}

	if ( is_numeric( $category_id ) ) {
		$output = wpsc_get_categorymeta( $category_id, 'description' );
	}


	if ( is_numeric( $_GET['product_id'] ) ) {
		$product_id = absint( $_GET['product_id'] );
		$output = $wpdb->get_var( $wpdb->prepare( "SELECT `post_content` FROM `" . $wpdb->posts . "` WHERE `id`= %d LIMIT 1", $product_id ) );
	}
	return $output;
}
