<?php

/**
 * WP eCommerce Fancy Notifications
 */

add_action( 'wp_enqueue_scripts', array( 'WPSC_Fancy_Notifications', 'maybe_enqueue' ) );
add_action( 'wpsc_add_to_cart_button_form_begin', array( 'WPSC_Fancy_Notifications', 'add_fancy_notifications' ) );
add_action( 'wpsc_theme_footer', array( 'WPSC_Fancy_Notifications', 'fancy_notifications' ) );
add_filter( 'wpsc_add_to_cart_json_response', array( 'WPSC_Fancy_Notifications', 'wpsc_add_to_cart_json_response' ) );

/**
 * WP eCommerce Fancy Notifications Class
 *
 * @since  4.0
 */
class WPSC_Fancy_Notifications {

	/**
	 * Enqueue Fancy Notifications Scripts/Styles if the option is enabled.
	 *
	 * @since  4.0
	 */
	public static function maybe_enqueue() {
		if ( get_option( 'fancy_notifications' ) ) {

			$url_path = self::plugin_url();

			wp_enqueue_style( 'wpsc-fancy-notifications', $url_path . '/css/fancy-notifications.css', false, '1.0' );

			wp_enqueue_script( 'wpsc-fancy-notifications', $url_path . '/js/fancy-notifications.js', array( 'jquery' ), '1.0' );

		}
	}

	/**
	 * Fancy Notifications
	 *
	 * Container HTML for fancy notifications.
	 *
	 * @since  4.0
	 *
	 * @param   boolean  $return  Return output.
	 * @return  string            Output.
	 */
	public static function fancy_notifications( $return = false ) {

		static $already_output = false;

		if ( $already_output ) {
			return '';
		}

		$output = '';
		if ( get_option( 'fancy_notifications' ) ) {
			$output .= '<div id="fancy_notification">';
			$output .= '   <div id="loading_animation">';
			$output .= '      <img id="fancy_notificationimage" title="' . esc_attr__( 'Loading', 'wp-e-commerce' ) . '" alt="' . esc_attr__( 'Loading', 'wp-e-commerce' ) . '" src="' . esc_url( wpsc_loading_animation_url() ) . '" />' . esc_html__( 'Updating', 'wp-e-commerce' ) . '...';
			$output .= '   </div>';
			$output .= '   <div id="fancy_notification_content"></div>';
			$output .= '</div>';
		}

		$already_output = true;

		if ( $return ) {
			return $output;
		}
		echo $output;

	}

	/**
	 * Fancy Notification Content
	 *
	 * @since  4.0
	 *
	 * @param   array   $cart_messages  Cart message.
	 * @return  string                  Fancy notification content.
	 */
	public static function fancy_notification_content( $cart_messages ) {

		$output = '';
		foreach ( (array)$cart_messages as $cart_message ) {
			$output .= '<span>' . $cart_message . '</span><br />';
		}
		$output .= sprintf( '<a href="%s" class="go_to_checkout">%s</a>', esc_url( get_option( 'shopping_cart_url' ) ), esc_html__( 'Go to Checkout', 'wp-e-commerce' ) );
		$output .= sprintf( '<a href="#" onclick="jQuery( \'#fancy_notification\' ).css( \'display\', \'none\' ); return false;" class="continue_shopping">%s</a>', esc_html__( 'Continue Shopping', 'wp-e-commerce' ) );

		return $output;

	}

	/**
	 * Add To Cart JSON Response
	 *
	 * Adds 'fancy_notification' content to JSON response.
	 *
	 * @since  4.0
	 *
	 * @param   array  $json_response  JSON response.
	 * @return  array                  Updated JSON response.
	 */
	public static function wpsc_add_to_cart_json_response( $json_response ) {

		if ( is_numeric( $json_response['product_id'] ) && get_option( 'fancy_notifications' ) ) {
			$json_response['fancy_notification'] = str_replace( array( "\n", "\r" ), array( '\n', '\r' ), self::fancy_notification_content( $json_response['cart_messages'] ) );
		}

		return $json_response;

	}

	/**
	 * Add Fancy Notifications
	 *
	 * @since  4.0
	 */
	public static function add_fancy_notifications() {

		add_action( 'wp_footer', array( 'WPSC_Fancy_Notifications', 'fancy_notifications' ) );

	}

	/**
	 * Plugin URL
	 *
	 * @since  4.0
	 *
	 * @return  string  URL for fancy notifications directory.
	 */
	public static function plugin_url() {

		return plugins_url( '', __FILE__ );

	}

}
