<?php
/**
 * Popup upgrader view
 *
 * @package Sputnik
 * @subpackage Admin View
 */

/**
 * Popup upgrader view
 *
 * @package Sputnik
 * @subpackage Admin View
 */
class Sputnik_View_Upgrade extends Sputnik_View_Install {
	protected $file;

	public function __construct() {
		parent::__construct();
		$this->title = __('Update Plugin', 'wpsc');

		$this->body_id = 'sputnik-upgrade';
		$this->nonce_prefix = 'sputnik_upgrade-plugin_';
		$this->title_format = __('Updating Plugin: %s', 'wpsc');
	}

	protected function prepare() {
		try {
			$this->file = $_GET['upgrade'];
			$data = Sputnik::get_from_file($file);
			if ($data === null) {
				throw new Exception(__('Plugin not found', 'wpsc'));
			}
			$this->id = $data['Sputnik ID'];
		}
		catch (Exception $e) {
			status_header(500);
			iframe_header( __('Update Plugin', 'wpsc') );
			echo $e->getMessage();
			iframe_footer();
			die();
		}

		parent::prepare();
	}

	public function render() {
		$this->prepare();
		$this->header();
		$this->upgrader->upgrade($this->file);
		$this->footer();
	}
}