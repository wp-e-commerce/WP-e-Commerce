<?php

function _wpsc_db_upgrade_6() {
	_wpsc_maybe_create_meta_tables();
}

function _wpsc_maybe_create_meta_tables() {

	_wpsc_create_cart_item_meta_table();
	_wpsc_meta_migrate_wpsc_cart_item();
}