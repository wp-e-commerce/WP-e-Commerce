<?php
/* Author : Greg Gullett and Instinct.co.uk
 * SVN : UPS Trunk :
 * Version : 1.1.0 : December 21, 2010
 */
class ash_ups {
	var $internal_name, $name;
	var $service_url = '';
	var $Services = '';
	var $singular_shipping = FALSE;
	var $shipment;
	var $is_external = true;

	function ash_ups () {
		$this->internal_name = 'ups';
		$this->name = _x( 'UPS', 'Shipping Module', 'wpsc' );
		$this->is_external = true;
		$this->requires_curl = true;
		$this->requires_weight = true;
		$this->needs_zipcode = true;
		$this->_setServiceURL();
		$this->_includeUPSData();
		return true;
	}

	function __autoload ( $name ) {
		include( '../wpsc-includes/shipping.helper.php' );
	}

	function getId () {
		// return $this->usps_id;
	}

	function setId ( $id ) {
		// $usps_id = $id;
		// return true;
	}

	private function _setServiceURL () {
		global $wpdb;
		$wpsc_ups_settings = get_option( 'wpsc_ups_settings' );
		$wpsc_ups_environment = ( array_key_exists( 'upsenvironment', (array) $wpsc_ups_settings ) ) ? $wpsc_ups_settings['upsenvironment'] : '0'; //(1) testing, else (0) production.

		if ( '1' == $wpsc_ups_environment ){
			$this->service_url = 'https://wwwcie.ups.com/ups.app/xml/Rate';
		} else {
			$this->service_url = 'https://www.ups.com/ups.app/xml/Rate';
		}
	}

	function getName () {
		return $this->name;
	}

	function getInternalName () {
		return $this->internal_name;
	}

	private function _includeUPSData () {
		$this->drop_types = array(
			'01' => __( 'Daily Pickup', 'wpsc' ),
			'03' => __( 'Customer Counter', 'wpsc' ),
			'06' => __( 'One Time Pickup', 'wpsc' ),
			'07' => __( 'On Call Air', 'wpsc' ),
			'19' => __( 'Letter Center', 'wpsc' ),
			'20' => __( 'Air Service Center', 'wpsc' ),
			'11' => __( 'Suggested Retail Rates (Advanced Config)', 'wpsc' ),
		);

		$this->cust_types = array(
			'01' => __( 'Daily Pickup, with UPS Account', 'wpsc' ),
			'03' => __( 'No Daily Pickup, with No or Other Account', 'wpsc' ),
			'04' => __( 'Retail Outlet (Only US origin shipments)', 'wpsc' )
		);

		$this->Services = array(
			'14' => __( 'Next Day Air Early AM', 'wpsc' ),
			'01' => __( 'Next Day Air', 'wpsc' ),
			'13' => __( 'Next Day Air Saver', 'wpsc' ),
			'59' => __( '2nd Day Air AM', 'wpsc' ),
			'02' => __( '2nd Day Air', 'wpsc' ),
			'12' => __( '3 Day Select', 'wpsc' ),
			'03' => __( 'Ground', 'wpsc' ),
			'11' => __( 'Standard', 'wpsc' ),
			'07' => __( 'Worldwide Express', 'wpsc' ),
			'54' => __( 'Worldwide Express Plus', 'wpsc' ),
			'08' => __( 'Worldwide Expedited', 'wpsc' ),
			'65' => __( 'Saver', 'wpsc' ),
			'82' => __( 'UPS Today Standard', 'wpsc' ),
			'83' => __( 'UPS Today Dedicated Courier', 'wpsc' ),
			'84' => __( 'UPS Today Intercity', 'wpsc' ),
			'85' => __( 'UPS Today Express', 'wpsc' ),
			'86' => __( 'UPS Today Express Saver', 'wpsc' )
		);
	}

	function getForm () {
		if ( ! isset( $this->Services ) ) {
			$this->_includeUPSData();
		}
		//__('Your Packaging', 'wpsc');  <-- use to translate
		$wpsc_ups_settings = get_option( 'wpsc_ups_settings' );
		$wpsc_ups_services = get_option( 'wpsc_ups_services' );
		// Defined on page 41 in UPS API documentation RSS_Tool_06_10_09.pdf
		/*$packaging_options['00'] = __('**UNKNOWN**', 'wpsc');*/
		$packaging_options['01'] = __( 'UPS Letter', 'wpsc' );
		$packaging_options['02'] = __( 'Your Packaging', 'wpsc' );
		$packaging_options['03'] = __( 'UPS Tube', 'wpsc' );
		$packaging_options['04'] = __( 'UPS Pak', 'wpsc' );
		$packaging_options['21'] = __( 'UPS Express Box', 'wpsc' );
		$packaging_options['2a'] = __( 'UPS Express Box - Small', 'wpsc' );
		$packaging_options['2b'] = __( 'UPS Express Box - Medium', 'wpsc' );
		$packaging_options['2c'] = __( 'UPS Express Box - Large', 'wpsc' );

		ob_start();
		?>
		<tr><td><table class='form-table'>
			<tr>
				<td><?php _e( 'Destination Type', 'wpsc' ); ?></td>
				<td>
					<label>
						<input type='radio' <?php checked( '2' != $wpsc_ups_settings['49_residential'] ); ?> value='1' name='wpsc_ups_settings[49_residential]' />
						<?php _e( 'Residential Address', 'wpsc' ); ?>
					</label><br />
					<label><input type='radio' <?php checked( '2' == $wpsc_ups_settings['49_residential'] ); ?>value='2' name='wpsc_ups_settings[49_residential]' />
						<?php _e( 'Commercial Address', 'wpsc' )?>
					</label>
				</td>
			</tr>

			<?php
			$sel2_drop = '';
			if ( empty( $wpsc_ups_settings['DropoffType'] ) ) {
				$sel2_drop = '01';
			} else {
				$sel2_drop = $wpsc_ups_settings['DropoffType'];
			}
			?>
			<tr>
				<td><?php _e( 'Dropoff Type', 'wpsc' ); ?></td>
				<td>
					<script type="text/javascript">
						function checkDropValue () {
							var val = jQuery("#drop_type option:selected").val();
							if (val == "11"){
								jQuery("#cust_type").removeAttr("disabled");
							} else {
								jQuery("#cust_type").attr("disabled", true);
							}
						}
					</script>
					<select id='drop_type' name='wpsc_ups_settings[DropoffType]' onChange='checkDropValue()' >
						<?php foreach ( array_keys( (array) $this->drop_types ) as $dkey ): ?>
							<option value="<?php esc_attr_e( $dkey ); ?>" <?php selected( $dkey, $sel2_drop ); ?> >
								<?php echo( $this->drop_types[$dkey] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>

			<?php
				$sel3_drop = '';
				if ( empty( $wpsc_ups_settings['CustomerType'] ) ) {
					$sel3_drop = '01';
				} else {
					$sel3_drop = $wpsc_ups_settings['CustomerType'];
				}
			?>
			<tr>
				<td><?php _e( 'Customer Type', 'wpsc' ); ?></td>
				<td>
					<select id='cust_type' name='wpsc_ups_settings[CustomerType]' <?php disabled( $wpsc_ups_settings['DropoffType'] != '11' ); ?> >
						<?php foreach( array_keys( $this->cust_types ) as $dkey ): ?>
							<option value="<?php esc_attr_e( $dkey ); ?>" <?php selected( $sel3_drop == $dkey ); ?> >
								<?php echo( $this->cust_types[$dkey] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>

			<tr>
				<td><?php _e( 'Packaging', 'wpsc' ); ?></td>
				<td>
					<select name='wpsc_ups_settings[48_container]'>
						<?php foreach ( $packaging_options as $key => $name ): ?>
							<option value='<?php esc_attr_e( $key ); ?>' <?php selected( $key == $wpsc_ups_settings['48_container'] );?>>
								<?php esc_html_e( $name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>

			<tr>
				<td><?php _e( 'Shipping Settings', 'wpsc' ); ?></td>
				<td>
					<input type="checkbox" id="ups_env_test" name="wpsc_ups_settings[upsenvironment]" value="1" <?php checked( 1, isset( $wpsc_ups_settings['upsenvironment'] ) && (bool) $wpsc_ups_settings['upsenvironment'] ); ?> />
					<label for="ups_env_test" ><?php _e( 'Use Testing Environment', 'wpsc' ); ?></label>
					<br />
					<input type="checkbox" id="ups_negotiated_rates" name="wpsc_ups_settings[ups_negotiated_rates]" value="1" <?php checked( 1, isset( $wpsc_ups_settings['ups_negotiated_rates'] ) && (bool) $wpsc_ups_settings['ups_negotiated_rates'] ); ?> />
					<label for="ups_negotiated_rates" ><?php _e( 'Show UPS negotiated rates', 'wpsc' ); ?> *</label>
					<br />
					<input type="checkbox" id="ups_insured_shipment" name="wpsc_ups_settings[insured_shipment]" value="1" <?php checked( 1, isset( $wpsc_ups_settings['insured_shipment'] ) && (bool) $wpsc_ups_settings['insured_shipment'] ); ?> />
					<label for="ups_insured_shipment" ><?php _e( 'Insure shipment against cart total', 'wpsc' ); ?> *</label>
					<br />
					<input type="checkbox" id="ups_singular_shipping" name="wpsc_ups_settings[singular_shipping]" value="1" <?php checked( 1, isset( $wpsc_ups_settings['singular_shipping'] ) && (bool) $wpsc_ups_settings['singular_shipping'] ); ?> />
					<label for="ups_singular_shipping" ><?php _e( 'Singular Shipping', 'wpsc' ); ?> *</label>
					<p class='description'><?php _e( 'Rate each quantity of items in a cart as its own package using dimensions on product', 'wpsc' ); ?></p>
					<input type="checkbox" id="ups_intl_rate" name="wpsc_ups_settings[intl_rate]" value="1" <?php checked( 1, isset( $wpsc_ups_settings['intl_rate'] ) && (bool) $wpsc_ups_settings['intl_rate'] ); ?> />
					<label for="ups_intl_rate" ><?php _e( 'Disable International Shipping', 'wpsc' ); ?></label>
					<p class='description'><?php _e( 'No shipping rates will be displayed if the shipment destination country is different than your base country/region.', 'wpsc' ); ?></p>
				</td>
			</tr>

			<?php
				ksort( $this->Services );
				$first = false;
			?>
			<tr>
				<td><?php _e( 'UPS Preferred Services', 'wpsc' ); ?></td>
				<td>
					<div class="ui-widget-content multiple-select">
						<?php foreach( array_keys( $this->Services ) as $service ): ?>
							<input type="checkbox" id="wps_ups_srv_<?php esc_attr_e( $service ); ?>" name="wpsc_ups_services[]" value="<?php esc_attr_e( $service ); ?>" <?php checked( is_array( $wpsc_ups_services ) && ( array_search( $service, $wpsc_ups_services ) !== false ) ); ?> />
							<label for="wps_ups_srv_<?php esc_attr_e( $service); ?>"><?php esc_html_e( $this->Services[$service] ); ?></label><br />
						<?php endforeach; ?>
					</div>
					<p class='description'><?php _e( 'Note: All services used if no services selected', 'wpsc' ); ?></p>
				</td>
			</tr>

			<tr>
				<td colspan='2'><strong><?php _e( 'My UPS', 'wpsc' ); ?></strong></td>
			</tr>
			<tr>
				<td><?php _e( 'Account Number', 'wpsc' ); ?> *</td>
				<td>
					<input type="text" name='wpsc_ups_settings[upsaccount]' value="<?php esc_attr_e( $wpsc_ups_settings['upsaccount'] ); ?>" />
				</td>
			</tr>
			<tr>
				<td><?php _e( 'Username', 'wpsc' ); ?></td>
				<td>
					<input type="text" name='wpsc_ups_settings[upsusername]' value="<?php esc_attr_e( base64_decode( $wpsc_ups_settings['upsusername'] ) ); ?>" />
				</td>
			</tr>
			<tr>
				<td><?php _e( 'Password', 'wpsc' ); ?></td>
				<td>
					<input type="password" name='wpsc_ups_settings[upspassword]' value="<?php esc_attr_e( base64_decode( $wpsc_ups_settings['upspassword'] ) ); ?>" />
				</td>
			</tr>
			<tr>
				<td><?php _e( 'UPS XML API Key', 'wpsc' ); ?></td>
				<td>
					<input type="text" name='wpsc_ups_settings[upsid]' value="<?php esc_attr_e( base64_decode( $wpsc_ups_settings['upsid'] ) ); ?>" />
					<p class='description'><?php printf( __( "Don't have an API login/ID? <a href='%s' target='_blank'>Sign up for My UPS</a>", 'wpsc' ), esc_url( "https://www.ups.com/upsdeveloperkit?loc=en_US" ) ); ?></p>
				</td>
			</tr>
			<tr>
				<td colspan='2'>
					<p class='description'><?php _e( '* For Negotiated rates, you must enter a UPS account number and select "Show UPS negotiated rates" ', 'wpsc' ); ?></p>
					<p class='description'><?php printf( __( "For more help configuring UPS, please <a href='%s'>read our documentation</a>", 'wpsc' ), esc_url( 'http://docs.getshopped.org/wiki/documentation/shipping/ups' ) ); ?></p>
				</td>
			</tr>
		</table></td></tr>
		<?php
		// End new Code
		return ob_get_clean();
	}

	function submit_form() {
		/* This function is called when the user hit "submit" in the
		 * UPS settings area under Shipping to update the setttings.
		 */
		if (isset( $_POST['wpsc_ups_settings'] ) && !empty( $_POST['wpsc_ups_settings'] ) ) {
			if ( isset( $_POST['wpsc_ups_services'] ) ) {
				$wpsc_ups_services = $_POST['wpsc_ups_services'];
				update_option('wpsc_ups_services',$wpsc_ups_services);
			}
			$temp = stripslashes_deep( $_POST['wpsc_ups_settings'] );
			// base64_encode the information so it isnt stored as plaintext.
			// base64 is by no means secure but without knowing if the server
			// has mcrypt installed what can you do really?
			$temp['upsusername'] = base64_encode( $temp['upsusername'] );
			$temp['upspassword'] = base64_encode( $temp['upspassword'] );
			$temp['upsid'] = base64_encode( $temp['upsid'] );

			update_option('wpsc_ups_settings', $temp);
		}
		return true;
	}

	function array2xml( $data ) {
		$xml = "";
		if ( is_array( $data ) ) {
			foreach( $data as $key => $value ) {
				$xml .= "<" . trim( $key ) . ">";
				$xml .= $this->array2xml($value);
				$xml .= "</" . trim( $key ) . ">";
			}
		} else if ( is_bool( $data ) ) {
			if ( $data ){
				$xml = "true\n";
			} else {
				$xml = "false\n";
			}
		} else {
			$xml = trim( $data ) . "\n";
		}
		return $xml;
	}

	private function _is_large( &$pack, $package ) {
		$maximum = 165; // in inches
		$large_floor = 130; // in inches
		$calc_total=(round($package->length)+(2*round($package->width))+(2*round($package->height))); //see http://www.ups.com/content/us/en/resources/prepare/oversize.html.
		if ( $calc_total > $maximum || round( $package->length ) > 108 ) { //see http://www.ups.com/content/us/en/resources/prepare/oversize.html.
			throw new Exception( "Package dimensions exceed non-freight limits" );
		} elseif ( $calc_total > $large_floor ) {
			$pack["LargePackageIndicator"] = "";
		}
	}

	private function _insured_value( &$pack, $package, $args ){
		$monetary_value = $package->value;
		if ( $package->insurance === TRUE ){
			if ( $package->insured_amount ) {
				$monetary_value = $package->insured_amount;
			}
			$pack["PackageServiceOptions"]["InsuredValue"] = array(
				"CurrencyCode" => $args["currency"],
				"MonetaryValue" => $package->insured_amount
			);
		}
	}

	private function _declared_value( &$pack, $package, $args ){
		$pack["PackageServiceOptions"]["DeclaredValue"] = array(
			"CurrencyCode" => $args["currency"],
			"MonetaryValue" => $package->value //Per package value, not total value
		);
	}

	private function _build_shipment( &$Shipment, $args ){

		$cart_shipment = apply_filters( 'wpsc_the_shipment', $this->shipment, $args ); //Filter to allow reprocessing shipment packages.

		foreach ( $cart_shipment->packages as $package ) {
			$pack = array(
				"PackagingType" => array(
					"Code" => "02"
				),
				"Dimensions" => array(
					"UnitOfMeasurement" => array(
						"Code" => "IN"
					),
					"Length" => $package->length,
					"Width" => $package->width,
					"Height" => $package->height
				),
				"PackageWeight"=>array(
					"UnitOfMeasurement"=>array(
						"Code" => "LBS"
					),
					"Weight" => $package->weight
				)
			); // End Package
			// handle if the package is "large" or not (UPS standard)
			$this->_is_large($pack, $package);
			$this->_insured_value($pack, $package, $args);
			$this->_declared_value($pack, $package, $args);
			$Shipment .= $this->array2xml(array("Package"=>$pack));
		} // End for each package in shipment
	}

	private function _buildRateRequest( $args ){
		// Vars is an array
		// $RateRequest, $RatePackage, $RateCustomPackage, $RateRequestEnd
		// Are defined in ups_data.php that is included below if not
		// done so by instantiating class ... shouldnt ever need to
		// Always start of with this, it includes the auth block
		$REQUEST = "<?xml version=\"1.0\"?>\n
		<AccessRequest xml:lang=\"en-US\">\n";

		$access = array(
			"AccessLicenseNumber" => base64_decode( $args['api_id'] ),   // UPS API ID#
			"UserId" => base64_decode( $args['username'] ), // UPS API Username
			"Password" => base64_decode( $args['password'] )  // UPS API Password
		);

		$REQUEST .= $this->array2xml( $access );
		$REQUEST .= "</AccessRequest>\n";
		$REQUEST .= "<RatingServiceSelectionRequest xml:lang=\"en-US\">\n";

		// By Default we will shop. Shop when you do not have a service type
		// and you want to get a set of services and rates back!
		$RequestType = "Shop";
		// If service type is set we cannot shop so instead we Rate!
		if ( isset( $args["service"] ) ) {
			$RequestType = "Rate";
		}

		$RatingServiceRequest = array(
			"Request" => array(
				"TransactionReference" => array(
					"CustomerContext" => "Rate Request",
					"XpciVersion" => "1.0001"
				),
				"RequestAction" => "Rate",
				"RequestOption" => $RequestType
			)
		);

		// Set the dropoff code
		$dropCode = ( array_key_exists( 'DropoffType', $args ) ) ? $args['DropoffType'] : '01';
		$PickupType = array(
			"PickupType" => array(
				"Code" => $dropCode
			)
		);

		$REQUEST .= $this->array2xml( $PickupType );

		if ( $dropCode == "11" && $args['shipr_ccode'] == "US" ) {
			// Set the request code
			$CustCode = ( array_key_exists( 'CustomerType', $args ) ) ? $args['CustomerType'] : '01';
			$CustomerType = array(
				"CustomerClassification" => array(
					"Code"=>$CustCode
				)
			);
			$REQUEST .= $this->array2xml( $CustomerType );
		}

		// Set up Shipment Node
		$Shipment = "";

		// Shipper Address (billing)
		$Shipper = array(
			"Address" => array(
				"StateProvinceCode" => $args['shipr_state'],
				"PostalCode" => $args['shipr_pcode'], // The shipper Postal Code
				"CountryCode" => $args['shipr_ccode']
			)
		);

		// Negotiated Rates
		if ( array_key_exists( 'negotiated_rates', $args ) ) {
			if ( $args['negotiated_rates'] == '1' && ! empty( $args['account_number'] ) ) {
				$Shipper["ShipperNumber"] = $args['account_number'];
			}
		}

		// If the city is configured use it
		if ( array_key_exists( 'shipr_city', $args ) ) {
			if ( ! empty( $args['shipr_city'] ) ) {
				$Shipper["Address"]["City"] = $args["shipr_city"];
			}
		}

		$Shipment .= $this->array2xml( array("Shipper" => $Shipper ) );

		// The physical address the shipment is from (normally the same as billing)
		$ShipFrom = array(
			"Address" => array(
				"StateProvinceCode" => $args['shipf_state'],
				"PostalCode" => $args['shipf_pcode'], // The shipper Postal Code
				"CountryCode" => $args['shipf_ccode']
			));

		// If the city is configured use it
		if ( array_key_exists( 'shipf_city', $args ) ) {
			if ( ! empty( $args['shipf_city'] ) ) {
				$ShipFrom["Address"]["City"] = $args["shipf_city"];
			}
		}

		$Shipment .= $this->array2xml( array( "ShipFrom" => $ShipFrom ) );

		$ShipTo = array(
			"Address" => array(
				"StateProvinceCode" => $args['dest_state'], // The Destination State
				"PostalCode" => $args['dest_pcode'], // The Destination Postal Code
				"CountryCode" => $args['dest_ccode'], // The Destination Country
				//"ResidentialAddress"=>"1"
			)
		);

		if ( $args['residential'] == '1' ) { //ResidentialAddressIndicator orig - Indicator
			$ShipTo["Address"]["ResidentialAddressIndicator"] = "1";
		}

		$Shipment .= $this->array2xml( array( "ShipTo" => $ShipTo ) );

		// If there is a specific service being requested then
		// we want to pass the service into the XML
		if ( isset( $args["service"] ) ) {
		   $Shipment .= $this->array2xml( array( "Service" => array( "Code" => $args['service'] ) ) );
		}

		// Include this only if you want negotiated rates
		if ( array_key_exists( 'negotiated_rates', $args ) ){
			if ( $args['negotiated_rates'] == "1" ) {
				$Shipment .= $this->array2xml( array( "RateInformation" => array( "NegotiatedRatesIndicator" => "" ) ) );
			}
		}

		if ( (boolean) $args["singular_shipping"] ) {
			$this->_build_shipment( $Shipment, $args );
		} else {
			$package = array(
				"Package" => array(
					"PackagingType" => array(
						"Code" => $args['packaging']
					),
					"PackageWeight" => array(
						"UnitOfMeasurement" => array(
							"Code" => $args['units']
						),
						"Weight" => $args["weight"]
					)
				)
			);
			if ( (boolean) $args["insured_shipment"] ) {
				$package["PackageServiceOptions"] = array(
					"InsuredValue"=> array(
						"CurrencyCode" => $args["currency"],
						"MonetaryValue" => $args["cart_total"]
					)
				);
			}

			$Shipment .= $this->array2xml( $package );
		}

		// Set the structure for the Shipment Node
		$RatingServiceRequest["Shipment"] = $Shipment;

		$REQUEST .= $this->array2xml( $RatingServiceRequest );
		$REQUEST .= "</RatingServiceSelectionRequest>";

		// Return the final XML document as a string to be used by _makeRateRequest
		return $REQUEST;
	}

	private function _makeRateRequest( $message ){
		// Make the XML request to the server and retrieve the response
		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL, $this->service_url );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $message );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

		$data = curl_exec( $ch );
		curl_close( $ch );
		return $data;
	}

	public function futureDate( $interval ){
		//Wed Apr 7
		date_default_timezone_set( 'America/Los_Angeles' );
		$timestamp = date( 'c' );
		$hour = date( "G" );
		if ( (int) $hour >= 3 ) {
			$interval += 1;
		}

		$date = date( "Y-m-d" );
		$interval = " +$interval day";
		$final = date( "D M j", strtotime( date( "Y-m-d", strtotime( $date ) ) . $interval ) );
		$test = explode(" ", $final);

		if ( $test[0] == "Sat" ) {
			return $this->futureDate( $interval + 2 );
		} else if ( $test[0] == "Sun" ) {
			return $this->futureDate( $interval + 1 );
		}
		return $final;
	}

	private function _parseQuote( $raw ){
		global $wpdb;

		$config = get_option( 'wpsc_ups_settings', array() );
		$debug = ( array_key_exists( 'upsenvironment', $config) ) ? $config['upsenvironment'] : "";

		$rate_table = array();
		$wpsc_ups_services = get_option( "wpsc_ups_services" );
		// Initialize a DOM using the XML passed in!
		$objDOM = new DOMDocument();
		if ( $raw != '' ) {
			$objDOM->loadXML( $raw );

			// Get the <ResponseStatusCode> from the UPS XML
			$getStatusNode = $objDOM->getElementsByTagName( "ResponseStatusCode" );
			// Get the value of the error code, 1 == No Error, 0 == Error !!!
			$statusCode = $getStatusNode->item( 0 )->nodeValue;

			if ( $statusCode == "0" ) {
				// Usually I dont leave debug stuff in but this is handy stuff!!
				// it will print out the error message returned by UPS!
				if ( $debug == "1" ) {
					$getErrorDescNode = $objDOM->getElementsByTagName( "ErrorDescription" );
					$ErrorDesc = $getErrorDescNode->item( 0 )->nodeValue;
					echo "<br />Error : " . $ErrorDesc . "<br />";
				}
				return false;
			} else {
				$RateBlocks = $objDOM->getElementsByTagName( 'RatedShipment' );
				foreach ( $RateBlocks as $rate_block ) {
					// Get the <Service> Node from the XML chunk
					$getServiceNode = $rate_block->getElementsByTagName( 'Service' );
					$serviceNode = $getServiceNode->item( 0 );

					// Get the <Code> Node from the <Service> chunk
					$getServiceCodeNode = $serviceNode->getElementsByTagName( 'Code' );
					// Get the value from <Code>
					$serviceCode = $getServiceCodeNode->item( 0 )->nodeValue;
					$go = true;
					$price = '';
					$time = '';

					//if (array_key_exists('ups_negotiated_rates', $config)){
					$getNegotiatedRateNode = $rate_block->getElementsByTagName( 'NegotiatedRates' );
					if ( $getNegotiatedRateNode ) {
						$negotiatedRateNode = $getNegotiatedRateNode->item( 0 );
						if ( $negotiatedRateNode ) {
							$getNetSummaryNode = $negotiatedRateNode->getElementsByTagName( 'NetSummaryCharges' );
							$netSummaryNode = $getNetSummaryNode->item( 0 );

							$getGrandTotalNode = $netSummaryNode->getElementsByTagName( 'GrandTotal' );
							$grandTotalNode = $getGrandTotalNode->item( 0 );

							$getMonetaryNode = $grandTotalNode->getElementsByTagName( 'MonetaryValue' );
							$monetaryNode = $getMonetaryNode->item( 0 )->nodeValue;
							if ( ! empty( $monetaryNode ) ) {
								$go = false;
								$price = $monetaryNode;
							}
						}
					}

					// Get the <TotalCharges> Node from the XML chunk
					$getChargeNodes = $rate_block->getElementsByTagName( 'TotalCharges' );
					$chargeNode     = $getChargeNodes->item( 0 );

					// Get the <CurrencyCode> from the <TotalCharge> chunk
					$getCurrNode = $chargeNode->getElementsByTagName( 'CurrencyCode' );

					// Get the value of <CurrencyCode>
					$currCode = $getCurrNode->item( 0 )->nodeValue;

					if ( $go == true ){
						// Get the <MonetaryValue> from the <TotalCharge> chunk
						$getMonetaryNode = $chargeNode->getElementsByTagName( 'MonetaryValue' );
						// Get the value of <MonetaryValue>
						$price = $getMonetaryNode->item( 0 )->nodeValue;
					}
					// If there are any services specified in the admin area
					// this will check that list and pass on adding any services that
					// are not explicitly defined.
					if ( ! empty( $wpsc_ups_services ) ) {
						if ( is_array( $wpsc_ups_services ) ) {
							if ( array_search( $serviceCode, (array) $wpsc_ups_services ) === false ) {
								continue;
							}
						} else if ( $wpsc_ups_services != $serviceCode ) {
							continue;
						}
					}
					if ( array_key_exists( $serviceCode, (array) $this->Services ) ) {
						$rate_table[ $this->Services[ $serviceCode ] ] = array( $currCode, $price );
					}
				} // End foreach rated shipment block
			}
		}
		// Reverse sort the rate selection so it is cheapest first!
		if ( ! empty( $rate_table ) ) {
			asort( $rate_table );
		}
		return $rate_table;
	}

	private function _formatTable( $services, $currency = false ) {
		/* The checkout template expects the array to be in a certain
		 * format. This function will iterate through the provided
		 * services array and format it for use. During the loop
		 * we take advantage of the loop and translate the currency
		 * if necessary based off of what UPS tells us they are giving us
		 * for currency and what is set for the main currency in the settings
		 * area
		 */
		$converter = null;
		if ( $currency ) {
			$converter = new CURRENCYCONVERTER();
		}
		$finalTable = array();
		foreach ( array_keys( $services ) as $service ) {
			if ( $currency != false && $currency != $services[ $service ][0] ) {
				$temp = $services[ $service ][1];
				$services[ $service ][1] = $converter->convert(
					$services[ $service ][1],
					$currency,
					$services[ $service ][0]
				);
			}
			$finalTable[ $service ] = $services[ $service ][1];
		}
		return $finalTable;
	}

	function getQuote() {
		global $wpdb, $wpec_ash, $wpsc_cart, $wpec_ash_tools;
		// Arguments array for various functions to use
		$args = array();

		$args['dest_ccode'] = wpsc_get_customer_meta( 'shippingcountry' );

		// Get the ups settings from the ups account info page (Shipping tab)
		$wpsc_ups_settings = get_option( 'wpsc_ups_settings', array() );

		//Disable International Shipping. Default: Enabled, as it currently is.
		$args['intl_rate'] = isset( $wpsc_ups_settings['intl_rate'] ) && ! empty( $wpsc_ups_settings['intl_rate'] ) ? FALSE : TRUE;
		if ( ! $args['intl_rate'] && $args['dest_ccode'] != get_option( 'base_country' ) ) {
			return array();
		}

		// Destination zip code
		$args['dest_pcode'] = (string) wpsc_get_customer_meta( 'shippingpostcode' );

		if ( ! is_object( $wpec_ash_tools ) ) {
			$wpec_ash_tools = new ASHTools();
		}

		if ( empty( $args['dest_pcode'] ) && $wpec_ash_tools->needs_post_code( $args['dest_ccode'] ) ) {
			// We cannot get a quote without a zip code so might as well return!
			return array();
		}

		// Get the total weight from the shopping cart
		$args['weight'] = wpsc_cart_weight_total();
		if ( empty( $args['weight'] ) ) {
			return array();
		}

		$args['dest_state'] = '';

		$wpsc_country = new WPSC_Country( wpsc_get_customer_meta( 'shippingcountry' ) );

		if ( $wpsc_country->has_regions() ) {
			$wpsc_region = $wpsc_country->get_region( wpsc_get_customer_meta( 'shippingregion' )  );
			if ( $wpsc_region ) {
				$args['dest_state'] = $wpsc_region->get_code();
			}
		}

		if ( empty ( $args['dest_state'] ) ) {
			$args['dest_state'] = wpsc_get_customer_meta( 'shippingstate' );
		}

		if ( ! is_object( $wpec_ash ) ) {
			$wpec_ash = new ASH();
		}

		$shipping_cache_check['state']   = $args['dest_state']; //The destination is needed for cached shipment check.
		$shipping_cache_check['country'] = $args['dest_ccode'];
		$shipping_cache_check['zipcode'] = $args['dest_pcode'];

		$this->shipment = $wpec_ash->get_shipment();
		$this->shipment->set_destination( $this->internal_name, $shipping_cache_check ); //Set this shipment's destination.
		$this->shipment->rates_expire = date( 'Y-m-d' );
		$args['shipper'] = $this->internal_name;

		$args['singular_shipping'] = ( array_key_exists( 'singular_shipping', $wpsc_ups_settings ) ) ? $wpsc_ups_settings['singular_shipping']    : '0';
		if ( $args['weight'] > 150 && ! (boolean) $args['singular_shipping'] ) { // This is where shipping breaks out of UPS if weight is higher than 150 LBS
				$over_weight_txt = apply_filters(
						'wpsc_shipment_over_weight',
						__( 'Your order exceeds the standard shipping weight limit. Please contact us to quote other shipping alternatives.', 'wpsc' ),
						$args
					);
				$shipping_quotes[$over_weight_txt] = 0; // yes, a constant.
				$wpec_ash->cache_results( $this->internal_name, array( $shipping_quotes ), $this->shipment ); //Update shipment cache.
				return array( $shipping_quotes );
		}

		$cache = $wpec_ash->check_cache( $this->internal_name, $this->shipment ); //And now, we're ready to check cache.

		// We do not want to spam UPS (and slow down our process) if we already
		// have a shipping quote!
		if ( count( $cache['rate_table'] ) >= 1 ) {
			return $cache['rate_table'];
		}

		// Final rate table
		$rate_table = array();

		// API Auth settings //
		$args['username']          = ( array_key_exists( 'upsaccount',           $wpsc_ups_settings ) ) ? $wpsc_ups_settings['upsusername']          : '';
		$args['password']          = ( array_key_exists( 'upspassword',          $wpsc_ups_settings ) ) ? $wpsc_ups_settings['upspassword']          : '';
		$args['api_id']            = ( array_key_exists( 'upsid',                $wpsc_ups_settings ) ) ? $wpsc_ups_settings['upsid']                : '';
		$args['account_number']    = ( array_key_exists( 'upsaccount',           $wpsc_ups_settings ) ) ? $wpsc_ups_settings['upsaccount']           : '';
		$args['negotiated_rates']  = ( array_key_exists( 'ups_negotiated_rates', $wpsc_ups_settings ) ) ? $wpsc_ups_settings['ups_negotiated_rates'] : '';
		$args['residential']       = $wpsc_ups_settings['49_residential'];
		$args['insured_shipment']  = ( array_key_exists( 'insured_shipment',     $wpsc_ups_settings ) ) ? $wpsc_ups_settings['insured_shipment']     : '0';
		// What kind of pickup service do you use ?
		$args['DropoffType']       = $wpsc_ups_settings['DropoffType'];
		$args['packaging']         = $wpsc_ups_settings['48_container'];
		// Preferred Currency to display
		$currency_data = WPSC_Countries::get_currency_code( get_option( 'currency_type' ) );
		if ( ! empty( $currency_data ) ) {
			$args['currency'] = $currency_data;
		} else {
			$args['currency'] = 'USD';
		}
		// Shipping billing / account address
		$region = new WPSC_Region( get_option( 'base_country' ), get_option( 'base_region' ) );

		$args['shipr_state'] = $region->get_code();
		$args['shipr_city']  = get_option( 'base_city' );
		$args['shipr_ccode'] = get_option( 'base_country' );
		$args['shipr_pcode'] = get_option( 'base_zipcode' );

		// Physical Shipping address being shipped from
		$args['shipf_state'] = $args['shipr_state'];
		$args['shipf_city']  = $args['shipr_city'];
		$args['shipf_ccode'] = $args['shipr_ccode'];
		$args['shipf_pcode'] = $args['shipr_pcode'];
		$args['units']       = 'LBS';

		$args['cart_total'] = $wpsc_cart->calculate_subtotal( true );
		$args = apply_filters( 'wpsc_shipment_data', $args, $this->shipment );
		if ( isset( $args['stop'] ) ) { //Do not get rates.
			return array();
		}
		// Build the XML request
		$request = $this->_buildRateRequest( $args );
		// Now that we have the message to send ... Send it!
		$raw_quote = $this->_makeRateRequest( $request );
		// Now we have the UPS response .. unfortunately its not ready
		// to be viewed by normal humans ...
		$quotes = $this->_parseQuote( $raw_quote );
		// If we actually have rates back from UPS we can use em!
		if ( $quotes != false ) {
			$rate_table = apply_filters( 'wpsc_rates_table', $this->_formatTable( $quotes, $args['currency'] ), $args, $this->shipment );
		} else {
			if ( isset( $wpsc_ups_settings['upsenvironment'] ) ) {
				echo '<strong>:: GetQuote ::DEBUG OUTPUT::</strong><br />';
				echo 'Arguments sent to UPS';
				print_r( $args );
				echo '<hr />';
				print $request;
				echo '<hr />';
				echo 'Response from UPS';
				echo $raw_quote;
				echo '</strong>:: GetQuote ::End DEBUG OUTPUT::';
			}
		}

		$wpec_ash->cache_results(
			$this->internal_name,
			$rate_table,
			$this->shipment
		);

		// return the final formatted array !
		return $rate_table;
	}

	// Empty Function, this exists just b/c it is prototyped elsewhere
	function get_item_shipping() {
	}
}
$ash_ups = new ash_ups();
$wpsc_shipping_modules[ $ash_ups->getInternalName() ] = $ash_ups;
