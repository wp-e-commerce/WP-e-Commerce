<?php
/**
 * Output the breadcrumb of a shop page.
 *
 * See {@link wpsc_get_breadcrumb()} for a list of available options to customize the output.
 *
 * @since 4.0
 * @uses  wpsc_get_breadcrumb()
 * @uses  wpsc_product_breadcrumb_after()
 * @uses  wpsc_product_breadcrumb_before()
 *
 * @param  string $args Optional. Options to customize the output. Defaults to ''.
 */
function wpsc_breadcrumb( $args = '' ) {
	wpsc_product_breadcrumb_before();
	echo wpsc_get_breadcrumb( $args );
	wpsc_product_breadcrumb_after();
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
		'before'          => '<ul class="%s">',
		'after'           => '</ul>',
		'before_item'     => '<li class="%s">',
		'after_item'      => '</li>',
		'before_divider'  => '<span class="%s">',
		'after_divider'   => '</span>',
		'divider'         => '/',
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

	$defaults = apply_filters( 'wpsc_get_breadcrumb_default_args', $defaults );

	$r = array_merge( $defaults, $args );
	extract( $r );

	$before         = sprintf( $before        , 'wpsc-breadcrumb'         );
	$before_item    = sprintf( $before_item   , 'wpsc-breadcrumb-item'    );
	$before_divider = sprintf( $before_divider, 'wpsc-breadcrumb-divider' );

	if ( $padding ) {
		$length = strlen( $divider ) + $padding * 2;
		$padding = str_repeat( "&nbsp;", $padding );
		$divider = $padding . $divider . $padding;
	}
	$divider        = $before_divider . $divider . $after_divider;

	$breadcrumbs = array();

	if ( $include_current && ! empty( $current_text ) ) {
		$before_current_item = sprintf( $before_item, 'wpsc-breadcrumb-item wpsc-breadcrumb-current' );
		$breadcrumbs[] = $before_current_item . $current_text . $after_item;
	}

	$ancestors = array();
	if ( $parent ) {
		while ( ! is_wp_error( $parent ) && is_object( $parent ) && $parent->parent ) {
			if ( in_array( $parent->parent, $ancestors ) )
				break;

			$ancestors[] = $parent->parent;
			$before_this_item = sprintf( $before_item, 'wpsc-breadcrumb-item wpsc-breadcrumb-ancestor' );
			$link = '<a href="' . wpsc_get_product_category_permalink( $parent ) . '">' . esc_html( $parent->name ) . '</a>';
			$breadcrumbs[] = $before_this_item . $link . $divider . $after_item;
			$parent = get_term( $parent->parent, 'wpsc_product_category' );
		}
	}

	if ( $include_catalog && ! empty( $catalog_text ) && ! wpsc_is_product_catalog() ) {
		$before_this_item = sprintf( $before_item, 'wpsc-breadcrumb-item wpsc-breadcrumb-catalog' );
		$link = '<a href="' . wpsc_get_catalog_url() . '">' . $catalog_text . '</a>';
		$breadcrumbs[] = $before_this_item . $link . $divider . $after_item;
	}

	if ( $include_home && ! empty( $home_text ) && ! is_home() ) {
		$before_this_item = sprintf( $before_item, 'wpsc-breadcrumb-item wpsc-breadcrumb-home' );
		$link = '<a href="' . trailingslashit( home_url() ) . '">' . $home_text . '</a>';
		$breadcrumbs[] = $before_this_item . $link . $divider . $after_item;
	}

	$breadcrumbs = apply_filters( 'wpsc_breadcrumb_array', array_reverse( $breadcrumbs ), $r );
	$html        = $before . implode( '', $breadcrumbs ) . $after;

	return apply_filters( 'wpsc_get_breadcrumb', $html, $breadcrumbs, $r );
}

function wpsc_user_messages( $args = '' ) {
	echo wpsc_get_user_messages( $args );
}

function wpsc_get_user_messages( $args = '' ) {
	$defaults = array(
		'context'             => 'main',
		'types'               => 'all',
		'before_message_list' => '<div class="%s">',
		'after_message_list'  => '</div>',
		'before_message_item' => '<p>',
		'after_message_item'  => '</p>',
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	$message_collection = WPSC_Message_Collection::get_instance();
	$messages = $message_collection->query( $types, $context );

	$output = '';

	foreach ( $messages as $type => $type_messages ) {
		$classes = "wpsc-alert wpsc-alert-block wpsc-alert-{$type}";
		if ( $type == 'validation' )
			$classes .= ' wpsc-alert-error';

		$output .= sprintf( $before_message_list, $classes );
		foreach ( $type_messages as $message ) {
			$output .= $before_message_item;
			$output .= apply_filters( 'wpsc_inline_validation_error_message', $message );
			$output .= $after_message_item;
		}
		$output .= $after_message_list;
	}

	return $output;
}

function wpsc_inline_validation_error( $field, $args = '' ) {
	global $wpsc_page_instance;

	$defaults = array(
		'before' => '<br /><span class="wpsc-inline-validation-error">',
		'after'  => '</span>',
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	$error = $wpsc_page_instance->get_validation_errors();
	$message = $error->get_error_message( $field );
	if ( $message !== '' ) {
		echo $before;
		echo apply_filters( 'wpsc_inline_validation_error_message', $message, $field, $error );
		echo $after;
	}
}

function wpsc_get_cart_title() {
	return apply_filters( 'wpsc_get_cart_title', __( 'Cart', 'wpsc' ) );
}

function wpsc_get_login_title() {
	return apply_filters( 'wpsc_get_login_title', __( 'Login', 'wpsc' ) );
}

function wpsc_get_register_title() {
	return apply_filters( 'wpsc_get_register_title', __( 'Register', 'wpsc' ) );
}

function wpsc_keep_shopping_button() {
	$keep_shopping_url = isset( $_REQUEST['_wp_http_referer'] ) ? esc_attr( $_REQUEST['_wp_http_referer'] ) : wpsc_get_catalog_url();
	?>
	<a class="wpsc-button wpsc-back-to-shopping" href="<?php echo esc_url( $keep_shopping_url ); ?>"><?php esc_html_e( 'Keep Shopping', 'wpsc' ); ?></a>
	<?php
}