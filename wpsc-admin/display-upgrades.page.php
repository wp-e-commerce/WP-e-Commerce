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
							<label for="activation_key"><?php _e( 'License Key ', 'wp-e-commerce' ); ?></label>
							<input type="text" id="activation_key" name="product_license" size="48" value="" class="text" />
						</p>
						<p>
							<input type="hidden" value="true" name="product_license_key" />
							<?php wp_nonce_field( 'wpec_license_actions', 'wpec_license_actions' ); ?>
							<?php submit_button( __( 'Register License', 'wp-e-commerce' ), 'primary', 'submit_values', false ); ?>
							<?php submit_button( __( 'Reset License', 'wp-e-commerce' ), 'secondary', 'reset_values', false ); ?>
						</p>
						<?php
						echo '<p>' . sprintf(
							__( 'Enter your extension license keys here to receive updates for purchased extensions. If your license key has expired, please <a href="%s" target="_blank">renew your license</a>.', 'wp-e-commerce' ),
							'https://docs.wpecommerce.org/article/36-renewing-licenses'
						) . '</p>';
						?>						
						<p>
							<?php _e( 'API keys purchased prior to November 6, 2015 will not work.', 'wp-e-commerce' ); ?>
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
	$licenses = get_option( 'wpec_licenses_registered_addons', array() );
	?>
	<table class="wp-list-table widefat striped">
			<thead>
				<tr>
					<th scope="col" id="product_name" class="manage-column column-product_name column-primary"><?php _e('Product Name', 'wp-e-commerce'); ?></th>
					<th scope="col" id="product_license" class="manage-column column-product_license"><?php _e('License Key', 'wp-e-commerce'); ?></th>
					<th scope="col" id="product_status" class="manage-column column-product_status"><?php _e('License status', 'wp-e-commerce'); ?></th>
					<th scope="col" id="product_expiry" class="manage-column column-product_expiry"><?php _e('License Expiration', 'wp-e-commerce'); ?></th>
				</tr>
			</thead>
			<tbody id="the-list">
	<?php
	if ( ! empty( $licenses ) ) {
		foreach ( $licenses as $license ) {?>
			<tr><td class="product_name column-product_name"><p><strong><?php echo esc_html( $license['name'] ); ?></strong></p></td>
			<td class="product_license column-product_license"><p><strong><?php echo esc_html( $license['key'] ); ?></strong></p></td>
			<td class="product_status column-product_status"><p><strong><?php echo esc_html( $license['status'] ); ?></strong></p></td>
			<td class="product_expiry column-product_expiry"><p><strong><?php echo esc_html( $license['expire'] ); ?></strong></p></td></tr>
		<?php }
	} else {
		?>
		<tr class="no-items"><td class="colspanchange" colspan="4"><p><?php _e('No licenses found.', 'wp-e-commerce'); ?></p></td></tr>
	<?php
	}
	?>
	</tbody>
	</table>
		<?php
}

/**
 * Activate premium plugins
 */
function wpec_licenses_action_stuff() {
	//Activate a new Product License

	if( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_POST['product_license_key'] ) && $_POST['product_license_key'] == 'true' ) {
		if ( isset( $_POST['product_license'] ) && $_POST['product_license'] != '' ) {

			if( ! check_admin_referer( 'wpec_license_actions', 'wpec_license_actions' ) ) {
				return;
			}

			// data to send in our API request
			$api_params = array(
				'license'    => sanitize_text_field( $_POST['product_license'] ),
				'url'        => home_url()
			);

			$activation = false;
			if ( isset( $_POST['submit_values'] ) && $_POST['submit_values'] == __( 'Register License', 'wp-e-commerce' ) ) {
				$activation = true;
				$api_params['edd_action'] = 'activate_license';
			} elseif ( isset( $_POST['reset_values'] ) && $_POST['reset_values'] == __( 'Reset License', 'wp-e-commerce' ) ) {
				$api_params['edd_action'] = 'deactivate_license';
			}

			// Call the custom API.
			$response = wp_remote_post( 'https://wpecommerce.org', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$message =  ( is_wp_error( $response ) && ! empty( $response->get_error_message() ) ) ? $response->get_error_message() : __( 'An error occurred, please try again.' );
			} else {
				$license_data = json_decode( wp_remote_retrieve_body( $response ) );
				$active_licenses = get_option( 'wpec_licenses_registered_addons', array() );

				if ( $activation ) {
					//This is a activation
					if ( false === $license_data->success ) {
						switch( $license_data->error ) {
							case 'expired' :
								$message = sprintf(
									__( 'Your license key expired on %s. ' ),
									date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
								);
								break;
							case 'revoked' :
								$message = __( 'Your license key has been disabled.' );
								break;
							case 'missing' :
								$message = __( 'Invalid license.' );
								break;
							case 'invalid' :
							case 'site_inactive' :
								$message = __( 'Your license is not active for this URL.' );
								break;
							case 'item_name_mismatch' :
								$message = __( 'This appears to be an invalid license key.' );
								break;
							case 'no_activations_left':
								$message = __( 'Your license key has reached its activation limit.' );
								break;
							default :
								$message = __( 'An error occurred, please try again.' );
								break;
						}
					}

					// Check if anything passed on a message constituting a failure
					if ( empty( $message ) ) {
						// $license_data->license will be either "valid" or "invalid"
						$active_licenses[$license_data->download_id] = array(
							'status' => $license_data->license,
							'expire' => $license_data->expires,
							'key'    => $license_data->license_key,
							'name'   => $license_data->download_name,
							'download' => $license_data->download_id,
						);
						$message = __( 'License has been activated.' );
					}
				} else {
					//This is a deactivation
					if( $license_data->license == 'deactivated' ) {
						unset( $active_licenses[$license_data->download_id] );
						$message = __( 'License has been deactivated.' );
					} else {
						$message = __( 'An error occurred. Please contact support.' );
					}
				}

				update_option( 'wpec_licenses_registered_addons', $active_licenses );
			}

			if ( ! empty( $message ) ) {
				echo '<div class="updated"><p>' . esc_html( $message ).'</p></div>';
			}
		}
	}
}
add_action( 'wpsc_upgrades_license_activation', 'wpec_licenses_action_stuff' );
