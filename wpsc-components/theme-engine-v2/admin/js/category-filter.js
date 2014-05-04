/*globals WPSC_Settings_Page, jQuery */
(function($) {
	'use strict';
	// elements
	var catsToFilter, catsToFilterField, drillDown, radios, customCats, customCatsDiv;

	var toggleSettings = function() {
		if ( radios.filter(':checked').val() == '1' ) {
			catsToFilter.fadeIn( 150 );
			drillDown.fadeIn( 150);
		} else {
			catsToFilter.fadeOut( 150 );
			drillDown.fadeOut( 150 );
		}
	};

	var toggleCustomCats = function() {
		if ( catsToFilterField.filter(':checked').val() == 'custom' ) {
			customCatsDiv.slideDown( 150 );
		} else {
			customCatsDiv.slideUp( 150 );
		}
	};

	$(WPSC_Settings_Page).on( 'wpsc_settings_tab_loaded_presentation', function() {
		// assign elements
		catsToFilterField = $( 'input[name="wpsc_categories_to_filter"]' );
		catsToFilter = catsToFilterField.closest( 'tr' );
		drillDown = $( 'input[name="wpsc_category_filter_drill_down"]' ).closest( 'tr' );
		radios = $( 'input[name="wpsc_display_category_filter"]' );
		customCatsDiv = $('.wpsc-settings-category-filter-custom' );
		customCats = $('#categories-to-filter-custom-select');

		toggleSettings();
		toggleCustomCats();

		radios.on( 'change', toggleSettings );
		catsToFilterField.on( 'change', toggleCustomCats );
	});
})(jQuery);