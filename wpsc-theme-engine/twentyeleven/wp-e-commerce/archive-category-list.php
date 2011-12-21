<header class="page-header">
	<h1 class="page-title">
		<?php esc_html_e( 'Product Categories', 'wpsc' ); ?>
	</h1>
</header>

<?php
	wpsc_list_product_categories( array(
		'show_description' => false, // switch "false" to "true" to show description
		'show_thumbnail'   => false, // switch "false" to "true" to show thumbnail
		'title_li'         => false,
	) );
?>