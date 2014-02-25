<?php
class WPSC_Settings_Tab_General extends WPSC_Settings_Tab {
	private $regions = array();

	public function __construct() {
		$this->get_regions();
	}

	private function get_regions() {
		global $wpdb;
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_POST['country'] ) )
			$base_country = $_POST['country'];
		else
			$base_country = get_option( 'base_country' );
		$from = WPSC_TABLE_REGION_TAX . ' AS r';
		$join = WPSC_TABLE_CURRENCY_LIST . ' AS c';
		$sql = $wpdb->prepare( "
			SELECT r.id, r.name
			FROM {$from}
			INNER JOIN {$join} ON r.country_id = c.id AND c.isocode = %s
		", $base_country );
		$this->regions = $wpdb->get_results( $sql );
	}

	public function display_region_drop_down() {
		$base_region = get_option( 'base_region' );
		if ( ! empty( $this->regions ) ):
			?>
				<select name='wpsc_options[base_region]'>
					<?php foreach ( $this->regions as $region ): ?>
						<option value='<?php echo esc_attr( $region->id ); ?>' <?php selected( $region->id, $base_region ); ?>><?php echo esc_html( $region->name ); ?></option>
					<?php endforeach ?>
				</select>
			<?php
		endif;
	}

	public function display() {
		global $wpdb;
		?>
		<h3><?php echo esc_html_e( 'General Settings', 'wpsc' ); ?></h3>
		<table class='wpsc_options form-table'>
			<tr>
				<th scope="row"><label for="wpsc-base-country-drop-down"><?php esc_html_e( 'Base Country/Region', 'wpsc' ); ?></label></th>
				<td>
					<?php
						wpsc_country_dropdown( array(
							'id'                => 'wpsc-base-country-drop-down',
							'name'              => 'wpsc_options[base_country]',
							'selected'          => get_option( 'base_country' ),
							'include_invisible' => true,
						) );
					?>
					<span id='wpsc-base-region-drop-down'>
						<?php $this->display_region_drop_down(); ?>
						<img src="<?php echo esc_url( wpsc_get_ajax_spinner() ); ?>" class="ajax-feedback" title="" alt="" />
					</span>
					<p class='description'><?php esc_html_e( 'Select your primary business location.', 'wpsc' ); ?></p>
				</td>
			</tr>

			<?php
				/* START OF TARGET MARKET SELECTION */
				$countrylist = $wpdb->get_results( "SELECT id,country,visible FROM `" . WPSC_TABLE_CURRENCY_LIST . "` ORDER BY country ASC ", ARRAY_A );
			?>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Target Markets', 'wpsc' ); ?>
				</th>
				<td>
					<?php
						// check for the suhosin module
						if ( wpsc_is_suhosin_enabled() ) {
							echo "<em>" . __( "The Target Markets feature has been disabled because you have the Suhosin PHP extension installed on this server. If you need to use the Target Markets feature then disable the suhosin extension, if you can not do this, you will need to contact your hosting provider.", 'wpsc' ) . "</em>";
						} else {
							?>
							<span>
								<?php printf( __( 'Select: <a href="%1$s"  class="wpsc-select-all" title="All">All</a> <a href="%2$s" class="wpsc-select-none" title="None">None</a>' , 'wpsc') , add_query_arg( array( 'selected_all' => 'all' ) ), add_query_arg( array( 'selected_all' => 'none' ) )  ); ?>
							</span><br />
							<div id='wpsc-target-markets' class='ui-widget-content multiple-select'>
								<?php foreach ( (array)$countrylist as $country ) : ?>
									<?php if ( $country['visible'] == 1 ) : ?>
										<input type='checkbox' id="countrylist2-<?php echo $country['id']; ?>" name='countrylist2[]' value='<?php echo $country['id']; ?>' checked='checked' />
										<label for="countrylist2-<?php echo $country['id']; ?>"><?php esc_html_e( $country['country'] ); ?></label><br />
									<?php else : ?>
										<input type='checkbox' id="countrylist2-<?php echo $country['id']; ?>" name='countrylist2[]' value='<?php echo $country['id']; ?>'  />
										<label for="countrylist2-<?php echo $country['id']; ?>"><?php esc_html_e( $country['country'] ); ?></label><br />
									<?php endif; ?>
								<?php endforeach; ?>
							</div>

							<p class='description'><?php esc_html_e( 'Select the markets you are selling products to.' , 'wpsc'); ?></p>
							<?php
						}
					?>
				</td>
			</tr>

			<?php
				$stock_keeping_time = wpsc_get_stock_keeping_time();
				$stock_keeping_interval = wpsc_get_stock_keeping_interval();
			?>
			<tr>
				<th scope="row">
					<label for="wpsc-stock-keeping-time"><?php esc_html_e( 'Keep stock in cart for', 'wpsc' ); ?></label>
				</th>
				<td>
					<input type="text" name="wpsc_options[wpsc_stock_keeping_time]" id="wpsc-stock-keeping-time" size="2" value="<?php echo esc_attr( $stock_keeping_time ); ?>" />
					<select name="wpsc_options[wpsc_stock_keeping_interval]">
						<option value="hour" <?php selected( 'hour', $stock_keeping_interval ); ?>><?php echo _n( 'hour', 'hours', $stock_keeping_time, 'wpsc' ); ?></option>
						<option value="day" <?php selected( 'day', $stock_keeping_interval ); ?>><?php echo _n( 'day', 'days', $stock_keeping_time, 'wpsc' ) ?></option>
						<option value="week" <?php selected( 'week', $stock_keeping_interval ); ?>><?php echo _n( 'week', 'weeks', $stock_keeping_time, 'wpsc' ) ?></option>
					</select>
					<p class='description'><?php esc_html_e( "Set the amount of time items in a customer's cart are reserved. You can also specify decimal amounts such as '0.5 days' or '1.25 weeks'. Note that the minimum interval you can enter is 1 hour, i.e. you can't schedule it to run every 0.5 hour.", 'wpsc' ) ?></p>
				</td>
			</tr>

			<?php
				$hierarchical_category = get_option( 'product_category_hierarchical_url', 0 );
			?>
			<tr>
				<th scope="row">
					<?php _e( 'Use Hierarchical Product Category URL', 'wpsc' ); ?>
				</th>
				<td>
					<label><input type="radio" <?php checked( $hierarchical_category, 1 ); ?> name="wpsc_options[product_category_hierarchical_url]" value="1" /> <?php _e( 'Yes', 'wpsc' ); ?></label>&nbsp;&nbsp;
					<label><input type="radio" <?php checked( $hierarchical_category, 0 ); ?>name="wpsc_options[product_category_hierarchical_url]" value="0" /> <?php _e( 'No', 'wpsc' ); ?></label><br />
					<p class='description'><?php _e( 'When Hierarchical Product Category URL is enabled, parent product categories are also included in the product URL.<br />For example: <code>http://example.com/products-page/parent-cat/sub-cat/product-name</code>', 'wpsc' ); ?></p>
				</td>
			</tr>
		</table>

		<h3 class="form_group"><?php esc_html_e( 'Currency Settings', 'wpsc' ); ?></h3>
		<table class='wpsc_options form-table'>
			<?php
				$currency_data = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_CURRENCY_LIST . "` ORDER BY `country` ASC", ARRAY_A );
				$currency_type = esc_attr( get_option( 'currency_type' ) );
			?>
			<tr>
				<th scope="row"><label for="wpsc_options_currency_type"><?php esc_html_e( 'Currency Type', 'wpsc' ); ?></label></th>
				<td>
					<select id="wpsc_options_currency_type" name='wpsc_options[currency_type]' onchange='getcurrency(this.options[this.selectedIndex].value);'>
					<?php foreach ( $currency_data as $currency ) : ?>
						<option value='<?php echo $currency['id']; ?>' <?php selected( $currency['id'], $currency_type ); ?>><?php esc_html_e( $currency['country'] ); ?> (<?php echo $currency['currency']; ?>)</option>
					<?php endforeach; ?>
					</select>
				</td>
			</tr>

			<?php
				$currency_data = $wpdb->get_row( "SELECT `symbol`,`symbol_html`,`code` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id`='" . esc_attr( get_option( 'currency_type' ) ) . "' LIMIT 1", ARRAY_A );

				if ( $currency_data['symbol'] != '' ) {
					$currency_sign = esc_attr( $currency_data['symbol_html'] );
				} else {
					$currency_sign = esc_attr( $currency_data['code'] );
				}

				$currency_sign_location = esc_attr( get_option( 'currency_sign_location' ) );
				$csl1 = "";
				$csl2 = "";
				$csl3 = "";
				$csl4 = "";
				switch ( $currency_sign_location ) {
					case 1:
						$csl1 = "checked='checked'";
						break;

					case 2:
						$csl2 = "checked='checked'";
						break;

					case 3:
						$csl3 = "checked='checked'";
						break;

					case 4:
						$csl4 = "checked='checked'";
						break;
				}
			?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Currency Sign Location', 'wpsc' ); ?></th>
				<td>
					<input type='radio' value='1' name='wpsc_options[currency_sign_location]' id='csl1' <?php echo $csl1; ?> />
					<label for='csl1'><?php _ex( '100', 'Currency sign location - option 1', 'wpsc' ); ?><span id='cslchar1'><?php echo $currency_sign; ?></span></label> &nbsp;
					<input type='radio' value='2' name='wpsc_options[currency_sign_location]' id='csl2' <?php echo $csl2; ?> />
					<label for='csl2'><?php _ex( '100', 'Currency sign location - option 2', 'wpsc' ); ?> <span id='cslchar2'><?php echo $currency_sign; ?></span></label> &nbsp;
					<input type='radio' value='3' name='wpsc_options[currency_sign_location]' id='csl3' <?php echo $csl3; ?> />
					<label for='csl3'><span id='cslchar3'><?php echo $currency_sign; ?></span><?php _ex( '100', 'Currency sign location - option 3', 'wpsc' ); ?></label> &nbsp;
					<input type='radio' value='4' name='wpsc_options[currency_sign_location]' id='csl4' <?php echo $csl4; ?> />
					<label for='csl4'><span id='cslchar4'><?php echo $currency_sign; ?></span> <?php _ex( '100', 'Currency sign location - option 4', 'wpsc' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Thousands and decimal separators', 'wpsc' ); ?></th>
				<td>
					<label for="wpsc_options_wpsc_thousands_separator"><?php esc_html_e( 'Thousands separator', 'wpsc' ); ?></label>: <input name="wpsc_options[wpsc_thousands_separator]" id="wpsc_options_wpsc_thousands_separator" type="text" maxlength="1" size="1" value="<?php echo esc_attr(  get_option( 'wpsc_thousands_separator' ) ); ?>" /><br />
					<label for="wpsc_options_wpsc_decimal_separator"><?php esc_html_e( 'Decimal separator', 'wpsc' ); ?></label>: <input name="wpsc_options[wpsc_decimal_separator]" id="wpsc_options_wpsc_decimal_separator" type="text" maxlength="1" size="1" value="<?php echo esc_attr( get_option( 'wpsc_decimal_separator' ) ); ?>" /><br />
					<?php esc_html_e( 'Preview:', 'wpsc' ); ?> 10<?php echo esc_attr(  get_option( 'wpsc_thousands_separator' ) ); ?>000<?php echo esc_attr( get_option( 'wpsc_decimal_separator' ) ); ?>00
				</td>
			</tr>
		</table>
		<?php
	}
} // end class
