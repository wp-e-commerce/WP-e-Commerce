<?php
	$curr_cat       = get_term( $category_id, 'wpsc_product_category', ARRAY_A );
	$category_list  = get_terms( 'wpsc_product_category', 'hide_empty=0&parent=' . $category_id );
	$link = get_term_link((int)$category_id , 'wpsc_product_category');
	$category_image = wpsc_get_categorymeta( $curr_cat['term_id'], 'image' );
	$category_image = WPSC_CATEGORY_URL . $category_image;
	$show_name = $instance['show_name'];
	
	if ( $grid ) : ?>

		<a href="<?php echo esc_url( $link ); ?>" style="padding: 4px 4px 0 0; width:<?php echo $width; ?>px; height:<?php echo $height; ?>px;" title="<?php echo $curr_cat['name']; ?>" class="wpsc_category_grid_item">
			<?php wpsc_parent_category_image( $show_thumbnails, $category_image , $width, $height, true ,$show_name); ?>
		</a>

		<?php wpsc_start_category_query( array( 'parent_category_id' => $category_id, 'show_thumbnails' => $show_thumbnails, 'show_name' => $show_name) ); ?>

		<a href="<?php wpsc_print_category_url(); ?>" style="width:<?php echo $width; ?>px; height:<?php echo $height; ?>px" class="wpsc_category_grid_item" title="<?php wpsc_print_category_name(); ?>">
			<?php wpsc_print_category_image( $width, $height ); ?>
		</a>

		<?php wpsc_print_subcategory( '', '' ); ?>

		<?php wpsc_end_category_query(); ?>

<?php else : ?>
		<div class="wpsc_categorisation_group" id="categorisation_group_<?php echo $category_id; ?>">
			<ul class="wpsc_categories wpsc_top_level_categories">
				<li class="wpsc_category_<?php echo $curr_cat['term_id']; wpsc_print_category_classes($curr_cat);  ?>">
					<?php if(! ($category_image == WPSC_CATEGORY_URL) ){ ?>
						<a href="<?php echo esc_url( $link ); ?>" class="wpsc_category_image_link"><?php 
						wpsc_parent_category_image( $show_thumbnails, $category_image , $width, $height, false, $show_name ); ?></a>
					<?php } ?>
					
					<a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $curr_cat['name'] ); ?></a>

					<ul class="wpsc_categories wpsc_second_level_categories">

						<?php wpsc_start_category_query( array( 'parent_category_id' => $category_id, 'show_thumbnails' => $show_thumbnails , 'show_name' => $show_name) ); ?>

							<li class="wpsc_category_<?php wpsc_print_category_id(); wpsc_print_category_classes_section();?>">
								<a href="<?php wpsc_print_category_url(); ?>" class="wpsc_category_image_link">

									<?php wpsc_print_category_image( $width, $height ); ?>

								</a>

								<a href="<?php wpsc_print_category_url(); ?>" class="wpsc_category_link">

									<?php wpsc_print_category_name(); ?>

									<?php if ( 1 == get_option( 'show_category_count') ) wpsc_print_category_products_count( "(",")" ); ?>

								</a>

								<?php wpsc_print_subcategory( '<ul>', '</ul>' ); ?>

							</li>

						<?php wpsc_end_category_query(); ?>

					</ul>
				</li>
			</ul>

			<div class="clear_category_group"></div>
		</div>

<?php endif; ?>
