
/**
 * Deprecated javascript variables for WP-e-Commerce
 * 
 * conditionally loaded based upon the value of WPEC_LOAD_DEPRECATED
 */

if ( typeof wpsc_deprecated_js_vars !== undefined ) {
	
	/*
	 * Deprecated in 3.8.14
	 */
	var WPSC_DIR_NAME				= wpsc_deprecated_js_vars['WPSC_DIR_NAME'];
	var fileLoadingImage			= wpsc_deprecated_js_vars['fileLoadingImage'];	
	var fileBottomNavCloseImage		= wpsc_deprecated_js_vars['fileBottomNavCloseImage'];
	var resizeSpeed					= wpsc_deprecated_js_vars['resizeSpeed'];	
	var borderSize					= wpsc_deprecated_js_vars['borderSize'];
}

/**
 * Deprecated javascript functions for WP-e-Commerce
 * 
 * conditionally loaded based upon the value of WPEC_LOAD_DEPRECATED
 */


// a console log function in case it isn't there for us to use
if ( ! window.console ){ window.console = {log: function(){} }; } 

/*****************************************************************
 *  FUNCTION set_billing_country DEPRECATED AS OF 3.8.14
 *  
 *  It remains here in case third party scripts are trying to 
 *  use it 
 ****************************************************************/
function set_billing_country(html_form_id, form){
	if ( window.console && window.console.log ) {
		console.log( "WPEC javascript function 'set_billing_country' is deprecated as of version 3.8.14. The meta values are automatically updated when the HTML element changes by the javascript function wpsc_update_customer_meta" );
	}
}

/*****************************************************************
 *  FUNCTION set_shipping_country DEPRECATED AS OF 3.8.14
 *  
 *  It remains here as a stub in case third party scripts 
 *  are trying to use it 
 ****************************************************************/
function set_shipping_country(html_form_id, form){
	if ( window.console && window.console.log ) {
		console.log( "WPEC javascript function 'set_billing_country' is deprecated as of version 3.8.14, please update your code. The meta values are automatically updated when the HTML element changes by the javascript function wpsc_update_customer_meta" );
	}
}

/*****************************************************************
 *  FUNCTION wpsc_shipping_same_as_billing DEPRECATED AS OF 3.8.14
 *  
 *  It remains here as a stub in case third party scripts 
 *  are trying to use it 
 ****************************************************************/
function wpsc_shipping_same_as_billing(){
	if ( window.console && window.console.log ) {
		console.log( "WPEC javascript function 'wpsc_shipping_same_as_billing' is deprecated as of version 3.8.14. The meta values are automatically updated when the HTML element changes by the javascript function wpsc_update_customer_meta" );
	}
}

/*****************************************************************
 *  FUNCTION wpsc_handle_country_change DEPRECATED AS OF 3.8.14
 *  
 *  It remains here as a stub in case third party scripts 
 *  are trying to use it 
 *****************************************************************/
function wpsc_handle_country_change( response ) {
	if ( window.console && window.console.log ) {
		console.log( "WPEC javascript function 'wpsc_handle_country_change' is deprecated as of version 3.8.14. The meta values are automatically updated when the HTML element changes by the javascript function wpsc_update_customer_meta" );
	}
}

/*****************************************************************
 *  FUNCTION wpsc_update_shipping_quotes DEPRECATED AS OF 3.8.14
 *  
 *  It remains here as a stub in case third party scripts 
 *  are trying to use it 
 ****************************************************************/
function wpsc_update_shipping_quotes() {
	if ( window.console && window.console.log ) {
		console.log( "WPEC javascript function 'wpsc_update_shipping_quotes' is deprecated as of version 3.8.14, Visibility should be automatically controlled, but you cann call wpsc_adjust_checkout_form_element_visibility if needed" );
	}
	wpsc_adjust_checkout_form_element_visibility();
}



