module.exports = function( notifs ) {

	return Backbone.Model.extend({
		defaults: {
			id             : 0,
			nonce          : '',
			deleteNonce    : '',
			url            : '',
			price          : '',
			formattedPrice : '',
			title          : '',
			thumb          : '',
			quantity       : 0,
			remove_url     : '',
			variations     : [],
			editQty        : false,
			action         : ''
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
					value = notifs.currency.format( this.get( 'price' ) );
					break;

				case 'variations':
					break;

				case 'editQty':
					value = Boolean( value );
					break;

				default:
					value = value.trim();
					break;
			}

			return value;
		},

		sync: function( method, model, options ) {
			var beforeSend;

			options = options || {};
			options.url = model.url();

			if ( 'update' === method ) {
				if ( 'quantity' in model.changed ) {
					options.url = this.addQueryVar( options.url, 'quantity', encodeURIComponent( this.get( 'quantity' ) ) );
				} else {
					options.url = model.collection.url;
					options.url += '/add/' + encodeURIComponent( this.get( 'id' ) );
				}
			}

			var nonce = 'delete' === method ? this.get( 'deleteNonce' ) : this.get( 'nonce' );
			options.url = this.addQueryVar( options.url, '_wp_nonce', encodeURIComponent( nonce ) );

			if ( ! _.isUndefined( notifs.apiNonce ) && ! _.isNull( notifs.apiNonce ) ) {
				beforeSend = options.beforeSend;

				options.beforeSend = function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', notifs.apiNonce );

					if ( beforeSend ) {
						return beforeSend.apply( this, arguments );
					}
				};
			}

			// window.console.warn('method', method);
			// window.console.warn('model.changed', model.changed);
			// window.console.warn('options.url', options.url);
			return Backbone.sync( method, model, options );
		},

		addQueryVar :function( uri, key, value ) {
			var re = new RegExp( '([?&])' + key + '=.*?(&|$)', 'i' );

			if ( uri.match( re ) ) {
				return uri.replace( re, '$1' + key + '=' + value + '$2' );
			}

			return uri + ( -1 !== uri.indexOf( '?' ) ? '&' : '?' ) + key + '=' + value;
		}
	} );
};
