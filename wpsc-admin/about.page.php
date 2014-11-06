<?php

/**
 * About Page Class.
 *
 * @package     WP e-Commerce
 * @subpackage  About Page
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPSC_About_Page' ) ) :

	/**
	 * Load WP e-Commerce About Page.
	 *
	 * @since  3.9
	 */
	class WPSC_About_Page {

		/**
		 * The main BuddyPress admin loader.
		 *
		 * @since  3.9
		 *
		 * @uses  WPSC_About_Page::setup_actions()  Setup the hooks and actions.
		 */
		public function __construct() {

			$this->setup_actions();

		}

		/**
		 * Set up the admin hooks, actions, and filters.
		 *
		 * @access  private
		 * @since   3.9
		 */
		private function setup_actions() {

			// Add About and Credits pages.
			add_action( 'admin_menu', array( $this, 'admin_menus' ), 5 );

			// Remove About and Credits pages from admin menu.
			add_action( 'admin_head', array( $this, 'admin_head' ), 999 );

			// Add a link to About page to the admin bar
			add_action( 'admin_bar_menu', array( $this, 'admin_bar_about_link' ), 15 );

			// Add link to plugins page
			add_filter( 'plugin_action_links', array( $this, 'modify_plugin_action_links' ), 10, 2 );
			add_filter( 'network_admin_plugin_action_links', array( $this, 'modify_plugin_action_links' ), 10, 2 );

		}

		/**
		 * Add the navigational menu elements.
		 *
		 * @since  3.9
		 *
		 * @uses  add_dashboard_page()  To add the About and Credit pages.
		 */
		public function admin_menus() {

			// Bail if user cannot moderate
			if ( ! wpsc_is_store_admin() ) {
				return;
			}

			// About
			add_dashboard_page(
				__( 'Welcome to BuddyPress',  'wpsc' ),
				__( 'Welcome to BuddyPress',  'wpsc' ),
				'manage_options',
				'wpsc-about',
				array( $this, 'about_screen' )
			);

			// Credits
			add_dashboard_page(
				__( 'Welcome to BuddyPress',  'wpsc' ),
				__( 'Welcome to BuddyPress',  'wpsc' ),
				'manage_options',
				'wpsc-credits',
				array( $this, 'credits_screen' )
			);

		}

		/**
		 * Add a link to WP e-Commerce About page to the admin bar.
		 *
		 * @since  3.9
		 *
		 * @param  WP_Admin_Bar  $wp_admin_bar  As passed to 'admin_bar_menu'.
		 */
		public function admin_bar_about_link( $wp_admin_bar ) {
			if ( is_user_logged_in() ) {
				$wp_admin_bar->add_menu( array(
					'parent' => 'wp-logo',
					'id'     => 'wpsc-about',
					'title'  => esc_html__( 'About WP e-Commerce', 'wpsc' ),
					'href'   => add_query_arg( array( 'page' => 'wpsc-about' ), admin_url( 'index.php' ) ),
				) );
			}
		}

		/**
		 * Add About link to plugins row.
		 *
		 * @since  3.9
		 *
		 * @param   array   $links  Links array in which we would prepend our link.
		 * @param   string  $file   Current plugin basename.
		 * @return  array           Processed links.
		 */
		public function modify_plugin_action_links( $links, $file ) {

			// Return normal links if not WPEC
			if ( WPSC_PLUGIN_BASENAME != $file ) {
				return $links;
			}

			// Add about link to the links array
			return array_merge( $links, array(
				'about' => '<a href="' . add_query_arg( array( 'page' => 'wpsc-about' ), admin_url( 'index.php' ) ) . '">' . esc_html__( 'About', 'wpsc' ) . '</a>'
			) );

		}

		/**
		 * Remove About and Credits pages from admin menu.
		 *
		 * @since  3.9
		 */
		public function admin_head() {

			remove_submenu_page( 'index.php', 'wpsc-about'   );
			remove_submenu_page( 'index.php', 'wpsc-credits' );

		}

		/**
		 * Output the about screen.
		 *
		 * @since  3.9
		 */
		public function about_screen() {

			?>

			<div class="wrap about-wrap">

				<?php $this->about_screen_intro(); ?>

				<?php if ( $this->is_new_install() ) : ?> 

					<div id="welcome-panel" class="welcome-panel">
						<div class="welcome-panel-content">
							<h3 style="margin:0"><?php _e( 'Getting Started with WP e-Commerce', 'wpsc' ); ?></h3>
							<div class="welcome-panel-column-container">
								<div class="welcome-panel-column">
									<h4><?php _e( 'Configure WP e-Commerce', 'wpsc' ); ?></h4>
									<ul>
										<li><?php printf( '<a href="%s" class="welcome-icon welcome-edit-page">' . __( 'Store Settings', 'wpsc' ) . '</a>', admin_url( 'options-general.php?page=wpsc-settings' ) ); ?></li>
										<li><?php printf( '<a href="%s" class="welcome-icon welcome-edit-page">' . __( 'Add-Ons', 'wpsc' ) . '</a>', admin_url( 'edit.php?post_type=wpsc-product&page=sputnik' ) ); ?></li>
									</ul>
									<a class="button button-primary button-hero" style="margin-bottom:20px;margin-top:0;" href="<?php echo esc_url( admin_url( 'options-general.php?page=wpsc-settings' ) ); ?>"><?php _e( 'Get Started', 'wpsc' ); ?></a>
								</div>
								<div class="welcome-panel-column">
									<h4><?php _e( 'Administration Tools', 'wpsc' ); ?></h4>
									<ul>
										<li><?php printf( '<a href="%s" class="welcome-icon welcome-add-page">' . __( 'Add Product', 'wpsc' ) . '</a>', admin_url( 'post-new.php?post_type=wpsc-product' ) ); ?></li>
										<li><?php printf( '<a href="%s" class="welcome-icon welcome-add-page">' . __( 'Manage Products', 'wpsc' ) . '</a>', admin_url( 'edit.php?post_type=wpsc-product' ) ); ?></li>
										<li><?php printf( '<a href="%s" class="welcome-icon welcome-add-page">' . __( 'Manage Categories', 'wpsc' ) . '</a>', admin_url( 'edit-tags.php?taxonomy=wpsc_product_category&post_type=wpsc-product' ) ); ?></li>
										<li><?php printf( '<a href="%s" class="welcome-icon welcome-add-page">' . __( 'Manage Variations', 'wpsc' ) . '</a>', admin_url( 'edit-tags.php?taxonomy=wpsc-variation&post_type=wpsc-product' ) ); ?></li>
										<li><?php printf( '<a href="%s" class="welcome-icon welcome-add-page">' . __( 'Manage Coupons', 'wpsc' ) . '</a>', admin_url( 'edit.php?post_type=wpsc-product&page=wpsc-edit-coupons' ) ); ?></li>
									</ul>
								</div>
								<div class="welcome-panel-column welcome-panel-last">
									<h4><?php _e( 'Community and Support', 'wpsc'  ); ?></h4>
									<p class="welcome-icon welcome-learn-more" style="margin-right:10px"><?php _e( 'Looking for help? Visit our <a href="#">documentation</a> pages.', 'wpsc' ) ?></p> 
									<p class="welcome-icon welcome-learn-more" style="margin-right:10px"><?php _e( 'Can&#8217;t find what you need? Stop by <a href="#">our support forums</a>.', 'wpsc' ) ?></p>
								</div>
							</div>
						</div>
					</div>

				<?php endif; ?>

				<hr />

				<div class="changelog">
					<h2 class="about-headline-callout"><?php _e( 'Example Single Column Feature', 'wpsc' ); ?></h2>
					<p><?php _e( 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco <code>example code</code> ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.', 'wpsc' ) ?></p>
					<p style="text-align: center"><img src="<?php echo esc_url( WPSC_URL . 'wpsc-admin/images/placeholder.gif' ); ?>" alt="<?php esc_attr_e( 'Demo of new feature', 'wpsc' ); ?>" style="margin-bottom: 20px"></p>
	 			</div>

				<hr />

				<div class="changelog">
					<h2 class="about-headline-callout"><?php _e( 'Example Three Column Feature', 'wpsc' ); ?></h2>

					<div class="feature-section col three-col">
						<div class="col-1">
							<h4><?php esc_html_e( 'Feature One', 'wpsc' ); ?></h4>
							<p><?php esc_html_e( 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', 'wpsc' ); ?></p>
						</div>

						<div class="col-2">
							<h4><?php esc_html_e( 'Feature Two', 'wpsc' ); ?></h4>
							<p><?php esc_html_e( 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco ut aliquip ex ea commodo consequat.', 'wpsc' ); ?></p>
						</div>

						<div class="col-3 last-feature">
							<h4><?php esc_html_e( 'Feature Three', 'wpsc' ); ?></h4>
							<p><?php _e( 'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.', 'wpsc' ); ?></p>
						</div>
					</div>
				</div>

				<hr />

				<div class="changelog">
					<h2 class="about-headline-callout"><?php esc_html_e( 'Enhancements for Plugin &amp; Theme Developers', 'wpsc' ); ?></h2>
					<div class="feature-section col two-col">
						<div class="col-1">
							<p><?php _e( 'If you&#8217re a plugin developer, or make custom themes, or want to contribute back to the WP e-Commerce project, here&#8217s what you should know about this release:', 'wpsc' ); ?></p>
							<p><?php _e( 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco <code>example code</code> ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.', 'wpsc' ); ?></p>
							<p><?php _e( 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco <code>example code</code> ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.', 'wpsc' ); ?></p>
						</div>

						<div class="col-2 last-feature">
							<p><?php esc_html_e( 'Other interesting changes:', 'wpsc' ); ?>

							<ul>
								<li><?php _e( 'Lorem ipsum dolor.', 'wpsc' ); ?></li>
								<li><?php _e( 'Lorem ipsum dolor.', 'wpsc' ); ?></li>
								<li><?php _e( 'Lorem ipsum dolor.', 'wpsc' ); ?></li>
								<li><?php _e( 'Lorem ipsum dolor.', 'wpsc' ); ?></li>
								<li><?php _e( 'Lorem ipsum dolor.', 'wpsc' ); ?></li>
								<li><?php printf( __( '<a href="%s">&hellip;and lots more!</a>', 'wpsc' ), 'http://getshopped.org/' ); ?></li>
							</ul>
						</div>
					</div>
				</div>
			<?php
		}

		/**
		 * Output the credits screen.
		 *
		 * Hardcoding this in here is fine for now.
		 * Better to leverage api.wordpress.org eventually.
		 *
		 * @since  3.9
		 */
		public function credits_screen() {

			?>

			<div class="wrap about-wrap">

				<?php $this->about_screen_intro( 'credits' ); ?>

				<p class="about-description"><?php _e( 'WP e-Commerce is created by...', 'wpsc' ); ?></p>

				<h4 class="wp-people-group"><?php _e( 'Project Leaders', 'wpsc' ); ?></h4>
				<ul class="wp-people-group " id="wp-people-group-project-leaders">
					<li class="wp-person" id="wp-person-justinsainton">
						<a href="http://profiles.wordpress.org/mufasa"><img src="http://0.gravatar.com/avatar/5ba89a2ce585864ce73cafa7e79d114c?s=60" class="gravatar" alt="John James Jacoby" /></a>
						<a class="web" href="http://profiles.wordpress.org/mufasa">Dan Milward</a>
						<span class="title"><?php _e( 'Project Lead', 'wpsc' ); ?></span>
					</li>
					<li class="wp-person" id="wp-person-justinsainton">
						<a href="http://profiles.wordpress.org/justinsainton"><img src="http://0.gravatar.com/avatar/02fbf19ad633e203e3bc571b80ca3f66?s=60" class="gravatar" alt="John James Jacoby" /></a>
						<a class="web" href="http://profiles.wordpress.org/justinsainton">Justin Sainton</a>
						<span class="title"><?php _e( 'Project Lead', 'wpsc' ); ?></span>
					</li>
					<li class="wp-person" id="wp-person-garyc40">
						<a href="http://profiles.wordpress.org/garyc40"><img src="http://0.gravatar.com/avatar/aea5ee57d1e882ad17e95c99265784d1?s=60" class="gravatar" alt="Boone B. Gorges" /></a>
						<a class="web" href="http://profiles.wordpress.org/garyc40">Gary Cao</a>
						<span class="title"><?php _e( 'Lead Developer', 'wpsc' ); ?></span>
					</li>
				</ul>

				<h4 class="wp-people-group"><?php _e( 'Core Team', 'wpsc' ); ?></h4>
				<ul class="wp-people-group " id="wp-people-group-core-team">
					<li class="wp-person" id="wp-person-justinsainton">
						<a href="http://profiles.wordpress.org/mufasa"><img src="http://0.gravatar.com/avatar/5ba89a2ce585864ce73cafa7e79d114c?s=60" class="gravatar" alt="John James Jacoby" /></a>
						<a class="web" href="http://profiles.wordpress.org/mufasa">Dan Milward</a>
						<span class="title"><?php _e( 'Lead Developer', 'wpsc' ); ?></span>
					</li>
					<li class="wp-person" id="wp-person-justinsainton">
						<a href="http://profiles.wordpress.org/justinsainton"><img src="http://0.gravatar.com/avatar/02fbf19ad633e203e3bc571b80ca3f66?s=60" class="gravatar" alt="John James Jacoby" /></a>
						<a class="web" href="http://profiles.wordpress.org/justinsainton">Justin Sainton</a>
						<span class="title"><?php _e( 'Lead Developer', 'wpsc' ); ?></span>
					</li>
					<li class="wp-person" id="wp-person-garyc40">
						<a href="http://profiles.wordpress.org/garyc40"><img src="http://0.gravatar.com/avatar/aea5ee57d1e882ad17e95c99265784d1?s=60" class="gravatar" alt="Boone B. Gorges" /></a>
						<a class="web" href="http://profiles.wordpress.org/garyc40">Gary Cao</a>
						<span class="title"><?php _e( 'Lead Developer', 'wpsc' ); ?></span>
					</li>
				</ul>

				<h4 class="wp-people-group"><?php _e( 'Recent Rockstars', 'wpsc' ); ?></h4>
				<ul class="wp-people-group " id="wp-people-group-rockstars">
					<li class="wp-person" id="wp-person-justinsainton">
						<a href="http://profiles.wordpress.org/mufasa"><img src="http://0.gravatar.com/avatar/5ba89a2ce585864ce73cafa7e79d114c?s=60" class="gravatar" alt="John James Jacoby" /></a>
						<a class="web" href="http://profiles.wordpress.org/mufasa">Dan Milward</a>
						<span class="title"><?php _e( 'Developer', 'wpsc' ); ?></span>
					</li>
					<li class="wp-person" id="wp-person-justinsainton">
						<a href="http://profiles.wordpress.org/justinsainton"><img src="http://0.gravatar.com/avatar/02fbf19ad633e203e3bc571b80ca3f66?s=60" class="gravatar" alt="John James Jacoby" /></a>
						<a class="web" href="http://profiles.wordpress.org/justinsainton">Justin Sainton</a>
						<span class="title"><?php _e( 'Developer', 'wpsc' ); ?></span>
					</li>
					<li class="wp-person" id="wp-person-garyc40">
						<a href="http://profiles.wordpress.org/garyc40"><img src="http://0.gravatar.com/avatar/aea5ee57d1e882ad17e95c99265784d1?s=60" class="gravatar" alt="Boone B. Gorges" /></a>
						<a class="web" href="http://profiles.wordpress.org/garyc40">Gary Cao</a>
						<span class="title"><?php _e( 'Developer', 'wpsc' ); ?></span>
					</li>
				</ul>

				<h4 class="wp-people-group"><?php printf( __( 'Contributors to WP e-Commerce %s', 'wpsc' ), $this->get_display_version() ); ?></h4>
				<p class="wp-credits-list">
					Abid Omar,
					<a href="https://profiles.wordpress.org/husobj/">Ben Huson</a>,
					Chrillep,
					<a href="https://profiles.wordpress.org/garyc40/">Gary Cao</a>,
					<a href="https://profiles.wordpress.org/jeffpyebrookcom/">Jeffrey Schutzman</a>,
					<a href="https://profiles.wordpress.org/justinsainton/">Justin Sainton</a>,
					<a href="https://profiles.wordpress.org/leewillis77/">Lee Willis</a>,
					<a href="https://profiles.wordpress.org/misulicus/">Misulicus</a>.
				</p>

				<h4 class="wp-people-group"><?php _e( 'External Libraries', 'wpsc' ); ?></h4>
				<p class="wp-credits-list">
					<a href="http://jacklmoore.com/colorbox">jQuery Colorbox</a>,
					<a href="http://docs.jquery.com/UI/Droppables">jQuery UI Droppable</a>,
					<a href="http://docs.jquery.com/UI/Datepicker">jQuery UI Datepicker</a>,
					<a href="https://www.codylindley.com">Thickbox</a>.
				</p>

			</div>

			<?php
		}

		/**
		 * Output the about screen intro.
		 *
		 * @since  3.9
		 */
		public function about_screen_intro( $screen = 'about' ) {

			?>

			<h1><?php printf( __( 'Welcome to WP e-Commerce %s', 'wpsc' ), $this->get_display_version() ); ?></h1>

			<div class="about-text">
				<?php if ( $this->is_new_install() ) : ?>
					<?php printf( __( 'Thank you for installing WP e-Commerce! WP e-Commerce %s ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore.', 'wpsc' ), $this->get_display_version() ); ?>
				<?php else : ?>
					<?php printf( __( 'Howdy. WP e-Commerce %s ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore.', 'wpsc' ), $this->get_display_version() ); ?>
				<?php endif; ?>
			</div>

			<div class="wpsc-badge"></div>

			<?php
			$screen_tabs = array(
				'about'   => __( 'What&#8217;s New', 'wpsc' ),
				'credits' => __( 'Credits', 'wpsc' )
			);
			?>

			<h2 class="nav-tab-wrapper">
				<?php
				foreach ( $screen_tabs as $screen_tab => $screen_tab_label ) {
					$class = $screen_tab == $screen ? ' nav-tab-active' : '';
					$url = add_query_arg( array( 'page' => 'wpsc-' . $screen_tab ), 'index.php' );
					if ( $this->is_new_install() ) {
						$url = add_query_arg( array( 'is_new_install' => 1 ), $url );
					}
					printf( '<a class="nav-tab%s" href="%s">%s</a>', $class, esc_url( admin_url( $url ) ), $screen_tab_label );
				}
				?>
			</h2>

			<?php

		}

		/**
		 * Is new install?
		 *
		 * @since  3.9
		 *
		 * @return  boolean
		 */
		private function is_new_install() {

			return isset( $_GET['is_new_install'] ) && ! empty( $_GET['is_new_install'] );

		}

		/**
		 * Get Display Version
		 *
		 * @since  3.9
		 *
		 * @return  string  Display version.
		 */
		private function get_display_version() {

			list( $display_version ) = explode( '-', WPSC_VERSION );
			return $display_version;

		}

	}

endif;
