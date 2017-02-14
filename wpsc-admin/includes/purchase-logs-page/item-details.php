<div class="wrap" id="poststuff">
	<h2 class="log-details-title-area">
		<span class="log-title-details">
			<?php esc_html_e( 'Sales Log Details', 'wp-e-commerce' ); ?>
			<span class="subtitle">#<?php echo $this->log_id; ?> – <?php echo wpsc_purchaselog_details_date_time(); ?></span>
		</span>
 		<?php $this->purchase_logs_pagination() ?>
 	</h2>
	<?php if ( $receipt_sent ): ?>
		<div class="updated">
			<p><?php esc_html_e( 'Receipt has been resent successfully.', 'wp-e-commerce' ); ?></p>
		</div>
	<?php elseif ( $receipt_not_sent ) : ?>
		<div class="error">
			<p><?php esc_html_e( 'Receipt could not be sent to buyer. Please contact your hosting service and make sure your server can send emails.', 'wp-e-commerce' ); ?></p>
		</div>
	<?php endif; ?>

	<?php do_action( 'wpsc_purchlog_before_metaboxes', $this->log_id ); ?>

	<div id="post-body">
		<?php if ( wpsc_has_purchlog_shipping() ): ?>
		<?php do_action( 'wpsc_shipping_details_top', $this->log_id ); ?>
			<div id="wpsc_shipping_details_box" class="log-details-box">
				<h3>
					<?php esc_html_e( 'Shipping Address', 'wp-e-commerce' ); ?>
					<?php if ( $this->can_edit ) : ?>
						<a class="edit-log-details edit-shipping-details" href="#edit-shipping-address"><?php _e( 'Edit', 'wp-e-commerce' ); ?></a>
					<?php endif; ?>
				</h3>
				<blockquote id="wpsc-shipping-details">
					<?php self::shipping_address_output(); ?>
				</blockquote>

				<?php $method = wpsc_display_purchlog_shipping_method(); ?>
				<?php if ( ! empty( $method ) ) : ?>
				<h4><?php esc_html_e( 'Shipping Details', 'wp-e-commerce' ); ?></h4>
				<blockquote>
					<strong><?php esc_html_e( 'Shipping Method:', 'wp-e-commerce' ); ?></strong> <?php echo $method; ?><br />
					<strong><?php esc_html_e( 'Shipping Option:', 'wp-e-commerce' ); ?></strong> <?php echo wpsc_display_purchlog_shipping_option(); ?><br />
					<?php $purchase_weight = wpsc_purchlogs_get_weight_text(); ?>
					<?php if ( ! empty( $purchase_weight ) ) { ?>
						<strong><?php esc_html_e( 'Purchase Weight:', 'wp-e-commerce' ); ?></strong> <?php echo $purchase_weight; ?><br />
						<?php } ?>
					<?php if ( wpsc_purchlogs_has_tracking() ) { ?>
						<strong><?php echo esc_html_x( 'Tracking ID:', 'purchase log', 'wp-e-commerce' ); ?></strong> <?php echo wpsc_purchlogitem_trackid(); ?><br />

						<?php $tracking_status = wpsc_purchlogitem_trackstatus(); ?>
						<?php if ( ! empty ( $tracking_status ) ) { ?>
							<strong><?php esc_html_e( 'Shipping Status:', 'wp-e-commerce' ); ?></strong> <?php echo $tracking_status ?><br />
						<?php  } ?>

						<?php $tracking_history = wpsc_purchlogitem_trackhistory(); ?>
						<?php if ( ! empty ( $tracking_history ) ) { ?>
							<strong><?php esc_html_e( 'Track History:', 'wp-e-commerce' ); ?></strong> <?php echo $tracking_history; ?><br />
						<?php } ?>

					<?php } ?>
				</blockquote>
				<?php do_action( 'wpsc_shipping_details_bottom', $this->log_id ); ?>
			<?php endif; ?>
			</div>
		<?php endif ?>

		<div id="wpsc_billing_details_box" class="log-details-box">
			<?php do_action( 'wpsc_billing_details_top', $this->log_id ); ?>
			<h3>
				<?php esc_html_e( 'Billing Details', 'wp-e-commerce' ); ?>
				<?php if ( $this->can_edit ) : ?>
					<a class="edit-log-details edit-billing-details" href="#edit-billing-address"><?php _e( 'Edit', 'wp-e-commerce' ); ?></a>
				<?php endif; ?>
			</h3>
			<blockquote id="wpsc-billing-details">
				<?php self::billing_address_output(); ?>
			</blockquote>
			<h4><?php esc_html_e( 'Payment Details', 'wp-e-commerce' ); ?></h4>
			<blockquote id="wpsc-payment-details">
				<?php self::payment_details_output(); ?>
			</blockquote>
			<?php do_action( 'wpsc_billing_details_bottom', $this->log_id ); ?>
		</div>

		<?php if ( $this->can_edit ) : ?>
			<div class="wpsc-controller postbox" id="edit-shipping-billing" style="display:none;">
				<?php $this->edit_contact_details_form(); ?>
			</div>
		<?php endif; ?>

		<?php do_meta_boxes( get_current_screen()->id, 'normal', $this->log ); ?>

		<?php do_meta_boxes( get_current_screen()->id, 'low', $this->log ); ?>
	</div>


	<div id="wpsc_purchlogitems_links">
		<h3><?php esc_html_e( 'Actions', 'wp-e-commerce' ); ?></h3>
		<?php do_action( 'wpsc_purchlogitem_links_start' ); ?>
		<ul>
			<?php
			require_once( WPSC_FILE_PATH . '/wpsc-admin/includes/purchase-log-action-links.php' );
			$action_links = new WPSC_Purchase_Log_Action_Links( $this->log_id );
			$action_links->display_link_list_items();
			?>
		</ul>
	</div>

</div>

<script type="text/html" id="tmpl-wpsc-found-products">
	<table class="widefat"><thead><tr><th class="found-radio"><br /></th><th><?php esc_html_e( 'Title', 'wp-e-commerce' ); ?></th><th class="no-break"><?php esc_html_e( 'Date', 'wp-e-commerce' ); ?></th><th class="no-break"><?php esc_html_e( 'Status', 'wp-e-commerce' ); ?></th></tr></thead><tbody></tbody></table>
</script>

<script type="text/html" id="tmpl-wpsc-found-product-rows">
	<# _.each( data.posts, function( post ) { #>
		<tr class="found-posts {{ post.class }}">
			<td class="found-radio"><input type="checkbox" id="found-{{ post.ID }}" name="found_post_id" value="{{ post.ID }}"></td>
			<td><label for="found-{{ post.ID }}">{{ post.title }}</label></td><td class="no-break">{{ post.time }}</td><td class="no-break">{{ post.status }}</td>
		</tr>
	<#} ); #>
</script>
