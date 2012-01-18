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
			if ( is_callable( array( $this, $process_callback ) ) )
				$this->$process_callback();
		}

		call_user_func_array( array( $this, $this->callback ), $this->args );
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

	public function set_message( $message, $type = 'message' ) {
		$this->messages[$type][] = $message;
	}

	public function get_messages() {
		return $this->messages;
	public function get_callback() {
		return $this->callback;
	}

	public function get_slug() {
		return $this->slug;
	}

	public function main() {
	}
}

function wpsc_get_front_end_page( $page, $callback = 'main' ) {
	return WPSC_Front_End_Page::get_page( $page, $callback );
}