(function($){
	// apply multi selection to the selected fields
	$.fn.wpsc_multi_select = function(options) {
		this.
			not( '[id*="__i__"]' ). // filter out widget template
			select2(options);
	};

	$(function() {
		// select all or none, delegated event handler in case AJAX is involved
		$( 'body' ).on( 'click', 'a.wpsc-multi-select-all, a.wpsc-multi-select-none', function( e ) {
			var t = $(this),
				el = $('#' + t.data('for'));

			e.preventDefault();

			// if this control hasn't been initialized, do nothing
			if ( ! el.data( 'select2' ) )
				return;

			// select all or none based on html class
			el.find( 'option' ).prop( 'selected', t.hasClass( 'wpsc-multi-select-all' ) );

			// update Select2 control
			el.trigger( 'change' );
		});

		// initialize all select boxes with class .wpsc-multi-select by default
		$('.wpsc-multi-select').wpsc_multi_select();
	});

	// automatically refresh the elements in case an AJAX request is made
	$(document).ajaxComplete(function() {
		$( '.wpsc-multi-select' ).each( function( index, el ) {
			var t = $(el);

			if ( ! t.data( 'select2' ) ) {
				$( '#' + t.attr( 'id' ).replace( /\-/g, '_' ) + '_chosen' ).remove();
				t.wpsc_multi_select({
					search_contains: true
				});
			}

			t.wpsc_multi_select();
		} );
	});
}(jQuery));