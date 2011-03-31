/**
 * @author: Jeremy Smith - www.firefly-media-solutions.com
 * @description: File containing Javascript functions used in WPEC Taxes
 *               Module.
**/

/**
 * @description: wpec_taxes_get_regions - retrieves regions select box from the server.
 *                   Inserts the select box after the country.
 *
 * @param: key - integer. Used to select the id's of the form elements on the page.
 * @return: null
**/
function wpec_taxes_get_regions(key, type)
{
	//ajax variables to pass to the server
	var data = {
		action: 'wpec_taxes_ajax',
		wpec_taxes_action: 'wpec_taxes_get_regions',
		current_key: key,
		taxes_type: type,
		country_code: jQuery('#'+type+'-country-'+key).val()
	};
	
	//remove the current region input
	jQuery('#'+type+'-region-'+key).remove();

	//add the loading indicator
	jQuery('#'+type+'-country-'+key).after('<img id="'+type+'-'+key+'-loading" src="'+WPSC_CORE_IMAGES_URL+'/indicator.gif" />');

	//run ajax and process response
	jQuery.get(ajaxurl, data, function(response) {
		//remove the loading indicator
		jQuery('#'+type+'-'+key+'-loading').remove();
		
		//add the new region information
		jQuery('#'+type+'-country-'+key).after(response);
	});
}// wpec_taxes_get_regions

/**
 * @description: wpec_taxes_count_rates - counts all elements with the wpec-tax-rates class.
 *
 * @param: void
 * @return: integer
**/
function wpec_taxes_count_rates()
{
	return jQuery('.wpec-tax-rates').size();
}// wpec_taxes_count_rates

/**
 * @description: wpec_taxes_build_rate_form - retrieves an entire tax rate row from the server.
 *
 * @param: void
 * @return: boolean false
**/
function wpec_taxes_build_rate_form()
{
	var key = wpec_taxes_count_rates();

	var data = {
		action: 'wpec_taxes_ajax',
		wpec_taxes_action: 'wpec_taxes_build_rate_form',
		current_key: key
	};

	//run ajax and process response
	jQuery.get(ajaxurl, data, function(response) {
		jQuery('#add_taxes_rate').before(response);
	});
	return false;
}// wpec_taxes_tax_rate_form

/**
 * @description: wpec_taxes_count_bands - counts all elements with the wpec-tax-bands class.
 *
 * @param: void
 * @return: integer
**/
function wpec_taxes_count_bands()
{
	return jQuery('.wpec-tax-bands').size();
}// wpec_taxes_count_rates

/**
 * @description: wpec_taxes_build_band_form - retrieves an entire tax band row from the server.
 *
 * @param: void
 * @return: boolean false
**/
function wpec_taxes_build_band_form()
{
	var key = wpec_taxes_count_bands();

	var data = {
		action: 'wpec_taxes_ajax',
		wpec_taxes_action: 'wpec_taxes_build_band_form',
		current_key: key
	};

	//run ajax and process response
	jQuery.get(ajaxurl, data, function(response) {
		jQuery('#add_taxes_band').before(response);
	});
	return false;
}// wpec_taxes_tax_band_form

/**
 * @description: wpec_taxes_delete_tax_rate - given a key will remove the associated
 *                                            tax rate form row.
 *
 * @param: key - integer. Used in referring to the id for the row.
 * @return: null
**/
function wpec_taxes_delete_tax_rate(key)
{
	if(isNaN(key))
	{
		var key = key.split('-');
		key = key[1];
	}

	jQuery('#rates-row-'+key).remove();
}// wpec_taxes_delete_tax_rate

/**
 * @description: wpec_taxes_delete_tax_band - given a key will remove the associated
 *                                            tax band form row.
 *
 * @param: key - integer. Used in referring to the id for the row.
 * @return: null
**/
function wpec_taxes_delete_tax_band(key)
{
	if(isNaN(key))
	{
		var key = key.split('-');
		key = key[1];
	}

	jQuery('#bands-row-'+key).remove();
}// wpec_taxes_delete_tax_band

//bind the click function to the add_tax_rate link and initialize with 0
jQuery('#add_taxes_rate').live('click', function(){
	wpec_taxes_build_rate_form();
	return false;
});

//bind the click function to each new tax_rate delete link
jQuery('.taxes-rates-delete').live('click', function(){
	wpec_taxes_delete_tax_rate(jQuery(this).attr('id'));
	return false;
});

//bind the click function to the add_tax_band link and initialize with 0
jQuery('#add_taxes_band').live('click', function(){
	wpec_taxes_build_band_form();
	return false;
});

//bind the click function to each new tax_band delete link
jQuery('.taxes-bands-delete').live('click', function(){
	wpec_taxes_delete_tax_band(jQuery(this).attr('id'));
	return false;
});
