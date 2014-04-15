<?php

function nzshpcrt_country_list( $selected_country = null ) {
	return _wpsc_country_dropdown_options( array(
		'selected' => $selected_country,
	) );
}

function nzshpcrt_region_list( $selected_country = null, $selected_region = null ) {
	global $wpdb;

	if ( $selected_region == null )
		$selected_region = get_option( 'base_region' );

	$output = "";
	$region_list = WPSC_Countries::get_regions( $selected_country, true );

	if ( $region_list != null ) {
		foreach ( $region_list as $region ) {
			if ( $selected_region == $region['id'] ) {
				$selected = "selected='selected'";
			} else {
				$selected = "";
			}

			$output .= "<option value='" . $region['id'] . "' $selected>" . $region['name'] . "</option>\r\n";
		}
	} else {
		$output .= "<option value=''>" . esc_html__( 'None', 'wpsc' ) . "</option>\r\n";
	}

	return $output;
}

function nzshpcrt_form_field_list( $selected_field = null ) {
	global $wpdb;
	$output = "<option value=''>" . esc_html__( 'Please choose', 'wpsc' ) . "</option>";
	$form_sql = "SELECT * FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `active` = '1';";
	$form_data = $wpdb->get_results( $form_sql, ARRAY_A );

	foreach ( (array)$form_data as $form ) {
		$selected = '';
		if ( $selected_field == $form['id'] ) {
			$selected = "selected='selected'";
		}
		$output .= "<option value='" . $form['id'] . "' $selected>" . $form['name'] . "</option>";
	}

	return $output;
}

function wpsc_parent_category_list( $taxonomies, $args, $parent, $current_term_id ) {
	$myterms = get_terms( $taxonomies, $args );
	$output = "<select name='category_parent'>";
	$output .="<option value='0'>" . __( 'No Parent', 'wpsc' ) . "</option>";
	$output .= wpsc_category_options(0, null, null, 0, $current_term_id );
	$output .="</select>";

	return $output;
}

/*
 * Displays the category forms for adding and editing products
 * Recurses to generate the branched view for subcategories
 */
function wpsc_category_options( $group_id, $this_category = null, $category_id = null, $iteration = 0, $selected_id = null ) {
	global $wpdb;
	$selected_term = get_term($selected_id,'wpsc_product_category');
	$values = get_terms( 'wpsc_product_category', 'hide_empty=0&parent=' . $group_id );
	$selected = "";
	$output = "";

	foreach ( (array)$values as $option ) {
		if ( $option->term_id != $this_category ) {
			if ( isset($selected_term->parent) && $selected_term->parent == $option->term_id ) {
				$selected = "selected='selected'";
			}

			$output .= "<option $selected value='" . $option->term_id . "'>" . str_repeat( "-", $iteration ) . esc_html( $option->name ) . "</option>\r\n";
			$output .= wpsc_category_options( $option->term_id, $this_category, $option->term_id, $iteration + 1, $selected_id );
			$selected = "";
		}
	}

	return $output;
}

