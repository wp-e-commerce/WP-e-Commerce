window.WPSC_Purchase_Logs_Admin = window.WPSC_Purchase_Logs_Admin || {};

( function( window, document, $, wpsc, undefined ) {
	'use strict';

	var ENTER = 13;
	var BR = "\n";
	var $c = {};

	var admin = {
		blur_timeout : null,
		reset_textbox_width : true,
		$ : $c
	};

	admin.cache = function() {
		$c.wrapper = $('table.purchase-logs');
	};

	admin.init = function() {
		admin.cache();

		if ( $c.wrapper.length ) {
			$c.wrapper.on( 'change'   , '.wpsc-purchase-log-status'     , admin.event_log_status_change );
			$c.wrapper.on( 'focus'    , '.wpsc-purchase-log-tracking-id', admin.event_tracking_id_focused );
			$c.wrapper.on( 'click'    , '.column-tracking a.add'        , admin.event_button_add_clicked );
			$c.wrapper.on( 'blur'     , '.wpsc-purchase-log-tracking-id', admin.event_tracking_id_blurred );
			$c.wrapper.on( 'click'    , '.column-tracking a.save'       , admin.event_button_save_clicked );
			$c.wrapper.on( 'click'    , '.column-tracking .send-email a', admin.event_button_send_email_clicked );
			$c.wrapper.on( 'keypress' , '.wpsc-purchase-log-tracking-id', admin.event_enter_key_pressed );
			$c.wrapper.on( 'mousedown', '.column-tracking a.save'       , admin.event_disable_textbox_resize );
			$c.wrapper.on( 'focus'    , '.column-tracking a.save'       , admin.event_disable_textbox_resize );
		}
	};

	admin.event_enter_key_pressed = function(evt) {
		var code = evt.keyCode ? evt.keyCode : evt.which;
		if ( ENTER === code ) {
			$(this).siblings('.save').click();
			evt.preventDefault();
		}
	};

	admin.event_button_send_email_clicked = function() {
		var $this = $(this);

		var post_data = {
			'action' : 'purchase_log_send_tracking_email',
			'log_id' : $this.closest('div').data('log-id'),
			'nonce'  : wpsc.purchase_log_send_tracking_email_nonce
		};

		var ajax_callback = function(response) {
			if (! response.is_successful) {
				window.alert(response.error.messages.join(BR));
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

	admin.event_button_save_clicked = function() {
		var $this = $(this);
		var $textbox = $this.siblings('.wpsc-purchase-log-tracking-id');
		var $spinner = $this.siblings('.ajax-feedback');

		var post_data = {
			'action' : 'purchase_log_save_tracking_id',
			'value'  : $textbox.val(),
			'log_id' : $this.parent().data('log-id'),
			'nonce'  : wpsc.purchase_log_save_tracking_id_nonce
		};

		var ajax_callback = function(response) {
			$spinner.toggleClass('ajax-feedback-active');
			$textbox.blur();
			if (! response.is_successful) {
				window.alert(response.error.messages.join(BR));
				return;
			}
			$this.parent().removeClass('empty');
			admin.reset_tracking_id_width($this.siblings('.wpsc-purchase-log-tracking-id'));
		};

		$this.hide();
		$spinner.toggleClass('ajax-feedback-active');
		$textbox.width(160);

		$.wpsc_post(post_data, ajax_callback);

		return false;
	};

	admin.event_disable_textbox_resize = function() {
		admin.reset_textbox_width = false;
	};

	admin.event_button_add_clicked = function() {
		$(this).siblings('.wpsc-purchase-log-tracking-id').trigger('focus');
		return false;
	};

	admin.reset_tracking_id_width = function($obj) {
		var reset_width = function() {
			if (admin.reset_textbox_width) {
				$obj.siblings('a.save').hide();
				$obj.width('');
				if ($obj.val() === '') {
					$obj.siblings('.add').show();
				}
			}

			admin.reset_textbox_width = true;
		};

		admin.blur_timeout = setTimeout(reset_width, 100);
	};

	admin.event_tracking_id_blurred = function() {
		admin.reset_tracking_id_width( $(this) );
	};

	admin.event_tracking_id_focused = function() {
		var $this = $(this);
		$this.width(128);
		$this.siblings('a.save').show();
		$this.siblings('a.add').hide();
	};

	admin.event_log_status_change = function() {
		var $this = $(this);
		var post_data = {
			nonce      : wpsc.change_purchase_log_status_nonce,
			action     : 'change_purchase_log_status',
			id         : $this.data('log-id'),
			new_status : $this.val(),
			m          : wpsc.current_filter,
			status     : wpsc.current_view,
			paged      : wpsc.current_page,
			_wp_http_referer : window.location.href
		};
		var spinner = $this.siblings('.ajax-feedback');
		spinner.addClass('ajax-feedback-active');
		var ajax_callback = function(response) {
			if (! response.is_successful) {
				window.alert(response.error.messages.join(BR));
				return;
			}
			spinner.removeClass('ajax-feedback-active');
			$('ul.subsubsub').replaceWith(response.obj.views);
			$('.tablenav.top').replaceWith(response.obj.tablenav_top);
			$('.tablenav.bottom').replaceWith(response.obj.tablenav_bottom);
		};

		$.wpsc_post(post_data, ajax_callback);
	};

	$.extend( wpsc, admin );

	$( wpsc.init );

} )( window, document, jQuery, window.WPSC_Purchase_Logs_Admin );
