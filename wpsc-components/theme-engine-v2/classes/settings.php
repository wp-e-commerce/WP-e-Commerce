<?php
define( 'WPSC_PAGE_NUMBER_POSITION_TOP'   , 1 );
define( 'WPSC_PAGE_NUMBER_POSITION_BOTTOM', 2 );
define( 'WPSC_PAGE_NUMBER_POSITION_BOTH'  , 3 );

class WPSC_Settings {
	private static $instance;
	private $default_settings = array();


	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new WPSC_Settings();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->default_settings = apply_filters(
			'wpsc_default_settings',
			array(
				'store_slug'                        => 'store',
				'store_as_front_page'               => 0,
				'crop_thumbnails'                   => 0,
				'display_category_filter'           => 0,
				'categories_to_filter'              => 'all',
				'categories_to_filter_custom'       => array(),
				'category_filter_drilldown'         => 0,
				'default_category'                  => 'all',
				'product_base_slug'                 => 'product',
				'category_base_slug'                => 'category',
				'hierarchical_product_category_url' => 0,
				'page_number_position'              => WPSC_PAGE_NUMBER_POSITION_BOTTOM,
				'products_per_page'                 => 0,
				'cart_page_slug'                    => 'cart',
				'checkout_page_slug'                => 'checkout',
				'login_page_slug'                   => 'login',
				'register_page_slug'                => 'register',
				'password_reminder_page_slug'       => 'password-reminder',
				'customer_account_page_slug'        => 'account',
				'decimal_separator'                 => '.',
				'thousands_separator'               => ',',
				'display_pagination'                => 1,
				'default_style'                     => 1,
				'store_title'                       => _x( 'Store', 'main store page title', 'wp-e-commerce' ),
				'cart_page_title'                   => _x( 'Shopping Cart', 'shopping cart page title', 'wp-e-commerce' ),
				'checkout_page_title'               => _x( 'Checkout', 'checkout page title', 'wp-e-commerce' ),
				'transaction_page_title'            => _x( 'Transaction Results', 'transaction results page title', 'wp-e-commerce' ),
				'customer_account_page_title'       => _x( 'Your Account', 'customer account page title', 'wp-e-commerce' ),
				'login_page_title'                  => _x( 'Login', 'login page title', 'wp-e-commerce' ),
				'password_reminder_page_title'      => _x( 'Reset Password', 'password reminder page title', 'wp-e-commerce' ),
				'register_page_title'               => _x( 'Register', 'register page title', 'wp-e-commerce' ),
				'default_styles'                    => array( 'wpsc-common', 'wpsc-common-inline' ),
			)
		);
	}

	public function _action_setup() {
		foreach ( $this->default_settings as $name => $value ) {
			add_option( 'wpsc_' . $name, $value );
		}
	}

	public function get( $setting ) {
		$default = array_key_exists( $setting, $this->default_settings ) ? $this->default_settings[ $setting ] : null;
		return get_option( 'wpsc_' . $setting, $default );
	}

	public function set( $setting, $value ) {
		return update_option( 'wpsc_' . $setting, $value );
	}
}

// patch for beta sites which uses 'default_style' option
add_filter( 'wpsc_default_settings', '_wpsc_filter_default_styles_setting' );

function _wpsc_filter_default_styles_setting( $settings ) {
	// prevent infinite loop
	remove_filter( 'wpsc_default_settings', '_wpsc_filter_default_styles_setting' );

	if ( ! wpsc_get_option( 'default_style' ) ) {
		$settings['default_styles'] = array();
	}

	return $settings;
}