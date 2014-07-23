<?php

/**
 *  Show WPeC admin notifications, a.k.a. Nags
 *
 * an admin notifications class is defined here in core includes so that notifications
 * can be queued by logic running on the user facing side that will be visisble to
 * store administrators when they are on the admin pages
 *
 * @access public
 *
 * @since 3.8.14.1
 *
 */
class WPSC_Admin_Notifications {

	static $instance = null;

	function __construct() {

		if ( ! self::$instance ) {
			self::$instance = $this;
			if ( is_admin() ) {
				add_action( 'admin_notices', array( $this, 'show_messages' ) );
				add_action( 'wp_ajax_wpsc_dismiss_admin_msg',  array( $this, 'dismiss_msg' ) );
			}
		}
	}

	/**
	 * add one or more new messages to be shown to the administrator
	 * @param string|array[string] $new_messages to show to the admin
	 */
	function new_message( $new_messages ) {

		if ( empty ( $new_messages ) )
			return;

		if ( is_string( $new_messages ) ) {
			$new_messages = array( $new_messages );
		}

		$messages = get_option( __CLASS__ , array() );
		$save_admin_messages = false;

		foreach ( $new_messages as $new_message ) {
			// using the hash key is an easy way of preventing duplicate messages
			$id = md5( $new_message );

			if ( ! isset($messages[$id] ) ) {
				$messages[$id] = $new_message;
				$save_admin_messages = true;
			}
		}

		// only save the admin messages if they have been updated
		if ( $save_admin_messages ) {
			update_option( __CLASS__ , $messages );
		}

		if ( did_action( 'admin_notices' ) ) {
			$wpsc_admin_notifications = new WPSC_Admin_Notifications();
			$wpsc_admin_notifications->show_messages();
		}
	}

	/**
	 * display admin messages(nags)
	 *
	 * @since 3.8.14.1
	 */
	function show_messages() {
		$messages = get_option( __CLASS__ , array() );

		static $script_already_sent = false;
		static $already_displayed   = array();

		// first time though this function and we should add the admin nag script to the page
		if ( ! $script_already_sent  ) {
			?>
				<script type="text/javascript">
				// WPeC Admin nag handler
				jQuery(document).ready(function ($) {
					function wpsc_dismiss_admin_msg(id) {
						jQuery( "#wpsc-admin-message-"+id ).hide();
						jQuery.ajax({
							type : "post",
							dataType : "text",
							url : "<?php echo admin_url( 'admin-ajax.php' );?>",
							data : {action: "wpsc_dismiss_admin_msg", id : id},
							success: function (response) {
							},
							error: function (response) {
								;
							},
						});
					}
					jQuery(".wpsc-admin-message-dismiss").click(function(event) {
						wpsc_dismiss_admin_msg(event.target.id);
						return false;
					});
				});
			</script>
			<?php
			$script_already_sent = true;
		}

		foreach ( $messages as $id => $message ) {
			if ( in_array( $id, $already_displayed ) )
				continue;

			$already_displayed[] = $id;


			?>
			<div class="updated wpsc-admin-message" id="wpsc-admin-message-<?php echo esc_attr( $id );?>">
				<div class="message-text">
					<p>
						<?php echo $message; ?>
					</p>
				</div>
				<div class="wpsc-admin-message-action" style="width: 100%; text-align: right;">
					<a class="wpsc-admin-message-dismiss" id="<?php echo esc_attr( $id );?>"><?php _e( 'Dismiss', 'wpec' )?></a>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Dismiss an admin message
	 *
	 * @param string $message_id  the unqiue message id to be dismissed
	 */
	function dismiss_msg( $message_id = false ) {
		if ( ! $message_id ) {
			if ( isset( $_REQUEST['id'] ) ) {
				$message_id = $_REQUEST['id'];
			}
		}

		$messages = get_option( __CLASS__ , array() );

		if ( isset($messages[$message_id] ) ) {
			unset( $messages[$message_id] );
			update_option( __CLASS__, $messages );
		}

		wp_send_json_success( true );
	}
}

/**
 * Show one or more admin notification(s) that must be acknowledged
 *
 * @param string|array[string] $messages admin the messages(nags) to show
 */
function wpsc_admin_nag( $messages ) {
	static $wpsc_admin_notifications = null;

	if ( empty( $wpsc_admin_notifications ) ) {
		$wpsc_admin_notifications = new WPSC_Admin_Notifications();
	}

	$wpsc_admin_notifications->new_message( $messages );
}



// If we are showing an admin page we want to show the admin nags
if ( is_admin() ) {
	$wpsc_admin_notifications = new WPSC_Admin_Notifications();
}

