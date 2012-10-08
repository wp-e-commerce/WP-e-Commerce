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

	echo "    </div>";
}

function wpsc_country_region_list( $form_id = null, $ajax = false, $selected_country = null, $selected_region = null, $supplied_form_id = null, $checkoutfields = false ) {
	global $wpdb;

	$output = '';

	if ( $selected_country == null )
		$selected_country = get_option( 'base_country' );

	if ( $selected_region == null )
		$selected_region = get_option( 'base_region' );

	if ( $form_id != null )
		$html_form_id = "region_country_form_$form_id";
	else
		$html_form_id = 'region_country_form';

	if ( $checkoutfields ) {
		$js = "onchange='set_shipping_country(\"$html_form_id\", \"$form_id\");'";
		$title = 'shippingcountry';
	} else {
		$js = "onchange='set_billing_country(\"$html_form_id\", \"$form_id\");'";
		$title = 'billingcountry';
	}

	$country_data = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_CURRENCY_LIST . "` ORDER BY `country` ASC", ARRAY_A );
	$additional_attributes = "title='{$title}' {$js}";
	$output .= "<div id='$html_form_id'>\n\r";
	$output .= wpsc_get_country_dropdown( array(
		'id'                    => $supplied_form_id,
		'name'                  => "collected_data[{$form_id}][0]",
		'class'                 => 'current_country',
		'selected'              => $selected_country,
		'additional_attributes' => $additional_attributes,
		'placeholder'           => '',
	) );

	$region_list    = $wpdb->get_results( $wpdb->prepare( "SELECT `" . WPSC_TABLE_REGION_TAX . "`.* FROM `" . WPSC_TABLE_REGION_TAX . "`, `" . WPSC_TABLE_CURRENCY_LIST . "`  WHERE `" . WPSC_TABLE_CURRENCY_LIST . "`.`isocode` IN(%s) AND `" . WPSC_TABLE_CURRENCY_LIST . "`.`id` = `" . WPSC_TABLE_REGION_TAX . "`.`country_id`", $selected_country ), ARRAY_A );
	$sql            = "SELECT `" . WPSC_TABLE_CHECKOUT_FORMS . "`.`id` FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `unique_name` = 'shippingstate' ";
	$region_form_id = $wpdb->get_var( $sql );

	if ( $checkoutfields ) {
		$namevalue = "name='collected_data[" . $region_form_id . "]'";
		$js = "onchange='set_shipping_country(\"$html_form_id\", \"$form_id\");'";
		$title = 'shippingstate';
	} else {
		$namevalue = "name='collected_data[" . $form_id . "][1]'";
		$js = "onchange='set_billing_country(\"$html_form_id\", \"$form_id\");'";
		$title = 'billingstate';
	}

	$output .= "<div id='region_select_$form_id'>";
	if ( $region_list != null ) {
		$output .= "<select title='$title' " . $namevalue . " class='current_region' " . $js . ">\n\r";
		foreach ( $region_list as $region ) {
			if ( $selected_region == $region['id'] ) {
				$selected = "selected='selected'";
			} else {
				$selected = "";
			}
			$output .= "<option value='" . $region['id'] . "' $selected>" . esc_html( $region['name'] ) . "</option>\n\r";
		}
		$output .= "</select>\n\r";
	}

	$output .= "</div>";
	$output .= "</div>\n\r";

	return $output;
}

?>
