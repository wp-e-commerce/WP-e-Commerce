/*global jQuery, wpec_ppbt */
( function($) {
	var clientToken = wpec_ppbt.ctoken;
	var errmsg = '';
	var components = {
	  client: null,
	  threeDSecure: null,
	  hostedFields: null,
	  paypalCheckout: null,
	  kount: null,
	};

	var gateway;
	var my3DSContainer;
	var modal = $('#pp-btree-hosted-fields-modal');
	var closeFrame = $('#pp-btree-hosted-fields-text-close');
	var bankFrame = $('.pp-btree-hosted-fields-bt-modal-body');
	var cart_form = $( '#wpsc-checkout-form, .wpsc_checkout_forms' );
	var submit_btn = $('.wpsc-checkout-form-button, .wpsc_buy_button');
	var paypalButton = $('#pp_braintree_pp_button');

	function create3DSecure( clientInstance ) {
		// DO 3DS
		if ( wpec_ppbt.t3ds == '1' ) {
			braintree.threeDSecure.create({
				client:  clientInstance
			}, function (threeDSecureErr, threeDSecureInstance) {
				if (threeDSecureErr) {
				  // Handle error in 3D Secure component creation
				  console.error('error in 3D Secure component creation');
				  return;
				}
				components.threeDSecure = threeDSecureInstance;
			});
		}
	}

	function addFrame(err, iframe) {
		// Set up your UI and add the iframe.
		bankFrame.append(iframe);
		modal.removeClass('pp-btree-hosted-fields-modal-hidden');
		modal.focus();
	}

	function removeFrame() {
		var iframe = $('.pp-btree-hosted-fields-bt-modal-body iframe');
		modal.addClass('pp-btree-hosted-fields-modal-hidden');
		$( iframe ).remove();
		submit_btn.attr('disabled', false);
	}

	function createHostedFields( clientInstance ) {
		braintree.hostedFields.create({
			client: clientInstance,
			styles: {
			  'input': {
				'font-size': '14px',
				'font-family': 'helvetica, tahoma, calibri, sans-serif',
				'font-weight': 'lighter',
				'color': '#ccc'
			  },
			  ':focus': {
				'color': 'black'
			  },
			  '.valid': {
				'color': '#8bdda8'
			  }
			},
			fields: {
			  number: {
				selector: '#braintree-credit-cards-card-number',
				placeholder: '4111 1111 1111 1111'
			  },
			  cvv: {
				selector: '#braintree-credit-cards-card-cvc',
				placeholder: '123'
			  },
			  expirationDate: {
				selector: '#braintree-credit-cards-card-expiry',
				placeholder: 'MM/YYYY'
			  }
			}
		}, function (hostedFieldsErr, hostedFieldsInstance) {
			if (hostedFieldsErr) {
				console.error(hostedFieldsErr.code);
				alert(hostedFieldsErr.code);
				return;
			}
			components.hostedFields = hostedFieldsInstance;
			submit_btn.attr('disabled', false);
			cart_form.on( 'submit', function (event) {
				if ( gateway !== 'braintree-credit-cards' ) { return; }
				event.preventDefault();
				components.hostedFields.tokenize(function (tokenizeErr, payload) {
					if (tokenizeErr) {
						switch (tokenizeErr.code) {
						  case 'HOSTED_FIELDS_FIELDS_EMPTY':
							// occurs when none of the fields are filled in
							errmsg = 'Please enter credit card details!';

							break;
						  case 'HOSTED_FIELDS_FIELDS_INVALID':
							// occurs when certain fields do not pass client side validation
							errmsg = 'Some credit card fields are invalid: ' + tokenizeErr.details.invalidFieldKeys;
							break;
						  case 'HOSTED_FIELDS_TOKENIZATION_FAIL_ON_DUPLICATE':
							// occurs when:
							//   * the client token used for client authorization was generated
							//     with a customer ID and the fail on duplicate payment method
							//     option is set to true
							//   * the card being tokenized has previously been vaulted (with any customer)
							// See: https://developers.braintreepayments.com/reference/request/client-token/generate/#options.fail_on_duplicate_payment_method
							errmsg = 'This payment method already exists in your vault.';
							break;
						  case 'HOSTED_FIELDS_TOKENIZATION_CVV_VERIFICATION_FAILED':
							// occurs when:
							//   * the client token used for client authorization was generated
							//     with a customer ID and the verify card option is set to true
							//     and you have credit card verification turned on in the Braintree
							//     control panel
							//   * the cvv does not pass verfication (https://developers.braintreepayments.com/reference/general/testing/#avs-and-cvv/cid-responses)
							// See: https://developers.braintreepayments.com/reference/request/client-token/generate/#options.verify_card
							errmsg = 'CVV did not pass verification';
							break;
						  case 'HOSTED_FIELDS_FAILED_TOKENIZATION':
							// occurs for any other tokenization error on the server
							errmsg = 'Tokenization failed server side. Is the card valid?';
							break;
						  case 'HOSTED_FIELDS_TOKENIZATION_NETWORK_ERROR':
							// occurs when the Braintree gateway cannot be contacted
							errmsg = 'Network error occurred when tokenizing.';
							break;
						  default:
							errmsg = 'Something bad happened!' + tokenizeErr;
						}

						console.error(errmsg);
						alert(errmsg);
						location.reload();
						return;
					}
					
					if ( components.threeDSecure ) {
						components.threeDSecure.verifyCard({
							amount: wpec_ppbt.cart_total,
							nonce: payload.nonce,
							addFrame: addFrame,
							removeFrame: removeFrame
							}, function (err, response) {
								// Handle response
								if (!err) {
									var liabilityShifted = response.liabilityShifted; // true || false
									var liabilityShiftPossible =  response.liabilityShiftPossible; // true || false
									if ( liabilityShifted ) {
										// The 3D Secure payment was successful so proceed with this nonce
										$('input[name="pp_btree_method_nonce"]').val( response.nonce );
										cart_form[0].submit();
									} else {
										// The 3D Secure payment failed an initial check so check whether liability shift is possible
										if (liabilityShiftPossible) {
											// LiabilityShift is possible so proceed with this nonce
											$('input[name="pp_btree_method_nonce"]').val( response.nonce );
											cart_form[0].submit();
										} else {
											if ( wpec_ppbt.t3dsonly == '1' ) {
												// Check whether the 3D Secure check has to be passed to proceeed. If so then show an error
											  console.error('There was a problem with your payment verification');
											  alert('There was a problem with your payment verification');
											  return;
											} else {
												// ...and if not just proceed with this nonce
												$('input[name="pp_btree_method_nonce"]').val( response.nonce );
												cart_form[0].submit();
											}
										}
									}
									// 3D Secure finished. Using response.nonce you may proceed with the transaction with the associated server side parameters below.
									$('input[name="pp_btree_method_nonce"]').val( response.nonce );
									cart_form[0].submit();
								} else {
									// Handle errors
									console.log('verification error:', err);
									alert( 'Sorry this card type isn`t supported, please use a different card or payment method' );
									return;
								}
							});
						} else {
							// send the nonce to your server.
							$('input[name="pp_btree_method_nonce"]').val( payload.nonce );
							cart_form[0].submit();
						}
				});
			});
		});
	};

	function createPayPalCheckout( clientInstance ) {
		  braintree.paypalCheckout.create({
			client: clientInstance
		  }, function (paypalErr, paypalCheckoutInstance) {
			if (paypalErr) {
			  console.error('Error creating PayPal:', paypalErr);
			  alert(paypalErr.code);
			  return;
			}
			components.paypalCheckout = paypalCheckoutInstance;
			paypalButton.attr('disabled', false);
			// Set up PayPal with the checkout.js library
			paypal.Button.render({
				env: wpec_ppbt.sandbox,
				style: {
					label: 'pay',
					size:  wpec_ppbt.but_size,
					shape: wpec_ppbt.but_shape,
					color: wpec_ppbt.but_colour,
				},
				commit: true,
			payment: function () {
				var args = {
					 flow: 'checkout', // Required
					 intent: 'sale',
					 amount: wpec_ppbt.cart_total, // Required
					 currency: wpec_ppbt.currency, // Required
					 locale: 'en_US',
					 useraction: 'commit'
				 };

				if ( wpec_ppbt.is_shipping ) {
					args = $.extend( args, {
					enableShippingAddress: true,
					shippingAddressEditable: false,
					shippingAddressOverride: {
					  recipientName: $( 'input[title="shippingfirstname"]' ).val() + $( 'input[title="shippinglastname"]' ).val(),
					  line1: $( 'textarea[title="shippingaddress"]' ).text(),
					  city: $( 'input[title="shippingcity"]' ).val(),
					  countryCode: $( 'select[data-wpsc-meta-key="shippingcountry"]' ).val(),
					  postalCode: $( 'input[title="shippingpostcode"]' ).val(),
					  state: replace_state_code( $( 'input[title="shippingstate"]' ).val() ),
					}
				} );
				} else {    
					args.enableShippingAddress = false;
				}

				return components.paypalCheckout.createPayment( args );
			},
			  onAuthorize: function (data, actions) {
				return components.paypalCheckout.tokenizePayment(data)
				  .then(function (payload) {
					// Submit `payload.nonce` to your server
					paypalButton.attr('disabled', true);
					$('input[name="pp_btree_method_nonce"]').val( payload.nonce );
					cart_form[0].submit();
				  });
			  },
			  onCancel: function (data) {
				console.log('checkout.js payment cancelled', JSON.stringify(data, 0, 2));
			  },
			  onError: function (err) {
				msg = 'There\'s been a problem processing your payment, please check and retry. If you continue to have an issue please choose an alternative payment method';
				console.error( msg );
				alert( msg );
				return;
			  }
			}, document.getElementById( 'pp_braintree_pp_button' ) ).then(function () {
			  // The PayPal button will be rendered in an html element with the id
			  // `paypal-button`. This function will be called when the PayPal button
			  // is set up and ready to be used.
			});
		  });
	};

	function replace_state_code( state ) {
		var states = {
			'Alabama':'AL',
			'Alaska':'AK',
			'Arizona':'AZ',
			'Arkansas':'AR',
			'California':'CA',
			'Colorado':'CO',
			'Connecticut':'CT',
			'Delaware':'DE',
			'Florida':'FL',
			'Georgia':'GA',
			'Hawaii':'HI',
			'Idaho':'ID',
			'Illinois':'IL',
			'Indiana':'IN',
			'Iowa':'IA',
			'Kansas':'KS',
			'Kentucky':'KY',
			'Louisiana':'LA',
			'Maine':'ME',
			'Maryland':'MD',
			'Massachusetts':'MA',
			'Michigan':'MI',
			'Minnesota':'MN',
			'Mississippi':'MS',
			'Missouri':'MO',
			'Montana':'MT',
			'Nebraska':'NE',
			'Nevada':'NV',
			'New Hampshire':'NH',
			'New Jersey':'NJ',
			'New Mexico':'NM',
			'New York':'NY',
			'North Carolina':'NC',
			'North Dakota':'ND',
			'Ohio':'OH',
			'Oklahoma':'OK',
			'Oregon':'OR',
			'Pennsylvania':'PA',
			'Rhode Island':'RI',
			'South Carolina':'SC',
			'South Dakota':'SD',
			'Tennessee':'TN',
			'Texas':'TX',
			'Utah':'UT',
			'Vermont':'VT',
			'Virginia':'VA',
			'Washington':'WA',
			'West Virginia':'WV',
			'Wisconsin':'WI',
			'Wyoming':'WY'
		};
		return states[state];
	}

	function wpscCheckSubmitStatus( e ) {
		var pp_button = $( '.wpsc-checkout-form-button, .wpsc_buy_button' );
		gateway = $( 'input[name="custom_gateway"]:checked, input[name="custom_gateway"]:hidden, input[name="wpsc_payment_method"]:checked' ).val();

		if ( gateway == 'braintree-paypal' ) {
			if ( e && e.keyCode == 13 ) {
				e.preventDefault();
				return;
			}

			if ( pp_button.is(":visible") ) {
				pp_button.hide();
				return;
			}
		}

		pp_button.show();
	}

	function wpscBootstrapBraintree() {
		//Disable the regular purchase button if using PayPal
		wpscCheckSubmitStatus();

		if ( gateway !== 'braintree-credit-cards' && gateway !== 'braintree-paypal' ) {
			return;
		}
		
		if ( components.client ) {
			return;
		}

		braintree.client.create({
		  authorization: clientToken
		}, function(err, clientInstance) {
		  if (err) {
			console.error(err);
			return;
		  }
		  components.client = clientInstance;

		  braintree.dataCollector.create({
			client: clientInstance,
			kount: true
		  }, function (err, dataCollectorInstance) {
			if (err) {
			  console.log(err);
			  // Handle error in creation of data collector
			  return;
			}
			// At this point, you should access the dataCollectorInstance.deviceData value and provide it
			// to your server, e.g. by injecting it into your form as a hidden input.
			components.kount = dataCollectorInstance.deviceData;
			
			$('#pp_btree_card_kount').val( components.kount );
		  });

		  if ( wpec_ppbt.is_cc_active ) {
			  create3DSecure( clientInstance );
			  createHostedFields(clientInstance);
		  }
		  if ( wpec_ppbt.is_pp_active ) {
			createPayPalCheckout(clientInstance );
		  }
		});


	}
	
	closeFrame.on( 'click', function () {
	  components.threeDSecure.cancelVerifyCard( removeFrame() );
	});

	$( document ).ready( wpscBootstrapBraintree );
	$( document ).on( 'keypress', '.wpsc_checkout_forms', wpscCheckSubmitStatus );
	$( document ).on( 'keypress', '#wpsc-checkout-form', wpscCheckSubmitStatus );
	$( 'input[name=\"custom_gateway\"]' ).change( wpscBootstrapBraintree );
	$( 'input[name=\"wpsc_payment_method\"]' ).change( wpscBootstrapBraintree );

} )( jQuery );