module.exports = function( currency, ajaxurl, baseRoute ) {
	return Backbone.Model.extend({
		defaults: {
			id             : 0,
			url            : '',
			price          : '',
			formattedPrice : '',
			title          : '',
			thumb          : '',
			quantity       : 0,
			remove_url     : '',
			variations     : [],
			action         : ''
		},

		initialize: function() {
			this.listenTo( this, 'add remove', this.sync );
		},

		getTotal : function() {
			return this.get( 'price' ) * parseInt( this.get( 'quantity' ), 10 );
		},

		get : function( attribute ) {
			var value = Backbone.Model.prototype.get.call( this, attribute );

			switch ( attribute ) {
				case 'id':
				case 'quantity':
					value = parseInt( value, 10 );
					break;

				case 'price':
					value = parseFloat( value ).toFixed(2);
					break;

				case 'formattedPrice':
					value = currency.format( this.get( 'price' ) );
					break;

				case 'variations':
					break;

				default:
					value = value.trim();
					break;
			}

			return value;
		},

		url: function() {
			var url = baseRoute + encodeURIComponent( this.get( 'id' ) ) + '?_wp_nonce='+ encodeURIComponent( this.get( 'nonce' ) );

			// switch( this.get( 'action' ) ) {

			// 	case 'edit':
			// 		url += '&action=edit&quantity=' + this.get( 'quantity' );
			// 		break;

			// 	default:
			// 		url += '&action=' + this.get( 'action' );
			// 		break;
			// }

			return url;
		}
	});
};
