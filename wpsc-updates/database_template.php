<?php
/**
 * WP eCommerce Database template
 *
 * This is the WPSC database template it is a multidimensional associative array used to create and update the database tables.
 * @package wp-e-commerce
 * @subpackage wpsc-updating-code
 */

// code to create or update the {$wpdb->prefix}wpsc_also_bought table
$table_name = WPSC_TABLE_ALSO_BOUGHT; /* !wpsc_also_bought */
$wpsc_database_template[$table_name]['columns']['id'] = "bigint(20) unsigned NOT NULL auto_increment";
$wpsc_database_template[$table_name]['columns']['selected_product'] = "bigint(20) unsigned NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['associated_product'] = "bigint(20) unsigned NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['quantity'] = "int(10) unsigned NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['indexes']['PRIMARY'] = "PRIMARY KEY  ( `id` )";
$wpsc_database_template[$table_name]['previous_names'] = "{$wpdb->prefix}also_bought_product";


// code to create or update the {$wpdb->prefix}wpsc_cart_contents table
$table_name = WPSC_TABLE_CART_CONTENTS; /* !wpsc_cart_contents */
$wpsc_database_template[$table_name]['columns']['id'] = "bigint(20) unsigned NOT NULL auto_increment";
$wpsc_database_template[$table_name]['columns']['prodid'] = "bigint(20) unsigned NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['name'] = "varchar(255) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['purchaseid'] = "bigint(20) unsigned NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['price'] = "decimal(11,2) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['pnp'] = "decimal(11,2) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['tax_charged'] = "decimal(11,2) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['gst'] = "decimal(11,2) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['quantity'] = "int(10) unsigned NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['donation'] = "varchar(1) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['no_shipping'] = "varchar(1) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['custom_message'] = "text NOT NULL ";
$wpsc_database_template[$table_name]['columns']['files'] = "text NOT NULL ";
$wpsc_database_template[$table_name]['columns']['meta'] = "longtext NULL ";
$wpsc_database_template[$table_name]['indexes']['PRIMARY'] = "PRIMARY KEY ( `id` )";
$wpsc_database_template[$table_name]['previous_names'] = "{$wpdb->prefix}cart_contents";


// code to create or update the {$wpdb->prefix}wpsc_meta table

$table_name = WPSC_TABLE_META; /* !wpsc_meta */
$wpsc_database_template[$table_name]['columns']['meta_id'] = "bigint(20) NOT NULL auto_increment";
$wpsc_database_template[$table_name]['columns']['object_type'] = "varchar(24) NOT NULL default 'cart_Item'";
$wpsc_database_template[$table_name]['columns']['object_id'] = "bigint(20) NOT NULL default '0'";
$wpsc_database_template[$table_name]['columns']['meta_key'] = "varchar(255) default NULL";
$wpsc_database_template[$table_name]['columns']['meta_value'] = "longtext";
$wpsc_database_template[$table_name]['indexes']['PRIMARY'] = "PRIMARY KEY  (`meta_id`)";
$wpsc_database_template[$table_name]['indexes']['object_type__meta_key'] = "KEY `object_type__meta_key` (`object_type`,`meta_key`)";
$wpsc_database_template[$table_name]['indexes']['object_type__object_id__meta_key'] = "KEY `object_type__object_id__meta_key` (`object_type`,`object_id`,`meta_key`)";


// code to create or update the {$wpdb->prefix}wpsc_checkout_forms table
$table_name = WPSC_TABLE_CHECKOUT_FORMS; /* !wpsc_checkout_forms */
$wpsc_database_template[$table_name]['columns']['id'] = "bigint(20) unsigned NOT NULL auto_increment";
$wpsc_database_template[$table_name]['columns']['name'] = "text NOT NULL";
$wpsc_database_template[$table_name]['columns']['type'] = "varchar(64) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['mandatory'] = "varchar(1) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['display_log'] = "char(1) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['default'] = "varchar(128) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['active'] = "varchar(1) NOT NULL DEFAULT '1' ";
$wpsc_database_template[$table_name]['columns']['checkout_order'] = "int(10) unsigned NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['unique_name'] = "varchar(255) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['options'] = "longtext ";
$wpsc_database_template[$table_name]['columns']['checkout_set'] = "VARCHAR( 64 ) NOT NULL DEFAULT '0'";

$wpsc_database_template[$table_name]['indexes']['PRIMARY'] = "PRIMARY KEY  ( `id` )";
$wpsc_database_template[$table_name]['indexes']['checkout_order'] = " KEY `checkout_order` ( `checkout_order` )";
$wpsc_database_template[$table_name]['actions']['before']['all'] = "wpsc_rename_checkout_column";
$wpsc_database_template[$table_name]['actions']['after']['all'] = "wpsc_add_checkout_fields";
$wpsc_database_template[$table_name]['previous_names'] = "{$wpdb->prefix}collect_data_forms";


// code to create or update the {$wpdb->prefix}wpsc_currency_list table
$table_name = WPSC_TABLE_CURRENCY_LIST; /* !wpsc_currency_list */
$wpsc_database_template[$table_name]['columns']['id'] = "bigint(20) unsigned NOT NULL auto_increment";
$wpsc_database_template[$table_name]['columns']['country'] = "varchar(255) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['isocode'] = "char(2) NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['currency'] = "varchar(255) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['symbol'] = "varchar(10) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['symbol_html'] = "varchar(10) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['code'] = "char(3) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['has_regions'] = "char(1) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['tax'] = "varchar(8) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['continent'] = "varchar(20) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['visible'] = "varchar(1) NOT NULL DEFAULT '1' ";
$wpsc_database_template[$table_name]['indexes']['PRIMARY'] = "PRIMARY KEY  ( `id` )";
$wpsc_database_template[$table_name]['actions']['after']['all'] = "wpsc_add_currency_list";
$wpsc_database_template[$table_name]['previous_names'] = "{$wpdb->prefix}currency_list";



// code to create or update the {$wpdb->prefix}wpsc_download_status table
$table_name = WPSC_TABLE_DOWNLOAD_STATUS;
$wpsc_database_template[$table_name]['columns']['id'] = "bigint(20) unsigned NOT NULL auto_increment";
$wpsc_database_template[$table_name]['columns']['product_id'] = "bigint(20) unsigned NULL";
$wpsc_database_template[$table_name]['columns']['fileid'] = "bigint(20) unsigned NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['purchid'] = "bigint(20) unsigned NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['cartid'] = "bigint(20) unsigned NULL";
$wpsc_database_template[$table_name]['columns']['uniqueid'] = "varchar(64) NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['downloads'] = "int(11) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['ip_number'] = "varchar(255) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['active'] = "varchar(1) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['datetime'] = "datetime NOT NULL";
$wpsc_database_template[$table_name]['indexes']['PRIMARY'] = "PRIMARY KEY  ( `id` )";
$wpsc_database_template[$table_name]['indexes']['product_id'] = " KEY `product_id` ( `product_id` )";
$wpsc_database_template[$table_name]['indexes']['uniqueid'] = "UNIQUE KEY `uniqueid` ( `uniqueid` )";
$wpsc_database_template[$table_name]['previous_names'] = "{$wpdb->prefix}download_status";

// code to create or update the {$wpdb->prefix}wpsc_product_rating table
$table_name = WPSC_TABLE_PRODUCT_RATING; /* !wpsc_product_rating */
$wpsc_database_template[$table_name]['columns']['id'] = "bigint(20) unsigned NOT NULL auto_increment";
$wpsc_database_template[$table_name]['columns']['ipnum'] = "varchar(30) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['productid'] = "bigint(20) unsigned NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['rated'] = "tinyint(1) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['time'] = "bigint(20) unsigned NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['indexes']['PRIMARY'] = "PRIMARY KEY  ( `id` )";
$wpsc_database_template[$table_name]['previous_names'] = "{$wpdb->prefix}product_rating";

// code to create or update the {$wpdb->prefix}wpsc_purchase_logs table
$table_name = WPSC_TABLE_PURCHASE_LOGS;  /* !wpsc_purchase_logs */
$wpsc_database_template[$table_name]['columns']['id'] = "bigint(20) unsigned NOT NULL auto_increment";
$wpsc_database_template[$table_name]['columns']['totalprice'] = "decimal(11,2) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['statusno'] = "smallint(6) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['sessionid'] = "varchar(255) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['transactid'] = "varchar(255) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['authcode'] = "varchar(255) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['processed'] = "bigint(20) unsigned NOT NULL DEFAULT '1' ";
$wpsc_database_template[$table_name]['columns']['user_ID'] = "bigint(20) unsigned NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['date'] = "varchar(255) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['gateway'] = "varchar(64) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['billing_country'] = "char(6) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['shipping_country'] = "char(6) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['base_shipping'] = "decimal(11,2) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['email_sent'] = "char(1) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['stock_adjusted'] = "char(1) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['discount_value'] = "decimal(11,2) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['discount_data'] = "text NULL";
$wpsc_database_template[$table_name]['columns']['track_id'] = "varchar(50) NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['billing_region'] = "char(6) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['shipping_region'] = "char(6) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['find_us'] = "varchar(255) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['engravetext'] = "varchar(255) NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['shipping_method'] = "VARCHAR(64) NULL ";
$wpsc_database_template[$table_name]['columns']['shipping_option'] = "VARCHAR(128) NULL ";
$wpsc_database_template[$table_name]['columns']['affiliate_id'] = "VARCHAR(32) NULL ";
$wpsc_database_template[$table_name]['columns']['plugin_version'] = "VARCHAR(32) NULL ";
$wpsc_database_template[$table_name]['columns']['notes'] = "text NULL";
$wpsc_database_template[$table_name]['columns']['wpec_taxes_total'] = "decimal(11,2)";
$wpsc_database_template[$table_name]['columns']['wpec_taxes_rate'] = "decimal(11,2)";
$wpsc_database_template[$table_name]['indexes']['PRIMARY'] = "PRIMARY KEY  ( `id` )";
$wpsc_database_template[$table_name]['indexes']['sessionid'] = "UNIQUE KEY `sessionid` ( `sessionid` )";
$wpsc_database_template[$table_name]['indexes']['gateway'] = " KEY `gateway` ( `gateway` )";
$wpsc_database_template[$table_name]['previous_names'] = "{$wpdb->prefix}purchase_logs";


// code to create or update the {$wpdb->prefix}wpsc_region_tax table
$table_name = WPSC_TABLE_REGION_TAX;  /* !wpsc_region_tax */
$wpsc_database_template[$table_name]['columns']['id'] = "bigint(20) unsigned NOT NULL auto_increment";
$wpsc_database_template[$table_name]['columns']['country_id'] = "bigint(20) unsigned NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['name'] = "varchar(64) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['code'] = "char(2) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['tax'] = "float NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['indexes']['PRIMARY'] = "PRIMARY KEY  ( `id` )";
$wpsc_database_template[$table_name]['indexes']['country_id'] = " KEY `country_id` ( `country_id` )";
$wpsc_database_template[$table_name]['actions']['after']['all'] = "wpsc_add_region_list";
$wpsc_database_template[$table_name]['previous_names'] = "{$wpdb->prefix}region_tax";


// code to create or update the {$wpdb->prefix}wpsc_submited_form_data table
$table_name = WPSC_TABLE_SUBMITTED_FORM_DATA; /* !wpsc_submitted_form_data */
$wpsc_database_template[$table_name]['columns']['id'] = "bigint(20) unsigned NOT NULL auto_increment";
$wpsc_database_template[$table_name]['columns']['log_id'] = "bigint(20) unsigned NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['form_id'] = "bigint(20) unsigned NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['value'] = "longtext ";
$wpsc_database_template[$table_name]['indexes']['PRIMARY'] = "PRIMARY KEY  ( `id` )";
$wpsc_database_template[$table_name]['indexes']['log_id'] = " KEY `log_id` ( `log_id`, `form_id` )";
$wpsc_database_template[$table_name]['previous_names'] = "{$wpdb->prefix}submited_form_data";

// code to create or update the {$wpdb->prefix}wpsc_coupon_codes table
$table_name = WPSC_TABLE_COUPON_CODES;
$wpsc_database_template[$table_name]['columns']['id'] = "bigint(20) unsigned NOT NULL auto_increment";
$wpsc_database_template[$table_name]['columns']['coupon_code'] = "varchar(255) NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['value'] = "decimal(11,2) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['is-percentage'] = "char(1) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['use-once'] = "char(1) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['is-used'] = "char(1) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['active'] = "char(1) NOT NULL DEFAULT '1' ";
$wpsc_database_template[$table_name]['columns']['every_product'] = "varchar(255) NOT NULL DEFAULT '' ";
$wpsc_database_template[$table_name]['columns']['start'] = "datetime NOT NULL";
$wpsc_database_template[$table_name]['columns']['expiry'] = "datetime NOT NULL";
$wpsc_database_template[$table_name]['columns']['condition'] = " text NULL";
$wpsc_database_template[$table_name]['indexes']['PRIMARY'] = "PRIMARY KEY  ( `id` )";
$wpsc_database_template[$table_name]['indexes']['coupon_code'] = " KEY `coupon_code` ( `coupon_code` )";
$wpsc_database_template[$table_name]['indexes']['active'] = " KEY `active` ( `active` )";
$wpsc_database_template[$table_name]['indexes']['start'] = " KEY `start` ( `start` )";
$wpsc_database_template[$table_name]['indexes']['expiry'] = " KEY `expiry` ( `expiry` )";

// code to create or update the {$wpdb->prefix}wpsc_claimed_stock table
$table_name = WPSC_TABLE_CLAIMED_STOCK;  /* !wpsc_claimed_stock */
$wpsc_database_template[$table_name]['columns']['product_id'] = "bigint(20) UNSIGNED NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['variation_stock_id'] = "bigint(20) UNSIGNED NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['stock_claimed'] = "FLOAT NOT NULL ";
$wpsc_database_template[$table_name]['columns']['last_activity'] = "DATETIME NOT NULL ";
$wpsc_database_template[$table_name]['columns']['cart_id'] = "VARCHAR( 255 ) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['columns']['cart_submitted'] = "VARCHAR( 1 ) NOT NULL DEFAULT '0' ";
$wpsc_database_template[$table_name]['indexes']['unique_key'] = "UNIQUE KEY `unique_key` ( `product_id`,`variation_stock_id`,`cart_id`)";
$wpsc_database_template[$table_name]['indexes']['last_activity'] = "KEY `last_activity` ( `last_activity` )";
$wpsc_database_template[$table_name]['indexes']['cart_submitted'] = "KEY `cart_submitted` ( `cart_submitted` )";


?>
