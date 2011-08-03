<?php
/**
 * Walker Variation Checklist
 * Outputs checkboxes for variation sets
 */
class WPSC_Walker_Variation_Checklist extends Walker_Category_Checklist {	
	private $is_displayed = true;
	
	// Don't need to output anything - if this was a nest list it would be a <ul>
	// It's here purely to override the default output with nothing.
	function start_lvl( &$output, $depth, $args ) {
	}
	
	// Same as above for the closing tag.
	function end_lvl( &$output, $depth, $args ) {
	}
	
	// Start variation set or variation
	function start_el( &$output, $category, $depth, $args ) {
		extract( $args );
		if ( empty( $taxonomy ) )
			$taxonomy = 'wpsc-variation';
		if ( $depth == 0 ) {
			// Start variation set
			$this->is_displayed = in_array( $category->term_id, $selected_cats );
			$output .= '<div class="variation_set">';
			$output .= '<label class="set_label">
					<input type="checkbox"' . checked( $this->is_displayed, true, false ) .'name="variations[' . $category->term_id . ']" value="1">
					' . esc_html( apply_filters( 'the_category', $category->name ) ) . '
				</label>';
		} else {
			// Start variation
			$output .= '<div class="variation"' . ( $this->is_displayed ? '' : ' style="display:none;"' ) . '>
				<label>
					<input type="checkbox"' . checked( in_array( $category->term_id, $selected_cats ), true, false ) . 'name="edit_var_val[' . $category->parent . '][' . $category->term_id . ']" value="1">
					' . esc_html( apply_filters( 'the_category', $category->name ) ) . '
				</label>';
		}
	}
	
	// End variation set or variation
	function end_el( &$output, $category, $depth, $args ) {
		$output .= '</div>';
		if ( $depth == 0 )
			$this->is_displayed = true;
	}
}