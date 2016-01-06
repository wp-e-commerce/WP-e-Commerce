
/**
 * WP eCommerce Fancy Notifications JS
 */

var WPEC_Fancy_Notifications;

( function( $ ) {

	WPEC_Fancy_Notifications = {

		/**
		 * Move Fancy Notification element to end of HTML body.
		 */
		appendToBody : function() {

			$( '#fancy_notification' ).appendTo( 'body' );

		},

		/**
		 * Fancy Notification: Show
		 */
		wpscAddToCart : function( e ) {

			$( 'div.wpsc_loading_animation' ).css( 'visibility', 'hidden' );
			WPEC_Fancy_Notifications.fancy_notification( e.form );

		},

		/**
		 * Fancy Notification
		 *
		 * @param  object  parent_form  Form element.
		 */
		fancy_notification : function( parent_form ) {

			var fancyNotificationEl = $( '#fancy_notification' );

			if ( typeof( WPSC_SHOW_FANCY_NOTIFICATION ) == 'undefined' ) {
				WPSC_SHOW_FANCY_NOTIFICATION = true;
			}

			if ( ( WPSC_SHOW_FANCY_NOTIFICATION === true ) && ( fancyNotificationEl !== null ) ) {
				fancyNotificationEl.css( {
					display  : 'block',
					position : 'fixed',
					left     : ( $( window ).width() - fancyNotificationEl.outerWidth() ) / 2,
					top      : ( $( window ).height() - fancyNotificationEl.outerHeight() ) / 2
				} );
				$( '#loading_animation' ).css( 'display', 'block' );
				$( '#fancy_notification_content' ).css( 'display', 'none' );
			}

		},

		/**
		 * Fancy Notification: Hide
		 */
		wpscAddedToCart : function( e ) {

			if ( ( e.response ) ) {
				if ( e.response.hasOwnProperty( 'fancy_notification' ) && e.response.fancy_notification ) {
					if ( $( '#fancy_notification_content' ) ) {
						$( '#fancy_notification_content' ).html( e.response.fancy_notification );
						$( '#loading_animation').css( 'display', 'none' );
						$( '#fancy_notification_content' ).css( 'display', 'block' );
					}
				}
				$( document ).trigger( { type : 'wpsc_fancy_notification', response : e.response } );
			}

			if ( $( '#fancy_notification' ).length > 0 ) {
				$( '#loading_animation' ).css( 'display', 'none' );
			}

		}

	};

	/**
	 * Event Handlers
	 */
	$( document ).on( 'ready', WPEC_Fancy_Notifications.appendToBody );
	$( document ).on( 'wpscAddToCart', WPEC_Fancy_Notifications.wpscAddToCart );
	$( document ).on( 'wpscAddedToCart', WPEC_Fancy_Notifications.wpscAddedToCart );

} ) ( jQuery );
