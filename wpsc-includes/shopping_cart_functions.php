<?php

function wpsc_shopping_cart( $input = null, $override_state = null ) {
	global $wpdb, $wpsc_cart;

	$customer_id = wpsc_get_current_customer_id();

	if ( is_numeric( $override_state ) )
		$state = $override_state;
	else
		$state = get_option( 'cart_location' );

	if ( get_option( 'show_sliding_cart' ) == 1 ) {
		if ( isset( $_SESSION['slider_state'] ) && is_numeric( $_SESSION['slider_state'] ) ) {
			if ( $_SESSION['slider_state'] == 0 ) {
				$collapser_image = 'plus.png';
			} else {
				$collapser_image = 'minus.png';
			}
			$fancy_collapser = "<a href='#' onclick='return shopping_cart_collapser()' id='fancy_collapser_link'><img src='" . WPSC_CORE_IMAGES_URL . "/$collapser_image' title='' alt='' id='fancy_collapser' /></a>";
		} else {
			if ( $customer_id ) {
				$collapser_image = 'minus.png';
			} else {
				$collapser_image = 'plus.png';
			}
			$fancy_collapser = "<a href='#' onclick='return shopping_cart_collapser()' id='fancy_collapser_link'><img src='" . WPSC_CORE_IMAGES_URL . "/$collapser_image' title='' alt='' id='fancy_collapser' /></a>";
		}
	} else {
		$fancy_collapser = "";
	}

	if ( $state == 1 ) {
		if ( $input != '' ) {
			echo "<div id='sideshoppingcart'><div id='shoppingcartcontents'>";
			echo wpsc_shopping_basket_internals();
			echo "</div></div>";
		}
	} else if ( ($state == 3) || ($state == 4) ) {
		if ( $state == 4 ) {
			echo "<div id='widgetshoppingcart'>";
			echo "<h3>" . __( 'Shopping Cart', 'wpsc' ) . "$fancy_collapser</h3>";
			echo "  <div id='shoppingcartcontents'>";
			echo wpsc_shopping_basket_internals(false, false, true );
			echo "  </div>";
			echo "</div>";
			$dont_add_input = true;
		} else {
			echo "<div id='sideshoppingcart'>";
			echo "<h3>" . __( 'Shopping Cart', 'wpsc' ) . "$fancy_collapser</h3>";
			echo "  <div id='shoppingcartcontents'>";
			echo wpsc_shopping_basket_internals( false, false, true );
			echo "  </div>";
			echo "</div>";
		}
	} else {
		if ( (isset( $GLOBALS['nzshpcrt_activateshpcrt'] ) && $GLOBALS['nzshpcrt_activateshpcrt'] === true ) ) {
			echo "<div id='shoppingcart'>";
			echo "<h3>" . __( 'Shopping Cart', 'wpsc' ) . "$fancy_collapser</h3>";
			echo "  <div id='shoppingcartcontents'>";
			echo wpsc_shopping_basket_internals( false, false, true );
			echo "  </div>";
			echo "</div>";
		}
	}
	return $input;
}

function wpsc_shopping_basket_internals( $deprecated = false, $quantity_limit = false, $no_title=false ) {
	global $wpdb;

	$display_state = '';

	if ( ( ( ( isset( $_SESSION['slider_state'] ) && $_SESSION['slider_state'] == 0) ) || ( wpsc_cart_item_count() < 1 ) ) && ( get_option( 'show_sliding_cart' ) == 1 ) )
		$display_state = "style='display: none;'";

	echo "    <div id='sliding_cart' class='shopping-cart-wrapper' $display_state>";

	include_once( wpsc_get_template_file_path( 'wpsc-cart_widget.php' ) );

	echo '    </div>';
}

function wpsc_country_region_list( $form_id = null, $ajax = false, $selected_country = null, $selected_region = null, $supplied_form_id = null, $shippingfields = false ) {
	global $wpdb;

	$output = '';

	if ( $selected_country == null ) {
		$selected_country = get_option( 'base_country' );
	}

	if ( $selected_region == null ) {
		//$selected_region = get_option( 'base_region' );
	}

	$selected_country = new WPSC_Country( $selected_country );
	$selected_region = $selected_country->get_region( $selected_region );

	if ( $form_id != null ) {
		$html_form_id = "region_country_form_$form_id";
	} else {
		$html_form_id = 'region_country_form';
	}

	if ( $shippingfields ) {
		$js = '';
		$title = 'shippingcountry';
		$id = 'shippingcountry';
	} else {
		$js = '';
		$title = 'billingcountry';
		$id = 'billingcountry';
	}

	$additional_attributes = 'data-wpsc-meta-key="' . $title . '" title="' . $title . '" ' . $js;
	$output .= "<div id='$html_form_id'>\n\r";
	$output .= wpsc_get_country_dropdown(
		array(
				'id'                    => $id,
				'name'                  => "collected_data[{$form_id}][0]",
				'class'                 => 'current_country wpsc-visitor-meta',
				'selected'              => $selected_country->get_isocode(),
				'additional_attributes' => $additional_attributes,
				'placeholder'           => '',
		)
	);

	$region_list = $selected_country->get_regions();

	$checkout_form = new WPSC_Checkout_Form();
	$region_form_id = $checkout_form->get_field_id_by_unique_name( 'shippingstate' );

	if ( $shippingfields ) {
		$namevalue = ' name="collected_data[' . $region_form_id . ']" ';
		$js = '';
		$title = 'shippingregion';
		$id = 'shippingregion';
	} else {
		$namevalue = ' name="collected_data[' . $form_id . '][1]" ';
		$js = '';
		$title = 'billingregion';
		$id = 'billingregion';
	}

	$output .= "<div id='region_select_$form_id'>";
	if ( $region_list != null ) {
		$output .= '<select id="' . $id . '" class="current_region wpsc-visitor-meta" data-wpsc-meta-key="' . $title . '"  title="' . $title . '" ' . $namevalue . '" ' . $js . ">\n\r";
		foreach ( $region_list as $region ) {
			if ( $selected_region && $selected_region->get_id() == $region->get_id() ) {
				$selected = "selected='selected'";
			} else {
				$selected = '';
			}
			$output .= "<option value='" . $region->get_id() . "' $selected>" . esc_html( $region->get_name() ) . "</option>\n\r";
		}
		$output .= "</select>\n\r";
	}

	$output .= '</div>';
	$output .= "</div>\n\r";

	return $output;
}

?>
