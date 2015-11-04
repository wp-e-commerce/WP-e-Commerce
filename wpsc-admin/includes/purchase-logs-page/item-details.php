<div class="wrap">
	<div id="icon-users" class="icon32"><br/></div>
	<h2>
		<?php esc_html_e( 'Sales Log Details', 'wp-e-commerce' ); ?>
		<span class="subtitle">#<?php echo $this->log_id; ?> – <?php echo wpsc_purchaselog_details_date_time(); ?></span>
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

	<div id='post-body'>
		<?php if ( wpsc_has_purchlog_shipping() ): ?>
		<?php do_action( 'wpsc_shipping_details_top', $this->log_id ); ?>
			<div id='wpsc_shipping_details_box'>
				<h3><?php esc_html_e( 'Shipping Address', 'wp-e-commerce' ); ?></h3>
				<blockquote>
					<strong>
						<?php echo ( wpsc_display_purchlog_shipping_name() != ""           ) ? wpsc_display_purchlog_shipping_name() . "<br />"               : '<span class="field-blank">' . __( 'Anonymous', 'wp-e-commerce' ) . '</span>' ; ?>
					</strong>
					<?php echo ( wpsc_display_purchlog_shipping_address() != ""            ) ? wpsc_display_purchlog_shipping_address() . "<br />"            : '' ; ?>
					<?php echo ( wpsc_display_purchlog_shipping_city() != ""               ) ? wpsc_display_purchlog_shipping_city() . ", "               : '' ; ?>
					<?php echo ( wpsc_display_purchlog_shipping_state_and_postcode() != "" ) ? wpsc_display_purchlog_shipping_state_and_postcode() . "<br />" : '' ; ?>
					<?php echo ( wpsc_display_purchlog_shipping_country() != ""            ) ? wpsc_display_purchlog_shipping_country() . "<br />"            : '<span class="field-blank">' . __( 'Country not specified', 'wp-e-commerce' ) . '</span>' ; ?>
				</blockquote>
				<h4><?php esc_html_e( 'Shipping Details', 'wp-e-commerce' ); ?></h4>
				<blockquote>
					<strong><?php esc_html_e( 'Shipping Method:', 'wp-e-commerce' ); ?></strong> <?php echo wpsc_display_purchlog_shipping_method(); ?><br />
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
			</div>
		<?php endif ?>

		<div id='wpsc_billing_details_box'>
			<?php do_action( 'wpsc_billing_details_top', $this->log_id ); ?>
			<h3><?php esc_html_e( 'Billing Details', 'wp-e-commerce' ); ?></h3>
			<blockquote>
				<strong>
					<?php echo ( wpsc_display_purchlog_buyers_name() != ""           ) ? wpsc_display_purchlog_buyers_name() . "<br />"               : '<span class="field-blank">' . __( 'Anonymous', 'wp-e-commerce' ) . '</span>' ; ?>
				</strong>
				<?php echo ( wpsc_display_purchlog_buyers_address() != ""            ) ? wpsc_display_purchlog_buyers_address() . "<br />"            : '' ; ?>
				<?php echo ( wpsc_display_purchlog_buyers_city() != ""               ) ? wpsc_display_purchlog_buyers_city() . "<br />"               : '' ; ?>
				<?php echo ( wpsc_display_purchlog_buyers_state_and_postcode() != "" ) ? wpsc_display_purchlog_buyers_state_and_postcode() . "<br />" : '' ; ?>
				<?php echo ( wpsc_display_purchlog_buyers_country() != ""            ) ? wpsc_display_purchlog_buyers_country() . "<br />"            : '<span class="field-blank">' . __( 'Country not specified', 'wp-e-commerce' ) . '</span>' ; ?>
			</blockquote>
			<h4><?php esc_html_e( 'Payment Details', 'wp-e-commerce' ); ?></h4>
			<blockquote>
				<strong><?php esc_html_e( 'Phone:', 'wp-e-commerce' ); ?> </strong><?php echo ( wpsc_display_purchlog_buyers_phone() != "" ) ? wpsc_display_purchlog_buyers_phone() : __( '<em class="field-blank">not provided</em>', 'wp-e-commerce' ); ?><br />
				<strong><?php esc_html_e( 'Email:', 'wp-e-commerce' ); ?> </strong>
					<a href="mailto:<?php echo wpsc_display_purchlog_buyers_email(); ?>?subject=<?php echo rawurlencode( sprintf( __( 'Message from %s', 'wp-e-commerce' ), site_url() ) ); ?>">
						<?php echo ( wpsc_display_purchlog_buyers_email() != "" ) ? wpsc_display_purchlog_buyers_email() : __( '<em class="field-blank">not provided</em>', 'wp-e-commerce' ); ?>
					</a>
				<br />
				<strong><?php esc_html_e( 'Payment Method:', 'wp-e-commerce' ); ?> </strong><?php echo wpsc_display_purchlog_paymentmethod(); ?><br />
				<?php if ( wpsc_display_purchlog_display_howtheyfoundus() ) : ?>
					<strong><?php esc_html_e( 'How User Found Us:', 'wp-e-commerce' ); ?> </strong><?php echo wpsc_display_purchlog_howtheyfoundus(); ?><br />
				<?php endif; ?>
			</blockquote>
			<?php do_action( 'wpsc_billing_details_bottom', $this->log_id ); ?>
		</div>

		<div id='wpsc_items_ordered'>
			<h3><?php esc_html_e( 'Items Ordered', 'wp-e-commerce' ); ?></h3>
			<table class="widefat" cellspacing="0">
				<thead>
				<tr>
					<?php
						print_column_headers( 'wpsc_purchase_log_item_details' );
					 ?>
				</tr>
				</thead>

				<tbody>
					<?php $this->purchase_log_cart_items(); ?>

					<tr class="wpsc_purchaselog_start_totals">
						<td colspan="<?php echo $cols; ?>">
							<?php if ( wpsc_purchlog_has_discount_data() ): ?>
								<?php esc_html_e( 'Coupon Code', 'wp-e-commerce' ); ?>: <?php echo wpsc_display_purchlog_discount_data(); ?>
							<?php endif; ?>
						</td>
						<th class='right-col'><?php esc_html_e( 'Discount', 'wp-e-commerce' ); ?> </th>
						<td><?php echo wpsc_display_purchlog_discount(); ?></td>
					</tr>

					<?php if( ! wpec_display_product_tax() ): ?>
						<tr>
							<td colspan='<?php echo $cols; ?>'></td>
							<th class='right-col'><?php esc_html_e( 'Taxes', 'wp-e-commerce' ); ?> </th>
							<td><?php echo wpsc_display_purchlog_taxes(); ?></td>
						</tr>
					<?php endif; ?>

					<tr>
						<td colspan='<?php echo $cols; ?>'></td>
						<th class='right-col'><?php esc_html_e( 'Shipping', 'wp-e-commerce' ); ?> </th>
						<td><?php echo wpsc_display_purchlog_shipping(); ?></td>
					</tr>
					<tr>
						<td colspan='<?php echo $cols; ?>'></td>
						<th class='right-col'><?php esc_html_e( 'Total', 'wp-e-commerce' ); ?> </th>
						<td><?php echo wpsc_display_purchlog_totalprice(); ?></td>
					</tr>
				</tbody>
			</table>

			<?php do_action( 'wpsc_purchlogitem_metabox_start', $this->log_id ); ?>

			<div class="metabox-holder">
				<div id="purchlogs_notes" class="postbox">
					<h3 class='hndle'><?php _e( 'Order Notes' , 'wp-e-commerce' ); ?></h3>
					<div class='inside'>
						<form method="post" action="">
							<input type='hidden' name='wpsc_admin_action' value='purchlogs_update_notes' />
							<input type="hidden" name="wpsc_purchlogs_update_notes_nonce" id="wpsc_purchlogs_update_notes_nonce" value="<?php echo wp_create_nonce( 'wpsc_purchlogs_update_notes' ); ?>" />
							<input type='hidden' name='purchlog_id' value='<?php echo $this->log_id; ?>' />
							<p><textarea name="purchlog_notes" rows="3" wrap="virtual" id="purchlog_notes" style="width:100%;"><?php echo esc_textarea( wpsc_display_purchlog_notes() ); ?></textarea></p>
							<div><input class="button" type="submit" name="button" id="button" value="<?php _e( 'Update Notes', 'wp-e-commerce' ); ?>" /></div>
						</form>
					</div>
				</div>
			</div>

			<?php $this->purchase_logs_checkout_fields(); ?>
			<?php do_action( 'wpsc_purchlogitem_metabox_end', $this->log_id ); ?>

		</div>
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
