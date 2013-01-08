<?php
/**
  * Plugin Name: WP e-Commerce
  * Plugin URI: http://getshopped.org/
  * Description: A plugin that provides a WordPress Shopping Cart. See also: <a href="http://getshopped.org" target="_blank">GetShopped.org</a> | <a href="http://getshopped.org/forums/" target="_blank">Support Forum</a> | <a href="http://docs.getshopped.org/" target="_blank">Documentation</a>
  * Version: 3.8.9.5
  * Author: Instinct Entertainment
  * Author URI: http://getshopped.org/
  **/

/**
 * WP_eCommerce
 *
 * Main WPEC Plugin Class
 *
 * @package wp-e-commerce
 */
class WP_eCommerce {

	/**
	 * Start WPEC on plugins loaded
	 */
	function WP_eCommerce() {
		add_action( 'plugins_loaded', array( $this, 'init' ), 8 );
	}

	/**
	 * Takes care of loading up WPEC
	 */
	function init() {
		// Previous to initializing
		do_action( 'wpsc_pre_init' );

		// Initialize
		$this->start();
		$this->constants();
		$this->includes();
		$this->load();

		// Finished initializing
		do_action( 'wpsc_init' );
	}

	/**
	 * Initialize the basic WPEC constants
	 */
	function start() {
		// Set the core file path
		define( 'WPSC_FILE_PATH', dirname( __FILE__ ) );

		// Define the path to the plugin folder
		define( 'WPSC_DIR_NAME',  basename( WPSC_FILE_PATH ) );

		// Define the URL to the plugin folder
		define( 'WPSC_FOLDER',    dirname( plugin_basename( __FILE__ ) ) );
		define( 'WPSC_URL',       plugins_url( '', __FILE__ ) );

		//load text domain
		if( !load_plugin_textdomain( 'wpsc', false, '../languages/' ) )
			load_plugin_textdomain( 'wpsc', false, dirname( plugin_basename( __FILE__ ) ) . '/wpsc-languages/' );

		// Finished starting
		do_action( 'wpsc_started' );
	}

	/**
	 * Setup the WPEC core constants
	 */
	function constants() {
		// Define globals and constants used by wp-e-commerce
		require_once( WPSC_FILE_PATH . '/wpsc-core/wpsc-constants.php' );

		// Load the WPEC core constants
		wpsc_core_constants();

		// Is WordPress Multisite
		wpsc_core_is_multisite();

		// Start the wpsc session
		wpsc_core_load_session();

		// Which version of WPEC
		wpsc_core_constants_version_processing();

		// WPEC Table names and related constants
		wpsc_core_constants_table_names();

		// Uploads directory info
		wpsc_core_constants_uploads();

		// Any additional constants can hook in here
		do_action( 'wpsc_constants' );
	}

	/**
	 * Include the rest of WPEC's files
	 */
	function includes() {
		require_once( WPSC_FILE_PATH . '/wpsc-core/wpsc-functions.php' );
		require_once( WPSC_FILE_PATH . '/wpsc-core/wpsc-installer.php' );
		require_once( WPSC_FILE_PATH . '/wpsc-core/wpsc-includes.php' );

		// Any additional file includes can hook in here
		do_action( 'wpsc_includes' );
	}

	/**
	 * Setup the WPEC core
	 */
	function load() {
		// Before setup
		do_action( 'wpsc_pre_load' );

		// Legacy action
		do_action( 'wpsc_before_init' );

		// Setup the customer ID just in case to make sure it's set up correctly
		_wpsc_action_create_customer_id( 'create' );

		// Setup the core WPEC globals
		wpsc_core_setup_globals();

		// Setup the core WPEC cart
		wpsc_core_setup_cart();

		// Load the thumbnail sizes
		wpsc_core_load_thumbnail_sizes();

		// Load the purchase log statuses
		wpsc_core_load_purchase_log_statuses();

		// Load unique names and checout form types
		wpsc_core_load_checkout_data();

		// Load the gateways
		wpsc_core_load_gateways();

		// Load the shipping modules
		wpsc_core_load_shipping_modules();

		// Set page title array for important WPSC pages
		wpsc_core_load_page_titles();

		// WPEC is fully loaded
		do_action( 'wpsc_loaded' );
	}

	/**
	 * WPEC Activation Hook
	 */
	function install() {
		global $wp_version;
		if((float)$wp_version < 3.0){
			 deactivate_plugins(plugin_basename(__FILE__)); // Deactivate ourselves
			 wp_die( __('Looks like you\'re running an older version of WordPress, you need to be running at least WordPress 3.0 to use WP e-Commerce 3.8', 'wpsc'), __('WP e-Commerce 3.8 not compatible', 'wpsc'), array('back_link' => true));
			return;
		}
		define( 'WPSC_FILE_PATH', dirname( __FILE__ ) );
		require_once( WPSC_FILE_PATH . '/wpsc-core/wpsc-installer.php' );
		$this->constants();
		wpsc_install();

	}

	public function deactivate() {
		foreach ( wp_get_schedules() as $cron => $schedule ) {
			wp_clear_scheduled_hook( "wpsc_{$cron}_cron_task" );
		}
	}
}

// Start WPEC
$wpec = new WP_eCommerce();

// Activation
register_activation_hook( __FILE__, array( $wpec, 'install' ) );
register_deactivation_hook( __FILE__, array( $wpec, 'deactivate' ) );
?>