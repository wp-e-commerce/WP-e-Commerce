<?php

// make the countries and regions information available to our javascript
add_filter( '_wpsc_javascript_localizations', '_wpsc_countries_localizations', 10, 1 );

/**
 * add countries data to the wpec javascript localizations
 *
 * @access private
 * @since 3.8.14
 *
 * @param array 	localizations  other localizations that can be added to
 *
 * @return array	localizations array with countries information added
 */
function _wpsc_countries_localizations( $localizations_array ) {

	$localizations_array['no_country_selected']       = __( 'Please select a country', 'wpsc' );
	$localizations_array['no_region_selected_format'] = __( 'Please select a %s', 'wpsc' );
	$localizations_array['no_region_label']           = __( 'State/Province', 'wpsc' );

	$country_list  = array();

	foreach ( WPSC_Countries::get_countries() as $country_id => $wpsc_country ) {
		if ( $wpsc_country->is_visible() ) {
			$country_list[$wpsc_country->get_isocode()] = $wpsc_country->get_name();

			if ( $wpsc_country->has_regions() ) {
				$regions = $wpsc_country->get_regions();
				$region_list = array();
				foreach ( $regions as $region_id => $wpsc_region ) {
					$region_list[$region_id] = $wpsc_region->get_name();
				}

				if ( ! empty ( $region_list ) ) {
					$localizations_array[ 'wpsc_country_'.$wpsc_country->get_isocode() . '_regions' ] = $region_list;
				}
			}

			$region_label = $wpsc_country->get( 'region_label' );
			if ( ! empty( $region_label ) ) {
				$localizations_array['wpsc_country_' . $wpsc_country->get_isocode() . '_region_label' ] = $region_label;
			}
		}
	}

	if ( ! empty( $country_list ) ) {
		$localizations_array['wpsc_countries'] = $country_list;
	}

	return $localizations_array;
}

/**
 * add checkout unique name to form id map to user javascript localizations
 *
 * @access private
 * @since 3.8.14
 *
 * @param array 	localizations  other localizations that can be added to
 *
 * @return array	localizations array with checkout information added
 */
function _wpsc_localize_checkout_item_name_to_from_id( $localizations ) {
	$localizations['wpsc_checkout_unique_name_to_form_id_map'] = _wpsc_create_checkout_unique_name_to_form_id_map();
	$localizations['wpsc_checkout_item_active'] = _wpsc_create_checkout_item_active_map();
	$localizations['wpsc_checkout_item_required'] = _wpsc_create_checkout_item_required_map();
	return $localizations;
}

add_filter( '_wpsc_javascript_localizations', '_wpsc_localize_checkout_item_name_to_from_id', 10, 1 );


/**
 * Creates an array mapping from checkout item id to the item name in the field.  array is
 * localized into the user javascript so that the javascript can find items using the well known names.
 *
 * In release 3.8.14 the unique key for a field is available in an element attribute
 * called 'data-wpsc-meta-key'. Just in case someone out there has a highly customized
 * checkout experience that doesn't use the WPeC core functions to create the controls
 * using this map will maintain backwards compatibility.
 *
 * @access public
 *
 * @since 3.8.14
 * @return (boolean)
 */
function _wpsc_create_checkout_unique_name_to_form_id_map() {
	global $wpsc_checkout;

	if ( empty( $wpsc_checkout ) ) {
		$wpsc_checkout = new wpsc_checkout();
	} else {
		$wpsc_checkout->rewind_checkout_items();
	}

	$checkout_item_map = array();
	while ( wpsc_have_checkout_items() ) {
		$checkout_item = wpsc_the_checkout_item();

		if ( ! empty( $checkout_item->unique_name ) ) {
			$checkout_item_map[$wpsc_checkout->form_item_unique_name()] = $wpsc_checkout->form_element_id();
		}
	}

	$wpsc_checkout->rewind_checkout_items();

	return $checkout_item_map;

}

/**
 *	Create an array of item name and active status
 * @access public
 *
 * @since 3.8.14
 * @return (boolean)
 */
function _wpsc_create_checkout_item_active_map() {
	global $wpsc_checkout;

	if ( empty( $wpsc_checkout ) ) {
		$wpsc_checkout = new wpsc_checkout();
	} else {
		$wpsc_checkout->rewind_checkout_items();
	}

	$checkout_item_map = array();
	while ( wpsc_have_checkout_items() ) {
		$checkout_item = wpsc_the_checkout_item();

		if ( ! empty( $checkout_item->unique_name ) ) {
			$checkout_item_map[$wpsc_checkout->form_item_unique_name()] = $wpsc_checkout->form_element_active();
		}
	}

	$wpsc_checkout->rewind_checkout_items();

	return $checkout_item_map;

}

/**
 *	Create an array of item name and active status
 * @access public
 *
 * @since 3.8.14
 * @return (boolean)
 */
function _wpsc_create_checkout_item_required_map() {
	global $wpsc_checkout;

	if ( empty( $wpsc_checkout ) ) {
		$wpsc_checkout = new wpsc_checkout();
	}

	$checkout_item_map = array();
	while ( wpsc_have_checkout_items() ) {
		$checkout_item = wpsc_the_checkout_item();

		if ( ! empty( $checkout_item->unique_name ) ) {
			$checkout_item_map[$wpsc_checkout->form_item_unique_name()] = $wpsc_checkout->form_name_is_required();
		}
	}

	$wpsc_checkout->rewind_checkout_items();

	return $checkout_item_map;

}
