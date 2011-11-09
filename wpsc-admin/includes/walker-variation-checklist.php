<?php
/**
 * Walker Variation Checklist
 * Outputs checkboxes for variation sets
 */
class WPSC_Walker_Variation_Checklist extends Walker_Category_Checklist {
	function start_el(&$output, $category, $depth, $args) {
		extract($args);
		if ( empty($taxonomy) )
			$taxonomy = 'category';

		$checked = in_array( $category->term_id, $selected_cats );
		$class = ( $depth === 0 ) ? ' class="variation-set"' : '';
		$expand_class = $checked ? ' class="expanded"' : '';
		$output .= "\n<li id='{$taxonomy}-{$category->term_id}'{$expand_class}>" . ( $depth == 0 ? '<a href="#" class="expand">Expand</a> ' : '' ) . '<label class="selectit"><input' . $class . ' value="1" type="checkbox" name="edit_var_val[' . $category->parent . '][' . $category->term_id . ']" id="in-'.$taxonomy.'-' . $category->term_id . '"' . checked( $checked, true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . esc_html( apply_filters('the_category', $category->name )) . '</label>';
	}
}