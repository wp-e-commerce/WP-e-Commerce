<?php

function wpsc_options_shipping() {
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
	$currency_data = $wpdb->get_row( "SELECT `symbol`,`symbol_html`,`code` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id`='" . get_option( 'currency_type' ) . "' LIMIT 1", ARRAY_A );
	if ( $currency_data['symbol'] != '' ) {
		$currency_sign = $currency_data['symbol_html'];
	} else {
		$currency_sign = $currency_data['code'];
	}
	//get shipping options that are selected
	$selected_shippings = get_option( 'custom_shipping_options' );
?>
	<form name='cart_options' id='cart_options' method='post' action='' class='wpsc_form_track'>

	<script type='text/javascript'>
		function selectgateway() {
			document.forms.shippingopt.submit();
		}
	</script>
		<div class="wrap">
			<div class="metabox-holder">
				<form name='shippingopt' method='post' id='shipping_options' action='' class='wpsc_form_track'>
					<input type='hidden' name='shipping_submits' value='true' />
<?php wp_nonce_field( 'update-options', 'wpsc-update-options' ); ?>
					<input type='hidden' name='wpsc_admin_action' value='submit_options' />

<?php

	if ( !isset( $_SESSION['previous_shipping_name'] ) )
		$_SESSION['previous_shipping_name'] = "";

	$shipping_data = wpsc_get_shipping_form( $_SESSION['previous_shipping_name'] );

	if ( get_option( 'custom_gateway' ) == 1 ) {
		$custom_gateway_hide = "style='display:block;'";
		$custom_gateway1 = 'checked="checked"';
	} else {
		$custom_gateway_hide = "style='display:none;'";
		$custom_gateway2 = 'checked="checked"';
	}
	if ( $shipping_data['has_submit_button'] == 0 )
		$update_button_css = 'style= "display: none;"';
	else
		$update_button_css = '';
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
							<tr>
								<td>
								<div class='submit' <?php echo $update_button_css; ?>>
									<input type='submit' value='<?php _e( 'Update &raquo;', 'wpsc' ); ?>' name='updateoption' />
								</div>
								</td>
							</tr>
							<?php do_action('wpsc_shipping_settings_page'); ?>
							
						</table>
								</div>
									</div>
										<table id='gateway_options' >
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
												| <span class="edit">
															<a class='edit-shipping-module' rel="<?php echo $shipping->internal_name; ?>" title="Edit this Shipping Module" href='<?php echo htmlspecialchars( add_query_arg('tab', 'shipping' , add_query_arg('page', 'wpsc-settings'  , add_query_arg( 'shipping_module', $shipping->internal_name ) ) ) ); ?>#gateway_options' style="cursor:pointer;">Edit</a>
												</span> |
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
										| <span class="edit">
													<a class='edit-shippping-module' rel="<?php echo $shipping->internal_name; ?>"  title="Edit this Shipping Module" href='<?php echo htmlspecialchars( add_query_arg('tab', 'shipping' , add_query_arg('page', 'wpsc-settings'  , add_query_arg( 'shipping_module', $shipping->internal_name ) ) ) ); ?>#gateway_options' style="cursor:pointer;"><?php _e( 'Edit' , 'wpsc' ); ?></a>
														</span> |
													</div>
													<p><input <?php echo $disabled; ?> name='custom_shipping_options[]' <?php echo $shipping->checked; ?> type='checkbox' value='<?php echo $shipping->internal_name; ?>' id='<?php echo $shipping->internal_name; ?>_id' /><label for='<?php echo $shipping->internal_name; ?>_id'><?php esc_attr_e( $shipping->name ); ?></label></p>
												</div>
										<?php } ?>

										<div class='submit gateway_settings'>
											<input type='hidden' value='true' name='update_gateways'/>
											<input type='submit' value='<?php _e( 'Update &raquo;', 'wpsc' ); ?>' name='updateoption'/>
										</div>

												</div>
											</div>
									</td>

									<td class='gateway_settings' rowspan='2'>
										<div class='postbox'>
											<h3 class='hndle'><?php esc_html( $shipping_data['name'] ); ?></h3>
											<div class='inside'>
												<table class='form-table'>
													<?php echo $shipping_data['form_fields']; ?>
												</table>

											<div class='submit' <?php echo $update_button_css; ?>>
												<input type='submit' value='<?php _e( 'Update &raquo;', 'wpsc' ); ?>' name='updateoption' />
											</div>
											</div>
										</div>
									</td>
								</tr>
							</table>
						</form>
					</div>
				</div>
			</form>
<?php
}
?>
