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
		wp_enqueue_script( 'wpsc-media', WPSC_URL . '/wpsc-admin/js/media.js', array( 'media-editor', 'wp-e-commerce-admin', 'post' ), WPSC_VERSION );
		wp_localize_script( 'wpsc-media', 'WPSC_Media', array(
			'l10n' => array(
				'productMediaTitle' => __( 'Add Images to Product Gallery', 'wpsc' ),
				'saveGallery'       => __( 'Set Product Images', 'wpsc' ),
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
			<span class="title"><?php _e( 'Featured image', 'wpsc' ); ?></span>
			<a class="edit-selection" href="#"><?php _ex( 'Edit', 'edit featured image', 'wpsc' ); ?></a>
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

function wpsc_get_product_gallery( $id ) {
	$ids = get_post_meta( $id, '_wpsc_product_gallery', true );

	$args = array(
		'nopaging' => true,
		'post_status' => 'all',
		'post_type' => 'attachment'
	);

	// By default, when the user took no action to select product gallery, all the
	// images attached to a product are treated as gallery images. If $ids is not
	// empty, however, it means the user has made some selection for the product
	// gallery, we should respect that selection.
	if ( empty( $ids ) ) {
		$args['post_parent'] = $id;
		$args['orderby'] = 'menu_order';
		$args['order'] = 'ASC';
	} else {
		if ( ! is_array( $ids ) )
			$ids = array();

		if ( has_post_thumbnail( $id ) ) {
			$thumb_id = get_post_thumbnail_id( $id );
			if ( ! in_array( $thumb_id, $ids ) )
				array_unshift( $ids, $thumb_id );
		}

		if ( ! is_array( $ids ) || empty( $ids ) )
			return array();

		$args['post__in'] = $ids;
	}

	return get_posts( $args );
}

function wpsc_set_product_gallery( $id, $attachments ) {
	$attachment_ids = array();
	foreach ( $attachments as $attachment ) {
		if ( is_object( $attachment ) )
			$attachment_ids[] = $attachment->ID;
		elseif ( is_numeric( $attachment ) )
			$attachment_ids[] = absint( $attachment );
	}

	return update_post_meta( $id, '_wpsc_product_gallery', $attachment_ids );
}