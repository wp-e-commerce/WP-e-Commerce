<?php
require_once( 'class-message-collection.php' );

class WPSC_Page
{
	private static $instances = array();

	public static function get_page( $page, $callback = 'main' ) {
		if ( ! array_key_exists( $page, self::$instances ) ) {
			$file_name = WPSC_FILE_PATH . '/wpsc-theme-engine/page-subclasses/' . $page . '.php';
			$suffix = str_replace( array( '-', '_' ), ' ', $page );
			$suffix = ucwords( $suffix );
			$suffix = str_replace( ' ', '_', $suffix );
			$class_name = 'WPSC_Page_' . $suffix;
			if ( file_exists( $file_name ) )
				require_once( $file_name );

			if ( ! class_exists( $class_name ) )
				$class_name = 'WPSC_Page';

			$reflection = new ReflectionClass( $class_name );

			self::$instances[$page] = $reflection->newInstance( $callback );
			self::$instances[$page]->set_page( $page );
			self::$instances[$page]->set_uri( $page . '/' . $callback );
		}

		return self::$instances[$page];
	}

	protected $page                       = '';
	protected $template_name              = 'wpsc-page';
	protected $validation_errors          = array();
	protected $slug                       = '';
	protected $callback                   = '';
	protected $uri                        = '';
	protected $message_collection         = null;

	public function __construct( $callback = 'main' ) {
		global $wp_query;
		$wp_query->is_home = false;

		$this->message_collection = WPSC_Message_Collection::get_instance();
		$args = explode( '/', ltrim( $callback, '/' ) );
		$callback = array_shift( $args );
		$this->slug = $callback;

		$callback = str_replace( array( ' ', '-' ), '_', $callback );

		if ( ! is_callable( array( $this, $callback ) ) ) {
			$callback = $this->slug = 'main';
		}

		$this->callback = $callback;
		$this->args = $args;

		add_action( 'wp', array( $this, '_action_set_200_header' ), 1 );
		add_filter( 'template_include', array( $this, '_filter_template_include' ) );

		if ( array_key_exists( 'action', $_REQUEST ) ) {
			$process_callback = '_callback_' . $_REQUEST['action'];
			if ( is_callable( array( $this, $process_callback ) ) ) {
				call_user_func_array( array( $this, $process_callback ), $this->args );
				return;
			}
		}

		call_user_func_array( array( $this, $this->callback ), $this->args );
	}

	public function get_uri() {
		return $this->uri;
	}

	public function get_callback() {
		return $this->callback;
	}

	public function get_slug() {
		return $this->slug;
	}

	public function main() {
	}

	private function set_uri( $uri ) {
		$this->uri = $uri;
	}

	private function set_page( $page ) {
		$this->page = $page;
	}

	public function _filter_template_include( $template ) {
		$templates = array(
			"{$this->template_name}-{$this->slug}.php",
			"{$this->template_name}.php",
		);

		$located = wpsc_locate_template( $templates );

		if ( ! empty( $located ) )
			return $located;

		$theme_engine = WPSC_Theme_Engine::get_instance();
		return $theme_engine->locate_compat_template('cart');
	}

	public function _action_set_200_header() {
		global $wp_query;
		$wp_query->is_home      = false;
		$wp_query->is_404       = false;
		$wp_query->wpsc_is_page = true;

		status_header( 200 );
	}
}

class WPSC_Page_SSL extends WPSC_Page
{
	protected $template_name;

	public function __construct( $callback = 'main', $redirect_to ) {
		// see if SSL is forced for login
		if ( force_ssl_login() && ! is_ssl() ) {
			wp_safe_redirect( $redirect_to );
			exit;
		}

		parent::__construct( $callback );
	}
}