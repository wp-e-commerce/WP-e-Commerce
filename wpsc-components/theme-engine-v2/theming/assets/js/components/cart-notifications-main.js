( function( window, document, $, notifs, undefined ) {
	'use strict';

	var ESCAPE = 27;

	var $id = function( id ) {
		return $( document.getElementById( id ) );
	};

	var log = function() {
		log.history = log.history || [];
		log.history.push( arguments );
		if ( notifs.debug && window.console && window.console.log ) {
			window.console.log( Array.prototype.slice.call(arguments) );
		}
	};

	notifs.currency = require( './utils/currency.js' )( notifs.currency );

	notifs.models = {
		Product : require( './models/product.js' )( notifs ),
		Status  : require( './models/status.js' )( notifs.currency, notifs.strings )
	};

	notifs.collections = {
		Products : require( './collections/products.js' )( notifs )
	};

	notifs.views = {
		ProductRow : require( './views/product-row.js' )( log, notifs )
	};

	notifs.views.Cart = require( './views/cart.js' )( {
		currency      : notifs.currency,
		statusModel   : notifs.models.Status,
		initialStatus : notifs.CartView.status,
		rowView       : notifs.views.ProductRow
	}, $id, log );


	notifs.init = function() {
		$( document.body )
			.on( 'click', '.wpsc-add-to-cart', notifs.clickAddProductToCart )
			.on( 'click', '#wpsc-modal-overlay', notifs.closeModal )
			.on( 'click', '#wpsc-view-cart-button', notifs.openModal )
			.append( $id( 'tmpl-wpsc-modal' ).html() );


		$( document ).on( 'keydown', function( evt ) {
			if ( ESCAPE === evt.which ) {
				notifs.closeModal();
			}
		} );

		// Kick it off.
		notifs.CartView = new notifs.views.Cart({
			collection : new notifs.collections.Products( notifs.CartView.items )
		} );
	};

	notifs.clickAddProductToCart = function( evt ) {
		var $productForm = null;
		var $this = $( this );
		var $product = $this.parents( '.wpsc-product' );
			evt.preventDefault();

		if ( ! $product.length ) {
			$productForm = $this.parents( '.wpsc-add-to-cart-form' );
			if ( $productForm.length ) {
				$product = $id( 'product-' + $productForm.data( 'id' ) );
			}
		}

		if ( $product.length ) {
			notifs.addProductToCart( $product, $productForm );
		}
	};

	notifs.addProductToCart = function( $product, $productForm ) {
		// Experimental.
		// TODO: Replace dom-to-model with actual localized JSON model data.
		notifs.domToModel = notifs.domToModel || require( './utils/product-dom-to-model.js' )( notifs.currency );
		notifs.CartView.trigger( 'add-to-cart', notifs.domToModel.prepare( $product, $productForm ) );
	};

	notifs.closeModal = function() {
		notifs.CartView.trigger( 'close' );
	};

	notifs.openModal = function() {
		notifs.CartView.trigger( 'open' );
	};

	$( notifs.init );

} )( window, document, jQuery, window.WPSC.cartNotifications );
