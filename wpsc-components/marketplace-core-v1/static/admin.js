jQuery(document).ready(function($) {
	var receiveMessage = function() {
		if ( event.data == 'tb_close' )
			tb_remove();
	}
	window.addEventListener( 'message', receiveMessage ,false );

	if ( sputnikL10n.buy_id && sputnikL10n.buy_href ) {
		var dg = new PAYPAL.apps.DGFlow({trigger:sputnikL10n.buy_id});
		dg.startFlow(sputnikL10n.buy_href);
	}
	$('#menu-posts-wpsc-product div ul li a[href$="page=sputnik-account"]').parent('li').remove();
	$('.grid-view').masonry({
		selector: '.plugin'
	});

	$('#rateme').mousemove(function (event) {
		var score = Sputnik.rating_from_event(event);
		$('#rateme .star-rating').width(score * 20);
	}).mouseleave(function () {
		$('#rateme .star-rating').width($(this).data('rating') * 20);
	}).click(function (event) {
		var score = Sputnik.rating_from_event(event);
		jQuery.post(ajaxurl, {
			action: 'sputnik_rate',
			rating: score,
			product: $(this).data('productid')
		}, function (data) {
			$('#rateme').data('rating', data.rating);
			$('#rateme .star-rating').width(data.rating * 20);
			alert('Rating set!');
		});
	});

	$('.thickbox.info').click(function () {

		var href = $(this).attr('href');
		var dim = Sputnik.window_dimensions();
		href += '&width=' + Math.min(Math.max(dim[0] - 50, 300), 700)
			+ '&height=' + Math.min(Math.max(dim[1] - 100, 250), 550);
		tb_show('', href);
		this.blur();

		$('#TB_title').css({'background-color':'#222','color':'#cfcfcf'});
		$('#TB_ajaxWindowTitle').html('<strong>' + sputnikL10n.plugin_information + '</strong>&nbsp;' + $(this).attr('title') );

		return false;
	});

	$('.button.install').click(function () {
		tb_click.call(this);
		return false;
	});

	$('#sputnik-install a.close,#sputnik-upgrade a.close').click(function () {
		if (window.parent !== window) {
			window.parent.tb_remove();
			return false;
		}
	});

	$('.buy').click(function(){
		var href = $(this).attr('href');
		if (href.indexOf('TB_iframe') !== -1) {
			tb_show($(this).attr('title'), href);
			return false;
		}

		var dg = new PAYPAL.apps.DGFlow({trigger:$(this).attr('id')});
		dg.startFlow($(this).attr('href'));
		return false;
	});

	var $_GET = getQueryParams(window.location.search);
	if($_GET['run-installer'] != null && $_GET['run-installer'].length > 0 && $('div').closest('#TB_window').size() == 0){
		var t = 'Installing ...';
		var a = $_GET['run-installer'];

		tb_show(t,a,false);
	}

	function getQueryParams(qs) {
		qs = qs.split("+").join(" ");
		var params = {},
		tokens,
		re = /[?&]?([^=]+)=([^&]*)/g;

		while (tokens = re.exec(qs)) {
			params[decodeURIComponent(tokens[1])]
			= decodeURIComponent(tokens[2]);
		}
		return params;
	}
});

var Sputnik = {
	rating_from_event: function (event) {
		var score = (event.pageX - jQuery(event.target).offset().left) / 20;
		return Math.floor(score) + 1;
	},
	window_dimensions: function () {
		var de = document.documentElement;
		var width = window.innerWidth || self.innerWidth
			|| (de&&de.clientWidth) || document.body.clientWidth;
		var height = window.innerHeight || self.innerHeight
			|| (de&&de.clientHeight) || document.body.clientHeight;
		return [width, height];
	}
};
