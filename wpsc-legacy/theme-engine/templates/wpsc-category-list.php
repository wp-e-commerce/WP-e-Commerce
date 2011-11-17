<?php
/**
 * wpsc-category-shortcode is the code trigered by using the [showcategories] shortcode
 * @package wp-e-commerce 
 * @since 3.8
 */
?>
<div class="wpsc_categories wpsc_category_grid group">
	<?php wpsc_start_category_query(array('category_group'=> get_option('wpsc_default_category'), 'show_thumbnails'=> 1)); ?>
		<a href="<?php wpsc_print_category_url();?>" class="wpsc_category_grid_item  <?php wpsc_print_category_classes_section(); ?>" title="<?php wpsc_print_category_name(); ?>">
			<?php wpsc_print_category_image(get_option('category_image_width'),get_option('category_image_height')); ?>
		</a>
		<?php wpsc_print_subcategory("", ""); ?>
	<?php wpsc_end_category_query(); ?>
	
</div><!--close wpsc_categories-->

<?php 
?>