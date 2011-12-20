<?php
/**
 * WP e-Commerce template tags.
 *
 * This file contains template tags that theme developers can use in theme templates.
 *
 * @see        theme.functions.php
 * @see        conditional-tags.functions.php
 * @since      4.0
 * @package    wp-e-commerce
 * @subpackage template-tags
 */

/**
 * Whether current product loop has results to loop over.
 *
 * @see   WP_Query::have_posts()
 * @since 4.0
 * @uses  $wpsc_query Global WPEC query object
 *
 * @return bool
 */
function wpsc_have_products() {
	global $wpsc_query;
	return $wpsc_query->have_posts();
}

/**
 * Iterate the product index of the loop.
 *
 * @see   WP_Query::the_post()
 * @since 4.0
 * @uses  $wpsc_query Global WPEC query object
 */
function wpsc_the_product() {
	global $wpsc_query;
	$wpsc_query->the_post();
}

/**
 * Return the current product ID in the loop.
 *
 * @since 4.0
 * @uses  get_the_ID()
 *
 * @return int The Product ID
 */
function wpsc_get_product_id() {
	return get_the_ID();
}

/**
 * Output the current product ID in the loop.
 *
 * @since 4.0
 * @uses  the_ID()
 */
function wpsc_product_id() {
	the_ID();
}

/**
 * Output the class attribute of the current product in the loop.
 *
 * @since 4.0
 * @uses  post_class()
 */
function wpsc_product_class() {
	post_class();
}

/**
 * Return the product permalink.
 *
 * @since 4.0
 * @uses  apply_filters() Applies 'wpsc_get_product_permalink' filter
 * @uses  get_permalink()
 *
 * @param  int    $id        Optional. The product ID. Defaults to the current post in the loop.
 * @param  bool   $leavename Optional. Whether to keep product name. Defaults to false.
 * @return string
 */
function wpsc_get_product_permalink( $id = 0, $leavename = false ) {
	return apply_filters( 'wpsc_get_product_permalink', get_permalink() );
}

/**
 * Output the permalink of the current product in the loop.
 *
 * @since 4.0
 * @uses  wpsc_get_product_permalink()
 */
function wpsc_product_permalink() {
	echo wpsc_get_product_permalink();
}

/**
 * Sanitize the current title when retrieving or displaying.
 *
 * Works like {@link wpsc_product_title()}, except the parameters can be in a string or
 * an array. See the function for what can be override in the $args parameter.
 *
 * The title before it is displayed will have the tags stripped and {@link
 * esc_attr()} before it is passed to the user or displayed. The default
 * as with {@link wpsc_product_title()}, is to display the title.
 *
 * @since 4.0
 * @uses  esc_attr()
 * @uses  wp_parse_args()
 * @uses  wpsc_get_product_title()
 *
 * @param  string|array $args Optional. Override the defaults.
 * @return string|null  Null on failure or display. String when echo is false.
 */
function wpsc_product_title_attribute( $args = '' ) {
	$title = wpsc_get_product_title();

	if ( strlen($title) == 0 )
		return;

	$defaults = array('before' => '', 'after' =>  '', 'echo' => true);
	$r = wp_parse_args($args, $defaults);
	extract( $r, EXTR_SKIP );

	$title = $before . $title . $after;
	$title = esc_attr(strip_tags($title));

	if ( $echo )
		echo $title;
	else
		return $title;
}

/**
 * Return the title a product.
 *
 * @since 4.0
 * @uses apply_filters() Applies 'wpsc_get_product_title' filter
 * @uses get_the_title()
 *
 * @param  int    $id Optional. The product ID. Defaults to the current post in the loop.
 * @return string
 */
function wpsc_get_product_title( $id = 0 ) {
	return apply_filters( 'wpsc_get_product_title', get_the_title( $id ), $id );
}

/**
 * Output the title of the current product in the loop.
 *
 * @since 4.0
 * @uses  wpsc_get_product_title()
 *
 * @param  string      $before Optional. Specify HTML before the title. Defaults to ''.
 * @param  string      $after  Optional. Specify HTML after the title. Defaults to ''.
 * @param  bool        $echo   Optional. Whether to output or return the title. Defaults to true.
 * @return null|string
 */
function wpsc_product_title( $before = '', $after = '', $echo = true ) {
	$title = wpsc_get_product_title();

	if ( strlen( $title ) == 0 )
		return;

	$title = $before . $title . $after;

	if ( $echo )
		echo $title;
	else
		return $title;
}

/**
 * Return HTML for the list of product categories.
 *
 * This function accepts a query string or array containing arguments to further customize the HTML
 * output of the list:
 *
 *     'id'        - The product ID for which you want to get the category list. Defaults to current product in the loop.
 *     'before'    - HTML before the list. Defaults to ''.
 *     'after'     - HTML after the list. Defaults to ''.
 *     'separator' - The separator of list items. Defaults to ', '.
 *
 * @since 4.0
 * @uses  get_the_term_list()
 * @uses  wp_parse_args()
 *
 * @param  string|array $args Optional. Specify custom arguments for this function.
 * @return string
 */
function wpsc_get_product_category_list( $args = '' ) {
	$defaults = array(
		'id'        => 0,
		'before'    => '',
		'after'     => '',
		'separator' => __( ', ', 'category list separator', 'wpsc' ),
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	return get_the_term_list( $id, 'wpsc_product_category', $before, $separator, $after );
}

/**
 * Output the category list of the current product in the loop.
 *
 * @since 4.0
 * @uses  wpsc_get_product_category_list()
 *
 * @param string $args Optional. Defaults to ''. See {@link wpsc_get_product_category_list()} for the full list of arguments you can use to customize the output.
 */
function wpsc_product_category_list( $args = '' ) {
	echo wpsc_get_product_category_list( $args );
}

/**
 * Return HTML for the list of product tags.
 *
 * @since 4.0
 * @uses  get_the_term_list()
 * @uses  wp_parse_args()
 *
 * @param  string|array $args Optional. Defaults to ''. See {@link wpsc_get_product_category_list()} for the full list of arguments you can use to customize the output.
 * @return string
 */
function wpsc_get_product_tag_list( $args = '' ) {
	$defaults = array(
		'id'        => 0,
		'before'    => '',
		'after'     => '',
		'separator' => __( ', ', 'tag list separator', 'wpsc' ),
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );
	return get_the_term_list( $id, 'product_tag', $before, $separator, $after );
}

/**
 * Return the number of categories associated with a product.
 *
 * @since 4.0
 * @uses  get_the_terms()
 *
 * @param  int $id Optional. Product ID. Defaults to current product in the loop.
 * @return int
 */
function wpsc_get_product_category_count( $id = 0 ) {
	$cats = get_the_terms( $id, 'wpsc_product_category' );

	if ( $cats === false )
		return 0;

	return count( $cats );
}

/**
 * Return the number of tags associated with a product.
 *
 * @since 4.0
 * @uses  get_the_terms()
 *
 * @param  int $id Optional. Product ID. Defaults to current product in the loop.
 * @return int
 */
function wpsc_get_product_tag_count( $id = 0 ) {
	$tags = get_the_terms( $id, 'product_tag' );

	if ( $tags === false )
		return 0;

	return count( $tags );
}

/**
 * Output the edit link of a product.
 *
 * @since 4.0
 * @uses  apply_filters() Applies 'wpsc_edit_product_link' filter.
 * @uses  edit_post_link()
 * @uses  wp_parse_args()
 *
 * @param  string $args Optional. Defaults to ''.
 */
function wpsc_edit_product_link( $args = '' ) {
	$defaults = array(
		'id'     => 0,
		'before' => '',
		'after'  => '',
		'title'  => _x( 'Edit This Product', 'product edit link template tag', 'wpsc' ),
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );
	ob_start();
	edit_post_link( $title, $before, $after, $id );
	$link = ob_get_clean();
	echo apply_filters( 'wpsc_edit_product_link', $link, $id );
}

/**
 * Return the ID of the product thumbnail of a product.
 *
 * @since 4.0
 * @uses  get_post_thumbnail_id()
 *
 * @param  null|int $product_id Optional. The product ID. Defaults to the current product ID in the loop.
 * @return int
 */
function wpsc_get_product_thumbnail_id( $product_id = null ) {
	return get_post_thumbnail_id( $product_id );
}

/**
 * Return the HTML of a product's featured thumbnail.
 *
 * Note that the $size argument of this function is different from that of get_the_post_thumbnail().
 * For this function, you can only use these three sizes that correspond to the sizes specified in
 * your Settings -> Store -> Presentation option page:
 *     'single'   - corresponds to "Single Product Image Size" option.
 *     'archive'  - corresponds to "Default Product Thumbnail Size" option.
 *     'taxonomy' - corresponds to "Default Product Group Thumbnail Size" option.
 *
 * @see   wpsc_check_thumbnail_support() Where the thumbnail sizes are registered.
 * @since 4.0
 * @uses  $_wp_additional_image_sizes The array holding registered thumbnail sizes.
 * @uses  get_attached_file()
 * @uses  get_post_meta()
 * @uses  get_the_post_thumbnail()
 * @uses  wp_get_attachment_metadata()
 * @uses  wp_update_attachment_metadata()
 * @uses  wpsc_get_product_thumbnail_id()
 * @uses  wpsc_has_product_thumbnail()
 * @uses  update_post_meta()
 *
 * @param  null|int $id   Optional. The product ID. Defaults to the current product in the loop.
 * @param  string   $size Optional. Size of the product thumbnail. Defaults to 'single'.
 * @param  string   $attr Optional. Query string or array of attributes. Defaults to ''.
 * @return string
 */
function wpsc_get_product_thumbnail( $id = null, $size = 'single', $attr = '' ) {
	global $_wp_additional_image_sizes;
	$wp_size = 'wpsc_product_' . $size . '_thumbnail';

	if ( wpsc_has_product_thumbnail() ) {
		$thumb_id = wpsc_get_product_thumbnail_id( $id );

		// Get the size metadata registered in wpsc_check_thumbnail_support()
		$size_metadata = $_wp_additional_image_sizes[$wp_size];

		// Get the current size metadata that has been generated for this product
		$current_size_metadata = get_post_meta( $thumb_id, '_wpsc_current_size_metadata', true );
		if ( empty( $current_size_metadata ) )
			$current_size_metadata = array();

		// If this thumbnail for the current size was not generated yet, or generated with different
		// parameters (crop, for example), we need to regenerate the thumbnail
		if ( ! array_key_exists( $size, $current_size_metadata ) || $current_size_metadata[$size] != $size_metadata ) {
			// Get the original thumbnail image file
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			$metadata = wp_get_attachment_metadata( $thumb_id );
			if ( ! is_array( $metadata ) )
				$metadata = array();
			if ( empty( $metadata['sizes'] ) )
				$metadata['sizes'] = array();
			$file = get_attached_file( $thumb_id );

			// Re-generate the thumbnail metadata for this size
			$generated = wp_generate_attachment_metadata( $thumb_id, $file );
			if ( ! empty( $generated ) && ! empty( $generated['sizes'] ) )
				$metadata['sizes'] = array_merge( $metadata['sizes'], $generated['sizes'] );

			// Update the thumbnail metadata for this size
			wp_update_attachment_metadata( $thumb_id, $metadata );

			$current_size_metadata[$size] = $size_metadata;

			// Save the information of the size parameters that we use to re-generate this thumbnail
			update_post_meta( $thumb_id, '_wpsc_current_size_metadata', $current_size_metadata );
		}
	}

	return get_the_post_thumbnail( $id, $wp_size, $attr );
}

/**
 * Output the thumbnail for the current product in the loop.
 *
 * @since 4.0
 * @uses  wpsc_get_product_thumbnail()
 *
 * @param  string $size Optional. Defaults to 'single'. See {@link wpsc_get_product_thumbnail()} for a list of available sizes you can use.
 * @param  string $attr Optional. Query string or array of attributes. Defaults to ''.
 */
function wpsc_product_thumbnail( $size = 'single', $attr = '' ) {
	echo wpsc_get_product_thumbnail( null, $size, $attr );
}

/**
 * Output a dummy thumbnail image in case the current product in the loop does not have a specified
 * featured thumbnail.
 *
 * @since 4.0
 * @uses  $_wp_additional_image_size The array containing registered image sizes
 * @uses  apply_filters() Applies 'wpsc_product_no_thumbnail_url' filter
 * @uses  apply_filters() Applies 'wpsc_product_no_thumbnail_html' filter
 *
 * @param string $size Optional. Defaults to 'single'. See {@link wpsc_get_product_thumbnail()} for a list of available sizes you can use.
 * @param string $attr Optional. Query string or array of attributes. Defaults to ''.
 */
function wpsc_product_no_thumbnail_image( $size = 'single', $attr = '' ) {
	global $_wp_additional_image_sizes;

	$wp_size    = 'wpsc_product_' . $size . '_thumbnail';
	$dimensions = $_wp_additional_image_sizes[$wp_size];
	$title      = wpsc_the_product_title_attribute( array( 'echo' => false ) );
	$src        = apply_filters( 'wpsc_product_no_thumbnail_url', WPSC_THEME_ENGINE_COMPAT_URL . '/default/images/no-thumbnails.png', $size, $attr );
	$html       = '<img src="' . $src . '" title="' . $title . '" width="' . $dimensions['width'] . '" height="' . $dimensions['height'] . '" />';
	$html       = apply_filters( 'wpsc_product_no_thumbnail_html', $html, $size, $attr );

	echo $html;
}

/**
 * Output the description of the current product in the loop.
 *
 * @see   wpsc_get_product_description()
 * @since 4.0
 * @uses  apply_filters() Applies 'the_content' filter
 * @uses  apply_filters() Applies 'wpsc_product_description' filter
 * @uses  wpsc_get_product_description()
 *
 * @param null|string $more_link_text Optional. Content for when there is more text.
 * @param string      $mode           Optional. See {@link wpsc_get_product_description} for a full list of options you can use to customize the output.
 */
function wpsc_product_description( $more_link_text = null, $mode = 'with-teaser' ) {
	$content = wpsc_get_product_description( $more_link_text, $mode );
	$content = apply_filters( 'the_content', $content );
	$content = apply_filters( 'wpsc_product_description', $content );
	$content = str_replace( ']]>', ']]&gt;', $content );
	echo $content;
}

/**
 * Remove the "more" link by hooking into 'the_content_more_link' and return an empty string.
 *
 * @since 4.0
 *
 * @param  string $link
 * @return string An empty string
 */
function wpsc_filter_remove_the_content_more_link( $link ) {
	return '';
}

/**
 * Return the description of the current product in the loop.
 *
 * If your product description has a <!--more--> tag, then only the teaser will be displayed on
 * product listing pages (product catalog, taxonomy etc.). On single product view only the teaser
 * will be displayed.
 *
 * The $more_link_text argument lets you customize the "more" text.
 *
 * The $mode argument can have the following values:
 *     'with-teaser' - The teaser is displayed along with the main description
 *     'only-teaser' - Only the teaser is displayed, the text after <!--more--> tag will be ignored
 *     'no-teaser'   - The teaser is stripped out.
 *
 * @since 4.0
 * @uses  add_filter()      Adds 'wpsc_filter_remove_content_more_link' to 'the_content_more_link' filter
 * @uses  apply_filters()   Applies 'wpsc_get_product_description' filter hook
 * @uses  get_the_content() Retrieves product's description
 * @uses  remove_filter()   Removes 'wpsc_filter_remove_content_more_link' from 'the_content_more_link' filter
 *
 * @param  null|string $more_link_text Optional. The customized text for the "read more" link. Defaults to 'more'.
 * @param  string      $mode           Optional. Specify how to deal with teaser. Defaults to 'with-teaser'.
 * @return string
 */
function wpsc_get_product_description( $more_link_text = null, $mode = 'with-teaser' ) {
	$stripteaser = $mode == 'no-teaser';

	if ( $mode == 'only-teaser' )
		add_filter( 'the_content_more_link', 'wpsc_filter_remove_the_content_more_link', 99 );

	$content = get_the_content( $more_link_text, $stripteaser );

	if ( $mode == 'only-teaser' ) {
		remove_filter( 'the_content_more_link', 'wpsc_filter_remove_the_content_more_link', 99 );
		$sub = '<span id="more-' . get_the_ID() . '"></span>';
		$pos = strpos( $content, $sub );
		if ( $pos !== false )
			$content = substr( $content, 0, $pos );
	}

	return apply_filters( 'wpsc_get_product_description', $content, $mode );
}

/**
 * Get an array of variation sets for a product.
 *
 * @since 4.0
 * @uses  wpsc_get_product_id()
 * @uses  WPSC_Product_Variations::get_instance()
 * @uses  WPSC_Product_Variations::get_variation_sets()
 *
 * @param  null|int $id Optional. The product ID. Defaults to the current product in the loop.
 * @return array        An associated array of $term_id => $term_name
 */
function wpsc_get_product_variation_sets( $id = null ) {
	if ( empty( $id ) )
		$id = wpsc_get_product_id();

	$variations = WPSC_Product_Variations::get_instance( $id );
	return $variations->get_variation_sets();
}

/**
 * Display the drop down listing child variation terms of a variation set associated with a certain
 * product.
 *
 * @since 4.0
 * @uses  wpsc_get_product_variation_set_dropdown()
 *
 * @param  int $variation_set_id The term_id of the variation set.
 * @param  int $product_id       Optional. The product ID. Defaults to the current product in the loop.
 */
function wpsc_product_variation_set_dropdown( $variation_set_id, $product_id = null ) {
	echo wpsc_get_product_variation_set_dropdown( $variation_set_id, $product_id );
}

/**
 * Return the HTML for variation set dropdown of a certain product.
 *
 * @since 4.0
 * @uses  wpsc_get_product_id()
 * @uses  WPSC_Product_Variations::get_instance()
 * @uses  WPSC_Product_Variations::variation_set_dropdown()
 *
 * @param  int $variation_set_id The term_id of the variation set.
 * @param  int $product_id       Optional. The product ID. Defaults to the current product in the loop.
 * @return string
 */
function wpsc_get_product_variation_set_dropdown( $variation_set_id, $product_id = null ) {
	if ( empty( $product_id ) )
		$product_id = wpsc_get_product_id();

	$variations = WPSC_Product_Variations::get_instance( $product_id );
	return $variations->get_variation_set_dropdown( $variation_set_id );
}

/**
 * Output the original price of a product.
 *
 * See {@link wpsc_get_product_original_price()} for more information about the $format
 * argument.
 *
 * @since 4.0
 * @uses  apply_filters() Applies 'wpsc_product_original_price' filter.
 * @uses  wpsc_get_product_original_price()
 *
 * @param null|int $product_id     Optional. The product ID. Defaults to current product in the loop.
 * @param string   $format Optional. The format of the price. Defaults to 'string'.
 */
function wpsc_product_original_price( $product_id = null, $format = 'string' ) {
	$original_price = apply_filters( 'wpsc_product_original_price', wpsc_get_product_original_price( $product_id, $format ) );
	echo $original_price;
}

/**
 * Return the original price of a product.
 *
 * The $return_type can be one of the following values:
 *     'string' - Return the price with currency symbol and 2 decimal places (e.g. $10.26)
 *     'float'  - Return an unformatted numeric value with no currency symbol (e.g. 10.259)
 *
 * @since 4.0
 * @uses  apply_filters() Applies 'wpsc_get_product_original_price' filter.
 * @uses  get_post_meta()
 * @uses  wpsc_format_price()
 * @uses  wpsc_get_product_id()
 * @uses  wpsc_has_product_variations()
 * @uses  WPSC_Product_Variations::get_instance()
 * @uses  WPSC_Product_Variations::get_original_from_price()
 *
 * @param  null|int $product_id  Optional. The product ID. Defaults to the current product in the loop.
 * @param  string   $format      Optional. The format of the price. Defaults to 'string'.
 * @return float|string
 */
function wpsc_get_product_original_price( $product_id = null, $format = 'string' ) {
	if ( empty( $product_id ) )
		$product_id = wpsc_get_product_id();

	if ( wpsc_has_product_variations( $product_id ) ) {
		// get minimum original price of all variations
		$variations = WPSC_Product_Variations::get_instance( $product_id );
		$original_price = $variations->get_original_from_price( $format );
	} else {
		$original_price = get_post_meta( $product_id, '_wpsc_price', true );
		if ( $format === 'string' )
			$original_price = wpsc_format_price( $original_price );
		else
			$original_price = (float) $original_price;
	}

	return apply_filters( 'wpsc_get_product_original_price', $original_price, $product_id, $format );
}

/**
 * Output the sale price of a product.
 *
 * See {@link wpsc_get_product_sale_price()} for more information about the $format argument.
 *
 * @since 4.0
 * @uses  apply_filters() Applies 'wpsc_product_sale_price' filter.
 * @uses  wpsc_get_product_sale_price()
 *
 * @param int    $product_id Optional. The product ID. Defaults to the current product in the loop.
 * @param string $format     Optional. The format of the price. Defaults to 'string'.
 */
function wpsc_product_sale_price( $product_id = null, $format = 'string' ) {
	$sale_price = apply_filters( 'wpsc_the_product_sale_price', wpsc_get_product_sale_price( $product_id, $format ) );
	echo $sale_price;
}

/**
 * Return the sale price of a product.
 *
 * The $return_type can be one of the following values:
 *     'string' - Return the price with currency symbol and 2 decimal places (e.g. $10.26)
 *     'float'  - Return an unformatted numeric value with no currency symbol (e.g. 10.259)
 *
 * @since 4.0
 * @uses  apply_filters() Applies 'wpsc_get_product_sale_price' filter.
 * @uses  get_post_meta()
 * @uses  wpsc_format_price()
 * @uses  wpsc_get_product_id()
 * @uses  wpsc_has_product_variations()
 * @uses  WPSC_Product_Variations::get_instance()
 * @uses  WPSC_Product_Variations::get_sale_from_price()
 *
 * @param  null|int $product_id  Optional. The product ID. Defaults to the current product in the loop.
 * @param  string   $format      Optional. The format of the price. Defaults to 'string'.
 * @return float|string
 */
function wpsc_get_product_sale_price( $product_id = null, $format = 'string' ) {
	if ( empty( $product_id ) )
		$product_id = wpsc_get_product_id();

	if ( wpsc_has_product_variations( $product_id ) ) {
		$variations = WPSC_Product_Variations::get_instance( $product_id );
		$sale_price = $variations->get_sale_from_price( $format );
	} else {
		$sale_price = get_post_meta( $product_id, '_wpsc_special_price', true );
		if ( $format === 'string' )
			$sale_price = wpsc_format_price( $sale_price );
		else
			$sale_price = (float) $sale_price;
	}

	return apply_filters( 'wpsc_get_the_product_sale_price', $sale_price, $product_id, $format );
}

/**
 * Format a price amount.
 *
 * The available options that you can specify in the $args argument include:
 *     'display_currency_symbol' - Whether to attach the currency symbol to the figure.
 *                                 Defaults to true.
 *     'display_decimal_point'   - Whether to display the decimal point.
 *                                 Defaults to true.
 *     'display_currency_code'   - Whether to attach the currency code to the figure.
 *                                 Defaults to fault.
 *     'isocode'                 - Specify the isocode of the base country that you want to use for
 *                                 this price.
 *                                 Defaults to the settings in Settings->Store->General.
 *
 * @since 4.0
 * @uses  apply_filters() Applies 'wpsc_format_price'                     filter
 * @uses  apply_filters() Applies 'wpsc_format_price_currency_code'       filter.
 * @uses  apply_filters() Applies 'wpsc_format_price_currency_symbol'     filter.
 * @uses  apply_filters() Applies 'wpsc_format_price_decimal_separator'   filter.
 * @uses  apply_filters() Applies 'wpsc_format_price_thousands_separator' filter.
 * @uses  apply_filters() Applies 'wpsc_modify_decimals' filter.
 * @uses  get_option()    Gets the value of 'currency_sign_location' in Settings->Store->General.
 * @uses  get_option()    Gets the value of 'currency_type' in Settings->Store->General.
 * @uses  WPSC_Country::__construct()
 * @uses  WPSC_Country::get()
 * @uses  wp_parse_args()
 *
 * @param  float|int|string $amt  The price you want to format.
 * @param  string|array     $args A query string or array containing the options. Defaults to ''.
 * @return string                 The formatted price.
 */
function wpsc_format_price( $amt, $args = '' ) {
	$defaults = array(
		'display_currency_symbol' => true,
		'display_decimal_point'   => true,
		'display_currency_code'   => false,
		'isocode'                 => false,
		'currency_code'           => false,
	);

	$args = wp_parse_args( $args );

	// Either display symbol or code, not both
	if ( array_key_exists( 'display_currency_symbol', $args ) )
		$args['display_currency_code'] = ! $args['display_currency_symbol'];
	elseif ( array_key_exists( 'display_currency_code', $args ) )
		$args['display_currency_symbol'] = ! $args['display_currency_code'];

	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	$currencies_without_fractions = array( 'JPY', 'HUF' );
	if ( $isocode )
		$currency = new WPSC_Country( $isocode, 'isocode' );
	else
		$currency = new WPSC_Country( get_option( 'currency_type' ) );
	$currency_code = $currency->get( 'code' );

	// No decimal point, no decimals
	if ( ! $display_decimal_point || in_array( $currency_code, $currencies_without_fractions ) )
		$decimals = 0;
	else
		$decimals = 2; // default is 2

	$decimals            = apply_filters( 'wpsc_modify_decimals'                 , $decimals, $isocode );
	$decimal_separator   = apply_filters( 'wpsc_format_price_decimal_separator'  , wpsc_get_option( 'decimal_separator' ), $isocode );
	$thousands_separator = apply_filters( 'wpsc_format_price_thousands_separator', wpsc_get_option( 'thousands_separator' ), $isocode );

	// Format the price for output
	$formatted = number_format( $amt, $decimals, $decimal_separator, $thousands_separator );

	if ( ! $display_currency_code )
		$currency_code = '';

	$symbol = $display_currency_symbol ? $currency->get('symbol' ) : '';
	$symbol = esc_html( $symbol );
	$symbol = apply_filters( 'wpsc_format_price_currency_symbol', $symbol, $isocode );

	$currency_sign_location = get_option( 'currency_sign_location' );

	// Rejig the currency sign location
	switch ( $currency_sign_location ) {
		case 1:
			$format_string = '%3$s%1$s%2$s';
			break;

		case 2:
			$format_string = '%3$s %1$s%2$s';
			break;

		case 4:
			$format_string = '%1$s%2$s  %3$s';
			break;

		case 3:
		default:
			$format_string = '%1$s %2$s%3$s';
			break;
	}
	$currency_code = apply_filters( 'wpsc_format_price_currency_code', $currency_code, $isocode );

	// Compile the output
	$output = trim( sprintf( $format_string, $currency_code, $symbol, $formatted ) );
	$output = apply_filters( 'wpsc_format_price', $output, $isocode );
	return $output;
}

/**
 * Output or return the HTML of the "Add to Cart" button of a product.
 *
 * @since 4.0
 * @uses  apply_filters() Applies 'wpsc_product_add_to_cart_button_title' filter.
 * @uses  apply_filters() Applies 'wpsc_product_add_to_cart_button'       filter.
 * @uses  wpsc_get_product_id()
 *
 * @param  null|string $title Optional. Title of the button. Defaults to "Add to Cart'."
 * @param  null|int    $id    Optional. The product ID. Defaults to current product in the loop.
 * @param  bool        $echo  Optional. Whether to echo the HTML or to return it. Defaults to true.
 * @return null|string
 */
function wpsc_the_product_add_to_cart_button( $title = null, $id = null, $echo = true ) {
	if ( ! $id )
		$id = wpsc_get_product_id();

	if ( ! $title )
		$title = _x( 'Add to Cart', 'product add to cart button', 'wpsc' );

	$title  = apply_filters( 'wpsc_product_add_to_cart_button_title', $title, $id );
	$output = '<input class="wpsc-product-add-to-cart-button" id="wpsc-product-add-to-cart-button-' . $id . '" type="submit" value="' . esc_attr( $title ) . '" />';
	$output = apply_filters( 'wpsc_product_add_to_cart_button', $output, $title, $id );
	if ( $echo )
		echo $output;
	else
		return $output;
}

/**
 * Output the hidden field for a product id.
 *
 * This function is attached to 'wpsc_theme_product_add_to_cart_actions_after'.
 *
 * @since 4.0
 * @uses wpsc_get_product_id()
 *
 * @param  null|int $id Optional. The product ID. Defaults to the current product in the loop.
 */
function wpsc_product_id_hidden_field( $id = null ) {
	if ( ! $id )
		$id = wpsc_get_product_id();

	echo '<input type="hidden" name="wpsc_product_id" value="' . esc_attr( $id ) . '" />';
}
add_action( 'wpsc_theme_product_add_to_cart_actions_after', 'wpsc_product_id_hidden_field', 10, 1 );

/**
 * Wraps the read more link with a custom class.
 *
 * @since 4.0
 * @uses  get_post_type()
 *
 * @param  string $link
 * @return string
 */
function wpsc_filter_content_more_link( $link ) {
	if ( get_post_type( 'post_type' ) == 'wpsc-product' )
		$link = '<p class="wpsc-more-link">' . $link . '</p>';
	return $link;
}
add_filter( 'the_content_more_link', 'wpsc_filter_content_more_link' );

/**
 * Output pagination for the current loop.
 *
 * @since 4.9
 * @uses  wpsc_is_pagination_enabled()
 * @uses  wpsc_get_template_part()
 *
 * @param string $position Position of the pagination div.
 */
function wpsc_product_pagination( $position = 'bottom' ) {
	if ( ! wpsc_is_pagination_enabled( $position ) )
		return;

	echo '<div class="wpsc-pagination wpsc-pagination-' . esc_attr( $position ) . '">';
	wpsc_get_template_part( 'pagination-product-archive', $position );
	echo '</div>';
}

/**
 * Return the number of pages for the current loop.
 *
 * @since 4.0
 * @uses $wpsc_query The global product query object.
 *
 * @return int
 */
function wpsc_product_pagination_page_count() {
	global $wpsc_query;
	return $wpsc_query->max_num_pages;
}

/**
 * Output the pagination count.
 *
 * @since 4.0
 * @uses apply_filters() Applies 'wpsc_product_pagination_count' filter.
 * @uses $wpsc_query     The global product query object.
 * @uses get_query_var()
 * @uses wpsc_get_current_page_number()
 *
 * @return [type]
 */
function wpsc_product_pagination_count() {
	global $wpsc_query;

	$total        = empty( $wpsc_query->found_posts ) ? $wpsc_query->post_count : $wpsc_query->found_posts;
	$total_pages  = $wpsc_query->max_num_pages;
	$per_page     = get_query_var( 'posts_per_page' );
	$current_page = wpsc_get_current_page_number();
	$from         = ( $current_page - 1 ) * $per_page + 1;
	$to           = $from + $per_page - 1;
	$post_count   = $wpsc_query->post_count;

	if ( $to > $total )
		$to = $total;

	if ( $total > 1 ) {
		if ( $from == $to )
			$output = sprintf( __( 'Viewing product %1$s (of %2$s total)', 'wpsc' ), $from, $total );
		elseif ( $total_pages === 1 )
			$output = sprintf( __( 'Viewing %1$s products', 'wpsc' ), $total );
		else
			$output = sprintf( __( 'Viewing %1$s products - %2$s through %3$s (of %4$s total)', 'wpsc' ), $post_count, $from, $to, $total );
	} else {
		$output = sprintf( __( 'Viewing %1$s product', 'wpsc' ), $total );
	}

	// Filter and return
	echo apply_filters( 'wpsc_product_pagination_count', $output );
}

/**
 * Return the current page number of the current loop.
 *
 * @since 4.0
 * @uses  get_query_var()
 *
 * @return int
 */
function wpsc_get_current_page_number() {
	$current = get_query_var( 'paged' );
	if ( $current )
		return $current;

	return 1;
}

/**
 * Output the pagination links for the current loop.
 *
 * See {@link paginate_links()} for the available options that you can use with this function.
 *
 * @since 4.0
 * @uses  $wp_rewrite
 * @uses  $wpsc_query
 * @uses  apply_filters() Applies 'wpsc_product_pagination_links'      filter.
 * @uses  apply_filters() Applies 'wpsc_product_pagination_links_args' filter.
 * @uses  home_url()
 * @uses  is_rtl()
 * @uses  paginate_links()
 * @uses  wp_parse_args()
 * @uses  WP_Rewrite::using_permalinks()
 * @uses  wpsc_get_current_page_number()
 *
 * @param  string|array $args Query string or an array of options.
 */
function wpsc_product_pagination_links( $args = '' ) {
	global $wp_rewrite, $wpsc_query;

	$base = '';

	if ( wpsc_is_product_catalog() )
		$base = home_url( wpsc_get_option( 'catalog_slug' ) );
	elseif ( wpsc_is_product_category() )
		$base = wpsc_get_product_category_permalink();
	elseif ( wpsc_is_product_tag() )
		$base = wpsc_get_product_tag_permalink();

	if ( $wp_rewrite->using_permalinks() )
		$format = 'page/%#%';
	else
		$format = '?page=%#%';

	$defaults = array(
		'base'      => trailingslashit( $base ) . '%_%',
		'format'    => $format,
		'total'     => $wpsc_query->max_num_pages,
		'current'   => wpsc_get_current_page_number(),
		'prev_text' => is_rtl() ? __( '&rarr;', 'wpsc' ) : __( '&larr;', 'wpsc' ),
		'next_text' => is_rtl() ? __( '&larr;', 'wpsc' ) : __( '&rarr;', 'wpsc' ),
	);

	$r = wp_parse_args( $args, $defaults );
	$r = apply_filters( 'wpsc_product_pagination_links_args', $r );
	$links = apply_filters( 'wpsc_product_pagination_links', paginate_links( $r ) );
	echo $links;
}

/**
 * Output the permalink of a product category.
 *
 * @since 4.0
 * @uses  wpsc_get_product_category_permalink()
 *
 * @param  int|string|object $cat Optional. Either a term ID, term object or term slug.  Defaults to the main product category.
 */
function wpsc_product_category_permalink( $cat = '' ) {
	echo wpsc_get_product_category_permalink( $cat );
}

/**
 * Return the permalink of a product category.
 *
 * @since 4.0
 * @uses  apply_filters() Applies 'wpsc_get_product_category_url' filter.
 * @uses  get_query_var()
 * @uses  get_term_link()
 *
 * @param  int|string|object $cat Optional. Either a term ID, term object or term slug. Defaults to the main product category.
 * @return string
 */
function wpsc_get_product_category_permalink( $cat = '' ) {
	if ( ! $cat )
		$cat = get_query_var( 'term' );

	if ( is_numeric( $cat ) )
		$cat = absint( $cat );

	$link = get_term_link( $cat, 'wpsc_product_category' );

	return apply_filters( 'wpsc_get_product_category_permalink', $link, $cat );
}

/**
 * Output a product tag's permalink.
 *
 * @since 4.0
 * @uses  wpsc_get_product_tag_permalink()
 *
 * @param  int|string|object $tag Optional. Either a term ID, term object or term slug. Defaults to the main product tag.
 */
function wpsc_product_tag_permalink( $tag = '' ) {
	echo wpsc_get_product_tag_permalink( $tag );
}

/**
 * Return a product tag's permalink.
 *
 * @since 4.0
 * @uses  get_query_var()
 * @uses  get_term_link()
 *
 * @param  int|string|object $tag Optional. Either a term ID, term object or term slug.  Defaults to the main product tag.
 * @return string
 */
function wpsc_get_product_tag_permalink( $tag = '' ) {
	if ( ! $tag )
		$tag = get_query_var( 'term' );

	if ( is_numeric( $tag ) )
		$tag = absint( $tag );

	$link = get_term_link( $tag, 'product_tag' );

	return apply_filters( 'wpsc_get_product_tag_permalink', $link, $tag );
}

/**
 * Return the title of the product catalog page.
 *
 * @since 4.0
 * @uses  apply_filters() Applies 'wpsc_get_product_catalog_title' filter.
 * @uses  get_post_type_object()
 *
 * @return string
 */
function wpsc_get_product_catalog_title() {
	$post_type_object = get_post_type_object( 'wpsc-product' );
	return apply_filters( 'wpsc_get_product_catalog_title', $post_type_object->labels->name );
}

/**
 * Return the name of a product category.
 *
 * @since 4.0
 * @uses  apply_filters() Applies 'wpsc_get_product_category_name' filter.
 * @uses  get_query_var()
 * @uses  get_term()
 * @uses  get_term_by()
 * @uses  is_wp_error()
 *
 * @param  int|string|object $term Optional. Either a term ID, term object or term slug.
 * @return string
 */
function wpsc_get_product_category_name( $term = '' ) {
	if ( empty( $term ) )
		$term = get_query_var( 'term' );

	if ( ! is_object( $term ) ) {
		if ( is_int( $term ) ) {
			$term = get_term( $term, 'wpsc_product_category' );
		} else {
			$term = get_term_by( 'slug', $term, 'wpsc_product_category' );
		}
	}

	if ( ! is_object( $term ) || is_wp_error( $term ) )
		return '';

	return apply_filters( 'wpsc_get_product_category_name', $term->name, $term );
}

/**
 * Return the name of a product tag.
 *
 * @since 4.0
 * @uses  apply_filters() Applies 'wpsc_get_product_tag_name' filter.
 * @uses  get_query_var()
 * @uses  get_term()
 * @uses  get_term_by()
 * @uses  is_wp_error()
 *
 * @param  int|string|object $term Optional. Either a term ID, term object or term slug.
 * @return string
 */
function wpsc_get_product_tag_name( $term = '' ) {
	if ( empty( $term ) )
		$term = get_query_var( 'term' );

	if ( ! is_object( $term ) ) {
		if ( is_int( $term ) ) {
			$term = get_term( $term, 'product_tag' );
		} else {
			$term = get_term_by( 'slug', $term, 'product_tag' );
		}
	}

	if ( ! is_object( $term ) || is_wp_error( $term ) )
		return '';

	return apply_filters( 'wpsc_get_product_tag_name', $term->name, $term );
}

/**
 * Output the breadcrumb of a shop page.
 *
 * See {@link wpsc_get_breadcrumb()} for a list of available options to customize the output.
 *
 * @since 4.0
 * @uses  wpsc_get_breadcrumb()
 * @uses  wpsc_theme_product_breaccrumb_after()
 * @uses  wpsc_theme_product_breadcrumb_before()
 *
 * @param  string $args Optional. Options to customize the output. Defaults to ''.
 */
function wpsc_breadcrumb( $args = '' ) {
	wpsc_theme_product_breadcrumb_before();
	echo wpsc_get_breadcrumb( $args );
	wpsc_theme_product_breadcrumb_after();
}

/**
 * Return the HTML for the breadcrumb of a shop page.
 *
 * The available options to customize the output include:
 *     'before'          - HTML before the breadcrumb. Defaults to '<p class="%s">'. The %s
 *                         placeholder will be replaced by the class attribute.
 *     'after'           - HTML after the breadcrumb. Defaults to '</p>'.
 *     'separator'       - The separator between breadcrumb items. Defaults to &rsaquo; .
 *     'padding'         - The number of spaces you want to insert to the both sides of the
 *                         separator. Defaults to 1.
 *     'include_home'    - Whether to include a link to home in the breadcrumb. Defaults to true.
 *     'home_text'       - The text for the home link. Defaults to "Home".
 *     'include_catalog' - Whether to include a link to the main catalog in the breadcrumb.
 *                         Defaults to true.
 *     'catalog_text'    - The text for the catalog link. Defaults to "Products".
 *     'include_current' - Whether to include a link to the current page in the breadcrumb.
 *                         Defaults to true.
 *     'current_text'    - The text for the current link. Defaults to the category / product title.
 *
 * @since 4.0
 * @uses  apply_filters()      Applies 'wpsc_breadcrumb_array'     filter.
 * @uses  apply_filters()      Applies 'wpsc_breadcrumb_class'     filter.
 * @uses  apply_filters()      Applies 'wpsc_breadcrumb_separator' filter.
 * @uses  apply_filters()      Applies 'wpsc_get_breadcrumb'       filter.
 * @uses  get_option()         Get the 'page_on_front' option.
 * @uses  get_queried_object()
 * @uses  get_term_field()
 * @uses  get_the_title()
 * @uses  wp_get_object_terms()
 * @uses  wp_parse_args()
 * @uses  wpsc_is_product_catalog()
 * @uses  wpsc_get_catalog_url()
 * @uses  wpsc_get_product_catalog_title()
 * @uses  wpsc_get_product_category_name()
 * @uses  wpsc_get_product_category_permalink()
 * @uses  wpsc_get_product_tag_name()
 * @uses  wpsc_get_product_title()
 * @uses  wpsc_is_product_category()
 * @uses  wpsc_is_product_tag()
 * @uses  wpsc_is_single_product()
 *
 * @param  string|array $args Optional. Query string or array of options. Defaults to ''.
 * @return string
 */
function wpsc_get_breadcrumb( $args = '' ) {
	$args = wp_parse_args( $args );

	$pre_front_text = $pre_current_text = '';

	// No custom home text
	if ( empty( $args['home_text'] ) ) {

		// Set home text to page title
		if ( $front_id = get_option( 'page_on_front' ) ) {
			$pre_front_text = get_the_title( $front_id );

		// Default to 'Home'
		} else {
			$pre_front_text = __( 'Home', 'wpsc' );
		}
	}

	// No custom catalog text
	if ( empty( $args['catalog_text'] ) ) {
		$pre_catalog_text = wpsc_get_product_catalog_title();
	}

	$parent = null;

	if ( wpsc_is_single_product() ) {
		$pre_current_text   = wpsc_get_product_title();
		$product_categories = wp_get_object_terms( wpsc_get_product_id(), 'wpsc_product_category' );

		// if there are multiple product categories associated with this product, choose the most
		// appropriate one based on the context
		if ( ! empty( $product_categories ) ) {
			$parent = $product_categories[0];
			$context = get_query_var( 'wpsc_product_category' );
			if ( $context && in_array( $context, wp_list_pluck( $product_categories, 'slug' ) ) ) {
				$parent = get_term_by( 'slug', $context, 'wpsc_product_category' );
			}
		}
	} elseif ( wpsc_is_product_catalog() ) {
		$pre_current_text = wpsc_get_product_catalog_title();
	} elseif ( wpsc_is_product_category() ) {
		$pre_current_text = wpsc_get_product_category_name();
		$term             = get_queried_object();
		if ( $term->parent )
			$parent = get_term( $term->parent, 'wpsc_product_category' );
	} elseif ( wpsc_is_product_tag() ) {
		$pre_current_text = wpsc_get_product_tag_name();
	}

	$defaults = array(
		// HTML
		'before'          => '<p class="%s">',
		'after'           => '</p>',
		'separator'       => is_rtl() ? __( '&lsaquo;', 'wpsc' ) : __( '&rsaquo;', 'wpsc' ),
		'padding'         => 1,

		// Home
		'include_home'    => true,
		'home_text'       => $pre_front_text,

		// Catalog
		'include_catalog' => true,
		'catalog_text'    => $pre_catalog_text,

		// Current
		'include_current' => true,
		'current_text'    => $pre_current_text,
	);

	$r = array_merge( $defaults, $args );
	extract( $r );

	$class = apply_filters( 'wpsc_breadcrumb_class', 'wpsc-breadcrumb' );
	$before = sprintf( $before, $class );

	// Pad the separator
	if ( !empty( $padding ) )
		$separator = str_pad( $separator, strlen( $separator ) + ( (int) $padding * 2 ), ' ', STR_PAD_BOTH );

	$separator   = apply_filters( 'wpsc_breadcrumb_separator', $separator, $padding );
	$breadcrumbs = array();

	if ( $include_current && ! empty( $current_text ) )
		$breadcrumbs[] = '<span class="wpsc-breadcrumb-item wpsc-breadcrumb-current">' . $current_text . '</span>';

	$ancestors = array();
	if ( $parent ) {
		while ( ! is_wp_error( $parent ) && is_object( $parent ) && $parent->parent ) {
			if ( in_array( $parent->parent, $ancestors ) )
				break;

			$ancestors[] = $parent->parent;
			$breadcrumbs[] = '<a class="wpsc-breadcrumb-item wpsc-breadcrumb-ancestors" href="' . wpsc_get_product_category_permalink( $parent ) . '">' . esc_html( $parent->name ) . '</a>';
			$parent = get_term( $parent->parent, 'wpsc_product_category' );
		}
	}

	if ( $include_catalog && ! empty( $catalog_text ) )
		$breadcrumbs[] = '<a class="wpsc-breadcrumb-item wpsc-breadcrumb-catalog" href="' . wpsc_get_catalog_url() . '">' . $catalog_text . '</a>';

	if ( $include_home && ! empty( $home_text ) )
		$breadcrumbs[] = '<a class="wpsc-breadcrumb-item wpsc-breadcrumb-home" href="' . trailingslashit( home_url() ) . '">' . $home_text . '</a>';

	$breadcrumbs = apply_filters( 'wpsc_breadcrumb_array', array_reverse( $breadcrumbs ), $r );
	$html        = $before . implode( $separator, $breadcrumbs ) . $after;

	return apply_filters( 'wpsc_get_breadcrumb', $html, $breadcrumbs, $r );
}