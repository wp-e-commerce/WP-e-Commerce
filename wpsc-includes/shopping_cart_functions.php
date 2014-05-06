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

	if ( empty( $supplied_form_id ) ) {
		$supplied_form_id = $id;
	}


	$additional_attributes = 'data-wpsc-meta-key="' . $title . '" title="' . $title . '" ' . $js;
	$output .= "<div id='$html_form_id'>\n\r";
	$output .= wpsc_get_country_dropdown(
		array(
				'id'                    => $supplied_form_id,
				'name'                  => "collected_data[{$form_id}][0]",
				'class'                 => 'current_country wpsc-visitor-meta',
				'selected'              => $selected_country->get_isocode(),
				'additional_attributes' => $additional_attributes,
				'placeholder'           => __( 'Please select a country', 'wpsc' ),
		)
	);

	$region_list = $selected_country->get_regions();

	$checkout_form = new WPSC_Checkout_Form();
	$region_form_id = $checkout_form->get_field_id_by_unique_name( 'shippingstate' );

	if ( $shippingfields ) {
		$namevalue = ' name="collected_data[' . $region_form_id . ']" ';
		$title = 'shippingregion';
	} else {
		$namevalue = ' name="collected_data[' . $form_id . '][1]" ';
		$title = 'billingregion';
	}

	$region_form_id = $supplied_form_id . '_region';

	$output .= "<div id='region_select_$form_id'>";

	$output .= '<select id="' . $region_form_id . '" class="current_region wpsc-visitor-meta wpsc-region-dropdown" data-wpsc-meta-key="' . $title . '"  title="' . $title . '" ' . $namevalue . ">\n\r ";

	if ( $region_list != null ) {

		if ( count( $region_list ) > 1 ) {
			$label = $selected_country->get( 'region_label' );
			$please_select_message = sprintf( __( 'Please select a %s', 'wpsc' ), $label );
			$output .= "<option value='0'>"  . $please_select_message. "</option>\n\r";
		}

		foreach ( $region_list as $region ) {
			if ( $selected_region && $selected_region->get_id() == $region->get_id() ) {
				$selected = "selected='selected'";
			} else {
				$selected = '';
			}
			$output .= "<option value='" . $region->get_id() . "' $selected>" . esc_html( $region->get_name() ) . "</option>\n\r";
		}
	}

	$output .= "</select>\n\r";

	$output .= '</div>';
	$output .= "</div>\n\r";
	return $output;
}

/**
 * get a country list for checkout
 *
 * @param string|null $form_id
 * @param deprecated|null $ajax
 * @param string|null $selected_country
 * @param deprecated|null $selected_region
 * @param string|null $supplied_form_id
 * @param boolean $shippingfields
 * @return string
 */
function wpsc_country_list( $form_id = null, $ajax = null, $selected_country = null, $selected_region = null, $supplied_form_id = null, $shippingfields = false ) {
	global $wpdb;

	$output = '';

	if ( $form_id != null ) {
		$html_form_id = "region_country_form_$form_id";
	} else {
		$html_form_id = 'region_country_form';
	}

	if ( $shippingfields ) {
		$js    = '';
		$title = 'shippingcountry';
		$id    = 'shippingcountry';
	} else {
		$js    = '';
		$title = 'billingcountry';
		$id    = 'billingcountry';
	}

	if ( empty( $supplied_form_id ) ) {
		$supplied_form_id = $id;
	}


	// if there is only one country to choose from we are going to set that as the shipping country,
	// later in the UI generation the same thing will happen to make the single country the current
	// selection
	$countries = WPSC_Countries::get_countries( false );
	if ( count( $countries ) == 1 ) {
		reset( $countries );
		$id_of_only_country_available = key( $countries );
		$wpsc_country = new WPSC_Country( $id_of_only_country_available );
		wpsc_update_customer_meta( $id, $wpsc_country->get_isocode() );
	}

	$additional_attributes = 'data-wpsc-meta-key="' . $title . '" title="' . $title . '" ' . $js;
	$output .= "<div id='$html_form_id'>\n\r";
	$output .= wpsc_get_country_dropdown(
											array(
													'id'                    => $supplied_form_id,
													'name'                  => "collected_data[{$form_id}][0]",
													'class'                 => 'current_country wpsc-visitor-meta',
													'selected'              => $selected_country,
													'additional_attributes' => $additional_attributes,
													'placeholder'           => __( 'Please select a country', 'wpsc' ),
												)
										);
	$output .= '</div>';
	$output .= "</div>\n\r";
	return $output;
}



/**
 * get the output used to show a shipping state and region select drop down
 *
 * @since 3.8.14
 *
 * @param wpsc_checkout|null  $wpsc_checkout checkout object
 * @return string
 */
function wpsc_checkout_billing_state_and_region( $wpsc_checkout = null ) {

	// just in case the checkout form was not presented, like when we are doing the shipping calculator
	if ( empty( $wpsc_checkout ) ) {
		$wpsc_checkout = new wpsc_checkout();
		$doing_checkout_form = false;
	} else {
		$doing_checkout_form = true;
	}

	// if we aren't showing the billing state on the cor we have no work to do
	if ( ! $wpsc_checkout->get_checkout_item( 'billingstate' ) ) {
		return '';
	}

	// save the current checkout item in case we adjust it in the routine, we'll put it back before return
	$saved_checkout_item = $wpsc_checkout->checkout_item;

	// check a new checkout form with all fields
	$checkout_form = new WPSC_Checkout_Form( null, false );

	// is the billing country visible on the form, let's find out
	$billing_country_form_element = $checkout_form->get_field_by_unique_name( 'billingcountry' );
	$showing_billing_country = $billing_country_form_element->active;

	// the current values of the form elements we will need
	$billing_country = wpsc_get_customer_meta( 'billingcountry'  );
	$billing_region  = wpsc_get_customer_meta( 'billingregion'   );
	$billing_state   = wpsc_get_customer_meta( 'billingstate'   );

	// if we are showing the billing country on the form then we use the value that can be
	// changed by the user, otherwise we will use the base country as configured in store admin
	if ( $showing_billing_country ) {
		$wpsc_country = new WPSC_Country( $billing_country );
	} else {
		$wpsc_country = new WPSC_Country( wpsc_get_base_country() );
	}

	// if there are regions for the current selection we put them into the HTML now
	$region_list = $wpsc_country->get_regions();


	// make sure the billing state is the current checkout element
	$wpsc_checkout->checkout_item = $wpsc_checkout->get_checkout_item( 'billingstate' );

	$form_element_id = $wpsc_checkout->form_element_id();
	if ( $doing_checkout_form ) {
		$id_attribute = ' id="'. $form_element_id . '" ';
	} else {
		$id_attribute = '';
	}

	// if there are regions for the current country we are going to
	// create the billing state edit, but hide it
	$style = '';
	if ( ! empty( $region_list ) ) {
		$style = 'style="display: none;"';
	}

	$placeholder = apply_filters(
									'wpsc_checkout_field_placeholder',
									apply_filters( 'wpsc_checkout_field_name', $wpsc_checkout->checkout_item->name ),
									$wpsc_checkout->checkout_item
								);

	$output = '<input data-wpsc-meta-key="' . $wpsc_checkout->checkout_item->unique_name. '" title="'
				. $wpsc_checkout->checkout_item->unique_name
					. $id_attribute
						. ' class="shipping_region wpsc-visitor-meta" '
							. 'name="collected_data['. $wpsc_checkout->checkout_item->id . ']" '
								. ' placeholder="'. esc_attr( $placeholder ) . '" '
									. ' value="' . esc_attr( $billing_state ) . '" '
										. $style
											. '" />';

	// setup the drop down field, aka 'billingregion'
	// move the checkout item pointer to the billing country, so we
	// can generate form element ids, highly lame
	$wpsc_checkout->checkout_item = $checkout_form->get_field_by_unique_name( 'billingcountry' );


	$title = 'billingregion';

	// if there aren't any regions for the current country we are going to
	// create the empty region select, but hide it
	$style = ' ';
	if ( empty( $region_list ) ) {
		$style = 'style="display: none;"';
	}

	$region_form_id = $wpsc_checkout->form_element_id() . '_region';

	$output .= '<select '
					. 'id="' . $region_form_id . '" '
						. ' class="current_region wpsc-visitor-meta wpsc-region-dropdown" data-wpsc-meta-key="' . $title
							. '"  title="' . $title
								. 'name="collected_data['. $wpsc_checkout->checkout_item->id . ']" '
									. $style
										. ">\n\r ";


	$wpsc_current_region = $wpsc_country->get_region( $billing_region );

	if ( $region_list != null ) {

		if ( count( $region_list ) > 1 ) {
			$label = $wpsc_country->get( 'region_label' );
			$please_select_message = sprintf( __( 'Please select a %s', 'wpsc' ), $label );
			$output .= "<option value='0'>"  . $please_select_message. "</option>\n\r";
		}

		foreach ( $region_list as $wpsc_region ) {
			if ( $wpsc_current_region && $wpsc_current_region->get_id() == $wpsc_region->get_id() ) {
				$selected = "selected='selected'";
			} else {
				$selected = '';
			}
			$output .= "<option value='" . $wpsc_region->get_id() . "' $selected>" . esc_html( $wpsc_region->get_name() ) . "</option>\n\r";
		}
	}

	$output .= "</select>\n\r";

	// restore the checkout item in case we messed with it
	$wpsc_checkout->checkout_item = $saved_checkout_item;

	return $output;
}


/**
 * get the output used to show a shipping state and region select drop down
 *
 * @since 3.8.14
 *
 * @param wpsc_checkout|null  $wpsc_checkout checkout object
 * @return string
 */
function wpsc_checkout_shipping_state_and_region( $wpsc_checkout = null ) {

	// just in case the checkout form was not presented, like when we are doing the shipping calculator
	if ( empty( $wpsc_checkout ) ) {
		$wpsc_checkout = new wpsc_checkout();
		$doing_checkout_form = false;
	} else {
		$doing_checkout_form = true;
	}

	// if we aren't showing the shipping state on the cor we have no work to do
	if ( ! $wpsc_checkout->get_checkout_item( 'shippingstate' ) ) {
		return '';
	}

	// save the current checkout item in case we adjust it in the routine, we'll put it back before return
	$saved_checkout_item = $wpsc_checkout->checkout_item;

	// check a new checkout form with all fields
	$checkout_form = new WPSC_Checkout_Form( null, false );

	// is the shipping country visible on the form, let's find out
	$shipping_country_form_element = $checkout_form->get_field_by_unique_name( 'shippingcountry' );
	$showing_shipping_country      = (bool) $shipping_country_form_element->active;

	// make sure the shipping state is the current checkout element
	$wpsc_checkout->checkout_item = $wpsc_checkout->get_checkout_item( 'shippingstate' );

	// setup the edit field, aka 'shippingstate'
	$shipping_country = wpsc_get_customer_meta( 'shippingcountry' );
	$shipping_region  = wpsc_get_customer_meta( 'shippingregion'  );
	$shipping_state   = wpsc_get_customer_meta( 'shippingstate'  );

	// if we are showing the billing country on the form then we use the value that can be
	// changed by the user, otherwise we will use the base country as configured in store admin
	if ( $showing_shipping_country ) {
		$wpsc_country = new WPSC_Country( $shipping_country );
	} else {
		$wpsc_country = new WPSC_Country( wpsc_get_base_country() );
	}

	$region_list = $wpsc_country->get_regions();

	$placeholder = $wpsc_country->get( 'region_label' );
	if ( empty ( $placeholder ) ) {
		$placeholder = $wpsc_checkout->checkout_item->name;
	}

	$placeholder = apply_filters(
									'wpsc_checkout_field_placeholder',
									apply_filters( 'wpsc_checkout_field_name',  $placeholder ),
									$wpsc_checkout->checkout_item
								);

	$form_element_id = $wpsc_checkout->form_element_id();
	if ( $doing_checkout_form ) {
		$id_attribute = ' id="'. $form_element_id . '" ';
	} else {
		$id_attribute = '';
	}

	// if there are regions for the current country we are going to
	// create the billing state edit, but hide it
	$style = ' ';
	if ( ! empty( $region_list ) ) {
		$style = 'style="display: none;"';
	}

	$output = '<input class="shipping_region text  wpsc-visitor-meta" '
				. ' data-wpsc-meta-key="'. $wpsc_checkout->checkout_item->unique_name . '" '
					. ' title="'. $wpsc_checkout->checkout_item->unique_name . '" '
						. ' type="text" '
							. $id_attribute
								. ' placeholder="' . esc_attr( $placeholder ) . '" '
									. ' value="' . esc_attr( $shipping_state ) .'" '
										. ' name="collected_data[' . $wpsc_checkout->checkout_item->id . ']" '
											. $style . ' />'
												. "\n\r";


	// setup the drop down field, aka 'shippingregion'

	// move the checkout item pointer to the billing country, so we can generate form element ids, highly lame
	$wpsc_checkout->checkout_item = $checkout_form->get_field_by_unique_name( 'shippingcountry' );

	// if there aren't any regions for the current country we are going to
	// create the empty region select, but hide it
	$style = ' ';
	if ( empty( $region_list ) ) {
		$style = 'style="display: none;"';
	}

	$title = 'shippingregion';

	$region_form_id = $wpsc_checkout->form_element_id() . '_region';

	$output .= '<select id="'. $region_form_id . '" '
				. ' class="current_region wpsc-visitor-meta wpsc-region-dropdown" '
					. ' data-wpsc-meta-key="shippingregion" '
						. ' title="' . $title . '" '
							. 'name="collected_data['. $wpsc_checkout->checkout_item->id . '][1]" '
								. $style
									. ">\n\r";

	$wpsc_current_region = $wpsc_country->get_region( $shipping_region );

	if ( ! empty ( $region_list ) ) {

		if ( count( $region_list ) > 1 ) {
			$label = $wpsc_country->get( 'region_label' );
			$please_select_message = sprintf( __( 'Please select a %s', 'wpsc' ), $label );
			$output .= "<option value='0'>"  . $please_select_message. "</option>\n\r";
		}

		foreach ( $region_list as $wpsc_region ) {
			if ( (bool)$wpsc_current_region && $wpsc_current_region->get_id() == $wpsc_region->get_id() ) {
				$selected = "selected='selected'";
			} else {
				$selected = '';
			}
			$output .= "<option value='" . $wpsc_region->get_id() . "' $selected>" . esc_html( $wpsc_region->get_name() ) . "</option>\n\r";
		}
	}

	$output .= "</select>\n\r";

	// restore the checkout item in case we messed with it
	$wpsc_checkout->checkout_item = $saved_checkout_item;

	return $output;
}

/**
 * get the WPeC base country as configured in store admin
 *
 * @return string   current base country
 */
function wpsc_get_base_country() {
	return get_option( 'base_country' );
}


/**
 * Record an error message related to shipping
 *
 * @access private
 *
 * @since 3.8.14.1
 *
 * @param string $message
 */
function _wpsc_shipping_add_error_message( $message ) {
	$shipping_error_messages = wpsc_get_customer_meta( 'shipping_error_messages' );
	if ( empty ( $shipping_error_messages ) && ! is_array( $shipping_error_messages ) ) {
		$shipping_error_messages = array();
	}

	$id = md5( $message );
	$shipping_error_messages[$id] = $message;

	wpsc_update_customer_meta( 'shipping_error_messages', $shipping_error_messages );
}


/**
 * clear shipping error messages
 *
 * @since 3.8.14.1
 *
 * @access private
 *
 */
function _wpsc_clear_shipping_error_messages() {
	wpsc_delete_customer_meta( 'shipping_error_messages' );
}

// clear shipping messages before shipping quotes are recalculated
add_action(  'wpsc_before_get_shipping_method', '_wpsc_clear_shipping_error_messages' );


/**
 * output shipping error messages
 *
 * @since 3.8.14.1
 *
 * @access private
 */
function _wpsc_shipping_error_messages() {
	$shipping_error_messages = wpsc_get_customer_meta( 'shipping_error_messages' );
	?>
	<div class="wpsc-shipping-error_messages">
	<?php
	if ( ! empty ( $shipping_error_messages ) ) {
		foreach ( $shipping_error_messages as $id => $message ) {
			?>
			<div class="wpsc-shipping-error_message" id="<?php echo esc_attr( $id );?>">
			<?php
				echo esc_html( $message );
			?>
			</div>
			<?php
		}
	}
	?>
	</div>
	<?php
}

// echo shipping error messages on checkout form
add_action(  'wpsc_before_shipping_of_shopping_cart', '_wpsc_shipping_error_messages' );




