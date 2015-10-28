<?php

function wpsc_parent_category_list( $taxonomies, $args, $parent, $current_term_id ) {
	$myterms = get_terms( $taxonomies, $args );
	$output = "<select name='category_parent'>";
	$output .="<option value='0'>" . __( 'No Parent', 'wp-e-commerce' ) . "</option>";
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