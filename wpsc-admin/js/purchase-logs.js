(function($){
	$.extend(WPSC_Purchase_Logs_Admin, {
		blur_timeout : null,
		reset_textbox_width : true,

		init : function() {
			$(function(){
				$('table.purchase-logs').delegate('.wpsc-purchase-log-status', 'change', WPSC_Purchase_Logs_Admin.event_log_status_change).
				                         delegate('.wpsc-purchase-log-tracking-id', 'focus', WPSC_Purchase_Logs_Admin.event_tracking_id_focused).
				                         delegate('.column-tracking a.add', 'click', WPSC_Purchase_Logs_Admin.event_button_add_clicked).
				                         delegate('.wpsc-purchase-log-tracking-id', 'blur', WPSC_Purchase_Logs_Admin.event_tracking_id_blurred).
				                         delegate('.column-tracking a.save', 'click', WPSC_Purchase_Logs_Admin.event_button_save_clicked).
				                         delegate('.column-tracking .send-email a', 'click', WPSC_Purchase_Logs_Admin.event_button_send_email_clicked).
				                         delegate('.wpsc-purchase-log-tracking-id', 'keypress', WPSC_Purchase_Logs_Admin.event_enter_key_pressed).
				                         delegate('.column-tracking a.save', 'mousedown', WPSC_Purchase_Logs_Admin.event_disable_textbox_resize).
				                         delegate('.column-tracking a.save', 'focus', WPSC_Purchase_Logs_Admin.event_disable_textbox_resize);

			});
		},

		event_enter_key_pressed : function(e) {
			var code = e.keyCode ? e.keyCode : e.which;
			if (code == 13) {
				$(this).siblings('.save').click();
				e.preventDefault();
			}
		},

		event_button_send_email_clicked : function() {
			var t = $(this);

			var post_data = {
				'action' : 'purchase_log_send_tracking_email',
				'log_id' : t.closest('div').data('log-id'),
				'nonce'  : WPSC_Purchase_Logs_Admin.purchase_log_send_tracking_email_nonce
			};

			var ajax_callback = function(response) {
				if (! response.is_successful) {
					alert(response.error.messages.join("\n"));
					t.show().siblings('em').remove();
					return;
				}
				t.siblings('em').addClass('sent').text(WPSC_Purchase_Logs_Admin.sent_message);
				t.remove();
			};

			t.hide().after('<em>' + WPSC_Purchase_Logs_Admin.sending_message + '</em>');
			$.wpsc_post(post_data, ajax_callback);

			return false;
		},

		event_button_save_clicked : function() {
			var t = $(this), textbox = t.siblings('.wpsc-purchase-log-tracking-id'), spinner = t.siblings('.ajax-feedback');

			var post_data = {
				'action' : 'purchase_log_save_tracking_id',
				'value'  : textbox.val(),
				'log_id' : t.parent().data('log-id'),
				'nonce'  : WPSC_Purchase_Logs_Admin.purchase_log_save_tracking_id_nonce
			};

			var ajax_callback = function(response) {
				spinner.toggleClass('ajax-feedback-active');
				textbox.blur();
				if (! response.is_successful) {
					alert(response.error.messages.join("\n"));
					return;
				}
				t.parent().removeClass('empty');
				WPSC_Purchase_Logs_Admin.reset_tracking_id_width(t.siblings('.wpsc-purchase-log-tracking-id'));
			};

			t.hide();
			spinner.toggleClass('ajax-feedback-active');
			textbox.width(160);

			$.wpsc_post(post_data, ajax_callback);

			return false;
		},

		event_disable_textbox_resize : function() {
			WPSC_Purchase_Logs_Admin.reset_textbox_width = false;
		},

		event_button_add_clicked : function() {
			$(this).siblings('.wpsc-purchase-log-tracking-id').trigger('focus');
			return false;
		},

		reset_tracking_id_width : function(t) {
			var reset_width = function() {
				if (WPSC_Purchase_Logs_Admin.reset_textbox_width) {
					t.siblings('a.save').hide();
					t.width('');
					if (t.val() === '') {
						t.siblings('.add').show();
					}
				}

				WPSC_Purchase_Logs_Admin.reset_textbox_width = true;
			};

			WPSC_Purchase_Logs_Admin.blur_timeout = setTimeout(reset_width, 100);
		},

		event_tracking_id_blurred : function() {
			var t = $(this);

			WPSC_Purchase_Logs_Admin.reset_tracking_id_width(t);
		},

		event_tracking_id_focused : function() {
			var t = $(this);
			t.width(128);
			t.siblings('a.save').show();
			t.siblings('a.add').hide();
		},

		event_log_status_change : function() {
			var post_data = {
					nonce      : WPSC_Purchase_Logs_Admin.change_purchase_log_status_nonce,
					action     : 'change_purchase_log_status',
					id         : $(this).data('log-id'),
					new_status : $(this).val(),
					m          : WPSC_Purchase_Logs_Admin.current_filter,
					status     : WPSC_Purchase_Logs_Admin.current_view,
					paged      : WPSC_Purchase_Logs_Admin.current_page,
					_wp_http_referer : window.location.href
				},
				spinner = $(this).siblings('.ajax-feedback'),
				t = $(this);
			spinner.addClass('ajax-feedback-active');
			var ajax_callback = function(response) {
				if (! response.is_successful) {
					alert(response.error.messages.join("\n"));
					return;
				}
				spinner.removeClass('ajax-feedback-active');
				$('ul.subsubsub').replaceWith(response.obj.views);
				$('.tablenav.top').replaceWith(response.obj.tablenav_top);
				$('.tablenav.bottom').replaceWith(response.obj.tablenav_bottom);
			};

			$.wpsc_post(post_data, ajax_callback);
		}
	});

})(jQuery);

WPSC_Purchase_Logs_Admin.init();