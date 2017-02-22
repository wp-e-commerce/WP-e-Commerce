;(function($) {
	$( '.wpsc-field-textfield' ).FloatLabel();
	$( '.wpsc-field-select_region' ).FloatLabel();
	$( '.wpsc-field-select_country' ).FloatLabel();
	$( '.wpsc-field-password' ).FloatLabel();
	$( '.wpsc-cc-field' ).FloatLabel();

	$('.wpsc-credit-card-form-card-number').on('keypress change', function (e) {
		var a = [];
    	var k = e.which;

    	for (i = 48; i < 58; i++)
	        a.push(i);

	    if (!(a.indexOf(k)>=0))
        	e.preventDefault();

    	$(this).val(function (index, value) {
			return value.replace(/\W/gi, '').replace(/(.{4})/g, '$1 ');
		});
	});
	$('.wpsc-credit-card-form-card-expiry').on('keypress change', function (e) {
		var a = [];
    	var k = e.which;

    	for (i = 48; i < 58; i++)
	        a.push(i);

	    if (!(a.indexOf(k)>=0))
        	e.preventDefault();

    	$(this).val(function (index, value) {
			return value.replace(/\W/gi, '').replace(/(.{2})/g, '$1\/');
		});
	});
	$('.wpsc-credit-card-form-card-cvc').on('keypress change', function (e) {
		var a = [];
    	var k = e.which;

    	for (i = 48; i < 58; i++)
	        a.push(i);

	    if (!(a.indexOf(k)>=0))
        	e.preventDefault();
	});

})(jQuery);