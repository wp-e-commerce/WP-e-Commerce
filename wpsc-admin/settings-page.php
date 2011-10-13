<?php

abstract class WPSC_Settings_Tab
{
	abstract public function display();
}

final class WPSC_Settings_Page
{
	private static $instance;
	private static $default_tabs;

	public static function get_instance() {
		if ( ! self::$default_tabs ) {
			self::$default_tabs = array(
				'general'      => _x( 'General', 'General settings tab in Settings->Store page', 'wpsc' ),
				'presentation' => _x( 'Presentation', 'Presentation settings tab in Settings->Store page', 'wpsc' ),
				'admin'        => _x( 'Admin', 'Admin settings tab in Settings->Store page', 'wpsc' ),
				'taxes'        => _x( 'Taxes', 'Taxes settings tab in Settings->Store page', 'wpsc' ),
				'shipping'     => _x( 'Shipping', 'Shipping settings tab in Settings->Store page', 'wpsc' ),
				'gateway'      => _x( 'Payments', 'Payments settings tab in Settings->Store page', 'wpsc' ),
				'checkout'     => _x( 'Checkout', 'Checkout settings tab in Settings->Store page', 'wpsc' ),
				'marketing'    => _x( 'Marketing', 'Marketing settings tab in Settings->Store page', 'wpsc' ),
				'import'       => _x( 'Import', 'Import settings tab in Settings->Store page', 'wpsc' )
			);
		}

		if ( ! self::$instance ) {
			add_action( 'wpsc_register_settings_tabs' , array( 'WPSC_Settings_Page', 'register_default_tabs' ), 1 );
			add_action( 'wpsc_load_settings_tab_class', array( 'WPSC_Settings_Page', 'load_default_tab_class' ), 1 );
			self::$instance = new WPSC_Settings_Page();
		}

		return self::$instance;
	}

	public static function load_default_tab_class( $page_instance ) {
		$current_tab_id = $page_instance->get_current_tab_id();
		if ( array_key_exists( $current_tab_id, self::$default_tabs ) ) {
			require_once( 'includes/settings-tabs/' . $current_tab_id . '.php' );
		}
	}

	public static function register_default_tabs( $page_instance ) {
		foreach ( self::$default_tabs as $id => $title ) {
			$page_instance->register_tab( $id, $title );
		}
	}

	private $current_tab_id;
	private $current_tab;
	private $tabs;

	private function __construct() {
		do_action( 'wpsc_register_settings_tabs', $this );
		$this->tabs = apply_filters( 'wpsc_settings_tabs', $this->tabs );
		$this->set_current_tab();
	}

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

	public function get_current_tab_id() {
		return $this->current_tab_id;
	}

	public function set_current_tab() {
		if ( isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $this->tabs ) )
			$this->current_tab_id = $_GET['tab'];
		else
			$this->current_tab_id = array_shift( array_keys( $this->tabs ) );

		$this->current_tab = $this->get_current_tab();
	}

	public function register_tab( $id, $title ) {
		$this->tabs[$id] = $title;
	}

	public function get_tabs() {
		return $this->tabs;
	}

	private function tab_class( $id ) {
		$class = 'nav-tab';
		if ( $id == $this->current_tab_id )
			$class .= ' nav-tab-active';
		return $class;
	}

	private function tab_href( $id ) {
		$href = add_query_arg( array( 'tab' => $id ) );
		$href = remove_query_arg( 'isocode', $href );
		$href = wp_nonce_url( $href, 'tab-' . $id );
		return $href;
	}

	private function submit_url() {
		$location = add_query_arg( 'tab', $this->current_tab_id );
		$location = wp_nonce_url( $location, 'tab-' . $this->current_tab_id );
		return $location;
	}

	public function output_tabs() {
		?>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $this->tabs as $id => $title ): ?>
					<a class="<?php echo $this->tab_class( $id ); ?>" href="<?php echo esc_attr( $this->tab_href( $id ) ); ?>"><?php echo esc_html( $this->tabs[$id] ); ?></a>
				<?php endforeach ?>
			</h2>
		<?php
	}

	public function display() {
		?>
			<div id="wpsc_options" class="wrap">
				<div id="icon_card" class="icon32"></div>
				<?php $this->output_tabs(); ?>
				<div id='wpsc_options_page'>
					<form method='post' action='<?php echo esc_attr( $this->submit_url() ); ?>' id='cart_options' name='cart_options' class='wpsc_form_track'>
						<div id="options_<?php echo esc_attr( $this->current_tab_id ); ?>">
							<?php
								if ( is_callable( array( $this->current_tab, 'display' ) ) ) {
									$this->current_tab->display();
								}
							?>

							<?php do_action('wpsc_' . $this->current_tab_id . '_settings_page'); ?>
							<div class="submit">
								<input type='hidden' name='wpsc_admin_action' value='submit_options' />
								<?php wp_nonce_field( 'update-options', 'wpsc-update-options' ); ?>
								<input type="submit" value="<?php _e( 'Update &raquo;', 'wpsc' ); ?>" name="updateoption" />
							</div>
						</div>
					</form>
				</div>
			</div>
		<?php
	}
}