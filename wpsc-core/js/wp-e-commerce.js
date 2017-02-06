/* globals jQuery */
///////////////////////////////////////////////////////////////////////////////////////////////
// This section is used to create the globals that were originally defined in the
// dynamic-js file pre 3.8.14.  Note that variables also also exist in the "wpsc_ajax" structure.
// To add a new global property that can be referenced in the script see the hook
// wpsc_javascript_localizations in wpsc-core/wpsc-functions.php
//

/**
 * javascript variables for WP-e-Commerce
 *
 * These WPeC WordPress localized variables were in use prior to release 3.8.14, and are explicitly
 * declared here for maximum backwards compatibility.
 *
 * In releases prior to 3.8.14 these legacy variables may have been declared in the dynamically
 * created javascript, or in the HTML as a localized variable.
 *
 * For javascript variables added after version 3.8.14  use the following utility function to access the
 * localized variables.
 *
 * wpsc_var_get ( name )
 * wpsc_var_set ( name, value )
 * wpsc_var_isset ( name, value );
 *
 */
if ( typeof wpsc_vars !== 'undefined' ) {
	var wpsc_ajax                = wpsc_vars.wpsc_ajax;
	var base_url                 = wpsc_vars.base_url;
	var WPSC_URL                 = wpsc_vars.WPSC_URL;
	var WPSC_IMAGE_URL           = wpsc_vars.WPSC_IMAGE_URL;
	var WPSC_IMAGE_URL           = wpsc_vars.WPSC_IMAGE_URL;
	var WPSC_CORE_IMAGES_URL     = wpsc_vars.WPSC_CORE_IMAGES_URL;
	var fileThickboxLoadingImage = wpsc_vars.fileThickboxLoadingImage;
}
// end of variable definitions
///////////////////////////////////////////////////////////////////////////////////////////////

/**
 * check if a localized WPeC value is set
 *
 * @since 3.8.14
 *
 * @param string 	name 		name of localized variable
 *
 * @returns boolean		true if the variable is set, false otherwise
 *
 */
function wpsc_var_isset( name ) {
	if ( typeof wpsc_vars !== 'undefined' ) {
		return  wpsc_vars[name] !== undefined;
	}

	return false;
}

/**
 * get the value of a localized WPeC value if it is set
 *
 * @since 3.8.14
 *
 * @param string 	name 		name of localized variable
 *
 * @returns varies				value of the var set
 *
 */
function wpsc_var_get( name ) {
	if ( typeof wpsc_vars !== 'undefined' ) {
		return  wpsc_vars[name];
	}

	return undefined;
}

/**
 * Checks to determine whether or not an element is fully visible
 *
 * @since  3.8.14.1
 * @param  jQuery object el Element being checked for visibility.
 * @return boolean          Whether or not element is visible.
 */
function wpsc_element_is_visible( el ) {
	var top   = jQuery( window ).scrollTop(),
	bottom    = top + jQuery( window ).height(),
	elTop     = el.offset().top;

	return ( (elTop >= top ) && ( elTop <= bottom ) && ( elTop <= bottom ) && ( elTop >= top ) ) && el.is( ':visible' );
}

/**
 * change the value of a localized WPeC var
 *
 * @since 3.8.14
 *
 * @param string 	name 		name of localized variable
 * @param varies 	value 		value of the var being set
 *
 * @returns varies		value of the var being set
 *
 */
function wpsc_var_set( name, value ) {
	if ( typeof wpsc_vars !== 'undefined' ) {
		wpsc_vars[name] = value;
		return value;
	}

	return undefined;
}

/**
 * Create an <option> tag in a cross-browser manner.
 * See: https://github.com/wp-e-commerce/WP-e-Commerce/issues/1792
 *
 * @since 3.11.0
 *
 * @param {string} displaytext              The text to put between the <option></option> tags.
 * @param {string|int|float} [value='']     The value's option, (for the "value" attribute).
 *
 * @returns {*}         A jQuerified <option> element.
 */
function wpsc_create_option(  displaytext, value ) {
	if ( 'undefined' === value ) value = '';
	return jQuery( document.createElement( 'option' ) ).val( value ).text( displaytext );
}

///////////////////////////////////////////////////////////////////////////////////////////////
// Setting up the WPEC customer identifier
//
// When WPEC generates a page it sets a 'customer cookie' into the browser.  This cookie is a
// persistent identifier that connects a user's session to their cart or other profile data a
// store may need to work correctly.
//
// When page caching or a CDN is in place WPEC does not get to set the cookie because
// the page is served without the overhead of computing the page contents.
// This means that the first GET/POST request, including requests using AJAX are required to
// initialize the customer identifier
//
// Because browsers may execute these requests in parallel the probability of multiple unique
// cookies being set is very high. This means that in the absence of the logic below WPEC would
// have to create multiple unique profiles as each of the parallel requests are executed.  This
// can cause data when one request uses one profile and the other request uses a different profile.
// It could also cause performance issues on the back-end, and create a potentially resource
// intensive and wasteful situation.
//
// The mitigation for this issue is to look for the customer identifier when this script first
// runs.  If the identifier is not found, initiate a very quick synchronous AJAX request.  This
// happens before any other processing takes place.  This request should create the unique
// customer identifier before it is required by other processing.
//

// a global variable used to hold the current users visitor id,
// if you are going to user it always check to be sure it is not false
var wpsc_visitor_id = false;

if ( document.cookie.indexOf("wpsc_customer_cookie") < 0 ) {
	if ( document.cookie.indexOf("wpsc_attempted_validate") < 0 ) {
		// create a cookie to signal that we have attempted validation.  If we find the cookie is set
		// we don't re-attempt validation.  This means will only try to validate once and not slow down
		// subsequent page views.

		// The lack of expiration date means the cookie will be deleted when the browser
		// is closed, so the next time the visitor attempts to access the site after closing the browser
		// they will revalidate.
		var now = new Date();
		document.cookie = "wpsc_attempted_validate="+now;

		var wpsc_http = new XMLHttpRequest();

		// open setup and send the request in synchronous mode
		wpsc_http.open( "POST", wpsc_ajax.ajaxurl + "?action=wpsc_validate_customer", false );
		wpsc_http.setRequestHeader( "Content-type", "application/json; charset=utf-8" );

		// Note that we cannot set a timeout on synchronous requests due to XMLHttpRequest limitations
		wpsc_http.send();

		// we did the request in synchronous mode so we don't need the on load or ready state change events	to check the result
		if (wpsc_http.status == 200) {
			 var result = JSON.parse( wpsc_http.responseText );
			 if ( result.valid && result.id ) {
				 wpsc_visitor_id = result.id;
			 }
		}
	}
}
// end of setting up the WPEC customer identifier
///////////////////////////////////////////////////////////////////////////////////////////////
function wpsc_do_ajax_request( data, success_callback ) {
	jQuery.ajax({
		type      : "post",
		dataType  : "json",
		url       : wpsc_ajax.ajaxurl,
		data      : data,
		success   : success_callback
	});
}

/**
 * update a customer meta value
 *
 * @since 3.8.14
 * @param meta_key string
 * @param meta_value string
 * @param response_callback function
 */
function wpsc_update_customer_data( meta_key, meta_value, response_callback ) {

	// wrap our ajax request in a try/catch so that an error doesn't stop the script from running
	try {
		var ajax_data = {action: 'wpsc_customer_updated_data', meta_key : meta_key, meta_value : meta_value };
		wpsc_do_ajax_request( ajax_data, response_callback );
	} catch ( err ) {
		if ( window.console && window.console.log ) {
			console.log( err );
		}
	}

}

/**
 * get all customer data
 *
 * @since 3.8.14
 * @param meta_key string
 * @param meta_value string
 * @param response_callback function
 */
function wpsc_get_customer_data( response_callback ) {
	// wrap our ajax request in a try/catch so that an error doesn't stop the script from running
	try {
		var ajax_data = {action: 'wpsc_get_customer_meta' };
		wpsc_do_ajax_request( ajax_data, response_callback );
	} catch ( err ) {
		if ( window.console && window.console.log ) {
			console.log( err );
		}
	}
}

/**
 * common callback to update fields based on response from ajax processing.
 *
 * @since 3.8.14
 * @param response object returned from ajax request
 */
function wpsc_update_customer_meta( response ) {

	var element_that_caused_change_event = response.data.request.meta_key;

	if ( response.hasOwnProperty( 'data' ) && response.data.hasOwnProperty( 'customer_meta' )) {
		var customer_meta = response.data.customer_meta;

		// if the response includes customer meta values find out where the value
		// belongs and then put it there
		jQuery.each( customer_meta,  function( meta_key, meta_value ) {

			// if there are other fields on the current page that are used to change the same meta value then
			// they need to be updated
			var selector = '[data-wpsc-meta-key="' + meta_key + '"]';

			jQuery( selector ).each( function( index, value ) {
				var element_meta_key = wpsc_get_element_meta_key( this );

				if ( element_meta_key != element_that_caused_change_event ) {
					if ( jQuery(this).is(':checkbox') ) {
						var boolean_meta_value = wpsc_string_to_boolean( meta_value );
						if ( boolean_meta_value ) {
							jQuery( this ).attr( 'checked', 'checked' );
						} else {
							jQuery( this ).removeAttr( 'checked' );
						}
					} else if ( jQuery(this).hasClass('wpsc-region-dropdown') ) {
						// we are going to skip updating the region value because the select is dependant on
						// the value of other meta, specifically the billing or shipping country.
						// rather than enforce a field order in the response we will take care of it by doing
						// a second pass through the updates looking for only the region drop downs
					} else if ( jQuery(this).hasClass('wpsc-country-dropdown') ) {
						var current_value = jQuery( this ).val();
						if ( current_value != meta_value ) {
							jQuery( this ).val( meta_value );

							// if we are updating a country drop down we need to make sure that
							// the correct regions are in the list before we change the value
							wpsc_update_regions_list_to_match_country( jQuery( this ) );
						}
					} else {
						var current_value = jQuery( this ).val();
						if ( current_value != meta_value ) {
							jQuery( this ).val( meta_value );
						}
					}
				}
			});
		});

		// this second pass through the properties only looks for region drop downs, their
		// contents is dependant on other meta values so we do these after everything else has
		jQuery.each( customer_meta,  function( meta_key, meta_value ) {

			// if there are other fields on the current page that are used to change the same meta value then
			// they need to be updated
			var selector = '[data-wpsc-meta-key="' + meta_key + '"]';

			jQuery( selector ).each( function( index, value ) {
				var element_meta_key = wpsc_get_element_meta_key( this );

				if ( element_meta_key != element_that_caused_change_event ) {
					if ( jQuery(this).hasClass('wpsc-region-dropdown') ) {
						var current_value = jQuery( this ).val();
						if ( current_value != meta_value ) {
							jQuery( this ).val( meta_value );
						}
					}
				}
			});
		});
	}
}

/**
 * If shipping quotes need to be recalcualted adjust the checkout form and notify the user
 *
 * @param response  data from AJAX request
 */
function wpsc_check_for_shipping_recalc_needed( response ) {
	// TODO: if shipping needs to be re-calculated we need to refresh the page.  This is the only option
	// in version 3.8.14 and earlier.  Future versions should support replacing the shipping quote elements
	// via AJAX
	if ( response.hasOwnProperty( 'needs_shipping_recalc' ) && jQuery( '#checkout_page_container' ).length ) {
		if ( response.needs_shipping_recalc ) {

			var form = jQuery('table.productcart' ).first();
			var msg  = wpsc_var_get( 'msg_shipping_need_recalc' );

			if ( ! jQuery( '#shipping_quotes_need_recalc').length ) {

				form.before( '<div id="shipping_quotes_need_recalc" style="display:none">' + msg + '</div>' );
				jQuery( '#shipping_quotes_need_recalc' ).show( 375 );

				if ( wpsc_ajax.hasOwnProperty( 'slide_to_shipping_error' ) && wpsc_ajax.slide_to_shipping_error && ! wpsc_element_is_visible( jQuery( '#shipping_quotes_need_recalc' ) ) ) {
					jQuery( 'html, body' ).animate({
						scrollTop : jQuery( '#checkout_page_container' ).offset().top
					}, 600 );
				}

			}

			jQuery( 'input:radio[name=shipping_method]' ).prop('checked', false).attr('disabled',true);
			jQuery( 'input:radio[name=shipping_method]' ).closest( 'tr' ).hide( 275 );
			jQuery( 'tr.wpsc_shipping_header' ).hide( 275 );
			jQuery( '.wpsc_checkout_table_totals' ).hide( 275 );
			jQuery( '.total_tax' ).closest( 'table' ).hide( 275 );
		}
	}

}

/**
 * Take data from checkout data array and put it where it belongs
 *
 * Note: logic extracted from pre 3.8.14 wpsc_handle_country_change function
 *
 * @since 3.8.14
 * @param checkout_info
 * @return true if execution should continue, false if it should stop
 */
function wpsc_update_checkout_info( checkout_info ) {

	if ( checkout_info.hasOwnProperty( 'shipping_keys' ) ) {
		jQuery.each( checkout_info.shipping_keys, function( key, shipping ) {
			jQuery( '#shipping_' + key ).html( shipping );
		});
	}

	if ( checkout_info.hasOwnProperty( 'tax' ) ) {
		if ( checkout_info.tax > 0 ) {
			jQuery( 'tr.total_tax' ).show();
		} else {
			jQuery( 'tr.total_tax' ).hide();
		}
	}

	if ( checkout_info.hasOwnProperty( 'cart_shipping' ) ) {
		jQuery( '#checkout_shipping' ).html( checkout_info.cart_shipping );
	}

	if ( checkout_info.hasOwnProperty( 'widget_output' ) ) {
		jQuery( 'div.shopping-cart-wrapper' ).html( checkout_info.widget_output );
	}

	if ( checkout_info.hasOwnProperty( 'display_tax' ) ) {
		jQuery( '#checkout_tax' ).html( "<span class='pricedisplay'>" + checkout_info.display_tax + "</span>" );
	}

	if ( checkout_info.hasOwnProperty( 'total_input' ) ) {
		jQuery( '#checkout_total' ).html( checkout_info.total + "<input id='shopping_cart_total_price' type='hidden' value='" + checkout_info.total_input + "' />" );
	}

	jQuery( ".wpsc-visitor-meta").on( "change", wpsc_meta_item_change );
	jQuery( document ).trigger( { type : 'wpsc_update_checkout_info', info : checkout_info } );

	return true;
}

/**
 * common callback to update checkout fields based on response from ajax processing.  All fields that set
 * are present to make it easier to see where the plugin can be extended
 *
 * @since 3.8.14
 * @param response object returned from ajax request
 */
function wpsc_meta_item_change_response( response ) {

	jQuery( ".wpsc-visitor-meta").off( 'change', wpsc_meta_item_change );

	if ( response.hasOwnProperty('success') && response.success && response.hasOwnProperty('data') ) {

		// Whatever replacements have been sent for the checkout form can be efficiently
		// put into view
		if ( response.data.hasOwnProperty('replacements') ) {
			jQuery.each( response.data.replacements, function( elementname, replacement ) {
				jQuery( '#' + replacement.elementid ).replaceWith( replacement.element );
			});
		}


		// Whatever has changed as far as customer meta should be processed
		if ( response.data.hasOwnProperty( 'checkout_info' ) ) {
			if ( ! wpsc_update_checkout_info( response.data.checkout_info ) ) {
				return false;
			}
		}

		// Whatever has changed as far as customer meta should be processed
		if ( response.data.hasOwnProperty( 'customer_meta' ) ) {
			wpsc_update_customer_meta( response );
		}

		// TODO: this is where we can rely on the PHP application to generate and format the content for the
		// checkout screen rather than doing lot's of work in this js.  If we update the PHP application top
		// return the elements for the checkout screen using the same logic that is used when the checkout
		// page is originally created we simplify this script, maintain consistency, allow WordPress and WPEC
		// hooks to be used to change checkout and make chckout display better for those client paltforms
		// that may not have the necessary computing power to use js to do the work we are asking.
		var event = jQuery.Event( "wpsc-visitor-meta-change" );
		event.response = response;
		jQuery( ".wpsc-visitor-meta:first" ).trigger( event );

		// Check if shipping quotes need to be updated
		wpsc_check_for_shipping_recalc_needed( response.data );

	}

	jQuery( ".wpsc-visitor-meta" ).on( "change", wpsc_meta_item_change );

	wpsc_adjust_checkout_form_element_visibility();
}

/**
 * find the WPeC meta key associated with an element, if there is one
 *
 * @param The element to extract the meta key from
 *
 * @returns string meta_key
 */
function wpsc_get_element_meta_key( element ) {

	if ( element instanceof jQuery ) {
		;
	} else if ( typeof input == "string" ) {
		element = wpsc_get_wpsc_meta_element( element );
	} else if ( typeof input == "object" ){
		element = jQuery( element );
	} else {
		return null;
	}

	var meta_key = element.attr( "data-wpsc-meta-key" );

	if ( meta_key === undefined ) {
		meta_key = element.attr( "title" );

		if ( meta_key === undefined ) {
			meta_key = element.attr( "id" );
		}
	}

	return meta_key;
}

/**
 * common callback triggered whenever a WPEC meta value is changed
 *
 * @since 3.8.14
 */
function wpsc_meta_item_change() {

	var meta_value = wpsc_get_value_from_wpsc_meta_element( this );
	var meta_key   = wpsc_get_element_meta_key( jQuery( this ) );

	// if there are other fields on the current page that are used to change the same meta value then
	// they need to be updated
	var selector = '[data-wpsc-meta-key="' + meta_key + '"]';

	var element_that_changed_meta_value = this;

	jQuery( selector ).each( function( index, value ) {
		if ( element_that_changed_meta_value != this ) {
			if ( jQuery(this).is(':checkbox') ) {
				var boolean_meta_value =  wpsc_string_to_boolean( meta_value );
				if ( boolean_meta_value ) {
					jQuery( this ).attr( 'checked', 'checked' );
				} else {
					jQuery( this ).removeAttr( 'checked' );
				}

			} else {
				jQuery( this ).val( meta_value );
			}
		}
	});

	wpsc_update_customer_data( meta_key, meta_value, wpsc_meta_item_change_response );
}

function wpsc_adjust_checkout_form_element_visibility() {

	// make sure any item that changes checkout data is bound to the proper event handler
	jQuery( ".wpsc-visitor-meta" ).off( "change", wpsc_meta_item_change );

	if ( jQuery( "#shippingSameBilling" ).length ) {

		var shipping_row = jQuery( "#shippingSameBilling" ).closest( "tr" );

		if( ! wpsc_show_checkout_shipping_fields() ) {
			jQuery( shipping_row ).siblings( ":not( .checkout-heading-row, :has( #agree ), :has( .wpsc_gateway_container ) ) ").hide();
			jQuery( "#shippingsameasbillingmessage" ).show();
		} else {
			jQuery( shipping_row ).siblings().show();
			jQuery( "#shippingsameasbillingmessage" ).hide();
		}
	}

	wpsc_update_location_elements_visibility();
	wpsc_countries_lists_handle_restrictions();

	// make sure any item that changes checkout data is bound to the proper event handler
	jQuery( ".wpsc-visitor-meta" ).on( "change", wpsc_meta_item_change );

	return true;
}


/*
 * update the countries lists as appropriate based on acceptible shipping countries and
 * shipping same as billing
 *
 * since 3.8.14
 *
 */
function wpsc_countries_lists_handle_restrictions() {

	// if there isn't a list of acceptable countries then we don't have any work to do
	if ( typeof wpsc_acceptable_shipping_countries === "object" ) {

		// we need to know the current billing country
		var current_billing_country  = wpsc_get_value_from_wpsc_meta_element( 'billingcountry' );
		var current_shipping_country = wpsc_get_value_from_wpsc_meta_element( 'shippingcountry' );

		var selector = 'select[data-wpsc-meta-key="shippingcountry"]';

		var put_allowed_countries_into_billing_list = false;

		if ( jQuery( "#shippingSameBilling" ).length ) {
			if ( jQuery( "#shippingSameBilling" ).is(":checked") ) {
				put_allowed_countries_into_billing_list = true;
				selector = selector + ', select[data-wpsc-meta-key="billingcountry"]';
			}
		}

		var country_drop_downs = jQuery( selector );

		country_drop_downs.empty();

		country_drop_downs.append( wpsc_create_option( wpsc_var_get( 'no_country_selected' ) ) );
		for ( var isocode in wpsc_acceptable_shipping_countries ) {
			if ( wpsc_acceptable_shipping_countries.hasOwnProperty( isocode ) ) {
				var country_name = wpsc_acceptable_shipping_countries[isocode];
				country_drop_downs.append( wpsc_create_option( country_name, isocode ) );
			}
		}

		country_drop_downs.val( current_shipping_country );

		if ( ! put_allowed_countries_into_billing_list ) {
			selector = 'select[data-wpsc-meta-key="billingcountry"]';
			country_drop_downs = jQuery( selector );
			if ( country_drop_downs.length ) {
				country_drop_downs.empty();
				country_drop_downs.append( wpsc_create_option( wpsc_var_get( 'no_country_selected' ) ) );
				countries = wpsc_var_get( 'wpsc_countries' );
				for ( var isocode in countries ) {
					if ( countries.hasOwnProperty( isocode ) ) {
						var country_name = countries[isocode];
						country_drop_downs.append( wpsc_create_option( country_name, isocode ) );
				  	}
				}
			}
			country_drop_downs.val( current_billing_country );
		}
	}

	return true;
}

/*
 * Change the labels assicated with country and region fields to match the
 * terminology for the selected location.  For example, regions in the USA are
 * called states, regions in Canada are called provinces
 *
 * since 3.8.14
 *
 */
function wpsc_update_location_labels( country_select ) {

	var country_meta_key = wpsc_get_element_meta_key( country_select ),
		label, country_code;

	if ( country_meta_key == 'billingcountry' ) {

		var billing_state_element = wpsc_get_wpsc_meta_element( 'billingstate' ) ;

		if ( billing_state_element ) {
			country_code = wpsc_get_value_from_wpsc_meta_element( 'billingcountry' );
			label = wpsc_country_region_label( country_code );
			billing_state_element.attr( 'placeholder', label );
			wpsc_update_labels( wpsc_get_wpsc_meta_elements( 'billingstate' ), label );
		}
	} else if ( country_meta_key == 'shippingcountry' ) {

		var shipping_state_element = wpsc_get_wpsc_meta_element( 'shippingstate' );

		if ( shipping_state_element ) {
			country_code = wpsc_get_value_from_wpsc_meta_element( 'shippingcountry' );
			label = wpsc_country_region_label( country_code );
			shipping_state_element.attr( 'placeholder', label );
			wpsc_update_labels( wpsc_get_wpsc_meta_elements( 'shippingstate' ), label );
		}
	}

	return true;
}

/**
 * Fill the associated regions drop down based on the value in the country drop down
 *
 * @param country_select jQuery Object  	Country drop down to work with
 */
function wpsc_update_regions_list_to_match_country( country_select ) {
	var country_meta_key   = wpsc_get_element_meta_key( country_select );
	var region_meta_key;

	if ( country_meta_key.indexOf( "shipping" ) === 0 ) {
		region_meta_key = 'shippingregion';
	} else {
		region_meta_key = 'billingregion';
	}

	var region_select      = wpsc_country_region_element( country_select );
	var all_region_selects = wpsc_get_wpsc_meta_elements( region_meta_key ).filter( 'select' );
	var country_code       = wpsc_get_value_from_wpsc_meta_element( country_select );
	var region             = wpsc_get_value_from_wpsc_meta_element( region_meta_key );

	if ( wpsc_country_has_regions( country_code ) ) {
		var select_a_region_message = wpsc_no_region_selected_message( country_code );
		var regions = wpsc_country_regions( country_code );
		all_region_selects.empty();
		all_region_selects.append( wpsc_create_option( select_a_region_message ) );
		for ( var region_code in regions ) {
		  if ( regions.hasOwnProperty( region_code ) ) {
			  var region_name = regions[region_code];
			  all_region_selects.append( wpsc_create_option( region_name,  region_code ) );
		  }
		}

		if ( region ) {
			all_region_selects.val( region );
		}

		region_select.show();
	} else {
		region_select.hide();
		region_select.empty();
	}

	wpsc_update_location_labels( country_select );
	wpsc_update_location_elements_visibility();
	wpsc_copy_meta_value_to_similiar( country_select );
}

function wpsc_string_to_boolean( string ) {
	return string.trim() !== '';
}

/*
 * Load the region dropdowns based on changes to the country dropdowns
 *
 * since 3.8.14
 *
 */
function wpsc_change_regions_when_country_changes() {
	wpsc_copy_meta_value_to_similiar( jQuery( this ) );
	wpsc_update_regions_list_to_match_country( jQuery( this ) );
	return true;
}

function wpsc_copy_meta_value_to_similiar( element ) {

	var element_meta_key = wpsc_get_element_meta_key( element ),
		meta_value = element.val(),
		current_value;

	// if there are other fields on the current page that are used to change the same meta value then
	// they need to be updated
	var selector = '[data-wpsc-meta-key="' + element_meta_key + '"]';

	jQuery( selector ).each( function( index, value ) {
		if ( this != element) {

			if ( jQuery(this).is(':checkbox') ) {
				var boolean_meta_value =  wpsc_string_to_boolean( meta_value );
				if ( boolean_meta_value ) {
					jQuery( this ).attr( 'checked', 'checked' );
				} else {
					jQuery( this ).removeAttr( 'checked' );
				}
			} else {
				current_value = jQuery( this ).val();
				if ( current_value != meta_value && meta_value ) {
					jQuery( this ).val( meta_value );
				}
			}
		}
	});
}

/*
 * returns the element id for the cehckout item if it is in the checkout form
 *
 * @since 3.8.14
 *
 * @param string 	name		unqiue name of the checkout item
 *
 * @return int|boolean			element id if it is in the checkout form, false if the element is not in the checkout form
 */
function wpsc_checkout_item_form_id( name ) {

	var map_from_name_to_id = wpsc_var_get( 'wpsc_checkout_unique_name_to_form_id_map' );

	var checkout_item_form_id = false;

	if ( map_from_name_to_id )  {
		if ( map_from_name_to_id.hasOwnProperty( name ) ) {
			checkout_item_form_id = map_from_name_to_id[name];
		}
	}

	return checkout_item_form_id;
}

/*
 * decide if shipping fields should be show or not
 */
function wpsc_show_checkout_shipping_fields() {
	// we will need to know if shipping fields should be show or not, if there
	// isn't a shipping same as billing element, then we show by default, if there
	// is a shipping same as billing we show if the element is not checked
	var show_shipping_field = true;
	if( jQuery("#shippingSameBilling").length ) {
		show_shipping_field = ! jQuery("#shippingSameBilling").is(":checked");
	}

	return show_shipping_field;
}

function wpsc_setup_region_dropdowns() {

	wpsc_get_wpsc_meta_elements( 'billingcountry' ).each( function( index, value ){
		 wpsc_update_regions_list_to_match_country( jQuery( this ) );
	});

	wpsc_get_wpsc_meta_elements( 'shippingcountry' ).each( function( index, value ){
		 wpsc_update_regions_list_to_match_country( jQuery( this ) );
	});
}


/**
 * changes the visibility of the  region edit element and the region drop down element based on
 *  on the state and contents of the coutnry drop down
 *
 *  @since 3.8.14
 *
 *  @returns {Boolean}
 */
function wpsc_update_location_elements_visibility() {

	if ( wpsc_checkout_item_active( 'billingstate' ) ) {
		// for convenience, get the jQuery objects for each of the billing elements we want to manipulate up front
		var billing_state_elements = wpsc_get_wpsc_meta_elements( 'billingstate' );
		var billing_region_elements = wpsc_get_wpsc_meta_elements( 'billingregion' );

		if ( wpsc_billing_country_has_regions() ) {
			billing_region_elements.show();
			billing_state_elements.hide();
		} else {
			billing_region_elements.hide();
			billing_state_elements.show();
		}
	}

	if ( wpsc_checkout_item_active( 'shippingstate' ) ) {
		// for convenience, get the jQuery objects for each of the billing elements we want to manipulate up front
		var shipping_state_elements  = wpsc_get_wpsc_meta_elements( 'shippingstate' );
		var shipping_region_elements = wpsc_get_wpsc_meta_elements( 'shippingregion' );

		if ( wpsc_shipping_country_has_regions() ) {
			shipping_region_elements.show();
			shipping_state_elements.hide();
		} else {
			shipping_region_elements.hide();
			shipping_state_elements.show();
		}
	}

	return true;
}

function wpsc_country_has_regions( country_code ) {
	var regions_object_name = "wpsc_country_" + country_code + "_regions";
	return wpsc_var_isset( regions_object_name );
}

function wpsc_country_regions( country_code ) {
	var regions_object_name = "wpsc_country_" + country_code + "_regions";
	return wpsc_var_get( regions_object_name );
}

function wpsc_country_region_label( country_code ) {
	var regions_label_name = "wpsc_country_" + country_code + "_region_label";
	var label = wpsc_var_get( regions_label_name );
	if ( ! label ) {
		label = wpsc_var_get( 'no_region_label' );
	}

	return label;
}

function wpsc_current_destination_country() {
	return wpsc_get_value_from_wpsc_meta_element( 'shippingcountry' );
}

function wpsc_no_region_selected_message( country_code ) {
	var label = wpsc_country_region_label( country_code );
	var format = wpsc_var_get( 'no_region_selected_format' );
	var message = format.replace("%s",label);
	return message;
}

function wpsc_get_label_element( input ) {

	var input_element;

	if ( input instanceof jQuery ) {
		input_element = input;
	} else if ( typeof input == "string" ) {
		input_element = wpsc_get_wpsc_meta_element( input );
	} else if ( typeof input == "object" ){
		input_element = jQuery( input );
	} else {
		return null;
	}

	var label_element;

	if ( input_element ) {
		var element_id = input_element.attr('id');
		if ( element_id ) {
			label_element = jQuery( "label[for='" + element_id + "']" ).first();
		}
	}

	return label_element;
}

function wpsc_update_labels( elements, label ) {
	elements.each( function( index, value ){
		var label_element = wpsc_get_label_element( jQuery( this ) );
		if ( label_element !== undefined ) {

			if ( label_element.find('.asterix') ) {
				label = label + '<span class="asterix">*</span>';
			}

			label_element.html( label );
		}
	});
}

function wpsc_get_wpsc_meta_element( meta_key ) {
	var elements = wpsc_get_wpsc_meta_elements( meta_key );
	return elements.first();
}

function wpsc_get_wpsc_meta_elements( meta_key ) {
	var selector = '[data-wpsc-meta-key="' + meta_key + '"]';
	var elements = jQuery( selector );
	return elements;
}

function wpsc_get_value_from_wpsc_meta_element( meta ) {
	var element;

	if ( meta instanceof jQuery ) {
		element = meta;
	} else if ( typeof meta == "string" ) {
		element = wpsc_get_wpsc_meta_element( meta );
	} else if ( typeof meta == "object" ){
		element = jQuery( meta );
	} else {
		return null;
	}

	var meta_value = false;

	if ( element.is(':checkbox') ) {
		if ( element.is(':checked') ) {
			meta_value = element.val();
		} else {
			meta_value = '';
		}
	} else if ( element.is('select') ) {
		meta_value = element.val();
		if ( ! meta_value && 'none' == element.css('display') ) {
			meta_value = element.find( 'option[selected]' ).val();
		}

	} else 	{
		meta_value = element.val();
	}

	return meta_value;
}

/*
 * find the region dropdown that goes with the country dropdown
 *
 * since 3.8.14
 *
 */
function wpsc_country_region_element( country ) {

	// if the meta key was was given as the arument we can find the element easy enough
	if ( typeof country == "string" ) {
		country = wpsc_get_wpsc_meta_element( country );
	}

	var country_id = country.attr('id');
	var region_id = country_id + "_region";
	var region_select = jQuery( "#" + region_id );

	return region_select;
}

/**
 * process region drop down change event
 *
 * @since 3.8.14
 */
function wpsc_region_change() {
	wpsc_copy_meta_value_to_similiar( jQuery( this ) );
}

function wpsc_checkout_item_active( $checkout_item ) {
	var active_items = wpsc_var_get( "wpsc_checkout_item_active" );

	if ( 'undefined' === typeof active_items ) {
		return false;
	}

	var is_active = active_items.hasOwnProperty( $checkout_item ) && active_items[$checkout_item];

	return is_active;
}


function wpsc_billing_country_has_regions() {
	var has_regions = false;

	var country_code = wpsc_billing_country();

	if ( country_code ) {
		has_regions = wpsc_country_has_regions( country_code );
	}

	return has_regions;
}

function wpsc_billing_country() {
	var billing_country_active = wpsc_checkout_item_active( 'billingcountry' );

	var country_code;

	if ( billing_country_active ) {
		country_code = wpsc_get_value_from_wpsc_meta_element( 'billingcountry' );
	} else {
		country_code = wpsc_var_get( "base_country" );
	}

	return country_code;
}


function wpsc_shipping_country() {
	var shipping_country_active = wpsc_checkout_item_active( 'shippingcountry' );

	var country_code;

	if ( shipping_country_active ) {
		country_code = wpsc_get_value_from_wpsc_meta_element( 'shippingcountry' );
	} else {
		country_code = wpsc_var_get( "base_country" );
	}

	return country_code;
}


function wpsc_shipping_country_has_regions() {
	var has_regions = false;

	var country_code = wpsc_shipping_country();

	if ( country_code ) {
		has_regions = wpsc_country_has_regions( country_code );
	}

	return has_regions;
}


/**
 * ready to setup the events for user actions that casuse meta item changes
 *
 * @since 3.8.14
 */
jQuery(document).ready(function ($) {

	//////////////////////////////////////////////////////////////////////////////////////////////////////
	// a check for backwards compatibility due in row visibility.  Starting in version 3.8.14 the row
	// with the billing state also contains the billing region, same for shipping state and shipping
	// region, prior versions use to hide the row, we don't want to do that anymore.  So that older
	// themes work we will remove the class that hides the row
	var selector = 'select[data-wpsc-meta-key="shippingregion"]';
	var select = jQuery( selector ).first();
	if ( select.closest( 'tr' ).hasClass( 'wpsc_hidden' ) ) {
		select.closest( 'tr' ).removeClass( 'wpsc_hidden' );
	}

	var selector = 'select[data-wpsc-meta-key="billingregion"]';
	var select = jQuery( selector ).first();
	if ( select.closest( 'tr' ).hasClass( 'wpsc_hidden' ) ) {
		select.closest( 'tr' ).removeClass( 'wpsc_hidden' );
	}
	// end of backwards compatibility code
	//////////////////////////////////////////////////////////////////////////////////////////////////////

	if ( jQuery( ".wpsc-country-dropdown" ).length ) {
		jQuery( ".wpsc-country-dropdown"   ).on( 'change', wpsc_change_regions_when_country_changes );
	}

	if ( jQuery( ".wpsc-region-dropdown" ).length ) {
		jQuery( ".wpsc-region-dropdown"   ).on( 'change', wpsc_region_change );
	}

	if ( jQuery( ".wpsc-visitor-meta" ).length ) {
		jQuery( ".wpsc-visitor-meta").on( "change", wpsc_meta_item_change );
	}

	// setup checkout form and make sure visibility of form elements is what it should be
	wpsc_setup_region_dropdowns();
	wpsc_adjust_checkout_form_element_visibility();
	wpsc_update_location_elements_visibility();
	wpsc_countries_lists_handle_restrictions();

	jQuery( "#shippingSameBilling"  ).on( 'change', wpsc_adjust_checkout_form_element_visibility );

	if ( jQuery('#checkout_page_container .wpsc_email_address input').val() ) {
		jQuery('#wpsc_checkout_gravatar').attr('src', 'https://secure.gravatar.com/avatar/'+MD5(jQuery('#checkout_page_container .wpsc_email_address input').val().split(' ').join(''))+'?s=60&d=mm');
	}

	jQuery('#checkout_page_container .wpsc_email_address input').keyup(function(){
		jQuery('#wpsc_checkout_gravatar').attr('src', 'https://secure.gravatar.com/avatar/'+MD5(jQuery(this).val().split(' ').join(''))+'?s=60&d=mm');
	});

	/* Clears shipping state and billing state on body load if they are numeric */
	$( 'input[title="shippingstate"], input[title="billingstate"]' ).each( function( index, value ){
		var $this = $( this ), $val = $this.val();

		if ( $this.is( ':visible' ) && ! isNaN( parseFloat( $val ) ) && isFinite( $val ) ) {
			$this.val( '' );
		}

	});

	// Submit the product form using AJAX
	jQuery( 'form.product_form, .wpsc-add-to-cart-button-form' ).on( 'submit', function() {
		// we cannot submit a file through AJAX, so this needs to return true to submit the form normally if a file formfield is present
		file_upload_elements = jQuery.makeArray( jQuery( 'input[type="file"]', jQuery( this ) ) );
		if(file_upload_elements.length > 0) {
			return true;
		} else {

			var action_buttons = jQuery( 'input[name="wpsc_ajax_action"]', jQuery( this ) );

			var action;
			if ( action_buttons.length > 0 ) {
				action = action_buttons.val();
			} else {
				action = 'add_to_cart';
			}

			form_values = jQuery(this).serialize() + '&action=' + action;

			jQuery( 'div.wpsc_loading_animation', this ).css( 'visibility', 'visible' );

			var success = function( response ) {
				if ( ( response ) ) {
					jQuery('div.shopping-cart-wrapper').html( response.widget_output );
					jQuery('div.wpsc_loading_animation').css('visibility', 'hidden');

					jQuery( '.cart_message' ).delay( 3000 ).slideUp( 500 );

					// Until we get to an acceptable level of education on the new custom event - this is probably necessary for plugins.
					if ( response.wpsc_alternate_cart_html ) {
						eval( response.wpsc_alternate_cart_html );
					}

				}

				jQuery( document ).trigger( { 'type' : 'wpscAddedToCart', 'response' : response } );

			};

			jQuery( document ).trigger( { 'type' : 'wpscAddToCart', 'form' : this } );

			jQuery.post( wpsc_ajax.ajaxurl, form_values, success, 'json' );

			return false;
		}
	});

	jQuery( 'a.wpsc_category_link, a.wpsc_category_image_link' ).click(function(){
		product_list_count = jQuery.makeArray(jQuery('ul.category-product-list'));
		if(product_list_count.length > 0) {
			jQuery('ul.category-product-list', jQuery(this).parent()).toggle();
			return false;
		}
	});

	// Toggle the additional description content
	jQuery("a.additional_description_link").click(function() {
		parent_element = jQuery(this).parent(".additional_description_container, .additional_description_span");
		jQuery('.additional_description',parent_element).slideToggle('fast');
		return false;
	});

	// update the price when the variations are altered.
	jQuery( 'div.wpsc_variation_forms' ).on( 'change', '.wpsc_select_variation', function() {
		jQuery( 'option[value="0"]', this ).attr('disabled', 'disabled');
		var self = this;
		var parent_form = jQuery(this).closest("form.product_form");

		if ( parent_form.length === 0 ) {
			return;
		}

		var prod_id = jQuery("input[name='product_id']",parent_form).val();
		var form_values = jQuery("input[name='product_id'], .wpsc_select_variation",parent_form).serialize() + '&action=update_product_price';

		jQuery.post( wpsc_ajax.ajaxurl, form_values, function(response) {
			var variation_display = jQuery('div#variation_display_' + prod_id );
			var stock_display = jQuery('div#stock_display_' + prod_id),
				price_field = jQuery('input#product_price_' + prod_id),
				price_span = jQuery('#product_price_' + prod_id + '.pricedisplay, #product_price_' + prod_id + ' .currentprice'),
				donation_price = jQuery('input#donation_price_' + prod_id),
				old_price = jQuery('#old_product_price_' + prod_id),
				save = jQuery('#yousave_' + prod_id),
				buynow = jQuery('#buy-now-product_' + prod_id);

			jQuery( document ).trigger( { type : 'wpsc_select_variation', response : response } );

			if ( response.variation_found ) {

				jQuery( '.wpsc_buy_button', parent_form ).prop( 'disabled', false ).css( 'cursor', 'pointer' );


				if ( response.stock_available ) {
					jQuery( '.wpsc_buy_button', parent_form ).prop( 'disabled', false ).css( 'cursor', 'pointer' );
					stock_display.removeClass('out_of_stock').addClass('in_stock');
				} else {
					jQuery( '.wpsc_buy_button', parent_form ).prop( 'disabled', true ).css( 'cursor', 'not-allowed' );
					stock_display.addClass('out_of_stock').removeClass('in_stock');
				}

				variation_display.removeClass('no_variation').addClass('is_variation');

			} else {
				jQuery( '.wpsc_buy_button', parent_form ).prop( 'disabled', true ).css( 'cursor', 'not-allowed' );
				variation_display.removeClass('is_variation').addClass('no_variation');
			}

			stock_display.html(response.variation_msg);

			if ( response.price !== undefined ) {
				if (price_field.length && price_field.attr('type') == 'text') {
					price_field.val(response.numeric_price);
					old_price.parent().hide();
					save.parent().hide();
				} else {
					price_span.html(response.price);
					old_price.html(response.old_price);
					save.html(response.you_save);
					if (response.numeric_old_price > response.numeric_price) {
						old_price.parent().show();
						save.parent().show();
					} else {
						old_price.parent().hide();
						save.parent().hide();
					}
				}

				donation_price.val(response.numeric_price);

				//Set quantity field based on response for wpsc_check_variation_stock_availability
				if ( response.stock_available === true ) {
					buynow.find( 'input.wpsc-buy-now-quantity' ).val( '1' );
					buynow.find( 'input.wpsc-buy-now-button' ).prop( 'disabled', false );
				} else {
					buynow.find( 'input.wpsc-buy-now-button' ).prop( 'disabled', true );
				}

				buynow.find('input[name="'+jQuery(self).prop('name')+'"]').val(jQuery(self).val());
			}
		}, 'json' );
	});

		//this is for storing data with the product image, like the product ID, for things like dropshop and the like.
	jQuery( 'form.product_form' ).ready(  function() {
			product_id = jQuery('input[name="product_id"]',this).val();
			image_element_id = 'product_image_'+product_id;
			jQuery( "#"+image_element_id).data("product_id", product_id );
			parent_container = jQuery(this).parents('div.product_view_'+product_id);
			jQuery("div.item_no_image", parent_container).data("product_id", product_id);
		});


	// Ajax cart loading code.
	// if we have a cart widget on our page we need to load it via AJAX jsut in case there is a page cache or
	// content delivery network being used to help deliver pages.
	// If we are on the checkout page, we don't need to make the AJAX call because checkout pages
	// are never, and can never be, cached.
	// If we are on a checkout page then we know the page is not cached
	if ( jQuery( 'div.wpsc_cart_loading' ).length  ) {
		if ( ! ( jQuery( 'table.wpsc_checkout_table' ).length && jQuery( '.wpsc_buy_button' ).length) ) {
			jQuery( 'div.wpsc_cart_loading' ).ready( function(){
					form_values = { action : 'get_cart' };
				jQuery.ajax({
					type : "post",
					dataType : "html",
					url : wpsc_ajax.ajaxurl,
					data : {action : 'get_cart'},
					success: function (response) {
						jQuery( 'div.shopping-cart-wrapper' ).html( response );
						jQuery('div.wpsc_loading_animation').css('visibility', 'hidden');
					},
					error: function (result) {
						jQuery( 'div.shopping-cart-wrapper' ).html( wpsc_ajax.ajax_get_cart_error );
						jQuery('div.wpsc_loading_animation').css('visibility', 'hidden');
					}
				});
			});
		}
	}

	jQuery( 'body' ).on( 'click', 'a.emptycart', function(){
		parent_form = jQuery(this).parents( 'form' );
		form_values = jQuery(parent_form).serialize() + '&action=' + jQuery( 'input[name="wpsc_ajax_action"]', parent_form ).val();

		jQuery.post( wpsc_ajax.ajaxurl, form_values, function(response) {
			jQuery('div.shopping-cart-wrapper').html( response.widget_output );
			jQuery( document ).trigger( { type : 'wpsc_empty_cart', response : response } );
		}, 'json');

		return false;
	});
});

// update the totals when shipping methods are changed.
function switchmethod( key, key1 ) {
	data = {
		action : 'update_shipping_price',
		option : key,
		method : key1
	};
	jQuery.post( wpsc_ajax.ajaxurl, data, function( response ) {

		jQuery( document ).trigger( { type : 'switchmethod', response : response } );

		if ( jQuery( '.pricedisplay.checkout-shipping .pricedisplay' ) ) {
			jQuery( '.pricedisplay.checkout-shipping > .pricedisplay:first' ).html( response.shipping );
			jQuery( '.shoppingcart .pricedisplay.checkout-shipping > .pricedisplay:first' ).html( response.shipping );
		} else {
			jQuery( '.pricedisplay.checkout-shipping' ).html( response.shipping );
		}

		if ( jQuery( '#coupons_amount .pricedisplay' ).size() > 0 ) {
			jQuery( '#coupons_amount .pricedisplay' ).html( response.coupon );
		} else {
			jQuery( '#coupons_amount' ).html( response.coupon );
		}

		if ( jQuery( '#checkout_tax.pricedisplay' ).size() > 0 ) {
			jQuery( '.pricedisplay.checkout-tax' ).html( response.tax );
		}

		jQuery( '.pricedisplay.checkout-total' ).html( response.cart_total );

	}, 'json' );
}

// submit the country forms.
function submit_change_country(){
	document.forms.change_country.submit();
}

/**
 * Submit the fancy notifications forms.
 *
 * @deprecated  Since 4.0. Use WPEC_Fancy_Notifications.fancy_notification() instead.
 *
 * @param  object  parent_form  Deprecated. Form element. Kept for legacy purposes.
 */
function wpsc_fancy_notification( parent_form ) {

	if ( window.console && window.console.log ) {
		console.log( 'wpsc_fancy_notification() is deprecated. Use WPEC_Fancy_Notifications.fancy_notification() instead.' );
	}

	WPEC_Fancy_Notifications.fancy_notification();

}

function shopping_cart_collapser() {
	form_values = { set_slider : 'true' };

	switch(jQuery("#sliding_cart").css("display")) {
		case 'none':
			form_values.state = '1';
			jQuery("#sliding_cart").slideToggle("fast",function(){
				jQuery.post( wpsc_ajax.ajaxurl, form_values, function(returned_data) { });
				jQuery("#fancy_collapser").attr("src", (WPSC_CORE_IMAGES_URL + "/minus.png"));
			});
			break;

		default:
			form_values.state = '0';
			jQuery("#sliding_cart").slideToggle("fast",function(){
				jQuery.post( wpsc_ajax.ajaxurl, form_values, function(returned_data) { });
				jQuery("#fancy_collapser").attr("src", (WPSC_CORE_IMAGES_URL + "/plus.png"));
			});
			break;
	}
	return false;
}

function wpsc_set_profile_country(html_form_id, form_id) {
	var country_field = jQuery('#' + html_form_id),
	form_values = {
		action  : "change_profile_country",
		form_id : form_id,
		country : country_field.val()
	};

	jQuery.post( wpsc_ajax.ajaxurl, form_values, function(response) {
		country_field.siblings('select').remove();
		if (response.has_regions) {
			country_field.after('<br />' + response.html);
			jQuery('input[name="collected_data[' + response.region_field_id + ']"]').closest('tr').hide();
		} else {
			jQuery('input[name="collected_data[' + response.region_field_id + ']"]').closest('tr').show();
		}
	}, 'json');
}

//Javascript for variations: bounce the variation box when nothing is selected and return false for add to cart button.
jQuery(document).ready(function(){
	jQuery('.productcol, .textcol, .product_grid_item, .wpsc-add-to-cart-button').each(function(){
		jQuery('.wpsc_buy_button', this).click(function(){
			var dropdowns = jQuery(this).closest('form').find('.wpsc_select_variation');
			var not_selected = false;
			dropdowns.each(function(){
				var t = jQuery(this);
				if(t.val() <= 0){
					not_selected = true;
					t.css('position','relative');
					t.animate({'left': '-=5px'}, 50, function(){
						t.animate({'left': '+=10px'}, 100, function(){
							t.animate({'left': '-=10px'}, 100, function(){
								t.animate({'left': '+=10px'}, 100, function(){
									t.animate({'left': '-=5px'}, 50);
								});
							});
						});
					});
				}
			});
			if (not_selected)
				return false;
		});
	});
});

jQuery(document).ready(function(){
	jQuery('.attachment-gold-thumbnails').click(function(){
		jQuery(this).parents('.imagecol:first').find('.product_image').attr('src', jQuery(this).parent().attr('rev'));
		jQuery(this).parents('.imagecol:first').find('.product_image').parent('a:first').attr('href', jQuery(this).parent().attr('href'));
		return false;
	});
});

//MD5 function for gravatars
eval(function(p,a,c,k,e,d){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--){d[e(c)]=k[c]||e(c)}k=[function(e){return d[e]}];e=function(){return'\\w+'};c=1};while(c--){if(k[c]){p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c])}}return p}('e 27=o(p){o 1c(N,1y){m(N<<1y)|(N>>>(32-1y))}o f(1k,1e){e 1j,1l,E,B,w;E=(1k&1r);B=(1e&1r);1j=(1k&1f);1l=(1e&1f);w=(1k&1B)+(1e&1B);V(1j&1l){m(w^1r^E^B)}V(1j|1l){V(w&1f){m(w^1Z^E^B)}1h{m(w^1f^E^B)}}1h{m(w^E^B)}}o F(x,y,z){m(x&y)|((~x)&z)}o G(x,y,z){m(x&z)|(y&(~z))}o H(x,y,z){m(x^y^z)}o I(x,y,z){m(y^(x|(~z)))}o l(a,b,c,d,x,s,v){a=f(a,f(f(F(b,c,d),x),v));m f(1c(a,s),b)};o j(a,b,c,d,x,s,v){a=f(a,f(f(G(b,c,d),x),v));m f(1c(a,s),b)};o h(a,b,c,d,x,s,v){a=f(a,f(f(H(b,c,d),x),v));m f(1c(a,s),b)};o i(a,b,c,d,x,s,v){a=f(a,f(f(I(b,c,d),x),v));m f(1c(a,s),b)};o 1A(p){e A;e J=p.1g;e 1q=J+8;e 1D=(1q-(1q%1G))/1G;e 1m=(1D+1)*16;e t=1z(1m-1);e K=0;e q=0;24(q<J){A=(q-(q%4))/4;K=(q%4)*8;t[A]=(t[A]|(p.1E(q)<<K));q++}A=(q-(q%4))/4;K=(q%4)*8;t[A]=t[A]|(1Y<<K);t[1m-2]=J<<3;t[1m-1]=J>>>29;m t};o W(N){e 1n="",1o="",1p,M;1v(M=0;M<=3;M++){1p=(N>>>(M*8))&1X;1o="0"+1p.1U(16);1n=1n+1o.1V(1o.1g-2,2)}m 1n};o 1C(p){p=p.1W(/\\r\\n/g,"\\n");e u="";1v(e n=0;n<p.1g;n++){e c=p.1E(n);V(c<1i){u+=D.C(c)}1h V((c>1T)&&(c<25)){u+=D.C((c>>6)|26);u+=D.C((c&1s)|1i)}1h{u+=D.C((c>>12)|2c);u+=D.C(((c>>6)&1s)|1i);u+=D.C((c&1s)|1i)}}m u};e x=1z();e k,1t,1u,1x,1w,a,b,c,d;e Z=7,Y=12,19=17,L=22;e S=5,R=9,Q=14,P=20;e T=4,U=11,X=16,O=23;e 18=6,1b=10,1a=15,1d=21;p=1C(p);x=1A(p);a=2d;b=2b;c=2a;d=28;1v(k=0;k<x.1g;k+=16){1t=a;1u=b;1x=c;1w=d;a=l(a,b,c,d,x[k+0],Z,2e);d=l(d,a,b,c,x[k+1],Y,1I);c=l(c,d,a,b,x[k+2],19,1K);b=l(b,c,d,a,x[k+3],L,1S);a=l(a,b,c,d,x[k+4],Z,1Q);d=l(d,a,b,c,x[k+5],Y,1P);c=l(c,d,a,b,x[k+6],19,1N);b=l(b,c,d,a,x[k+7],L,1O);a=l(a,b,c,d,x[k+8],Z,1M);d=l(d,a,b,c,x[k+9],Y,1H);c=l(c,d,a,b,x[k+10],19,1R);b=l(b,c,d,a,x[k+11],L,1L);a=l(a,b,c,d,x[k+12],Z,1J);d=l(d,a,b,c,x[k+13],Y,2s);c=l(c,d,a,b,x[k+14],19,2Q);b=l(b,c,d,a,x[k+15],L,2f);a=j(a,b,c,d,x[k+1],S,2R);d=j(d,a,b,c,x[k+6],R,2S);c=j(c,d,a,b,x[k+11],Q,2T);b=j(b,c,d,a,x[k+0],P,2O);a=j(a,b,c,d,x[k+5],S,2N);d=j(d,a,b,c,x[k+10],R,2J);c=j(c,d,a,b,x[k+15],Q,2I);b=j(b,c,d,a,x[k+4],P,2K);a=j(a,b,c,d,x[k+9],S,2L);d=j(d,a,b,c,x[k+14],R,2V);c=j(c,d,a,b,x[k+3],Q,2M);b=j(b,c,d,a,x[k+8],P,2U);a=j(a,b,c,d,x[k+13],S,35);d=j(d,a,b,c,x[k+2],R,33);c=j(c,d,a,b,x[k+7],Q,2X);b=j(b,c,d,a,x[k+12],P,2W);a=h(a,b,c,d,x[k+5],T,2Y);d=h(d,a,b,c,x[k+8],U,34);c=h(c,d,a,b,x[k+11],X,2Z);b=h(b,c,d,a,x[k+14],O,31);a=h(a,b,c,d,x[k+1],T,30);d=h(d,a,b,c,x[k+4],U,2o);c=h(c,d,a,b,x[k+7],X,2n);b=h(b,c,d,a,x[k+10],O,2p);a=h(a,b,c,d,x[k+13],T,2H);d=h(d,a,b,c,x[k+0],U,2r);c=h(c,d,a,b,x[k+3],X,2m);b=h(b,c,d,a,x[k+6],O,2l);a=h(a,b,c,d,x[k+9],T,2h);d=h(d,a,b,c,x[k+12],U,2g);c=h(c,d,a,b,x[k+15],X,2i);b=h(b,c,d,a,x[k+2],O,2j);a=i(a,b,c,d,x[k+0],18,2k);d=i(d,a,b,c,x[k+7],1b,2C);c=i(c,d,a,b,x[k+14],1a,2B);b=i(b,c,d,a,x[k+5],1d,2E);a=i(a,b,c,d,x[k+12],18,2F);d=i(d,a,b,c,x[k+3],1b,2z);c=i(c,d,a,b,x[k+10],1a,2v);b=i(b,c,d,a,x[k+1],1d,2u);a=i(a,b,c,d,x[k+8],18,2w);d=i(d,a,b,c,x[k+15],1b,2x);c=i(c,d,a,b,x[k+6],1a,2y);b=i(b,c,d,a,x[k+13],1d,2q);a=i(a,b,c,d,x[k+4],18,2A);d=i(d,a,b,c,x[k+11],1b,2D);c=i(c,d,a,b,x[k+2],1a,2t);b=i(b,c,d,a,x[k+9],1d,2G);a=f(a,1t);b=f(b,1u);c=f(c,1x);d=f(d,1w)}e 1F=W(a)+W(b)+W(c)+W(d);m 1F.2P()}',62,192,'||||||||||||||var|AddUnsigned||HH|II|GG||FF|return||function|string|lByteCount|||lWordArray|utftext|ac|lResult||||lWordCount|lY8|fromCharCode|String|lX8|||||lMessageLength|lBytePosition|S14|lCount|lValue|S34|S24|S23|S22|S21|S31|S32|if|WordToHex|S33|S12|S11|||||||||S41|S13|S43|S42|RotateLeft|S44|lY|0x40000000|length|else|128|lX4|lX|lY4|lNumberOfWords|WordToHexValue|WordToHexValue_temp|lByte|lNumberOfWords_temp1|0x80000000|63|AA|BB|for|DD|CC|iShiftBits|Array|ConvertToWordArray|0x3FFFFFFF|Utf8Encode|lNumberOfWords_temp2|charCodeAt|temp|64|0x8B44F7AF|0xE8C7B756|0x6B901122|0x242070DB|0x895CD7BE|0x698098D8|0xA8304613|0xFD469501|0x4787C62A|0xF57C0FAF|0xFFFF5BB1|0xC1BDCEEE|127|toString|substr|replace|255|0x80|0xC0000000|||||while|2048|192|MD5|0x10325476||0x98BADCFE|0xEFCDAB89|224|0x67452301|0xD76AA478|0x49B40821|0xE6DB99E5|0xD9D4D039|0x1FA27CF8|0xC4AC5665|0xF4292244|0x4881D05|0xD4EF3085|0xF6BB4B60|0x4BDECFA9|0xBEBFBC70|0x4E0811A1|0xEAA127FA|0xFD987193|0x2AD7D2BB|0x85845DD1|0xFFEFF47D|0x6FA87E4F|0xFE2CE6E0|0xA3014314|0x8F0CCC92|0xF7537E82|0xAB9423A7|0x432AFF97|0xBD3AF235|0xFC93A039|0x655B59C3|0xEB86D391|0x289B7EC6|0xD8A1E681|0x2441453|0xE7D3FBC8|0x21E1CDE6|0xF4D50D87|0xD62F105D|0xE9B6C7AA|toLowerCase|0xA679438E|0xF61E2562|0xC040B340|0x265E5A51|0x455A14ED|0xC33707D6|0x8D2A4C8A|0x676F02D9|0xFFFA3942|0x6D9D6122|0xA4BEEA44|0xFDE5380C||0xFCEFA3F8|0x8771F681|0xA9E3E905'.split('|'),0,{}));
