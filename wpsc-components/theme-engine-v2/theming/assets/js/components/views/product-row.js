module.exports = function( log ) {
	return Backbone.View.extend({
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
			// 'click .wpsc-cart-item-edit' : 'edit'
			'click .wpsc-cart-item-remove' : 'removeIt'
		},

		initialize: function() {
			this.listenTo( this, 'change', this.maybeRender );
			this.listenTo( this, 'sync', this.didSync );
			this.listenTo( this, 'error', this.handleError );
		},

		handleError: function( errorObject ) {
			log( 'Model handleError', errorObject );
		},

		didSync: function( didSync ) {
			log( 'Model didSync', didSync );
		},

		// Render the row
		maybeRender: function( changedModel ) {
			if ( changedModel.changed.quantity ) {
				return;
			}
			this.render();
		},

		// Render the row
		render: function() {
			this.$el.html( this.template( this.model.toJSON() ) );
			return this;
		},

		edit: function(e) {
			e.preventDefault();

			// TODO: Show quantity input.
		},

		// Perform the Removal
		removeIt: function(e) {
			e.preventDefault();
			var _this     = this;

			// Ajax error handler
			var destroyError = function( model, response ) {
				log( 'destroyError', response );

				// for now:
				_this.$el.remove();
				// whoops.. re-show row and add error message
				// _this.$el.fadeIn( 300 );
			};

			// Ajax success handler
			var destroySuccess = function( model, response ) {
				// If our response reports success
				if ( response.success ) {
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
			this.model.destroy({ success: destroySuccess, error: destroyError, wait: true });
		}
	});
};
