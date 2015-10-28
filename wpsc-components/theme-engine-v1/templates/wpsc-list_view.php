<?php
global $wp_query, $wpdb;
?>
<div id="list_view_products_page_container">

<?php wpsc_output_breadcrumbs(); ?>

	<?php do_action('wpsc_top_of_products_page'); // Plugin hook for adding things to the top of the products page, like the live search ?>

	<?php if(wpsc_display_categories()): ?>
	  <?php if(wpsc_category_grid_view()) :?>
			<div class="wpsc_categories wpsc_category_grid group">
				<?php wpsc_start_category_query(array('category_group'=> 1, 'show_thumbnails'=> 1)); ?>
					<a href="<?php wpsc_print_category_url();?>" class="wpsc_category_grid_item  <?php wpsc_print_category_classes_section(); ?>" title="<?php wpsc_print_category_name();?>">
						<?php wpsc_print_category_image(); ?>
					</a>
					<?php wpsc_print_subcategory("", ""); ?>
				<?php wpsc_end_category_query(); ?>
			</div><!--close wpsc_categories-->
	  <?php else:?>
			<ul class="wpsc_categories">
				<?php wpsc_start_category_query(array('category_group'=> 1, 'show_thumbnails'=> get_option('show_category_thumbnails'))); ?>
						<li>
							<?php wpsc_print_category_image(); ?>

							<a href="<?php wpsc_print_category_url();?>" class="wpsc_category_link  <?php wpsc_print_category_classes_section(); ?>"><?php wpsc_print_category_name();?></a>
							<?php if(get_option('wpsc_category_description')) :?>
								<?php wpsc_print_category_description("<div class='wpsc_subcategory'>", "</div>"); ?>
							<?php endif;?>

							<?php wpsc_print_subcategory("<ul>", "</ul>"); ?>
						</li>
				<?php wpsc_end_category_query(); ?>
			</ul>
		<?php endif; ?>
	<?php endif; ?>




	<?php if(wpsc_display_products()): ?>
		<?php if(wpsc_is_in_category()) : ?>
			<div class="wpsc_category_details">
				<?php if(get_option('show_category_thumbnails') && wpsc_category_image()) : ?>
					<img src='<?php echo wpsc_category_image(); ?>' alt='<?php echo wpsc_category_name(); ?>' title='<?php echo wpsc_category_name(); ?>' />
				<?php endif; ?>

				<?php if(get_option('wpsc_category_description') &&  wpsc_category_description()) : ?>
					<?php echo wpsc_category_description(); ?>
				<?php endif; ?>
			</div><!--close wpsc_category_details-->
		<?php endif; ?>

		<?php if(wpsc_has_pages_top()) : ?>
			<div class="wpsc_page_numbers_top">
				<?php wpsc_pagination(); ?>
			</div><!--close wpsc_page_numbers_top-->
		<?php endif; ?>
		<table class="list_productdisplay <?php echo wpsc_category_class(); ?>">
			<?php /** start the product loop here */?>
			<?php $alt = 0;	?>
			<?php while (wpsc_have_products()) :  wpsc_the_product(); ?>
				<?php
				$alt++;
				if ($alt %2 == 1) { $alt_class = 'alt'; } else { $alt_class = ''; }
				?>
				<tr class="product_view_<?php echo wpsc_the_product_id(); ?> <?php echo $alt_class;?>">
					<td width="40%">
						<h2 class="prodtitle">
							<?php if(get_option('hide_name_link') == 1) : ?>
								<?php echo wpsc_the_product_title(); ?>
							<?php else: ?>
								<a class="wpsc_product_title" href="<?php echo wpsc_the_product_permalink(); ?>"><?php echo wpsc_the_product_title(); ?></a>
							<?php endif; ?>
							<?php echo wpsc_edit_the_product_link(); ?>
						</h2>
					</td>
					<?php if(wpsc_show_stock_availability()): ?>
						<td class="stock">
						<?php if(wpsc_product_has_stock()) : ?>
									<div id="stock_display_<?php echo wpsc_the_product_id(); ?>" class="in_stock"><?php _e('Product in stock', 'wp-e-commerce'); ?></div>
							<?php else: ?>
									<div id="stock_display_<?php echo wpsc_the_product_id(); ?>" class="out_of_stock"><?php _e('Product not in stock', 'wp-e-commerce'); ?></div>
							<?php endif; ?>
						</td>
					<?php endif; ?>

					<td>
 						<?php do_action('wpsc_product_before_description', wpsc_the_product_id(), $wp_query->post); ?>
					</td>
					<td class='wpsc_price_td'>
						<?php wpsc_the_product_price_display( array( 'output_you_save' => false ) ); ?>
					</td>

					<td>
						<?php if(wpsc_product_external_link(wpsc_the_product_id()) != '') : ?>
							<?php	$action =  wpsc_product_external_link(wpsc_the_product_id()); ?>
						<?php else: ?>
							<?php	$action =  wpsc_this_page_url(); ?>
						<?php endif; ?>
						<form class='product_form' id="product_<?php echo wpsc_the_product_id(); ?>" enctype="multipart/form-data" action="<?php echo $action; ?>" method="post" name="product_<?php echo wpsc_the_product_id(); ?>">
							<?php do_action ( 'wpsc_product_form_fields_begin' ); ?>

							<?php if(wpsc_has_multi_adding()): ?>
                            <div class="quantity_container">
								<label class="wpsc_quantity_update" for="wpsc_quantity_update_<?php echo wpsc_the_product_id(); ?>"><?php _e('Quantity:', 'wp-e-commerce'); ?></label>
								<input type="text" id="wpsc_quantity_update_<?php echo wpsc_the_product_id(); ?>" name="wpsc_quantity_update" size="2" value="1" />
								<input type="hidden" name="key" value="<?php echo wpsc_the_cart_item_key(); ?>"/>
								<input type="hidden" name="wpsc_update_quantity" value="true" />
								<input type='hidden' name='wpsc_ajax_action' value='wpsc_update_quantity' />
							</div><!--close quantity_container-->
							<?php endif ;?>
							<input type="hidden" value="add_to_cart" name="wpsc_ajax_action" />
							<input type="hidden" value="<?php echo wpsc_the_product_id(); ?>" name="product_id" />

							<?php if((get_option('hide_addtocart_button') == 0) &&  (get_option('addtocart_or_buynow') !='1')) : ?>
								<?php if(wpsc_product_has_stock()) : ?>
									<div class="wpsc_buy_button_container">

											<?php if(wpsc_product_external_link(wpsc_the_product_id()) != '') : ?>
											<?php $action = wpsc_product_external_link( wpsc_the_product_id() ); ?>
											<input class="wpsc_buy_button" type="button" value="<?php echo wpsc_product_external_link_text( wpsc_the_product_id(), __( 'Buy Now', 'wp-e-commerce' ) ); ?>" onclick="return gotoexternallink('<?php echo $action; ?>', '<?php echo wpsc_product_external_link_target( wpsc_the_product_id() ); ?>')">
											<?php elseif ( wpsc_product_has_variations( wpsc_the_product_id() ) ) : ?>
											<a href="<?php echo esc_url( wpsc_the_product_permalink() ); ?>" class="wpsc_buy_button"><?php _e( 'View Product', 'wp-e-commerce' )?></a>
											<?php else : ?>
										<input type="submit" value="<?php _e('Add To Cart', 'wp-e-commerce'); ?>" name="Buy" class="wpsc_buy_button" id="product_<?php echo wpsc_the_product_id(); ?>_submit_button" />
											<?php endif; ?>
										<div class="wpsc_loading_animation">
											<img title="<?php esc_attr_e( 'Loading', 'wp-e-commerce' ); ?>" alt="<?php esc_attr_e( 'Loading', 'wp-e-commerce' ); ?>" src="<?php echo wpsc_loading_animation_url(); ?>" />
											<?php _e('Updating cart...', 'wp-e-commerce'); ?>
										</div><!--close wpsc_loading_animation-->
									</div><!--close wpsc_buy_button_container-->
								<?php else : ?>
									<p class="soldout"><?php _e('This product has sold out.', 'wp-e-commerce'); ?></p>
								<?php endif ; ?>
							<?php endif ; ?>
							<?php do_action ( 'wpsc_product_form_fields_end' ); ?>
						</form>
					</td>
				</tr>
			<?php endwhile; ?>
			<?php /** end the product loop here */?>
		</table>


		<?php if(wpsc_product_count() == 0):?>
			<p><?php  _e('There are no products in this group.', 'wp-e-commerce'); ?></p>
		<?php endif ; ?>

	    <?php do_action( 'wpsc_theme_footer' ); ?>

			<?php if(wpsc_has_pages_bottom()) : ?>
			<div class="wpsc_page_numbers_bottom">
				<?php wpsc_pagination(); ?>
			</div><!--close wpsc_page_numbers_bottom-->
		<?php endif; ?>
	<?php endif; ?>
</div><!--close list_view_products_page_container-->
