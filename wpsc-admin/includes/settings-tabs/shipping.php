<?php
class WPSC_Settings_Tab_Shipping extends WPSC_Settings_Tab
{
	public function __construct() {
		parent::__construct();

		if ( isset( $_REQUEST['shipping_module_id'] ) )
			update_user_option( get_current_user_id(), 'wpsc_settings_selected_shipping_module', $_REQUEST['shipping_module_id'] );
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
			if(empty($module))continue;
			if ( isset( $module->is_external ) && ($module->is_external == true) ) {
				$external_shipping_modules[$key] = $module;
			} else {
				$internal_shipping_modules[$key] = $module;
			}
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
							<h3 class='hndle'><?php _e( 'General Settings', 'wpsc' ); ?></h3>
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
										<?php _e( 'If you are only selling digital downloads, you should select no to disable the shipping on your site.', 'wpsc' ); ?>
									</td>
								</tr>

								<tr>
									<th><?php _e( 'Base City:', 'wpsc' ); ?></th>
									<td>
										<input type='text' name='wpsc_options[base_city]' value='<?php esc_attr_e( get_option( 'base_city' ) ); ?>' />
										<br /><?php _e( 'Please provide for more accurate rates', 'wpsc' ); ?>
									</td>
								</tr>
								<tr>
									<th><?php _e( 'Base Zipcode/Postcode:', 'wpsc' ); ?></th>
									<td>
										<input type='text' name='wpsc_options[base_zipcode]' value='<?php esc_attr_e( get_option( 'base_zipcode' ) ); ?>' />
										<br /><?php _e( 'If you are based in America then you need to set your own Zipcode for UPS and USPS to work. This should be the Zipcode for your Base of Operations.', 'wpsc' ); ?>
									</td>
								</tr>
								<?php
										$shipwire1 = "";
										$shipwire2 = "";
										switch ( get_option( 'shipwire' ) ) {
											case 1:
												$shipwire1 = "checked ='checked'";
												$shipwire_settings = 'style=\'display: block;\'';
												break;

											case 0:
											default:
												$shipwire2 = "checked ='checked'";
												$shipwire_settings = '';
												break;
										}
								?>

										<tr>
											<th scope="row">
										<?php _e( 'ShipWire Settings', 'wpsc' ); ?><span style='color: red;'></span> :
									</th>
									<td>
										<input type='radio' onclick='jQuery("#wpsc_shipwire_setting").show()' value='1' name='wpsc_options[shipwire]' id='shipwire1' <?php echo $shipwire1; ?> /> <label for='shipwire1'><?php _e( 'Yes', 'wpsc' ); ?></label> &nbsp;
										<input type='radio' onclick='jQuery("#wpsc_shipwire_setting").hide()' value='0' name='wpsc_options[shipwire]' id='shipwire2' <?php echo $shipwire2; ?> /> <label for='shipwire2'><?php _e( 'No', 'wpsc' ); ?></label>
										<?php
										$shipwireemail = esc_attr_e( get_option( "shipwireemail" ) );
										$shipwirepassword = esc_attr_e( get_option( "shipwirepassword" ) );
										?>
										<div id='wpsc_shipwire_setting' <?php echo $shipwire_settings; ?>>
											<table>
												<tr><td><?php _e( 'ShipWire Email', 'wpsc' ); ?> :</td><td> <input type="text" name='wpsc_options[shipwireemail]' value="<?php echo $shipwireemail; ?>" /></td></tr>
												<tr><td><?php _e( 'ShipWire Password', 'wpsc' ); ?> :</td><td><input type="text" name='wpsc_options[shipwirepassword]' value="<?php echo $shipwirepassword; ?>" /></td></tr>
												<tr><td><a onclick='shipwire_sync()' style="cursor:pointer;">Sync product</a></td></tr>
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
										$value = esc_attr ( get_option( 'shipping_discount_value' ) );
										?>
										<div <?php echo $shipping_discount_settings; ?> id='shipping_discount_value'>

							<?php printf(__('Sales over or equal to: %1$s<input type="text" size="6" name="wpsc_options[shipping_discount_value]" value="%2$s" id="shipping_discount_value" /> will receive free shipping.', 'wpsc'), $currency_sign, $value ); ?>
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
																<a class='edit-shipping-module' data-module-id="<?php echo $shipping->internal_name; ?>" title="Edit this Shipping Module" href='<?php echo esc_attr( $this->get_shipping_module_url( $shipping ) ); ?>' style="cursor:pointer;"><?php _e( 'Edit', 'wpsc' ); ?></a>
																<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-feedback" title="" alt="" />
													</span>
												</div>

												<p><input name='custom_shipping_options[]' <?php echo $shipping->checked; ?> type='checkbox' value='<?php echo $shipping->internal_name; ?>' id='<?php echo $shipping->internal_name; ?>_id' /><label for='<?php echo $shipping->internal_name; ?>_id'><?php echo $shipping->name; ?></label></p>
													</div>
											<?php }	?>
										<br />
										<p>
											<strong><?php _e( 'External Shipping Calculators', 'wpsc' ); ?></strong>
										<?php if ( !function_exists( 'curl_init' ) ) {
	 ?>
													<br /><span style='color: red; font-size:8pt; line-height:10pt;'><?php _e( 'The following shipping modules all need cURL which is not installed on this server, you may need to contact your web hosting provider to get it set up. ', 'wpsc' ); ?></span>
										<?php } ?>
											</p>
										<?php
											// print the internal shipping methods
											foreach ( $external_shipping_modules as $shipping ) {
												$disabled = '';
												if ( isset($shipping->requires_curl) && ($shipping->requires_curl == true) && !function_exists( 'curl_init' ) ) {
													$disabled = "disabled='disabled'";
												}
												$shipping->checked = '';
												if ( in_array( $shipping->getInternalName(), (array)$selected_shippings ) )
													$shipping->checked = " checked='checked' ";
										?>
											<div class='wpsc_shipping_options'>
												<div class="wpsc-shipping-actions">
											<span class="edit">
														<a class='edit-shipping-module' data-module-id="<?php echo $shipping->internal_name; ?>"  title="Edit this Shipping Module" href='<?php echo esc_attr( $this->get_shipping_module_url( $shipping ) ); ?>' style="cursor:pointer;"><?php _e( 'Edit' , 'wpsc' ); ?></a>
														<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-feedback" title="" alt="" />
															</span>
														</div>
														<p><input <?php echo $disabled; ?> name='custom_shipping_options[]' <?php echo $shipping->checked; ?> type='checkbox' value='<?php echo $shipping->internal_name; ?>' id='<?php echo $shipping->internal_name; ?>_id' /><label for='<?php echo $shipping->internal_name; ?>_id'><?php esc_attr_e( $shipping->name ); ?></label></p>
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