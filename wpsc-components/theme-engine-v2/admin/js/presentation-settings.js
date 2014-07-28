/*globals WPSC_Settings_Page, jQuery */
(function($) {
	'use strict';
	// elements
	var catsToFilter, catsToFilterField, drillDown, catRadios, customCats, customCatsDiv, cssRadios;

	var toggleSettings = function() {
		if ( catRadios.filter( ':checked' ).val() == '1' ) {
			catsToFilter.fadeIn( 150 );
			drillDown.fadeIn( 150);
		} else {
			catsToFilter.fadeOut( 150 );
			drillDown.fadeOut( 150 );
		}
	};

	var toggleCustomCats = function() {
		if ( catsToFilterField.filter( ':checked' ).val() == 'custom' ) {
			customCatsDiv.slideDown( 150 );
		} else {
			customCatsDiv.slideUp( 150 );
		}
	};

	var checkCss = function() {
		if ( cssRadios.eq( 1 ).is( ':checked' ) && ! ( cssRadios.eq( 0 ).is( ':checked' ) ) ) {
			if ( cssRadios.parents( 'td' ).find( 'div.error' ).length === 0 ) {
				cssRadios.parents( 'td' ).append( '<div class="error" style="display:none"><p>' + wpsc_adminL10n.wpsc_inline_css_error + '</p></div>' );
				cssRadios.parents( 'td' ).find( 'div.error' ).fadeIn( 150 );
			}
		} else {
			cssRadios.parents( 'td' ).find( 'div.error' ).fadeOut( 150 ).remove();
		}
	};

	$( WPSC_Settings_Page ).on( 'wpsc_settings_tab_loaded_presentation', function() {

		// assign elements
		catsToFilterField = $( 'input[name="wpsc_categories_to_filter"]' );
		catsToFilter      = catsToFilterField.closest( 'tr' );
		drillDown         = $( 'input[name="wpsc_category_filter_drill_down"]' ).closest( 'tr' );
		catRadios         = $( 'input[name="wpsc_display_category_filter"]' );
		cssRadios         = $( 'input', $( 'label.wpsc-form-checkbox-wrapper' ) );
		customCatsDiv     = $( '.wpsc-settings-category-filter-custom' );
		customCats        = $( '#categories-to-filter-custom-select' );

		toggleSettings();
		toggleCustomCats();

		cssRadios.on(         'change', checkCss );
		catRadios.on(         'change', toggleSettings );
		catsToFilterField.on( 'change', toggleCustomCats );
	});
})(jQuery);