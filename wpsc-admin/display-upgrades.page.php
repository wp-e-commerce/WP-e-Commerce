<?php

function wpsc_display_upgrades_page() {

	do_action( 'wpsc_gold_module_activation' ); ?>

	<div class='wrap'>
		<div class='metabox-holder wpsc_gold_side'>
			<strong><?php esc_html_e( 'WP e-Commerce Upgrades', 'wpsc' ); ?></strong><br />
			<span><?php esc_html_e( 'Add more functionality to your e-Commerce site. Prices may be subject to change.', 'wpsc' ); ?><input type='button' class='button-primary' onclick='window.open ("http://getshopped.org/extend/premium-upgrades/","mywindow");' value='<?php esc_html_e( 'Buy Now', 'wpsc' ); ?>' id='visitInstinct' name='visitInstinct' /></span>

			<br />
			<div class='wpsc_gold_module'>
				<br />
				<a href="http://getshopped.org/extend/premium-upgrades/premium-upgrades/gold-cart-plugin/" target="_blank"><strong><?php esc_html_e( 'Gold Cart', 'wpsc' ); ?></strong></a>
				<p class='wpsc_gold_text'><?php esc_html_e( 'Add product search, multiple image upload, gallery view, Grid View and multiple payment gateway options to your shop', 'wpsc' ); ?></p>
				<span class='wpsc_gold_info'><?php esc_html_e( '$40', 'wpsc' ); ?></span>
			</div>

			<div class='wpsc_gold_module'>
				<br />
				<a href="http://getshopped.org/extend/premium-upgrades/premium-upgrades/dropshop-2010/" target="_blank"><strong><?php esc_html_e( 'DropShop', 'wpsc' ); ?></strong></a>
				<p class='wpsc_gold_text'><?php esc_html_e( 'Impress your customers with our AJAX powered DropShop that lets your customers drag and drop products into their shopping cart', 'wpsc' ); ?></p>
				<span class='wpsc_gold_info'><?php esc_html_e( '$100', 'wpsc' ); ?></span>
			</div>

			<div class='wpsc_gold_module'>
				<br />
				<a href="http://getshopped.org/extend/premium-upgrades/premium-upgrades/member-access-plugin/" target="_blank"><strong><?php esc_html_e( 'Members Access Plugin', 'wpsc' ); ?></strong></a>
				<p class='wpsc_gold_text'><?php esc_html_e( 'Create product memberships and sell them in your store. Use these memberships to restrict premium content on your posts and pages creating a "members only" area perfect for: Forums, Images and Movies and Podcasts', 'wpsc' ); ?></p>
				<span class='wpsc_gold_info'><?php esc_html_e( '$49', 'wpsc' ); ?></span>
			</div>

			<div class='wpsc_gold_module'>
				<br />
				<a href="http://getshopped.org/extend/premium-upgrades/premium-upgrades/product-slider-2010/" target="_blank"><strong><?php esc_html_e( 'Product Slider', 'wpsc' ); ?> </strong></a>
				<p class='wpsc_gold_text'><?php esc_html_e( 'Display your products in a new and fancy way using the "Product Slider" module.', 'wpsc' ); ?></p>
				<span class='wpsc_gold_info'><?php esc_html_e( '$45', 'wpsc' ); ?></span>
			</div>

			<div class='wpsc_gold_module'>
				<br />
				<a href="http://getshopped.org/extend/premium-upgrades/premium-upgrades/nextgen-gallery-buy-now1/" target="_blank"><strong><?php esc_html_e( 'NextGen Gallery Buy Now Buttons', 'wpsc' ); ?> </strong></a>
				<p class='wpsc_gold_text'><?php esc_html_e( 'Make your Online photo gallery into an e-Commerce solution.', 'wpsc' ); ?></p>
				<span class='wpsc_gold_info'><?php esc_html_e( '$10', 'wpsc' ); ?></span>
			</div>

			<div class='wpsc_gold_module'>
				<br />
				<a href="http://getshopped.org/extend/premium-upgrades/premium-upgrades/jplayer-mp3-player/" target="_blank"><strong><?php esc_html_e( 'JPlayer - MP3 Plugin', 'wpsc' ); ?> </strong></a>
				<p class='wpsc_gold_text'><?php esc_html_e( 'JPlayer is a Plugin that provides a Javascript powered MP3 player to each product. This is very similar to our alternative MP3 Player except that it uses CSS and Javascript to customize the look and feel of the player making it much easier for you to style it also comes with a range of skins.', 'wpsc' ); ?></p>
				<span class='wpsc_gold_info'><?php esc_html_e( '$40', 'wpsc' ); ?></span>
			</div>
			<div class='wpsc_gold_module'>
				<br />
				<a href="http://getshopped.org/extend/premium-upgrades/premium-upgrades/fedex-shipping-module/" target="_blank"><strong><?php esc_html_e( 'FedEx Plugin', 'wpsc' ); ?> </strong></a>
				<p class='wpsc_gold_text'><?php esc_html_e( 'This plugin offers shop owners the ability to provide Fedex Shipping Quotes for products with weights.', 'wpsc' ); ?></p>
				<span class='wpsc_gold_info'><?php esc_html_e( '$40', 'wpsc' ); ?></span>
			</div>
			<div class='wpsc_gold_module'>
				<br />
				<a href="http://www.bravenewcode.com/store/plugins/piggy/?utm_source=affiliate-6331&amp;utm_medium=affiliates&amp;utm_campaign=wpec" target="_blank"><strong><?php esc_html_e( 'Piggy', 'wpsc' ); ?> </strong></a>
				<p class='wpsc_gold_text'><?php esc_html_e( 'Your WP E-Commerce sales, in your pocket. Piggy is a web-app that provides mobile access to view sales data for your WP E-Commerce powered WordPress website. Works on iOS and Android.', 'wpsc' ); ?></p>
				<span class='wpsc_gold_info'><?php esc_html_e( '$39', 'wpsc' ); ?></span>
			</div>

		</div>

		<h2><?php esc_html_e( 'Upgrades', 'wpsc' ); ?></h2>
		<div class='wpsc_gold_float'>
			<p><?php esc_html_e( 'Enter your API Username and API Key below.', 'wpsc' ); ?></p>
			<p><a href="http://docs.getshopped.org/category/extending-your-store/premium-plugins/gold-cart/"><?php esc_html_e( 'For more information visit our documentation page.', 'wpsc' ); ?></a></p>

			<div class='metabox-holder'>
				<form method='post' id='gold_cart_form' action=''>

					<?php
					if ( defined( 'WPSC_GOLD_MODULE_PRESENT' ) && ( true == WPSC_GOLD_MODULE_PRESENT ) ) {
						do_action( 'wpsc_gold_module_activation_forms' );
					} else {
					?>

						<div id='wpsc_gold_options_outside'>
							<div  class='form-wrap' >
								<p>
									<?php esc_html_e( "You don't have any Upgrades yet!", 'wpsc' ); ?>
								</p>
							</div>

							<h2><?php _e( 'API Key Reset', 'wpsc' ); ?></h2>
							<div class='form-wrap' >
								<p>
									<?php esc_html_e( 'Enter your API name and key to release it from an old site that you no longer use.', 'wpsc' ); ?> <br /><br />
								</p>
							</div>
						</div>

						<div class='postbox'>
							<h3 class='hndle'><?php esc_html_e( 'API Key Reset', 'wpsc' ); ?></h3>
							<p>
								<label for='activation_name'><?php esc_html_e( 'Name:', 'wpsc' ); ?></label>
								<input class='text' type='text' size='40' value='<?php echo get_option( 'activation_name' ); ?>' name='activation_name' id='activation_name' />
							</p>
							<p>
								<label for='activation_key'><?php esc_html_e( 'API Key:', 'wpsc' ); ?></label>
								<input class='text' type='text' size='40' value='<?php echo get_option( 'activation_key' ); ?>' name='activation_key' id='activation_key' />
							</p>
							<p>
								<input type='hidden' value='true' name='reset_api_key' />
								<input type='submit' class='button-primary' value='<?php esc_html_e( 'Reset API Key', 'wpsc' ); ?>' name='submit_values' />
							</p>
						</div>

					<?php } ?>
				</form>
			<?php do_meta_boxes('wpsc_upgrade_page', 'top', true); ?>
			</div>
		</div>
	</div>

<?php
}

function wpsc_reset_api_key() {
	if ( isset( $_POST['reset_api_key'] ) && ( $_POST['reset_api_key'] == 'true' ) ) {
		if ( $_POST['activation_name'] != null ) {
			$target = "http://instinct.co.nz/wp-goldcart-api/api_register.php?name=" . $_POST['activation_name'] . "&key=" . $_POST['activation_key'] . "&url=" . get_option( 'siteurl' ) . "";

			$remote_access_fail = false;
			$useragent = 'WP e-Commerce plugin';

			$activation_name = urlencode( $_POST['activation_name'] );
			$activation_key = urlencode( $_POST['activation_key'] );
			$activation_state = update_option( 'activation_state', "false" );

			$siteurl = urlencode( get_option( 'siteurl' ) );
			$request = '';

			$http_request = "GET /wp-goldcart-api/api_register.php?name=$activation_name&key=&url=$siteurl HTTP/1.0\r\n";
			$http_request .= "Host: instinct.co.nz\r\n";
			$http_request .= "Content-Type: application/x-www-form-urlencoded; charset=" . get_option( 'blog_charset' ) . "\r\n";
			$http_request .= "Content-Length: " . strlen( $request ) . "\r\n";
			$http_request .= "User-Agent: $useragent\r\n";
			$http_request .= "\r\n";
			$http_request .= $request;

			$response = '';

			if ( false != ( $fs = @fsockopen( 'instinct.co.nz', 80, $errno, $errstr, 10 ) ) ) {
				fwrite( $fs, $http_request );

				while ( !feof( $fs ) )
					$response .= fgets( $fs, 1160 ); // One TCP-IP packet

					fclose( $fs );
			}

			$response = explode( "\r\n\r\n", $response, 2 );
			$returned_value = (int)trim( $response[1] );

			update_option( 'activation_name', '' );
			update_option( 'activation_key', '' );

			echo "<div class='updated'><p align='center'>" . esc_html__( 'Your API key has been Reset', 'wpsc' ) . "</p></div>";
		}
	}
}

add_action( 'wpsc_gold_module_activation', 'wpsc_reset_api_key' );

?>