<?php

function _wpsc_is_country_disabled( $country, $args ) {
	extract( $args, EXTR_SKIP );

	$isocode = $country->isocode();
	$id      = $country->id();

	if ( is_array( $acceptable ) && ! in_array( $isocode, $acceptable ) )
		return true;

	if ( is_array( $acceptable_ids ) && ! in_array( $id, $acceptable_ids ) )
		return true;

	if ( is_array( $disabled ) && in_array( $isocode, $disabled ) )
		return true;

	if ( is_array( $disabled_ids ) && in_array( $id, $disabled_ids ) )
		return true;

	return false;
}

function _wpsc_country_dropdown_options( $args = '' ) {
	$defaults = array(
			'acceptable'        => null,
			'acceptable_ids'    => null,
			'selected'          => '',
			'disabled'          => null,
			'disabled_ids'      => null,
			'placeholder'       => __( 'Please select', 'wpsc' ),
			'include_invisible' => false,
	);

	$args = wp_parse_args( $args, $defaults );

	$output = '';

	if ( $args['placeholder'] )
		$output .= "<option value=''>" . esc_html( $args['placeholder'] ) . "</option>\n\r";

	$countries = WPSC_Countries::countries( $args['include_invisible'] );

	foreach ( $countries as $country ) {
		$isocode = $country->isocode();
		$name = $country->name();

		// if we're in admin area, and the legacy country code "UK" or "TP" is selected as the
		// base country, we should display both this and the more proper "GB" or "TL" options
		// and distinguish these choices somehow
		if ( is_admin() ) {
			if ( in_array( $isocode, array( 'TP', 'UK' ) ) )
				/* translators: This string will mark the legacy isocode "UK" and "TP" in the country selection dropdown as "legacy" */
				$name = sprintf( __( '%s (legacy)', 'wpsc' ), $name );
			elseif ( in_array( $isocode, array( 'GB', 'TL' ) ) )
			/* translators: This string will mark the legacy isocode "GB" and "TL" in the country selection dropdown as "ISO 3166" */
			$name = sprintf( __( '%s (ISO 3166)', 'wpsc' ), $name );
		}

		$output .= sprintf(
				'<option value="%1$s" %2$s %3$s>%4$s</option>' . "\n\r",
				/* %1$s */ esc_attr( $isocode ),
				/* %2$s */ selected( $args['selected'], $isocode, false ),
				/* %3$s */ disabled( _wpsc_is_country_disabled( $country, $args ), true, false ),
				/* %4$s */ esc_html( $name )
		);
	}

	return $output;
}

function wpsc_get_country_dropdown( $args = '' ) {
	static $count = 0;
	$count ++;

	$defaults = array(
			'name'                  => 'wpsc_countries',
			'id'                    => "wpsc-country-dropdown-{$count}",
			'class'                 => 'wpsc_country_dropdown',
			'additional_attributes' => '',
	);

	$args = wp_parse_args( $args, $defaults );

	$output = sprintf(
			'<select name="%1$s" id="%2$s" class="%3$s" %4$s>',
			/* %1$s */ esc_attr( $args['name'] ),
			/* %2$s */ esc_attr( $args['id'] ),
			/* %3$s */ esc_attr( $args['class'] ),
			/* %4$s */ $args['additional_attributes']
	);

	$output .= _wpsc_country_dropdown_options( $args );

	$output .= '</select>';

	return $output;
}

function wpsc_country_dropdown( $args = '' ) {
	echo wpsc_get_country_dropdown( $args );
}
