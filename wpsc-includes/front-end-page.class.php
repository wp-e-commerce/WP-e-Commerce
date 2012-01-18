<?php

class WPSC_Front_End_Page
{
	private static $instances = array();

	public static function get_page( $page, $callback = 'main' ) {
		if ( ! array_key_exists( $page, self::$instances ) ) {
			$file_name = WPSC_FILE_PATH . '/wpsc-includes/front-end-pages/' . $page . '.php';

			$suffix = str_replace( array( '-', '_' ), ' ', $page );
			$suffix = ucwords( $suffix );
			$suffix = str_replace( ' ', '_', $suffix );
			$class_name = 'WPSC_Front_End_Page_' . $suffix;
			if ( file_exists( $file_name ) )
				require_once( $file_name );

			if ( ! class_exists( $class_name ) )
				$class_name = 'WPSC_Front_End_Page';

			$reflection = new ReflectionClass( $class_name );

			self::$instances[$page] = $reflection->newInstance( $callback );
			self::$instances[$page]->set_uri( $page . '/' . $callback );
		}

		return self::$instances[$page];
	}

	protected $template_name     = 'wpsc-page';
	protected $messages          = array();
	protected $validation_errors = array();
	protected $slug              = '';
	protected $callback          = '';
	protected $uri               = '';

	public function __construct( $callback = 'main' ) {
		global $wp_query;
		$wp_query->is_home = false;

		$args = explode( '/', ltrim( $callback, '/' ) );
		$callback = array_shift( $args );
		$this->slug = $callback;

		$callback = str_replace( array( ' ', '-' ), '_', $callback );

		if ( ! is_callable( array( $this, $callback ) ) ) {
			$callback = $this->slug = 'main';
		}

		$this->callback = $callback;
		$this->args = $args;

		add_action( 'wp', array( $this, 'action_set_200_header' ), 1 );
		add_filter( 'template_include', array( $this, 'filter_template_include' ) );

		if ( array_key_exists( 'action', $_REQUEST ) ) {
			$process_callback = 'process_' . $_REQUEST['action'];
			if ( is_callable( array( $this, $process_callback ) ) ) {
				call_user_func_array( array( $this, $process_callback ), $this->args );
				return;
			}
		}

		call_user_func_array( array( $this, $this->callback ), $this->args );
	}

	private function set_uri( $uri ) {
		$this->uri = $uri;
	}

	public function get_uri() {
		return $this->uri;
	}

	public function action_set_200_header() {
		global $wp_query;
		$wp_query->is_home      = false;
		$wp_query->is_404       = false;
		$wp_query->wpsc_is_page = true;

		status_header( 200 );
	}

	public function filter_template_include( $template ) {
		$templates = array(
			"{$this->template_name}-{$this->slug}.php",
			"{$this->template_name}.php",
		);

		$located = wpsc_locate_template( $templates );

		if ( ! empty( $located ) )
			$template = $located;

		return $template;
	}

	public function set_message( $message, $type = 'success', $context = 'main' ) {
		if ( ! isset( $this->messages[$type] ) )
			$this->messages[$type] = array();
		if ( ! isset( $this->messages[$type][$context] ) )
			$this->messages[$type][$context] = array();
		$this->messages[$type][$context][] = $message;
	}

	public function get_messages( $types = 'all', $context = 'main' ) {
		if ( $types == 'all' )
			$types = array_keys( $this->messages );

		if ( ! is_array( $types ) )
			$types = explode( ',', $types );

		$messages = array();
		foreach ( $types as $type ) {
			if ( isset( $this->messages[$type] ) && isset( $this->messages[$type][$context] ) )
				$messages = array_merge( $messages, array( $type => $this->messages[$type][$context] ) );
		}

		return $messages;
	}

	public function has_messages( $types = 'all', $context = 'main' ) {
		if ( $types == 'all' )
			$types = array_keys( $this->messages );

		if ( ! is_array( $types ) )
			$types = explode( ',', $types );

		foreach ( $types as $type ) {
			if ( ! empty( $this->messages[$type] ) && ! empty( $this->messages[$type][$context] ) )
				return true;
		}

		return false;
	}

	public function get_callback() {
		return $this->callback;
	}

	public function get_slug() {
		return $this->slug;
	}

	public function main() {
	}
}

class WPSC_Front_End_Page_SSL extends WPSC_Front_End_Page
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

function wpsc_get_front_end_page( $page, $callback = 'main' ) {
	return WPSC_Front_End_Page::get_page( $page, $callback );
}