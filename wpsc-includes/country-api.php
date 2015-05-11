<?php

/**
 * Get a country name using it's unique indentfier
 *
 * @param int|string unique country identifier, the ISO code is preferred, but
 *                   the WP eCommerce specific integer country id can also be used
 *
 * @since 3.8
 *
 * @return string the country name, empty string on failure
 */
function wpsc_get_country( $country_identifier ) {
	$wpsc_country = wpsc_get_country_object( $country_identifier );
	if ( $wpsc_country ) {
		$country_name = $wpsc_country->get_name();
	} else {
		$country_name = '';
	}

	return $country_name;
}

/**
 * Get a region name using it's WP eCommerce integer region id
 *
 * @param int the WP eCommerce specific integer region id
 *
 * @since 3.8
 *
 * @return WPSC_Country|boolean the country, or false on failure
 */
function wpsc_get_region( $region_identifier ) {
	$wpsc_region = wpsc_get_region_object( $region_identifier );
	if ( $wpsc_region ) {
		$region_name = $wpsc_region->get_name();
	} else {
		$region_name = '';
	}

	return $region_name;
}

/**
 * Get a country object using it's unique indentfier
 *
 * @param int|string unique country identifier, the ISO code is preferred, but
 *                   the WP eCommerce specific integer country id can also be used
 *
 * @since 4.1
 *
 * @return WPSC_Country|boolean the country, or false on failure
 */
function wpsc_get_country_object( $country_identifier ) {
	return WPSC_Countries::get_country( $country_identifier );
}

/**
 * Get a region object using it's WP eCommerce integer region id
 *
 * @param int the WP eCommerce specific integer region id
 *
 * @since 4.1
 *
 * @return WPSC_Country|boolean the country, or false on failure
 */
function wpsc_get_region_object( $region_id ) {
	return WPSC_Countries::get_region( false, $region_id );
}

/**
 * Chaneg the cisibility of all countries site wide
 *
 * @param int the WP eCommerce specific integer region id
 *
 * @since 4.1
 *
 * @return WPSC_Country|boolean the country, or false on failure
 */
function wpsc_set_all_countries_visibility( $visibility = true ) {
	WPSC_Countries::set_visibility( $visibility );
}

/**
 * Get countries
 *
 * @param bool $include_invisible include the invisible countries in the result
 * @param bool $sort_by_name  sort the results by the country name
 *
 * @since 4.1
 *
 * @return WPSC_Country[]
 *
 */
function wpsc_get_country_objects( $include_invisible = false, $sort_by_name = true ) {
	return WPSC_Countries::get_countries( $include_invisible, $sort_by_name );
}

/**
 * Get all countries
 *
 * @param bool $include_invisible include the invisible countries in the result
 * @param bool $sort_by_name  sort the results by the country name
 *
 * @since 4.1
 *
 * @return WPSC_Country[]
 *
 */
function wpsc_get_all_countries( $sort_by_name = true ) {
	return WPSC_Countries::get_countries( true, $sort_by_name );
}

/**
 * Get visible countries
 *
 * @param bool $include_invisible include the invisible countries in the result
 * @param bool $sort_by_name  sort the results by the country name
 *
 * @since 4.1
 *
 * @return WPSC_Country[]
 *
 */
function wpsc_get_visible_countries( $sort_by_name = true ) {
	return WPSC_Countries::get_countries( false, $sort_by_name );
}

/**
 * Get all known regions
 *
 * @since 4.1
 *
 * @return WPSC_Region[]
 *
 */
function wpsc_get_all_regions() {
	return WPSC_Countries::get_all_regions();
}

/**
 * Get the country object correcposnding to the currently configured store currency type, might be the default
 * currency type if store admin has not configured currency type
 *
 * @since 4.1
 *
 * @return WPSC_Country|boolean the country, or false on failure
 */
function wpsc_get_currency_type_country_object() {

	$wpsc_country_of_currency_type = false;

	$currency_type_country_id = intval( get_option( 'currency_type', - 1 ) );

	// get the country for the current currency type, make sure it is valid, if not
	// we will move on as if a currency type was not set
	if ( - 1 != $currency_type_country_id ) {
		$wpsc_country_of_currency_type = wpsc_get_country_object( $currency_type_country_id );
		if ( ! $wpsc_country_of_currency_type ) {
			$currency_type_country_id = - 1;
		}
	}

	if ( - 1 == $currency_type_country_id ) {
		$wpsc_country_of_currency_type = wpsc_get_country_object( 'US' );
		if ( $wpsc_country_of_currency_type ) {
			$currency_type_country_id = $wpsc_country_of_currency_type->get_id();
		}
	}

	return $wpsc_country_of_currency_type;
}


/**
 * Get the country id correcposnding to the currently configured store currency type, might be the default
 * currency type countri id if store admin has not configured currency type
 *
 * @since 4.1
 *
 * @return int country id matching the current currency type, 0 if there isn't a current value
 */
function wpsc_get_currency_type_country_id() {

	$wpsc_country_of_currency_type = wpsc_get_currency_type_country_object();
	if ( $wpsc_country_of_currency_type ) {
		$currency_type_country_id = $wpsc_country_of_currency_type->get_id();
	} else {
		$currency_type_country_id = 0; // should never happen
	}

	return $currency_type_country_id;
}
