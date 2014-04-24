///////////////////////////////////////////////////////////////////////////////////////////////
// This section is used to create the globals that were originally defined in the
// dynamic-js file pre 3.8.14.  Note that variables also also exist in the "wpsc_ajax" structure.
// To add a new global property that can be referenced in the script see the hook
// wpsc_javascript_localizations in wpsc-core/wpsc-functions.php
//

/**
 * Legacy javascript variables for WP-e-Commerce
 *
 * These WPeC WordPress localized vars were in use prior to release 3.8.14, and are explicitly
 * declared here for maximum backwards compatibility.  For admin related js vars added after
 * version 3.8.14  use the following utility function to access the localized variables.
 *
 * wpsc_admin_var_get ( name )
 * wpsc_admin_var_set ( name, value )
 * wpsc_admin_var_isset ( name, value );
 *
 */

if ( typeof wpsc_admin_vars !== undefined ) {
	var ajaxurl                      = wpsc_admin_vars['ajaxurl'];
	var base_url                     = wpsc_admin_vars['base_url'];
	var WPSC_URL                     = wpsc_admin_vars['WPSC_URL'];
	var WPSC_IMAGE_URL               = wpsc_admin_vars['WPSC_IMAGE_URL'];
	var fileThickboxLoadingImage     = wpsc_admin_vars['fileThickboxLoadingImage'];
    var hidden_boxes                 = wpsc_admin_vars['hidden_boxes'];
    var IS_WP27                      = wpsc_admin_vars['IS_WP27'];
    var TXT_WPSC_DELETE              = wpsc_admin_vars['TXT_WPSC_DELETE'];
    var TXT_WPSC_TEXT                = wpsc_admin_vars['TXT_WPSC_TEXT'];
    var TXT_WPSC_EMAIL               = wpsc_admin_vars['TXT_WPSC_EMAIL'];
    var TXT_WPSC_COUNTRY             = wpsc_admin_vars['TXT_WPSC_COUNTRY'];
    var TXT_WPSC_TEXTAREA            = wpsc_admin_vars['TXT_WPSC_TEXTAREA'];
    var TXT_WPSC_HEADING             = wpsc_admin_vars['TXT_WPSC_HEADING'];
    var TXT_WPSC_COUPON              = wpsc_admin_vars['TXT_WPSC_COUPON'];
    var HTML_FORM_FIELD_TYPES        = wpsc_admin_vars['HTML_FORM_FIELD_TYPES'];
    var HTML_FORM_FIELD_UNIQUE_NAMES = wpsc_admin_vars['HTML_FORM_FIELD_UNIQUE_NAMES'];
    var TXT_WPSC_LABEL               = wpsc_admin_vars['TXT_WPSC_LABEL'];
    var TXT_WPSC_LABEL_DESC          = wpsc_admin_vars['TXT_WPSC_LABEL_DESC'];
    var TXT_WPSC_ITEM_NUMBER         = wpsc_admin_vars['TXT_WPSC_ITEM_NUMBER'];
    var TXT_WPSC_LIFE_NUMBER         = wpsc_admin_vars['TXT_WPSC_LIFE_NUMBER'];
    var TXT_WPSC_PRODUCT_CODE        = wpsc_admin_vars['TXT_WPSC_PRODUCT_CODE'];
    var TXT_WPSC_PDF                 = wpsc_admin_vars['TXT_WPSC_PDF'];
    var TXT_WPSC_AND_ABOVE           = wpsc_admin_vars['TXT_WPSC_AND_ABOVE'];
    var TXT_WPSC_IF_PRICE_IS         = wpsc_admin_vars['TXT_WPSC_IF_PRICE_IS'];
    var TXT_WPSC_IF_WEIGHT_IS        = wpsc_admin_vars['TXT_WPSC_IF_WEIGHT_IS'];
}
///////////////////////////////////////////////////////////////////////////////////////////////

/**
 * check if a localized WPeC value is set
 *
 * @since 3.8.14
 *
 * @param string 	name 		name of localized variable
 *
 * @returns boolean		true if the var is set, false otherwise
 *
 */
function wpsc_admin_var_isset( name ) {
	if ( typeof wpsc_admin_vars !== undefined ) {
		return  wpsc_admin_vars[name] !== undefined;
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
function wpsc_admin_var_get( name ) {
	if ( typeof wpsc_admin_vars !== undefined ) {
		return  wpsc_admin_vars[name];
	}

	return undefined;
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
function wpsc_admin_var_set( name, value ) {
	if ( typeof wpsc_admin_vars !== undefined ) {
		wpsc_admin_vars[name] = value;
		return value;
	}

	return undefined;
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

if ( ! ( document.cookie.indexOf("wpsc_customer_cookie") >= 0 ) ) {
	if ( ! ( document.cookie.indexOf("wpsc_attempted_validate") >= 0 ) ) {
		// create a cookie to signal that we have attempted validation.  If we find the cookie is set
		// we don't re-attempt validation.  This means will only try to validate once and not slow down
		// subsequent page views.

		// The lack of expiration date means the cookie will be deleted when the browser
		// is closed, so the next time the visitor attempts to access the site they will
		// attempt to revalidate
		var now = new Date();
		document.cookie="wpsc_attempted_validate="+now;

		var wpsc_http = new XMLHttpRequest();
		wpsc_http.overrideMimeType( "application/json" );

		// open setup and send the request in synchronous mode
		wpsc_http.open( "POST", ajaxurl + "?action=wpsc_validate_customer", false );
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

