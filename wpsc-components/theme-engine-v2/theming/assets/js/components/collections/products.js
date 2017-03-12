module.exports = function( notifs ) {
	return Backbone.Collection.extend({
		model : notifs.models.Product,
		url : notifs.baseRoute,

		getById : function( id ) {
			id = parseInt( id, 10 );
			return this.find( function( model ) {
				return model.get( 'id' ) === id;
			} );
		},

		totalPrice: function(){
			return this.reduce( function( memo, model ) {
				return memo + model.getTotal();
			}, 0 ).toFixed(2);
		},

		sync: function( method, model, options ) {
			var beforeSend;

			options = options || {};

			if ( ! _.isUndefined( notifs.apiNonce ) && ! _.isNull( notifs.apiNonce ) ) {
				beforeSend = options.beforeSend;

				options.beforeSend = function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', notifs.apiNonce );

					if ( beforeSend ) {
						return beforeSend.apply( this, arguments );
					}
				};
			}

			return Backbone.sync( method, model, options );
		}
	});
};
