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
			var url = baseRoute + 'add/' + encodeURIComponent( this.get( 'id' ) ) + '?_wp_nonce='+ encodeURIComponent( this.get( 'nonce' ) );

			var qty = this.collection.pluck( 'quantity' );
			window.console.warn('qty', qty);
			// /store/cart
			// `/app/public/wp-content/plugins/WP-e-Commerce/wpsc-components/theme-engine-v2/mvc/controllers/cart.php:131:
			// array (size=4)
			//   '_wp_nonce' => string '78762affc4' (length=10)
			//   'quantity' =>
			//     array (size=1)
			//       0 => string '4' (length=1)
			//   'update_quantity' => string 'Update Quantity' (length=15)
			//   'action' => string 'update_quantity' (length=15)
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
