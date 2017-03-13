module.exports = function( notifs ) {
	return Backbone.Collection.extend({
		model : notifs.models.Product,
		url : notifs.baseRoute + '/cart',

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
	} );
};
