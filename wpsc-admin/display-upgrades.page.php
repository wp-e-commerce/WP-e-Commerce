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
	$licenses = get_option( 'wpec_licenses_active_products', array() );
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
		foreach ( (array) $licenses as $license ) { $license_info = get_option( 'wpec_product_' . $license . '_license_active', array() ); ?>
			<?php do_action( 'wpec_license_individual_license', $license_info ); ?>
			<tr><td class="product_name column-product_name"><p><strong><?php echo esc_html( $license_info->item_name ); ?></strong></p></td>
			<td class="product_license column-product_license"><p><strong><?php echo esc_html( $license_info->license_key ); ?></strong></p></td>
			<td class="product_expiry column-product_expiry"><p><strong><?php if ( $license_info->expiration == 'lifetime' ) { _e('Lifetime', 'wp-e-commerce'); } else { echo esc_html( $license_info->expiration ); } ?></strong></p></td></tr>
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
function wpec_licenses_action_stuff() {
	//Activate a new Product License
	
	if( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	if ( isset( $_POST['product_license_key'] ) && $_POST['product_license_key'] == 'true' ) {
		if ( isset( $_POST['product_license'] ) && $_POST['product_license'] != '' ) {
			
			//Do stuff
			$params = array (
				'license'   			=> sanitize_text_field( $_POST['product_license'] ),
				'url'        			=> home_url()
			);
			
			$activation = false;
			if ( isset( $_POST['submit_values'] ) && $_POST['submit_values'] == 'submit_values' ) {
				$activation = true;
				$params['wpec_lic_action'] = 'activate_license';
			} elseif ( isset( $_POST['reset_values'] ) && $_POST['reset_values'] == 'reset_values' ) {
				$params['wpec_lic_action'] = 'deactivate_license';
			}
			
			$response = wp_remote_post(
				'https://wpecommerce.org/',
				array(
					'timeout'   => 15,
					'sslverify' => false,
					'body'      => $params
				)
			);
			
			// Make sure there are no errors
			if ( is_wp_error( $response ) ) {
				return;
			}
			
			// Decode license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			$active_licenses = get_option( 'wpec_licenses_active_products', array() );

			if( $license_data->success === true ) {
				if ( $activation ) {
					// Tell WordPress to look for updates
					set_site_transient( 'update_plugins', null );
					$active_licenses[] = $license_data->item_id;
					update_option( 'wpec_licenses_active_products', $active_licenses );
					update_option( 'wpec_product_' . $license_data->item_id . '_license_active', $license_data );
				} else {
					$key = array_search( $license_data->item_id, $active_licenses );
					unset( $active_licenses[ $key ] );
					update_option( 'wpec_licenses_active_products', $active_licenses );
					delete_option( 'wpec_product_' . $license_data->item_id . '_license_active' );
				}
				

				echo '<div class="updated"><p>'.esc_html( $license_data->message ).'</p></div>';
			} else {
				echo '<div class="error"><p>'.esc_html( $license_data->message ).'</p></div>';
			}
		}
	}
}
add_action( 'wpsc_upgrades_license_activation', 'wpec_licenses_action_stuff' );

function wpec_lic_weekly_license_check() {
	
		if( ! empty( $_POST['product_license_key'] ) ) {
			return; // Don't fire when saving settings
		}

		$active_licenses = get_option( 'wpec_licenses_active_products', array() );
		if( empty( $active_licenses ) ) {
			return;
		}
		
		foreach ( (array) $active_licenses as $license ) {
			$license_info = get_option( 'wpec_product_' . $license . '_license_active' );
			
			// data to send in our API request
			$api_params = array(
				'wpec_lic_action'=> 'check_license',
				'license' 	=> $license_info->license_key,
				'item_id' 	=> $license_info->item_id,
				'url'       => home_url()
			);

			// Call the API
			$response = wp_remote_post(
				'https://wpecommerce.org/',
				array(
					'timeout'   => 15,
					'sslverify' => false,
					'body'      => $api_params
				)
			);

			// make sure the response came back okay
			if ( is_wp_error( $response ) ) {
				return false;
			}

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			update_option( 'wpec_product_' . $license . '_license_active', $license_data );	
		}
}
add_action( 'wpsc_weekly_cron_task', 'wpec_lic_weekly_license_check' ); // For testing use admin_init

function wpec_license_notices() {
		if( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		$active_licenses = get_option( 'wpec_licenses_active_products', array() );
		if( empty( $active_licenses ) ) {
			return;
		}
		
		$messages = array();
		
		foreach ( (array) $active_licenses as $license ) {
			$license = get_option( 'wpec_product_' . $license . '_license_active' );
			if( is_object( $license ) && 'valid' !== $license->license && empty( $showed_invalid_message ) ) {
				if( isset( $_GET['page'] ) && 'wpsc-upgrades' !== $_GET['page'] ) {
					$messages[] = sprintf(
						__( 'You have invalid or expired license keys for WP eCommerce. Please go to the <a href="%s" title="Go to Licenses page">Licenses page</a> to correct this issue.', 'wp-e-commerce' ),
						admin_url( 'index.php?page=wpsc-upgrades' )
					);
					$showed_invalid_message = true;
				}
			}		
		}

		if( ! empty( $messages ) ) {
			foreach( $messages as $message ) {
				echo '<div class="error">';
					echo '<p>' . $message . '</p>';
				echo '</div>';
			}
		}	
}
add_action( 'admin_notices', 'wpec_license_notices' );
