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

