module.exports = function( currency, strings ) {
	return Backbone.Model.extend({
		sync: function () { return false; },

		defaults: {
			status         : 'closed',
			action         : 'added',
			actionText     : strings.status_added,
			actionIcon     : 'wpsc-icon-check',
			countClass     : '',
			view           : 'expanded',
			total          : 0,
			formattedTotal : 0,
			subTotal       : 0,
			shippingTotal  : 0,
			numberChanged  : 1,
			numberItems    : 0
		},

		_get : function( value, attribute ) {
			var action;

			switch ( attribute ) {
				case 'countClass':
					value = 'none' === this.get( 'action' ) ? 'wpsc-hide' : this.defaults.countClass;
					break;

				case 'actionText':
					action = this.get( 'action' );
					value = strings[ 'status_' + action ] ? strings[ 'status_' + action ] : this.defaults.actionText;
					break;

				case 'formattedTotal':
					value = currency.format( this.get( 'total' ) );
					break;

				case 'actionIcon':
					switch( this.get( 'action' ) ) {
						case 'removed':
							value = 'wpsc-icon-remove-sign';
							break;

						case 'none':
							value = 'wpsc-hide';
							break;

						default:
							value = this.defaults.actionIcon;
					}
					break;
			}

			return value;
		},

		get : function( attribute ) {
			return this._get( Backbone.Model.prototype.get.call( this, attribute ), attribute );
		},

		// hijack the toJSON method and overwrite the data that is sent back to the view.
		toJSON: function() {
			return _.mapObject( Backbone.Model.prototype.toJSON.call( this ), _.bind( this._get, this ) );
		}
	} );
};
