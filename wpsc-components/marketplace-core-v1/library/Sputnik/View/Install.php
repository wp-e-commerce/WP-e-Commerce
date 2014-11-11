<?php
/**
 * Popup installer view
 *
 * @package Sputnik
 * @subpackage Admin View
 */

/**
 * Popup installer view
 *
 * @package Sputnik
 * @subpackage Admin View
 */
class Sputnik_View_Install extends Sputnik_View_Mini {
	protected $body_id = 'sputnik-install';

	protected $id;
	protected $nonce_prefix = 'sputnik_install-plugin_';
	protected $title_format = 'Installing Plugin: %s';
	protected $upgrader = null;
	protected $api = null;

	public function __construct() {
		parent::__construct(__('Plugin Install', 'wpsc'));
		$this->id = $_GET['install'];
		$this->title = __('Installing Plugin: %s', 'wpsc');
	}

	protected function prepare() {
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		try {
			$this->api = $api = Sputnik::get_plugin($this->id);
		}
		catch (Exception $e) {
			status_header(500);

			$this->header();
			echo '<p>' . $e->getMessage() . '</p>';
			$this->footer();
			return;
		}

		if (!Sputnik::is_purchased($this->api->slug)) {
			wp_redirect(Sputnik_Admin::build_url(array('buy' => $this->id)));
			die();
		}

		if ( ! current_user_can('install_plugins') )
			wp_die(__('You do not have sufficient permissions to install plugins for this site.', 'wpsc'));

		check_admin_referer($this->nonce_prefix . $this->api->slug);

		include_once(ABSPATH . 'wp-admin/includes/plugin-install.php');

		$title = sprintf( $this->title_format, $this->api->name . ' ' . $this->api->version );
		$nonce = $this->nonce_prefix . $this->id;
		$url = 'update.php?action=install-plugin&plugin=' . $this->id;
		if ( isset($_GET['from']) )
			$url .= '&from=' . urlencode(stripslashes($_GET['from']));

		$type = 'web'; //Install plugin type, From Web or an Upload.

		if ( $this->api->is_theme )
			$this->upgrader = new Sputnik_ThemeUpgrader( new Sputnik_View_Install_Skin( compact('title', 'url', 'nonce', 'plugin', 'api') ) );
		else
			$this->upgrader = new Sputnik_Upgrader( new Sputnik_View_Install_Skin( compact('title', 'url', 'nonce', 'plugin', 'api') ) );

	}

	public function render() {
		$this->prepare();
		$this->header();
		add_filter('http_request_args', array('Sputnik_Updater', 'mangle_http'), 10, 2);
		$this->upgrader->install($this->api->download_link);
		remove_filter('http_request_args', array('Sputnik_Updater', 'mangle_http'), 10, 2);
		$this->footer();
	}
}