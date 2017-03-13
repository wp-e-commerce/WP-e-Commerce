module.exports = function( log, notifs ) {
	return Backbone.View.extend({
		$quantity : null,
		template : wp.template( 'wpsc-modal-product' ),

		tagName : 'div',

		id : function() {
			return 'wpsc-modal-cart-item-' + this.model.get( 'id' );
		},

		className : function() {
			var thumbClass = this.model.get( 'thumb' ) ? '' : 'no-';
			var id = this.model.get( 'id' );
			return 'wpsc-cart-item wpsc-cart-item-' + id + ' wpsc-cart-' + thumbClass + 'thumb';
		},

		// Attach events
		events : {
			'click .wpsc-cart-item-edit'   : 'editQty',
			'click .wpsc-cart-item-remove' : 'maybeRemoveIt',
			'click .wpsc-qty-button'       : 'modifyQty',
			'change .modify-cart-quantity' : 'quantityChanged'
		},

		initialize: function() {
			// this.listenTo( this, 'change', function( changedModel ) {
			// 	window.console.log( 'changedModel.changed', changedModel.changed );
			// } );

			this.listenTo( this.model, 'change:price', this.render );
			this.listenTo( this.model, 'change:formattedPrice', this.render );
			this.listenTo( this.model, 'change:editQty', this.render );
			this.listenTo( this.model.collection, 'closeModal', this.hideQty );

			this.listenTo( this, 'error', this.handleError );
		},

		handleError: function( errorObject ) {
			log( 'Model handleError', errorObject );
		},

		// Render the row
		render: function() {
			this.$el.html( this.template( this.model.toJSON() ) );
			return this;
		},

		editQty: function( evt ) {
			evt.preventDefault();
			var editQty = this.model.get( 'editQty' );
			this.model.set( 'editQty', ! editQty );
		},

		hideQty : function() {
			this.model.set( 'editQty', false );
		},

		quantityChanged: function( evt ) {
			var $input = this.$( '.modify-cart-quantity' );
			var qty = $input.val();

			if ( qty < 1 ) {
				// Check if they meant to remove the item.
				if ( this.maybeRemoveIt( evt ) ) {
					return;
				}
				// Ok, that was an oops, so keep the item around.
				qty = 1;
				$input.val( qty );
			}

			// Update cart item quantity.
			this.model.save( { quantity: qty } );
		},

		// Perform the Removal
		maybeRemoveIt: function( evt ) {
			if ( window.confirm( notifs.strings.sure_remove ) ) {
				this.removeIt( evt );
				return true;
			}

			return false;
		},

		// Perform the Removal
		removeIt: function( evt ) {
			evt.preventDefault();
			var _this     = this;

			// Ajax error handler
			var destroyError = function( model, response ) {
				log( 'destroyError', response );

				// whoops.. re-show row and add error message
				_this.$el.fadeIn( 300 );
			};

			// Ajax success handler
			var destroySuccess = function( model, response ) {
				// If our response reports success
				if ( response.id ) {
					log( 'destroySuccess', response );

					// remove our row completely
					_this.$el.remove();
				} else {
					// whoops, error
					destroyError( model, response );
				}
			};

			// Hide error message (if it's showing)
			// Optimistically hide row
			_this.$el.fadeOut( 300 );

			// Remove model and fire ajax event
			this.model.destroy({ success: destroySuccess, error: destroyError, wait: true } );

			return true;
		},

		modifyQty: function( evt ) {
			var $button = this.$( evt.currentTarget );
			var $input  = $button.parent().find( 'input' );
			var oldVal  = parseInt( $input.val(), 10 );
			var newVal  = oldVal + 1;

			if ( '-' === $button.text() ) {
			  // Don't allow decrementing below zero
			  if ( oldVal > 0 ) {
			      newVal = oldVal - 1;
			  } else {
			      newVal = 0;
			  }
			}

			$input.val( newVal ).trigger( 'change' );
		}

	} );
};
