/* global WPSC_Purchase_Log_Action_Links, alert */
( function( $ ) {

	/**
	 * Purchase Log Action Links
	 */
	$.extend( WPSC_Purchase_Log_Action_Links, {

		/**
		 * Setup purchase log action links and event handlers.
		 *
		 * @since  3.9.0
		 */
		init : function() {

			$( function() {
				var wrapper = $( '#wpsc_purchlogitems_links ul' );

				// Add spinners to AJAX links
				wrapper.find( 'a.wpsc-purchlog-action-link.is-ajax' ).each( function() {
					$( this ).prepend( $( '<span class="spinner is-active" />' ) );
				} );

				wrapper.on( 'click', 'a.wpsc-purchlog-action-link.is-ajax', WPSC_Purchase_Log_Action_Links.event_ajax_link_clicked );
			} );

		},

		/**
		 * Handle purchase log action link click.
		 *
		 * @since  3.9.0
		 */
		event_ajax_link_clicked : function( e ) {

			var action = $( this ).data( 'purchase-log-action' );
			var nonce = $( this ).data( 'nonce' );

			if ( action ) {

				// Only do AJAX request if not already doing it.
				if ( ! $( this ).hasClass( 'doing' ) ) {

					var post_data = {
						'action'                    : 'purchase_log_action_link',
						'purchase_log_action_link'  : action,
						'purchase_log_action_nonce' : nonce,
						'log_id'                    : WPSC_Purchase_Log_Action_Links.log_id,
						'nonce'                     : WPSC_Purchase_Log_Action_Links.purchase_log_action_link_nonce
					};

					$( this ).addClass( 'doing' );
					$.wpsc_post( post_data, WPSC_Purchase_Log_Action_Links.ajax_callback );

				}

				e.preventDefault();

			}

		},

		/**
		 * Handle purchase log action AJAX response.
		 *
		 * @since  3.9.0
		 */
		ajax_callback : function( response ) {
			var dashicon, dashicon_class;

			// If AJAX successful and purchase log action successful.
			if ( response.is_successful && response.obj.success ) {

				dashicon       = $( '#wpsc_purchlogitems_links ul a.wpsc-purchlog-action-link-' + response.obj.purchase_log_action_link + ' .dashicons' );
				dashicon_class = dashicon.attr( 'class' );

				// Successful notification.
				dashicon.removeClass().addClass( 'dashicons dashicons-yes' );
				setTimeout( function() {
					dashicon.removeClass().addClass( dashicon_class );
				}, 3000 );

				// Remove spinner
				$( '#wpsc_purchlogitems_links ul a.wpsc-purchlog-action-link.doing' ).removeClass( 'doing' );

			} else {

				// Ideally we'd always like to know which link was clicked, but we don't
				// so just clear all spinners and only clear specific spinner if AJAX response was processed.

				dashicon = $( '#wpsc_purchlogitems_links ul a.wpsc-purchlog-action-link.doing .dashicons' );

				if ( response.is_successful ) {
					dashicon = $( '#wpsc_purchlogitems_links ul a.wpsc-purchlog-action-link-' + response.obj.purchase_log_action_link + ' .dashicons' );
				}
				dashicon_class = dashicon.attr( 'class' );

				if ( response.obj.success != null ) {

					// Failure notification.
					dashicon.removeClass().addClass( 'dashicons dashicons-no' );
					setTimeout( function() {
						dashicon.removeClass().addClass( dashicon_class );
					}, 3000 );

				} else {
					dashicon.removeClass().addClass( dashicon_class );
				}

				// Remove spinner.
				$( '#wpsc_purchlogitems_links ul a.wpsc-purchlog-action-link.doing' ).removeClass( 'doing' );

				// Show error message.
				if ( typeof response.error !== 'undefined' ) {
					alert( response.error.messages.join( "\n" ) );
				}

			}

		}

	} );

} )( jQuery );

WPSC_Purchase_Log_Action_Links.init();
