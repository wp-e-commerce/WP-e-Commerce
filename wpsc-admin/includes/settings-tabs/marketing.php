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
				<p><?php _e( '<strong>You appear to have Google Analytics for WordPress installed.</strong>. <br /> This is not a problem, however, you also appear to have the WPeC tracking enabled.  We highly recommend disabling that setting and using the settings on this page.', 'wp-e-commerce' ); ?></p>
			</div>
		<?php
		}
	}

	public function display() {
		$this->marketing_settings_form();
		$this->rss_address_settings_form();
		$this->google_merch_center_settings_form();
		$this->google_analytics_integration_settings_form();
	}

	public function marketing_settings_form() {

		$wpsc_also_bought  = get_option( 'wpsc_also_bought' );
		$wpsc_also_bought1 = '';

		if ( '1' == $wpsc_also_bought )
			$wpsc_also_bought1 = "checked='checked'";

		$wpsc_share_this  = get_option( 'wpsc_share_this' );
		$wpsc_share_this1 = '';

		if ( '1' == $wpsc_share_this )
			$wpsc_share_this1 = "checked='checked'";

		$facebook_like  = get_option( 'wpsc_facebook_like' );
		$facebook_like1 = '';
		if ( 'on' == $facebook_like )
			$facebook_like1 = "checked='checked'";

		$display_find_us  = get_option( 'display_find_us' );
		$display_find_us1 = '';

		if ( '1' == $display_find_us )
			$display_find_us1 = "checked='checked'";

		?>

		<h3><?php esc_html_e( 'Marketing Settings', 'wp-e-commerce'); ?></h3>
		<table class='form-table'>
			<tr>
				<th>
					<?php esc_html_e( "'Users who bought this also bought'", 'wp-e-commerce' ); ?>
				</th>
				<td>
					<label>
						<input <?php echo $wpsc_also_bought1; ?> type='checkbox' name='wpsc_also_bought' />
						<?php esc_html_e( "Add 'Users who bought this also bought' item to the single products page.", 'wp-e-commerce' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th>
					<?php esc_html_e( "'Share This' Social Bookmarks", 'wp-e-commerce' ); ?>
				</th>
				<td>
					<label>
						<input <?php echo $wpsc_share_this1; ?> type='checkbox' name='wpsc_share_this' />
						<?php esc_html_e( 'Add the \'Share this link\' item to the single products page.', 'wp-e-commerce' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th>
					<?php esc_html_e( "'How Customer Found Us' Survey", 'wp-e-commerce' ) ?>
				</th>
				<td>
					<label>
						<input <?php echo $display_find_us1; ?> type='checkbox' name='display_find_us' />
						<?php esc_html_e( 'Add the \'How did you find out about us\' drop-down option at checkout.', 'wp-e-commerce' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th>
					<?php esc_html_e( "Facebook 'Like' Button", 'wp-e-commerce' ) ?>
				</th>
				<td>
					<input type='hidden' value='0' name='wpsc_options[wpsc_facebook_like]' />
					<input <?php echo $facebook_like1; ?> type='checkbox' name='wpsc_options[wpsc_facebook_like]' />
					<?php esc_html_e( 'Add the Facebook Like button on your single products page.', 'wp-e-commerce' ); ?>
				</td>
			</tr>
		</table>
		<?php
	}

	public function rss_address_settings_form() {
		?>
		<h3><?php esc_html_e( 'Product RSS Address', 'wp-e-commerce'); ?></h3>
		<p><?php esc_html_e( 'People can use this RSS feed to keep up to date with your product list.', 'wp-e-commerce' ); ?></p>

		<table class='form-table'>
			<tr>
				<th>
					<?php esc_html_e( 'RSS Feed Address', 'wp-e-commerce' ); ?>
				</th>
				<td>
					<?php $rss_url = add_query_arg( array( 'rss' => 'true', 'action' => 'product_list' ), home_url( '/' ) ); ?>
					<a href="<?php echo esc_url( $rss_url ); ?>"><code><?php echo esc_url( $rss_url ); ?></code></a>
				</td>
			</tr>
		</table>
		<?php
	}

	public function google_merch_center_settings_form() {
		?>
		<h3><?php esc_html_e( 'Google Merchant Centre / Google Product Search', 'wp-e-commerce'); ?></h3>
   		<p><?php printf( __( 'To import your products into <a href="%s" target="_blank">Google Merchant Centre</a> so that they appear within Google Product Search results, sign up for a Google Merchant Centre account and add a scheduled data feed with the following URL:', 'wp-e-commerce' ), 'http://www.google.com/merchants/' ); ?></p>

		<table class='form-table'>
			<tr>
				<th>
					<?php esc_html_e( 'Google Product Feed', 'wp-e-commerce' ); ?>
				</th>
				<td>
					<?php $google_feed_url = add_query_arg( array( 'rss' => 'true', 'action' => 'product_list', 'xmlformat' => 'google' ), home_url( '/' ) ); ?>
					<a href="<?php echo esc_url( $google_feed_url ); ?>"><code><?php echo esc_url( $google_feed_url ); ?></code></a>
				</td>
			</tr>
		</table>
		<?php
	}

	public function google_analytics_integration_settings_form() {
		?>
		<h3><?php esc_html_e( 'Google Analytics E-Commerce Tracking', 'wp-e-commerce' ); ?></h3>
		<p><?php printf( __( 'Track successful transactions and items purchased in <a href="%s">Google Analytics</a>.', 'wp-e-commerce' ), 'http://www.google.com/analytics/' ); ?></p>
		<input type='hidden' name='change-settings' value='true' />
		<table class='form-table'>
			<tr>
				<th>
					<?php _e( 'Enable', 'wp-e-commerce' ); ?>
				</th>
				<td>
					<label>
						<input value='1' type='hidden' name='wpsc_ga_disable_tracking' />
						<input value='0' <?php checked( '0', get_option( 'wpsc_ga_disable_tracking' ) ); ?> type='checkbox' name='wpsc_ga_disable_tracking' />
						<?php _e( 'Enable Google Analytics tracking', 'wp-e-commerce' ); ?>
					</label>
					<p class='description'><?php _e( 'If, for whatever reason, you decide you do not want any tracking, disable it.', 'wp-e-commerce' ); ?></p>
				</td>
			</tr>
			<tr>
				<th>
					<?php _ex( 'Google Analytics Tracking ID', 'google analytics', 'wp-e-commerce' ); ?>
				</th>
				<td>
					<input value="<?php echo esc_attr( get_option( 'wpsc_ga_tracking_id' ) ); ?>" type='text' name='wpsc_ga_tracking_id' />
					<span class='description'><?php _e( 'e.g. <code>UA-XXXXX-Y</code>', 'wp-e-commerce' ); ?></span>
				</td>
			</tr>
			<tr>
				<th>
					<?php _e( 'Universal Analytics', 'wp-e-commerce' ); ?>
				</th>
				<td>
					<label>
						<input value="1" <?php checked( '1', get_option( 'wpsc_ga_use_universal' ) ); ?> type="checkbox" name="wpsc_ga_use_universal" />
						<?php printf( __( 'Use <a%s>Universal Analytics</a> tracking code', 'wp-e-commerce' ), ' href="https://support.google.com/analytics/answer/2790010?hl=en-GB&ref_topic=6010376" target="universal_analytics"' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th>
					<?php _e( 'Tracking Code Present', 'wp-e-commerce' ); ?>
				</th>
				<td>
					<label>
						<input value='1' <?php checked( '1', get_option( 'wpsc_ga_currently_tracking' ) ); ?> type='checkbox' name='wpsc_ga_currently_tracking' />
						<?php _e( 'Google Analytics is tracking my site', 'wp-e-commerce' ); ?>
					</label>
					<p class='description'><?php printf( __( 'Enable this if the Google Analytics tracking code is already present on your site, e.g. manually placed in your theme, or managed by another plugin. We will only insert the <a href="%s">E-Commerce tracking events</a> on the transaction results page.', 'wp-e-commerce' ), 'https://developers.google.com/analytics/devguides/collection/gajs/methods/gaJSApiEcommerce'); ?></p>
				</td>
			</tr>
			<tr>
				<th>
					<?php _e( 'Advanced Mode', 'wp-e-commerce' ); ?>
				</th>
				<td>
					<label>
						<input value='1' <?php checked( '1', get_option( 'wpsc_ga_advanced' ) ); ?> type='checkbox' name='wpsc_ga_advanced' />
						<?php _e( 'Enable Advanced Mode', 'wp-e-commerce' ); ?>
					</label>
					<p class='description'><?php _e( 'By default, we insert the multiple-domain asynchronous tracking code.  This should be fine for 99% of users.  If you need to fine-tune it, select the Advanced option.  Then, instead of simply entering your tracking ID, you will enter the tracking code from Google Analytics into the header.php file of your theme.', 'wp-e-commerce' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

}
