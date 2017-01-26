<?php echo $before_widget; ?>

<?php
if ( ! empty( $title ) )
	echo $before_title . $title . $after_title;
?>

<ul class="wpsc-widget-latest-product-list">
<?php
	foreach ( $products as $post ): setup_postdata( $post ); ?>
	<li class="wpsc-widget-latest-product-list-item">
<?php	if ( $instance['show_image'] ): ?>
		<a class="wpsc-thumbnail wpsc-product-thumbnail" href="<?php the_permalink(); ?>"><?php wpsc_product_thumbnail( 'widget' ); ?></a>
<?php 	endif; ?>

<?php	if ( $instance['show_name'] ): ?>
	<strong>
		<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
	</strong>
<?php endif; ?>

<?php 	if ( $instance['show_description'] ): ?>
		<div class="wpsc-on-sale-widget-description">
			<?php wpsc_product_description(); ?>
		</div>
<?php  	endif; ?>

<?php 	if ( $instance['show_normal_price'] || $instance['show_sale_price'] ): ?>
		<div class="wpsc-product-price">

<?php 		if ( $instance['show_normal_price'] ): ?>
				<del class="wpsc-old-price">
					<?php /* translators: Reg. means Regular */ 
					esc_html_e( 'Reg.', 'wp-e-commerce' ); ?>
					<span class="wpsc-amount"><?php wpsc_product_original_price(); ?></span>
				</del>
<?php 		endif; ?>

<?php 		if ( $instance['show_normal_price'] ): ?>
				<div class="wpsc-sale-price">
					<span class="wpsc-sale"><?php esc_html_e( 'Sale', 'wp-e-commerce' ); ?>: </span>
					<span class="wpsc-amount"><?php wpsc_product_sale_price(); ?></span>
				</div>
<?php 		endif; ?>

<?php 		if ( $instance['show_you_save'] ): ?>
				<div class="wpsc-you-save">
					<strong><?php esc_html_e( 'You save', 'wp-e-commerce' ); ?>:</strong> <span class="wpsc-amount"><?php wpsc_product_you_save(); ?></span>
				</div>
<?php 		endif; ?>
		</div>
<?php 	endif; ?>
	</li>
<?php
	endforeach; wp_reset_postdata(); ?>
</ul>

<?php echo $after_widget; ?>