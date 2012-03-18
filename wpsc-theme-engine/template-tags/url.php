<?php

function wpsc_catalog_url() {
	echo wpsc_get_catalog_url();
}

/**
 * Return the main catalog URL based on the settings in Settings->Store->Pemalinks
 *
 * @since 4.0
 * @uses  home_url()
 * @uses  wpsc_get_option() Gets WPEC 'catalog_slug' option.
 * @return [type]
 */
function wpsc_get_catalog_url( $slug = '' ) {
	$uri = wpsc_get_option( 'catalog_slug' );
	if ( $slug )
		$uri = trailingslashit( $uri ) . ltrim( $slug, '/' );
	return user_trailingslashit( home_url( $uri ) );
}

function wpsc_cart_url( $slug = '' ) {
	echo wpsc_get_cart_url( $slug );
}

function wpsc_get_cart_url( $slug = '' ) {
	$uri = wpsc_get_option( 'cart_page_slug' );
	if ( $slug )
		$uri = trailingslashit( $uri ) . ltrim( $slug, '/' );
	return user_trailingslashit( home_url( $uri ) );
}

function wpsc_checkout_url( $slug = '' ) {
	echo wpsc_get_checkout_url( $slug );
}

function wpsc_get_checkout_url( $slug = '' ) {
	$uri = wpsc_get_option( 'checkout_page_slug' );
	if ( $slug )
		$uri = trailingslashit( $uri ) . ltrim( $slug, '/' );
	return user_trailingslashit( home_url( $uri ) );
}

function wpsc_login_url( $slug = '' ) {
	echo wpsc_get_login_url( $slug );
}

function wpsc_get_login_url( $slug = '' ) {
	$uri = wpsc_get_option( 'login_page_slug' );
	if ( $slug )
		$uri = trailingslashit( $uri ) . ltrim( $slug, '/' );
	$scheme = force_ssl_login() ? 'https' : null;
	return user_trailingslashit( home_url( $uri, $scheme ) );
}

function wpsc_register_url( $slug = '' ) {
	echo wpsc_get_register_url( $slug );
}

function wpsc_get_register_url( $slug = '' ) {
	$uri = wpsc_get_option( 'register_page_slug' );
	if ( $slug )
		$uri = trailingslashit( $uri ) . ltrim( $slug, '/' );
	$scheme = force_ssl_login() ? 'https' : null;
	return user_trailingslashit( home_url( $uri, $scheme ) );
}

function wpsc_password_reminder_url( $slug = '' ) {
	echo wpsc_get_password_reminder_url( $slug );
}

function wpsc_get_password_reminder_url( $slug = '' ) {
	$uri = wpsc_get_option( 'password_reminder_page_slug' );
	if ( $slug )
		$uri = trailingslashit( $uri ) . ltrim( $slug, '/' );
	$scheme = force_ssl_login() ? 'https' : null;
	return user_trailingslashit( home_url( $uri, $scheme ) );
}

function wpsc_page_get_current_slug() {
	global $wpsc_page_instance;
	if ( ! isset( $wpsc_page_instance ) )
		return '';

	return $wpsc_page_instance->get_slug();
}

function wpsc_customer_account_url( $slug = '' ) {
	echo wpsc_get_customer_account_url( $slug );
}

function wpsc_get_customer_account_url( $slug = '' ) {
	$uri = wpsc_get_option( 'customer_account_page_slug' );
	if ( $slug )
		$uri = trailingslashit( $uri ) . ltrim( $slug, '/' );
	return user_trailingslashit( home_url( $uri ) );
}
