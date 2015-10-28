/*global WPSC_Set_Post_Thumbnail, post_id, alert */
function WPSetAsThumbnail(id, nonce){
	var $link = jQuery('a#wp-post-thumbnail-' + id);

	$link.text( WPSC_Set_Post_Thumbnail.saving );
	jQuery.wpsc_post(
		{
			action       :"set_variation_product_thumbnail",
			post_id      : post_id,
			thumbnail_id : id,
			'nonce'      : WPSC_Set_Post_Thumbnail.nonce,
			cookie       : encodeURIComponent(document.cookie)
		},
		function(response){
			var win = window.dialogArguments || opener || parent || top;
			$link.text( WPSC_Set_Post_Thumbnail.link_text );
			if ( ! response.success ) {
				alert( WPSC_Set_Post_Thumbnail.error );
			} else {
				jQuery('a.wp-post-thumbnail').show();
				$link.text( WPSC_Set_Post_Thumbnail.done );
				$link.fadeOut( 2000 );
				win.wpsc_set_variation_product_thumbnail(post_id, response.src);
			}
		},
		'json'
	);
}
