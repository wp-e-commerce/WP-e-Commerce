<?php

function _wpsc_db_upgrade_4() {
	_wpsc_maybe_update_submitted_form_data_value();
}

function _wpsc_maybe_update_submitted_form_data_value() {
	global $wpdb;
	$wpdb->query( 'ALTER TABLE ' . WPSC_TABLE_SUBMITTED_FORM_DATA . ' MODIFY columnname INTEGER;' );
}