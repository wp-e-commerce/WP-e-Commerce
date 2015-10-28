<?php

class WPSC_Controller {

	public $title                = '';
	public $wp_filter            = array();
	public $merged_filters       = array();
	public $main_query;
	private $needs_authorization = false;
	private $needs_compat        = true;
	protected $view              = '';
	protected $message_collection;

	public function __get( $name ) {
		// read-only properties
		if ( in_array( $name, array(
			'message_collection', 'main_query', 'needs_compat', 'view' )
		) ) {
			return $this->$name;
		}

		return null;
	}

	public function __construct() {
		require_once( WPSC_TE_V2_CLASSES_PATH . '/message-collection.php' );

		add_filter( 'template_include' , array( $this, '_filter_template_router' ) );
		add_action( 'wpsc_router_init', array( $this, 'force_ssl'               ) );

		$this->message_collection = WPSC_Message_Collection::get_instance();
	}

	protected function verify_nonce( $action ) {
		if ( ! wp_verify_nonce( $_POST['_wp_nonce'], $action ) ) {
			$this->message_collection->add(
				__( 'Your form submission could not be processed by our system because the page has been left idle for too long. Please try submitting it again.', 'wp-e-commerce' ),
				'error'
			);

			return false;
		}

		return true;
	}

	public function force_ssl() {
		if ( ! is_ssl()                           &&
			'1' == get_option( 'wpsc_force_ssl' ) &&
			( wpsc_is_cart() || wpsc_is_checkout() )
		 ) {

		 	$url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		 	if ( isset( $_REQUEST['_wp_nonce'] ) ) {
				$url = add_query_arg( '_wp_nonce', $_REQUEST['_wp_nonce'], $url );
		 	}

			wp_safe_redirect( $url );
			exit;
		}
	}

	public function _filter_template_router() {

		$located = $this->get_native_template();

		if ( ! $located ) {
			$current_controller = _wpsc_get_current_controller_name();
			$located = wpsc_locate_view_wrappers( $current_controller . '-wrapper.php' );
		}

		if ( $located ) {
			$this->needs_compat = false;
		}

		if ( ! $located ) {
			$located = locate_template( 'page.php' );
		}

		if ( $this->needs_compat ) {
			$this->prepare_compat();
		}

		return $located;
	}

	protected function get_native_template() {
	}

	private function prepare_compat() {
		add_filter( 'comments_array', array( $this, '_filter_comments_array' ), 10, 2 );
		$this->reset_globals();
		add_filter( 'the_content', array( $this, '_action_replace_content' ), 0 );
	}

	public function _filter_comments_array( $comments, $id ) {
		if ( is_main_query() && ! $id ) {
			return array();
		}

		return $comments;
	}

	/**
	 * Reset the global variables before the template is included so that our
	 * controller is displayed correctly within page.php template
	 *
	 * The purpose is to fool WordPress themes into thinking the WPEC controller
	 * is a proper WordPress page.
	 *
	 * @access private
	 * @since  0.1
	 */
	private function reset_globals() {
		global $wp_query, $wp_the_query;

		// default values for the global $post object
		$reset_post = array(
			'ID'              => 0,
			'post_title'      => $this->title,
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
			'filter'          => 'raw',
		);

		// default values for the global $wp_query object
		$reset_wp_query = array(
			'post_count'      => 1,
			'is_404'          => false,
			'is_page'         => false,
			'is_single'       => false,
			'is_home'         => false,
			'is_front_page'   => false,
			'comment_count'   => 0,
		);

		// in case a single product is being displayed, use the corresponding
		// post attributes
		if ( isset( $wp_query->post ) && is_singular() ) {
			$post_id = $wp_query->post->ID;
			$reset_post = array_merge( $reset_post, array(
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

		$reset_post['post_content'] = $this->get_replaced_content();

		// store the $wp_query away before butchering it, this will be restored
		// later
		$this->main_query = unserialize( serialize( $wp_query ) );

		// Clear out the post related globals
		$GLOBALS['post'] = $wp_query->post = new WP_Post( (object) $reset_post );
		$wp_query->posts = array( $wp_query->post );

		// Reset $wp_query flags
		foreach ( $reset_wp_query as $flag => $value ) {
			$wp_query->$flag = $value;
		}
	}

	public function _action_replace_content( $content ) {
		$priority = has_filter( 'the_content', 'wpautop' );

		if ( in_the_loop() && is_main_query() ) {

			if ( $priority !== false ) {
				remove_filter( 'the_content', 'wpautop' );
			}

		} elseif ( $priority === false ) {
			add_filter( 'the_content', 'wpautop' );
		}

		return $content;
	}

	public function get_replaced_content() {
		global $wp_query;

		$current_controller = _wpsc_get_current_controller_name();

		$before = apply_filters(
			'wpsc_replace_the_content_before',
			'<div class="%s">',
			$current_controller
		);

		$after  = apply_filters(
			'wpsc_replace_the_content_after' ,
			'</div>',
			$current_controller
		);

		$before = sprintf( $before, 'wpsc-page wpsc-page-' . $current_controller );
		ob_start();
		wpsc_get_template_part( $this->view );
		$content = ob_get_clean();

		return $before . $content . $after;
	}

	public function needs_authorization( $val = null ) {
		if ( is_null( $val ) ) {
			return $this->needs_authorization;
		}

		$this->needs_authorization = $val;
	}

	/**
	 * @todo  Investigate if this is necessary.  It appears it is unused.
	 * @return [type] [description]
	 */
	private function restore_main_query() {
		$GLOBALS['wp_query'] = $this->main_query;
		wp_reset_postdata();
	}
}