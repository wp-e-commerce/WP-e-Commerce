<?php
/*
 * Display Settings page
 */

function wpsc_display_settings_page() {
	WPSC_Settings_Page::get_instance()->display();
}

/*
 * Create settings page tabs
 */

function wpsc_settings_tabs() {
	return WPSC_Settings_Page::get_instance()->get_tabs();
}

/*
 * Display settings tabs
 */

function wpsc_the_settings_tabs() {
	WPSC_Settings_Page::get_instance()->output_tabs();
}

function wpsc_settings_page_update_notification() {

	if ( isset( $_GET['skipped'] ) || isset( $_GET['updated'] ) || isset( $_GET['regenerate'] ) || isset( $_GET['deleted'] ) || isset( $_GET['shipadd'] ) ) { ?>

	<div id="message" class="updated fade"><p>
		<?php

		if ( isset( $_GET['updated'] ) && (int)$_GET['updated'] ) {
			printf( _n( '%s Setting options updated.', ' %s Settings options updated.', $_GET['updated'], 'wpsc' ), absint( $_GET['updated'] ) );
			unset( $_GET['updated'] );
			$message = true;
		}
		if ( isset( $_GET['deleted'] ) && (int)$_GET['deleted'] ) {
			printf( _n( '%s Setting option deleted.', '%s Setting option deleted.', $_GET['deleted'], 'wpsc' ), absint( $_GET['deleted'] ) );
			unset( $_GET['deleted'] );
			$message = true;
		}
		if ( isset( $_GET['shipadd'] ) && (int)$_GET['shipadd'] ) {
			printf( _n( '%s Shipping option updated.', '%s Shipping option updated.', $_GET['shipadd'], 'wpsc' ), absint( $_GET['shipadd'] ) );
			unset( $_GET['shipadd'] );
			$message = true;
		}
		if ( isset( $_GET['added'] ) && (int)$_GET['added'] ) {
			printf( _n( '%s Checkout field added.', '%s Checkout fields added.', $_GET['added'], 'wpsc' ), absint( $_GET['added'] ) );
			unset( $_GET['added'] );
			$message = true;
		}

		if ( ! isset( $message ) )
			_e( 'Settings successfully updated.', 'wpsc' );

		$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'locked', 'regenerate', 'skipped', 'updated', 'deleted', 'wpsc_downloadcsv', 'rss_key', 'start_timestamp', 'end_timestamp', 'email_buyer_id' ), $_SERVER['REQUEST_URI'] ); ?>
	</p></div>

<?php
	}
}

?>