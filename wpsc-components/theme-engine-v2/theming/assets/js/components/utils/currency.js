module.exports = function( l10n ) {
	var currency = {
		l10n : l10n,
		template : false
	};

	currency.format = function( amt ) {

		// Format the price for output
		amt = currency.numberFormat( amt, l10n.decimals, l10n.decimalSep, l10n.thousandsSep );

		if ( ! currency.template ) {
			currency.template = wp.template( 'wpsc-currency-format' ); // #tmpl-wpsc-currency-format
		}

		return currency.template( {
			'code'   : l10n.code,
			'symbol' : l10n.symbol,
			'amount' : amt
		} ).trim();
	};

	currency.deformat = function( formatted ) {
		var amount = formatted
			.replace( l10n.decimalSep, '.' )
			.replace( '-', '' )
			.replace( l10n.thousandsSep, '' )
			.replace( l10n.code, '' )
			.replace( l10n.symbol, '' );

		return parseFloat( amount ).toFixed(2);
	};

	// http://locutus.io/php/number_format/
	currency.numberFormat = function( number, decimals, decSep, thouSep ) {

		number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
		var n = !isFinite(+number) ? 0 : +number;
		var prec = !isFinite(+decimals) ? 0 : Math.abs(decimals);
		var sep = (typeof thouSep === 'undefined') ? ',' : thouSep;
		var dec = (typeof decSep === 'undefined') ? '.' : decSep;
		var s = '';

		var toFixedFix = function (n, prec) {
			var k = Math.pow(10, prec);
			return '' + (Math.round(n * k) / k)
				.toFixed(prec);
		};

		// @todo: for IE parseFloat(0.55).toFixed(0) = 0;
		s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
		if (s[0].length > 3) {
			s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
		}
		if ((s[1] || '').length < prec) {
			s[1] = s[1] || '';
			s[1] += new Array(prec - s[1].length + 1).join('0');
		}

		return s.join(dec);
	};

	return currency;
};
