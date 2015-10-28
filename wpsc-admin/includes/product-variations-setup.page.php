<div id="product_variations">
	<h4><a href="#wpsc_variation_metabox" class="add_variation_set_action"><?php esc_html_e( '+ Add New Variants', 'wp-e-commerce' ) ?></a></h4>

	<div id="add-new-variation-set">
		<p>
			<label for="new-variation-set-name"><?php esc_html_e( "Enter variation set's name", 'wp-e-commerce' ); ?></label>
			<input type="text" class="text-field" id="new-variation-set-name" /><br />
		</p>
		<p class="howto"><?php esc_html_e( "Example: Color. If you want to add variants to an existing set, you can enter the name of that set here.", 'wp-e-commerce' ); ?></p>
		<p>
			<label for="new-variants"><?php esc_html_e( "Enter new variants", 'wp-e-commerce' ); ?></label>
			<input type="text" class="text-field" id="new-variants" /><br />
		</p>
		<p class="howto"><?php esc_html_e( "Example: Red, Green, Blue. Separate variants with commas.", 'wp-e-commerce' ); ?></p>
		<p>
			<a class="button" href="#"><?php esc_html_e( 'Add New Variants', 'wp-e-commerce' ); ?></a>
			<img src="<?php echo esc_url( wpsc_get_ajax_spinner() ); ?>" class="ajax-feedback" title="" alt="" /><br class="clear" />
		</p>
	</div>

	<p><a name='variation_control'>&nbsp;</a><?php _e( 'Select the Variation sets and then the corresponding Variants you want to add to this product.', 'wp-e-commerce' ) ?></p>

	<form action="" method="post">
		<ul class="variation_checkboxes">
			<?php
				wp_terms_checklist( $this->parent_id, array(
					'taxonomy'      => 'wpsc-variation',
					'walker'        => new WPSC_Walker_Variation_Checklist(),
					'checked_ontop' => false,
				) );
			?>
		</ul>
		<input type="hidden" name="action2" value="generate" />
		<input type="hidden" name="product_id" value="<?php echo $this->parent_id; ?>" />
		<?php wp_nonce_field( 'wpsc_generate_product_variations', '_wpsc_generate_product_variations_nonce' ); ?>
		<?php submit_button( __( 'Generate Variations', 'wp-e-commerce' ) ); ?>
	</form>

	<div class="clear"></div>
</div>