<?php

class Sputnik_List_Account extends Sputnik_List_Install {
	protected $view = 'grid';
	protected $connect_error = false;

	public function prepare_items() {
		require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );

		global $tabs, $tab, $paged, $type, $term;

		wp_reset_vars( array( 'tab' ) );

		$paged = $this->get_pagenum();

		// These are the tabs which are shown on the page
		$tabs = array();
		$tabs['purchased'] = __( 'Purchased Plugins', 'wp-e-commerce' );
		$tabs['yours'] = __( 'Your Plugins', 'wp-e-commerce' );

		$nonmenu_tabs = array( ); //Valid actions to perform which do not have a Menu item.

		$tabs = apply_filters( 'install_plugins_tabs', $tabs );
		$nonmenu_tabs = apply_filters( 'install_plugins_nonmenu_tabs', $nonmenu_tabs );

		// If a non-valid menu tab has been selected, and its not a non-menu action.
		if ( empty( $tab ) || ( !isset( $tabs[ $tab ] ) && !in_array( $tab, (array) $nonmenu_tabs ) ) )
			$tab = key( $tabs );

		$args = array();

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

		if (!empty($_REQUEST['view'])) {
			switch ($_REQUEST['view']) {
				case 'grid':
				case 'list':
					$this->view = $_REQUEST['view'];
					break;
				default:
					$this->view = 'grid';
					break;
			}
		} else {
			$this->view = 'grid';
		}

		$this->items = $api['body'];

		if (isset($api['headers']['x-pagecount'])) {
			$this->set_pagination_args( array(
				'total_items' => $api['headers']['x-pagecount'],
				'per_page' => 30,
			) );
		}
	}

	public function no_items() {
		global $tab;
		echo '<p>';
		if ($tab === 'yours') {
			_e( "You haven't created any plugins yet. Check out our <a href='http://developer.renku.me/'>developer documentation</a> to find out how!", 'wp-e-commerce' );
		}
		else {
			printf(__( "You haven't purchased any plugins yet. Why not <a href='%s'>buy some</a>?", 'wp-e-commerce' ), Sputnik_Admin::build_url());
		}
		echo '</p>';
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

	public function display_grid() {
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
				<div class="alignright actions">
<?php
			switch ($this->view) {
				case 'list':
					$view = 'grid';
					$name = __('Grid', 'wp-e-commerce' );
					break;
				case 'grid':
					$view = 'list';
					$name = __('List', 'wp-e-commerce' );
					break;
			}
?>
				<!--	<a href="<?php echo add_query_arg('view', $view) ?>" class="view-as-<?php echo $view; ?> button"><?php echo $name ?></a> -->
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

	public static function mangle_action_for_own($actions, $plugin) {
		$actions[] = sprintf('<a href="%s" class="button edit">%s</a>', sprintf(Sputnik::SITE_BASE . '/your-products/edit/%d', $plugin->product_id), _x('Edit', 'edit own product', 'wp-e-commerce' ));
		return $actions;
	}
}