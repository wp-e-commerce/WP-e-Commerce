<?php
/**
 * options marketing is the main function for displaying the WP-Admin : Settings > Marketing page
 * @access public
 *
 * @since 3.8
 * @param null
 * @return null
 */
function wpsc_options_marketing() {

	/* wpsc_setting_page_update_notification displays the wordpress styled notifications */
	wpsc_settings_page_update_notification(); ?>

	<div class='metabox-holder'>
		<?php
			add_meta_box( 'wpsc_marketing_settings', __( 'Marketing Section', 'wpsc' ), 'wpsc_marketing_meta_box', 'wpsc' );
			add_meta_box( 'wpsc_rss_address', __( 'RSS Address', 'wpsc' ), 'wpsc_rss_address_meta_box', 'wpsc' );
			add_meta_box( 'wpsc_google_merch_center', __( 'Google Merchant Centre / Google Product Search', 'wpsc' ), 'wpsc_google_merch_center_meta_box', 'wpsc' );

			do_meta_boxes( 'wpsc', 'advanced', null );
		?>

	</div>

<?php

}

function wpsc_marketing_meta_box() {

	$wpsc_also_bought  = get_option( 'wpsc_also_bought' );
	$wpsc_also_bought1 = '';

	if ( '1' == $wpsc_also_bought )
		$wpsc_also_bought1 = "checked ='checked'";

	$wpsc_share_this  = get_option( 'wpsc_share_this' );
	$wpsc_share_this1 = '';

	if ( '1' == $wpsc_share_this )
		$wpsc_share_this1 = "checked ='checked'";
	
	$facebook_like  = get_option( 'wpsc_facebook_like' );
	$facebook_like1 = '';
	if ( 'on' == $facebook_like )
		$facebook_like1 = "checked ='checked'";	

	$display_find_us  = get_option( 'display_find_us' );
	$display_find_us1 = '';

	if ( '1' == $display_find_us )
		$display_find_us1 = "checked ='checked'"; ?>
		
		
		<form method='post' action='' id='cart_options' name='cart_options' class='wpsc_form_track'>
			<input type='hidden' name='change-settings' value='true' />
			<p>
				<span class='input_label'><?php _e( 'Display Cross Sales', 'wpsc' ); ?></span>
				<input <?php echo $wpsc_also_bought1; ?> type='checkbox' name='wpsc_also_bought' />
				<span class='description'>  <?php _e( 'Adds the \'Users who bought this also bought\' item to the single products page.', 'wpsc' ); ?></span>
			</p><br />
			<p>
				<span class='input_label'><?php _e( 'Show Share This (Social Bookmarks)', 'wpsc' ); ?></span>
				<input <?php echo $wpsc_share_this1; ?> type='checkbox' name='wpsc_share_this' />
				<span class='description'>  <?php _e( 'Adds the \'Share this link\' item to the single products page.', 'wpsc' ); ?></span>
			</p><br />
			<p>
				<span class='input_label'> <?php _e( 'Display How Customer Found Us Survey', 'wpsc' ) ?></span>
				<input <?php echo $display_find_us1; ?> type='checkbox' name='display_find_us' />
				<span class='description'>  <?php _e( 'Adds the \'How did you find out about us\' drop-down option at checkout.', 'wpsc' ) ?></span>
			</p><br />
			<p>
				<span class='input_label'> <?php _e( 'Display Facebook Like', 'wpsc' ) ?></span>
				<input type='hidden' value='0' name='wpsc_options[wpsc_facebook_like]' />
				<input <?php echo $facebook_like1; ?> type='checkbox' name='wpsc_options[wpsc_facebook_like]' />
				<span class='description'>  <?php _e( 'Adds the Facebook Like button on your single products page.', 'wpsc' ) ?></span>
			</p><br />
			<div class="submit">
				<input type='hidden' name='wpsc_admin_action' value='submit_options' />
				<?php wp_nonce_field( 'update-options', 'wpsc-update-options' ); ?>
				<input type='submit' class='button-primary' value='<?php _e( 'Update &raquo;', 'wpsc' ); ?>' name='form_submit' />
			</div>
	</form>

<?php
}

function wpsc_rss_address_meta_box() { ?>

	<p><?php _e( 'People can use this RSS feed to keep up to date with your product list.', 'wpsc' ); ?></p>
	<p><?php _e( 'RSS Feed Address', 'wpsc' ) ?> :	<?php echo get_bloginfo( 'url' ) . "/index.php?rss=true&amp;action=product_list"; ?></p>

<?php
}

function wpsc_google_merch_center_meta_box() { ?>

	<p><?php _e( 'To import your products into <a href="http://www.google.com/merchants/" target="_blank">Google Merchant Centre</a> so that they appear within Google Product Search results, sign up for a Google Merchant Centre account and add a scheduled data feed with the following URL:', 'wpsc' ); ?></p>

	<?php $google_feed_url = get_bloginfo( 'url' ) . "/index.php?rss=true&action=product_list&xmlformat=google"; ?>

	<a href="<?php esc_attr_e( htmlentities( $google_feed_url, ENT_QUOTES, 'UTF-8' ) ); ?>"><?php esc_attr_e(  htmlentities( $google_feed_url, ENT_QUOTES, 'UTF-8' ) ); ?></a>

<?php
}

?>
