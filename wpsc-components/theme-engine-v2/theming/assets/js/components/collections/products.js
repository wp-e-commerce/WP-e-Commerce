module.exports = function( currency, prodouctModel, baseRoute ) {
	return Backbone.Collection.extend({
		model : prodouctModel,

		url: function( model ) {
			return baseRoute + model.get( 'id' ) + '?' + model.get( 'nonce' );
		},

		initialize: function() {
			this.listenTo( this, 'add remove', this.sync );
		},

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
		}

	});
};
