<?php

function wpsc_twentyeleven_product_category_and_tag_list() {
	$cat_list =wpsc_get_product_category_list( array( 'separator' => __( ', ', 'twentyeleven' ) ) );
	$tag_list = wpsc_get_product_tag_list( array( 'separator' => __( ', ', 'twentyeleven' ) ) );
	$cat_count = wpsc_get_product_category_count();
	$tag_count = wpsc_get_product_tag_count();
	$show_sep = $cat_count > 0 && $tag_count > 0;
	?>
	<?php if ( $cat_count > 0 ): ?>
		<span class="cat-links wpsc-product-category-links">
			<?php
				printf(
					_nx( '<span class="%1$s">Category:</span> %2$s', '<span class="%1$s">Categories:</span> %2$s', $cat_count, 'twentyeleven product category list', 'wpsc' ),
					'entry-utility-prep entry-utility-prep-cat-links', // %1$s
					$cat_list                                          // %2$s
				);
			?>
		</span>
	<?php endif; ?>

	<?php if ( $show_sep ): ?>
		<span class="sep"> | </span>
	<?php endif; ?>

	<?php if ( $tag_count > 0 ): ?>
		<span class="tag-links wpsc-product-tag-links">
			<?php
				printf(
					_nx( '<span class="%1$s">Tag:</span> %2$s', '<span class="%1$s">Tags:</span> %2$s', $tag_count, 'twentyeleven product tag list', 'wpsc' ),
					'entry-utility-prep entry-utility-prep-tag-links', // %1$s
					$tag_list                                          // %2$s
				);
			?>
		</span>
	<?php endif; ?>
	<?php
}

function wpsc_twentyeleven_enqueue_styles() {
	wp_register_style( 'wpsc-twentyeleven-main', wpsc_locate_theme_file_uri( 'css/shop-main.css' ) );
	wp_enqueue_style( 'wpsc-twentyeleven-main' );
}
add_action( 'wp_print_styles', 'wpsc_twentyeleven_enqueue_styles' );