<?php
function wpsc_display_upgrades_page() {

	do_action( 'wpsc_upgrades_license_activation' ); ?>

	<div class='wrap'>
		<div class='metabox-holder wpsc_gold_side'>
			<div class='metabox-holder'>
				<form method='post' id='product_license_box' action=''>
					<div class="postbox">
						<h3 class="hndle"><?php _e( 'Product License Registration', 'wpsc' );?></h3>
						<p>
							<label for="activation_key"><?php _e( 'License Key ', 'wpsc' ); ?>:</label>
							<input type="text" id="activation_key" name="product_license" size="48" value="" class="text" />
						</p>
						<p>
							<input type="hidden" value="true" name="product_license_key" />
							<button type="submit" name="submit_values" value="submit_values"><?php _e( 'Register License', 'wpsc' ); ?></button>
							<button type="submit" name="reset_values" value="reset_values"><?php _e( 'Reset License', 'wpsc' ); ?></button>
						</p>
						<p style='font-size:8pt; line-height:10pt; font-weight:bold;'>
							<?php _e( 'In order to receive automatic plugin updates you need to Register your License for each product that provides one. ', 'wpsc' ); ?></br>
							<?php _e( 'Old API Keys will not work! ', 'wpsc' ); ?>
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

	echo '<table class="wp-list-table widefat striped">
			<thead>
				<tr>
					<th scope="col" id="product_name" class="manage-column column-product_name column-primary">Product Name</th>
					<th scope="col" id="product_license" class="manage-column column-product_license">License Key</th>
					<th scope="col" id="product_expiry" class="manage-column column-product_expiry">License Expiration Date</th>
				</tr>
			</thead>
			<tbody id="the-list">';
	
	if ( ! empty( $licenses ) ) {
		foreach ( (array) $licenses as $license ) {
			
			echo '<tr><td class="product_name column-product_name"><p><strong>'.$license['name'].'</strong></p></td>';
			echo '<td class="product_license column-product_license"><p><strong>'.$license['license'].'</strong></p></td>';
			echo '<td class="product_expiry column-product_expiry"><p><strong>'.$license['expires'].'</strong></p></td></tr>';
		}
	} else {
		echo '<tr class="no-items"><td class="colspanchange" colspan="4"><p>No Licenses found.</p></td></tr>';
	}
	
	echo '</tbody>
		<tfoot>
			<tr>
				<th scope="col" class="manage-column column-product_name column-primary">Product Name</th>
				<th scope="col" class="manage-column column-product_license">License Key</th>
				<th scope="col" class="manage-column column-product_expiry">License Expiry Date</th>
			</tr>
		</tfoot>
		</table>';
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
				echo '<div class="updated"><p>'.$response->message.'</p></div>';

			} elseif ( 'deactivated' === $response->status ) {
				
				foreach ( $licenses as $key => $license ) {
					if ( in_array( $response->fileid, $license ) ) {
						unset( $licenses[$key] );
						array_values($licenses);
					}
				}
				
				update_option( 'wpec_license_active_products', $licenses );
				echo '<div class="updated"><p>'.$response->message.'</p></div>';
				
			} else {
				echo '<div class="error"><p>'.$response->message.'</p></div>';
			}
		}
	}
}
add_action( 'wpsc_upgrades_license_activation', 'wpsc_licenses_action_stuff' );
