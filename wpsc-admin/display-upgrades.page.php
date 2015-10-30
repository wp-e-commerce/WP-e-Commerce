<?php
function wpsc_display_upgrades_page() {

	do_action( 'wpsc_upgrades_license_activation' ); ?>

	<div class='wrap'>
		<div class='metabox-holder wpsc_gold_side'>
			<div class='metabox-holder'>
				<form method='post' id='product_license_box' action=''>
					<div class="postbox">
						<h3 class="hndle"><?php _e( 'Product License Registration', 'wp-e-commerce' );?></h3>
						<p>
							<label for="activation_key"><?php _e( 'License Key ', 'wp-e-commerce' ); ?>:</label>
							<input type="text" id="activation_key" name="product_license" size="48" value="" class="text" />
						</p>
						<p>
							<input type="hidden" value="true" name="product_license_key" />
							<button type="submit" name="submit_values" value="submit_values" class="button button-primary"><?php _e( 'Register License', 'wp-e-commerce' ); ?></button>
							<button type="submit" name="reset_values" value="reset_values"><?php _e( 'Reset License', 'wp-e-commerce' ); ?></button>
						</p>
						<p>
							<?php _e( 'In order to receive automatic plugin updates you need to Register your License for each product that provides one. ', 'wp-e-commerce' ); ?></br>
							<?php _e( 'Old API Keys will not work! ', 'wp-e-commerce' ); ?>
						</p>
					</div>
				</form>
			</div>
		</div>
	</div>
<?php
wpse_license_page_display_licenses();
}

function wpse_license_page_display_licenses () {
	$licenses = get_option( 'wpec_license_active_products', array() );
	?>
	<table class="wp-list-table widefat striped">
			<thead>
				<tr>
					<th scope="col" id="product_name" class="manage-column column-product_name column-primary"><?php _e('Product Name', 'wp-e-commerce'); ?></th>
					<th scope="col" id="product_license" class="manage-column column-product_license"><?php _e('License Key', 'wp-e-commerce'); ?></th>
					<th scope="col" id="product_expiry" class="manage-column column-product_expiry"><?php _e('License Expiration', 'wp-e-commerce'); ?></th>
				</tr>
			</thead>
			<tbody id="the-list">
	<?php
	if ( ! empty( $licenses ) ) {
		foreach ( (array) $licenses as $license ) { ?>
			<tr><td class="product_name column-product_name"><p><strong><?php echo esc_html( $license['name'] ); ?></strong></p></td>
			<td class="product_license column-product_license"><p><strong><?php echo esc_html( $license['license'] ); ?></strong></p></td>
			<td class="product_expiry column-product_expiry"><p><strong><?php echo esc_html( $license['expires'] ); ?></strong></p></td></tr>
		<?php }
	} else {
		?>
		<tr class="no-items"><td class="colspanchange" colspan="4"><p><?php _e('No Licenses found.', 'wp-e-commerce'); ?></p></td></tr>
	<?php
	}
	?>
	</tbody>
		<tfoot>
			<tr>
				<th scope="col" class="manage-column column-product_name column-primary"><?php _e('Product Name', 'wp-e-commerce'); ?></th>
				<th scope="col" class="manage-column column-product_license"><?php _e('License Key', 'wp-e-commerce'); ?></th>
				<th scope="col" class="manage-column column-product_expiry"><?php _e('License Expiration', 'wp-e-commerce'); ?></th>
			</tr>
		</tfoot>
		</table>
		<?php
}

/**
 * Activate Gold Cart plugin
 */
function wpsc_licenses_action_stuff() {
	//Activate a new Product License
	if ( isset( $_POST['product_license_key'] ) && $_POST['product_license_key'] == 'true' ) {
		if ( isset( $_POST['product_license'] ) && $_POST['product_license'] != '' ) {
			
			//Do stuff
			$url = "https://wpecommerce.org/wp-license-api/license_register.php";
			$params = array (
				'api'		=> 'v2',
				'key'		=> base64_encode( stripslashes( $_POST['product_license'] ) ),
				'url'		=> base64_encode( esc_url_raw( site_url() ) )
			);
			
			$args = array(
				'httpversion' => '1.0',
				'sslverify'	  => false,
				'timeout'	  => 15,
				'user-agent'  => 'WP eCommerce Licensing/' . get_bloginfo( 'url' ),	
			);
			
			if ( isset( $_POST['submit_values'] ) && $_POST['submit_values'] == 'submit_values' ) {
				$params['action'] = 'activate';
			} elseif ( isset( $_POST['reset_values'] ) && $_POST['reset_values'] == 'reset_values' ) {
				$params['action'] = 'deactivate';
			}
			
			$url = add_query_arg( $params, $url );
			

			$response = json_decode( wp_remote_retrieve_body( wp_remote_get( $url, $args ) ) );

			$licenses = get_option( 'wpec_license_active_products', array() );
			
			if ( 'activated' === $response->status) {
				
				$licenses[] = array(
					'tag'		=> $response->fileid,
					'name'		=> $response->product,
					'license'	=> $response->license,
					'expires'	=> $response->valid
				);
				
				update_option( 'wpec_license_active_products', $licenses );
				echo '<div class="updated"><p>'.esc_html( $response->message ).'</p></div>';

			} elseif ( 'deactivated' === $response->status ) {
				
				foreach ( $licenses as $key => $license ) {
					if ( in_array( $response->fileid, $license ) ) {
						unset( $licenses[$key] );
						array_values($licenses);
					}
				}
				
				update_option( 'wpec_license_active_products', $licenses );
				echo '<div class="updated"><p>'.esc_html( $response->message ).'</p></div>';
				
			} else {
				echo '<div class="error"><p>'.esc_html( $response->message ).'</p></div>';
			}
		}
	}
}
add_action( 'wpsc_upgrades_license_activation', 'wpsc_licenses_action_stuff' );
