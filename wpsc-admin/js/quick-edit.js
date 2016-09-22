(function($) {

	// we create a copy of the WP inline edit post function
	var $wp_inline_edit = inlineEditPost.edit;
	
	// and then we overwrite the function with our own code
	inlineEditPost.edit = function( id ) {
	
		// "call" the original WP edit function
		// we don't want to leave WordPress hanging
		$wp_inline_edit.apply( this, arguments );
		
		// now we take care of our business
		
		// get the post ID
		var $post_id = 0;
		if ( typeof( id ) == 'object' )
			$post_id = parseInt( this.getId( id ) );
			
		if ( $post_id > 0 ) {
		
			// define the edit row
			var $edit_row = $( '#edit-' + $post_id );
			
			// get the data
			var $stock = $( '#inline_' + $post_id + '_stock' ).text();
			var $sku = $( '#inline_' + $post_id + '_sku' ).text();
			var $price = $( '#inline_' + $post_id + '_price' ).text();
			var $sale_price = $( '#inline_' + $post_id + '_sale_price' ).text();
			var $weight = $( '#inline_' + $post_id + '_weight' ).text();

			// assign data to quick edit fields
			$edit_row.find( 'input[name="stock"]' ).val( $stock );
			$edit_row.find( 'input[name="sku"]' ).val( $sku );
			$edit_row.find( 'input[name="price"]' ).val( $price );
			$edit_row.find( 'input[name="sale_price"]' ).val( $sale_price );
			$edit_row.find( 'input[name="weight"]' ).val( $weight );
		}
	};	
})(jQuery);