<?php

function wpsc_display_upgrades_page() {

	do_action( 'wpsc_gold_module_activation' ); ?>

	<div class='wrap'>
		<div class='metabox-holder wpsc_gold_side'>
			<strong><?php _e( 'WP e-Commerce Upgrades', 'wpsc' ); ?></strong><br />
			<span><?php _e( 'Add more functionality to your e-Commerce site. Prices may be subject to change.', 'wpsc' ); ?><input type='button' class='button-primary' onclick='window.open ("http://getshopped.org/extend/premium-upgrades/","mywindow"); ' value='Buy Now' id='visitInstinct' name='visitInstinct' /></span>

			<br />
			<div class='wpsc_gold_module'>
				<br />
				<a href="http://getshopped.org/extend/premium-upgrades/premium-upgrades/gold-cart-plugin/" target="_blank"><strong><?php _e( 'Pure Gold', 'wpsc' ); ?></strong></a>
				<p class='wpsc_gold_text'>Add product search, multiple image upload, gallery view, Grid View and multiple payment gateway options to your shop</p>
				<span class='wpsc_gold_info'>$40</span>
			</div>

			<div class='wpsc_gold_module'>
				<br />
				<a href="http://getshopped.org/extend/premium-upgrades/premium-upgrades/dropshop-2010/" target="_blank"><strong><?php _e( 'DropShop', 'wpsc' ); ?></strong></a>
				<p class='wpsc_gold_text'>Impress your customers with our AJAX powered DropShop that lets your customers drag and drop products into their shopping cart</p>
				<span class='wpsc_gold_info'>$75</span>
			</div>

			<div class='wpsc_gold_module'>
				<br />
				<a href="http://getshopped.org/extend/premium-upgrades/premium-upgrades/mp3-audio-player/" target="_blank"><strong><?php _e( 'MP3 Player', 'wpsc' ); ?></strong></a>
				<p class='wpsc_gold_text'>Adding this module lets you upload and manage MP3 preview files that can be associated with your digital downloads.</p>
				<span class='wpsc_gold_info'>$10</span>
			</div>

			<div class='wpsc_gold_module'>
				<br />
				<a href="http://getshopped.org/extend/premium-upgrades/premium-upgrades/members-only-module/" target="_blank"><strong><?php _e( 'Members Only Module', 'wpsc' ); ?></strong></a>
				<p class='wpsc_gold_text'>The Members modules lets you set private pages and posts that are only available to paying customers. Activating this module also adds a new option under "WordPress Users" menu that shop owners can use to manage their subscribers.</p>
				<span class='wpsc_gold_info'>$25</span>
			</div>

			<div class='wpsc_gold_module'>
				<br />
				<a href="http://getshopped.org/extend/premium-upgrades/premium-upgrades/product-slider-2010/" target="_blank"><strong><?php _e( 'Product Slider', 'wpsc' ); ?> </strong></a>
				<p class='wpsc_gold_text'>Display your products in a new and fancy way using the "Product Slider" module.</p>
				<span class='wpsc_gold_info'>$25</span>
			</div>

			<div class='wpsc_gold_module'>
				<br />
				<a href="http://getshopped.org/extend/premium-upgrades/premium-upgrades/nextgen-gallery-buy-now1/" target="_blank"><strong><?php _e( 'NextGen Gallery Buy Now Buttons', 'wpsc' ); ?> </strong></a>
				<p class='wpsc_gold_text'>Make your Online photo gallery into an e-Commerce solution.</p>
				<span class='wpsc_gold_info'>$10</span>
			</div>
		</div>

		<h2><?php _e( 'Upgrades', 'wpsc' ); ?></h2>
		<div class='wpsc_gold_float'>
			<p><?php _e( 'Enter your API Username and API Key below.', 'wpsc' ); ?></p>
			<p><a href="http://getshopped.org/resources/docs/installation/gold-files/"><?php _e( 'For more information visit our documentation page.', 'wpsc' ); ?></a></p>

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
									<?php _e( "You don't have any Upgrades yet!", 'wpsc' ); ?>
								</p>
							</div>

							<h2><?php _e( 'API Key Reset', 'wpsc' ); ?></h2>
							<div class='form-wrap' >
								<p>
									<?php _e( 'Enter your API name and key to release it from an old site that you no longer use.', 'wpsc' ); ?> <br /><br />
								</p>
							</div>
						</div>

						<div class='postbox'>
							<h3 class='hndle'><?php _e( 'API Key Reset', 'wpsc' ); ?></h3>
							<p>
								<label for='activation_name'><?php _e( 'Name:', 'wpsc' ); ?></label>
								<input class='text' type='text' size='40' value='<?php echo get_option( 'activation_name' ); ?>' name='activation_name' id='activation_name' />
							</p>
							<p>
								<label for='activation_key'><?php _e( 'API Key:', 'wpsc' ); ?></label>
								<input class='text' type='text' size='40' value='<?php echo get_option( 'activation_key' ); ?>' name='activation_key' id='activation_key' />
							</p>
							<p>
								<input type='hidden' value='true' name='reset_api_key' />
								<input type='submit' class='button-primary' value='<?php _e( 'Reset API Key', 'wpsc' ); ?>' name='submit_values' />
							</p>
						</div>

					<?php } ?>
				</form>
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

			echo "<div class='updated'><p align='center'>" . __( 'Your API key has been Reset', 'wpsc' ) . "</p></div>";
		}
	}
}

add_action( 'wpsc_gold_module_activation', 'wpsc_reset_api_key' );

?>