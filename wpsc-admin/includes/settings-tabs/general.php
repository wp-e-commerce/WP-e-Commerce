<?php
class WPSC_Settings_Tab_General extends WPSC_Settings_Tab {
	private $regions = array();

	public function __construct() {
		$this->get_regions();
		add_action( 'admin_notices', array( $this, 'no_target_markets' ) );
	}

	public function no_target_markets() {

		$countries = WPSC_Countries::get_countries();

		if ( empty( $countries ) ) {
			?>
			<div class="notice error is-dismissible below-h2">
				<p><?php _e( '<strong>You have not enabled any target markets.</strong> To sell tangible goods, you will need to set at least one target market.', 'wp-e-commerce' ); ?></p>
			</div>
			<?php
		}
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
		<h3><?php echo esc_html_e( 'General Settings', 'wp-e-commerce' ); ?></h3>
		<table class='wpsc_options form-table'>
			<tr>
				<th scope="row"><label for="wpsc-base-country-drop-down"><?php esc_html_e( 'Base Country/Region', 'wp-e-commerce' ); ?></label></th>
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
					<p class='description'><?php esc_html_e( 'Select your primary business location.', 'wp-e-commerce' ); ?></p>
				</td>
			</tr>

			<?php
				/* START OF TARGET MARKET SELECTION */
				$countrylist = WPSC_Countries::get_countries_array( true, true );
			?>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Target Markets', 'wp-e-commerce' ); ?>
				</th>
				<td>
					<?php
						// check for the suhosin module
						if ( wpsc_is_suhosin_enabled() ) {
							echo "<em>" . __( "The Target Markets feature has been disabled because you have the Suhosin PHP extension installed on this server. If you need to use the Target Markets feature, then disable the suhosin extension. If you can not do this, you will need to contact your hosting provider.", 'wp-e-commerce' ) . "</em>";
						} else {
							?>
							<span>
								<?php printf( __( 'Select: <a href="%1$s"  class="wpsc-select-all" title="All">All</a> <a href="%2$s" class="wpsc-select-none" title="None">None</a>' , 'wp-e-commerce') , esc_url( add_query_arg( array( 'selected_all' => 'all' ) ) ), esc_url( add_query_arg( array( 'selected_all' => 'none' ) ) ) ); ?>
							</span><br />
							<div id='wpsc-target-markets' class='ui-widget-content multiple-select'>
								<?php foreach ( (array)$countrylist as $country ) : ?>
									<?php if ( $country['visible'] == 1 ) : ?>
										<input type='checkbox' id="countrylist2-<?php echo $country['id']; ?>" name='countrylist2[]' value='<?php echo $country['id']; ?>' checked='checked' />
										<label for="countrylist2-<?php echo $country['id']; ?>"><?php echo esc_html( $country['country'] ); ?></label><br />
									<?php else : ?>
										<input type='checkbox' id="countrylist2-<?php echo $country['id']; ?>" name='countrylist2[]' value='<?php echo $country['id']; ?>'  />
										<label for="countrylist2-<?php echo $country['id']; ?>"><?php echo esc_html( $country['country'] ); ?></label><br />
									<?php endif; ?>
								<?php endforeach; ?>
							</div>

							<p class='description'><?php esc_html_e( 'Select the markets you are selling products to.' , 'wp-e-commerce'); ?></p>
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
					<label for="wpsc-stock-keeping-time"><?php esc_html_e( 'Keep stock in cart for', 'wp-e-commerce' ); ?></label>
				</th>
				<td>
					<input type="text" name="wpsc_options[wpsc_stock_keeping_time]" id="wpsc-stock-keeping-time" size="2" value="<?php echo esc_attr( $stock_keeping_time ); ?>" />
					<select name="wpsc_options[wpsc_stock_keeping_interval]">
						<option value="hour" <?php selected( 'hour', $stock_keeping_interval ); ?>><?php echo _n( 'hour', 'hours', $stock_keeping_time, 'wp-e-commerce' ); ?></option>
						<option value="day" <?php selected( 'day', $stock_keeping_interval ); ?>><?php echo _n( 'day', 'days', $stock_keeping_time, 'wp-e-commerce' ) ?></option>
						<option value="week" <?php selected( 'week', $stock_keeping_interval ); ?>><?php echo _n( 'week', 'weeks', $stock_keeping_time, 'wp-e-commerce' ) ?></option>
					</select>
					<p class='description'><?php esc_html_e( "Set the amount of time items in a customer's cart are reserved. You can also specify decimal amounts such as '0.5 days' or '1.25 weeks'. Note that the minimum interval you can enter is 1 hour, i.e. you can't schedule it to run every 0.5 hour.", 'wp-e-commerce' ) ?></p>
				</td>
			</tr>

			<?php
				$hierarchical_category = get_option( 'product_category_hierarchical_url', 0 );
			?>
			<tr>
				<th scope="row">
					<?php _e( 'Use Hierarchical Product Category URL', 'wp-e-commerce' ); ?>
				</th>
				<td>
					<label><input type="radio" <?php checked( $hierarchical_category, 1 ); ?> name="wpsc_options[product_category_hierarchical_url]" value="1" /> <?php _e( 'Yes', 'wp-e-commerce' ); ?></label>&nbsp;&nbsp;
					<label><input type="radio" <?php checked( $hierarchical_category, 0 ); ?>name="wpsc_options[product_category_hierarchical_url]" value="0" /> <?php _e( 'No', 'wp-e-commerce' ); ?></label><br />
					<p class='description'><?php _e( 'When Hierarchical Product Category URL is enabled, parent product categories are also included in the product URL.<br />For example: <code>http://example.com/products-page/parent-cat/sub-cat/product-name</code>', 'wp-e-commerce' ); ?></p>
				</td>
			</tr>
		</table>

		<h3 class="form_group"><?php esc_html_e( 'Currency Settings', 'wp-e-commerce' ); ?></h3>
		<table class='wpsc_options form-table'>
			<?php
				$currency_data = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_CURRENCY_LIST . "` ORDER BY `country` ASC", ARRAY_A );
				$currency_type = esc_attr( get_option( 'currency_type' ) );
			?>
			<tr>
				<th scope="row"><label for="wpsc_options_currency_type"><?php esc_html_e( 'Currency Type', 'wp-e-commerce' ); ?></label></th>
				<td>
					<select id="wpsc_options_currency_type" name='wpsc_options[currency_type]' onchange='getcurrency(this.options[this.selectedIndex].value);'>
					<?php foreach ( $currency_data as $currency ) : ?>
						<option value='<?php echo $currency['id']; ?>' <?php selected( $currency['id'], $currency_type ); ?>><?php echo esc_html( $currency['country'] ); ?> (<?php echo $currency['currency']; ?>)</option>
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
				<th scope="row"><?php esc_html_e( 'Currency Sign Location', 'wp-e-commerce' ); ?></th>
				<td>
					<input type='radio' value='1' name='wpsc_options[currency_sign_location]' id='csl1' <?php echo $csl1; ?> />
					<label for='csl1'><?php _ex( '100', 'Currency sign location - option 1', 'wp-e-commerce' ); ?><span id='cslchar1'><?php echo $currency_sign; ?></span></label> &nbsp;
					<input type='radio' value='2' name='wpsc_options[currency_sign_location]' id='csl2' <?php echo $csl2; ?> />
					<label for='csl2'><?php _ex( '100', 'Currency sign location - option 2', 'wp-e-commerce' ); ?> <span id='cslchar2'><?php echo $currency_sign; ?></span></label> &nbsp;
					<input type='radio' value='3' name='wpsc_options[currency_sign_location]' id='csl3' <?php echo $csl3; ?> />
					<label for='csl3'><span id='cslchar3'><?php echo $currency_sign; ?></span><?php _ex( '100', 'Currency sign location - option 3', 'wp-e-commerce' ); ?></label> &nbsp;
					<input type='radio' value='4' name='wpsc_options[currency_sign_location]' id='csl4' <?php echo $csl4; ?> />
					<label for='csl4'><span id='cslchar4'><?php echo $currency_sign; ?></span> <?php _ex( '100', 'Currency sign location - option 4', 'wp-e-commerce' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Thousands and decimal separators', 'wp-e-commerce' ); ?></th>
				<td>
					<label for="wpsc_options_wpsc_thousands_separator"><?php esc_html_e( 'Thousands separator', 'wp-e-commerce' ); ?></label>: <input name="wpsc_options[wpsc_thousands_separator]" id="wpsc_options_wpsc_thousands_separator" type="text" maxlength="1" size="1" value="<?php echo esc_attr(  get_option( 'wpsc_thousands_separator' ) ); ?>" /><br />
					<label for="wpsc_options_wpsc_decimal_separator"><?php esc_html_e( 'Decimal separator', 'wp-e-commerce' ); ?></label>: <input name="wpsc_options[wpsc_decimal_separator]" id="wpsc_options_wpsc_decimal_separator" type="text" maxlength="1" size="1" value="<?php echo esc_attr( get_option( 'wpsc_decimal_separator' ) ); ?>" /><br />
					<?php esc_html_e( 'Preview:', 'wp-e-commerce' ); ?> 10<?php echo esc_attr(  get_option( 'wpsc_thousands_separator' ) ); ?>000<?php echo esc_attr( get_option( 'wpsc_decimal_separator' ) ); ?>00
				</td>
			</tr>
		</table>

		<h3 class="form_group"><?php esc_html_e( 'Usage Tracking', 'wp-e-commerce' ); ?></h3>
		<table class='wpsc_options form-table'>
			<tr>
				<th scope="row">
					<label for="wpsc_options_usage_tracking"><?php esc_html_e( 'Allow Usage Tracking ?', 'wp-e-commerce' ); ?></label>
				</th>
				<td>
					<?php $usage_tracking = get_option( 'wpsc_usage_tracking', 0 ); ?>
					<label><input type="radio" <?php checked( $usage_tracking, 1 ); ?> name="wpsc_options[wpsc_usage_tracking]" value="1" /> <?php _e( 'Yes', 'wp-e-commerce' ); ?></label>&nbsp;&nbsp;
					<label><input type="radio" <?php checked( $usage_tracking, 0 ); ?>name="wpsc_options[wpsc_usage_tracking]" value="0" /> <?php _e( 'No', 'wp-e-commerce' ); ?></label><br />
					<p class='description'>
						<?php echo sprintf( 
							__( 'Allow WP eCommerce to anonymously track how this plugin is used and help us make the plugin better. Opt-in to tracking and our newsletter and immediately be emailed a 20&#37; discount to the WPeC shop, valid towards the <a href="%s" target="_blank">purchase of extensions</a>. No sensitive data is tracked.', 'wp-e-commerce' ),
							'https://wpecommerce.org/store/' );
						?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}
} // end class
