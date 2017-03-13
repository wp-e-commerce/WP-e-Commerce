<?php

/**
 * Template engine main class.
 *
 * @since 4.0
 */
class WPSC_Template_Engine {
	/**
	 * Singleton instance
	 * @since 4.0
	 * @var WPSC_Template_Engine
	 */
	private static $instance;

	/**
	 * Return the singleton instance
	 * @since  0.1
	 * @return WPSC_Template_Engine
	 */
	public static function get_instance() {

		if ( ! self::$instance ) {
			self::$instance = new WPSC_Template_Engine();
		}

		return self::$instance;
	}

	/**
	 * Paths where asset files can be found
	 *
	 * @since 4.0
	 * @var array
	 */
	private $asset_paths = array();

	/**
	 * Paths where template parts can be found.
	 *
	 * @since 4.0
	 * @var array
	 */
	private $template_part_paths = array();

	/**
	 * Paths where view wrappers can be found.
	 *
	 * @since 4.0
	 * @var array
	 */
	private $view_wrapper_paths = array();

	/**
	 * Core scripts ready to be enqueued during the wp_enqueue_scripts hook.
	 *
	 * @since 4.0
	 * @var array
	 */
	private $queued_scripts = array();

	/**
	 * Core scripts to register during wp_enqueue_scripts hook.
	 *
	 * @since 4.0
	 * @var array
	 */
	private $core_scripts = array();

	/**
	 * Constructor
	 *
	 * @since 4.0
	 * @access private
	 */
	private function __construct() {
		$this->register_default_asset_paths();
		$this->register_default_template_part_paths();
		$this->register_default_view_wrapper_paths();
	}

	/**
	 * Register default paths to assets
	 *
	 * @since 4.0
	 */
	private function register_default_asset_paths() {
		// First, search in wp-e-commerce/assets under current theme
		$this->register_asset_path( STYLESHEETPATH . '/wp-e-commerce/assets', 10 );

		// Then, if this is a child theme, search in wp-e-commerce/assets under the parent theme
		if ( is_child_theme() ) {
			$this->register_asset_path( TEMPLATEPATH . '/wp-e-commerce/assets', 20 );
		}

		// Finally, fall back to the default asset path in theme engine's folder
		$this->register_asset_path( WPSC_TE_V2_ASSETS_PATH, 30 );
	}

	/**
	 * Register default paths to template parts
	 *
	 * @since 4.0
	 */
	private function register_default_template_part_paths() {

		// First, search in wp-e-commerce/template-parts under the current theme
		$this->register_template_part_path( STYLESHEETPATH . '/wp-e-commerce/template-parts', 10 );

		// Then, if this is a child theme, search in wp-e-commerce/template-parts under the parent theme
		if ( is_child_theme() ) {
			$this->register_template_part_path( TEMPLATEPATH . '/wp-e-commerce/template-parts', 20 );
		}

		// Finally, fall back to the default template part path in theme engine's folder
		$this->register_template_part_path( WPSC_TE_V2_TEMPLATE_PARTS_PATH, 30 );
	}

	/**
	 * Register default view wrapper paths
	 *
	 * @since 4.0
	 */
	private function register_default_view_wrapper_paths() {
		// First, search in wp-e-commerce subfolder inside the current theme
		$this->register_view_wrapper_path( STYLESHEETPATH . '/wp-e-commerce', 10 );

		// Then, if this is a child theme, search in wp-e-commerce subfolder inside the parent theme
		if ( is_child_theme() ) {
			$this->register_view_wrapper_path( TEMPLATEPATH . '/wp-e-commerce', 20 );
		}
	}

	/**
	 * Register default core scripts data.
	 *
	 * @since 4.0
	 */
	private function register_default_core_scripts() {
		$do_minified = apply_filters( 'wpsc_use_minified_scripts', ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) );
		$suffix = $do_minified ? '.min' : '';

		$this->core_scripts = array(
			'wpsc-select-autocomplete' => array(
				'path'         => "js/jquery.select-to-autocomplete{$suffix}.js",
				'dependencies' => array( 'jquery-ui-autocomplete' ),
				'version'      => '1.0.5',
			),
			'wpsc-country-region' => array(
				'path'         => "js/country-region{$suffix}.js",
				'dependencies' => array( 'wpsc-select-autocomplete', 'jquery' ),
				'version'      => WPSC_VERSION,
			),
			'wpsc-copy-billing-info' => array(
				'path'         => "js/copy-billing-info{$suffix}.js",
				'dependencies' => array( 'jquery' ),
				'version'      => WPSC_VERSION,
				'data'         => array(
					'property_name' => 'copyBilling',
					'data' => array(
						'strings' => array(
							'billing_and_shipping' => apply_filters( 'wpsc_checkout_billing_header_label' , __( '<h2>Billing &amp; Shipping Details</h2>', 'wp-e-commerce' ) ),
							'shipping'             => apply_filters( 'wpsc_checkout_shipping_header_label' , __( '<h2>Shipping Details</h2>', 'wp-e-commerce' ) ),
							'billing'              => apply_filters( 'wpsc_checkout_billing_only_header_label', __( '<h2>Billing Details</h2>', 'wp-e-commerce' ) ),
						),
					),
				),
			),
			'wpsc-shipping-price-simulator' => array(
				'path'         => "js/shipping-price-simulator{$suffix}.js",
				'dependencies' => array( 'jquery' ),
				'version'      => WPSC_VERSION,
			),
			'jquery-payment' => array(
				'path'         => 'js/jquery.payment.min.js',
				'dependencies' => array( 'jquery' ),
				'version'      => WPSC_VERSION,
			),
			'wpsc-checkout-payment' => array(
				'path'         => "js/checkout-payment{$suffix}.js",
				'dependencies' => array( 'jquery', 'jquery-payment' ),
				'version'      => WPSC_VERSION,
			),
			'wpsc-cart-notifications' => array(
				'path'         => "js/cart-notifications{$suffix}.js",
				'dependencies' => array( 'jquery', 'wp-backbone' ),
				'version'      => WPSC_VERSION,
				'data'         => array(
					'property_name' => 'cartNotifications',
					'data' => array(
						'_templates' => array(
							'wpsc-modal-inner' => '_wpsc_cart_notifications',
						),
					),
				),
			),
			'wpsc-float-labels' => array(
				'path'         => "js/jquery.floatlabel{$suffix}.js",
				'dependencies' => array( 'jquery' ),
				'version'      => WPSC_VERSION,
			),
			'wpsc-checkout' => array(
				'path'         => "js/checkout{$suffix}.js",
				'dependencies' => array( 'jquery', 'wpsc-float-labels' ),
				'version'      => WPSC_VERSION,
			),
			'wpsc-fluidbox' => array(
				'path'         => "js/fluidbox{$suffix}.js",
				'dependencies' => array( 'jquery', 'underscore' ),
				'version'      => WPSC_VERSION
			),
			'wpsc-products' => array(
				'path'         => "js/products{$suffix}.js",
				'dependencies' => array( 'jquery' ),
				'version'      => WPSC_VERSION,
			),

		);

	}

	/**
	 * Get all the registered core scripts data.
	 *
	 * @since  4.0
	 * @return array
	 */
	public function get_core_scripts_data() {
		$this->register_default_core_scripts();
		return $this->core_scripts;
	}

	/**
	 * Register a path where template engine can look for a certain asset file
	 *
	 * @since 4.0
	 * @uses  WPSC_Template_Engine::register_thing()
	 * @param  string  $path     Path to the assets
	 * @param  integer $priority Optional. Priority of this path (smaller = higher priority). Defaults to 50.
	 */
	public function register_asset_path( $path, $priority = 50 ) {
		$this->register_thing( 'asset_paths', $path, $priority );
	}

	/**
	 * Register a path where template engine can look for a certain template part
	 *
	 * @since  0.1
	 * @param  string  $path     Path to the template parts
	 * @param  integer $priority Optional. Priority of this path (smaller = higher priority). Defaults to 50.
	 */
	public function register_template_part_path( $path, $priority = 50 ) {
		$this->register_thing( 'template_part_paths', $path, $priority );
	}

	/**
	 * Register a path where template engine can look for a certain view wraper
	 *
	 * @since  0.1
	 * @param  string  $path     Path to the view wrapper
	 * @param  integer $priority Optional. Priority of this path (smaller = higher priority). Defaults to 50.
	 */
	public function register_view_wrapper_path( $path, $priority = 50 ) {
		$this->register_thing( 'view_wrapper_paths', $path, $priority );
	}

	/**
	 * Register a path where template engine can look for a certain view wraper
	 *
	 * @since 4.0
	 * @param string $handle      Name of the core script to queue.
	 * @param array  $script_data (Optional) data to send to wp_localize_script under the WPSC namespace.
	 */
	public function register_queued_script( $handle, $script_data = array() ) {
		$this->queued_scripts[ $handle ] = $script_data;
	}

	/**
	 * Deregister a path where template engine can look for a certain asset.
	 *
	 * The priority has to be the same as when this path was registered.
	 *
	 * @since  0.1
	 * @param  string  $path     Path to remove
	 * @param  integer $priority Optional. Priority of this path (smaller = higher priority). Defaults to 50.
	 */
	public function deregister_asset_path( $path, $priority = 50 ) {
		$this->deregister_thing( 'asset_paths', $path, $priority );
	}

	/**
	 * Deregister a path where template engine can look for a certain template part
	 *
	 * The priority has to be the same as when this path was registered.
	 *
	 * @since  0.1
	 * @param  string  $path     Path to remove
	 * @param  integer $priority Optional. Priority of this path (smaller = higher priority). Defaults to 50.
	 */
	public function deregister_template_path( $path, $priority = 50 ) {
		$this->deregister_thing( 'template_part_paths', $path, $priority );
	}

	/**
	 * Deregister a path where template engine can look for a certain view wrapper
	 *
	 * The priority has to be the same as when this path was registered.
	 *
	 * @since  0.1
	 * @param  string  $path     Path to remove
	 * @param  integer $priority Optional. Priority of this path (smaller = higher priority). Defaults to 50.
	 */
	public function deregister_view_wrapper_path( $path, $priority = 50 ) {
		$this->deregister_thing( 'view_wrapper_paths', $path, $priority );
	}

	/**
	 * Register a path to the $var private variable.
	 *
	 * This is a private shortcut which is meant to be used internally.
	 *
	 * @since  0.1
	 * @param  string  $var      Variable name
	 * @param  string  $path     Path
	 * @param  integer $priority Priority
	 */
	private function register_thing( $var, $path, $priority = 50 ) {
		$arr = &$this->$var;

		if ( empty( $arr[ $priority ] ) ) {
			$arr[ $priority ] = array();
		}

		$arr[ $priority ][] = $path;
	}

	/**
	 * Deregister a path from the $var private variable.
	 *
	 * This is a private shortcut which is meant to be used internally.
	 *
	 * @since  0.1
	 * @param  string  $var      Variable name
	 * @param  string  $path     Path
	 * @param  integer $priority Priority
	 */
	private function deregister_thing( $var, $path, $priority = 50 ) {
		$arr = &$this->$var;

		if ( ! isset( $arr[ $priority ] ) ) {
			return;
		}

		$key = array_search( $path, $arr[ $priority ] );

		if ( $key !== false ) {
			unset( $arr[ $priority ][ $key ] );
		}

		return;
	}

	/**
	 * Get all the registered asset paths, ordered by priority
	 *
	 * @since  0.1
	 * @uses   WPSC_Template_Engine::get_paths()
	 * @return array
	 */
	public function get_asset_paths() {
		return $this->get_paths( 'asset_paths' );
	}

	/**
	 * Get all the registered template part paths, ordered by priority
	 *
	 * @since  0.1
	 * @uses   WPSC_Template_Engine::get_paths()
	 * @return array
	 */
	public function get_template_part_paths() {
		return $this->get_paths( 'template_part_paths' );
	}

	/**
	 * Get all the registered view wrapper paths, ordered by priority
	 *
	 * @since  0.1
	 * @uses   WPSC_Template_Engine::get_paths()
	 * @return array
	 */
	public function get_view_wrapper_paths() {
		return $this->get_paths( 'view_wrapper_paths' );
	}

	/**
	 * Get all the registered queued scripts.
	 *
	 * @since  4.0
	 * @return array
	 */
	public function get_queued_scripts() {
		return $this->queued_scripts;
	}

	/**
	 * Get all the registered paths from a private variable, ordered by priority.
	 *
	 * This is meant to be used privately.
	 *
	 * @since  0.1
	 * @uses   WPSC_Template_Engine::get_paths()
	 * @return array
	 */
	private function get_paths( $var ) {
		$return = array();

		foreach ( $this->$var as $paths ) {
			$return = array_merge( $return, $paths );
		}

		return $return;
	}
}
