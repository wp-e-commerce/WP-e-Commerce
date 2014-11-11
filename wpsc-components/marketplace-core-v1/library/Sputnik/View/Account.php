<?php
/**
 * Account browser view
 *
 * @package Sputnik
 * @subpackage Admin View
 */

/**
 * Account browser view
 *
 * @package Sputnik
 * @subpackage Admin View
 */
class Sputnik_View_Account extends Sputnik_View_Browser {
	protected $title = 'Account';

	protected function header() {
		parent::header();
		$account = Sputnik::get_account();
?>
		<div class="account-card">
			<div class="block">
				<?php echo get_avatar($account->email) ?>
				<p class="lead-in"><?php _e('Logged in as', 'wpsc') ?></p>
				<h3><?php echo esc_html($account->name) ?></h3>
				<p><?php printf(__('<a href="%s">Log out</a> of your account', 'wpsc'), Sputnik_Admin::build_url(array('oauth' => 'reset'))) ?></p>
			</div>
			<div class="block">
				<p><?php printf(__('Email: %s', 'wpsc'), '<code>' . $account->email . '</code>') ?></p>
				<p class="stat"><?php printf(__('<strong>%d</strong> <abbr title="Plugins you can install right now">Available</abbr>', 'wpsc'), count($account->purchased)) ?></p>
				<p class="stat"><?php printf(__('<strong>%d</strong> <abbr title="Plugins you have bought from the store">Purchased</abbr>', 'wpsc'), $this->count) ?></p>
			</div>
		</div>

<?php
	}

	public function get_view_url($view) {
		$bits = array('tab' => $view);
		if ($this->view->id !== 'grid') {
			$bits['view'] = $this->view->id;
		}
		return Sputnik_Admin::build_account_url($bits);
	}

	public function get_tabs() {
		$tabs = array();
		$tabs['purchased'] = __( 'Purchased Plugins', 'wpsc' );
		$tabs['yours'] = __( 'Your Plugins', 'wpsc' );
		return $tabs;
	}

	public function get_data($tab) {
		try {
			switch ( $tab ) {
				case 'yours':
					add_filter('sputnik_install_row_action_links', array(__CLASS__, 'mangle_action_for_own'), 10, 2);
					add_filter('sputnik_install_grid_action_links', array(__CLASS__, 'mangle_action_for_own'), 10, 2);
					$api = Sputnik_API::get_own();
					break;

				default:
				case 'purchased':
					$api = Sputnik_API::get_purchased();
					break;
			}
		}
		catch (Exception $e) {
			$this->connect_error = true;
			return false;
		}

		$this->count = count($api['body']);

		if ($tab === 'yours') {
			$api['body'][] = (object) array(
				'slug' => '__add_new',
				'name' => __('Add Your Plugin', 'wpsc'),
				'description' => __('List your plugin on the WPEconomy store. Read our developer documentation and get started!', 'wpsc'),
				'rating' => (object) array('average' => 0, 'count' => 0),
				'price' => 0,
				'version' => '',
				'author' => '',
				'author_slug' => '',
				'thumb' => false
			);
		}

		$count = 1;
		if (!empty($api['headers']['x-pagecount'])) {
			$count = $api['headers']['x-pagecount'];
		}

		return array('items' => $api['body'], 'pages' => $count);
	}

	public function get_views() {
		global $tabs, $tab;

		$display_tabs = array();
		foreach ( (array) $tabs as $action => $text ) {
			$class = ( $action == $tab ) ? ' class="current"' : '';
			$bits = array('tab' => $action);
			if ($this->view !== 'grid') {
				$bits['view'] = $this->view;
			}
			$href = Sputnik_Admin::build_account_url($bits);
			$display_tabs['plugin-install-'.$action] = "<a href='$href'$class>$text</a>";
		}

		return $display_tabs;
	}

	public function no_items() {
		global $tab;
		echo '<p>';
		if ($tab === 'yours') {
			_e( "You haven't created any plugins yet. Check out our <a href='http://developer.renku.me/'>developer documentation</a> to find out how!", 'wpsc' );
		}
		else {
			printf(__( "You haven't purchased any plugins yet. Why not <a href='%s'>buy some</a>?", 'wpsc' ), Sputnik_Admin::build_url());
		}
		echo '</p>';
	}

	public static function mangle_action_for_own($actions, $plugin) {
		if ($plugin->slug === '__add_new') {
			return $actions;
		}

		$actions[] = sprintf(
			'<a href="%s" class="button edit" title="%s">%s</a>',
			sprintf(Sputnik::SITE_BASE . '/plugins/%d/edit/', $plugin->product_id),
			esc_attr(sprintf(_x('Edit %s', 'Edit button title', 'wpsc'), $plugin->name)),
			_x('Edit', 'Edit button text', 'wpsc')
		);
		return $actions;
	}
}