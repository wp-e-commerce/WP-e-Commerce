;(function($){
	var l10n = window.WPSC.priceTable;

	l10n.subtotal *= 1;
	l10n.tax *= 1;

	var format_price = function(amt) {
		var parts = amt.toFixed(l10n.formatter.decimals).split('.');
		var num;
		if (parts[0].length > 3) {
			parts[0] = parts[0].replace(
				/\B(?=(?:\d{3})+(?!\d))/g,
				l10n.formatter.thousands_separator
			);
		}

		num = parts.join(l10n.formatter.decimal_separator);

		switch ( l10n.formatter.sign_location * 1 ) {
			case 1:
				return num + l10n.formatter.symbol;
			case 2:
				return num + ' ' + l10n.formatter.symbol;
			case 3:
				return l10n.formatter.symbol + num;
			case 4:
				return l10n.formatter.symbol + ' ' + num;
		}

		return num;
	};

	var recalculate_total = function() {
		var checked = $('input[name="wpsc_shipping_option"]:checked');
		var value = checked[0].value;
		var shipping = l10n.shipping[value];
		var discount = l10n.discount;
		var total =
				l10n.subtotal +
				l10n.tax      +
				shipping -
				discount;
		$('.wpsc-cart-shipping-row td').text(format_price(shipping));
		$('.wpsc-cart-total-row td').text(format_price(total));
	};

	$(function() {
		var shipping_options = $('input[name="wpsc_shipping_option"]');
		shipping_options.on('change', recalculate_total);
		$('.wpsc-cart-shipping-row, .wpsc-cart-total-row').show();
			recalculate_total();
		});
})(jQuery);
