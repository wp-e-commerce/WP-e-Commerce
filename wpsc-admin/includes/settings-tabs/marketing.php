<?php

class WPSC_Settings_Tab_Marketing extends WPSC_Settings_Tab {

	public function __construct() {
		add_action( 'admin_notices', array( $this, 'yoast_check' ) );
	}

	public function yoast_check() {
		$yoast_options = get_option( 'Yoast_Google_Analytics' );
		$wpec_tracking = isset( $yoast_options['wpec_tracking'] ) && $yoast_options['wpec_tracking'] ? true : false;

		if ( $wpec_tracking ) {
			?>
			<div class="error">
				<p><?php _e( '<strong>You appear to have Google Analytics for WordPress installed.</strong>. <br /> This is not a problem, however, you also appear to have the WPeC tracking enabled.  We highly recommend disabling that setting and using the settings on this page.', 'wpsc' ); ?></p>
			</div>
		<?php
		}
	}

	public function display() {
		?>
			<div class='metabox-holder'>
				<?php
					add_meta_box( 'wpsc_marketing_settings', __( 'Marketing Section', 'wpsc' ), array( $this, 'marketing_meta_box' ), 'wpsc' );
					add_meta_box( 'wpsc_rss_address', __( 'RSS Address', 'wpsc' ), array( $this, 'rss_address_meta_box' ), 'wpsc' );
					add_meta_box( 'wpsc_google_merch_center', __( 'Google Merchant Centre / Google Product Search', 'wpsc' ), array( $this, 'google_merch_center_meta_box' ), 'wpsc' );
					add_meta_box( 'wpsc_google_analytics_integration', __( 'Google Analytics', 'wpsc' ), array( $this, 'google_analytics_integration' ), 'wpsc' );

					do_meta_boxes( 'wpsc', 'advanced', null );
				?>

			</div>
		<?php
	}

	public function google_analytics_integration() {
		?>
			<input type='hidden' name='change-settings' value='true' />
			<p>
				<span class='input_label'><?php _e( 'Disable Google Analytics tracking', 'wpsc' ); ?></span>
				<input value='1' <?php checked( '1', get_option( 'wpsc_ga_disable_tracking' ) ); ?> type='checkbox' name='wpsc_ga_disable_tracking' />
				<span class='description'><?php _e( 'If, for whatever reason, you decide you do not want any tracking, disable it.', 'wpsc' ); ?></span>
			</p><br />
			<p class="wpsc_ga_currently_tracking">
				<span class='input_label'><?php _e( 'Currently tracking Google Analytics', 'wpsc' ); ?></span>
				<input value='1' <?php checked( '1', get_option( 'wpsc_ga_currently_tracking' ) ); ?> type='checkbox' name='wpsc_ga_currently_tracking' />
				<span class='description'><?php _e( 'If you have already manually placed your Google Analytics tracking code in your theme, or have another plugin handling it, check this box.', 'wpsc' ); ?></span>
			</p><br />
			<p class="wpsc_ga_advanced">
				<span class='input_label'><?php _e( 'Advanced', 'wpsc' ); ?></span>
				<input value='1' <?php checked( '1', get_option( 'wpsc_ga_advanced' ) ); ?> type='checkbox' name='wpsc_ga_advanced' /><br />
				<span class='description'><?php _e( 'By default, we insert the multiple-domain asynchronous tracking code.  This should be fine for 99% of users.  If you need to fine-tune it, select the Advanced option.  Then, instead of simply entering your tracking ID, you will enter the enter tracking code from Google Analytics into the header.php file of your theme.', 'wpsc' ); ?></span>
			</p><br />
			<p class='wpsc_ga_tracking_id'>
				<span class='input_label'><?php _e( 'Tracking ID', 'wpsc' ); ?></span>
				<input value="<?php echo esc_attr( get_option( 'wpsc_ga_tracking_id' ) ); ?>" type='text' name='wpsc_ga_tracking_id' />
				<span class='description'><?php _e( 'Enter your tracking ID here.', 'wpsc' ); ?></span>
			</p><br />
	<?php
	}

	public function marketing_meta_box() {

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
			<input type='hidden' name='change-settings' value='true' />
			<p>
				<span class='input_label'><?php esc_html_e( 'Display Cross Sales', 'wpsc' ); ?></span>
				<input <?php echo $wpsc_also_bought1; ?> type='checkbox' name='wpsc_also_bought' />
				<span class='description'><?php esc_html_e( 'Adds the \'Users who bought this also bought\' item to the single products page.', 'wpsc' ); ?></span>
			</p><br />
			<p>
				<span class='input_label'><?php esc_html_e( 'Show Share This (Social Bookmarks)', 'wpsc' ); ?></span>
				<input <?php echo $wpsc_share_this1; ?> type='checkbox' name='wpsc_share_this' />
				<span class='description'>  <?php esc_html_e( 'Adds the \'Share this link\' item to the single products page.', 'wpsc' ); ?></span>
			</p><br />
			<p>
				<span class='input_label'> <?php esc_html_e( 'Display How Customer Found Us Survey', 'wpsc' ) ?></span>
				<input <?php echo $display_find_us1; ?> type='checkbox' name='display_find_us' />
				<span class='description'>  <?php esc_html_e( 'Adds the \'How did you find out about us\' drop-down option at checkout.', 'wpsc' ) ?></span>
			</p><br />
			<p>
				<span class='input_label'> <?php esc_html_e( 'Display Facebook Like', 'wpsc' ) ?></span>
				<input type='hidden' value='0' name='wpsc_options[wpsc_facebook_like]' />
				<input <?php echo $facebook_like1; ?> type='checkbox' name='wpsc_options[wpsc_facebook_like]' />
				<span class='description'>  <?php esc_html_e( 'Adds the Facebook Like button on your single products page.', 'wpsc' ) ?></span>
			</p><br />
	<?php
	}

	public function rss_address_meta_box() {
		?>
			<p><?php esc_html_e( 'People can use this RSS feed to keep up to date with your product list.', 'wpsc' ); ?></p>
			<p><?php esc_html_e( 'RSS Feed Address', 'wpsc' ) ?> :	<?php echo get_bloginfo( 'url' ) . "/index.php?rss=true&amp;action=product_list"; ?></p>
		<?php
	}

	function google_merch_center_meta_box() {
		?>
			<p><?php esc_html_e( 'To import your products into <a href="http://www.google.com/merchants/" target="_blank">Google Merchant Centre</a> so that they appear within Google Product Search results, sign up for a Google Merchant Centre account and add a scheduled data feed with the following URL:', 'wpsc' ); ?></p>

			<?php $google_feed_url = add_query_arg( array( 'rss' => 'true', 'action' => 'product_list', 'xmlformat' => 'google' ), home_url( '/' ) ); ?>

			<a href="<?php echo esc_url( $google_feed_url ); ?>"><?php echo esc_url( $google_feed_url ); ?></a>

		<?php
	}
}