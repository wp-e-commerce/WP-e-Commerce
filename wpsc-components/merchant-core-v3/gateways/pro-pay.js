window.WPSC_Pro_Pay = window.WPSC_Pro_Pay || {};

( function( window, document, $, wpsc, undefined ) {
	'use strict';

	var $c = {};

	var admin = {
		blur_timeout : null,
		reset_textbox_width : true,
		$ : $c
	};

	admin.cache = function() {
		$c.body           = $( document.body );
		$c.wrapper        = $( '#gateway_settings_pro-pay_form' );
		$c.spinner        = $c.wrapper.find( '.spinner' );
	};

	admin.init = function() {
		admin.cache();

		if ( $c.wrapper.length ) {
			$c.wrapper.on( 'click'   , '.create-merchant-profile', admin.create_merchant_profile );
		}

	};

	admin.create_merchant_profile = function() {
		var $this = $(this);

		var post_data = {
			action : 'wpsc_propay_create_merchant_profile_id',
			nonce  : wpsc.merchant_profile_nonce
		};

		var ajax_callback = function(response) {
			if (! response.is_successful) {
				$this.show().siblings('em').remove();
				return;
			}
			$this.siblings('em').addClass('sent').text(wpsc.sent_message);
			$this.remove();
		};

		$this.hide().after('<em>' + wpsc.sending_message + '</em>');
		$.wpsc_post(post_data, ajax_callback);

		return false;
	};

	$( wpsc.init );

} )( window, document, jQuery, window.WPSC_Purchase_Logs_Admin );
