module.exports = function( args, $, log ) {
	return Backbone.View.extend({
		el: '#wpsc-cart-notification',
		template  : wp.template( 'wpsc-modal-inner' ),
		status: {},
		events   : {
			'click .wpsc-close-modal' : 'clickClose',
			'click .wpsc-cart-view-toggle i' : 'clickToggleView'
		},

		initialize: function() {
			this.$overlay = $( 'wpsc-modal-overlay' );

			this.status = new args.statusModel( args.initialStatus );
			this.status.set( 'numberItems', this.collection.length );
			this.status.set( 'numberChanged', this.collection.length );

			this.listenTo( this.collection, 'remove', this.checkEmpty );
			this.listenTo( this.collection, 'add remove', this.updateStatusandRender );
			this.listenTo( this.collection, 'render', this.render );
			this.listenTo( this.collection, 'change', this.render );
			this.listenTo( this.collection, 'error', this.handleError );
			// this.listenTo( this.collection, 'sync', this.didSync );

			this.listenTo( this.status, 'change', this.maybeUpdateView );
			this.listenTo( this, 'open', this.renderNoAction );
			this.listenTo( this, 'close', this.close );
			this.listenTo( this, 'add-to-cart', this.maybeAdd );

			// this.render();
			this.renderNoShow();
		},

		// didSync: function( didSync ) {
		// 	// log( 'Collection didSync', didSync );
		// },

		handleError: function( errorObject ) {
			log( 'Collection handleError', errorObject );
		},

		renderNoAction: function() {
			this.status.set( 'action', 'none' );
			this.render();
		},

		renderNoShow: function() {

			this.$el
				// Update cart HTML
				.html( this.template( this.status.toJSON() ) )
				// Then insert the products node.
				.find( '.wpsc-cart-body' ).html( this._getProducts() );

			return this;
		},

		render: function() {

			this.renderNoShow();
			this.$overlay.removeClass( 'wpsc-hide' );

			// Now that it's open, calculate it's inner height...
			var newHeight = this.$el
				.removeClass( 'wpsc-hide' ).removeClass( 'wpsc-cart-set-height' )
				.find( '.wpsc-cart-notification-inner' ).outerHeight();

			// Do some calculation to make sure we don't go over 70% of the height of window.
			var winHeight = jQuery( window ).height();
			var maxHeight = winHeight - ( winHeight * 0.3 );

			if ( newHeight > maxHeight ) {
				newHeight = maxHeight;
				this.$el.addClass( 'wpsc-overflow' );
			} else {
				this.$el.removeClass( 'wpsc-overflow' );
			}

			// And set the height of the modal to match.
			this.$el.height( Math.round( newHeight ) ).addClass( 'wpsc-cart-set-height' );

			return this;
		},

		_getProducts: function() {
			var productNodes = document.createDocumentFragment();

			// create a sub view for every model in the collection
			this.collection.each( function( model ) {
				var row = new args.rowView( { model: model } );
				productNodes.appendChild( row.render().el );
			} );

			return productNodes;
		},

		checkEmpty: function() {
			if ( ! this.collection.length ) {
				this.close();
			}
		},

		maybeUpdateView: function( statusChanged ) {
			if ( this.collection.length > 0 && ( statusChanged.changed.view || statusChanged.changed.total ) ) {
				this.render();
			}
		},

		close: function() {
			this.$overlay.addClass( 'wpsc-hide' );
			this.$el.addClass( 'wpsc-hide' );
		},

		updateStatusandRender: function() {
			var prevNumber = this.status.get( 'numberItems' );
			var numberChanged = this.collection.length - prevNumber;

			this.status.set( 'action', numberChanged < 0 ? 'removed' : 'added' );

			numberChanged = Math.abs( numberChanged );
			numberChanged = numberChanged < 1 ? 1 : numberChanged;

			this.status.set( 'numberChanged', numberChanged );
			this.status.set( 'numberItems', this.collection.length );
			this.status.set( 'total', this.collection.totalPrice() );
		},

		maybeAdd: function( data ) {
			var model = this.collection.getById( data.id );

			if ( model ) {
				var qty = Math.round( parseInt( model.get( 'quantity' ), 10 ) + parseInt( data.quantity, 10 ) );

				// Update quantity.
				model.set( 'quantity', qty );

				this.status.set( 'total', this.collection.totalPrice() );

			} else {
				model = this.collection.create( data );
			}

			return model;
		},

		clickClose: function( evt ) {
			evt.preventDefault();
			this.close();
		},

		clickToggleView: function( evt ) {
			evt.preventDefault();

			// Set the view state.
			this.status.set( 'view', jQuery( evt.target ).data( 'view' ) );
		}

	} );
};
