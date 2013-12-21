<?php

/**
 * @title TinyMCE V3 Button Integration (for Wp2.5)
 */

function wpsc_addbuttons() {

	// Don't bother doing this stuff if the current user lacks permissions
// 	if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') ) return;
//
// 	// Check for NextGEN capability
// 	if ( !current_user_can('NextGEN Use TinyMCE') ) return;

	// Add only in Rich Editor mode
	if ( get_user_option('rich_editing') == 'true') {

	// add the button for wp25 in a new way
		add_filter("mce_external_plugins", "add_wpsc_tinymce_plugin", 5);
		add_filter('mce_buttons', 'register_wpsc_button', 5);
	}
}

// used to insert button in wordpress 2.5x editor
function register_wpsc_button($buttons) {

	array_push($buttons, "separator", "WPSC");

	return $buttons;
}

// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
function add_wpsc_tinymce_plugin($plugin_array) {

	$plugin_array['WPSC'] =                        WPSC_CORE_JS_URL . '/tinymce3/editor_plugin.js';
	$plugin_array['productspage_image'] =          WPSC_CORE_JS_URL . '/tinymce3/editor_plugin.js';
	$plugin_array['transactionresultpage_image'] = WPSC_CORE_JS_URL . '/tinymce3/editor_plugin.js';
	$plugin_array['checkoutpage_image'] =          WPSC_CORE_JS_URL . '/tinymce3/editor_plugin.js';
	$plugin_array['userlogpage_image'] =           WPSC_CORE_JS_URL . '/tinymce3/editor_plugin.js';
	return $plugin_array;
}

function wpsc_change_tinymce_version($version) {
	return ++$version;
}

// Modify the version when tinyMCE plugins are changed.
add_filter('tiny_mce_version', 'wpsc_change_tinymce_version');

// init process for button control
add_action('init', 'wpsc_addbuttons');

?>