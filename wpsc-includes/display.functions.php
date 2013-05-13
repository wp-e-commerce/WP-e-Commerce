<?php

/**
 * WP eCommerce display functions
 *
 * These are functions for the wp-eCommerce themngine, template tags and shortcodes
 *
 * @package wp-e-commerce
 * @since 3.7
 */

/**
 * wpsc buy now button code products function
 * Sorry about the ugly code, this is just to get the functionality back, buy now will soon be overhauled, and this function will then be completely different
 * @return string - html displaying one or more products
 */
function wpsc_buy_now_button( $product_id, $replaced_shortcode = false ) {

	$product_id = absint($product_id);

	$product            = get_post( $product_id );
	$supported_gateways = array( 'wpsc_merchant_paypal_standard', 'paypal_multiple' );
	$selected_gateways  = get_option( 'custom_gateway_options' );

	if ( $replaced_shortcode )
		ob_start();

	if ( in_array( 'wpsc_merchant_paypal_standard', (array) $selected_gateways ) ) {
		if ( $product_id > 0 ) {

			$post_meta     = get_post_meta( $product_id, '_wpsc_product_metadata', true );
			$shipping      = isset( $post_meta['shipping'] ) ? $post_meta['shipping']['local'] : '';
			$price         = get_post_meta( $product_id, '_wpsc_price', true );
			$special_price = get_post_meta( $product_id, '_wpsc_special_price', true );

			if ( $special_price )
				$price = $special_price;

			if ( wpsc_uses_shipping ( ) ) {
				$handling = get_option( 'base_local_shipping' );
			} else {
				$handling = $shipping;
			}

			$has_variants = wpsc_product_has_variations( $product_id );

			$src     = apply_filters( 'wpsc_buy_now_button_src', _x( 'https://www.paypal.com/en_US/i/btn/btn_buynow_LG.gif', 'PayPal Buy Now Button', 'wpsc' ) );
			$classes = apply_filters( 'wpsc_buy_now_button_class', "wpsc-buy-now-form wpsc-buy-now-form-{$product_id}" );

			$button_html = sprintf( '<input%1$s class="wpsc-buy-now-button wpsc-buy-now-button-%2$s" type="image" name="submit" border="0" src="%3$s" alt="%4$s" />',
				disabled( $has_variants, true, false ),
				esc_attr( $product_id ),
				esc_url( $src ),
				esc_attr__( 'PayPal - The safer, easier way to pay online', 'wpsc' )
			);

			$button_html = apply_filters( 'wpsc_buy_now_button_html', $button_html, $product_id );
?>
			<form class="<?php echo esc_attr( sanitize_html_class( $classes, '' ) ); ?>" id="buy-now-product_<?php echo $product_id; ?>" target="paypal" action="<?php echo esc_url( home_url() ); ?>" method="post">
				<input type="hidden" name="wpsc_buy_now_callback" value="1" />
				<input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>" />
<?php
				if ( $has_variants ) :
					// grab the variation form fields here
					$wpsc_variations = new wpsc_variations( $product_id );
					while ( wpsc_have_variation_groups() ) : wpsc_the_variation_group();
						printf('<input type="hidden" class="variation-value" name="variation[%1$d]" id="%2$s" value="0"/>', wpsc_vargrp_id(), wpsc_vargrp_form_id() );
					endwhile;
				endif; /* END wpsc_product_has_variations */
?>
				<?php if ( get_option( 'multi_add' ) ) : ?>
					<label for="quantity"><?php esc_html_e( 'Quantity', 'wpsc' ); ?></label>
					<input type="text" size="4" id="quantity" name="quantity" value="" /><br />
				<?php else: ?>
					<input type="hidden" name="quantity" value="1" />
				<?php endif ?>
				<?php echo $button_html; ?>
				<img alt='' border='0' width='1' height='1' src='<?php echo esc_url( _x( 'https://www.paypal.com/en_US/i/scr/pixel.gif', 'PayPal Pixel', 'wpsc' ) ); ?>' />
			</form>
			<?php
		}
	}
	if ( $replaced_shortcode )
		return ob_get_clean();
}

/**
 * Displays products that were bought along with the product defined by $product_id.
 * This functionality will be deprecated and be provided by a plugin in a future version.
 */
function wpsc_also_bought( $product_id ) {
	global $wpdb;

	if ( get_option( 'wpsc_also_bought' ) == 0 ) {
		return '';
	}

	// To be made customiseable in a future release
	$also_bought_limit = 3;
	$element_widths = 96;
	$image_display_height = 96;
	$image_display_width = 96;

	// Filter will be used by a plugin to provide 'Also Bought' functionality when this is deprecated from core.
	// Filter is currently private and should not be used by plugin/theme devs as it may only be temporary.
	$output = apply_filters( '_wpsc_also_bought', '', $product_id );
	if ( ! empty( $output ) ) {
		return $output;
	}

	// If above filter returns output then the following is ignore and can be deprecated in future.
	$also_bought = $wpdb->get_results( $wpdb->prepare( "SELECT `" . $wpdb->posts . "`.* FROM `" . WPSC_TABLE_ALSO_BOUGHT . "`, `" . $wpdb->posts . "` WHERE `selected_product`= %d AND `" . WPSC_TABLE_ALSO_BOUGHT . "`.`associated_product` = `" . $wpdb->posts . "`.`id` AND `" . $wpdb->posts . "`.`post_status` IN('publish','protected') ORDER BY `" . WPSC_TABLE_ALSO_BOUGHT . "`.`quantity` DESC LIMIT $also_bought_limit", $product_id ), ARRAY_A );
	if ( is_array( $also_bought ) && count( $also_bought ) > 0 ) {
		$output .= '<h2 class="prodtitles wpsc_also_bought">' . __( 'People who bought this item also bought', 'wpsc' ) . '</h2>';
		$output .= '<div class="wpsc_also_bought">';
		foreach ( $also_bought as $also_bought_data ) {
			$output .= '<div class="wpsc_also_bought_item" style="width: ' . $element_widths . 'px;">';
			if ( get_option( 'show_thumbnails' ) == 1 ) {
				$image_path = wpsc_the_product_thumbnail( $image_display_width, $image_display_height, $also_bought_data['ID'] );
				if ( $image_path ) {
					$output .= '<a href="' . esc_attr( get_permalink( $also_bought_data['ID'] ) ) . '" class="preview_link" rel="' . esc_attr( sanitize_html_class( get_the_title( $also_bought_data['ID'] ) ) ) . '">';
					$output .= '<img src="' . esc_attr( $image_path ) . '" id="product_image_' . $also_bought_data['ID'] . '" class="product_image" />';
					$output .= '</a>';
				} else {
					if ( get_option( 'product_image_width' ) != '' ) {
						$width_and_height = 'width="' . $image_display_height . '" height="' . $image_display_height . '" ';
					} else {
						$width_and_height = '';
					}
					$output .= '<img src="' . WPSC_CORE_THEME_URL . '/wpsc-images/noimage.png" title="' . esc_attr( get_the_title( $also_bought_data['ID'] ) ) . '" alt="' . esc_attr( get_the_title( $also_bought_data['ID'] ) ) . '" id="product_image_' . $also_bought_data['ID'] . '" class="product_image" ' . $width_and_height . '/>';
				}
			}

			$output .= '<a class="wpsc_product_name" href="' . get_permalink( $also_bought_data['ID'] ) . '">' . get_the_title( $also_bought_data['ID'] ) . '</a>';
			if ( ! wpsc_product_is_donation( $also_bought_data['ID'] ) ) {
				// Ideally use the wpsc_the_product_price_display() function here but needs some tweaking
				$price = get_product_meta( $also_bought_data['ID'], 'price', true );
				$special_price = get_product_meta( $also_bought_data['ID'], 'special_price', true );
				if ( ! empty( $special_price ) ) {
					$output .= '<span style="text-decoration: line-through;">' . wpsc_currency_display( $price ) . '</span>';
					$output .= wpsc_currency_display( $special_price );
				} else {
					$output .= wpsc_currency_display( $price );
				}
			}
			$output .= '</div>';
		}
		$output .= '</div>';
		$output .= '<br clear="all" />';
	}
	return $output;
}

/**
 * Get the URL of the loading animation image.
 * Can be filtered using the wpsc_loading_animation_url filter.
 */
function wpsc_loading_animation_url() {
	return apply_filters( 'wpsc_loading_animation_url', WPSC_CORE_THEME_URL . 'wpsc-images/indicator.gif' );
}

function fancy_notifications() {
	return wpsc_fancy_notifications( true );
}
function wpsc_fancy_notifications( $return = false ) {
	static $already_output = false;

	if ( $already_output )
		return '';

	$output = "";
	if ( get_option( 'fancy_notifications' ) == 1 ) {
		$output = "";
		$output .= "<div id='fancy_notification'>\n\r";
		$output .= "  <div id='loading_animation'>\n\r";
		$output .= '<img id="fancy_notificationimage" title="' . esc_attr__( 'Loading', 'wpsc' ) . '" alt="' . esc_attr__( 'Loading', 'wpsc' ) . '" src="' . wpsc_loading_animation_url() . '" />' . __( 'Updating', 'wpsc' ) . "...\n\r";
		$output .= "  </div>\n\r";
		$output .= "  <div id='fancy_notification_content'>\n\r";
		$output .= "  </div>\n\r";
		$output .= "</div>\n\r";
	}

	$already_output = true;

	if ( $return )
		return $output;
	else
		echo $output;
}
add_action( 'wpsc_theme_footer', 'wpsc_fancy_notifications' );

function fancy_notification_content( $cart_messages ) {
	$output = '';
	foreach ( (array)$cart_messages as $cart_message ) {
		$output .= "<span>" . $cart_message . "</span><br />";
	}
	$output .= "<a href='" . get_option( 'shopping_cart_url' ) . "' class='go_to_checkout'>" . __( 'Go to Checkout', 'wpsc' ) . "</a>";
	$output .= "<a href='#' onclick='jQuery(\"#fancy_notification\").css(\"display\", \"none\"); return false;' class='continue_shopping'>" . __( 'Continue Shopping', 'wpsc' ) . "</a>";
	return $output;
}

/*
 * wpsc product url function, gets the URL of a product,
 * Deprecated, all parameters past the first unused. use get_permalink
 */

function wpsc_product_url( $product_id, $category_id = null, $escape = true ) {
	$post = get_post($product_id);
	if ( isset($post->post_parent) && $post->post_parent > 0) {
		return get_permalink($post->post_parent);
	} else {
		return get_permalink($product_id);
	}
}

function external_link( $product_id ) {
	$link = get_product_meta( $product_id, 'external_link', true );
	if ( !stristr( $link, 'http://' ) ) {
		$link = 'http://' . $link;
	}
	$target = wpsc_product_external_link_target( $product_id );
	$output .= "<input class='wpsc_buy_button' type='button' value='" . wpsc_product_external_link_text( $product_id, __( 'Buy Now', 'wpsc' ) ) . "' onclick='return gotoexternallink(\"$link\", \"$target\")'>";
	return $output;
}

/* 19-02-09
 * add cart button function used for php template tags and shortcodes
 */

function wpsc_add_to_cart_button( $product_id, $return = false ) {
	global $wpdb,$wpsc_variations;
	$output = '';
	if ( $product_id > 0 ) {
		// grab the variation form fields here
		$wpsc_variations = new wpsc_variations( $product_id );
		if ( $return )
			ob_start();
		?>
			<div class='wpsc-add-to-cart-button'>
				<form class='wpsc-add-to-cart-button-form' id='product_<?php echo esc_attr( $product_id ) ?>' action='' method='post'>
					<?php do_action( 'wpsc_add_to_cart_button_form_begin' ); ?>
					<div class='wpsc_variation_forms'>
						<?php while ( wpsc_have_variation_groups() ) : wpsc_the_variation_group(); ?>
							<p>
								<label for='<?php echo wpsc_vargrp_form_id(); ?>'><?php echo esc_html( wpsc_the_vargrp_name() ) ?>:</label>
								<select class='wpsc_select_variation' name='variation[<?php echo wpsc_vargrp_id(); ?>]' id='<?php echo wpsc_vargrp_form_id(); ?>'>
									<?php while ( wpsc_have_variations() ): wpsc_the_variation(); ?>
										<option value='<?php echo wpsc_the_variation_id(); ?>' <?php echo wpsc_the_variation_out_of_stock(); ?>><?php echo esc_html( wpsc_the_variation_name() ); ?></option>
									<?php endwhile; ?>
								</select>
							</p>
						<?php endwhile; ?>
					</div>
					<input type='hidden' name='wpsc_ajax_action' value='add_to_cart' />
					<input type='hidden' name='product_id' value='<?php echo $product_id; ?>' />
					<input type='submit' id='product_<?php echo $product_id; ?>_submit_button' class='wpsc_buy_button' name='Buy' value='<?php echo __( 'Add To Cart', 'wpsc' ); ?>'  />
					<?php do_action( 'wpsc_add_to_cart_button_form_end' ); ?>
				</form>
			</div>
		<?php

		if ( $return )
			return ob_get_clean();
	}
}

/**
 * wpsc_refresh_page_urls
 *
 * Refresh page urls when pages are updated
 *
 * @param  int    $post_id
 * @param  object $post
 * @uses   wpsc_update_permalink_slugs()
 * @return int    $post_id
 */
function wpsc_refresh_page_urls( $post_id, $post ) {

	if ( ! current_user_can( 'manage_options' ) )
		return;

	if ( 'page' != $post->post_type )
		return;

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return;

	if ( ! in_array( $post->post_status, array( 'publish', 'private' ) ) )
		return;

	wpsc_update_permalink_slugs();

	return $post_id;
}

add_action( 'save_post', 'wpsc_refresh_page_urls', 10, 2 );

/**
 * wpsc_obtain_the_title function, for replaacing the page title with the category or product
 * @return string - the new page title
 */
function wpsc_obtain_the_title() {
	global $wpdb, $wp_query, $wpsc_title_data;
	$output = null;
	$category_id = null;
	if( !isset( $wp_query->query_vars['wpsc_product_category']) &&  !isset( $wp_query->query_vars['wpsc-product']))
		return;

	if ( !isset( $wp_query->query_vars['wpsc_product_category'] ) && isset($wp_query->query_vars['wpsc-product']) )
		$wp_query->query_vars['wpsc_product_category'] = 0;


	if ( isset( $wp_query->query_vars['taxonomy'] ) && 'wpsc_product_category' ==  $wp_query->query_vars['taxonomy'] || isset($wp_query->query_vars['wpsc_product_category']))
		$category_id = wpsc_get_the_category_id($wp_query->query_vars['wpsc_product_category'],'slug');

	if ( $category_id > 0 ) {

		if ( isset( $wpsc_title_data['category'][$category_id] ) ) {
			$output = $wpsc_title_data['category'][$category_id];
		} else {
			$term = get_term($category_id, 'wpsc_product_category');
			$output = $term->name;
			$wpsc_title_data['category'][$category_id] = $output;
		}
	}

	if ( !isset( $_GET['wpsc-product'] ) )
		$_GET['wpsc-product'] = 0;

	if ( !isset( $wp_query->query_vars['wpsc-product'] ) )
		$wp_query->query_vars['wpsc-product'] = '';

	if ( isset( $wp_query->query_vars['wpsc-product'] ) || is_string( $_GET['wpsc-product'] ) ) {
		$product_name = $wp_query->query_vars['wpsc-product'];
		if ( isset( $wpsc_title_data['product'][$product_name] ) ) {
			$product_list = array( );
			$full_product_name = $wpsc_title_data['product'][$product_name];
		} else if ( $product_name != '' ) {
			$product_id = $wp_query->post->ID;
			$full_product_name = $wpdb->get_var( $wpdb->prepare( "SELECT `post_title` FROM `$wpdb->posts` WHERE `ID`= %d LIMIT 1", $product_id ) );
			$wpsc_title_data['product'][$product_name] = $full_product_name;
		} else {
			if(isset($_REQUEST['product_id'])){
				$product_id = absint( $_REQUEST['product_id'] );
				$product_name = $wpdb->get_var( $wpdb->prepare( "SELECT `post_title` FROM `$wpdb->posts` WHERE `ID`= %d LIMIT 1", $product_id ) );
				$full_product_name = $wpdb->get_var( $wpdb->prepare( "SELECT `post_title` FROM `$wpdb->posts` WHERE `ID`= %d LIMIT 1", $product_id ) );
				$wpsc_title_data['product'][$product_name] = $full_product_name;
			}else{
				//This has to exist, otherwise we would have bailed earlier.
				$category = $wp_query->query_vars['wpsc_product_category'];
				$cat_term = get_term_by('slug',$wp_query->query_vars['wpsc_product_category'], 'wpsc_product_category');
				$full_product_name = $cat_term->name;
			}
		}
		$output = $full_product_name;
	}

	if ( isset( $full_product_name ) && ($full_product_name != null) )
		$output = esc_html(  $full_product_name );
	$seperator = ' | ';
	$seperator = apply_filters('wpsc_the_wp_title_seperator' , $seperator);
	return $output.$seperator;
}

/**
 *	Return category or product description depending on queried item
 */
function wpsc_obtain_the_description() {

	$output = null;

	// Return Category Description
	if ( is_numeric( get_query_var('category_id') ) ) {
		$output = wpsc_get_categorymeta( get_query_var('category_id'), 'description' );
	} else if ( ! empty($_GET['category']) ) {
		$output = wpsc_get_categorymeta( absint( $_GET['category'] ), 'description' );
	}

	// Return product content as description if product page
	if ( !empty($_GET['product_id'] ) && is_numeric( $_GET['product_id'] ) ) {
		$product = get_post(absint( $_GET['product_id'] ));
		$output = $product->post_content;
	}

	return $output;
}

function wpsc_replace_wp_title( $input ) {
	global $wpdb, $wp_query;
	$output = wpsc_obtain_the_title();
	if ( $output != null ) {
		return $output;
	}
	return $input;
}

function wpsc_replace_bloginfo_title( $input, $show ) {
	global $wpdb, $wp_query;
	if ( $show == 'description' ) {
		$output = wpsc_obtain_the_title();
		if ( $output != null ) {
			return $output;
		}
	}
	return $input;
}

if ( get_option( 'wpsc_replace_page_title' ) == 1 ) {
	add_filter( 'wp_title', 'wpsc_replace_wp_title', 10, 2 );
}
?>