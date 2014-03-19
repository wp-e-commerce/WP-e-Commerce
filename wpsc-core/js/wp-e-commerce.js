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

if ( ! ( document.cookie.indexOf("wpsc_customer_cookie") >= 0 ) ) {
	if ( ! ( document.cookie.indexOf("wpsc_attempted_validate") >= 0 ) ) {	
		// create a cookie to signal that we have attempted validation.  If we find the cookie is set
		// we don't re-attempt validation.  This means will only try to validate once and not slow down
		// subsequent page views. 
		
		// The lack of expiration date means the cookie will be deleted when the browser
		// is closed, so the next time the visitor attempts to access the site 
		var now = new Date();
		document.cookie="wpsc_attempted_validate="+now;

		var wpsc_http = new XMLHttpRequest();
		wpsc_http.overrideMimeType( "application/json" );
		
		// open setup and send the request in synchronous mode
		wpsc_http.open( "POST",wpsc_ajax.ajaxurl + "?action=wpsc_validate_customer", false );
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



/**
 * update a customer meta value 
 * 
 * @since 3.8.14
 * @param meta_key string
 * @param meta_value string
 * @param response_callback function
 */
function wpsc_update_customer_data( meta_key, meta_value, response_callback ) {
	
//	jQuery.ajax({
//		type : "post",
//		dataType : "json",
//		url : wpsc_ajax.ajaxurl,
//		data : {action: 'wpsc_update_customer_meta', meta_key : meta_key, meta_value : meta_value },
//		success: function (response) {
//			if ( response_callback ) {
//				response_callback( response );
//			}
//		},
//		error: function (response) { 
//			; // this is a place to set a breakpoint if you are concerned that meta item update ajax call is not functioning as designed 
//		},
//	});	
	
	// wrap our ajax request in a try/catch so that an error doesn't stop the script from running
	try { 	
		var ajax_data = {action: 'wpsc_update_customer_meta', meta_key : meta_key, meta_value : meta_value };	
		jQuery.post( wpsc_ajax.ajaxurl, ajax_data, response_callback,  "json" );
	} catch ( err ) {
		; // we could handle the error here, or use it as a convenient place to set a breakpoint when debugging/testing
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
		jQuery.post( wpsc_ajax.ajaxurl, ajax_data, response_callback,  "json" );
	} catch ( err ) {
		; // we could handle the error here, or use it as a convenient place to set a breakpoint when debugging/testing
	}
}		

/**
 * common callback to update fields based on response from ajax processing.  
 * 
 * @since 3.8.14
 * @param response object returned from ajax request
 */
function wpsc_update_customer_meta( response ) {
	
	var customer_meta = response.data.customer_meta;
	
	// if the response includes customer meta values find out where the value 
	// belongs and then put it there 
	jQuery.each( customer_meta,  function( meta_key, meta_value ) {
		
		// if there are other fields on the current page that are used to change the same meta value then 
		// they need to be updated
		var selector = '[data-wpsc-meta-key="' + meta_key + '"]';
		
		jQuery( selector ).each( function( index, value ) {		
			if ( jQuery(this).is(':checkbox') ) {
				var boolean_meta_value = meta_value == "1"; 
				if ( boolean_meta_value ) {
					jQuery( this ).attr( 'checked', 'checked' );
				} else {
					jQuery( this ).removeAttr( 'checked' );
				}
				
			} else {
				if ( jQuery( this ).val() != meta_value ) {
					jQuery( this ).val( meta_value );
				}
			}
		});
	});

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
	
	// TODO: if shipping needs to be re-calccualted we need to refresh the page.  This is the pnly option
	// in version 3.8.14 and earlier.  Future versions should support replacing the shipping quote elements
	// via AJAX
	if ( checkout_info.hasOwnProperty( 'needs_shipping_recalc' ) ) {
		if ( checkout_info.needs_shipping_recalc ) {
			location.reload();
			return false;
		}
	}

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
	
	wpsc_adjust_checkout_form_element_visibility();
	
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
	
	jQuery( ".wpsc-visitor-meta").off( "change", wpsc_meta_item_change );
	
	if ( response.type == 'success' ) {		

		// Whatever replacements have been sent for the checkout form can be efficiently
		// put into view
		if ( response.data.hasOwnProperty('replacements') ) {
			jQuery.each( response.replacements, function( elementname, replacement ) {
				jQuery( '#'+replacement.elementid ).replaceWith( replacement.element );
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
			wpsc_update_customer_meta( response.data.customer_meta );
		}

		// TODO: this is where we can rely on the PHP application to generate and format the content for the 
		// checkout screen rather than doing lot's of work in this js.  If we update the PHP application top
		// return the elements for the checkout screen using the same logic that is used when the checkout 
		// page is originally created we simplify this script, maintain consistency, allow WordPress and WPEC
		// hooks to be used to change checkout and make chckout display better for those client paltforms
		// that may not have the necessary computing power to use js to do the work we are asking.
		var event = jQuery.Event( "wpsc-visitor-meta-change" );
		event.response = response;				
		jQuery( "wpsc-visitor-meta:first" ).trigger( event  );

	}
	
	jQuery( ".wpsc-visitor-meta" ).on( "change", wpsc_meta_item_change );
	
	wpsc_adjust_checkout_form_element_visibility();
}

/**
 * common callback triggered whenever a WPEC meta value is changed 
 * 
 * @since 3.8.14
 */
function wpsc_meta_item_change() {

	var meta_value;
	
	if ( jQuery(this).is(':checkbox') ) {
		if ( jQuery( this ).is(':checked') )
			meta_value = 1;
		else 
			meta_value = 0;
	} else {
		meta_value = jQuery( this ).val();	
	}
	
	var meta_key = jQuery( this ).attr( "data-wpsc-meta-key" );
	
	if ( meta_key == undefined ) {
		meta_key = jQuery( this ).attr( "title" );
		
		if ( meta_key == undefined ) {
			meta_key = jQuery( this ).attr( "id" );
		}
	}	
	
	// if there are other fields on the current page that are used to change the same meta value then 
	// they need to be updated
	var selector = 'input[data-wpsc-meta-key="' + meta_key + '"]';
	
	var element_that_changed_meta_value = this;
	
	jQuery( selector ).each( function( index, value ) {		
		if ( element_that_changed_meta_value != this ) {			
			if ( jQuery(this).is(':checkbox') ) {
				var boolean_meta_value = meta_value == "1";  
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

function wpsc_adjust_checkout_form_element_visibility(){

	var shipping_row = jQuery( "#shippingSameBilling" ).closest( "tr" );
	
	if( jQuery("#shippingSameBilling").is(":checked") ) { 
		jQuery( shipping_row ).siblings().hide();
		jQuery( "#shippingsameasbillingmessage" ).show();
	} else {
		jQuery( shipping_row ).siblings().show();
		jQuery( "#shippingsameasbillingmessage" ).hide();		
	} 
		
	jQuery( ".checkout-heading-row" ).show();
	
	// set the visibility of the shipping state input fields
	var shipping_country = jQuery( "#shippingcountry" ).val();
	var shipping_state_element = jQuery( "input[data-wpsc-meta-key='shippingstate']" ) ;
	
	if ( jQuery("#shippingSameBilling").is(":checked") || ('US' === shipping_country) || ('CA' === shipping_country) ) {
		shipping_state_element.closest( "tr" ).hide();
		shipping_state_element.val( '' ).attr( 'disabled', 'disabled' );
	} else {			
		shipping_state_element.closest( "tr" ).show();
		shipping_state_element.val( '' ).removeAttr( 'disabled' );
	}
	
	// set the visibility of the shipping state input fields
	var billing_country = jQuery( "#billingcountry" ).val();
	var billing_state_element = jQuery( "input[data-wpsc-meta-key='billingstate']" ) ;
	
	if ( 'US' === billing_country || 'CA' === billing_country ) {
		billing_state_element.closest( "tr" ).hide();
		billing_state_element.val( '' ).attr( 'disabled', 'disabled' );
	} else {			
		billing_state_element.closest( "tr" ).show();
		billing_state_element.val( '' ).removeAttr( 'disabled' );
	}	

	// maek sure any item that changes checkout data is bound to the proper event handler
	jQuery( ".wpsc-visitor-meta" ).on( "change", wpsc_meta_item_change );
}

/**
 * ready to setup the events for user actions that casuse meta item changes 
 * 
 * @since 3.8.14
 */
jQuery(document).ready(function ($) {
	// get the current value for all customer meta and display the values in available elements
	wpsc_get_customer_data( wpsc_update_customer_meta );
	
	// make sure that if the shopper clicks shipping same as billing the checkout form adjusts itself
	jQuery( "#shippingSameBilling" ).change( wpsc_adjust_checkout_form_element_visibility );
	
	// if the shopper changes a form value that is holding customer meta we should update 
	// the persistant customer meta
	jQuery( ".wpsc-visitor-meta").change( wpsc_meta_item_change );
	
	// make sure visibility of form elements is what it should be
	wpsc_adjust_checkout_form_element_visibility();
});





// this function is for binding actions to events and rebinding them after they are replaced by AJAX
// these functions are bound to events on elements when the page is fully loaded.
jQuery(document).ready(function ($) {
	if(jQuery('#checkout_page_container .wpsc_email_address input').val())
		jQuery('#wpsc_checkout_gravatar').attr('src', 'https://secure.gravatar.com/avatar/'+MD5(jQuery('#checkout_page_container .wpsc_email_address input').val().split(' ').join(''))+'?s=60&d=mm');
	jQuery('#checkout_page_container .wpsc_email_address input').keyup(function(){
		jQuery('#wpsc_checkout_gravatar').attr('src', 'https://secure.gravatar.com/avatar/'+MD5(jQuery(this).val().split(' ').join(''))+'?s=60&d=mm');
	});

	jQuery('#fancy_notification').appendTo('body');

	/* Clears shipping state and billing state on body load if they are numeric */
	$( 'input[title="shippingstate"], input[title="billingstate"]' ).each( function( index, value ){
		var $this = $( this ), $val = $this.val();

		if ( $this.is( ':visible' ) && ! isNaN( parseFloat( $val ) ) && isFinite( $val ) ) {
			$this.val( '' );
		}

	});


	/*****************************************************************
	 *  FUNCTION wpsc_update_shipping_quotes DEPRECATED AS OF 3.8.14
	 *  
	 *  It remains here as a stub in case third party scripts 
	 *  are trying to use it 
	 ****************************************************************/
	/**
	 * Update shipping quotes when "Shipping same as Billing" is checked or unchecked.
	 * @since 3.8.8
	 */
	function wpsc_update_shipping_quotes() {
		if ( window.console && window.console.log ) {
			console.log( "WPEC javascript function 'set_billing_country' is deprecated as of version 3.8.14, please update your code." );
		}
	}

	// Submit the product form using AJAX
	jQuery( 'form.product_form, .wpsc-add-to-cart-button-form' ).on( 'submit', function() {
		// we cannot submit a file through AJAX, so this needs to return true to submit the form normally if a file formfield is present
		file_upload_elements = jQuery.makeArray( jQuery( 'input[type="file"]', jQuery( this ) ) );
		if(file_upload_elements.length > 0) {
			return true;
		} else {
			var action_buttons = jQuery( 'input[name="wpsc_ajax_action"]', jQuery( this ) );
			var action = action_buttons[0].value;
			form_values = jQuery(this).serialize() + '&action=' + action;

			// Sometimes jQuery returns an object instead of null, using length tells us how many elements are in the object, which is more reliable than comparing the object to null
			if( jQuery( '#fancy_notification' ).length === 0 ) {
				jQuery( 'div.wpsc_loading_animation', this ).css( 'visibility', 'visible' );
			}

			var success = function( response ) {
				if ( ( response ) ) {
					if ( response.hasOwnProperty('fancy_notification') && response.fancy_notification ) {
						if ( jQuery( '#fancy_notification_content' ) ) {
							jQuery( '#fancy_notification_content' ).html( response.fancy_notification );
							jQuery( '#loading_animation').css( 'display', 'none' );
							jQuery( '#fancy_notification_content' ).css( 'display', 'block' );
						}
					}
					jQuery('div.shopping-cart-wrapper').html( response.widget_output );
					jQuery('div.wpsc_loading_animation').css('visibility', 'hidden');
	
					jQuery( '.cart_message' ).delay( 3000 ).slideUp( 500 );

					//Until we get to an acceptable level of education on the new custom event - this is probably necessary for plugins.
					if ( response.wpsc_alternate_cart_html ) {
						eval( response.wpsc_alternate_cart_html );
					}
	
					jQuery( document ).trigger( { type : 'wpsc_fancy_notification', response : response } );
				}

				if ( jQuery( '#fancy_notification' ).length > 0 ) {
					jQuery( '#loading_animation' ).css( "display", 'none' );
				}
			};

			jQuery.post( wpsc_ajax.ajaxurl, form_values, success, 'json' );

			wpsc_fancy_notification(this);
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
		jQuery('option[value="0"]', this).attr('disabled', 'disabled');
		var self = this;
		var parent_form = jQuery(this).closest("form.product_form");
		if ( parent_form.length === 0 )
			return;

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
				if ( response.stock_available ) {
					stock_display.removeClass('out_of_stock').addClass('in_stock');
				} else {
					stock_display.addClass('out_of_stock').removeClass('in_stock');
				}
				variation_display.removeClass('no_variation').addClass('is_variation');
			} else {
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

				buynow.find('input[name="'+jQuery(self).prop('name')+'"]').val(jQuery(self).val());
				buynow.find('input.wpsc-buy-now-button').prop('disabled', false);
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

		// Object frame destroying code.
	jQuery( 'div.shopping_cart_container' ).ready( function(){
		object_html = jQuery(this).html();
		window.parent.jQuery("div.shopping-cart-wrapper").html(object_html);
		});

	jQuery( 'div.shopping_cart_container' ).ready( function(){
		object_html = jQuery(this).html();
		window.parent.jQuery("div.shopping-cart-wrapper").html(object_html);
	});
	jQuery( 'body' ).on( 'click', 'a.emptycart', function(){
		parent_form = jQuery(this).parents( 'form' );
		form_values = jQuery(parent_form).serialize() + '&action=' + jQuery( 'input[name="wpsc_ajax_action"]', parent_form ).val();

		jQuery.post( wpsc_ajax.ajaxurl, form_values, function(response) {
			jQuery('div.shopping-cart-wrapper').html( response.widget_output );
			jQuery( document ).trigger( { type : 'wpsc_empty_cart', response : response } );
		}, 'json');

		return false;
	});

	var radios = jQuery(".productcart input:radio[name='shipping_method']");
	if (radios.length == 1) {
		// If there is only 1 shipping quote available during checkout, automatically select it
		jQuery(radios).click();
	} else if (radios.length > 1) {
		// There are multiple shipping quotes, simulate a click on the checked one
		jQuery(".productcart input:radio[name='shipping_method']:checked").click();
	}
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

// submit the fancy notifications forms.
function wpsc_fancy_notification(parent_form){
	if(typeof(WPSC_SHOW_FANCY_NOTIFICATION) == 'undefined'){
		WPSC_SHOW_FANCY_NOTIFICATION = true;
	}
	if((WPSC_SHOW_FANCY_NOTIFICATION == true) && (jQuery('#fancy_notification') != null)){
		jQuery('#fancy_notification').css({
		        position:'fixed',
		        left: (jQuery(window).width() - jQuery('#fancy_notification').outerWidth())/2,
		        top: (jQuery(window).height() - jQuery('#fancy_notification').outerHeight())/2
		    });

		jQuery('#fancy_notification').css("display", 'block');
		jQuery('#loading_animation').css("display", 'block');
		jQuery('#fancy_notification_content').css("display", 'none');
	}
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