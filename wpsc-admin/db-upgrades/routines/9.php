<?php

function _wpsc_db_upgrade_9() {
	_wpsc_maybe_create_purchase_meta_tables();
}

function _wpsc_maybe_create_purchase_meta_tables() {

	_wpsc_create_purchase_meta_table();
	_wpsc_meta_migrate_wpsc_purchase();
}