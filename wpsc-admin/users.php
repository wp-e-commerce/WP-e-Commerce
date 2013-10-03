<?php

add_action( 'user_profile_update_errors', '_wpsc_action_user_update_errors', 10, 3 );

function _wpsc_action_user_update_errors( $errors, $update, $user ) {
	if ( $user->role == 'wpsc_anonymous' )
		unset( $errors->errors['empty_email'] );
}