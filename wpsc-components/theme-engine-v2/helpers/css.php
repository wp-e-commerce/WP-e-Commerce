<?php

function _wpsc_te2_register_styles() {
	$suffix = _wpsc_te2_asset_suffix();

	wpsc_te2_register_style( 'wpsc-common', "common{$suffix}.css" );
	wpsc_te2_register_style( 'wpsc-responsive', "wpsc-responsive{$suffix}.css" );
	wpsc_te2_register_style( 'wpsc-cart-notifications', "cart-notifications{$suffix}.css" );

	do_action( 'wpsc_register_styles' );
}

function _wpsc_te2_enqueue_styles() {
	_wpsc_te2_register_styles();

	wp_enqueue_style( 'wpsc-common' );
	wp_enqueue_style( 'wpsc-responsive' );

	if ( apply_filters( 'wpsc_add_inline_style', true ) ) {
		wp_add_inline_style( 'wpsc-common', _wpsc_get_inline_style() );
	}

	do_action( 'wpsc_enqueue_styles' );
}

add_action( 'wp_enqueue_scripts' , '_wpsc_te2_enqueue_styles', 1 );

/**
 * Inline style that ensure the product summary's width take the thumbnail width
 * into consideration.
 *
 * @access private
 * @since  0.1
 *
 * @return string CSS output
 */
function _wpsc_get_inline_style() {
	$archive_width     = intval( get_option( 'product_image_width' ) );
	$single_width      = intval( get_option( 'single_view_image_width' ) );
	$tax_width         = intval( get_option( 'category_image_width' ) );
	$thumbnail_padding = apply_filters( 'wpsc_thumbnail_padding', 15 );

	ob_start();
	?>
	.wpsc-main-store .wpsc-product-summary,
	.archive .wpsc-product-summary {
		width: -moz-calc(100% - <?php echo $archive_width + $thumbnail_padding; ?>px);
		width: -webkit-calc(100% - <?php echo $archive_width + $thumbnail_padding; ?>px);
		width: calc(100% - <?php echo $archive_width + $thumbnail_padding; ?>px);
	}

	.wpsc-page-single .wpsc-product-summary {
		width: -moz-calc(100% - <?php echo $single_width + $thumbnail_padding; ?>px);
		width: -webkit-calc(100% - <?php echo $single_width + $thumbnail_padding; ?>px);
		width: calc(100% - <?php echo $single_width + $thumbnail_padding; ?>px);
	}

	.wpsc-page-single .wpsc-thumbnail-wrapper {
		width: <?php echo $single_width; ?>px;
	}

	.wpsc-category .wpsc-product-summary {
		width: -moz-calc(100% - <?php echo $tax_width + $thumbnail_padding; ?>px);
		width: -webkit-calc(100% - <?php echo $tax_width + $thumbnail_padding; ?>px);
		width: calc(100% - <?php echo $tax_width + $thumbnail_padding; ?>px);
	}
<?php
	return ob_get_clean();
}

function _wpsc_te2_asset_suffix() {
	$do_minified = apply_filters( 'wpsc_use_minified_styles', ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) );
	return $do_minified ? '.min' : '';
}

function wpsc_te2_register_style( $handle, $relative_src, $deps = array(), $ver = WPSC_VERSION, $media = 'all' ) {

	if ( is_admin() ) {
		$src = WPSC_TE_V2_URL . '/theming/assets/css/' . $relative_src;
	} else {
		$src = wpsc_locate_asset_uri( 'css/' . $relative_src );
	}

	return wp_register_style( $handle, $src, $deps, $ver, $media );
}
