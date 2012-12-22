<div class="wrap">
	<div id="icon-users" class="icon32"><br/></div>
	<h2>
		<?php esc_html_e( 'Sales Log Details', 'wpsc' ); ?>
		<span class="subtitle">#<?php echo $this->log_id; ?></span>
	</h2>
	<?php if ( $receipt_sent ): ?>
		<div class="updated">
			<p><?php esc_html_e( 'Receipt has been resent successfully.', 'wpsc' ); ?></p>
		</div>
	<?php elseif ( $receipt_not_sent ) : ?>
		<div class="error">
			<p><?php esc_html_e( 'Receipt could not be sent to buyer. Please contact your hosting service and make sure your server can send emails.', 'wpsc' ); ?></p>
		</div>
	<?php endif; ?>

	<div id='post-body'>
		<?php if ( wpsc_has_purchlog_shipping() ): ?>
			<div id='wpsc_shipping_details_box'>
				<h3><?php esc_html_e( 'Shipping Details', 'wpsc' ); ?></h3>
				<h4><?php esc_html_e( 'Shipping Address', 'wpsc' ); ?></h4>
				<p><strong><?php echo wpsc_display_purchlog_shipping_name(); ?></strong></p>
				<p>
					<?php echo wpsc_display_purchlog_shipping_address(); ?><br />
					<?php echo wpsc_display_purchlog_shipping_city(); ?><br />
					<?php echo wpsc_display_purchlog_shipping_state_and_postcode(); ?><br />
					<?php echo wpsc_display_purchlog_shipping_country(); ?><br />
				</p>
				<h4><?php esc_html_e( 'Shipping Options', 'wpsc' ); ?></h4>
				<p>
					<?php esc_html_e( 'Shipping Method:', 'wpsc' ); ?> <?php echo wpsc_display_purchlog_shipping_method(); ?><br />
					<?php esc_html_e( 'Shipping Option:', 'wpsc' ); ?> <?php echo wpsc_display_purchlog_shipping_option(); ?><br />
					<?php if( wpsc_purchlogs_has_tracking() ) : ?>
						<?php esc_html_e( 'Tracking ID:', 'wpsc' ); ?> <?php echo wpsc_purchlogitem_trackid(); ?><br />
						<?php esc_html_e( 'Shipping Status:', 'wpsc' ); ?> <?php echo wpsc_purchlogitem_trackstatus(); ?><br />
						<?php esc_html_e( 'Track History:', 'wpsc' ); ?> <?php echo wpsc_purchlogitem_trackhistory(); ?>
					<?php endif; ?>
				</p>
			</div>
		<?php endif ?>

		<div id='wpsc_billing_details_box'>
			<?php do_action( 'wpsc_billing_details_top' ); ?>
			<h3><?php esc_html_e( 'Billing Details', 'wpsc' ); ?></h3>
			<p><strong><?php esc_html_e( 'Purchase Log Date:', 'wpsc' ); ?> </strong><?php echo wpsc_purchaselog_details_date(); ?> </p>
			<p><strong><?php esc_html_e( 'Purchase Number:', 'wpsc' ); ?> </strong><?php echo wpsc_purchaselog_details_purchnumber(); ?> </p>
			<p><strong><?php esc_html_e( 'Buyers Name:', 'wpsc' ); ?> </strong><?php echo wpsc_display_purchlog_buyers_name(); ?></p>
			<p>
				<strong><?php esc_html_e( 'Address:', 'wpsc' ); ?> </strong><br />
				<?php echo wpsc_display_purchlog_buyers_address(); ?><br />
				<?php echo wpsc_display_purchlog_buyers_city(); ?><br />
				<?php echo wpsc_display_purchlog_buyers_state_and_postcode(); ?><br />
				<?php echo wpsc_display_purchlog_buyers_country(); ?><br />
			</p>

			<p><strong><?php esc_html_e( 'Phone:', 'wpsc' ); ?> </strong><?php echo wpsc_display_purchlog_buyers_phone(); ?></p>
			<p><strong><?php esc_html_e( 'Email:', 'wpsc' ); ?> </strong><a href="mailto:<?php echo wpsc_display_purchlog_buyers_email(); ?>?subject=<?php echo rawurlencode( sprintf( __( 'Message from %s', 'wpsc' ), site_url() ) ); ?>"><?php echo wpsc_display_purchlog_buyers_email(); ?></a></p>
			<p><strong><?php esc_html_e( 'Payment Method:', 'wpsc' ); ?> </strong><?php echo wpsc_display_purchlog_paymentmethod(); ?></p>
			<?php if(wpsc_display_purchlog_display_howtheyfoundus()) : ?>
				<p><strong><?php esc_html_e( 'How User Found Us:', 'wpsc' ); ?> </strong><?php echo wpsc_display_purchlog_howtheyfoundus(); ?></p>
			<?php endif; ?>
			<?php do_action( 'wpsc_billing_details_bottom'); ?>
		</div>

		<div id='wpsc_items_ordered'>
			<h3><?php esc_html_e( 'Items Ordered', 'wpsc' ); ?></h3>
			<table class="widefat" cellspacing="0">
				<thead>
				<tr>
					<?php print_column_headers( 'wpsc_purchase_log_item_details' ); ?>
				</tr>
				</thead>

				<tbody>
					<?php $this->purchase_log_cart_items(); ?>

					<tr class="wpsc_purchaselog_start_totals">
						<td colspan="<?php echo $cols; ?>">
							<?php if ( wpsc_purchlog_has_discount_data() ): ?>
								<?php esc_html_e( 'Coupon Code', 'wpsc' ); ?>: <?php echo wpsc_display_purchlog_discount_data(); ?>
							<?php endif; ?>
						</td>
						<th><?php esc_html_e( 'Discount', 'wpsc' ); ?> </th>
						<td><?php echo wpsc_display_purchlog_discount(); ?></td>
					</tr>

					<?php if( ! wpec_display_product_tax() ): ?>
						<tr>
							<td colspan='<?php echo $cols; ?>'></td>
							<th><?php esc_html_e( 'Taxes', 'wpsc' ); ?> </th>
							<td><?php echo wpsc_display_purchlog_taxes(); ?></td>
						</tr>
					<?php endif; ?>

					<tr>
						<td colspan='<?php echo $cols; ?>'></td>
						<th><?php esc_html_e( 'Shipping', 'wpsc' ); ?> </th>
						<td><?php echo wpsc_display_purchlog_shipping(); ?></td>
					</tr>
					<tr>
						<td colspan='<?php echo $cols; ?>'></td>
						<th><?php esc_html_e( 'Total', 'wpsc' ); ?> </th>
						<td><?php echo wpsc_display_purchlog_totalprice(); ?></td>
					</tr>
				</tbody>
			</table>

			<?php $this->purchase_log_custom_fields(); ?>

			<div class="metabox-holder">
				<div id="purchlogs_notes" class="postbox">
					<h3 class='hndle'><?php _e( 'Order Notes' , 'wpsc' ); ?></h3>
					<div class='inside'>
						<form method="post" action="">
							<input type='hidden' name='wpsc_admin_action' value='purchlogs_update_notes' />
							<input type="hidden" name="wpsc_purchlogs_update_notes_nonce" id="wpsc_purchlogs_update_notes_nonce" value="<?php echo wp_create_nonce( 'wpsc_purchlogs_update_notes' ); ?>" />
							<input type='hidden' name='purchlog_id' value='<?php echo $this->log_id; ?>' />
							<p>
								<textarea name="purchlog_notes" rows="3" wrap="virtual" id="purchlog_notes" style="width:100%;"><?php
										if ( isset( $_POST['purchlog_notes'] ) ) {
											echo esc_textarea( stripslashes( $_POST['purchlog_notes'] ) );
										} else {
											echo wpsc_display_purchlog_notes();
										}
									?></textarea>
							</p>
							<p><input class="button" type="submit" name="button" id="button" value="<?php _e( 'Update Notes', 'wpsc' ); ?>" /></p>
						</form>
					</div>
				</div>
			</div>
			<!-- End Order Notes (by Ben) -->

			<?php $this->purchase_logs_checkout_fields(); ?>

		</div>
	</div>

	<div id='wpsc_purchlogitems_links'>
		<h3><?php esc_html_e( 'Actions', 'wpsc' ); ?></h3>
		<?php do_action( 'wpsc_purchlogitem_links_start' ); ?>
		<?php if ( wpsc_purchlogs_have_downloads_locked() != false ): ?>
			<img src='<?php echo WPSC_CORE_IMAGES_URL; ?>/lock_open.png' alt='<?php _e( 'clear lock icon', 'wpsc' ); ?>' />&ensp;<a href='<?php echo $_SERVER['REQUEST_URI'].'&amp;wpsc_admin_action=clear_locks'; ?>'><?php echo wpsc_purchlogs_have_downloads_locked(); ?></a><br /><br class='small' />
		<?php endif; ?>
		<img src='<?php echo WPSC_CORE_IMAGES_URL; ?>/printer.png' alt='<?php _e( 'printer icon', 'wpsc' ); ?>' />&ensp;<a target="_blank" href='<?php echo add_query_arg( 'c', 'packing_slip' ); ?>'><?php esc_html_e( 'View Packing Slip', 'wpsc' ); ?></a>
		<br /><br class='small' />
		<img src='<?php echo WPSC_CORE_IMAGES_URL; ?>/email_go.png' alt='<?php _e( 'email icon', 'wpsc' ); ?>' />&ensp;<a href='<?php echo add_query_arg( 'email_buyer_id', $this->log_id ); ?>'><?php esc_html_e('Resend Receipt to Buyer', 'wpsc'); ?></a>

		<br /><br class='small' />
		<a class='submitdelete' title='<?php echo esc_attr(__( 'Remove this log', 'wpsc' )); ?>' href='<?php echo wp_nonce_url("admin.php?wpsc_admin_action=delete_purchlog&amp;purchlog_id=".$this->log_id, 'delete_purchlog_' .$this->log_id); ?>' onclick="if ( confirm(' <?php echo esc_js(sprintf( __("You are about to delete this log '%s'\n 'Cancel' to stop, 'OK' to delete.",'wpsc'),  wpsc_purchaselog_details_date() )) ?>') ) { return true;}return false;"><img src='<?php echo WPSC_CORE_IMAGES_URL . "/cross.png"; ?>' alt='<?php _e( 'delete icon', 'wpsc' ); ?>' />               &nbsp;<?php _e('Remove this record', 'wpsc') ?></a>

		<br /><br class='small' />&emsp;&ensp;    <a href='<?php echo esc_attr( wp_get_referer() ); ?>'><?php _e('Go Back', 'wpsc'); ?></a>
		<br /><br />
	</div>
	<br />

</div>
