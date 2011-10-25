(function($){
	$.extend(WPSC_Purchase_Logs_Admin, {
		init : function() {
			$(function(){
				$('table.purchase-logs').delegate('.wpsc-purchase-log-status', 'change', WPSC_Purchase_Logs_Admin.event_log_status_change);
			});
		},
		event_log_status_change : function() {
			var post_data = {
					nonce : WPSC_Purchase_Logs_Admin.nonce,
					action : 'wpsc_change_purchase_log_status',
					id : $(this).data('log-id'),
					new_status : $(this).val()
				},
				spinner = $(this).siblings('.ajax-feedback'),
				t = $(this);
			spinner.addClass('ajax-feedback-active');
			var ajax_callback = function(response) {
				spinner.removeClass('ajax-feedback-active');
				if (response != 'success') {
					alert(WPSC_Purchase_Logs_Admin.status_error_dialog);
				}
			};

			$.post(ajaxurl, post_data, ajax_callback);
		}
	});

})(jQuery);

WPSC_Purchase_Logs_Admin.init();