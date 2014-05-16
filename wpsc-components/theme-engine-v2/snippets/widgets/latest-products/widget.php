<?php echo $before_widget; ?>

<?php
if ( ! empty( $title ) ) {
	echo $before_title . $title . $after_title;
}
?>

<ul class="wpsc-widget-latest-product-list">
<?php
	while ( $query->have_posts() ): $query->the_post(); ?>
	<li class="wpsc-widget-latest-product-list-item">
<?php	if ( $instance['show_image'] ): ?>
		<a class="wpsc-thumbnail wpsc-product-thumbnail" href="<?php the_permalink(); ?>"><?php wpsc_product_thumbnail( 'widget' ); ?></a>
<?php 	endif; ?>

<?php	if ( $instance['show_name'] ): ?>
		<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
<?php endif; ?>
	</li>
<?php
	endwhile; wp_reset_postdata(); ?>
</ul>

<?php echo $after_widget; ?>