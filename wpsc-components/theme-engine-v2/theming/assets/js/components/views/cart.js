module.exports = function( args, $id, log ) {
	return Backbone.View.extend({
		el: '#wpsc-cart-notification',
		$btn : '',
		$overlay : '',
		template  : wp.template( 'wpsc-modal-inner' ),
		status: {},
		events   : {
			'click .wpsc-close-modal' : 'clickClose',
			'click .wpsc-cart-view-toggle i' : 'clickToggleView'
		},

		initialize: function() {
			this.$btn     = $id( 'wpsc-view-cart-button' );
			this.$overlay = $id( 'wpsc-modal-overlay' );

			this.status = new args.statusModel( args.initialStatus );
			this.status.set( 'numberItems', this.collection.length );
			this.status.set( 'numberChanged', this.collection.length );

			this.listenTo( this.collection, 'remove', this.checkEmpty );
			this.listenTo( this.collection, 'add remove', this.updateStatusandRender );
			this.listenTo( this.collection, 'render', this.render );
			this.listenTo( this.collection, 'change:quantity', this.setTotal );
			this.listenTo( this.collection, 'change:editQty', this.calculateHeight );
			this.listenTo( this.collection, 'error', this.handleError );

			this.listenTo( this.status, 'change', this.maybeUpdateView );
			this.listenTo( this, 'open', this.renderNoAction );
			this.listenTo( this, 'close', this.close );
			this.listenTo( this, 'add-to-cart', this.maybeAdd );

			this.renderNoShow();
		},

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
			this.status.set( 'status', 'open' );

			this.calculateHeight();

			return this;
		},

		calculateHeight: function() {
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
			this.status.set( 'status', 'closed' );
			this.collection.trigger( 'closeModal' );
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
			this.setTotal();

			this.$btn[ this.collection.length ? 'removeClass' : 'addClass' ]( 'wpsc-hide' );
		},

		setTotal: function() {
			this.status.set( 'total', this.collection.totalPrice() );
		},

		maybeAdd: function( data ) {
			var model = this.collection.getById( data.id );

			if ( model ) {
				var qty = Math.round( parseInt( model.get( 'quantity' ), 10 ) + parseInt( data.quantity, 10 ) );

				// Update quantity.
				model.set( 'quantity', qty );

				this.setTotal();

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
