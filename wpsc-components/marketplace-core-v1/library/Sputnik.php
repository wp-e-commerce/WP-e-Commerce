<?php
/**
 * Main class
 *
 * @package Sputnik
 * @subpackage Public API
 */

/**
 * Main class
 *
 * @package Sputnik
 * @subpackage Public API
 */
class Sputnik {
	/**
	 * Minimum version of WordPress that Sputnik requires
	 */
	const MINVERSION = '3.9';

	/**
	 * Base URI for store URLs
	 */
	const SITE_BASE = 'https://wpecommerce.org';

	/**
	 * Base URI for API URLs
	 */
	const API_BASE = 'https://wpecommerce.org/wpec';

	/**
	 * OAuth client key
	 */
	const OAUTH_KEY = 'k7q3lu9LeOJc';

	/**
	 * OAuth client secret
	 *
	 * Not so secret any more.
	 */
	const OAUTH_SECRET = '8jjSVN54VFhraZttr8pDsCdnogqE22Sq299zTRdFDL2hEUIq';

	/**
	 * Path to Sputnik
	 * @var string
	 */
	public static $path = '';

	/**
	 * Cache of purchased plugins API data
	 */
	protected static $purchased = false;

	/**
	 * Installed plugin IDs
	 */
	protected static $installed = array();

	/**
	 * Enabled plugins which haven't been purchased
	 */
	protected static $invalid = array();

	/**
	 * Plugins which have been suspended remotely
	 */
	protected static $suspended = array();

	protected static $account = null;

	/**
	 * Register everything we need
	 */
	public static function bootstrap() {

		spl_autoload_register(array(get_class(), 'autoload'));

		self::$installed = get_option('sputnik_installed', array());
		self::$suspended = get_option('sputnik_suspended', array());

		//add_action('activated_plugin', array(get_class(), 'clear_installed'));
		//add_action('deactivated_plugin', array(get_class(), 'clear_installed'));

		// 'deactivated_plugin' runs before saving, so we have to do this instead:
		add_action('update_option_active_plugins', array(get_class(), 'clear_installed'));

		add_action( 'plugins_loaded', array(get_class(), 'loaded'));
		add_action( 'init', array(get_class(), 'init'));
		add_action( 'init', array( get_class(), 'check_for_saas_push' ) );
		add_action( 'init', array( get_class(), 'thumbnails' ) );
		add_action( 'init', array( get_class(), 'credentials' ) );
		add_action( 'wp', array( get_class(), 'show_login_form' ) );
		add_filter( 'extra_plugin_headers', array(get_class(), 'extra_headers'));
		add_filter( 'extra_theme_headers', array(get_class(), 'extra_headers'));

		add_action( 'wpsc_update_purchase_log_status', array( get_class(), 'push_sales_data' ), 10, 4 );
		add_action( 'init', array( get_class(), 'sales_data_postback' ) );

		add_filter( 'wpsc_purchase_log_customer_notification_raw_message', array( get_class(), 'add_download_link' ), 10, 2 );
		add_action( 'wpsc_transaction_results_shutdown'                  , array( get_class(), 'add_download_link_page' ), 10, 3 );

		Sputnik_Admin::bootstrap();
		Sputnik_Updater::bootstrap();
		Sputnik_Pointers::bootstrap();
	}

	public static function add_download_link( $message, $notification ) {
		$cart_contents = $notification->get_purchase_log()->get_cart_contents();

		$products = '';

		foreach ( $cart_contents as $product ) {
			$download_link = get_post_meta( $product->prodid, '_download_url', true );
			if ( !empty ( $download_link) ) {
				$download_link = esc_url( add_query_arg( 'marketplace', Sputnik_API::domain(), $download_link ) );
				$products .= "\n" . '<a href="' . $download_link . '">Download ' . $product->name . '</a>' . "\n";
			}
		}

		return $message . $products;
	}

	public static function show_login_form() {
		if ( ! isset( $_GET['marketplace'] ) )
			return;

			$url = 'http://www.wpeconomy.org/products-page/add/?framed=true&marketplace=' . urlencode( esc_url_raw( Sputnik_API::domain() ) );

			$auth = Sputnik_API::auth_request( $url, false );
		?>
		<html>
		<head>
			<script type="text/javascript" src="<?php echo home_url( $GLOBALS['wp_scripts']->registered['jquery']->src ); ?>"></script>
			<script type="text/javascript">
				jQuery(window).load(function() {
					jQuery( '#add_form' ).load( '<?php echo esc_url( $auth ); ?>' );
				});
			</script>
		</head>
		<body>
		<div id="add_form"></div>
		</body>
		</html>
		<?php
		die;
	}

	public static function add_download_link_page( $purchase_log_object, $sessionid, $display_to_screen ) {
		if ( ! $display_to_screen )
			return;

		$cart_contents = $purchase_log_object->get_cart_contents();

		$products = '';

		foreach ( $cart_contents as $product ) {
			$download_link = get_post_meta( $product->prodid, '_download_url', true );
			if ( !empty ( $download_link) ) {
				$download_link = esc_url( add_query_arg( 'marketplace', Sputnik_API::domain(), $download_link ) );
				$products .= "\n" . '<a href="' . $download_link . '">Download ' . $product->name . '</a>' . "\n";
			}
		}

		echo $products;
	}

	/**
	 * Callback for 'plugins_loaded' action
	 */
	public static function loaded() {
		do_action('sputnik_loaded');
	}

	/**
	 * Callback for 'init' action
	 */
	public static function init() {
		do_action('sputnik_init');
	}

	public static function thumbnails() {
		if ( ! isset( $_REQUEST['thumbnails'] ) || ( isset( $_REQUEST['thumbnails'] ) && 'true' !== $_REQUEST['thumbnails'] ) )
			return;

		$thumbs = array( 'width' => get_option( 'product_image_width' ), 'height' => get_option( 'product_image_height' ) );

		echo json_encode( $thumbs );
		die;
	}

	public static function check_for_saas_push() {

		if ( ! isset( $_REQUEST['json_product_push'] ) || ( isset( $_REQUEST['json_product_push'] ) && 'true' !== $_REQUEST['json_product_push'] ) )
			return;

			error_reporting( E_ERROR );

			if ( ! empty( $_POST['product'] ) ) {
				$product = stripslashes( $_POST['product'] );
				$product = json_decode( $product );

				$download_url = Sputnik::API_BASE . '/download/' . $product->post_name . '.zip';
				$thumb_url    = $product->thumbnail_url;

				//Check if local product exists - if so, update it, if not, don't.
				$local = get_posts( array(
					'pagename' => $product->post_name,
					'post_type' => 'wpsc-product',
					'post_status' => 'publish',
					'numberposts' => 1 )
				);

				$user_check = get_user_by( 'email', $product->author_email );

				if ( $user_check ) {
					$product->post_author = $user_check->ID;

					if ( ! in_array( 'vendor-administrator', $user_check->roles ) )
						$user_check->add_role( 'vendor-administrator' );
				}
				else {
					$product->post_author = wp_insert_user( array( 'role' => 'vendor-administrator', 'user_email' => $product->author_email, 'user_pass' => wp_generate_password(), 'user_login' => $product->author_email ) );
				}

				$product = (array) $product;
				unset( $product['guid'] );
				unset( $product['post_date_gmt'] );
				unset( $product['post_date'] );

				require_once(ABSPATH . 'wp-admin/includes/media.php');
				require_once(ABSPATH . 'wp-admin/includes/file.php');
				require_once(ABSPATH . 'wp-admin/includes/image.php');

				if ( ! empty( $local ) ) {
					$product['ID'] = $local[0]->ID;
					$new_id = wp_update_post( $product );
				} else {
					unset( $product['ID'] );
					// Doesn't exist, create it.  Then, after created, add download URL and thumbnail.
					$new_id = wp_insert_post( $product );
				}

				update_post_meta( $new_id, '_download_url', $download_url );

				foreach ( $product['meta'] as $key => $val ) {
					if ( '_wpsc_product_metadata' == $key )
						continue;

					if ( '_wpsc_currency' == $key )
						continue;

					update_post_meta( $new_id, $key, $val[0] );
				}



				$thumb = media_sideload_image( $thumb_url, $new_id, 'Product Thumbnail' );

				if ( ! is_wp_error( $thumb ) ) {
					$thumbnail_id = get_posts( array( 'post_type' => 'attachment', 'post_parent' => $new_id ) );

					if ( ! empty( $thumbnail_id ) ) {

						$thumbnail = set_post_thumbnail( $new_id, $thumbnail_id[0]->ID );
						echo json_encode( array( 'set_thumbnail' => $thumbnail, 'post_id' => $new_id ) );
						die;
					}
					die;
				}
				die;
			}

		exit;
	}

	public static function credentials() {

		if ( ! isset( $_REQUEST['credential_request'] ) || ( isset( $_REQUEST['credential_request'] ) && 'true' !== $_REQUEST['credential_request'] ) )
			return;

		if ( ! isset( $_SERVER['HTTP_X_CREDENTIALS_REQUEST'] ) )
			return;

		die( json_encode( get_option( 'wpsc_payment_gateway_paypal_digital_goods' ) ) );

	}

	/**
	 * Pushes sales data back to Baikonur
	 *
	 * Only pushes once.  Accounts for annoying potential edge case of status-switching admins
	 *
	 * @param  WPSC_Purchase_Log object $purchase_log Purchase Log object
	 * @return void
	 */
	public static function push_sales_data( $purchase_log_id, $current_status, $old_status, $purchase_log ) {

		$purchase_log = new WPSC_Purchase_Log( $purchase_log_id );

		$id = absint( $purchase_log->get( 'id' ) );

		//Also checking is_order_received, as that's what Manual Payments do.
		if ( $purchase_log->is_transaction_completed() || $purchase_log->is_order_received() ) {

			$pushed_to_sass = wpsc_get_meta( $id, '_pushed_to_wpeconomy', 'purchase_log' );

			if ( empty( $pushed_to_saas ) ) {

				$data          = $purchase_log->get_data();
				$cart_contents = $purchase_log->get_cart_contents();

				//We want to push sales data - but naturally, IDs will differ, even names could potentially.
				//So we add the slug to the object we POST
				foreach ( $cart_contents as $key => $cart_item ) {
					$slug = get_post_field( 'post_name', $cart_item->prodid );
					$cart_contents[ $key ]->slug = $slug;
				}

				$args = array(
					'body' => array( 'data' => json_encode( $data ), 'cart_contents' => json_encode( $cart_contents ) )
				);

				$request  = wp_remote_post( 'http://www.wpeconomy.org/?sales_data=true', $args );
				$response = wp_remote_retrieve_response_code( $request );

				//For some reason, if the site is down, we want the ability to ensure we can grab the sale later.
				$success = ( 200 === $response );

				wpsc_update_meta( $id, '_pushed_to_wpeconomy', $success, 'purchase_log' );
			}
		}
	}

	public static function sales_data_postback() {
		if ( ! isset( $_REQUEST['sales_data'] ) )
			return;

		$data          = json_decode( stripslashes( $_POST['data'] ) );
		$cart_contents = json_decode( stripslashes( $_POST['cart_contents'] ) );

		//Unset purchase log ID, since we're inserting a new one.
		$data = (array) $data;

		unset( $data['id'] );

		$purchase_log = new WPSC_Purchase_Log( $data );
		$purchase_log->save();
		$purchase_log_id = $purchase_log->get( 'id' );

		global $wpdb;

		//We need to update the proper product ID, name and purchase ID
		foreach ( $cart_contents as $cart_item ) {

			$product = new WP_Query( array( 'post_type' => 'wpsc-product', 'pagename' => $cart_item->slug ) );
			$product = $product->get_posts();
			$product = $product[0];

			$cart_item = ( array ) $cart_item;

			unset( $cart_item['id'] );
			unset( $cart_item['slug'] );

			$cart_item['prodid']     = $product->ID;
			$cart_item['name']       = $product->post_title;
			$cart_item['purchaseid'] = $purchase_log_id;

			$wpdb->insert( WPSC_TABLE_CART_CONTENTS, $cart_item );
		}

		die;
	}


	/**
	 * Register our extra plugin metadata headers
	 */
	public static function extra_headers($headers) {
		$headers[] = 'Sputnik ID';
		$headers[] = 'Requires WPEC Version';
		$headers[] = 'Compatible to WPEC Version';
		return $headers;
	}

	/**
	 * Autoload a Sputnik class
	 *
	 * @param string $class
	 */
	public static function autoload($class) {
		if (strpos($class, 'Sputnik') !== 0) {
			return;
		}

		$file = str_replace('_', '/', $class);
		if (file_exists(self::$path . '/library/' . $file . '.php')) {
			require_once(self::$path . '/library/' . $file . '.php');
		}
	}

	/**
	 * Get available modules
	 *
	 * @return array Available plugins (array of meta objects)
	 */
	public static function get_available() {
		$plugins = Sputnik_API::get_all();
		return $plugins['body'];
	}

	/**
	 * Get popular tags
	 *
	 * @return array
	 */
	public static function get_tags() {
		$tags = Sputnik_API::get_tags();
		return $tags['body'];
	}

	/**
	 * Return whether an account is linked to Sputnik
	 *
	 * @return bool
	 */
	public static function account_is_linked() {
		$token = get_option('sputnik_oauth_access', false);
		return (is_array($token) && !empty($token['oauth_token']));
	}

	/**
	 * Get account information
	 *
	 * @return stdObject
	 */
	public static function get_account() {
		if ( is_null( self::$account ) ) {
			$account = Sputnik_API::get_account();
			self::$account = $account['body'];
		}

		return self::$account;
	}

	/**
	 * Forces an update to the account information from the server
	 *
	 * @return stdObject
	 */
	public static function update_account() {
		self::$account = null;
		return self::get_account();
	}

	/**
	 * Get purchased plugins
	 *
	 * @return array Plugin slugs
	 */
	public static function get_purchased() {
		// This should be cached in a transient
		if (self::$purchased === false) {
			$purchased = Sputnik_API::get_purchased();
			self::$purchased = $purchased['body'];
		}
		return self::$purchased;
	}

	/**
	 * Check if a plugin has been purchased
	 *
	 * @param string $plugin Plugin slug
	 * @return boolean
	 */
	public static function is_purchased($plugin) {
		if (is_object($plugin)) {
			$plugin = $plugin->slug;
		}

		try {
			$account = self::get_account();
			return in_array($plugin, (array) $account->purchased);
		}
		catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Check if a plugin has been suspended
	 *
	 * @param string $plugin Plugin slug
	 * @return boolean
	 */
	public static function is_suspended($plugin) {
		if (is_object($plugin)) {
			$plugin = $plugin->slug;
		}

		return array_key_exists($plugin, self::$suspended);
	}

	/**
	 * Suspend a plugin
	 *
	 * For internal use by {@see Sputnik_Updater} only
	 * @param string $plugin Plugin slug
	 */
	public static function suspend_plugin($plugin, $file, $data) {
		// Check that the updater was the one that issued the command
		if (!Sputnik_Updater::confirm_suspend($plugin, $data)) {
			return false;
		}

		self::$suspended[$plugin] = $data;
		deactivate_plugins(array($file), true);
		update_option('sputnik_suspended', self::$suspended);
		return true;
	}

	/**
	 * Get a single module
	 *
	 * @param string $id
	 * @return stdObject
	 */
	public static function get_plugin($id, $user = 0) {
		$plugin = Sputnik_API::get_single($id, $user);
		return $plugin['body'];
	}

	public static function get_from_file($file) {
		if (empty(self::$installed)) {
			self::$installed = self::load_installed();
		}

		if (!empty(self::$installed[$file])) {
			return self::$installed[$file];
		}

		return null;
	}

	public static function check($file, $callback = null) {
		$file = plugin_basename($file);

		$plugin = self::get_from_file($file);

		if (!self::is_purchased($plugin['Sputnik ID'])) {
			$plugin['sputnik_error'] = 'not_purchased';
			self::$invalid[] = $plugin;
			return false;
		}

		if ($callback !== null) {
			call_user_func($callback);
		}
		return true;
	}

	public static function get_installed($force = false) {
		if (empty(self::$installed) || $force === true) {
			self::$installed = self::load_installed();
		}

		return self::$installed;
	}

	protected static function load_installed() {
		if (!function_exists('get_plugins')) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}

		$all = get_plugins();
		$plugins = array();
		foreach ($all as $file => $plugin) {
			if (empty($plugin['Sputnik ID'])) {
				continue;
			}
			$plugins[$file] = $plugin;
		}

		update_option('sputnik_installed', $plugins);

		return $plugins;
	}

	public static function clear_installed() {
		delete_option('sputnik_installed');
		self::$installed = self::load_installed();
	}

	public static function get_invalid() {
		return self::$invalid;
	}
}