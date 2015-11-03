<?php
/**
 * Upgrade routines
 *
 * @since 3.8.9
 * @access private
 * @package wp-e-commerce
 */

/**
 * Execute upgrade routines if necessary
 *
 * @access private
 * @since 3.8.9
 */
function _wpsc_maybe_upgrade() {
	$current_db_ver = (int) get_option( 'wpsc_db_version', 0 );

	if ( ! _wpsc_needs_upgrade() )
		return;

	for ( $i = $current_db_ver + 1; $i <= WPSC_DB_VERSION; $i++ ) {
		$file_path = WPSC_FILE_PATH . '/wpsc-admin/db-upgrades/routines/' . $i . '.php';

		if ( file_exists( $file_path ) ) {
			require_once( $file_path );
		}

		if ( ! function_exists( '_wpsc_db_upgrade_' . $i ) ) {
			continue;
		}

		wpsc_core_flush_temporary_data();

		call_user_func( '_wpsc_db_upgrade_' . $i );
		update_option( 'wpsc_db_version', $i );
	}

	wpsc_core_flush_temporary_data();
}

function _wpsc_upgrade_display_backup_warning() {
	$message = __( '<strong>Important:</strong> Before proceeding with the database upgrade, <a href="%1$s">please backup your database and files</a>.<br>We recommend using <a href="%2$s">VaultPress</a> or <a href="%3$s">BackupBuddy</a> to regularly backup your WordPress installation.', 'wp-e-commerce' );
	$message = sprintf(
		$message,
		'http://codex.wordpress.org/WordPress_Backups',
		'http://vaultpress.com/',
		'http://ithemes.com/member/go.php?r=45982&i=l44'
	);
	?>
	<div id="wpsc-upgrade-warning" class="updated">
		<p><?php echo $message; ?></p>
	</div>
	<?php
}

function _wpsc_upgrade_display_prompt() {
	$message = __( '<strong>WP eCommerce %1$s is almost ready.</strong> Some database routines need to be run before the upgrade is complete. <a href="%2$s">Click here to start!</a>', 'wp-e-commerce' );
	$message = sprintf( $message, WPSC_VERSION, admin_url( '?page=wpsc-db-upgrade' ) );
	?>
	<div id="wpsc-upgrade-warning" class="error">
		<p><?php echo $message; ?></p>
	</div>
	<?php
}

function _wpsc_upgrade_display_successful() {
	$message = __( 'WP eCommerce has been successfully updated to %s. Enjoy!', 'wp-e-commerce' );
	$message = sprintf( $message, WPSC_VERSION );
	?>
		<div id="wpsc-upgrade-warning" class="updated">
			<p><?php echo $message; ?></p>
		</div>
	<?php
}

function _wpsc_action_admin_notices_db_upgrade() {
	if ( ! empty( $_GET['wpsc_db_upgrade_successful'] ) ) {
		_wpsc_upgrade_display_successful();
	} elseif ( _wpsc_is_db_upgrade_page() ) {
		_wpsc_upgrade_display_backup_warning();
	} elseif ( _wpsc_needs_upgrade() ) {
		_wpsc_upgrade_display_prompt();
	}
}
add_action( 'admin_notices', '_wpsc_action_admin_notices_db_upgrade' );

function _wpsc_needs_3dot7_db_upgrade() {
	global $wpdb;

	static $return = null;

	if ( is_null( $return ) ) {
		// in case this installation was first installed using 3.8.x, then return false
		if ( ! get_option( 'wpsc_needs_update', false ) ) {
			$return = false;
		} else {
			// in case this installation was first installed using 3.7.x, we need to check whether
			// the legacy database table exists, and if there are any products remain in that table
			$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '" . WPSC_TABLE_PRODUCT_LIST . "'" );
			$product_count = empty( $table_exists ) ? 0 : $wpdb->get_var( "SELECT COUNT(*) FROM " . WPSC_TABLE_PRODUCT_LIST );

			$return = $product_count > 0;
		}
	}

	return $return;
}

function _wpsc_needs_upgrade() {
	if ( ! current_user_can( 'update_plugins' ) )
		return false;

	$current_db_ver = get_option( 'wpsc_db_version', 0 );

	if ( WPSC_DB_VERSION <= $current_db_ver )
		return false;

	// if upgrading from 3.7.x, avoid displaying this notification until 3.7.x db has been successfully
	// migrated
	if ( _wpsc_needs_3dot7_db_upgrade() )
		return false;

	return true;
}

function _wpsc_is_db_upgrade_page() {
	$current_screen = get_current_screen();
	return ! empty( $current_screen->id ) && 'dashboard_page_wpsc-db-upgrade' == $current_screen->id;
}

function _wpsc_action_admin_menu_db_upgrade() {
	if ( _wpsc_needs_upgrade() ) {
		$page_hook = add_submenu_page( 'index.php', __( 'Database Upgrade', 'wp-e-commerce' ), __( 'Database Upgrade', 'wp-e-commerce' ), 'update_plugins', 'wpsc-db-upgrade', '_wpsc_callback_display_db_upgrade_page' );
		add_action( 'load-' . $page_hook, '_wpsc_action_load_db_upgrade' );
	}
}
add_action( 'admin_menu', '_wpsc_action_admin_menu_db_upgrade' );

function _wpsc_action_load_db_upgrade() {
	if ( empty( $_REQUEST['action'] ) )
		return;

	check_admin_referer( 'wpsc_db_upgrade' );

	_wpsc_maybe_upgrade();

	wp_redirect( esc_url_raw( add_query_arg( 'wpsc_db_upgrade_successful', 1, admin_url() ) ) );
	exit;
}

function _wpsc_callback_display_db_upgrade_page() {
	$update_title = sprintf( __( 'Your database needs to be upgraded before you can use WP eCommerce %s', 'wp-e-commerce' ), WPSC_VERSION );
	include( 'views/main.php' );
}
