jQuery(document).ready(function($) {
	$('.sputnik-message').insertAfter( $('div.wrap h2:first') );
	$('#menu-posts-wpsc-product div ul li a[href$="page=sputnik-account"]').parent('li').remove();
});