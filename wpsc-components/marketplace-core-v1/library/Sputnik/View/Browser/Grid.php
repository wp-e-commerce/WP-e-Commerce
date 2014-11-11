<?php

class Sputnik_View_Browser_Grid extends WP_List_Table {
	public $id = 'grid';

	protected $connect_error = false;

	public function __construct() {
		parent::__construct(array(
			'screen' => 'sputnik-browser'
		));
	}

	public function ajax_user_can() {
		return current_user_can('install_plugins');
	}

	public function old_prepare_items() {
		$this->parent->prepare_items();
	}

	public function prepare_items() {
		require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );

		global $tabs, $tab, $paged, $type, $term;

		wp_reset_vars( array( 'tab' ) );

		$paged = $this->get_pagenum();

		// These are the tabs which are shown on the page
		$tabs = $this->parent->get_tabs();

		$nonmenu_tabs = array( 'account' ); //Valid actions to perform which do not have a Menu item.

		$tabs = apply_filters( 'install_plugins_tabs', $tabs );
		$nonmenu_tabs = apply_filters( 'install_plugins_nonmenu_tabs', $nonmenu_tabs );

		// If a non-valid menu tab has been selected, and its not a non-menu action.
		if ( empty( $tab ) || ( !isset( $tabs[ $tab ] ) && !in_array( $tab, (array) $nonmenu_tabs ) ) )
			$tab = key( $tabs );

		$args = array();

		$data = $this->parent->get_data($tab);
		$this->items = $data['items'];

		$this->set_pagination_args( array(
			'total_items' => $data['pages'],
			'per_page' => 30,
		) );
	}

	public function no_items() {
		$this->parent->no_items();
	}

	public function get_views() {
		global $tabs, $tab;

		$display_tabs = array();
		foreach ( (array) $tabs as $action => $text ) {
			$class = ( $action == $tab ) ? ' class="current"' : '';
			$href = $this->parent->get_view_url($action);
			$display_tabs['sputnik-browse-' . $action] = "<a href='$href'$class>$text</a>";
		}

		return $display_tabs;
	}

	public function view_switcher() {
		$modes = array(
			'list' => __('List View', 'wpsc'),
			'grid' => __('Grid View', 'wpsc')
		);
		$current_mode = $this->parent->view_type;
?>
		<input type="hidden" name="mode" value="<?php echo esc_attr( $current_mode ); ?>" />
		<div class="view-switch">
<?php
			foreach ( $modes as $mode => $title ) {
				$class = ( $current_mode == $mode ) ? 'class="current"' : '';
				echo "<a href='" . esc_url( add_query_arg( 'view', $mode, $_SERVER['REQUEST_URI'] ) ) . "' $class><img id='view-switch-$mode' src='" . esc_url( includes_url( 'images/blank.gif' ) ) . "' width='20' height='20' title='$title' alt='$title' /></a>\n";
			}
		?>
		</div>
<?php
	}

	public function pagination( $which ) {
		global $mode;

		parent::pagination( $which );

		if ( 'top' == $which )
			$this->view_switcher();
	}

	public function display() {
		if ($this->connect_error) {
?>
	<div class="connect-error">
		<h2>Whoops!</h2>
		<p>We don't appear to be able to connect to the WPEconomy server right now.
			Try again later!</p>
	</div>
<?php
			return;
		}

		$this->display_grid();
	}

	protected function display_grid() {
		extract( $this->_args );

		$this->display_tablenav( 'top' );
?>
	<div class="grid-view">
<?php
		$this->display_rows_or_placeholder();
?>
	</div>
<?php
		$this->display_tablenav( 'bottom' );
	}

	public function display_tablenav( $which ) {
		global $tab;

		if ($tab === 'account')
			return;

		$account = Sputnik::get_account();
		if ( 'top' ==  $which ) { ?>
			<div class="tablenav top">
				<div class="alignright account">
					<?php printf(__('Logged in as %s', 'wpsc'), '<a href="' . menu_page_url( 'sputnik-account', false ) . '" class="account-link">' . $account->name . '</a>') ?>
<?php
			if ($tab === 'search') {
?>
					<?php Sputnik_Admin::search_form(); ?>
<?php
			}
?>
				</div>
<?php
			$this->pagination($which);
?>
				<img src="<?php echo esc_url( wpsc_get_ajax_spinner() ); ?>" class="ajax-loading list-ajax-loading" alt="" />
				<br class="clear" />
			</div>
		<?php } else { ?>
			<div class="tablenav bottom">
				<?php $this->pagination($which); ?>
				<img src="<?php echo esc_url( wpsc_get_ajax_spinner() ); ?>" class="ajax-loading list-ajax-loading" alt="" />
				<br class="clear" />
			</div>
		<?php
		}
	}

	public function get_table_classes() {
		extract( $this->_args );

		return array( 'plugin-install', 'widefat', $plural );
	}

	public function get_columns() {
		return array(
			'name'        => _x( 'Name', 'plugin name', 'wpsc' ),
			'version'     => __( 'Version', 'wpsc' ),
			'price'       => __( 'Action', 'wpsc' ),
			'rating'      => __( 'Rating', 'wpsc' ),
			'description' => __( 'Description', 'wpsc' ),
		);
	}

	public function display_rows() {
		$plugins_allowedtags = array(
			'a' => array( 'href' => array(),'title' => array(), 'target' => array() ),
			'abbr' => array( 'title' => array() ),'acronym' => array( 'title' => array() ),
			'code' => array(), 'pre' => array(), 'em' => array(),'strong' => array(),
			'ul' => array(), 'ol' => array(), 'li' => array(), 'p' => array(), 'br' => array()
		);

		list( $columns, $hidden ) = $this->get_column_info();

		$style = array();
		foreach ( $columns as $column_name => $column_display_name ) {
			$style[ $column_name ] = in_array( $column_name, $hidden ) ? 'style="display:none;"' : '';
		}

		foreach ( (array) $this->items as $plugin ) {

			$plugin->name = wp_kses( $plugin->name, $plugins_allowedtags );
			//Limit description to 400char, and remove any HTML.
			$plugin->description = strip_tags( $plugin->description );
			if ( strlen( $plugin->description ) > 400 )
				$plugin->description = mb_substr( $plugin->description, 0, 400 ) . '&#8230;';
			//remove any trailing entities
			$plugin->description = preg_replace( '/&[^;\s]{0,6}$/', '', $plugin->description );
			//strip leading/trailing & multiple consecutive lines
			$plugin->description = trim( $plugin->description );
			$plugin->description = preg_replace( "|(\r?\n)+|", "\n", $plugin->description );
			//\n => <br>
			$plugin->description = nl2br( $plugin->description );
			$plugin->version = wp_kses( $plugin->version, $plugins_allowedtags );
			$plugin->price = sprintf('$%.2f', $plugin->price);
			if ($plugin->price === '$0.00') {
				$plugin->price = _x('Free', 'plugin price', 'wpsc');
			}

			if (!empty($plugin->author))
				$plugin->author = ' <cite>' . sprintf( __( 'By %s', 'wpsc' ), $plugin->author ) . '.</cite>';

			$plugin->author = wp_kses( $plugin->author, $plugins_allowedtags );

			$this->display_row($plugin, $style);
		}
	}

	protected function display_row($plugin, $style) {
		$name = strip_tags( $plugin->name );
		$action_links = array();
		$action_links[] = '<a href="' . Sputnik_Admin::build_url(array('info' => $plugin->slug, 'TB_iframe' => true))
							. '" class="thickbox button info" title="' .
							esc_attr( sprintf( __( 'More information about %s', 'wpsc' ), $name ) ) . '">' . __( 'Details', 'wpsc' ) . '</a>';

		$purchase_link = $plugin->price;

		if ($plugin->slug === '__add_new') {
			$status = 'addown';
			$name = $plugin->name;
			$action_links = array();
			$action_links[] = '<a href="http://developer.renku.me/" class="thickbox button info">' . __( 'Documentation', 'wpsc' ) . '</a>';
			$purchase_link = '<a class="button-primary addown status" href="' . Sputnik::SITE_BASE . '/plugins/add/">' . esc_html__('Add Now', 'wpsc') . '</a>';
		}
		elseif ( current_user_can( 'install_plugins' ) || current_user_can( 'update_plugins' ) ) {
			$status = Sputnik_Admin::install_status( $plugin );

			switch ( $status['status'] ) {
				case 'purchase':
					if ( $status['url'] ) {
						$purchase_link = '<a id="' . $plugin->slug .'" class="button-primary buy status" href="' . $status['url'] . '" title="'
							. esc_attr(sprintf(__( 'Buy %s', 'wpsc'), $name)) . '">' . __('Buy Now', 'wpsc') . '</a>';
					}
					break;
				case 'install':
					if ( $status['url'] ) {
						$status['url'] = add_query_arg(array('TB_iframe' => true, 'width' => 800, 'height' => 600 ), $status['url']);
						$purchase_link = '<a class="button install status" href="' . $status['url'] . '" title="'
							. esc_attr(sprintf(__( 'Install %s', 'wpsc'), $name)) . '">' . __('Install', 'wpsc') . '</a>';
					}
					else {
						$purchase_link = '<span class="status" title="' . esc_attr__('Cannot auto-install, report this as a bug', 'wpsc') . '">'
							. __('Install', 'wpsc') . '</span>';
					}
					break;
				case 'update_available':
					if ( $status['url'] ) {
						$status['url'] = add_query_arg(array('TB_iframe' => true, 'width' => 800, 'height' => 600), $status['url']);
						$purchase_link = '<a class="button install" href="' . $status['url'] . '" title="'
							. esc_attr(sprintf(__( 'Update to version %s', 'wpsc'), $status['version'])) . '">' . __('Update', 'wpsc') . '</a>';
					}
					else {
						$purchase_link = '<span class="status" title="' . esc_attr__('Cannot auto-install, report this as a bug', 'wpsc') . '">'
							. __('Update', 'wpsc') . '</span>';
					}
					break;
				case 'latest_installed':
				case 'newer_installed':
					$purchase_link = '<span class="status" title="' . esc_attr__('This plugin is already installed and is up to date', 'wpsc') . ' ">'
						. __('Installed', 'wpsc') . '</span>';
					break;
			}
		}

		$action_links = apply_filters( 'sputnik_install_grid_action_links', $action_links, $plugin );

		$thumb = false;
		if (isset($plugin->thumb) && $plugin->thumb !== false) {
			$thumb = $plugin->thumb;
		}
?>
	<div>
		<div class="sputnik-plugin<?php if ( ! empty( $plugin->thumb ) ) echo ' has-thumb'; ?>">
			<div class="sputnik-card">
				<h4><?php echo $name ?><span class="price"><?php echo $plugin->price ?></span></h4>

				<?php
					if ( ! empty( $thumb ) ) :
				?>
				<div class="sputnik-plugin-thumb">
					<img src="<?php echo esc_url( $thumb ) ?>" alt="<?php echo esc_attr( $name ) ?> Thumbnail">
				</div>
				<?php
					endif;
				?>
				<div class="sputnik-plugin-details">
					<p><?php echo $plugin->description; ?></p>
					<?php if ( isset( $plugin->rating ) && isset( $plugin->rating->count ) ): ?>
						<div class="footer" style="display:none">
							<div class="star-holder" title="<?php printf( _n( '(based on %s rating)', '(based on %s ratings)', $plugin->rating->count, 'wpsc' ), number_format_i18n( $plugin->rating->count ) ) ?>">
								<div class="star star-rating" style="width: <?php echo (int) (20 * $plugin->rating->average) ?>px"></div>
								<?php
									$star_url = admin_url( 'images/stars.png?v=20110615' );
								?>
								<div class="star star5"><img src="<?php echo $star_url; ?>" alt="" /></div>
								<div class="star star4"><img src="<?php echo $star_url; ?>" alt="" /></div>
								<div class="star star3"><img src="<?php echo $star_url; ?>" alt="" /></div>
								<div class="star star2"><img src="<?php echo $star_url; ?>" alt="" /></div>
								<div class="star star1"><img src="<?php echo $star_url; ?>" alt="" /></div>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
			<div class="sputnik-plugin-actions">
				<?php if ( !empty( $action_links ) ) echo implode( ' ', $action_links ); ?>
				<?php echo $purchase_link; ?>
			</div>
		</div>
	</div>
<?php
	}
}