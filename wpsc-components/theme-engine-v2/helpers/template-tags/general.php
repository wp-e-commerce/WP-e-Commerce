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
	do_action( 'wpsc_product_breadcrumb_before' );
	echo wpsc_get_breadcrumb( $args );
	do_action( 'wpsc_product_breadcrumb_after' );
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
 *     'include_store' - Whether to include a link to the main store in the breadcrumb.
 *                         Defaults to true.
 *     'store_text'    - The text for the store link. Defaults to "Products".
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
 * @uses  wpsc_is_store()
 * @uses  wpsc_get_store_url()
 * @uses  wpsc_get_store_title()
 * @uses  wpsc_get_product_category_name()
 * @uses  wpsc_get_product_category_permalink()
 * @uses  wpsc_get_product_tag_name()
 * @uses  wpsc_get_product_title()
 * @uses  wpsc_is_product_category()
 * @uses  wpsc_is_product_tag()
 * @uses  wpsc_is_single()
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
			$pre_front_text = __( 'Home', 'wp-e-commerce' );
		}
	}

	// No custom store text
	if ( empty( $args['store_text'] ) ) {
		$pre_store_text = wpsc_get_store_title();
	} else {
		$pre_store_text = $args['store_text'];
	}

	$parent = null;

	if ( wpsc_is_single() ) {
		// if this is a single product, find its product category
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
	} elseif ( wpsc_is_store() ) {
		// if this is the main store, default the "current_text" argument to
		// the store title

		$pre_current_text = wpsc_get_store_title();
	} elseif ( wpsc_is_product_category() ) {
		// if this is a product category, find its parent category if it has
		// one
		$pre_current_text = wpsc_get_product_category_name();
		$term             = get_queried_object();

		if ( $term->parent ) {
			$parent = get_term( $term->parent, 'wpsc_product_category' );
		}
	} elseif ( wpsc_is_product_tag() ) {
		// if this is a product tag, set "current_text" to the tag name by default
		$pre_current_text = wpsc_get_product_tag_name();
	} elseif ( wpsc_is_customer_account() ) {
		// if this is the customer account page

		$c = _wpsc_get_current_controller();

		// if we're displaying an order's details, set the parent to the
		// "Your Account" page
		if ( $c->order_id ) {
			$pre_current_text = $c->order_id;
			$parent = array(
				array(
					'title' => __( 'Your Account', 'wp-e-commerce' ),
					'url'   => wpsc_get_customer_account_url()
				),
			);
		} else {
			// otherwise, set the "current_text" argument to "Your Account"
			$pre_current_text = __( 'Your Account', 'wp-e-commerce' );
		}
	}

	$defaults = array(
		// HTML
		'before'          => '<ul class="%s">',
		'after'           => '</ul>',
		'before_item'     => '<li class="%s">',
		'after_item'      => '</li>',
		'before_divider'  => '<span class="%s">',
		'after_divider'   => '</span>',
		'divider'         => '&raquo;',
		'padding'         => 0,

		// Home
		'include_home'    => true,
		'home_text'       => $pre_front_text,

		// Catalog
		'include_store' => true,
		'store_text'    => $pre_store_text,

		// Current
		'include_current' => true,
		'current_text'    => $pre_current_text,
	);

	$defaults = apply_filters( 'wpsc_get_breadcrumb_default_args', $defaults );

	$r = array_merge( $defaults, $args );
	extract( $r );

	// replace placeholders in arguments
	$before         = sprintf( $before        , 'wpsc-breadcrumb'         );
	$before_item    = sprintf( $before_item   , 'wpsc-breadcrumb-item'    );
	$before_divider = sprintf( $before_divider, 'wpsc-breadcrumb-divider' );

	// if padding is set, prepare the length, padding string and divider
	if ( $padding ) {
		$length = strlen( html_entity_decode( $divider, ENT_COMPAT, 'UTF-8' ) ) + $padding * 2;
		$padding = str_repeat( "&nbsp;", $length );
		$divider = $padding . $divider . $padding;
	}
	$divider        = $before_divider . $divider . $after_divider;

	// generate the breadcrumb array in reverse
	$breadcrumbs = array();

	// include current page in breadcrumb
	if ( $include_current && ! empty( $current_text ) ) {
		$before_current_item = sprintf( $before_item, 'wpsc-breadcrumb-item wpsc-breadcrumb-current' );
		$breadcrumbs[] = $before_current_item . $current_text . $after_item;
	}

	// include ancestors in breadcrumb
	$ancestors = array();

	// if the current page has a parent
	if ( $parent ) {
		if ( is_array( $parent ) ) {
			// if $parent is an array, then use the 'url' and 'title' elements
			foreach ( $parent as $p ) {
				$before_this_item = sprintf( $before_item, 'wpsc-breadcrumb-item wpsc-breadcrumb-ancestor' );
				$link = '<a href="' . esc_url( $p['url'] ) . '">' . esc_html( $p['title'] ) . '</a>';
				$breadcrumbs[] = $before_this_item . $link . $divider . $after_item;
			}
		} else {
			// if $parent is a term object, recursively find all ancestors and
			// include them in the breadcrumb array
			while ( ! is_wp_error( $parent ) && is_object( $parent ) ) {
				if ( in_array( $parent->parent, $ancestors ) )
					break;

				$ancestors[] = $parent->parent;
				$before_this_item = sprintf( $before_item, 'wpsc-breadcrumb-item wpsc-breadcrumb-ancestor' );
				$link = '<a href="' . wpsc_get_product_category_permalink( $parent ) . '">' . esc_html( $parent->name ) . '</a>';
				$breadcrumbs[] = $before_this_item . $link . $divider . $after_item;
				$parent = get_term( $parent->parent, 'wpsc_product_category' );
			}
		}
	}

	// include the store link if this is not the main store itself
	if ( $include_store && ! empty( $store_text ) && ! wpsc_is_store() ) {
		$before_this_item = sprintf( $before_item, 'wpsc-breadcrumb-item wpsc-breadcrumb-store' );
		$link = '<a href="' . wpsc_get_store_url() . '">' . $store_text . '</a>';
		$breadcrumbs[] = $before_this_item . $link . $divider . $after_item;
	}

	// include the home link
	if ( $include_home && ! empty( $home_text ) && ! is_home() && ! wpsc_get_option( 'store_as_front_page' ) ) {
		$before_this_item = sprintf( $before_item, 'wpsc-breadcrumb-item wpsc-breadcrumb-home' );
		$link = '<a href="' . trailingslashit( home_url() ) . '">' . $home_text . '</a>';
		$breadcrumbs[] = $before_this_item . $link . $divider . $after_item;
	}

	// reverse the breadcrumb array
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

		if ( $type == 'validation' ) {
			$classes .= ' wpsc-alert-error';
		}

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

/**
 * Return the HTML for the Keep Shopping button
 *
 * @since  0.1
 * @return string HTML output
 */
function wpsc_get_keep_shopping_button() {
	$keep_shopping_url = isset( $_REQUEST['_wp_http_referer'] )
	                     ? esc_attr( $_REQUEST['_wp_http_referer'] )
	                     : wpsc_get_store_url();

	$title = apply_filters(
		'wpsc_keep_shopping_button_title',
		__( 'Keep Shopping', 'wp-e-commerce' )
	);

	$button = sprintf(
		'<a class="wpsc-button wpsc-keep-shopping-button" href="%1$s">%2$s</a>',
		esc_url( $keep_shopping_url ),
		$title
	);

	return apply_filters( 'wpsc_get_keep_shopping_button', $button );
}

/**
 * Display the "Keep Shopping" button
 *
 * @since  0.1
 * @uses wpsc_get_keep_shopping_button()
 */
function wpsc_keep_shopping_button() {
	echo wpsc_get_keep_shopping_button();
}

function wpsc_checkout_steps() {
	echo wpsc_get_checkout_steps();
}

function wpsc_get_checkout_steps() {

	if ( _wpsc_get_current_controller_name() != 'checkout' ) {
		return '';
	}

	$wizard = WPSC_Checkout_Wizard::get_instance();
	$steps  = $wizard->steps;

	$output = '<ul class="wpsc-wizard">';
	$step_count = 1;

	foreach ( $steps as $step => $title ) {
		$classes = array( 'wpsc-wizard-step wpsc-wizard-step-' . $step );

		if ( $wizard->is_active( $step ) ) {
			$classes[] = 'active';
		}

		if ( $wizard->is_disabled( $step ) ) {
			$classes[] = 'disabled';
		} elseif ( $wizard->is_completed( $step ) ) {
			$classes[] = 'completed';
		} else {
			$classes[] = 'pending';
		}

		$classes[] = 'split-' . count( $steps );
		$output   .= '<li class="' . implode( ' ', $classes ) . '">';

		if ( ! $wizard->is_completed( $step ) ) {
			$output .= '<span>';
		} else {
			$output .= '<a href="' . wpsc_get_checkout_url( $step ) . '">';
		}

		$output .= '<span class="step">' . $step_count . '.</span> ' . $title;

		if ( ! $wizard->is_completed( $step ) ) {
			$output .= '</span>';
		} else {
			$output .= '</a>';
		}

		$output .= '</li>';

		$step_count ++;
	}
	$output .= '</ul>';
	return $output;
}

function wpsc_get_checkout_order_preview() {
	require_once( WPSC_TE_V2_CLASSES_PATH . '/cart-item-table.php' );
	$cart_item_table = WPSC_Cart_Item_Table::get_instance();
	ob_start();
	$cart_item_table->display();
	return apply_filters( 'wpsc_get_checkout_order_preview', ob_get_clean() );
}

function wpsc_checkout_order_preview() {
	echo wpsc_get_checkout_order_preview();
}

function wpsc_get_customer_account_tabs() {
	if ( _wpsc_get_current_controller_name() != 'customer-account' ) {
		return '';
	}

	$active_tab = _wpsc_get_current_controller_slug();

	$tabs = apply_filters( 'wpsc_customer_account_tabs', array(
		'orders'          => _x( 'Orders'          , 'customer account tab', 'wp-e-commerce' ),
		'digital-content' => _x( 'Digital Contents', 'customer account tab', 'wp-e-commerce' ),
		'settings'        => _x( 'Settings'        , 'customer account tab', 'wp-e-commerce' )
	), $active_tab );

	$output = sprintf( '<ul class="wpsc-tabs wpsc-customer-account-tabs">' );;

	foreach ( $tabs as $slug => $tab ) {
		$item_classes = array( 'wpsc-tab-item' );

		if ( $slug == $active_tab ) {
			$item_classes[] = 'active';
		}

		$output .= sprintf( '<li class="%s">', implode( ' ', $item_classes ) );

		$output .= sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( wpsc_get_customer_account_url( $slug ) ),
			esc_html( $tab )
		);
		$output .= '</li>';
	}

	$output .= '</ul>';

	return $output;
}

function wpsc_customer_account_tabs() {
	echo wpsc_get_customer_account_tabs();
}

function wpsc_get_customer_orders_statuses() {
	if (    _wpsc_get_current_controller_name() != 'customer-account'
	     || _wpsc_get_current_controller_slug() != 'orders' )
		return '';
	$controller = _wpsc_get_current_controller();

	$view_labels = array(
		0 => _nx_noop( 'All <span class="count">(%s)</span>'       , 'All <span class="count">(%s)</span>'       , 'purchase logs', 'wp-e-commerce' ),
		1 => _nx_noop( 'Incomplete <span class="count">(%s)</span>', 'Incomplete <span class="count">(%s)</span>', 'purchase logs', 'wp-e-commerce' ),
		2 => _nx_noop( 'Received <span class="count">(%s)</span>'  , 'Received <span class="count">(%s)</span>'  , 'purchase logs', 'wp-e-commerce' ),
		3 => _nx_noop( 'Accepted <span class="count">(%s)</span>'  , 'Accepted <span class="count">(%s)</span>'  , 'purchase logs', 'wp-e-commerce' ),
		4 => _nx_noop( 'Dispatched <span class="count">(%s)</span>', 'Dispatched <span class="count">(%s)</span>', 'purchase logs', 'wp-e-commerce' ),
		5 => _nx_noop( 'Closed <span class="count">(%s)</span>'    , 'Closed <span class="count">(%s)</span>'    , 'purchase logs', 'wp-e-commerce' ),
		6 => _nx_noop( 'Declined <span class="count">(%s)</span>'  , 'Declined <span class="count">(%s)</span>'  , 'purchase logs', 'wp-e-commerce' ),
	);

	$views = array();

	foreach ( $controller->status_filters as $status => $count ) {
		if ( ! isset( $view_labels[ $status ] ) || ( $status && ! $count ) )
			continue;

		$text = sprintf(
			translate_nooped_plural( $view_labels[ $status ], $count, 'wp-e-commerce' ),
			number_format_i18n( $count )
		);

		$url = ( $status )
		       ? wpsc_get_customer_account_url( 'orders/status/' . $status )
		       : wpsc_get_customer_account_url();
		$link = '<a href="' . esc_url( $url ) . '">' . $text . '</a>';

		$views[$status] = '<span class="wpsc-order-status-' . $status . '">' . $link . '</span>';
	}

	if ( count( $views ) == 2 ) {
		unset( $views[1] );
	}

	$output = '<div class="wpsc-order-statuses">';
	$output .= implode( '<span class="wpsc-order-status-separator"> | </span>', $views );
	$output .= '</div>';

	return $output;
}

function wpsc_customer_orders_statuses() {
	echo wpsc_get_customer_orders_statuses();
}

function wpsc_get_customer_orders_list() {

	if (    _wpsc_get_current_controller_name() != 'customer-account'
	     || _wpsc_get_current_controller_slug() != 'orders' )
		return '';

	$table = WPSC_Orders_Table::get_instance();
	ob_start();
	$table->display();
	return ob_get_clean();
}

function wpsc_customer_orders_list() {
	echo wpsc_get_customer_orders_list();
}

function wpsc_get_customer_orders_pagination_links( $args = array() ) {
	global $wp_rewrite;

	if (    _wpsc_get_current_controller_name() != 'customer-account'
	     || _wpsc_get_current_controller_slug() != 'orders' )
		return '';

	$controller = _wpsc_get_current_controller();

	$base = $controller->get_current_pagination_base();

	if ( $wp_rewrite->using_permalinks() ) {
		$format = 'page/%#%';
	} else {
		$format = '&page=%#%';
	}

	$defaults = array(
		'base'      => trailingslashit( $base ) . '%_%',
		'format'    => $format,
		'total'     => $controller->total_pages,
		'current'   => $controller->current_page,
		'prev_text' => is_rtl() ? __( '&rarr;', 'wp-e-commerce' ) : __( '&larr;', 'wp-e-commerce' ),
		'next_text' => is_rtl() ? __( '&larr;', 'wp-e-commerce' ) : __( '&rarr;', 'wp-e-commerce' ),
		'end_size'  => 3,
		'mid_size'  => 2,
	);

	$defaults = apply_filters( 'wpsc_get_customer_orders_pagination_links', $defaults );
	$r = wp_parse_args( $args, $defaults );

	return apply_filters( 'wpsc_get_product_pagination_links', paginate_links( $r ) );
}

function wpsc_customer_orders_pagination_links( $args = array() ) {
	echo wpsc_get_customer_orders_pagination_links( $args );
}

function wpsc_customer_orders_pagination_count() {
	$controller   = _wpsc_get_current_controller();
	$from         = ( $controller->current_page - 1 ) * $controller->per_page + 1;
	$to           = $from + $controller->per_page - 1;

	if ( $to > $controller->total_items ) {
		$to = $controller->total_items;
	}

	if ( $controller->total_items > 1 ) {
		if ( $from == $to ) {
			$output = sprintf( __( 'Viewing order %1$s (of %2$s total)', 'wp-e-commerce' ), $from, $controller->total_items );
		} elseif ( $controller->total_pages === 1 ) {
			$output = sprintf( __( 'Viewing %1$s orders', 'wp-e-commerce' ), $controller->total_items );
		} else {
			$output = sprintf( __( 'Viewing %1$s orders - %2$s through %3$s (of %4$s total)', 'wp-e-commerce' ), $controller->count_items, $from, $to, $controller->total_items );
		}
	} else {
		$output = sprintf( __( 'Viewing %1$s order', 'wp-e-commerce' ), $controller->total_items );
	}

	// Filter and return
	echo apply_filters( 'wpsc_customer_orders_pagination_count', $output );
}

function wpsc_get_customer_orders_pagination( $args = array() ) {
	ob_start();
	?>
	<div class="wpsc-pagination">
		<div class="wpsc-pagination-links">
			<?php wpsc_customer_orders_pagination_links( $args ); ?>
		</div>
		<div class="wpsc-pagination-count">
			<?php wpsc_customer_orders_pagination_count(); ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

function wpsc_customer_orders_pagination( $args = array() ) {
	echo wpsc_get_customer_orders_pagination( $args );
}

function wpsc_get_customer_account_order_details() {
	$c      = _wpsc_get_current_controller();
	$fields = $c->form->get_fields();
	include_once( WPSC_TE_V2_SNIPPETS_PATH . '/user-account-order-details.php' );
}

function wpsc_customer_account_order_details() {
	echo wpsc_get_customer_account_order_details();
}

function wpsc_get_customer_account_cart_items() {
	$c = _wpsc_get_current_controller();
	ob_start();
	$c->cart_item_table->display();
	return ob_get_clean();
}

function wpsc_customer_account_cart_items() {
	echo wpsc_get_customer_account_cart_items();
}

function wpsc_get_customer_account_order_date( $format = false ) {
	if ( ! $format ) {
		$format = get_option( 'date_format' );
	}

	$c = _wpsc_get_current_controller();
	return date_i18n( $format, $c->log->get( 'date' ) );
}

function wpsc_customer_account_order_date() {
	echo wpsc_get_customer_account_order_date();
}

function wpsc_get_customer_account_digital_contents() {
	$table = WPSC_Digital_Contents_Table::get_instance();
	ob_start();
	$table->display();
	return ob_get_clean();
}

function wpsc_customer_account_digital_contents() {
	echo wpsc_get_customer_account_digital_contents();
}

/**
 * Return Customer Details
 *
 * @return string $output Customer Details
 */
function wpsc_get_checkout_customer_details() {
	$output = '<h4>Buyer Details</h4>';
	$output .= apply_filters( 'wpsc_review_order_buyers_details', '' );
	$output .= '<h4>Shipping Details</h4>';
	$output .= apply_filters( 'wpsc_review_order_shipping_details', '' );

	return $output;
}

/**
 * Print Customer Details
 *
 * @return void
 */
function wpsc_checkout_customer_details() {
	echo wpsc_get_checkout_customer_details();
}

/**
 * Adds a UI for allowing guests to create an account on checkout.
 *
 * This function is hooked into the payment method args in WPSC_Controller_Checkout::payment().
 * Handling of the user account creation is in WPSC_Controller_Checkout::create_account.
 *
 * @param array $args Array of arguments for Forms API.
 *
 * @since  4.0
 * @return array $args Array of arguments for Forms API.
 */
function wpsc_create_account_checkbox( $args ) {
	ob_start();
?>
	<div class="wpsc-form-actions">
		<p><strong class="wpsc-large"><?php _e( 'Create an Account?', 'wp-e-commerce' ); ?></strong></p>
		<label><input type="checkbox" name="wpsc_create_account" /> <?php _e( 'Creating an account keeps your order history, user profile, and more all in one place. We’ll email you account information right away.', 'wp-e-commerce' ); ?></label>
	</div>
<?php
	$output = ob_get_clean();

	if ( ! isset( $args['before_form_actions'] ) ) {
		$args['before_form_actions'] = $output;
	} else {
		$args['before_form_actions'] = $output . $args['before_form_actions'];
	}

	return $args;
}

