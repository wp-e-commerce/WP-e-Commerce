<?php
class WPSC_Settings_Tab_Shipping extends WPSC_Settings_Tab {
	public function __construct() {
		parent::__construct();

		if ( isset( $_REQUEST['shipping_module_id'] ) )
			update_user_option( get_current_user_id(), 'wpsc_settings_selected_shipping_module', $_REQUEST['shipping_module_id'] );

		add_action( 'admin_notices', array( $this, 'no_shipping_notice' ) );
	}

	/**
	 * Hooks shipping notice into admin_notice if shipping is enabled but no shipping methods are selected
	 *
	 * @since 3.8.9
	 * @return null
	 */
	public function no_shipping_notice() {
		if ( ! empty( $_GET['shipping_disabled'] ) ) {
		?>

			<div class="error fade">
				<p><?php _e( '<strong>Shipping has been disabled.</strong> You enabled shipping but none of the shipping methods were selected.  Please re-enable shipping, select and configure a shipping method, and then update your settings.', 'wpsc' ); ?></p>
			</div>

		<?php
		}
	}

	public function callback_submit_options() {
		global $wpsc_shipping_modules;

		foreach ( $wpsc_shipping_modules as $shipping ) {
			if ( is_object( $shipping ) )
				$shipping->submit_form();
		}

		//This is for submitting shipping details to the shipping module
		if ( ! isset( $_POST['update_gateways'] ) )
			$_POST['update_gateways'] = '';

		if ( ! isset( $_POST['custom_shipping_options'] ) )
			$_POST['custom_shipping_options'] = null;

		update_option( 'custom_shipping_options', $_POST['custom_shipping_options'] );

		$shipadd = 0;
		foreach ( $wpsc_shipping_modules as $shipping ) {
			foreach ( (array) $_POST['custom_shipping_options'] as $shippingoption ) {
				if ( $shipping->getInternalName() == $shippingoption ) {
					$shipadd++;
				}
			}
		}

		if ( ! get_option( 'do_not_use_shipping' ) && ! get_option( 'custom_shipping_options' ) && ! ( bool ) get_option( 'shipwire' ) ) {
			update_option( 'do_not_use_shipping', '1' );
			return array( 'shipping_disabled' => 1 );
		} else {
			$_SERVER['REQUEST_URI'] = remove_query_arg( 'shipping_disabled' );
		}
	}

	public function display_shipping_module_settings_form( $selected_module_id = null ) {
		global $wpsc_shipping_modules;
		if ( ! $selected_module_id ) {
			$selected_module_id = (string) get_user_option( 'wpsc_settings_selected_shipping_module', get_current_user_id() );
		}

		$found_selected_module = array_key_exists( $selected_module_id, $wpsc_shipping_modules );

		if ( $found_selected_module ) {
			$selected_module = $wpsc_shipping_modules[$selected_module_id];
			$title = $selected_module->getName();
			$content = apply_filters( 'wpsc_shipping_module_settings_form', $selected_module->getForm(), $selected_module );
		} else {
			$title = __( 'Edit Shipping Module Settings', 'wpsc' );
			$content = __( 'To configure a shipping module select one on the left.', 'wpsc' );
		}

		?>
			<div id='wpsc_shipping_settings_<?php esc_attr_e( $selected_module_id ); ?>_form' class='shipping-module-settings-form'>
				<table class='form-table'>
					<?php echo $content; ?>
				</table>
				<table class='form-table'>
					<tr><td colspan='2'>
						<p class="submit inline-edit-save">
							<a class="button edit-shipping-module-cancel" title="<?php esc_attr_e( "Cancel editing this shipping calculator's settings", 'wpsc' ) ?>"><?php esc_html_e( "Cancel", 'wpsc' ); ?></a>
							<input type="submit" name="submit" class="button button-primary edit-shipping-module-update" value='<?php _e( "Update &raquo;", 'wpsc' ); ?>'>
						</p>
					</td></tr>
				</table>
			</div>
		<?php
	}

	private function get_shipping_module_url( $shipping ) {
		$location = ( isset( $_REQUEST['current_url'] ) ? $_REQUEST['current_url'] : $_SERVER['REQUEST_URI'] );
		$location = add_query_arg( array(
			'tab'                => 'shipping',
			'page'               => 'wpsc-settings',
			'shipping_module_id' => $shipping->getInternalName(),
		), $location );
		$location .= '#wpsc-shipping-module-options';
		return $location;
	}

	public function display() {
		global $wpdb, $wpsc_shipping_modules, $external_shipping_modules, $internal_shipping_modules;

		// sort into external and internal arrays.
		foreach ( $GLOBALS['wpsc_shipping_modules'] as $key => $module ) {
			if ( empty( $module ) )
				continue;

			if ( isset( $module->is_external ) && $module->is_external ) {
				$external_shipping_modules[$key] = $module;
			} else {
				$internal_shipping_modules[$key] = $module;
			}
		}

		?>

		<h3><?php esc_html_e( 'Shipping Settings', 'wpsc'); ?></h3>
		<input type='hidden' name='shipping_submits' value='true' />
		<?php wp_nonce_field( 'update-options', 'wpsc-update-options' ); ?>
		<input type='hidden' name='wpsc_admin_action' value='submit_options' />
		<table class='form-table'>
			<?php
				/* wpsc_setting_page_update_notification displays the wordpress styled notifications */
				wpsc_settings_page_update_notification();
			?>
			<tr>
				<th scope="row"><?php _e( 'Use Shipping', 'wpsc' ); ?></th>
				<td>
					<input type='hidden' value='1' name='wpsc_options[do_not_use_shipping]' />
					<input type='checkbox' value='0' name='wpsc_options[do_not_use_shipping]' id='do_not_use_shipping' <?php checked( '0',  get_option( 'do_not_use_shipping' ) ); ?> /> <label for='do_not_use_shipping'><?php _e( 'Enable Shipping settings', 'wpsc' ); ?></label>
					<p class='description'><?php esc_html_e( 'If you are only selling digital downloads, you should turn this off.', 'wpsc' ); ?></p>
				</td>
			</tr>

			<tr>
				<th><?php esc_html_e( 'Shipping Origin City', 'wpsc' ); ?></th>
				<td>
					<input type='text' name='wpsc_options[base_city]' value='<?php esc_attr_e( get_option( 'base_city' ) ); ?>' />
					<p class='description'><?php esc_html_e( 'The name of the city where you fulfill and ship orders from. This enables us to give your customers more accurate shipping pricing.', 'wpsc' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Shipping Origin Zipcode/Postcode', 'wpsc' ); ?></th>
				<td>
					<input type='text' name='wpsc_options[base_zipcode]' value='<?php esc_attr_e( get_option( 'base_zipcode' ) ); ?>' />
					<p class='description'>
						<?php esc_html_e( 'The zipcode/postcode for where you fulfill and ship orders from.', 'wpsc' ); ?><br />
						<?php esc_html_e( 'If you are based in the United States then this field is required in order for the UPS and USPS Shipping Calculators to work.', 'wpsc' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Shipwire', 'wpsc' ); ?><span style='color: red;'></span></th>
				<td>
					<input type='hidden' value='0' name='wpsc_options[shipwire]' />
					<input type='checkbox' onclick='jQuery("#wpsc_shipwire_setting").toggle( jQuery(this).prop("checked") );' value='1' name='wpsc_options[shipwire]' id='shipwire' <?php checked( '1',  get_option( 'shipwire' ) ); ?> />
					<label for='shipwire'><?php _e( 'Enable Shipwire Integration', 'wpsc' ); ?></label>
					<p class='description'><?php printf( __( '<a href="%1$s" target="_blank">Shipwire</a> provide e-commerce fulfillment warehouses. WP e-Commerce can integrate stock inventory and shipping tracking with their service.', 'wpsc' ), 'http://www.shipwire.com/' ); ?></p>
				</td>
			</tr>
			<?php
				switch ( get_option( 'shipwire' ) ) {
					case 1:
						$shipwire_settings = '';
						break;

					case 0:
					default:
						$shipwire_settings = 'style="display: none;"';
						break;
				}
			?>
			<tr id='wpsc_shipwire_setting' <?php echo $shipwire_settings; ?>>
				<th>&nbsp;</th>
				<td>
					<table>
						<tr>
							<th><?php esc_html_e( 'Shipwire Email', 'wpsc' ); ?></th>
							<td><input type="text" name='wpsc_options[shipwireemail]' value="<?php esc_attr_e( get_option( 'shipwireemail' ) ); ?>" /></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Shipwire Password', 'wpsc' ); ?></th>
							<td><input type="text" name='wpsc_options[shipwirepassword]' value="<?php esc_attr_e( get_option( 'shipwirepassword' ) ); ?>" /></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Use Test Server?', 'wpsc' ); ?></th>
							<td><input type="checkbox" name='wpsc_options[shipwire_test_server]' value="0" <?php checked( '1',  get_option( 'shipwire_test_server', '0' ) ); ?> /></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Force Sync with Shipwire', 'wpsc' ); ?></th>
							<td>
								<a class="shipwire_sync button"><?php esc_html_e( 'Update Tracking and Inventory', 'wpsc' ); ?></a>
								<img src="<?php echo esc_url( wpsc_get_ajax_spinner() ); ?>" class="ajax-feedback" title="" alt="" />
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<?php
				WPSC_Countries::currency_data( get_option( 'currency_type' ) , true );
				if ( $currency_data['symbol'] != '' ) {
					$currency_sign = $currency_data['symbol_html'];
				} else {
					$currency_sign = $currency_data['code'];
				}
			?>
			<tr>
				<th><?php _e( 'Free Shipping Discount', 'wpsc' ); ?></th>
				<td>
					<?php
						if ( get_option( 'shipping_discount' ) == 1 ) {
							$shipping_discount_settings = 'style=\'display: block;\'';
						} else {
							$shipping_discount_settings = '';
						}
					?>
					<input type='hidden' value='0' name='wpsc_options[shipping_discount]' />
					<input type='checkbox' onclick='jQuery("#shipping_discount_value").toggle( jQuery(this).prop("checked") );' value='1' name='wpsc_options[shipping_discount]' id='shipping_discount' <?php checked( '1',  get_option( 'shipping_discount' ) ); ?> />
					<label for='shipping_discount'><?php _e( 'Enable Free Shipping Discount', 'wpsc' ); ?></label>

				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td colspan="2">
					<?php
						$value = esc_attr( get_option( 'shipping_discount_value' ) );
					?>
					<div <?php echo $shipping_discount_settings; ?> id='shipping_discount_value'>

					<?php printf( __( 'Sales over or equal to %1$s<input type="text" size="6" name="wpsc_options[shipping_discount_value]" value="%2$s" id="shipping_discount_value" /> will receive free shipping.', 'wpsc' ), $currency_sign, $value ); ?>
					</div>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save Changes' ) ); ?>

		<h3><?php _e( 'Shipping Modules', 'wpsc' ) ?></h3>
		<p class='description'><?php _e( 'To enable shipping in WP e-Commerce you must select which shipping methods you want to enable on your site.', 'wpsc' ); ?></p>
		<p class='description'>
			<strong><?php _e( 'Tip', 'wpsc' ); ?></strong>:
			<?php printf( __( 'For fixed-price shipping options such as "Pickup - $0, Overnight - $10, Same day - $20, etc.", install our free <a href="%1$s">Fixed Rate Shipping</a> plugin.', 'wpsc' ), 'http://wordpress.org/extend/plugins/wp-e-commerce-fixed-rate-shipping/' ); ?>
		</p>

		<h4><?php _e( 'Internal Shipping Calculators', 'wpsc' ); ?></h4>
		<table id='wpsc-shipping-options-internal' class='wpsc-edit-module-options wp-list-table widefat plugins'>
			<thead>
				<tr>
					<th scope="col" id="wpsc-shipping-options-internal-active" class="manage-column"><?php _e( 'Active', 'wpsc' ); ?></th>
					<th scope="col" id="wpsc-shipping-options-internal-name" class="manage-column column-name"><?php _e( 'Shipping Calculator', 'wpsc' ); ?></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th scope="col" id="wpsc-shipping-options-internal-active" class="manage-column"><?php _e( 'Active', 'wpsc' ); ?></th>
					<th scope="col" id="wpsc-shipping-options-internal-name" class="manage-column column-name"><?php _e( 'Shipping Calculator', 'wpsc' ); ?></th>
				</tr>
			</tfoot>
			<tbody>
				<?php
					foreach ( $internal_shipping_modules as $shipping ) {
						$force = ( $shipping->getInternalName() === (string) get_user_option( 'wpsc_settings_selected_shipping_module', get_current_user_id() ) );
						$this->shipping_list_item( $shipping, $force );
					}
				?>
			</tbody>
		</table>
		<?php submit_button( __( 'Save Changes' ) ); ?>

		<h4><?php _e( 'External Shipping Calculators', 'wpsc' ); ?></h4>
		<?php if ( ! function_exists( 'curl_init' ) ) : ?>
			<p style='color: red; font-size:8pt; line-height:10pt;'>
				<?php _e( 'The following shipping modules all need cURL which is not installed on this server, you may need to contact your web hosting provider to get it set up. ', 'wpsc' ); ?>
			</p>
		<?php endif; ?>
		<table id='wpsc-shipping-options-external' class='wpsc-edit-module-options wp-list-table widefat plugins'>
			<thead>
				<tr>
					<th scope="col" id="wpsc-shipping-options-external-active" class="manage-column"><?php _e( 'Active', 'wpsc' ); ?></th>
					<th scope="col" id="wpsc-shipping-options-external-name" class="manage-column column-name"><?php _e( 'Shipping Calculator', 'wpsc' ); ?></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th scope="col" id="wpsc-shipping-options-external-active" class="manage-column"><?php _e( 'Active', 'wpsc' ); ?></th>
					<th scope="col" id="wpsc-shipping-options-external-name" class="manage-column column-name"><?php _e( 'Shipping Calculator', 'wpsc' ); ?></th>
				</tr>
			</tfoot>
			<tbody>
				<?php
					foreach ( $external_shipping_modules as $shipping ) {
						$force = ( $shipping->getInternalName() === (string) get_user_option( 'wpsc_settings_selected_shipping_module', get_current_user_id() ) );
						$this->shipping_list_item( $shipping, $force );
					}
				?>
			</tbody>
		</table>
		<?php
	}

	private function shipping_list_item ( $shipping, $force ) {
		//get shipping options that are selected
		$selected_shippings = get_option( 'custom_shipping_options' );

		$shipping->checked = is_object( $shipping ) && in_array( $shipping->getInternalName(), (array) $selected_shippings );
		$shipping->active  = $shipping->checked ? 'active' : 'inactive';
		$shipping->hidden  = $force             ? ''       : "style='display: none;'";
		$shipping->disabled = isset( $shipping->requires_curl ) && $shipping->requires_curl && ! function_exists( 'curl_init' ) ;

		?>
			<tr class="wpsc-select-shipping <?php echo $shipping->active; ?>" data-shipping-id="<?php echo esc_attr( $shipping->getInternalName() ); ?>" id="shipping_list_item_<?php echo $shipping->getInternalName();?>">
				<th scope="row" class="check-column">
					<input name='custom_shipping_options[]' <?php disabled( $shipping->disabled ); ?> <?php checked( $shipping->checked ); ?> type='checkbox' value='<?php echo $shipping->getInternalName(); ?>' id='<?php echo $shipping->getInternalName(); ?>_id' />
				</th>
				<td class="plugin-title">
					<label for='<?php echo $shipping->getInternalName(); ?>_id'><strong><?php echo $shipping->getName(); ?></strong></label>
					<div class="row-actions-visible">
						<span class="edit">
							<a class='edit-shipping-module' data-module-id="<?php echo $shipping->getInternalName(); ?>" title="<?php esc_attr_e( 'Edit this Shipping Module', 'wpsc' ); ?>" href='<?php echo esc_url( $this->get_shipping_module_url( $shipping ) ); ?>'><?php _ex( 'Settings', 'Shipping modules link to individual settings', 'wpsc' ); ?>
							<img src="<?php echo esc_url( wpsc_get_ajax_spinner() ); ?>" class="ajax-feedback" title="" alt="" />
						</span>
					</div>
				</td>
			</tr>
			<tr id="wpsc_shipping_settings_<?php echo esc_attr( $shipping->getInternalName() ); ?>" data-shipping-id="<?php echo esc_attr( $shipping->getInternalName() ); ?>" class='wpsc-select-shipping <?php echo $shipping->active; ?>' <?php echo $shipping->hidden; ?> >
				<td colspan="3" id="wpsc_shipping_settings_<?php echo esc_attr( $shipping->getInternalName() ); ?>_container">
					<?php $this->display_shipping_module_settings_form( $shipping->getInternalName() ); ?>
				</td>
			</tr>
		<?php
	}
}
