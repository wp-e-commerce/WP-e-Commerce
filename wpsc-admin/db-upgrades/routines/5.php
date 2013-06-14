<?php

function _wpsc_db_upgrade_5() {
	_wpsc_maybe_update_product_meta_array_keys();
}

/**
 * Rename old _wpsc_* product metadata array keys so they no longer include the '_wpsc_' prefix
 * See https://github.com/wp-e-commerce/WP-e-Commerce/issues/492 for details
 */
function _wpsc_maybe_update_product_meta_array_keys() {
	global $wpdb;

	$product_ids = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_wpsc_product_metadata' AND meta_value LIKE  '%_wpsc_%'" );

	foreach ( $product_ids as $product_id ) {
		$metadata_needs_saving = false;
		$product_metadata = get_post_meta( $product_id, '_wpsc_product_metadata', true );
		if ( is_array( $product_metadata ) ) {
			foreach ( $product_metadata as $meta_key => $meta_value ) {
				if ( '_wpsc_' === substr( $meta_key, 0, 6 ) ) {
					/*
					 Typical meta keys that need renaming are:
						 wpsc_url_name
						 _wpsc_sku
						 _wpsc_dimensions
						 _wpsc_engraved
						 _wpsc_can_have_uploaded_image
						 _wpsc_unpublish_oos
					 */
					$new_meta_key = substr( $meta_key, 6 );

					// remove the old (_wpsc_ prefixed) metadata from the array
					unset ( $product_metadata[ $meta_key ] );
					$metadata_needs_saving = true;

					// If metadata doesn't already exist with the new non-prefixed key, add it to the array
					// This check ensures that we don't overwrite newer product metadata
					if ( ! isset( $product_metadata[ $new_meta_key ] ) ) {
						$product_metadata[ $new_meta_key ] = $meta_value;
					}
				}
			}
		}
		if ( $metadata_needs_saving ) {
			update_post_meta( $product_id, '_wpsc_product_metadata', $product_metadata );
		}
	}
}