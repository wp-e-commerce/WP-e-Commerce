<?php
/**
 * WP eCommerce shortcode definitions
 *
 * These are the shortcode definitions for the wp-eCommerce plugin
 *
 * @package wp-e-commerce
 * @since 3.7
*/
/**
 * The WPSC shortcodes
 */

function wpsc_buy_now_shortcode($atts){
	$output = wpsc_buy_now_button( $atts['product_id'], true );
	return $output;
}

add_shortcode('buy_now_button', 'wpsc_buy_now_shortcode');
?>