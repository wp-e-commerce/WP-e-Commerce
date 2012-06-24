<?php

class WPSC_Theme_Engine_Compat
{
	private $type;

	public function __construct() {
		add_filter( 'wpsc_theme_compat_reset_globals_archive' , array( $this, '_filter_reset_globals_archive'   ) );
		add_filter( 'wpsc_theme_compat_reset_globals_cart'    , array( $this, '_filter_reset_globals_cart'      ) );
		add_filter( 'wpsc_theme_compat_reset_globals_taxonomy', array( $this, '_filter_reset_globals_taxonomy'  ) );
		add_filter( 'wpsc_theme_compat_reset_globals_login'   , array( $this, '_filter_reset_globals_login'     ) );
		add_filter( 'wpsc_theme_compat_reset_globals_register', array( $this, '_filter_reset_globals_register'  ) );
		add_filter( 'wpsc_theme_compat_reset_globals_password_reminder', array( $this, '_filter_reset_globals_password_reminder' ) );

		add_filter( 'wpsc_replace_the_content_archive' , array( $this, '_filter_replace_the_content_archive'  ) );
		add_filter( 'wpsc_replace_the_content_cart'    , array( $this, '_filter_replace_the_content_cart'     ) );
		add_filter( 'wpsc_replace_the_content_single'  , array( $this, '_filter_replace_the_content_single'   ) );
		add_filter( 'wpsc_replace_the_content_taxonomy', array( $this, '_filter_replace_the_content_taxonomy' ) );
		add_filter( 'wpsc_replace_the_content_login'   , array( $this, '_filter_replace_the_content_login'    ) );
		add_filter( 'wpsc_replace_the_content_register', array( $this, '_filter_replace_the_content_register' ) );
		add_filter( 'wpsc_replace_the_content_password_reminder', array( $this, '_filter_replace_the_content_password_reminder' ) );
	}

	public function _filter_reset_globals_taxonomy() {
		return array(
			'post' => array(
				'post_title' => wpsc_get_category_archive_title(),
			),
		);
	}

	public function _filter_reset_globals_archive() {
		return array(
			'post' => array(
				'post_title'   => wpsc_get_product_catalog_title(),
				'post_type'    => 'wpsc-product',
				'post_status'  => 'publish',
			),
		);
	}

	public function _filter_reset_globals_cart() {
		return array(
			'post' => array(
				'post_title' => wpsc_get_cart_title(),
			),
		);
	}

	public function _filter_reset_globals_login() {
		return array(
			'post' => array(
				'post_title' => wpsc_get_login_title(),
			),
		);
	}

	public function _filter_reset_globals_register() {
		return array(
			'post' => array(
				'post_title' => wpsc_get_register_title(),
			),
		);
	}

	public function _filter_reset_globals_password_reminder() {
		return array(
			'post' => array(
				'post_title' => wpsc_get_password_reminder_title(),
			),
		);
	}

	public function _filter_replace_the_content_archive( $content ) {
		$post_type_object = get_queried_object();
		$post_type = str_replace( array( 'wpsc_', 'wpsc-' ), '', $post_type_object->name );

		ob_start();
		wpsc_get_template_part( "archive-{$post_type}", 'list' );
		return ob_get_clean();
	}

	public function _filter_replace_the_content_cart( $content ) {
		ob_start();
		wpsc_get_template_part( 'cart' );
		return ob_get_clean();
	}

	public function _filter_replace_the_content_single( $content ) {
		ob_start();
		wpsc_get_template_part( 'product', 'single' );
		return ob_get_clean();
	}

	public function _filter_replace_the_content_taxonomy( $content ) {
		$current_term = get_queried_object();

		ob_start();
		wpsc_get_template_part( 'taxonomy', $current_term->taxonomy );
		return ob_get_clean();
	}

	public function _filter_replace_the_content_login( $content ) {
		ob_start();
		wpsc_get_template_part( 'login' );
		return ob_get_clean();
	}

	public function _filter_replace_the_content_register( $content ) {
		ob_start();
		wpsc_get_template_part( 'register' );
		return ob_get_clean();
	}

	public function _filter_replace_the_content_password_reminder( $content ) {
		global $wpsc_page_instance;
		$template_part = 'password-reminder';
		$callback = $wpsc_page_instance->get_callback();

		ob_start();
		wpsc_get_template_part( $template_part, $callback );
		return ob_get_clean();
	}

	public function _filter_replace_the_content( $content ) {
		remove_filter( 'the_content', array( $this, '_filter_replace_the_content' ), 9999 );

		$before = apply_filters( 'wpsc_replace_the_content_before', '<div class="%s">', $this->type, $content );
		$after  = apply_filters( 'wpsc_replace_the_content_after' , '</div>'          , $this->type, $content );

		$before = sprintf( $before, 'wpsc-replaced-content' );

		$content = apply_filters( "wpsc_replace_the_content_{$this->type}", $content );

		return $before . $content . $after;
	}

	public function activate( $type ) {
		$this->type = $type;
		$this->reset_globals();

		// replace the content, making sure this is the last filter that runs in 'the_content',
		// thereby escape all the sanitization that WordPress did
		add_filter( 'the_content', array( $this, '_filter_replace_the_content' ), 9999 );
	}

	public function reset_globals() {
		global $wp_query;

		$args = apply_filters( "wpsc_theme_compat_reset_globals_{$this->type}", array( 'post' => array(), 'wp_query' => array() ) );
		if ( ! isset( $args['wp_query'] ) )
			$args['wp_query'] = array();

		if ( ! isset( $args['post'] ) )
			$args['post'] = array();

		$defaults = array(
			'post' => array(
				'ID'              => 0,
				'post_title'      => '',
				'post_author'     => 0,
				'post_date'       => 0,
				'post_content'    => '',
				'post_type'       => 'page',
				'post_status'     => 'publish',
				'post_parent'     => 0,
				'post_name'       => '',
				'ping_status'     => 'closed',
				'comment_status'  => 'closed',
				'comment_count'   => 0,
			),
			'wp_query' => array(
				'post_count'      => 1,
				'is_404'          => false,
				'is_page'         => false,
				'is_single'       => false,
				'is_archive'      => false,
				'is_tax'          => false,
			),
		);

		// Default for current post
		if ( isset( $wp_query->post ) ) {
			$post_id = $wp_query->post->ID;
			$defaults['post'] = array_merge( $defaults['post'], array(
				'ID'              => $post_id,
				'post_title'      => get_post_field( 'post_title'  , $post_id ),
				'post_author'     => get_post_field( 'post_author' , $post_id ) ,
				'post_date'       => get_post_field( 'post_date'   , $post_id ),
				'post_content'    => get_post_field( 'post_content', $post_id ),
				'post_type'       => get_post_field( 'post_type'   , $post_id ),
				'post_status'     => get_post_field( 'post_status' , $post_id ),
				'post_name'       => get_post_field( 'post_name'   , $post_id ),
				'comment_status'  => comments_open(),
				)
			);
		}

		$args['post'] = array_merge( $defaults['post'], $args['post'] );
		$args['wp_query'] = array_merge( $defaults['wp_query'], $args['wp_query'] );

		// Clear out the post related globals
		$GLOBALS['post'] = $wp_query->post = (object) $args['post'];
		$wp_query->posts = array( $wp_query->post );

		// Prevent comments form from appearing
		foreach ( $args['wp_query'] as $flag => $value ) {
			$wp_query->$flag = $value;
		}
	}

	public function locate_template() {
		$templates = apply_filters( "wpsc_locate_compat_template_{$this->type}", array( 'page.php' ), $this->type );
		return wpsc_locate_template( $templates );
	}
}