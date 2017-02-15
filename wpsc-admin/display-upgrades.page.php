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
							<?php submit_button( __( 'Register License', 'wp-e-commerce' ), 'primary', 'submit_values', false ); ?>
							<?php submit_button( __( 'Reset License', 'wp-e-commerce' ), 'secondary', 'reset_values', false ); ?>
						</p>
						<?php
						echo '<p>' . sprintf(
							__( 'Enter your extension license keys here to receive updates for purchased extensions. If your license key has expired, please <a href="%s" target="_blank">renew your license</a>.', 'wp-e-commerce' ),
							'http://docs.wpecommerce.org/license-renewals/'
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
		<tr class="no-items"><td class="colspanchange" colspan="4"><p><?php _e('No licenses found.', 'wp-e-commerce'); ?></p></td></tr>
	<?php
	}
	?>
	</tbody>
		<tfoot>
			<tr>
				<th scope="col" class="manage-column column-product_name column-primary"><?php _e( 'Product Name', 'wp-e-commerce' ); ?></th>
				<th scope="col" class="manage-column column-product_license"><?php _e( 'License Key', 'wp-e-commerce' ); ?></th>
				<th scope="col" class="manage-column column-product_expiry"><?php _e( 'License Expiration', 'wp-e-commerce' ); ?></th>
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
			if ( isset( $_POST['submit_values'] ) && $_POST['submit_values'] == __( 'Register License', 'wp-e-commerce' ) ) {
				$activation = true;
				$params['wpec_lic_action'] = 'activate_license';
			} elseif ( isset( $_POST['reset_values'] ) && $_POST['reset_values'] == __( 'Reset License', 'wp-e-commerce' ) ) {
				$params['wpec_lic_action'] = 'deactivate_license';
			}

			$response = wp_safe_remote_post(
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

			if ( $license_data->success === true ) {
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


				echo '<div class="updated"><p>' . esc_html( $license_data->message ).'</p></div>';
			} else {
				echo '<div class="error"><p>' . esc_html( $license_data->message ).'</p></div>';
			}
		}
	}
}

add_action( 'wpsc_upgrades_license_activation', 'wpec_licenses_action_stuff' );

function wpec_license_notices() {
	static $showed_invalid_message;
	
	if ( ! current_user_can( 'manage_options' ) ) {
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
			$messages[] = sprintf(
				__( 'You have invalid or expired license keys for WP eCommerce. Please go to the <a href="%s" title="WPeC Licensing">WPeC Licensing</a> page to correct this issue.', 'wp-e-commerce' ),
				admin_url( 'index.php?page=wpsc-upgrades' )
			);
			$showed_invalid_message = true;
		}
	}

	if( ! empty( $messages ) ) {
		foreach( $messages as $message ) {
			echo '<div class="error"><p>' . $message . '</p></div>';
		}
	}
}
add_action( 'admin_notices', 'wpec_license_notices' );