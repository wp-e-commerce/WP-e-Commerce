<?php

class Sputnik_List_Install extends WP_List_Table {
	protected $view = 'grid';
	protected $connect_error = false;

	public function ajax_user_can() {
		return current_user_can('install_plugins');
	}

	public function prepare_items() {
		require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );

		global $tabs, $tab, $paged, $type, $term;

		wp_reset_vars( array( 'tab' ) );

		$paged = $this->get_pagenum();

		// These are the tabs which are shown on the page
		$tabs = array();
		$tabs['dashboard'] = __( 'Search', 'wpsc' );

		if ( Sputnik::account_is_linked() ) {
			$tabs['purchased'] = __( 'Purchased Plugins', 'wpsc' );
		} elseif ( $tab == 'purchased' ) {
			wp_redirect( Sputnik_Admin::build_url() );
			exit;
		}

		if ( 'search' == $tab )
			$tabs['search']	= __( 'Search Results', 'wpsc' );
		$tabs['featured'] = _x( 'Featured', 'Plugin Installer', 'wpsc' );
		$tabs['popular']  = _x( 'Popular', 'Plugin Installer', 'wpsc' );
		$tabs['new']      = _x( 'Newest', 'Plugin Installer', 'wpsc' );
		$tabs['updated']  = _x( 'Recently Updated', 'Plugin Installer', 'wpsc' );
		$tabs['price']    = _x( 'Lowest Priced', 'Plugin Installer', 'wpsc' );

		$nonmenu_tabs = array( 'account' ); //Valid actions to perform which do not have a Menu item.

		$tabs = apply_filters( 'install_plugins_tabs', $tabs );
		$nonmenu_tabs = apply_filters( 'install_plugins_nonmenu_tabs', $nonmenu_tabs );

		// If a non-valid menu tab has been selected, and its not a non-menu action.
		if ( empty( $tab ) || ( !isset( $tabs[ $tab ] ) && !in_array( $tab, (array) $nonmenu_tabs ) ) )
			$tab = key( $tabs );

		$args = array();

		try {
			switch ( $tab ) {
				case 'purchased':
					$api = Sputnik_API::get_purchased();
					break;

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

		$total_items = isset( $api['headers']['x-pagecount'] ) ? $api['headers']['x-pagecount'] : -1;

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page' => 30,
		) );
	}

	public function no_items() {
		global $tab;

		echo '<p>';
		if ( $tab == 'purchased' )
			printf( __( "You haven't purchased any add-ons yet. <a href='%s'>Browse our add-on collection.</a>", 'wpsc' ), Sputnik_Admin::build_url() );
		else
			_e( 'No plugins match your request.', 'wpsc' );
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
			$href = Sputnik_Admin::build_url($bits);
			$display_tabs['plugin-install-'.$action] = "<a href='$href'$class>$text</a>";
		}

		return $display_tabs;
	}

	public function display() {
		if ($this->connect_error) {
?>
	<div class="connect-error">
		<h2>Whoops!</h2>
		<p>We don't appear to be able to connect to the WPEConomy server right now.
			Try again later!</p>
	</div>
<?php
			return;
		}
		switch ($this->view) {
			case 'list':
				parent::display();
				break;
			default:
				$this->display_grid();
				break;
		}
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

		if ( ! Sputnik::account_is_linked() )
			return;

		$account = Sputnik::get_account();
		if ( 'top' ==  $which ) { ?>
			<div class="tablenav top">
				<div class="alignright actions">
<?php
			if ( in_array( $tab, array( 'dashboard', 'search' ) ) ) {
?>
					<?php Sputnik_Admin::search_form(); ?>
<?php
			}

			switch ($this->view) {
				case 'list':
					$view = 'grid';
					$name = __('Grid', 'wpsc' );
					break;
				case 'grid':
					$view = 'list';
					$name = __('List', 'wpsc' );
					break;
			}
?>
					<!--<a href="<?php echo add_query_arg('view', $view) ?>" class="view-as-<?php echo $view; ?> button"><?php echo $name ?></a>-->
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
			$plugin->title = wp_kses( $plugin->name, $plugins_allowedtags );
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
				$plugin->price = _x('Free', 'plugin price', 'wpsc' );
			}

			$plugin->author = $plugin->author;
			if (!empty($plugin->author))
				$plugin->author = ' <cite>' . sprintf( __( 'By %s', 'wpsc' ), $plugin->author ) . '.</cite>';

			$plugin->author = wp_kses( $plugin->author, $plugins_allowedtags );

			switch ($this->view) {
				case 'list':
					self::display_row($plugin, $style);
					break;
				default:
					self::display_as_grid($plugin, $style);
					break;
			}
		}
	}

	protected static function display_row($plugin, $style) {
		$name = strip_tags( $plugin->name . ' ' . $plugin->version );
		$action_links = array();
		$action_links[] = '<a href="' . Sputnik_Admin::build_url(array('info' => $plugin->slug, 'TB_iframe' => true))
							. '" class="thickbox info" title="' .
							esc_attr( sprintf( __( 'More information about %s', 'wpsc' ), $name ) ) . '">' . __( 'Details' ) . '</a>';

		$purchase_link = $plugin->price;

		if ( current_user_can( 'install_plugins' ) || current_user_can( 'update_plugins' ) ) {
			$status = Sputnik_Admin::install_status( $plugin );

			switch ( $status['status'] ) {
				case 'purchase':
					if ( $status['url'] ) {
						$purchase_link = '<a id="' . $plugin->slug . '" class="button-primary buy" href="' . $status['url'] . '" title="'
							. esc_attr(sprintf(__( 'Buy %s', 'wpsc' ), $name)) . '">' . sprintf(__('<span>%s</span> Buy Now</a>', 'wpsc' ), $plugin->price);
					}
					break;
				case 'install':
					if ( $status['url'] ) {
						$status['url'] = add_query_arg(array('TB_iframe' => true, 'width' => 700, 'height' => 550), $status['url']);
						$purchase_link = '<a class="button install" href="' . $status['url'] . '" title="'
							. esc_attr(sprintf(__( 'Install %s', 'wpsc' ), $name)) . '">' . __('Install', 'wpsc' ) . '</a>';
					}
					else {
						$purchase_link = '<span title="' . esc_attr__('Cannot auto-install, report this as a bug', 'wpsc' ) . '">'
							. __('Install', 'wpsc' ) . '</span>';
					}
					break;
				case 'update_available':
					if ( $status['url'] ) {
						$status['url'] = add_query_arg(array('TB_iframe' => true, 'width' => 700, 'height' => 550), $status['url']);
						$purchase_link = '<a class="button install" href="' . $status['url'] . '" title="'
							. esc_attr(sprintf(__( 'Update to version %s', 'wpsc' ), $status['version'])) . '">' . __('Update', 'wpsc' ) . '</a>';
					}
					else {
						$purchase_link = '<span title="' . esc_attr__('Cannot auto-install, report this as a bug', 'wpsc' ) . '">'
							. __('Update', 'wpsc' ) . '</span>';
					}
					break;
				case 'latest_installed':
				case 'newer_installed':
					$purchase_link = '<span title="' . esc_attr__('This plugin is already installed and is up to date', 'wpsc' ) . ' ">'
						. __('Installed', 'wpsc' ) . '</span>';
					break;
			}
		}

		$action_links = apply_filters( 'sputnik_install_row_action_links', $action_links, $plugin );
?>
		<tr>
			<td class="name column-name"<?php echo $style['name']; ?>><strong><?php echo $plugin->title; ?></strong>
				<div class="action-links"><?php if ( !empty( $action_links ) ) echo implode( ' | ', $action_links ); ?></div>
			</td>
			<td class="vers column-version"<?php echo $style['version']; ?>><?php echo $plugin->version; ?></td>
			<td class="vers column-price"<?php echo $style['price']; ?>><?php echo $purchase_link; ?></td>
			<td style="display:none" class="vers column-rating"<?php echo $style['rating']; ?>>
				<div class="star-holder" title="<?php printf( _n( '(based on %s rating)', '(based on %s ratings)', $plugin->rating->count, 'wpsc' ), number_format_i18n( $plugin->rating->count ) ) ?>">
					<div class="star star-rating" style="width: <?php echo (int) (20 * $plugin->rating->average) ?>px"></div>
					<?php
						$color = get_user_option('admin_color');
						if ( empty($color) || 'fresh' == $color )
							$star_url = admin_url( 'images/stars.png?v=20110615' ); // 'Fresh' Gray star for list tables
						else
							$star_url = admin_url( 'images/stars.png?v=20110615' ); // 'Classic' Blue star
					?>
					<div class="star star5"><img src="<?php echo $star_url; ?>" alt="" /></div>
					<div class="star star4"><img src="<?php echo $star_url; ?>" alt="" /></div>
					<div class="star star3"><img src="<?php echo $star_url; ?>" alt="" /></div>
					<div class="star star2"><img src="<?php echo $star_url; ?>" alt="" /></div>
					<div class="star star1"><img src="<?php echo $star_url; ?>" alt="" /></div>
				</div>
			</td>
			<td class="desc column-description"<?php echo $style['description']; ?>><?php echo $plugin->description, $plugin->author; ?></td>
		</tr>
<?php
	}

	protected static function display_as_grid($plugin, $style) {

		$name = strip_tags( $plugin->name . ' ' . $plugin->version );
		$action_links = array();
		$action_links[] = '<a href="' . Sputnik_Admin::build_url(array('info' => $plugin->slug, 'TB_iframe' => true))
							. '" class="thickbox button info" title="' .
							esc_attr( sprintf( __( 'More information about %s', 'wpsc' ), $name ) ) . '">' . __( 'Details', 'wpsc' ) . '</a>';

		$purchase_link = $plugin->price;

		if ( current_user_can( 'install_plugins' ) || current_user_can( 'update_plugins' ) ) {
			$status = Sputnik_Admin::install_status( $plugin );

			switch ( $status['status'] ) {
				case 'purchase':
					if ( $status['url'] ) {
						$purchase_link = '<a id="' . $plugin->slug . '" class="button-primary buy status" href="' . $status['url'] . '" title="'
							. esc_attr(sprintf(__( 'Buy %s', 'wpsc' ), $name)) . '">' . __('Buy Now', 'wpsc' ) . '</a>';
					}
					break;
				case 'install':
					if ( $status['url'] ) {
						$status['url'] = add_query_arg(array('TB_iframe' => true, 'width' => 700, 'height' => 550), $status['url']);
						$purchase_link = '<a class="button install status" href="' . $status['url'] . '" title="'
							. esc_attr(sprintf(__( 'Install %s', 'wpsc' ), $name)) . '">' . __('Install', 'wpsc' ) . '</a>';
					}
					else {
						$purchase_link = '<span class="status" title="' . esc_attr__('Cannot auto-install, report this as a bug', 'wpsc' ) . '">'
							. __('Install', 'wpsc' ) . '</span>';
					}
					break;
				case 'update_available':
					if ( $status['url'] ) {
						$status['url'] = add_query_arg(array('TB_iframe' => true, 'width' => 700, 'height' => 550), $status['url']);
						$purchase_link = '<a class="button install" href="' . $status['url'] . '" title="'
							. esc_attr(sprintf(__( 'Update to version %s', 'wpsc' ), $status['version'])) . '">' . __('Update', 'wpsc' ) . '</a>';
					}
					else {
						$purchase_link = '<span class="status" title="' . esc_attr__('Cannot auto-install, report this as a bug', 'wpsc' ) . '">'
							. __('Update', 'wpsc' ) . '</span>';
					}
					break;
				case 'latest_installed':
				case 'newer_installed':
					$purchase_link = '<span class="status" title="' . esc_attr__('This plugin is already installed and is up to date', 'wpsc' ) . ' ">'
						. __('Installed', 'wpsc' ) . '</span>';
					break;
			}
		}

		$action_links = apply_filters( 'sputnik_install_grid_action_links', $action_links, $plugin );

		$thumb = false;
		if ( empty( $plugin->thumb ) )
			$thumb = WPSC_CORE_THEME_URL . '/wpsc-images/noimage.png';
		else
			$thumb = $plugin->thumb;
?>
	<div>
		<div class="sputnik-plugin<?php if ( ! empty( $plugin->thumb ) ) echo ' has-thumb'; ?>">
			<div class="sputnik-card">
<?php
				if ( $thumb !== false ):
?>
				<div class="sputnik-plugin-thumb">
					<img src="<?php echo esc_url($thumb) ?>" alt="<?php echo esc_attr($name) ?> Thumbnail">
				</div>
<?php
				endif;
?>
				<div class="sputnik-plugin-details">
					<h4><?php echo $name ?></h4>
					<span class="price"><?php echo $plugin->price ?></span>
					<p><?php echo $plugin->description ?></p>
					<?php if ( isset( $plugin->rating ) && isset( $plugin->rating->count ) ): ?>
						<div class="footer" style="display:none">
							<div class="star-holder" title="<?php printf( _n( '(based on %s rating)', '(based on %s ratings)', $plugin->rating->count, 'wpsc' ), number_format_i18n( $plugin->rating->count ) ) ?>">
								<div class="star star-rating" style="width: <?php echo (int) (20 * $plugin->rating->average) ?>px"></div>
								<?php
									$color = get_user_option('admin_color');
									if ( empty($color) || 'fresh' == $color )
										$star_url = admin_url( 'images/stars.png?v=20110615' ); // 'Fresh' Gray star for list tables
									else
										$star_url = admin_url( 'images/stars.png?v=20110615' ); // 'Classic' Blue star
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
		</div>
		<div class="sputnik-plugin-actions">
			<?php if ( !empty( $action_links ) ) echo implode( ' ', $action_links ); ?>
			<?php echo $purchase_link; ?>
		</div>
	</div>
<?php
	}
}