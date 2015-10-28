<ul class="wpsc-widget-product-category-list wpsc-widget-product-category-list-<?php echo $parent ?>">
<?php foreach ( $categories as $cat ): ?>
	<li class="wpsc-widget-product-category-list-item">

<?php	if ( $this->instance['show_image'] ): ?>
		<a class="wpsc-thumbnail wpsc-product-category-thumbnail" href="<?php echo esc_url( get_term_link( (int) $cat->term_id, 'wpsc_product_category' ) ); ?>"><?php $this->category_image( $cat ); ?></a>
<?php 	endif; ?>

<?php	if ( $this->instance['show_name'] ): ?>
		<a href="<?php echo esc_url( get_term_link( (int) $cat->term_id, 'wpsc_product_category' ) ); ?>">
<?php		$string =    $this->instance['show_count']
			            /** translator: %1$s: category name, %2$s: category count **/
			          ? _x( '%1$s (%2$s)', 'product category widget name and count', 'wp-e-commerce' )
			          : '%1$s';
			printf(
				$string,
				esc_html( $cat->name ),
				esc_html( $cat->count )
			);
?>

		</a>
<?php 	endif; ?>
		</a>
<?php   $this->list_child_categories_of( $cat->term_id ); ?>
	</li><!-- .wpsc-widget-product-category-list-item -->
<?php endforeach; ?>
</ul><!-- .wpsc-widget-product-category-list-<?php echo $parent; ?> -->