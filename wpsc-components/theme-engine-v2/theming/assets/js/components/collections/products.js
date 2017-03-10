module.exports = function( currency, prodouctModel ) {
	return Backbone.Collection.extend({
		model : prodouctModel,

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
