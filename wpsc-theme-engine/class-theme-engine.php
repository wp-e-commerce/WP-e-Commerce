<?php
require_once( 'theme-engine.php'           );
require_once( 'template-tags/general.php'  );
require_once( 'template-tags/product.php'  );
require_once( 'template-tags/taxonomy.php' );
require_once( 'template-tags/form.php'     );
require_once( 'template-tags/url.php'      );
require_once( 'conditional-tags.php'       );
require_once( 'theme-actions.php'          );

class WPSC_Theme_Engine
{

	private static $instance = null;

	public static function get_instance() {
		if ( is_null( self::$instance ) )
			self::$instance = new WPSC_Theme_Engine();

		return self::$instance;
	}

	private $compat_mode = false;

	private function __construct() {
		add_filter( 'archive_template'  , array( $this, '_filter_get_archive_template'  ) );
		add_filter( 'single_template'   , array( $this, '_filter_get_single_template'   ) );
		add_filter( 'taxonomy_template' , array( $this, '_filter_get_taxonomy_template' ) );

		add_action( 'wp', array( $this, '_action_wp_clone_query_object' ), 1 );
	}

	private function locate_compat_template( $type ) {
		require_once( 'class-theme-compat.php' );

		$this->compat_mode = true;
		$this->compat = new WPSC_Theme_Engine_Compat();
		$this->compat->activate( $type );
		$this->compat->reset_globals();

		return $this->compat->locate_template();
	}

	public function _action_wp_clone_query_object() {
		global $wp_query;
		$this->init_query_flags();

		$GLOBALS['wpsc_query_levels'] = array();

		// Deep clone the $wp_query object
		// We need to do this because later it might be heavily modified by other plugins or
		// by compat theme engine.
		$GLOBALS['wpsc_query_levels'][] = $GLOBALS['wpsc_query'] = unserialize( serialize( $wp_query ) );
	}

	private function init_query_flags() {
		global $wp_query;

		$props = array(
			'page',
			'cart',
			'checkout',
			'login',
			'password_reminder',
			'register',
		);

		foreach ( $props as $prop ) {
			$prop = 'wpsc_is_' . $prop;
			if ( ! isset( $wp_query->$prop ) )
				$wp_query->$prop = false;
		}
	}

	/**
	 * This function is hooked into 'archive_template' filter.
	 *
	 * It searches for archive-wpsc-product.php and archive.php using {@link wpsc_locate_template()}
	 * instead of {@link locate_template()}, which means it looks for those templates in two additional
	 * paths that WP e-Commerce defines in {@link wpsc_locate_template()}.
	 *
	 * @since  4.0
	 * @access public
	 * @uses   get_post_type()
	 * @uses   wpsc_locate_template()
	 *
	 * @param  string $template The template file that get_query_template() found
	 * @return string           The template file located by WP e-Commerce
	 */
	public function _filter_get_archive_template( $template ) {
		if ( is_post_type_archive( array( 'wpsc-product' ) ) ) {
			if ( $located = apply_filters( 'wpsc_get_archive_template', false ) )
				return $located;

			$post_type_object = get_queried_object();
			$post_type = $post_type_object->name;

			$templates = array(
				"archive-{$post_type}.php",
			);

			if ( $located = wpsc_locate_template( $templates ) )
				$template = $located;
			else
				$template = $this->locate_compat_template( 'archive' );
		}

		return $template;
	}

	/**
	 * This function is hooked into 'single_template' filter.
	 *
	 * It searches for single-wpsc-product.php and single.php using {@link wpsc_locate_template()}
	 * instead of {@link locate_template()}, which means it looks for those templates in two additional
	 * paths that WP e-Commerce defines in {@link wpsc_locate_template()}.
	 *
	 * @since  4.0
	 * @access public
	 * @uses   get_post_type()
	 * @uses   wpsc_locate_template()
	 *
	 * @param  string $template
	 * @return string
	 */
	public function _filter_get_single_template( $template ) {
		$post_type = get_post_type();

		if ( in_array( $post_type, array( 'wpsc-product' ) ) ) {
			$templates = array(
				"single-{$post_type}.php",
			);

			if ( $located = wpsc_locate_template( $templates ) )
				$template = $located;
			else
				$template = $this->locate_compat_template( 'single' );
		}

		return $template;
	}

	/**
	 * This function is hooked into 'taxonomy_template' filter.
	 *
	 * It searches for WPEC related taxonomy templates using {@link wpsc_locate_template()}
	 * instead of {@link locate_template()}, which means it looks for those templates in two additional
	 * paths that WP e-Commerce defines in {@link wpsc_locate_template()}.
	 *
	 * @since  4.0
	 * @access public
	 * @uses   get_post_type()
	 * @uses   wpsc_locate_template()
	 * @uses   WPSC_Theme_Engine::locate_compat_template()
	 *
	 * @param  string $template The template file that get_query_template() found
	 * @return string           The template file located by WP e-Commerce
	 */
	public function _filter_get_taxonomy_template( $template ) {
		$term = get_queried_object();
		$taxonomy = $term->taxonomy;

		if ( in_array( $taxonomy, array( 'wpsc_product_category', 'product_tag' ) ) ) {
			if ( $located = apply_filters( 'wpsc_get_taxonomy_template', false ) )
				return $located;

			$templates = array(
				"taxonomy-$taxonomy-{$term->slug}.php",
				"taxonomy-$taxonomy.php",
				'taxonomy.php',
			);

			if ( $located = wpsc_locate_template( $templates ) )
				$template = $located;
			else
				$template = $this->locate_compat_template( 'taxonomy' );
		}

		return $template;
	}

}

WPSC_Theme_Engine::get_instance();