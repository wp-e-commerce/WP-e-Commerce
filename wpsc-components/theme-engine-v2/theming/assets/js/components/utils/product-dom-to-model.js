module.exports = function( currency ) {

	var getVariationsFromProductForm = function( $productForm ) {
		var $variations  = $productForm.find( '[name^="wpsc_product_variations"]' );
		var variations   = [];

		if ( ! $variations.length ) {
			return variations;
		}

		$variations.each( function() {
			var $variation = jQuery( this );
			var $variationLabel = $productForm.find( 'label[for="'+ $variation.attr( 'id' ) +'"]' );
			var variationValue, $selected, $selectedLabel;

			switch( $variation[0].tagName ) {
				case 'RADIO':
					$selected = $variation.find( '[value="'+ $variation.val() +'"]' );
					$selectedLabel = $selected.length ? $productForm.find( 'label[for="'+ $selected.attr( 'id' ) +'"]' ).text() : [];
					if ( $selectedLabel.length ) {
						variationValue = $selectedLabel.text();
					}
					break;

				case 'SELECT':
					$selected = $variation.find( '[value="'+ $variation.val() +'"]' );
					if ( $selected.length ) {
						variationValue = $selected.text();
					}
					break;

				default:
					variationValue = $variation.val();
					break;
			}

			variations.push( {
				label : $variationLabel.length ? $variationLabel.text() : '',
				value : variationValue
			} );
		} );

		return variations;
	};

	return {
		prepare : function( $product, $productForm ) {
			$productForm   = $productForm && $productForm.length ? $productForm : $product.find( '.wpsc-add-to-cart-form' );
			var nonce      = $productForm.find( '[name="_wp_nonce"]' ).val();
			var $thumb     = $product.find( '.wpsc-product-thumbnail' );
			var $salePrice = $product.find( '.wpsc-product-price .wpsc-sale-price .wpsc-amount' );
			var price      = $salePrice.length ? $salePrice.text() : $product.find( '.wpsc-product-price .wpsc-amount' ).last().text();

			return {
				id             : $productForm.data( 'id' ),
				nonce          : nonce,
				url            : $thumb.length ? $thumb.attr( 'href' ) : $product.find( '.wpsc-product-title > a' ).attr( 'href' ),
				price          : currency.deformat( price ),
				formattedPrice : price,
				title          : $product.find( '.wpsc-product-title > a' ).text(),
				thumb          : $thumb.length ? $thumb.html() : '',
				quantity       : $productForm.find( '[name="quantity"]' ).val(),
				remove_url     : '',
				variations     : getVariationsFromProductForm( $productForm )
			};
		}
	};
};
