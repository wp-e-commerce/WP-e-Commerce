<?php

function wpsc_shopping_cart( $input = null, $override_state = null ) {
	global $wpdb;

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
			if ( isset( $_SESSION['nzshpcrt_cart'] ) ) {
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
			$cart = $_SESSION['nzshpcrt_cart'];
			echo "<div id='sideshoppingcart'><div id='shoppingcartcontents'>";
			echo wpsc_shopping_basket_internals( $cart );
			echo "</div></div>";
		}
	} else if ( ($state == 3) || ($state == 4) ) {
		if ( isset( $_SESSION['nzshpcrt_cart'] ) ) {
			$cart = $_SESSION['nzshpcrt_cart'];
		}
		if ( $state == 4 ) {
			echo "<div id='widgetshoppingcart'>";
			echo "<h3>" . __( 'Shopping Cart', 'wpsc' ) . "$fancy_collapser</h3>";
			echo "  <div id='shoppingcartcontents'>";
			echo wpsc_shopping_basket_internals( $cart, false, true );
			echo "  </div>";
			echo "</div>";
			$dont_add_input = true;
		} else {
			echo "<div id='sideshoppingcart'>";
			echo "<h3>" . __( 'Shopping Cart', 'wpsc' ) . "$fancy_collapser</h3>";
			echo "  <div id='shoppingcartcontents'>";
			if ( isset( $cart ) ) {
				echo wpsc_shopping_basket_internals( $cart, false, true );
			}
			echo "  </div>";
			echo "</div>";
		}
	} else {
		if ( (isset( $GLOBALS['nzshpcrt_activateshpcrt'] ) && $GLOBALS['nzshpcrt_activateshpcrt'] === true ) ) {

			$cart = $GLOBALS['nzshpcrt_activateshpcrt'];

			if ( isset( $_SESSION['nzshpcrt_cart'] ) ) {
				$cart = $_SESSION['nzshpcrt_cart'];
			}
			echo "<div id='shoppingcart'>";
			echo "<h3>" . __( 'Shopping Cart', 'wpsc' ) . "$fancy_collapser</h3>";
			echo "  <div id='shoppingcartcontents'>";
			echo wpsc_shopping_basket_internals( $cart, false, true );
			echo "  </div>";
			echo "</div>";
		}
	}
	return $input;
}

function wpsc_shopping_basket_internals( $cart, $quantity_limit = false, $no_title=false ) {
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

	if ( $supplied_form_id != null )
		$supplied_form_id = "id='$supplied_form_id'";

	if ( $checkoutfields ) {
		$js = "onchange='set_shipping_country(\"$html_form_id\", \"$form_id\");'";
		$title = 'shippingcountry';
	} else {
		$js = "onchange='set_billing_country(\"$html_form_id\", \"$form_id\");'";
		$title = 'billingcountry';
	}

	$country_data = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_CURRENCY_LIST . "` ORDER BY `country` ASC", ARRAY_A );
	$output .= "<div id='$html_form_id'>\n\r";
	$output .= "<select $supplied_form_id title='$title' name='collected_data[" . $form_id . "][0]' class='current_country' $js >\n\r";

	foreach ( $country_data as $country ) {
		$selected = '';
		if ( $country['visible'] == '1' ) {
			if ( $selected_country == $country['isocode'] ) {
				$selected = "selected='selected'";
			}
			$output .= "<option value='" . $country['isocode'] . "' $selected>" . htmlentities( $country['country'], ENT_QUOTES, 'UTF-8' ) . "</option>\n\r";
		}
	}

	$output .= "</select>\n\r";


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
			$output .= "<option value='" . $region['id'] . "' $selected>" . htmlentities( $region['name'], ENT_QUOTES, 'UTF-8' ) . "</option>\n\r";
		}
		$output .= "</select>\n\r";
	}

	$output .= "</div>";
	$output .= "</div>\n\r";

	return $output;
}

?>
