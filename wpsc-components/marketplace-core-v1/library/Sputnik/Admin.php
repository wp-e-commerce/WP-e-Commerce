<?php

class Sputnik_Admin {
	protected static $page_is_current = false;
	protected static $list_table;

	protected static $page = 'dash';

	public static function bootstrap() {
		add_action( 'admin_init', array(__CLASS__, 'init'), 0);
		add_action( 'all_admin_notices', array(__CLASS__, 'report_errors'));

		add_action( 'admin_menu', array(__CLASS__, 'menu'));

		add_action( 'admin_head-wpsc-product_page_sputnik', array(__CLASS__, 'admin_head_page'));
		add_action( 'admin_head-wpsc-product_page_sputnik-account', array(__CLASS__, 'admin_head_page'));
		add_action( 'install_plugins_pre_plugin-information', array(__CLASS__, 'maybe_info'), 0);
		add_action( 'load-update.php', array(__CLASS__, 'maybe_redirect_update'));
		add_filter( 'plugin_row_meta', array(__CLASS__, 'add_row_note'), 10, 3);
		add_action( 'wp_ajax_sputnik_rate', array(__CLASS__, 'set_rating'));
	}

	public static function init() {

		if ( ! wpsc_is_store_admin() ) {
			return;
		}

		add_action('admin_print_styles', array(__CLASS__, 'styles'));
		add_action('admin_print_scripts', array(__CLASS__, 'scripts'));

		global $plugin_page;

		if ( $plugin_page !== 'sputnik' && $plugin_page !== 'sputnik-account' )
			return;

		// Run most OAuth stuff now, before output
		if (!empty($_GET['oauth'])) {
			if ($_GET['oauth'] == 'request') {
				$redirect_url = '';
				if ( ! empty( $_REQUEST['oauth_buy'] ) ) {
					$redirect_url = self::build_url( array( 'oauth' => 'callback' ) );
					$redirect_url = add_query_arg( 'oauth_buy', $_REQUEST['oauth_buy'], $redirect_url );
				}
				Sputnik_API::auth_request( $redirect_url );
			}
			if ($_GET['oauth'] == 'callback') {
				Sputnik_API::auth_access();
			}
			if ($_GET['oauth'] == 'reset') {
				delete_option('sputnik_oauth_request');
				delete_option('sputnik_oauth_access');

				wp_redirect(self::build_url());
			}
		}

		switch (true) {
			case isset($_GET['info']):
				self::$page = 'info';
				break;
			case isset($_GET['buy']):
				self::$page = 'buy';
				break;
			case isset($_GET['paid']):
				self::$page = 'paid';
				break;
			case isset($_GET['install']):
				self::$page = 'install';
				break;
			case isset($_GET['upgrade']):
				self::$page = 'upgrade';
				break;
			case isset($_GET['cancel_payment']):
				self::$page = 'cancel_payment';
				break;
			case $plugin_page === 'wpsc-product_page_sputnik-account':
				self::$page = 'account';
				$GLOBALS['tab'] = 'account';
				break;
			default:
				self::$page = 'dash';
				break;
		}

		// Avoid having to specify this for every page
		if (self::$page !== 'dash' && self::$page !== 'account') {
			$_GET['noheader'] = true;
		}
	}

	public static function report_errors() {
		$invalid = Sputnik::get_invalid();
		if (empty($invalid)) {
			return;
		}
?>
	<div class="error"><p><?php _e('The following plugins are disabled:', 'wp-e-commerce') ?></p>
	<ul>
<?php
		foreach ($invalid as $plugin) {
			if (empty($plugin['sputnik_error'])) {
				$plugin['sputnik_error'] = 'unknown';
			}
			switch ($plugin['sputnik_error']) {
				case 'not_purchased':
					$error = __('Not purchased', 'wp-e-commerce');
					break;
				default:
					$error = __('Unknown error', 'wp-e-commerce');
					break;
			}
?>
		<li><?php echo esc_html($plugin['Name']) ?> &mdash; <?php echo $error ?></li>
<?php
		}
?>
	</ul>
	</div>
<?php
	}

	/**
	 * Adds a note to all plugins handled by us on the plugin screen
	 */
	public static function add_row_note($meta, $file, $data) {
		if (empty($data['Sputnik ID'])) {
			return $meta;
		}
		echo '<a class="sputnik-plugin-row-note" href="' . self::build_url() . '"><span class="powered">' . __( 'Powered by WPEConomy', 'wp-e-commerce' ) . '</span><span class="corner"></span></a>';
		return $meta;
	}

	public static function admin_head_page() {

		if ( ! wpsc_is_store_admin() ) {
			return;
		}

		add_filter( 'admin_body_class', array( __CLASS__, 'admin_body_class' ) );

		if (self::$page === 'dash') {
			self::$list_table = new Sputnik_List_Install();
			$pagenum = self::$list_table->get_pagenum();
			self::$list_table->prepare_items();
		}
		elseif (self::$page === 'account') {
			self::$list_table = new Sputnik_List_Account();
			$pagenum = self::$list_table->get_pagenum();
			self::$list_table->prepare_items();
		}

		add_action('sputnik_messages', array(__CLASS__, 'admin_notices'));
	}

	public static function admin_body_class( $classes ) {
		return $classes . 'plugin-install-php';
	}

	public static function load_page() {
		//Sputnik_API::auth_or_redirect();
	}

	public static function styles() {
		wp_enqueue_style('sputnik', plugins_url( 'static/sputnik.css', Sputnik::$path . '/wpsc-marketplace' ), false, '20141202');
	?>
		<style type="text/css">
			span#wpsc-extensions-menu-link {
				font-weight: bold;
				color: <?php self::get_marketplace_link_color(); ?>;
			}
		</style>
	<?php
	}

	public static function scripts() {
		wp_enqueue_script('sputnik_js', plugins_url( 'static/sputnik.js', Sputnik::$path . '/wpsc-marketplace' ), array('jquery', 'common'), '20141202' );
	}

	public static function connect_notice() {
		if ( self::$page_is_current != true )
			return;

		if ( ! current_user_can( 'install_plugins' ) )
			return;

		$oauth_url    = self::build_url(array('oauth' => 'request', 'TB_iframe' => true));


?>
			<div class="sputnik-message updated">
				<p>
					<?php _e( '<strong>WPEConomy is now installed!</strong> &#8211; Get started by linking with your account!', 'wp-e-commerce' ); ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br />
					<?php _e( "If you haven't created an account yet, don't worry, you will be prompted to do so.", 'wp-e-commerce') ?>
				</p>
				<a href="<?php echo esc_html( $oauth_url ); ?>" class="thickbox button button-primary thickbox`"><?php _e( 'Link your account now', 'wp-e-commerce' ); ?></a>
			</div>
<?php
	}

	public static function admin_notices() {
		if ( isset( $_GET['payment_cancelled'] ) ) {
			self::print_message( __( 'Payment cancelled.', 'wp-e-commerce' ) );
		}
	}

	protected static function print_message($message = '') {
?>
		<div id="message" class="updated below-h2">
			<p><?php echo $message; ?></p>
		</div>
<?php
	}

	public static function menu_order($menu_order) {
		$real = array();

		foreach ( $menu_order as $index => $item ) {
			if ( $item != 'sputnik' )
				$real[] = $item;

			if ( $index == 0 )
				$real[] = 'sputnik';
		}

		return $real;
	}

	public static function menu() {
		$hooks[] = add_submenu_page( 'edit.php?post_type=wpsc-product', _x( 'Extensions', 'page title', 'wp-e-commerce' ), _x( '<span id="wpsc-extensions-menu-link">Extensions</span>', 'menu title', 'wp-e-commerce' ), 'install_plugins', 'sputnik', array( __CLASS__, 'page' ) );
		$hooks[] = 'plugin-install.php';

		foreach ( $hooks as $hook ) {
			add_action( "admin_print_styles-$hook" , array( __CLASS__, 'page_styles' ) );
			add_action( "admin_print_scripts-$hook", array( __CLASS__, 'page_scripts' ) );
		}
	}

	public static function build_url($args = array()) {
		$url = add_query_arg( array( 'post_type' => 'wpsc-product', 'page' => 'sputnik' ), admin_url( 'edit.php' ) );

		if (!empty($args)) {
			$url = add_query_arg( $args, $url );
		}
		return esc_url_raw( $url );
	}

	public static function build_account_url($args = array()) {
		$url = add_query_arg( array( 'post_type' => 'wpsc-product', 'page' => 'sputnik-account' ), admin_url( 'edit.php' ) );
		if (!empty($args)) {
			$url = add_query_arg( $args, $url );
		}
		return esc_url_raw( $url );
	}

	public static function page_styles() {
		self::$page_is_current = true;
		wp_enqueue_style('wpsc-marketplace-page', plugins_url( 'static/admin.css', Sputnik::$path . '/wpsc-marketplace' ), array( 'thickbox' ), '20141109' );
	}

	public static function get_marketplace_link_color() {
		global $_wp_admin_css_colors;

		$_color = get_user_option( 'admin_color' );

		if ( empty( $_color ) || ! isset( $_wp_admin_css_colors[ $_color ] ) ) {
			$_color = 'fresh';
		}

		$color = $_wp_admin_css_colors[ $_color ];

		if ( in_array( $_color, array( 'blue', 'coffee', 'ectoplasm', 'ocean', 'sunrise' ) ) ) {
			echo isset( $color->icon_colors['focus'] ) ? $color->icon_colors['focus'] : '';
		} else {
			echo isset( $color->colors[ 3 ] ) ? $color->colors[ 3 ] : '';
		}
	}

	public static function page_scripts() {
		wp_enqueue_script( 'jquery-masonry' );
		wp_enqueue_script( 'paypal', 'https://www.paypalobjects.com/js/external/dg.js' );
		wp_enqueue_script( 'wpsc-marketplace-js', plugins_url( 'static/admin.js', Sputnik::$path . '/wpsc-marketplace' ), array( 'jquery', 'jquery-masonry', 'thickbox', 'paypal' ), '20141109' );

		$l10n = array(
			'plugin_information' => __( 'Plugin Information:', 'wp-e-commerce' ),
			'ays'                => __( 'Are you sure you want to install this plugin?', 'wp-e-commerce' )
		);

		if ( ! empty( $_REQUEST['oauth_buy'] ) ) {
			$plugin           = Sputnik::get_plugin( $_REQUEST['oauth_buy'] );
			$status           = self::install_status( $plugin );
			$l10n['buy_id']   = $plugin->slug;
			$l10n['buy_href'] = $status['url'];
		}

		wp_localize_script( 'wpsc-marketplace-js', 'sputnikL10n', $l10n );
	}

	public static function page() {
		global $current_user;

		switch (self::$page) {
			case 'info':
				return self::info($_GET['info']);
			case 'buy':
				return self::purchase($_GET['buy']);
			case 'paid':
				return self::paid($_GET['paid']);
			case 'cancel_payment':
				return self::cancel_payment($_GET['cancel_payment']);
			case 'install':
				return self::install($_GET['install']);
			case 'upgrade':
				return self::upgrade($_GET['upgrade']);
			default:
				return self::other_pages();
		}
	}

	public static function maybe_info() {
		$plugin = $_REQUEST['plugin'];
		if (strpos($plugin, 'sputnik-') !== 0) {
			return;
		}

		$plugin = substr($plugin, 8);
		self::info($plugin);

		die();
	}

	protected static function info($plugin)	{
		global $tab;
		require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');

		define( 'IFRAME_REQUEST', true );

		try {
			if ( Sputnik::account_is_linked() ) {
				$account = Sputnik::get_account();
				$api = Sputnik::get_plugin( $plugin, $account->ID );
			} else {
				$api = Sputnik::get_plugin( $plugin );
			}
		} catch (Exception $e) {
			status_header(500);
			iframe_header( __('Plugin Install', 'wp-e-commerce') );
			echo $e->getMessage();
			iframe_footer();
			die();
		}

		$plugins_allowedtags = array('a' => array('href' => array(), 'title' => array(), 'target' => array()),
									'abbr' => array('title' => array()), 'acronym' => array('title' => array()),
									'code' => array(), 'pre' => array(), 'em' => array(), 'strong' => array(),
									'div' => array(), 'p' => array(), 'ul' => array(), 'ol' => array(), 'li' => array(),
									'h1' => array(), 'h2' => array(), 'h3' => array(), 'h4' => array(), 'h5' => array(), 'h6' => array(),
									'img' => array('src' => array(), 'class' => array(), 'alt' => array()));

		$plugins_section_titles = array(
			'description'  => _x('Description',  'Plugin installer section title', 'wp-e-commerce'),
			'installation' => _x('Installation', 'Plugin installer section title', 'wp-e-commerce'),
			'faq'          => _x('FAQ',          'Plugin installer section title', 'wp-e-commerce'),
			'screenshots'  => _x('Screenshots',  'Plugin installer section title', 'wp-e-commerce'),
			'changelog'    => _x('Changelog',    'Plugin installer section title', 'wp-e-commerce'),
			'other_notes'  => _x('Other Notes',  'Plugin installer section title', 'wp-e-commerce')
		);

		//Sanitize HTML
		$api->sections = isset( $api->sections ) ? (array) $api->sections : array();
		$api->author = links_add_target($api->author, '_blank');
		foreach ( $api->sections as $section_name => $content )
			$api->sections[$section_name] = wp_kses($content, $plugins_allowedtags);

		$api->screenshots = (array) $api->screenshots;
		foreach ( $api->screenshots as &$data ) {
			if (!isset($data->caption) || !isset($data->location)) {
				continue;
			}

			$data->caption = wp_kses($data->caption, $plugins_allowedtags);
			$data->location = esc_url($data->location, array('http', 'https'));
		}
		unset($data);

		foreach ( array( 'version', 'requires', 'tested', 'homepage', 'downloaded', 'slug', 'requires_wpec', 'tested_wpec' ) as $key ) {
			if ( isset( $api->$key ) )
				$api->$key = wp_kses( $api->$key, $plugins_allowedtags );
		}

		$section = isset($_REQUEST['section']) ? stripslashes( $_REQUEST['section'] ) : 'description'; //Default to the Description tab, Do not translate, API returns English.
		if ( empty($section) || (!isset($api->sections[ $section ]) && ($section !== 'screenshots' || empty($api->screenshots)))  )
			$section = array_shift( $section_titles = array_keys((array)$api->sections) );

		global $body_id;
		$body_id = 'sputnik-plugin-information';
		iframe_header( __('Plugin Install', 'wp-e-commerce') );
?>
		<div class="alignleft fyi">
			<h1><?php echo $api->name ?></h1>
			<?php if ( ! empty($api->download_link) && ( current_user_can('install_plugins') || current_user_can('update_plugins') ) ) : ?>
			<p class="action-button">
<?php
			$status = self::install_status( $api );

			switch ( $status['status'] ) {
				case 'purchase':
				default:
					if ( $status['url'] )
						echo '<a href="' . $status['url'] . '" target="_parent" id="' . $plugin . '" class="button-primary buy">' . sprintf(__('<span>$%.2f</span> Buy &amp; Install', 'wp-e-commerce'), $api->price) . '</a>';
					break;
				case 'install':
					if ( $status['url'] )
						echo '<a href="' . $status['url'] . '" class="button-primary install" title="' . __('You have already purchased, install now', 'wp-e-commerce') . '">' . __('Install Now', 'wp-e-commerce') . '</a>';
					break;
				case 'update_available':
					if ( $status['url'] )
						echo '<a href="' . $status['url'] . '" class="button-primary install">' . __('Install Update Now', 'wp-e-commerce') .'</a>';
					break;
				case 'newer_installed':
					echo '<a>' . sprintf(__('Newer Version (%s) Installed', 'wp-e-commerce'), $status['version']) . '</a>';
					break;
				case 'latest_installed':
					echo '<a>' . __('Latest Version Installed', 'wp-e-commerce') . '</a>';
					break;
			}
?>
			</p>
			<?php endif; ?>
<?php
		echo "<div id='plugin-information-header'>\n";
		echo "<ul id='sidemenu'>\n";
		foreach ( (array)$api->sections as $section_name => $content ) {
			if ( isset( $plugins_section_titles[ $section_name ] ) )
				$title = $plugins_section_titles[ $section_name ];
			else
				$title = ucwords( str_replace( '_', ' ', $section_name ) );

			$class = ( $section_name == $section ) ? ' class="current"' : '';
			$href = add_query_arg( array('tab' => $tab, 'section' => $section_name) );
			$href = esc_url($href);
			$san_section = esc_attr($section_name);
			echo "\t<li><a name='$san_section' href='$href'$class>$title</a></li>\n";
		}

		if (!empty($api->screenshots)) {
			$title = $plugins_section_titles['screenshots'];
			$class = ( 'screenshots' == $section ) ? ' class="current"' : '';
			$href = add_query_arg( array('tab' => $tab, 'section' => 'screenshots') );
			$href = esc_url($href);
			echo "\t<li><a name='screenshots' href='$href'$class>$title</a></li>\n";
		}
		echo "</ul>\n";
		echo "</div>\n";
?>
			<h2 class="mainheader"><?php /* translators: For Your Information */ _e('FYI', 'wp-e-commerce') ?></h2>
			<ul>
	<?php if ( ! empty($api->version) ) : ?>
				<li><strong><?php _e('Version:', 'wp-e-commerce') ?></strong> <?php echo $api->version ?></li>
	<?php endif; if ( ! empty($api->author) ) : ?>
				<li><strong><?php _e('Author:', 'wp-e-commerce') ?></strong> <?php echo $api->author ?></li>
	<?php endif; if ( ! empty($api->last_updated) ) : ?>
				<li><strong><?php _e('Last Updated:', 'wp-e-commerce') ?></strong> <span title="<?php echo $api->last_updated ?>"><?php
								printf( __('%s ago', 'wp-e-commerce'), human_time_diff(strtotime($api->last_updated)) ) ?></span></li>
	<?php endif; if ( ! empty($api->requires) ) : ?>
				<li><strong><?php _e('Requires WordPress Version:', 'wp-e-commerce') ?></strong> <?php printf(__('%s or higher', 'wp-e-commerce'), $api->requires) ?></li>
	<?php endif; if ( ! empty($api->tested) ) : ?>
				<li><strong><?php _e('Compatible up to:', 'wp-e-commerce') ?></strong> <?php echo $api->tested ?></li>
	<?php endif; if ( ! empty($api->requires_wpec) ) : ?>
				<li><strong><?php _e('Requires WPeC Version:', 'wp-e-commerce') ?></strong> <?php printf(__('%s or higher', 'wp-e-commerce'), $api->requires_wpec) ?></li>
	<?php endif; if ( ! empty($api->tested_wpec) ) : ?>
				<li><strong><?php _e('Compatible up to WPEC Version:', 'wp-e-commerce') ?></strong> <?php echo $api->tested_wpec ?></li>
	<?php endif; if ( ! empty($api->downloaded) ) : ?>
				<li><strong><?php _e('Downloaded:', 'wp-e-commerce') ?></strong> <?php printf(_n('%s time', '%s times', $api->downloaded, 'wp-e-commerce'), number_format_i18n($api->downloaded)) ?></li>
	<?php endif; if ( ! empty($api->homepage) ) : ?>
				<li><a target="_blank" href="<?php echo $api->homepage ?>"><?php _e('Plugin Homepage  &#187;', 'wp-e-commerce') ?></a></li>
	<?php endif; ?>
			</ul>
		</div>
		<div id="section-holder" class="wrap">
		<?php
			if ( !empty($api->tested) && version_compare( substr($GLOBALS['wp_version'], 0, strlen($api->tested)), $api->tested, '>') )
				echo '<div class="updated"><p>' . __('<strong>Warning:</strong> This plugin has <strong>not been tested</strong> with your current version of WordPress.', 'wp-e-commerce') . '</p></div>';

			else if ( !empty($api->requires) && version_compare( substr($GLOBALS['wp_version'], 0, strlen($api->requires)), $api->requires, '<') )
				echo '<div class="updated"><p>' . __('<strong>Warning:</strong> This plugin has <strong>not been marked as compatible</strong> with your version of WordPress.', 'wp-e-commerce') . '</p></div>';

			else if ( !empty($api->requires_wpec) && version_compare( substr( WPSC_VERSION, 0, strlen($api->requires_wpec)), $api->requires_wpec, '<') )
				echo '<div class="updated"><p>' . __('<strong>Warning:</strong> This plugin has <strong>not been marked as compatible</strong> with your version of WP eCommerce.', 'wp-e-commerce') . '</p></div>';

			else if ( !empty($api->tested_wpec) && version_compare( substr( WPSC_VERSION, 0, strlen($api->tested_wpec)), $api->tested_wpec, '<') )
				echo '<div class="updated"><p>' . __('<strong>Warning:</strong> This plugin has <strong>not been tested</strong> with your version of WP eCommerce.', 'wp-e-commerce') . '</p></div>';

			foreach ( $api->sections as $section_name => $content ) {
				if ( isset( $plugins_section_titles[ $section_name ] ) )
					$title = $plugins_section_titles[ $section_name ];
				else
					$title = ucwords( str_replace( '_', ' ', $section_name ) );

				$content = links_add_base_url($content, $api->permalink);
				$content = links_add_target($content, '_blank');

				$san_section = esc_attr($title);

				$display = ( $section_name == $section ) ? 'block' : 'none';

				echo "\t<div id='section-{$san_section}' class='section' style='display: {$display};'>\n";
				echo "\t\t<h2 class='long-header'>$title</h2>";
				echo $content;
				echo "\t</div>\n";
			}

			if (!empty($api->screenshots)) {
				$display = ( 'screenshots' == $section ) ? 'block' : 'none';
				echo "\t<div id='section-screenshots' class='section' style='display: {$display};'>\n";
				echo "\t\t<h2 class='long-header'>Screenshots</h2>\n";
				echo "\t\t<ol>\n";
				foreach ($api->screenshots as $data) {
					echo "\t\t\t<li><img src='{$data->location}' class='screenshot' /><p>{$data->caption}</p></li>\n";
				}
				echo "\t\t</ol>\n";
				echo "\t</div>\n";
			}

		echo "</div>\n";

		iframe_footer();
		die();
	}

	/**
	 * Set the rating for a given plugin
	 */
	public static function set_rating() {
		header('Content-Type: application/json; charset=utf-8');
		try {
			$rating = absint($_POST['rating']);
			Sputnik_API::rate_product($_POST['product'], $rating);
			echo json_encode(array('success' => true, 'rating' => $rating));
		}
		catch (Exception $e) {
			status_header(500);
			echo json_encode(array('success' => false, 'error' => $e->getMessage()));
		}
		die();
	}

	/**
	 * Determine the action we can perform on a plugin
	 *
	 * @param stdClass $api API data
	 * @param boolean $loop Prevents further loops when called recursively
	 * @return array Keys 'status', 'url', 'version'
	 */
	public static function install_status($api, $loop = false) {
		// Default to a "new" plugin
		$status = 'install';
		$url = false;

		// Check to see if this plugin is known to be installed, and has an update awaiting it.
		$update_plugins = get_site_transient('update_plugins');
		if (is_object($update_plugins) && isset($update_plugins->response)) {
			foreach ((array) $update_plugins->response as $file => $plugin) {
				if (!empty($plugin->sputnik_id) && $plugin->sputnik_id === $api->slug) {
					$status = 'update_available';
					$version = $plugin->new_version;
					if ( current_user_can('update_plugins') )
						$url = wp_nonce_url(self::build_url(array('upgrade' => $file)), 'sputnik_upgrade-plugin_' . $file);
					break;
				}
			}
		}

		if ('install' == $status) {
			$installed = get_plugins();
			$real = false;

			foreach ( $installed as $plugin ) {
				if ( ! empty( $plugin['Sputnik ID'] ) && $plugin['Sputnik ID'] === $api->slug ) {
					$real = $plugin;
					break;
				}
			}

			if ($real === false) {
				if (current_user_can('install_plugins')) {
					$url = wp_nonce_url(self::build_url(array('install' => $api->slug)), 'sputnik_install-plugin_' . $api->slug);
				}
			} else {
				if (version_compare($api->version, $plugin['Version'], '=')){
					$status = 'latest_installed';
				} elseif (version_compare($api->version, $plugin['Version'], '<')) {
					$status = 'newer_installed';
					$version = $plugin['Version'];
				} else {
					// If the above update check failed, Then that probably means that the update checker has out-of-date information, force a refresh
					if (!$loop) {
						delete_site_transient('update_plugins');
						wp_update_plugins();
						return self::install_status($api, true);
					}

					// Otherwise, we'll need to tell the user there's an update, though we have no idea how they can get it
					$status = 'update_available';
				}
			}
		}

		if (!Sputnik::is_purchased($api)) {
			$status = 'purchase';
			$url = wp_nonce_url(self::build_url(array('buy' => $api->slug)), 'sputnik_install-plugin_' . $api->slug);
		}

		if ( ! Sputnik::account_is_linked() )
			$url = self::build_url( array(
				'oauth'     => 'request',
				'oauth_buy' => $api->slug,
				'TB_iframe' => true,
				'height'    => 600,
				'width'     => 800
			) );

		return compact('status', 'url', 'version');
	}

	protected static function header( $account ) {
		if ($account !== false) {
			$tabs = array(
				'dash' => __('Store', 'wp-e-commerce'),
				'account' => __('Your Account', 'wp-e-commerce'),
			);
			$hrefs = array(
				'dash' => self::build_url(),
				'account' => menu_page_url( 'sputnik-account', false ),
			);

			$current = self::$page;
		}
?>
		<div class="wrap" id="sputnik-page">
			<h2><?php _e( 'Marketplace', 'wp-e-commerce' ); ?></h2>
<?php
		do_action('sputnik_messages');
	}

	protected static function other_pages() {
		global $tab;

		$account = false;
		try {
			$account = Sputnik::get_account();
		}
		catch (Exception $e) {
			if ($e->getCode() === 401) {
				delete_option('sputnik_oauth_access');
				delete_option('sputnik_oauth_request');
			}
			elseif ( $e->getCode() !== 1 ) {
				echo '<p>' . sprintf(__('Problem: %s', 'wp-e-commerce'), $e->getMessage() ). '</p>';
			}
		}

		self::header( $account );

		if ( Sputnik::account_is_linked() ) {
			self::auth();
			?>
			<div class="account-card">
				<div class="block">
					<?php echo get_avatar($account->email) ?>
					<p class="lead-in">Logged in as</p>
					<h3><?php echo esc_html($account->name) ?></h3>
					<p><?php printf(__('<a href="%s">Log out</a> of your account', 'wp-e-commerce'), self::build_url(array('oauth' => 'reset'))) ?></p>
				</div>
				<div class="block">
					<p>Email: <code><?php echo $account->email ?></code></p>
					<?php if ( $tab != 'purchased' ): ?>
						<p class="stat"><?php printf(__('<strong>%d</strong> <abbr title="Plugins you can install right now">Available</abbr>', 'wp-e-commerce'), count( self::$list_table->items )) ?></p>
					<?php endif; ?>
					<p class="stat"><?php printf(__('<strong>%d</strong> <abbr title="Plugins you have bought from the store">Purchased</abbr>', 'wp-e-commerce'), count( $account->purchased ) ) ?></p>
				</div>
			</div>
			<?php
		}
		self::$list_table->views();
		self::$list_table->display();
	}

	/**
	 * Output the main landing page for the Sputnik administration screen.
	 */
	protected static function dashboard() { ?>
		<p><?php _e('Some text about WPEconomy goes here! This will eventually be replaced with a dashboard-like interface, including latest news, etc.', 'wp-e-commerce'); ?></p>

		<h4><?php _e( 'Search', 'wp-e-commerce' ) ?></h4>
		<p class="install-help"><?php _e('Search for plugins by keyword.', 'wp-e-commerce') ?></p>
		<?php Sputnik_Admin::search_form(); ?>

		<h4><?php _e( 'Popular tags', 'wp-e-commerce' ) ?></h4>
		<p class="install-help"><?php _e('You may also browse based on the most popular tags on the store:', 'wp-e-commerce') ?></p>
<?php
		echo '<p class="popular-tags">';

		try {
			$api_tags = Sputnik::get_tags();

			//Set up the tags in a way which can be interpreted by wp_generate_tag_cloud()
			$tags = array();
			foreach ($api_tags as $tag) {
				$tags[ $tag->name ] = (object) array(
					'link' => esc_url( self::build_url(array('tab' => 'search', 's' => urlencode($tag->name))) ),
					'name' => $tag->name,
					'id' => sanitize_title_with_dashes($tag->name),
					'count' => $tag->count
				);
			}
			echo wp_generate_tag_cloud($tags, array( 'single_text' => __('%s plugin', 'wp-e-commerce'), 'multiple_text' => __('%s plugins', 'wp-e-commerce') ) );
		}
		catch (Exception $e) {
			echo $e->getMessage();
		}
		echo '</p><br class="clear" />';
	}

	public static function account() {
		self::$page = 'account';
		$account = false;

		try {
			$account = Sputnik::get_account();
		}
		catch (Exception $e) {
			if ($e->getCode() === 1) {
				$GLOBALS['tab'] = 'auth';
				return self::other_pages();
			}
			elseif ($e->getCode() === 401) {
				delete_option('sputnik_oauth_access');
				delete_option('sputnik_oauth_request');
				$GLOBALS['tab'] = 'auth';
				return self::other_pages();
			}
			else {
				self::header('Account', $account);
				echo '<p>' . sprintf(__('Problem: %s', 'wp-e-commerce'), $e->getMessage()) . '</p>';

				return;
			}
		}

		self::header('Account', $account);
?>
		<div class="account-card">
			<div class="block">
				<?php echo get_avatar($account->email) ?>
				<p class="lead-in">Logged in as</p>
				<h3><?php echo esc_html($account->name) ?></h3>
				<p><?php printf(__('<a href="%s">Log out</a> of your account', 'wp-e-commerce'), self::build_url(array('oauth' => 'reset'))) ?></p>
			</div>
			<div class="block">
				<p>Email: <code><?php echo $account->email ?></code></p>
				<p class="stat"><?php printf(__('<strong>%d</strong> <abbr title="Plugins you can install right now">Available</abbr>', 'wp-e-commerce'), count($account->purchased)) ?></p>
				<p class="stat"><?php printf(__('<strong>%d</strong> <abbr title="Plugins you have bought from the store">Purchased</abbr>', 'wp-e-commerce'), count(self::$list_table->items)) ?></p>
			</div>
		</div>

<?php
		self::$list_table->views();
		self::$list_table->display();
	}

	protected static function auth() {
		$oauth_url    = self::build_url(array('oauth' => 'request', 'TB_iframe' => true));

		if ( isset( $_GET['auth'] ) && $_GET['auth'] == 'denied' ) {
			self::print_message( __( 'Authorization cancelled.', 'wp-e-commerce' ) );
		}
	}

	/**
	 * When a user clicks a plugin's "Buy Now" button, setup a payment flow.
	 *
	 * @param string $plugin_id
	 */
	protected static function purchase( $plugin_id ) {

		$plugin = Sputnik::get_plugin( $plugin_id );

		if( Sputnik::is_purchased( $plugin->slug ) ) {
			wp_redirect( self::build_url( array( 'install' => $plugin_id ) ) );
			die();
		}

		// Request a checkout token from the Baikonur REST API for this product (associate user ID in custom field?)
		$response = Sputnik_API::get_checkout_token( $plugin );

		// Redirect to PayPal with token in checkout URL
		wp_redirect( $response['body']->checkout_uri );
		exit;
	}

	/**
	 * When a user returns from the server after making a payment, update their account,
	 * close the PayPal iframe & redirect to the installation page for the plugin they
	 * just purchased.
	 *
	 * @param string $product_slug the slug of the product just purchased
	 */
	protected static function paid( $product_slug ) {

		check_admin_referer( 'sputnik_install-plugin_' . $product_slug );

		// Update Sputnik account to include newly purchased plugin
		Sputnik::update_account();

		$install_url = self::build_url( array( 'install' => $product_slug ) );
		$install_url = add_query_arg('_wpnonce', wp_create_nonce('sputnik_install-plugin_' . $product_slug), $install_url);
		$install_url = add_query_arg( array( 'TB_iframe' => true ), $install_url );

		self::iframe_closer( self::build_url( array('run-installer' => urlencode( $install_url ) ) ), __( 'Installing ... ', 'wp-e-commerce' ) );
	}

	/**
	 * When a user cancels a payment, we need to close the PayPal iframe & redirect
	 * back to Sputnik with a notice.
	 *
	 * @param string $plugin_id the slug of the plugin just purchased
	 */
	protected static function cancel_payment() {

		$cancelled_url = self::build_url( array( 'payment_cancelled' => true ) );

		self::iframe_closer( $cancelled_url, __( 'Payment Cancelled', 'wp-e-commerce' ) );
	}

	protected static function install($id) {

		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		try {
			$api = Sputnik::get_plugin($id);
		}
		catch (Exception $e) {
			status_header(500);
			iframe_header( __('Plugin Install', 'wp-e-commerce') );
			echo $e->getMessage();
			iframe_footer();
			die();
		}

		if (!Sputnik::is_purchased($api->slug)) {
			wp_redirect(self::build_url(array('buy' => $id)));
			die();
		}

		if ( ! current_user_can('install_plugins') )
			wp_die(__('You do not have sufficient permissions to install plugins for this site.', 'wp-e-commerce'));

		include_once(ABSPATH . 'wp-admin/includes/plugin-install.php');

		check_admin_referer('sputnik_install-plugin_' . $api->slug);

		global $body_id;
		$body_id = 'sputnik-install';
		iframe_header( __('Plugin Install', 'wp-e-commerce') );

		$title = sprintf( __('Installing Plugin: %s', 'wp-e-commerce'), $api->name . ' ' . $api->version );
		$nonce = 'sputnik_install-plugin_' . $id;
		$url = 'update.php?action=install-plugin&plugin=' . $id;
		if ( isset($_GET['from']) )
			$url .= '&from=' . urlencode(stripslashes($_GET['from']));

		$type = 'web'; //Install plugin type, From Web or an Upload.

		if ( in_array( 'theme', $api->categories ) ) {
			$upgrader = new Sputnik_ThemeUpgrader( new Sputnik_Upgrader_Skin( compact('title', 'url', 'nonce', 'plugin', 'api') ) );
		} else {
			$upgrader = new Sputnik_Upgrader( new Sputnik_Upgrader_Skin( compact('title', 'url', 'nonce', 'plugin', 'api') ) );
		}

		$upgrader->install( $api->download_link );

		iframe_footer();
		die();
	}

	protected static function upgrade($file) {
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		try {
			$data = Sputnik::get_from_file($file);
			if ($data === null) {
				throw new Exception(__('Plugin not found', 'wp-e-commerce'));
			}
			$id = $data['Sputnik ID'];
			$api = Sputnik::get_plugin($id);
		}
		catch (Exception $e) {
			status_header(500);
			iframe_header( __('Update Plugin', 'wp-e-commerce') );
			echo $e->getMessage();
			iframe_footer();
			die();
		}

		if (!Sputnik::is_purchased($id)) {
			wp_redirect(self::build_url(array('buy' => $id)));
			die();
		}

		if ( ! current_user_can('install_plugins') )
			wp_die(__('You do not have sufficient permissions to install plugins for this site.', 'wp-e-commerce'));

		include_once(ABSPATH . 'wp-admin/includes/plugin-install.php');

		check_admin_referer('sputnik_upgrade-plugin_' . $file);

		global $body_id;
		$body_id = 'sputnik-upgrade';
		iframe_header( __('Update Plugin', 'wp-e-commerce') );

		$title = sprintf( __('Updating Plugin: %s', 'wp-e-commerce'), $api->name . ' ' . $api->version );
		$nonce = 'sputnik_upgrade-plugin_' . $id;
		$url = 'update.php?action=upgrade-plugin&plugin=' . $id;
		if ( isset($_GET['from']) )
			$url .= '&from=' . urlencode(stripslashes($_GET['from']));

		$type = 'web'; //Install plugin type, From Web or an Upload.
		$plugin = $id;

		if ( $api->is_theme )
			$upgrader = new Sputnik_ThemeUpgrader( new Sputnik_Upgrader_Skin( compact('title', 'url', 'nonce', 'plugin', 'api') ) );
		else
			$upgrader = new Sputnik_Upgrader( new Sputnik_Upgrader_Skin( compact('title', 'url', 'nonce', 'plugin', 'api') ) );

		$upgrader->upgrade($file);

		iframe_footer();
		die();
	}

	public static function search_form(){
		$type = isset($_REQUEST['type']) ? stripslashes( $_REQUEST['type'] ) : '';
		$term = isset($_REQUEST['s']) ? esc_attr($_REQUEST['s']) : '';

		?><form id="search-plugins" method="get" action="">
			<input type="hidden" name="page" value="sputnik" />
			<input type="hidden" name="post_type" value="wpsc-product" />
			<input type="hidden" name="tab" value="search" />
			<input type="text" name="s" value="<?php echo esc_attr($term) ?>" />
			<?php submit_button( __( 'Search Plugins', 'wp-e-commerce' ), 'button', '', false ); ?>
		</form><?php
	}

	/**
	 * When a user cancels a payment or returns after making a payment, we need to
	 * close the PayPal iframe.
	 *
	 * @param string $redirect_url The URL to load in the parent window.
	 * @param string $title optional The title attribute for the page.
	 */
	public static function iframe_closer( $redirect_url, $title = null ) {
		if (empty($title)) {
			$title = __('Redirecting...', 'wp-e-commerce');
		}
?>
<!DOCTYPE html><html>
	<head>
		<title><?php echo $title; ?></title>
		<script type="text/javascript">if (window!=top) {top.location.replace("<?php echo $redirect_url; ?>");}</script>
	</head>
	<body>&nbsp;</body>
</html>
<?php
	die();
	}

	public static function maybe_redirect_update() {
		if (empty($_GET['action']) || $_GET['action'] !== 'upgrade-plugin' || empty($_REQUEST['plugin'])) {
			return;
		}
		$file = trim($_REQUEST['plugin']);

		$data = Sputnik::get_from_file($file);
		if ($data === null || empty($data['Sputnik ID'])) {
			return;
		}

		$url = self::build_url(array('upgrade' => $file));
		// wp_nonce_url also does a esc_html, so do it ourselves
		$url = add_query_arg('_wpnonce', wp_create_nonce('sputnik_upgrade-plugin_' . $file), $url);
		wp_redirect( esc_url_raw( $url ) );

		die();
	}
}