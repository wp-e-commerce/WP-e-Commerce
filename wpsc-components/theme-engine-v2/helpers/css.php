<?php

add_action( 'wp_enqueue_scripts'        , '_wpsc_te2_enqueue_styles', 1 );

function _wpsc_te2_enqueue_styles() {
	wp_register_style( 'wpsc-common', wpsc_locate_asset_uri( 'css/common.css' ), array(), WPSC_VERSION );

	do_action( 'wpsc_register_styles' );

	wp_enqueue_style( 'wpsc-common' );

	if ( apply_filters( 'wpsc_add_inline_style', true ) ) {
		wp_add_inline_style( 'wpsc-common', _wpsc_get_inline_style() );
	}

	do_action( 'wpsc_enqueue_styles' );
}

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
	$archive_width     = get_option( 'product_image_width' );
	$single_width      = get_option( 'single_view_image_width' );
	$tax_width         = get_option( 'category_image_width' );
	$thumbnail_padding = apply_filters( 'wpsc_thumbnail_padding', 15 );

	ob_start();
	?>
	.wpsc-page-main-store .wpsc-product-summary {
		width: -moz-calc(100% - <?php echo $archive_width + $thumbnail_padding; ?>px);
		width: -webkit-calc(100% - <?php echo $archive_width + $thumbnail_padding; ?>px);
		width: calc(100% - <?php echo $archive_width + $thumbnail_padding; ?>px);
	}

	.wpsc-page-single .wpsc-product-summary {
		width: -moz-calc(100% - <?php echo $single_width + $thumbnail_padding; ?>px);
		width: -webkit-calc(100% - <?php echo $single_width + $thumbnail_padding; ?>px);
		width: calc(100% - <?php echo $single_width + $thumbnail_padding; ?>px);
	}

	.wpsc-page-taxonomy .wpsc-product-summary {
		width: -moz-calc(100% - <?php echo $tax_width + $thumbnail_padding; ?>px);
		width: -webkit-calc(100% - <?php echo $tax_width + $thumbnail_padding; ?>px);
		width: calc(100% - <?php echo $tax_width + $thumbnail_padding; ?>px);
	}
	<?php
	return ob_get_clean();
}
