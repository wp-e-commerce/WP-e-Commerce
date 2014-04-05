///////////////////////////////////////////////////////////////////////////////////////////////
// This section is used to create the globals that were originally defined in the 
// dynamic-js file pre 3.8.14.  Note that variables also also exist in the "wpsc_ajax" structure.

// iterate over the object and explicitly make each property a new global variable.  Because
// we are doing the operation in the global context the 'this' is the same as 'window' and 
// is the same functionally as do a 'var objectname' statement. Creating 'global variables' 
// in this manner the new "variable" is enumerable and can be deleted.  
// 
// To add a new global property that can be referenced in the script see the hook 
// wpsc_javascript_localizations in wpsc-core/wpsc-functions.php
//
for (var a_name in wpsc_admin_vars) {
  if (wpsc_admin_vars.hasOwnProperty(a_name)) {
	  a_value = wpsc_admin_vars[a_name];
	  this[a_name] = a_value;
  }
}
//
///////////////////////////////////////////////////////////////////////////////////////////////


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

/**
 * Get the URL that should be used when this script initiates AJAX requests to the server
 * 
 * @since 3.8.14
 * @access global
 * @param url to receive AJAX requests
 */
// a convenient function that will return the url to which ajax requests are sent
function wpsc_admin_ajax_url() {
	return _wpsc_admin_ajax_url;
}


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
		wpsc_http.open( "POST", wpsc_admin_ajax_url() + "?action=wpsc_validate_customer", false );
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

