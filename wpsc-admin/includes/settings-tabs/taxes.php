<?php

class WPSC_Settings_Tab_Taxes extends WPSC_Settings_Tab {
	public function __construct() {
	}

	public function callback_submit_options() {
		$taxes_enabled = ( isset( $_POST['wpsc_options']['wpec_taxes_enabled'] ) ) ? 1 : 0;
		update_option( 'wpec_taxes_enabled', $taxes_enabled );

		//currently there are two types - bands and rates
		$taxes_rates_types = array( 'rates', 'bands' );

		foreach ( $taxes_rates_types as $taxes_type ) {
			$saved_rates = array( ); //keep track of saved rates
			$exists = array( ); //keep track of what rates or names have been saved
			//check the rates
			if ( isset( $_POST['wpsc_options']['wpec_taxes_' . $taxes_type] ) ) {
				foreach ( $_POST['wpsc_options']['wpec_taxes_' . $taxes_type] as $tax_rate ) {
					if( !isset( $tax_rate['region_code'] ) )
						$tax_rate['region_code'] = '';

					//if there is no country then skip
					if ( empty( $tax_rate['country_code'] ) ) {
						continue;
					}

					//bands - if the name already exists then skip - if not save it
					if ( $taxes_type == 'bands' ) {
						if ( empty( $tax_rate['name'] ) || in_array( $tax_rate['name'], $exists ) || $tax_rate['name'] == 'Disabled' ) {
							continue;
						} else {
							$exists[] = $tax_rate['name'];
							$saved_rates[] = $tax_rate;
						}// if
					}// if
					//rates - check the shipping checkbox
					if ( $taxes_type == 'rates' ) {
						//if there is no rate then skip
						if ( empty( $tax_rate['rate'] ) ) {
							continue;
						}

						$tax_rate['shipping'] = (isset( $tax_rate['shipping'] )) ? 1 : 0;

						//check if country exists
						if ( array_key_exists( $tax_rate['country_code'], $exists ) ) {
							//if region already exists skip
							if ( array_search( $tax_rate['region_code'], $exists[$tax_rate['country_code']] ) == $tax_rate['country_code'] ) {
								continue;
							} else {
								//it's not in the array add it
								$exists[$tax_rate['country_code']][] = $tax_rate['region_code'];

								//save it
								$saved_rates[] = $tax_rate;
							}// if
						} else {
							//add codes to exists array
							$exists[$tax_rate['country_code']][] = $tax_rate['region_code'];

							//save it
							$saved_rates[] = $tax_rate;
						}// if
					}// if
				}// foreach
			}// if
			//replace post tax rates with filtered rates
			update_option( 'wpec_taxes_' . $taxes_type, $saved_rates );
		}
	}

	public function display() {
		$wpec_taxes_controller = new wpec_taxes_controller;
		$wpec_taxes_options = $wpec_taxes_controller->wpec_taxes->wpec_taxes_get_options();

		?>
		<h3><?php esc_html_e( 'Tax Settings', 'wpsc' ); ?></h3>
		<table class='form-table'>
			<tr>
				<th><?php esc_html_e( "Enable Tax", 'wpsc' ); ?></th>
				<td>
					<input <?php if ( $wpec_taxes_options['wpec_taxes_enabled'] ) echo 'checked="checked"'; ?> type="checkbox" id='wpec_taxes_enabled' name='wpsc_options[wpec_taxes_enabled]' />
					<label for='wpec_taxes_enabled'>
						<?php esc_html_e( 'Turn tax on', 'wpsc' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( "Product Prices", 'wpsc' ); ?></th>
				<td>
					<input <?php if ( $wpec_taxes_options['wpec_taxes_inprice'] == 'exclusive' ) echo 'checked="checked"'; ?> type="radio" value='exclusive' id='wpec_taxes_inprice1' name='wpsc_options[wpec_taxes_inprice]' />
					<label for='wpec_taxes_inprice1'>
						<?php esc_html_e( 'Product prices are tax exclusive - add tax to the price during checkout', 'wpsc' ); ?>
					</label><br />
					<input <?php if ( $wpec_taxes_options['wpec_taxes_inprice'] == 'inclusive' ) echo 'checked="checked"'; ?> type="radio" value='inclusive' id='wpec_taxes_inprice2' name='wpsc_options[wpec_taxes_inprice]' />
					<label for='wpec_taxes_inprice2'>
						<?php esc_html_e( "Product prices are tax inclusive - during checkout the total price doesn't increase but tax is shown as a line item", 'wpsc' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Product Specific Tax', 'wpsc' ); ?></th>
				<td>
					<input <?php if ( $wpec_taxes_options['wpec_taxes_product'] == 'add' ) echo 'checked="checked"'; ?> type="radio" value='add' id='wpec_taxes_product_1' name='wpsc_options[wpec_taxes_product]' />
					<label for='wpec_taxes_product_1'>
						<?php esc_html_e( 'Add per product tax to tax percentage if product has a specific tax rate', 'wpsc' ); ?>
					</label><br />
					<input <?php if ( $wpec_taxes_options['wpec_taxes_product'] == 'replace' ) echo 'checked="checked"'; ?> type="radio" value='replace' id='wpec_taxes_product_2' name='wpsc_options[wpec_taxes_product]' />
					<label for='wpec_taxes_product_2'>
						<?php esc_html_e( 'Replace tax percentage with product specific tax rate', 'wpsc' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><?php _e( 'Tax Logic', 'wpsc' ); ?></th>
				<td>
					<input <?php if ( $wpec_taxes_options['wpec_taxes_logic'] == 'billing_shipping' ) echo 'checked="checked"'; ?> type="radio" value='billing_shipping' id='wpec_taxes_logic_1' name='wpsc_options[wpec_taxes_logic]' />
					<label for='wpec_taxes_logic_1'>
						<?php esc_html_e( 'Apply tax when Billing and Shipping Country is the same as Tax Rate', 'wpsc' ); ?>
					</label>
					<div id='billing_shipping_preference_container' style='margin-left: 20px;'>
						<?php
							$checked = ( $wpec_taxes_options['wpec_taxes_logic'] == 'billing_shipping' && $wpec_taxes_options['wpec_billing_shipping_preference'] == 'billing_address' ? 'checked="checked"' : '' );
						 ?>
						<input <?php echo $checked;?> type="radio" value='billing_address' id='wpec_billing_preference' name='wpsc_options[wpec_billing_shipping_preference]' />
						<label for='wpec_billing_preference'>
							<?php esc_html_e( 'Apply tax to Billing Address', 'wpsc' ); ?>
						</label><br />
						<?php
							$checked = ( $wpec_taxes_options['wpec_taxes_logic'] == 'billing_shipping' && $wpec_taxes_options['wpec_billing_shipping_preference'] == 'shipping_address' ? 'checked="checked"' : '' );
						?>
						<input <?php echo $checked; ?>type="radio" value='shipping_address' id='wpec_shipping_preference' name='wpsc_options[wpec_billing_shipping_preference]' />
						<label for='wpec_shipping_preference'>
							<?php esc_html_e( 'Apply tax to Shipping Address', 'wpsc' ); ?>
						</label>
					</div>
					<input <?php if ( $wpec_taxes_options['wpec_taxes_logic'] == 'billing' ) echo 'checked="checked"'; ?> type="radio" value='billing' id='wpec_taxes_logic_2' name='wpsc_options[wpec_taxes_logic]' />
					<label for='wpec_taxes_logic_2'>
						<?php esc_html_e( 'Apply tax when Billing Country is the same as Tax Rate', 'wpsc' ); ?>
					</label><br />
					<input <?php if ( $wpec_taxes_options['wpec_taxes_logic'] == 'shipping' ) echo 'checked="checked"'; ?> type="radio" value='shipping' id='wpec_taxes_logic_3' name='wpsc_options[wpec_taxes_logic]' />
					<label for='wpec_taxes_logic_3'>
						<?php esc_html_e( 'Apply tax when Shipping Country is the same as Tax Rate', 'wpsc' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<h3><?php esc_html_e( 'Tax Rates', 'wpsc' ); ?></h3>
		<div id='wpec-taxes-rates'>
			<!--Start Taxes Output-->
			<table class='widefat page fixed ui-sortable'>
				<thead>
					<th scope='col' width='60%'><?php _e( 'Market', 'wpsc' ); ?></th>
					<th scope='col' width='10%'><?php _e( 'Tax Rate', 'wpsc' ); ?></th>
					<th scope='col'><?php _e( 'Tax Shipping?', 'wpsc' ); ?></th>
					<th scope='col' style='width: 60px'><?php _e( 'Actions', 'wpsc' ); ?></th>
				</thead>
				<tbody>
					<?php
						$tax_rates = $wpec_taxes_controller->wpec_taxes->wpec_taxes_get_rates();
						echo $wpec_taxes_controller->wpsc_build_taxes_row( 'rates', 'prototype', array( 'row_class' => 'prototype' ) );
						if ( count( $tax_rates ) === 0 ) {
							echo $wpec_taxes_controller->wpsc_build_taxes_row( 'rates', 0, null );
						}
						$tax_rate_count = 0;
						if ( ! empty( $tax_rates ) ) {
							foreach ( $tax_rates as $tax_rate ) {
								// OLD: echo $wpec_taxes_controller->wpec_taxes_build_form( $tax_rate_count, $tax_rate );
								echo $wpec_taxes_controller->wpsc_build_taxes_row( 'rates', $tax_rate_count, $tax_rate );
								$tax_rate_count++;
							}
						}
					?>
				</tbody>
			</table>
			<!--End Taxes Output-->
		</div>
		<div id='wpec-taxes-bands-container'>
			<h3><?php esc_html_e( 'Tax Bands', 'wpsc' ); ?></h3>
			<div id='wpec-taxes-bands'>
				<div class="updated inline">
					<p><?php _e( 'Note: Tax Bands are special tax rules you can create and apply on a per-product basis. Please visit the product page to apply your Tax Band.', 'wpsc' ); ?></p>
				</div>
				<?php if ( !$wpec_taxes_controller->wpec_taxes_isincluded() ) : ?>
					<div class="error inline">
						<p><?php _e( 'Warning: Tax Bands do not take effect when product prices are tax exclusive.', 'wpsc' ); ?></p>
					</div>
				<?php endif; ?>
				<table class='widefat page fixed ui-sortable'>
					<thead>
						<th scope='col'><?php _e( 'Band Name', 'wpsc' ); ?></th>
						<th scope='col' width="50%"><?php _e( 'Market', 'wpsc' ); ?></th>
						<th scope='col' width='20%'><?php _e( 'Tax Rate', 'wpsc' ); ?></th>
						<th scope='col' style='width: 60px'><?php _e( 'Actions', 'wpsc' ); ?></th>
					</thead>
					<tbody>
						<?php
							$tax_bands = $wpec_taxes_controller->wpec_taxes->wpec_taxes_get_bands();
							echo $wpec_taxes_controller->wpsc_build_taxes_row( 'bands', 'prototype', array( 'row_class' => 'prototype' ) );
							if ( count( $tax_bands ) === 0 ) {
								echo $wpec_taxes_controller->wpsc_build_taxes_row( 'bands', 0, null );
							}
							$tax_band_count = 0;
							if ( ! empty( $tax_bands ) ) {
								foreach ( $tax_bands as $tax_band ) {
									// OLD: echo $wpec_taxes_controller->wpec_taxes_build_form( $tax_band_count, $tax_band, 'bands' );
									echo $wpec_taxes_controller->wpsc_build_taxes_row( 'bands', $tax_band_count, $tax_band );
									$tax_band_count++;
								}
							}
						?>

					</tbody>
				</table>
			</div>
		</div><!--wpec-taxes-bands-container-->
		<?php
	}
}