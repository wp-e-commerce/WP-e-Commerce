<?php
class WPSC_Settings_Tab_Shipping extends WPSC_Settings_Tab
{
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
			foreach ( (array)$_POST['custom_shipping_options'] as $shippingoption ) {
				if ( $shipping->internal_name == $shippingoption ) {
					$shipadd++;
				}
			}
		}

		if ( ! get_option( 'do_not_use_shipping' ) && ! get_option( 'custom_shipping_options' ) ) {
			update_option( 'do_not_use_shipping', '1' );
			return array( 'shipping_disabled' => 1 );
		} else {
			$_SERVER['REQUEST_URI'] = remove_query_arg( 'shipping_disabled' );
		}
	}

	public function display_shipping_module_settings_form() {
		global $wpsc_shipping_modules;
		$classes = array( 'wpsc-module-settings' );
		$selected_module_id = (string) get_user_option( 'wpsc_settings_selected_shipping_module', get_current_user_id() );
		$found_selected_module = array_key_exists( $selected_module_id, $wpsc_shipping_modules );
		if ( $found_selected_module ) {
			$selected_module = $wpsc_shipping_modules[$selected_module_id];
			$title = $selected_module->name;
			$content = $selected_module->getForm();
			$classes[] = 'wpsc-shipping-module-settings-' . $selected_module_id;
		} else {
			$title = __( 'Edit Shipping Module Settings', 'wpsc' );
			$content = __( 'To configure a shipping module select one on the left.', 'wpsc' );
		}
		$classes = implode( ' ', $classes );
		?>
			<td id="wpsc-shipping-module-settings" class="<?php echo esc_attr( $classes ); ?>" rowspan='2'>
				<div class='postbox'>
					<h3 class='hndle'><?php echo esc_html( $title ); ?></h3>
					<div class='inside'>
						<table class='form-table'>
							<?php echo $content; ?>
						</table>
						<?php if ( $found_selected_module ): ?>
							<p class="submit">
								<input type="submit" value="<?php _e( 'Update &raquo;', 'wpsc' ); ?>" />
							</p>
						<?php endif; ?>
					</div>
				</div>
			</td>
		<?php
	}

	private function get_shipping_module_url( $shipping ) {
		$location = ( isset( $_REQUEST['current_url'] ) ? $_REQUEST['current_url'] : $_SERVER['REQUEST_URI'] );
		$location = add_query_arg( array(
			'tab'                => 'shipping',
			'page'               => 'wpsc-settings',
			'shipping_module_id' => $shipping->internal_name,
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

			if ( isset( $module->is_external ) && $module->is_external )
				$external_shipping_modules[$key] = $module;
			else
				$internal_shipping_modules[$key] = $module;
		}

		$currency_data = $wpdb->get_row( $wpdb->prepare( "SELECT `symbol`,`symbol_html`,`code` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id` = %d LIMIT 1", get_option( 'currency_type' ) ), ARRAY_A );
		if ( $currency_data['symbol'] != '' ) {
			$currency_sign = $currency_data['symbol_html'];
		} else {
			$currency_sign = $currency_data['code'];
		}
		//get shipping options that are selected
		$selected_shippings = get_option( 'custom_shipping_options' );
	?>
				<div class="metabox-holder">
						<input type='hidden' name='shipping_submits' value='true' />
						<?php wp_nonce_field( 'update-options', 'wpsc-update-options' ); ?>
						<input type='hidden' name='wpsc_admin_action' value='submit_options' />

	<?php

		if ( get_option( 'custom_gateway' ) == 1 ) {
			$custom_gateway_hide = "style='display:block;'";
			$custom_gateway1 = 'checked="checked"';
		} else {
			$custom_gateway_hide = "style='display:none;'";
			$custom_gateway2 = 'checked="checked"';
		}
					/* wpsc_setting_page_update_notification displays the wordpress styled notifications */
					wpsc_settings_page_update_notification(); ?>
						<div class='postbox'>
							<h3 class='hndle'><?php esc_html_e( 'General Settings', 'wpsc' ); ?></h3>
							<div class='inside'>

							<table class='wpsc_options form-table'>
								<tr>
									<th scope="row">
	<?php _e( 'Use Shipping', 'wpsc' ); ?>:
									</th>
									<td>
										<?php
										$do_not_use_shipping = get_option( 'do_not_use_shipping' );
										$do_not_use_shipping1 = "";
										$do_not_use_shipping2 = "";
										if( $do_not_use_shipping )
											$do_not_use_shipping1 = "checked ='checked'";
										else
											$do_not_use_shipping2 = "checked ='checked'";
										?>
										<input type='radio' value='0' name='wpsc_options[do_not_use_shipping]' id='do_not_use_shipping2' <?php echo $do_not_use_shipping2; ?> /> <label for='do_not_use_shipping2'><?php _e( 'Yes', 'wpsc' ); ?></label>&nbsp;
										<input type='radio' value='1' name='wpsc_options[do_not_use_shipping]' id='do_not_use_shipping1' <?php echo $do_not_use_shipping1; ?> /> <label for='do_not_use_shipping1'><?php _e( 'No', 'wpsc' ); ?></label><br />
										<?php esc_html_e( 'If you are only selling digital downloads, you should select no to disable the shipping on your site.', 'wpsc' ); ?>
									</td>
								</tr>

								<tr>
									<th><?php esc_html_e( 'Base City:', 'wpsc' ); ?></th>
									<td>
										<input type='text' name='wpsc_options[base_city]' value='<?php esc_attr_e( get_option( 'base_city' ) ); ?>' />
										<br /><?php esc_html_e( 'Please provide for more accurate rates', 'wpsc' ); ?>
									</td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Base Zipcode/Postcode:', 'wpsc' ); ?></th>
									<td>
										<input type='text' name='wpsc_options[base_zipcode]' value='<?php esc_attr_e( get_option( 'base_zipcode' ) ); ?>' />
										<br /><?php esc_html_e( 'If you are based in America then you need to set your own Zipcode for UPS and USPS to work. This should be the Zipcode for your Base of Operations.', 'wpsc' ); ?>
									</td>
								</tr>
										<tr>
											<th scope="row">
										<?php _e( 'Shipwire Settings', 'wpsc' ); ?><span style='color: red;'></span> :
									</th>
									<?php
										switch ( get_option( 'shipwire' ) ) {
											case 1:
												$shipwire_settings = 'style=\'display: block;\'';
												break;

											case 0:
											default:
												$shipwire_settings = '';
												break;
										}
									?>
									<td>
										<input type='radio' onclick='jQuery("#wpsc_shipwire_setting").show()' value='1' name='wpsc_options[shipwire]' id='shipwire1' <?php checked( '1',  get_option( 'shipwire' ) ); ?> /> <label for='shipwire1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
										<input type='radio' onclick='jQuery("#wpsc_shipwire_setting").hide()' value='0' name='wpsc_options[shipwire]' id='shipwire2' <?php checked( '0',  get_option( 'shipwire' ) ); ?> /> <label for='shipwire2'><?php _e( 'No', 'wpsc' ); ?></label>
										<div id='wpsc_shipwire_setting' <?php echo $shipwire_settings; ?>>
											<table>
												<tr><td><?php esc_html_e( 'Shipwire Email', 'wpsc' ); ?> :</td><td> <input type="text" name='wpsc_options[shipwireemail]' value="<?php esc_attr_e( get_option( 'shipwireemail' ) ); ?>" /></td></tr>
												<tr><td><?php esc_html_e( 'Shipwire Password', 'wpsc' ); ?> :</td><td><input type="text" name='wpsc_options[shipwirepassword]' value="<?php esc_attr_e( get_option( 'shipwirepassword' ) ); ?>" /></td></tr>
												<tr><td>
													<a class="shipwire_sync button"><?php esc_html_e( 'Update Tracking and Inventory', 'wpsc' ); ?></a>
													<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-feedback" title="" alt="" />
												</td></tr>
											</table>
										</div>
									</td>
								</tr>
								<tr>
									<th scope="row">
										<?php _e( 'Enable Free Shipping Discount', 'wpsc' ); ?>
									</th>
									<td>
										<?php
										if ( get_option( 'shipping_discount' ) == 1 ) {
											$selected2 = '';
											$selected1 = 'checked="checked"';
											$shipping_discount_settings = 'style=\'display: block;\'';
										} else {
											$selected2 = 'checked="checked"';
											$selected1 = '';
											$shipping_discount_settings = '';
										}
										?>
										<input type='radio' onclick='jQuery("#shipping_discount_value").show()' value='1' name='wpsc_options[shipping_discount]' id='shipping_discount1' <?php echo $selected1; ?> /> <label for='shipping_discount1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
										<input type='radio' onclick='jQuery("#shipping_discount_value").hide()' value='0' name='wpsc_options[shipping_discount]' id='shipping_discount2' <?php echo $selected2; ?> /> <label for='shipping_discount2'><?php _e( 'No', 'wpsc' ); ?></label>

									</td>
								</tr>
								<tr>
									<td>&nbsp;</td>
									<td colspan="2">
										<?php
										$value = esc_attr( get_option( 'shipping_discount_value' ) );
										?>
										<div <?php echo $shipping_discount_settings; ?> id='shipping_discount_value'>

										<?php printf( __( 'Sales over or equal to: %1$s<input type="text" size="6" name="wpsc_options[shipping_discount_value]" value="%2$s" id="shipping_discount_value" /> will receive free shipping.', 'wpsc' ), $currency_sign, $value ); ?>
										</div>
									</td>
								</tr>
							</table>
									</div>
										</div>
											<table id='wpsc-shipping-module-options' class='wpsc-edit-module-options'>
												<tr>
													<td class='select_gateway'>
													<a name="gateway_options"></a>
												<div class='postbox'>
													<h3 class='hndle'><?php _e( 'Shipping Modules', 'wpsc' ) ?></h3>
													<div class='inside'>

											<p>
										<?php _e( 'To enable shipping in WP e-Commerce you must select which shipping methods you want to enable on your site.<br /> If you want to use fixed-price shipping options like "Pickup - $0, Overnight - $10, Same day - $20, etc." you can download a WordPress plugin from plugins directory for <a href="http://wordpress.org/extend/plugins/wp-e-commerce-fixed-rate-shipping/">Simple shipping</a>. It will appear in the list as "Fixed rate".', 'wpsc' ); ?>
										</p>
										<br />
										<p>
											<strong><?php _e( 'Internal Shipping Calculators', 'wpsc' ); ?></strong>
										</p>
										<?php
											foreach ( $internal_shipping_modules as $shipping ) {

												$shipping->checked = '';
												if ( is_object( $shipping ) && in_array( $shipping->getInternalName(), (array)$selected_shippings ) )
													$shipping->checked = ' checked = "checked" ';
										?>

													<div class='wpsc_shipping_options'>
														<div class='wpsc-shipping-actions'>
													<span class="edit">
														<a class='edit-shipping-module' data-module-id="<?php echo $shipping->internal_name; ?>" title="<?php esc_attr_e( 'Edit this Shipping Module', 'wpsc' ); ?>" href='<?php echo esc_url( $this->get_shipping_module_url( $shipping ) ); ?>' style="cursor:pointer;"><?php _ex( 'Edit', 'Shipping modules link to individual settings', 'wpsc' ); ?></a>
														<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-feedback" title="" alt="" />
													</span>
												</div>

												<p><input name='custom_shipping_options[]' <?php echo $shipping->checked; ?> type='checkbox' value='<?php echo $shipping->internal_name; ?>' id='<?php echo $shipping->internal_name; ?>_id' /><label for='<?php echo $shipping->internal_name; ?>_id'> <?php echo $shipping->name; ?></label></p>
													</div>
											<?php }	?>
										<br />
										<p>
											<strong><?php _e( 'External Shipping Calculators', 'wpsc' ); ?></strong>
										<?php if ( ! function_exists( 'curl_init' ) ) {
	 ?>
													<br /><span style='color: red; font-size:8pt; line-height:10pt;'><?php _e( 'The following shipping modules all need cURL which is not installed on this server, you may need to contact your web hosting provider to get it set up. ', 'wpsc' ); ?></span>
										<?php } ?>
											</p>
										<?php
											// print the internal shipping methods
											foreach ( $external_shipping_modules as $shipping ) {
												$disabled = '';
												if ( isset( $shipping->requires_curl ) && $shipping->requires_curl && ! function_exists( 'curl_init' ) ) {
													$disabled = "disabled='disabled'";
												}
												$shipping->checked = '';
												if ( in_array( $shipping->getInternalName(), (array)$selected_shippings ) )
													$shipping->checked = " checked='checked' ";
										?>
											<div class='wpsc_shipping_options'>
												<div class="wpsc-shipping-actions">
													<span class="edit">
														<a class='edit-shipping-module' data-module-id="<?php echo $shipping->internal_name; ?>"  title="<?php esc_attr_e( 'Edit this Shipping Module', 'wpsc' ); ?>" href='<?php echo esc_url( $this->get_shipping_module_url( $shipping ) ); ?>' style="cursor:pointer;"><?php _ex( 'Edit', 'Shipping modules link to individual settings', 'wpsc' ); ?></a>
														<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-feedback" title="" alt="" />
													</span>
												</div>
												<p><input <?php echo $disabled; ?> name='custom_shipping_options[]' <?php echo $shipping->checked; ?> type='checkbox' value='<?php echo $shipping->internal_name; ?>' id='<?php echo $shipping->internal_name; ?>_id' /><label for='<?php echo $shipping->internal_name; ?>_id'> <?php echo $shipping->name; ?></label></p>
											</div>
											<?php } ?>
													<p class="submit">
														<input type='hidden' value='true' name='update_gateways' />
														<input type="submit" value="<?php _e( 'Update &raquo;', 'wpsc' ); ?>" />
													</p>
													</div>
												</div>
										</td>

										<?php $this->display_shipping_module_settings_form(); ?>
									</tr>
								</table>
						</div>
		<?php
	}
}