<?php

function wpsc_set_validation_errors( $validation, $context = 'main' ) {
	if ( ! is_wp_error( $validation ) ) {
		return;
	}

	$message_collection = WPSC_Message_Collection::get_instance();

	foreach ( $validation->errors as $id => $message ) {
		$message_collection->add( $message[0], 'validation', $context, 'normal', $id );
	}
}

function wpsc_get_inline_validation_error( $field, $types = 'validation', $context = 'inline', $mode = 'all' ) {
	static $cache = array();

	$cache_key = md5( $types . ':' . $context . ':' . $mode );

	if ( ! array_key_exists( $cache_key, $cache ) ) {
		$message_collection  = WPSC_Message_Collection::get_instance();
		$cache[ $cache_key ] = $message_collection->query( $types, $context, $mode );
	}

	$errors = array();

	foreach ( $cache[ $cache_key ] as $type => $messages ) {
		if ( array_key_exists( $field, $messages ) )
			$errors[] = $messages[ $field ];
	}

	return $errors;
}