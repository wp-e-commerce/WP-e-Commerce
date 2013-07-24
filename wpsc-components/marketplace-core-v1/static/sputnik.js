jQuery(document).ready(function($) {
	$('.sputnik-message').insertAfter( $('div.wrap h2:first') );
	//$('#menu-posts-wpsc-product').before('<li class="wp-not-current-submenu wp-menu-separator"><div class="separator"></div></li>');
	$('#menu-posts-wpsc-product div ul li a[href$="page=sputnik-account"]').parent('li').remove();
});