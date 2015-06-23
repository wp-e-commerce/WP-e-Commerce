/*global WPSC_Price_Table */
;(function($){
	WPSC_Price_Table.subtotal *= 1;
	WPSC_Price_Table.tax *= 1;

	var format_price = function(amt) {
		var parts = amt.toFixed(WPSC_Price_Table.formatter.decimals).split('.');
		var num;
		if (parts[0].length > 3) {
			parts[0] = parts[0].replace(
				/\B(?=(?:\d{3})+(?!\d))/g,
				WPSC_Price_Table.formatter.thousands_separator
			);
		}

		num = parts.join(WPSC_Price_Table.formatter.decimal_separator);

		switch ( WPSC_Price_Table.formatter.sign_location * 1 ) {
			case 1:
				return num + WPSC_Price_Table.formatter.symbol;
			case 2:
				return num + ' ' + WPSC_Price_Table.formatter.symbol;
			case 3:
				return WPSC_Price_Table.formatter.symbol + num;
			case 4:
				return WPSC_Price_Table.formatter.symbol + ' ' + num;
		}

		return num;
	};

	var recalculate_total = function() {
		var checked = $('input[name="wpsc_shipping_option"]:checked');
		var value = checked[0].value;
		var shipping = WPSC_Price_Table.shipping[value];
		var discount = WPSC_Price_Table.discount;
		var total =
				WPSC_Price_Table.subtotal +
				WPSC_Price_Table.tax      +
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