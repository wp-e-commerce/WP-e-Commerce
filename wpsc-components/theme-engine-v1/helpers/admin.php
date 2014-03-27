<?php

add_action( 'wpsc_register_settings_tabs', '_wpsc_te_v1_register_settings_tabs', 10, 1 );
add_action( 'wpsc_load_settings_tab_class', '_wpsc_te_v1_load_settings_tab_class', 10, 1 );

function _wpsc_te_v1_register_settings_tabs( $page_instance ) {
	$page_instance->register_tab( 'presentation', _x( 'Presentation', 'Presentation settings tab in Settings->Store page', 'wpsc' ) );
}

function _wpsc_te_v1_load_settings_tab_class( $page_instance ) {
	$current_tab_id = $page_instance->get_current_tab_id();
	if ( in_array( $current_tab_id, array( 'presentation' ) ) )
		require_once( WPSC_THEME_ENGINE_V1_PATH . '/classes/settings-tab-presentation.php' );
}
