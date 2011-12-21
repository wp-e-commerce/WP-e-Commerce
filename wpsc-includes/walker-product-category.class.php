<?php

class WPSC_Walker_Product_Category extends Walker
{
	/**
	 * @see   Walker::$tree_type
	 * @since 4.0
	 * @var   string
	 */
	public $tree_type = 'category';

	/**
	 * @see   Walker::$db_fields
	 * @since 4.0
	 * @todo  Decouple this
	 * @var   array
	 */
	public $db_fields = array ('parent' => 'parent', 'id' => 'term_id');

	/**
	 * @access public
	 * @see    Walker::start_lvl()
	 * @since  4.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int    $depth  Depth of category. Used for tab indentation.
	 * @param array  $args   Will only append content if style argument value is 'list'.
	 */
	public function start_lvl(&$output, $depth, $args) {
		if ( 'list' != $args['style'] )
			return;

		$indent = str_repeat("\t", $depth);
		$class = 'children level-' . $depth;
		$output .= $indent . sprintf( $args['before_nested_list'], $class ) . "\n";
	}

	/**
	 * @access public
	 * @see    Walker::end_lvl()
	 * @since  4.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int    $depth  Depth of category. Used for tab indentation.
	 * @param array  $args   Will only append content if style argument value is 'list'.
	 */
	public function end_lvl(&$output, $depth, $args) {
		if ( 'list' != $args['style'] )
			return;

		$indent = str_repeat("\t", $depth);
		$output .= $indent . $args['after_nested_list'] . "\n";
	}

	/**
	 * @access public
	 * @see    Walker::end_el()
	 * @since  2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $page   Not used.
	 * @param int    $depth  Depth of category. Not used.
	 * @param array  $args   Only uses 'list' for whether should append to output.
	 */
	public function end_el(&$output, $page, $depth, $args) {
		if ( 'list' != $args['style'] )
			return;

		$output .= $args['after_item'] . "\n";
	}

	/**
	 * @access public
	 * @see    Walker::start_el()
	 * @since  2.1.0
	 *
	 * @param string $output   Passed by reference. Used to append additional content.
	 * @param object $category Category data object.
	 * @param int    $depth    Depth of category in reference to parents.
	 * @param array  $args
	 */
	public function start_el(&$output, $category, $depth, $args) {
		global $_wp_additional_image_sizes;
		extract($args);

		$cat_name = esc_attr( $category->name );
		$cat_name = apply_filters( 'list_cats', $cat_name, $category );
		$link = '<a href="' . esc_attr( get_term_link($category) ) . '" ';
		if ( $use_desc_for_title == 0 || empty($category->description) )
			$link .= 'title="' . esc_attr( sprintf( __( 'View all products in %s' ), $cat_name ), 'wpsc' ) . '"';
		else
			$link .= 'title="' . esc_attr( strip_tags( apply_filters( 'category_description', $category->description, $category ) ) ) . '"';
		$link .= '>';
		$link .= $cat_name . '</a>';

		if ( ! empty( $feed_image ) || ! empty( $feed ) ) {
			$link .= ' ';

			if ( empty( $feed_image ) )
				$link .= '(';

			$link .= '<a href="' . get_term_feed_link( $category->term_id, $category->taxonomy, $feed_type ) . '"';

			if ( empty( $feed ) ) {
				$alt = ' alt="' . sprintf( __( 'Feed for all products in %s', 'wpsc' ), $cat_name ) . '"';
			} else {
				$title  = ' title="' . $feed . '"';
				$alt    = ' alt="' . $feed . '"';
				$name   = $feed;
				$link  .= $title;
			}

			$link .= '>';

			if ( empty( $feed_image ) )
				$link .= $name;
			else
				$link .= "<img src='$feed_image'$alt$title" . ' />';

			$link .= '</a>';

			if ( empty( $feed_image ) )
				$link .= ')';
		}

		if ( !empty( $show_count ) )
			$link .= ' (' . intval( $category->count ) . ')';

		if ( !empty( $show_date ) )
			$link .= ' ' . gmdate( 'Y-m-d', $category->last_update_timestamp );

		$class = 'cat-item cat-item-' . $category->term_id;
		if ( !empty($current_category) ) {
			$_current_category = get_term( $current_category, $category->taxonomy );
			if ( $category->term_id == $current_category )
				$class .=  ' current-cat';
			elseif ( $category->term_id == $_current_category->parent )
				$class .=  ' current-cat-parent';
		}
		$output .= "\t" . sprintf( $before_item, $class ) . $link . "\n";

		if ( $show_thumbnail ) {
			$class       = 'wpsc-thumbnail wpsc-product-category-thumbnail wpsc-product-category-thumbnail-' . $category->term_id;
			$class       = apply_filters( 'wpsc_category_thumbnail_class', $class, $category );
			$output     .= sprintf( $args['before_thumbnail'], $class );
			$dimensions  = $_wp_additional_image_sizes['wpsc_product_taxonomy_thumbnail'];
			$thumbnail   = wpsc_get_categorymeta( $category->term_id, 'image' );
			if ( $thumbnail && is_file( WPSC_CATEGORY_DIR . $thumbnail ) ) {
				$output .= "<img src='".WPSC_CATEGORY_URL."$thumbnail' alt='{$category->name}' title='{$category->name}' style='width: {$dimensions['width']}px; height: {$dimensions['height']}px;' />";
			} else {
				$src = apply_filters( 'wpsc_category_no_thumbnail_url', WPSC_THEME_ENGINE_COMPAT_URL . '/default/images/no-thumbnails.png' );
				$output .= '<img src="' . $src . '" alt="' . $category->name . '" title="' . $category->name . '" width="' . $dimensions['width'] . '" height="' . $dimensions['height'] . '" />';
			}
			$output .= $args['after_thumbnail'];
		}

		if ( $show_description ) {
			$allowed_tags = array('a' => array('href' => array(),'title' => array()),'abbr' => array('title' => array()),'acronym' => array('title' => array()),'code' => array(),'em' => array(),'strong' => array(), 'b'=> array());

			$allowedtags = apply_filters('wpsc_category_description_allowed_tags' , $allowed_tags);
			$description = wp_kses( stripslashes( $category->description ), $allowedtags );
			$description = wpautop( wptexturize( $description ) );
			$class       = "wpsc-product-category-description wpsc-product-category-description-{$category->term_id}";
			$output .= sprintf( $before_description, $class );
			$output .= $description;
			$output .= $after_description;
		}
	}
}