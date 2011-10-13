<?php
function phpme_map( $from_array, $map, $return_type = 'Array' ) {
	$return = array();
	
	foreach ( $map as $to_key => $from_key ) {
		if ( isset( $from_array[$from_key] ) )
		$return[$to_key] = $from_array[$from_key];
	}
	
	if ( $return_type == 'Object' )
		$return = (Object) $return;

	return $return;
}
?>