// JavaScript Document
(function($){
	$(document).ready(function() {
		//Button Settings Block
		$("h3:contains('Button Settings')").next().find('th[scope="row"]').addClass('button_settings_th allign_top');
	
		var i = $("h3:contains('Button Settings')").next().find('td').first();
		i.html(i.html().replace('Buy Now Button only works for Paypal Standard, please activate Paypal Standard to enable this option.',''));
		i.after("<td class='button_settings_desc'>Buy Now Button only works for Paypal Standard, please activate Paypal Standard to enable this option.</td>");
		i.addClass('allign_top');
	
		//Products Settings Block
		$("h3:contains('Product Settings')").next().find('th[scope="row"]').addClass('product_settings_th allign_top');
		$("h3:contains('Product Settings')").next().find('th[score="row"]').addClass('product_settings_th allign_top');
	
		//Product Page Settings
		$("h3:contains('Product Page Settings')").next().find('th[scope="row"]').addClass('product_page_settings_th allign_top');
	
		//Shopping Cart Settings
		$("h3:contains('Shopping Cart Settings')").next().find('th[scope="row"]').addClass('shopping_cart_settings_th allign_top');
	
		//Product category Settings
		$("h3:contains('Product Category Settings')").next().find('th[scope="row"]').addClass('product_category_settings_th allign_top');
	
		//Thumbnail Settings
		$("h3:contains('Thumbnail Settings')").next().next().find('th[scope="row"]').addClass('thumbnail_settings_th');
	
		//Pagination Settings
		$("h3:contains('Pagination settings')").next().find('th[scope="row"]').addClass('pagination_settings_th');
	
		//Comment Settings
		$("h3:contains('Comment Settings')").next().find('th[scope="row"]').addClass('comment_settings_th');
	
		//Select control
		$("select").wrap('<div class="wpsc_select_wrapper" />');
		$("select").before('<span class="wpsc_select_span"></span>');
		$("select").addClass("wpsc_select");
		$("select").change(function (){
				$(this).prev().text($(this).find('option:selected').text());
			});
	
		//Initialize select valua
		$("select").each(function(index, element) {
	        $(this).prev().text($(this).find('option:selected').text());
	    });
	
		//Remove Advanced Theme Setting
		$('#themes_and_appearance').remove();
	});
})(jQuery);