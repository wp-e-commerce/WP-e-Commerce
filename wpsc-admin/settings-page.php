<?php
/**
 * WP e-Commerce Settings Page API.
 *
 * Third-party plugin / theme developers can add their own tabs to WPEC store settings page.
 *
 * Let's say you want to create a tab for your plugin called "Recommendation System", for example.
 * You first need to register the tab ID and title like this:
 *
 * <code>
 * function my_plugin_settings_tabs( $settings_page ) {
 * 	$settings_page->register_tab( 'recommendation_system', 'Recommendation System' );
 * }
 * add_action( 'wpsc_load_settings_tab_class', 'my_plugin_settings_tabs', 10, 1 );
 * </code>
 *
 * Note that you need to hook into 'wpsc_load_settings_tab_class' to do this.
 *
 * The next step is to create a class for your tab which inherits from the base 'WPSC_Settings_Tab'.
 * The name of the class needs to follow this convention: all the words have to be capitalized and
 * separated with an underscore, and prefixed with 'WPSC_Settings_Tab_'.
 *
 * In our example, because we registered our tab ID as 'recommendation_system', the class name should
 * be 'WPSC_Settings_Tab_Recommendation_System'.
 *
 * <code>
 * class WPSC_Settings_Tab_Recommendation_System extends WPSC_Settings_Tab
 * {
 * 	public function display() {
 * 		echo '<h3>Recommendation System Settings</h3>';
 * 		// output your tab content here
 * 	}
 * }
 * </code>
 *
 * All tab has to implement a method `display()` which outputs the HTML content for the tab.
 * You don't need to output the <form> element because it will be done for you.
 *
 * When outputting your form fields for the tab, name the fields 'wpsc_options[$your_option_name]'
 * so that they will automatically get saved to the database when the user submits the form. E.g.:
 *
 * <code>
 * <input type="text" value="something" name="wpsc_options[some_option]" />
 * </code>
 *
 * If you need to handle the form submission yourself, create a method in your tab class called
 * 'callback_submit_options()'. Then process your submitted fields there.
 *
 * <code>
 * class WPSC_Settings_Tab_Recommendation_System extends WPSC_Settings_Tab
 * {
 * 	// ...
 * 	public function callback_submit_options() {
 * 		if ( isset( $_POST['my_option'] ) )
 * 			update_option( 'my_option', $_POST['my_option'] );
 * 	}
 * 	// ...
 * }
 * </code>
 *
 * @package wp-e-commerce
 * @subpackage settings-api
 */

/**
 * Abstract class for setting tabs
 *
 * @abstract
 * @since 3.8.8
 * @package wp-e-commerce
 * @subpackage settings-api
 */
abstract class WPSC_Settings_Tab
{
	/**
	 * Display the content of the tab. This function has to be overridden.
	 *
	 * @since 3.8.8
	 * @abstract
	 * @access public
	 */
	abstract public function display();

	/**
	 * Whether to display the update message when the options are submitted.
	 *
	 * @since 3.8.8.1
	 * @access private
	 */
	private $is_update_message_displayed = true;

	/**
	 * Whether to display the "Save Changes" button.
	 *
	 * @since 3.8.8.1
	 * @access private
	 */
	private $is_submit_button_displayed= true;

	/**
	 * Constructor
	 *
	 * @since 3.8.8
	 * @access public
	 */
	public function __construct() {}

	/**
	 * Make sure the update message will be displayed
	 *
	 * @since 3.8.8.1
	 * @access protected
	 */
	protected function display_update_message() {
		$this->is_update_message_displayed = true;
	}

	/**
	 * Make sure the update message will not be displayed
	 *
	 * @since 3.8.8.1
	 * @access protected
	 */
	protected function hide_update_message() {
		$this->is_update_message_displayed = false;
	}

	/**
	 * Query whether the update message is to be displayed or not.
	 *
	 * @since 3.8.8.1
	 * @access public
	 */
	public function is_update_message_displayed() {
		return $this->is_update_message_displayed;
	}

	/**
	 * Hide the default "Save Changes" button
	 *
	 * @since  3.8.8.1
	 * @access protected
	 */
	protected function hide_submit_button() {
		$this->is_submit_button_displayed = false;
	}

	/**
	 * Show the default "Save Changes" button
	 *
	 * @since 3.8.8.1
	 * @access protected
	 */
	protected function display_submit_button() {
		$this->is_submit_button_displayed = true;
	}

	/**
	 * Return whether the default "Save Changes" button is to be displayed.
	 *
	 * @since 3.8.8.1
	 * @access public
	 */
	public function is_submit_button_displayed() {
		return $this->is_submit_button_displayed;
	}
}

/**
 * Settings Page class. Singleton pattern.
 *
 * @since 3.8.8
 * @package wp-e-commerce
 * @subpackage settings-api
 * @final
 */
final class WPSC_Settings_Page
{
	/**
	 * @staticvar object The active object instance
	 * @since 3.8.8
	 * @access private
	 */
	private static $instance;

	/**
	 * @staticvar array An array of default tabs containing pairs of id => title
	 * @since 3.8.8
	 * @access private
	 */
	private static $default_tabs;

	/**
	 * Initialize default tabs and add necessary action hooks.
	 *
	 * @since 3.8.8
	 *
	 * @uses add_action() Attaches to wpsc_register_settings_tabs hook
	 * @uses add_action() Attaches to wpsc_load_settings_tab_class hook
	 *
	 * @see wpsc_load_settings_page()
	 *
	 * @access public
	 * @static
	 */
	public static function init() {
		self::$default_tabs = array(
			'general'      => _x( 'General'     , 'General settings tab in Settings->Store page'     , 'wpsc' ),
			'presentation' => _x( 'Presentation', 'Presentation settings tab in Settings->Store page', 'wpsc' ),
			'admin'        => _x( 'Admin'       , 'Admin settings tab in Settings->Store page'       , 'wpsc' ),
			'taxes'        => _x( 'Taxes'       , 'Taxes settings tab in Settings->Store page'       , 'wpsc' ),
			'shipping'     => _x( 'Shipping'    , 'Shipping settings tab in Settings->Store page'    , 'wpsc' ),
			'gateway'      => _x( 'Payments'    , 'Payments settings tab in Settings->Store page'    , 'wpsc' ),
			'checkout'     => _x( 'Checkout'    , 'Checkout settings tab in Settings->Store page'    , 'wpsc' ),
			'marketing'    => _x( 'Marketing'   , 'Marketing settings tab in Settings->Store page'   , 'wpsc' ),
			'import'       => _x( 'Import'      , 'Import settings tab in Settings->Store page'      , 'wpsc' )
		);

		add_action( 'wpsc_register_settings_tabs' , array( 'WPSC_Settings_Page', 'register_default_tabs'  ), 1 );
		add_action( 'wpsc_load_settings_tab_class', array( 'WPSC_Settings_Page', 'load_default_tab_class' ), 1 );
	}

	/**
	 * Get active object instance
	 *
	 * @since 3.8.8
	 *
	 * @access public
	 * @static
	 * @return object
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new WPSC_Settings_Page();
		}

		return self::$instance;
	}

	/**
	 * Automatically load tab classes inside wpsc-admin/includes/settings-tabs.
	 *
	 * @since 3.8.8
	 *
	 * @see WPSC_Settings_Page::init()
	 *
	 * @uses WPSC_Settings_Page::get_current_tab_id() Gets current tab ID
	 *
	 * @access public
	 * @param  object $page_instance The WPSC_Settings_Page instance
	 * @static
	 */
	public static function load_default_tab_class( $page_instance ) {
		$current_tab_id = $page_instance->get_current_tab_id();
		if ( array_key_exists( $current_tab_id, self::$default_tabs ) ) {
			require_once( 'includes/settings-tabs/' . $current_tab_id . '.php' );
		}
	}

	/**
	 * Register the default tabs' ids and titles.
	 *
	 * @since 3.8.8
	 *
	 * @see WPSC_Settings_Page::init()
	 *
	 * @uses WPSC_Settings_Page::register_tab() Registers default tabs' idds and titles.
	 *
	 * @access public
	 * @param  object $page_instance The WPSC_Settings_Page instance
	 * @static
	 */
	public static function register_default_tabs( $page_instance ) {
		foreach ( self::$default_tabs as $id => $title ) {
			$page_instance->register_tab( $id, $title );
		}
	}

	/**
	 * Current tab ID
	 * @since 3.8.8
	 * @access private
	 * @var string
	 */
	private $current_tab_id;

	/**
	 * Current tab object
	 * @since 3.8.8
	 * @access private
	 * @var object
	 */
	private $current_tab;

	/**
	 * An array containing registered tabs
	 * @since 3.8.8
	 * @access private
	 * @var array
	 */
	private $tabs;

	/**
	 * Constructor
	 *
	 * @since 3.8.8
	 *
	 * @uses do_action()   Calls wpsc_register_settings_tabs hook.
	 * @uses apply_filters Calls wpsc_settings_tabs hook.
	 * @uses WPSC_Settings_Page::set_current_tab() Set current tab to the specified ID
	 *
	 * @access public
	 * @param string $tab_id Optional. If specified then the current tab will be set to this ID.
	 */
	public function __construct( $tab_id = null ) {
		do_action( 'wpsc_register_settings_tabs', $this );
		$this->tabs = apply_filters( 'wpsc_settings_tabs', $this->tabs );
		$this->set_current_tab( $tab_id );
	}

	/**
	 * Returns the current tab object
	 *
	 * @since 3.8.8
	 *
	 * @uses do_action()         Calls wpsc_load_settings_tab_class hook.
	 * @uses WPSC_Settings_Tab() constructing a new settings tab object
	 *
	 * @access public
	 * @return object WPSC_Settings_Tab object
	 */
	public function get_current_tab() {
		if ( ! $this->current_tab ) {
			do_action( 'wpsc_load_settings_tab_class', $this );
			$class_name = ucwords( str_replace( array( '-', '_' ), ' ', $this->current_tab_id ) );
			$class_name = str_replace( ' ', '_', $class_name );
			$class_name = 'WPSC_Settings_Tab_' . $class_name;
			if ( class_exists( $class_name ) ) {
				$reflection = new ReflectionClass( $class_name );
				$this->current_tab = $reflection->newInstance();
			}
		}

		return $this->current_tab;
	}

	/**
	 * Get current tab ID
	 * @since  3.8.8
	 * @access public
	 * @return string
	 */
	public function get_current_tab_id() {
		return $this->current_tab_id;
	}

	/**
	 * Set current tab to the specified tab ID.
	 *
	 * @since 3.8.8
	 *
	 * @uses check_admin_referer() Prevent CSRF
	 * @uses WPSC_Settings_Page::get_current_tab()        Initializes the current tab object.
	 * @uses WPSC_Settings_Page::save_options()           Saves the submitted options to the database.
	 * @uses WPSC_Settings_Tab::callback_submit_options() If this method exists in the tab object, it will be called after WPSC_Settings_Page::save_options().
	 *
	 * @access public
	 * @param string $tab_id Optional. The Tab ID. If this is not specified, the $_GET['tab'] variable will be used. If that variable also does not exists, the first tab will be used.
	 */
	public function set_current_tab( $tab_id = null ) {
		if ( ! $tab_id ) {
			if ( isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $this->tabs ) )
				$this->current_tab_id = $_GET['tab'];
			else
				$this->current_tab_id = array_shift( array_keys( $this->tabs ) );
		} else {
			$this->current_tab_id = $tab_id;
		}

		$this->current_tab = $this->get_current_tab();

		if ( isset( $_REQUEST['wpsc_admin_action'] ) && ( $_REQUEST['wpsc_admin_action'] == 'submit_options' ) ) {
			check_admin_referer( 'update-options', 'wpsc-update-options' );
			$this->save_options();
			$query_args = array();
			if ( is_callable( array( $this->current_tab, 'callback_submit_options' ) ) ) {
				$additional_query_args = $this->current_tab->callback_submit_options();
				if ( ! empty( $additional_query_args ) )
					$query_args += $additional_query_args;
			}
			if ( $this->current_tab->is_update_message_displayed() ) {
				if ( ! count( get_settings_errors() ) )
					add_settings_error( 'wpsc-settings', 'settings_updated', __( 'Settings saved.' ), 'updated' );
				set_transient( 'settings_errors', get_settings_errors(), 30 );
				$query_args['settings-updated'] = true;
			}
			wp_redirect( add_query_arg( $query_args ) );
			exit;
		}
	}

	/**
	 * Register a tab's ID and title
	 *
	 * @since 3.8.8
	 *
	 * @access public
	 * @param  string $id    Tab ID.
	 * @param  string $title Tab title.
	 */
	public function register_tab( $id, $title ) {
		$this->tabs[$id] = $title;
	}

	/**
	 * Get an array containing tabs' IDs and titles
	 *
	 * @since 3.8.8
	 *
	 * @access public
	 * @return array
	 */
	public function get_tabs() {
		return $this->tabs;
	}

	/**
	 * Get the HTML class of a tab.
	 * @since 3.8.8
	 * @param  string $id Tab ID
	 * @return string
	 */
	private function tab_class( $id ) {
		$class = 'nav-tab';
		if ( $id == $this->current_tab_id )
			$class .= ' nav-tab-active';
		return $class;
	}

	/**
	 * Get the form's submit (action) url.
	 * @since 3.8.8
	 * @access private
	 * @return string
	 */
	private function submit_url() {
		$location = add_query_arg( 'tab', $this->current_tab_id );
		return $location;
	}

	/**
	 * Output HTML of tab navigation.
	 * @since 3.8.8
	 * @access public
	 * @uses esc_html Prevents xss
	 */
	public function output_tabs() {
		?>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $this->tabs as $id => $title ): ?>
					<a data-tab-id="<?php echo esc_attr( $id ); ?>" class="<?php echo $this->tab_class( $id ); ?>" href="<?php echo esc_attr( '?page=wpsc-settings&tab=' . $id ); ?>"><?php echo esc_html( $this->tabs[$id] ); ?></a>
				<?php endforeach ?>
			</h2>
		<?php
	}

	/**
	 * Display the current tab.
	 * @since 3.8.8
	 * @uses do_action() Calls wpsc_{$current_tab_id}_settings_page hook.
	 * @uses WPSC_Settings_Tab::display() Displays the tab.
	 * @access public
	 */
	public function display_current_tab() {
		?>
			<div id="options_<?php echo esc_attr( $this->current_tab_id ); ?>" class="tab-content">
				<?php
					if ( is_callable( array( $this->current_tab, 'display' ) ) ) {
						$this->current_tab->display();
					}
				?>

				<?php do_action('wpsc_' . $this->current_tab_id . '_settings_page'); ?>
				<div class="submit">
					<input type='hidden' name='wpsc_admin_action' value='submit_options' />
					<?php wp_nonce_field( 'update-options', 'wpsc-update-options' ); ?>
					<?php if ( $this->current_tab->is_submit_button_displayed() ): ?>
						<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'wpsc' ); ?>" name="updateoption" />
					<?php endif ?>
				</div>
			</div>
		<?php
	}

	/**
	 * Display the settings page.
	 * @since 3.8.8
	 * @uses esc_html_e()     Sanitize HTML
	 * @uses esc_attr()       Sanitize HTML attributes
	 * @uses wp_nonce_field() Prevent CSRF
	 * @uses WPSC_Settings_Page::output_tabs()         Display tab navigation.
	 * @uses WPSC_Settings_Page::display_current_tab() Display current tab.
	 * @access public
	 */
	public function display() {
		?>
			<div id="wpsc_options" class="wrap">
				<div id="icon_card" class="icon32"></div>
				<h2 id="wpsc-settings-page-title">
					<?php esc_html_e( 'Store Settings', 'wpsc' ); ?>
					<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-feedback" title="" alt="" />
				</h2>
				<?php $this->output_tabs(); ?>
				<div id='wpsc_options_page'>
					<form method='post' action='<?php echo esc_attr( $this->submit_url() ); ?>' enctype='multipart/form-data' id='wpsc-settings-form'>
						<?php $this->display_current_tab(); ?>
					</form>
				</div>
			</div>
		<?php
	}

	/**
	 * Save submitted options to the database.
	 * @since 3.8.8
	 * @uses check_admin_referer() Prevents CSRF.
	 * @uses update_option() Saves options to the database.
	 * @uses wpdb::query() Queries the database.
	 * @uses wpdb::get_col() Queries the database.
	 * @access public
	 */
	private function save_options( $selected='' ) {
		global $wpdb, $wpsc_gateways;
		$updated = 0;

		//This is to change the Overall target market selection
		check_admin_referer( 'update-options', 'wpsc-update-options' );

		//Should be refactored along with the Marketing tab
		if ( isset( $_POST['change-settings'] ) ) {

			if ( isset( $_POST['wpsc_also_bought'] ) && $_POST['wpsc_also_bought'] == 'on' )
				update_option( 'wpsc_also_bought', 1 );
			else
				update_option( 'wpsc_also_bought', 0 );

			if ( isset( $_POST['display_find_us'] ) && $_POST['display_find_us'] == 'on' )
				update_option( 'display_find_us', 1 );
			else
				update_option( 'display_find_us', 0 );

			if ( isset( $_POST['wpsc_share_this'] ) && $_POST['wpsc_share_this'] == 'on' )
				update_option( 'wpsc_share_this', 1 );
			else
				update_option( 'wpsc_share_this', 0 );

			if ( isset( $_POST['wpsc_ga_disable_tracking'] ) && $_POST['wpsc_ga_disable_tracking'] == '1' )
				update_option( 'wpsc_ga_disable_tracking', 1 );
			else
				update_option( 'wpsc_ga_disable_tracking', 0 );

			if ( isset( $_POST['wpsc_ga_currently_tracking'] ) && $_POST['wpsc_ga_currently_tracking'] == '1' )
				update_option( 'wpsc_ga_currently_tracking', 1 );
			else
				update_option( 'wpsc_ga_currently_tracking', 0 );

			if ( isset( $_POST['wpsc_ga_advanced'] ) && $_POST['wpsc_ga_advanced'] == '1' ) {
				update_option( 'wpsc_ga_advanced', 1 );
				update_option( 'wpsc_ga_currently_tracking', 1 );
			} else  {
				update_option( 'wpsc_ga_advanced', 0 );
			}

			if ( isset( $_POST['wpsc_ga_tracking_id'] ) && ! empty( $_POST['wpsc_ga_tracking_id'] ) )
				update_option( 'wpsc_ga_tracking_id', esc_attr( $_POST['wpsc_ga_tracking_id'] ) );
			else
				update_option( 'wpsc_ga_tracking_id', '' );

		}

		if (empty($_POST['countrylist2']) && !empty($_POST['wpsc_options']['currency_sign_location']))
			$selected = 'none';

		if ( !isset( $_POST['countrylist2'] ) )
			$_POST['countrylist2'] = '';
		if ( !isset( $_POST['country_id'] ) )
			$_POST['country_id'] = '';
		if ( !isset( $_POST['country_tax'] ) )
			$_POST['country_tax'] = '';

		if ( $_POST['countrylist2'] != null || !empty($selected) ) {
			$AllSelected = false;
			if ( $selected == 'all' ) {
				$wpdb->query( "UPDATE `" . WPSC_TABLE_CURRENCY_LIST . "` SET visible = '1'" );
				$AllSelected = true;
			}
			if ( $selected == 'none' ) {
				$wpdb->query( "UPDATE `" . WPSC_TABLE_CURRENCY_LIST . "` SET visible = '0'" );
				$AllSelected = true;
			}
			if ( $AllSelected != true ) {
				$countrylist = $wpdb->get_col( "SELECT id FROM `" . WPSC_TABLE_CURRENCY_LIST . "` ORDER BY country ASC " );
				//find the countries not selected
				$unselectedCountries = array_diff( $countrylist, $_POST['countrylist2'] );
				foreach ( $unselectedCountries as $unselected ) {
					$wpdb->update(
						WPSC_TABLE_CURRENCY_LIST,
						array(
						    'visible' => 0
						),
						array(
						    'id' => $unselected
						),
						'%d',
						'%d'
					    );
				}

				//find the countries that are selected
				$selectedCountries = array_intersect( $countrylist, $_POST['countrylist2'] );
				foreach ( $selectedCountries as $selected ) {
					$wpdb->update(
						WPSC_TABLE_CURRENCY_LIST,
						array(
						    'visible' => 1
						),
						array(
						    'id' => $selected
						),
						'%d',
						'%d'
					    );
				}
			}
		}
		$previous_currency = get_option( 'currency_type' );

		//To update options
		if ( isset( $_POST['wpsc_options'] ) ) {
			$_POST['wpsc_options'] = stripslashes_deep( $_POST['wpsc_options'] );
			// make sure stock keeping time is a number
			if ( isset( $_POST['wpsc_options']['wpsc_stock_keeping_time'] ) ) {
				$skt =& $_POST['wpsc_options']['wpsc_stock_keeping_time']; // I hate repeating myself
				$skt = (float) $skt;
				if ( $skt <= 0 || ( $skt < 1 && $_POST['wpsc_options']['wpsc_stock_keeping_interval'] == 'hour' ) ) {
					unset( $_POST['wpsc_options']['wpsc_stock_keeping_time'] );
					unset( $_POST['wpsc_options']['wpsc_stock_keeping_interval'] );
				}
			}

			foreach ( $_POST['wpsc_options'] as $key => $value ) {
				if ( $value != get_option( $key ) ) {
					update_option( $key, $value );
					$updated++;

				}
			}
		}

		if ( $previous_currency != get_option( 'currency_type' ) ) {
			$currency_code = $wpdb->get_var( "SELECT `code` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id` IN ('" . absint( get_option( 'currency_type' ) ) . "')" );

			$selected_gateways = get_option( 'custom_gateway_options' );
			$already_changed = array( );
			foreach ( $selected_gateways as $selected_gateway ) {
				if ( isset( $wpsc_gateways[$selected_gateway]['supported_currencies'] ) ) {
					if ( in_array( $currency_code, $wpsc_gateways[$selected_gateway]['supported_currencies']['currency_list'] ) ) {

						$option_name = $wpsc_gateways[$selected_gateway]['supported_currencies']['option_name'];

						if ( ! in_array( $option_name, $already_changed ) ) {
							update_option( $option_name, $currency_code );
							$already_changed[] = $option_name;
						}
					}
				}
			}
		}

		foreach ( $GLOBALS['wpsc_shipping_modules'] as $shipping ) {
			if ( is_object( $shipping ) )
				$shipping->submit_form();
		}

		//This is for submitting shipping details to the shipping module
		if ( !isset( $_POST['update_gateways'] ) )
			$_POST['update_gateways'] = '';

		if ( !isset( $_POST['custom_shipping_options'] ) )
			$_POST['custom_shipping_options'] = null;

		if ( $_POST['update_gateways'] == 'true' ) {

			update_option( 'custom_shipping_options', $_POST['custom_shipping_options'] );

			$shipadd = 0;
			foreach ( $GLOBALS['wpsc_shipping_modules'] as $shipping ) {
				foreach ( (array)$_POST['custom_shipping_options'] as $shippingoption ) {
					if ( $shipping->internal_name == $shippingoption ) {
						$shipadd++;
					}
				}
			}
		}
	}
}

WPSC_Settings_Page::init();