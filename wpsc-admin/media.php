<?php

add_action( 'admin_enqueue_scripts', '_wpsc_action_enqueue_media_scripts' );
add_action( 'admin_enqueue_scripts', '_wpsc_action_enqueue_media_styles' );
add_action( 'admin_footer', '_wpsc_action_print_media_templates' );

function _wpsc_action_enqueue_media_scripts() {
	$current_screen = get_current_screen();

	if ( in_array( $current_screen->id, array( 'wpsc-product-variations-iframe', 'wpsc-product' ) ) ) {
		$post = get_post();
		if ( ! $post )
			$id = absint( $_REQUEST['product_id'] );
		else
			$id = $post->ID;

		$gallery = _wpsc_get_product_gallery_json( $id );
		wp_enqueue_script( 'wpsc-media', WPSC_URL . '/wpsc-admin/js/media.js', array( 'media-editor', 'wp-e-commerce-admin', 'jquery-ui-sortable', 'post' ), WPSC_VERSION );
		wp_localize_script( 'wpsc-media', 'WPSC_Media', array(
			'l10n' => array(
				'productMediaTitle' => __( 'Add Images to Product Gallery', 'wp-e-commerce' ),
				'saveGallery'       => __( 'Set Product Images', 'wp-e-commerce' ),
			),
			'gallery' => $gallery,
			'updateGalleryNonce' => wp_create_nonce( 'wpsc_ajax_update_gallery_' . $id ),
			'getGalleryNonce'    => wp_create_nonce( 'wpsc_ajax_get_gallery_' . $id )
		) );
	}
}

function _wpsc_action_enqueue_media_styles() {
	$current_screen = get_current_screen();

	if ( 'wpsc-product' == $current_screen->id )
		wp_enqueue_style( 'wpsc-media', WPSC_URL . '/wpsc-admin/css/media.css', array( 'media-views' ), WPSC_VERSION );
}

function _wpsc_action_print_media_templates() {
	?>
	<script type="text/html" id="tmpl-wpsc-featured-image">
		<div class="wpsc-media-featured-image">
			<span class="title"><?php _e( 'Featured image', 'wp-e-commerce' ); ?></span>
			<a class="edit-selection" href="#"><?php _ex( 'Edit', 'edit featured image', 'wp-e-commerce' ); ?></a>
		</div>
		<div class="wpsc-selection-view"></div>
	</script>
	<?php
}

function _wpsc_ajax_verify_get_variation_gallery() {
	return _wpsc_ajax_verify_nonce( 'get_variation_gallery_' . absint( $_REQUEST['id'] ) );
}

function _wpsc_ajax_get_variation_gallery() {
	$id = absint( $_REQUEST['id'] );

	$gallery = _wpsc_get_product_gallery_json( $id );

	return array(
		'models' => $gallery,
		'featuredId' => wpsc_the_product_thumbnail_id( $id )
	);
}

/**
 * Verifies the save product gallery AJAX nonce.
 *
 * @return WP_Error|boolean True if nonce is valid. WP_Error if otherwise.
 */
function _wpsc_ajax_verify_save_product_gallery() {
	return _wpsc_ajax_verify_nonce( 'update_gallery_' . absint( $_REQUEST['postId'] ) );
}

function _wpsc_ajax_verify_get_product_gallery() {
	return _wpsc_ajax_verify_nonce( 'get_gallery_' . absint( $_REQUEST['postId'] ) );
}

function _wpsc_ajax_save_product_gallery() {
	$id = absint( $_REQUEST['postId'] );
	$items = array_map( 'absint', $_REQUEST['items'] );
	$thumb = get_post_thumbnail_id( $id );

	// always make sure the thumbnail is included
	if ( $thumb && ! in_array( $thumb, $items ) )
		$items[] = $thumb;

	$result = wpsc_set_product_gallery( $id, $items );

	return _wpsc_get_product_gallery_json( $id );
}

function _wpsc_ajax_get_product_gallery() {
	$id = absint( $_REQUEST['postId'] );
	return _wpsc_get_product_gallery_json( $id );
}

function _wpsc_get_product_gallery_json( $id ) {
	$attachments = wpsc_get_product_gallery( $id );
	return array_map( 'wp_prepare_attachment_for_js', $attachments );
}
