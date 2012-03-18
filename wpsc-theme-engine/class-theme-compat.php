<?php

class WPSC_Theme_Engine_Compat
{
	private $type;

	public function __construct() {
		add_filter( "wpsc_locate_compat_template_single", array( $this, '_filter_locate_compat_template_single' ) );

		add_filter( 'wpsc_theme_compat_reset_globals_archive', array( $this, '_filter_reset_globals_archive' ) );
	}

	public function _filter_locate_compat_template_single( $template ) {
		return array( 'single.php', 'page.php' );
	}

	public function _filter_reset_globals_archive() {
		return array(
			'ID'           => 0,
			'post_title'   => _x( 'Questions', 'archive title', 'wpsc' ),
			'post_author'  => 0,
			'post_date'    => 0,
			'post_content' => '',
			'post_type'    => 'wpsc_question',
			'post_status'  => 'publish',
			'is_archive'   => true
		);
	}

	public function activate( $type ) {
		global $wpsc_query;

		$this->type = $type;

		// replace the content, making sure this is the last filter that runs in 'the_content',
		// thereby escape all the sanitization that WordPress did
		add_filter( 'the_content', array( $this, '_filter_replace_the_content' ), 9999 );
		add_filter( 'wpsc_replace_the_content_archive', array( $this, '_filter_replace_the_content_archive' ) );
	}

	public function _filter_replace_the_content_archive( $content ) {
		$post_type_object = get_queried_object();
		$post_type = str_replace( 'wpsc_', '', $post_type_object->name );

		ob_start();
		wpsc_get_template_part( "archive-{$post_type}", 'list' );
		return ob_get_clean();
	}

	public function _filter_replace_the_content( $content ) {
		remove_filter( 'the_content', array( $this, 'replace_the_content' ), 9999 );

		$before = apply_filters( 'wpsc_replace_the_content_before', '<div class="%s">', $this->type, $content );
		$after  = apply_filters( 'wpsc_replace_the_content_after' , '</div>'          , $this->type, $content );

		$before = sprintf( $before, 'wpsc-replaced-content' );

		$content = apply_filters( "wpsc_replace_the_content_{$this->type}", $content );

		return $content;
	}

	public function reset_globals() {
		global $wp_query;

		$args = apply_filters( "wpsc_theme_compat_reset_globals_{$this->type}", array() );

		// Default for current post
		if ( isset( $wp_query->post ) ) {
			$defaults = array(
				'ID'              => get_the_ID(),
				'post_title'      => get_the_title(),
				'post_author'     => get_the_author_meta('ID'),
				'post_date'       => get_the_date(),
				'post_content'    => get_the_content(),
				'post_type'       => get_post_type(),
				'post_status'     => get_post_status(),
				'post_name'       => ! empty( $wp_query->post->post_name ) ? $wp_query->post->post_name : '',
				'comment_status'  => comments_open(),
				'is_404'          => false,
				'is_page'         => false,
				'is_single'       => false,
				'is_archive'      => false,
				'is_tax'          => false,
			);

		// Empty defaults
		} else {
			$defaults = array(
				'ID'              => 0,
				'post_title'      => '',
				'post_author'     => 0,
				'post_date'       => 0,
				'post_content'    => '',
				'post_type'       => 'page',
				'post_status'     => 'publish',
				'post_name'       => '',
				'comment_status'  => 'closed',
				'is_404'          => false,
				'is_page'         => false,
				'is_single'       => false,
				'is_archive'      => false,
				'is_tax'          => false,
			);
		}

		$args = array_merge( $defaults, $args );

		// Clear out the post related globals
		unset( $wp_query->posts );
		unset( $wp_query->post  );
		unset( $GLOBALS['post'] );

		// Setup the dummy post object
		$wp_query->post->ID             = $args['ID'];
		$wp_query->post->post_title     = $args['post_title'];
		$wp_query->post->post_author    = $args['post_author'];
		$wp_query->post->post_date      = $args['post_date'];
		$wp_query->post->post_content   = $args['post_content'];
		$wp_query->post->post_type      = $args['post_type'];
		$wp_query->post->post_status    = $args['post_status'];
		$wp_query->post->post_name      = $args['post_name'];
		$wp_query->post->comment_status = $args['comment_status'];

		$wp_query->posts[0] = $GLOBALS['post'] = $wp_query->post;

		// Prevent comments form from appearing
		$wp_query->post_count = 1;
		$wp_query->is_404     = $args['is_404'];
		$wp_query->is_page    = $args['is_page'];
		$wp_query->is_single  = $args['is_single'];
		$wp_query->is_archive = $args['is_archive'];
		$wp_query->is_tax     = $args['is_tax'];

	}

	public function locate_template() {
		$templates = apply_filters( "wpsc_locate_compat_template_{$this->type}", array( 'page.php' ), $this->type );
		return wpsc_locate_template( $templates );
	}
}