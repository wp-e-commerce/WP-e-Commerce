<?php
/**
 * Product browser view
 *
 * @package Sputnik
 * @subpackage Admin View
 */

/**
 * Product browser view
 *
 * @package Sputnik
 * @subpackage Admin View
 */
class Sputnik_View_Browser extends Sputnik_View {
	protected $title = 'Browse';

	protected $view = null;
	public $view_type = 'grid';
	protected $connect_error = false;

	public function __construct() {
		$class = 'Sputnik_View_Browser_Grid';
		if (!empty($_REQUEST['view'])) {
			switch ($_REQUEST['view']) {
				case 'list':
					$class = 'Sputnik_View_Browser_List';
					break;
				case 'grid':
				default:
					$class = 'Sputnik_View_Browser_Grid';
					break;
			}
		}

		$this->view = new $class();
		$this->view->parent = $this;
		$this->view->prepare_items();
	}

	public function get_view_url($view) {
		$bits = array('tab' => $view);
		if ($this->view->id !== 'grid') {
			$bits['view'] = $this->view->id;
		}
		return Sputnik_Admin::build_url($bits);
	}

	protected function display() {
		$this->view->views();
		$this->view->display();
	}

	public function get_tabs() {
		return array();

		global $tab;
		$tabs = array();
		$tabs['dashboard'] = __( 'Search', 'wp-e-commerce' );
		if ( 'search' == $tab )
			$tabs['search']	= __( 'Search Results', 'wp-e-commerce' );
		$tabs['featured'] = _x( 'Featured', 'Plugin Installer', 'wp-e-commerce' );
		$tabs['popular']  = _x( 'Popular', 'Plugin Installer', 'wp-e-commerce' );
		$tabs['new']      = _x( 'Newest', 'Plugin Installer', 'wp-e-commerce' );
		$tabs['updated']  = _x( 'Recently Updated', 'Plugin Installer', 'wp-e-commerce' );
		$tabs['price']    = _x( 'Lowest Priced', 'Plugin Installer', 'wp-e-commerce' );
		return $tabs;
	}

	public function get_data($tab) {
		global $paged;
		try {
			switch ( $tab ) {
				case 'search':
					$term = isset( $_REQUEST['s'] ) ? stripslashes( $_REQUEST['s'] ) : '';
					$api = Sputnik_API::search($term);
					break;

				case 'account':
					$api = Sputnik_API::get_purchased();
					break;

				case 'featured':
				case 'popular':
				case 'new':
				case 'updated':
				case 'price':
				default:
					$api = Sputnik_API::get_all($paged, array('browse' => $tab));
					break;
			}
		}
		catch (Exception $e) {
			$this->connect_error = true;
			return false;
		}

		return array('items' => $api['body'], 'pages' => $api['headers']['x-pagecount']);
	}

	public function no_items() {
		echo '<p>' . __( 'No plugins match your request.', 'wp-e-commerce' ) . '</p>';
	}

	public function footer() {
?>
	<script type="text/html" id="tmpl-sputnik-modal">
		<div class="sputnik-modal">
			<h3 class="sputnik-modal-title">{{ title }}</h3>
			<a class="sputnik-modal-close" href="" title="<?php esc_attr_e('Close', 'wp-e-commerce' ); ?>">&times;</a>
		</div>
		<div class="sputnik-modal-backdrop">
			<div></div>
		</div>
	</script>
<?php

		parent::footer();
	}
}