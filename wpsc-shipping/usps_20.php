<?php
/**
 * New USPS module for V4 Domestic RateRequest API and V2 International Rate Request
 * @author Greg Gullett (ecsquest.net) and Instinct.co.nz
 * @version 2.1
 */
class ash_usps {

	/**
	 * The USPS User ID for the API user Account
	 * @var string
	 */
	var $usps_id;

	/**
	 * The USPS password for the API user account
	 * @var string
	 * @deprecated Deprecated since 2.0
	 */
	var $usps_password;

	/**
	 * The name that the USPS class identifies itself as to internal systems
	 * default "usps" Don't change unless you know what you are doing!
	 * @var string
	 */
	var $internal_name = "usps";

	/**
	 *
	 * The external name that the USPS class identifies itself.
	 * This is the "prettier" version of $internal_name to be shown to end-users.
	 * @var string
	 */
	var $name = "USPS";

	/**
	 * This flag is used by WP-E-Commerce to denote whether or not
	 * it accesses an external API to provide shipping rates and requires cURL
	 * @var boolean
	 */
	var $is_external = TRUE;

	/**
	 * This flag is used by WP-E-Commerce to denote whether or not
	 * it requires a zipcode to process the quote.
	 * @var boolean
	 */
	var $needs_zipcode = TRUE;

	/**
	 * This flag is used by USPS (Not locked to WPEC) to denote which
	 * endpoint / rating environment it is to use.
	 * True = Use Testing Environment API Endpoint
	 * False = Use Production Environment API Endpoint
	 * @since 2.0
	 * @var boolean
	 */
	var $use_test_env = FALSE;

	/**
	 * This stores an ASHShipment instance object used in rating
	 * @var ASHShipment|Null
	 */
	var $shipment = NULL;


	/**
	 * Constructor for USPS class
	 * Automatically loads services that are available into the class instance
	 * @since 1.0
	 */
	function ash_usps() {
		$this->_load_services();
		return TRUE;
	}

	/**
	 * retrieves the USPS ID, not used
	 * This function only exists due to legacy code
	 * @since 1.0
	 * @deprecated deprecated since version 2.0
	 * @return string
	 */
	function getID() {
	   return $this->$usps_id;
	}

	/**
	 * Sets the USPS ID, not used
	 * This function only exists due to legacy code, unused
	 * @since 1.0
	 * @param int $id
	 * @deprecated deprecated since version 2.0
	 */
	function setId( $id ) {
		$this->$usps_id = $id;
	}

	/**
	 * Retrieves the external display name for the module
	 * @since 1.0
	 * @return string
	 */
	function getName() {
		return $this->name;
	}

	/**
	 * Retrieves internal name of the module
	 * @since 1.0
	 * @return string
	 */
	function getInternalName() {
		return $this->internal_name;
	}

	/**
	 * Houses the list of services available to USPS API.
	 * The majority is commented out until a proper
	 * Service->Package map can be created
	 * @author Greg Gullett (greg@ecsquest.com)
	 * @since 2.0
	 */
	function _load_services() {
		$services = array(
			// "Online Only *"=>"ONLINE",
			// "All Services"=>"ALL",
			__( "Parcel Post", 'wpsc' ) => "PARCEL",
			// "Media Mail"=>"MEDIA",
			// "Library Mail"=>"LIBRARY",
			__( "First Class", 'wpsc' ) => "FIRST CLASS",
			// "First Class Hold For Pickup Commercial"=>"FIRST CLASS HFP COMMERCIAL",
			__( "Priority Mail", 'wpsc' ) => "PRIORITY",
			// "Priority Commercial"=>"PRIORITY COMMERCIAL",
			// "Priority Hold For Pickup Commercial"=>"PRIORITY HFP COMMERCIAL",
			__( "Express Mail", 'wpsc' ) => "EXPRESS",
			// "Express Commerical"=>"EXPRESS COMMERCIAL",
			// "Express SH"=>"EXPRESS SH",
			// "Express SH Commercial"=> "EXPRESS SH COMMERCIAL",
			// "Express Hold for Pickup"=> "EXPRESS HFP",
			// "Express Hold for Pickup Commercial"=>"EXPRESS HFP COMMERCIAL"
		);
		$this->services = $services;

	}

	/**
	 * Provides the appropriate endpoint for the API to use to
	 * retrieve rates from USPS
	 * @author Greg Gullett (greg@ecsquest.com)
	 * @since 2.0
	 * @param boolean $intl Flag denotes if we are getting international rates or not, Default FALSE
	 * @return string The endpoint / URL
	 */
	function _get_endpoint( $intl = FALSE ){
		$end_points = array(
			"prod" => array( "server" => "production.shippingapis.com",
				"dll" => "ShippingAPI.dll"
			),
			"test" => array( "server" => "testing.shippingapis.com",
				"dll" => "ShippingAPITest.dll"
			),
		);

		$api = "RateV4";
		if ( $intl ) {
			$api = "IntlRateV2";
		}

		$env = "prod";
		if ( (bool) $this->use_test_env === true ) {
			$env = "test";
		}

		return "http://" . $end_points[$env]["server"] . "/" . $end_points[$env]["dll"] . "?" . $api;
	}

	/**
	 * Returns the settings form that controls the USPS API information
	 * @since 1.0
	 */
	function getForm() {
		$defaults = array(
			'test_server' => 0,
			'adv_rate'    => 0,
			'id'          => '',
			'services'    => array( 'ONLINE' ),
		);
		$settings = get_option( "wpec_usps", array() );
		$settings = array_merge( $defaults, $settings );
		$settings['services'] = array_merge( $defaults['services'], $settings['services'] );

		ob_start();
		?>
		<tr>
			<td><?php _e( 'USPS ID', 'wpsc' ); ?></td>
			<td>
				<input type='text' name='wpec_usps[id]' value='<?php esc_attr_e( $settings["id"] ); ?>' />
				<p class='description'><?php printf( __("Don't have a USPS API account? <a href='%s' target='_blank'>Register for USPS Web Tools</a>", 'wpsc' ), 'https://secure.shippingapis.com/registration/' ); ?></p>
				<p class='description'><?php _e( "Make sure your account has been activated with USPS - if you're unsure if this applies to you then please check with USPS", 'wpsc' ); ?></p>
			</td>
		</tr>

		<tr>
			<td><?php _e( 'Shipping Settings', 'wpsc' ); ?></td>
			<td>
				<label>
					<input type='checkbox' <?php checked( $settings['test_server'], 1 ); ?> name='wpec_usps[test_server]' value='1' />
					<?php _e( 'Use Test Server', 'wpsc' ); ?>
				</label>
				<br />

				<label>
					<input type='checkbox' <?php checked( $settings['adv_rate'], 1 ); ?> name='wpec_usps[adv_rate]' value='1' />
					<?php _e( 'Advanced Rates', 'wpsc' ); ?>
				</label>
				<p class='description'><?php _e( 'This setting will provide rates based on the dimensions from each item in your cart', 'wpsc' ); ?></p>
			</td>
		</tr>

		<?php
			$wpec_usps_services = $settings["services"];
		?>
		<tr>
			<td><?php _e( 'Select Services', 'wpsc' ); ?></td>
			<td>
				<div class="ui-widget-content multiple-select">
					<?php foreach ( $this->services as $label => $service ): ?>
						<input type="checkbox" id="wpec_usps_srv_<?php esc_attr_e( $service ); ?>" name="wpec_usps[services][]" value="<?php echo esc_attr_e( $service ); ?>" <?php checked( array_search( $service, $wpec_usps_services ) ); ?> />
				 		<label for="wpec_usps_srv_$service"><?php echo $label; ?></label>
				 		<br />
					<?php endforeach; ?>
				</div>
				<!--
				<span style=\"font-size: x-small\">".__("Online rates the following services only, when available",'wpsc')."
					<br />
					" . __( "US Domestic: Express Mail, Priority Mail", 'wpsc' ) . "
					<br />
					" . __( "International : Global Express Guarenteed, Express Mail Intl. , Priority Mail Intl.", 'wpsc' ) . "
				</span>
				<br />
				-->
			</td>
		</tr>

		<?php
			$mt_array = array(
				__( "Package", 'wpsc' ),
				__( "Envelope", 'wpsc' ),
				__( "Postcards or aerogrammes", 'wpsc' ),
				__( "Matter for the Blind", 'wpsc' )
				// "All"
			);
			$mt_selected = ( array_key_exists( "intl_pkg", $settings ) ) ? $settings["intl_pkg"] : __( "Package", 'wpsc' );
		?>
		<tr>
			<td><?php _e( "International Package Type", "wpsc" ); ?></td>
			<td>
				<select id="wpec_usps_intl_pkg" name="wpec_usps[intl_pkg]">
					<?php foreach ( $mt_array as $mt ): ?>
						<option value="<?php esc_attr_e( $mt ); ?>" <?php selected( $mt, $mt_selected );?> ><?php echo $mt; ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>

		<?php
			// If First Class, Online or All is selected then we need to know what Kind of First class
			// will be used.
			$fcl_types = array(
				__( "Parcel", 'wpsc' )   => "PARCEL",
				__( "Letter", 'wpsc' )   => "LETTER",
				__( "Flat", 'wpsc' )     => "FLAT",
				__( "Postcard", 'wpsc' ) => "POSTCARD"
			);
			$type_selected = ( array_key_exists( "fcl_type", $settings ) ) ? $settings["fcl_type"] : $fcl_types["Parcel"];
		?>
		<tr>
			<td><?php _e( "First Class Mail Type", "wpsc" ); ?></td>
			<td>
				<select id="first_cls_type" name="wpec_usps[fcl_type]">
					<?php foreach ( $fcl_types as $label => $value ): ?>
						<option value="<?php esc_attr_e( $value ); ?>" <?php selected( $value, $type_selected ); ?>><?php echo $label; ?></option>
					<?php endforeach; ?>
				</select>
				<br />
				<p class='description'><?php _e( "Note: Only used for First Class service rates if selected", "wpsc" ); ?></span>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * This is called when the form provided from get_form is submitted
	 * @since 1.0
	 */
	function submit_form() {
		// Completely revamped how these values are stored
		if ( ! empty( $_POST['wpec_usps'] ) ) {
			$settings = stripslashes_deep( $_POST['wpec_usps'] );
			update_option( 'wpec_usps', $settings );
		}
		return TRUE;
	}

	/**
	 * This is a temporary hack until I can
	 * build a UI to build "Service Packages" so you can designate
	 * all of these based on services
	 * @author Greg Gullett (greg@ecsquest.com)
	 * @since 2.0
	 * @param array $base
	 * @param string $service
	 * @param ASHPackage $package
	 */
	function _translate_package_options( &$base, $service, $package = FALSE ) {
		$container = "";
		$machinable = "true";
		$size = "REGULAR";
		switch ( $service ) {
			case "PRIORITY":
				$container = "VARIABLE";
				break;
			case "EXPRESS":
				$container = "VARIABLE";
				break;
			case "PARCEL":
				$container = "VARIABLE";
				$machinable = "true";
				$size = "REGULAR";
				break;
			case "ALL":
				$machinable = "true";
				break;
			case "ONLINE":
				$machinable = "true";
				break;
		}
		$base["Container"] = $container;
		$base["Size"] = $size;
		if ( $package ) {
			$base["Width"] = $package->width;
			$base["Length"] = $package->length;
			$base["Height"] = $package->height;
			$base["Girth"] = $package->girth;
			// $base["SpecialServices"] = "";  // Its here, not ready for it yet, think ASH 1.0 or higher.
		}
		$base["Machinable"] = $machinable;
	}

	/**
	 * Helper function that builds the list of packages for domestic rating
	 * @author Greg Gullett (greg@ecsquest.com)
	 * @since 2.0
	 * @param array reference $request
	 * @param array $data
	 * @param ASHPackage $package
	 * @return array
	 */
	function _build_domestic_shipment( &$request, $data, $package ) {
		$shipment = array();
		if ( $package ) {
			$data["weight"] = $package->weight;
		}
		$pound = floor( $data["weight"] );
		$ounce = ( $data["weight"] - $pound ) * 16;
		$data["pound"] = $pound;
		$data["ounce"] = $ounce;

		if ( ! array_key_exists( "services", (array) $data ) ) {
			$data["services"] = array( "ONLINE" );
		}
		$base = array(
			"ZipOrigination" => $data["base_zipcode"],
			"ZipDestination" => $data["dest_zipcode"],
			"Pounds" => $data["pound"],
			"Ounces" => $data["ounce"],
		);
		foreach ( $data["services"] as $label => $service ) {
			$temp = array();
			$temp["Service"] = $service;
			$temp["@attr"] = array( "ID" => count( $shipment ) );

			if ( $ounce > 13 || $pound > 1 ){
				if ( strpos( $service, "FIRST" ) === FALSE || $service == "ONLINE" ) {
					$temp["FirstClassMailType"] = $data["fcl_type"];
					$temp = array_merge( $temp, $base );
					$this->_translate_package_options( $temp, $service, $package );
					array_push( $shipment, $temp );
				}
			} else {
				if ( strpos( $service, "FIRST" ) !== FALSE || $service == "ONLINE" ) {
					$temp["FirstClassMailType"] = $data["fcl_type"];
					$temp = array_merge( $temp, $base );
					$this->_translate_package_options( $temp, $service, $package );
				} else {
					$temp = array_merge( $temp, $base );
					$this->_translate_package_options( $temp, $service, $package );
				}
				array_push( $shipment, $temp );
			}
		}
		$request[ $data["req"] ]["Package"] = $shipment;
	}

	/**
	 * Helper function that builds the list of packages for international rating
	 * @author Greg Gullett (greg@ecsquest.com)
	 * @since 2.0
	 * @param array reference $request
	 * @param array $data
	 * @param ASHPackage $package
	 * @return array
	 */
	function _build_intl_shipment( &$request, array $data, $package ) {
		$shipment = array();

		$data["pounds"] = floor( $package->weight );
		$data["ounces"] = ( $data["weight"] - $data["pounds"] ) * 16;

		if ( ! array_key_exists( "mail_type", (array) $data ) ) {
			$data["mail_type"] = array( "Package" );
		}

		$base = array(
			"Pounds"          => $data["pounds"],
			"Ounces"          => $data["ounces"],
			"Machinable"      => "True",
			"MailType"        => $data["mail_type"],
			"GXG"             => array(
				"POBoxFlag"   => "N",
				"GiftFlag"    => "N"
			),
			"ValueOfContents" => $data["value"],
			"Country"         => $data["dest_country"],
			"Container"       => "RECTANGULAR",
			"Size"            => "LARGE",
			"Width"           => $package->width,
			"Length"          => $package->length,
			"Height"          => $package->height,
			"Girth"           => $package->girth,
			"OriginZip"       => $data["base_zipcode"],
			"CommercialFlag"  => "Y"
		);

		$base["@attr"]["ID"] = 0;
		array_push( $shipment, $base );
		$request[$data["req"]]["Package"] = $shipment;
	}

	/**
	 * Used to build request to send to USPS API
	 * @author Greg Gullett (greg@ecsquest.com)
	 * @since 2.0
	 * @param array $data
	 * @return array
	 */
	function _build_request( &$data ) {
		global $wpec_ash_xml;
		if ( ! is_array( $data ) ) {
			return array();
		}
		$req = "RateV4Request";
		if ( $data["dest_country"] != "USA" ){
			$req = "IntlRateV2Request";
		}
		$data["req"] = $req;
		$request = array(
			$req => array(
				"@attr" => array(
					"USERID" => $data["user_id"]
				),
				"Revision" => "2"
			)
		);
		return $request;
	}

	/**
	 * Handles contacting the USPS server via cURL
	 * @author Greg Gullett (greg@ecsquest.com)
	 * @since 2.0
	 * @param string $request is the raw XML request
	 * @param boolean $intl flag to denote if it is US Domestic or International
	 * @return string XML Response from USPS API
	 */
	function _make_request( $request, $intl = false ) {
		// Get the proper endpoint to send request to
		$endpoint = $this->_get_endpoint( $intl );
		// Need to url encode the XML for the request
		$encoded_request = urlencode( $request );
		// Put endpoint and request together
		$url = $endpoint . "&XML=" . $encoded_request;
		// Make the request
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_NOPROGRESS, 1 );
		curl_setopt( $ch, CURLOPT_VERBOSE, 1 );
		@curl_setopt( $ch, CURLOPT_FOLLOWLOCATION,1 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 120 );
		curl_setopt( $ch, CURLOPT_USERAGENT, 'wp-e-commerce' );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$body = curl_exec( $ch );
		curl_close( $ch );

		return $body;
	}

	/**
	 * USPS seems to be not able to encode their own XML appropriately
	 * This function is used to fix their mistakes.
	 * @author Greg Gullett (greg@ecsquest.com)
	 * @since 2.0
	 * @param string $response Reference to the $response string
	 */
	function _clean_response( &$response ) {
		$bad_encoding = array( "&amp;lt;sup&amp;gt;&amp;amp;", ";&amp;lt;/sup&amp;gt;" );
		$good_encoding = array( "<sup>","</sup>" );
		$response = str_replace( $bad_encoding, $good_encoding, $response );
	}

	/**
	 * Parse the service out of the package
	 * @author Greg Gullett (greg@ecsquest.com)
	 * @since 2.0
	 * @param string $package
	 * @return string
	 */
	function _get_service( $ServiceTag, $package ) {
		global $wpec_ash_xml;
		$service = "";
		$temp_service = $wpec_ash_xml->get( $ServiceTag, $package );

		if ( $temp_service ) {
			$service = $temp_service[0];
		}

		preg_match( '/(.*?)<sup>/', $service, $temp );
		if ( ! empty( $temp ) ) {
			$service = $temp[1];
		}

		return $service;
	}

	/**
	 * Merges N-Many arrays together by key, without replacement
	 * @author Greg Gullett (greg@ecsquest.com)
	 * @since 2.0
	 * @param array $arrays
	 * @return array
	 */
	function _merge_arrays( array $arrays ) {
		$final_array = array();
		if ( ! is_array( $arrays ) ) {
			// How did that happen, I mean really, I am specifying array as the base type
			return $final_array;
		}
		foreach( $arrays as $arr ) {
			foreach( $arr as $key => $value ) {
				if ( ! array_key_exists( $key, $final_array ) ) {
					if ( $value ) {
						$final_array[ $key ] = $value;
					}
				} elseif ( $final_array[ $key ] < $value ) {
					$final_array[ $key ] = $value;
				}
			}
		}
		return $final_array;
	}

	/**
	 * This function parses the provided XML response from USPS to retrieve the final rates.
	 * @author Greg Gullett (greg@ecsquest.com)
	 * @since 2.0
	 * @param string $response The XML response from USPS
	 * @return array
	 */
	function _parse_domestic_response( $response ) {
		global $wpec_ash_xml;
		$package_services = array();
		$this->_clean_response( $response );

		$packages = $wpec_ash_xml->get( "Package", $response );
		if ( ! is_array( $packages ) ) {
			return array();
		}

		foreach ( $packages as $package ) {
			$temp = array();
			$postage_services = $wpec_ash_xml->get( "Postage", $package );
			if ( count( $postage_services ) == 1 ) {
				$postage_services = array( $package );
			}
			foreach ( $postage_services as $postage ) {
				$service_name = $this->_get_service( "MailService", $postage );
				$temp_rate = $wpec_ash_xml->get( "Rate", $postage );
				$rate = ( ! empty( $temp_rate ) ) ? $temp_rate[0] : 0.0;
				if ( ! empty( $service_name ) ) {
					$temp[ $service_name ] = apply_filters( 'wpsc_usps_domestic_rate', $rate, $service_name );
				}
			}
			array_push( $package_services, $temp );
		}
		return $package_services;
	}

	/**
	 * This function parses the provided XML response for international requests
	 * from USPS to retrieve the final rates as an array.
	 * @author Greg Gullett (greg@ecsquest.com)
	 * @since 2.0
	 * @param string $response The XML response from USPS
	 * @return array
	 */
	function _parse_intl_response( $response ) {
		global $wpec_ash_xml;
		$services_table = array();
		$this->_clean_response( $response );

		$services = $wpec_ash_xml->get( "Service", $response );
		if ( empty( $services ) ) {
			return array();
		}
		foreach ( $services as $service ) {
			$service_name = $this->_get_service( "SvcDescription", $service );
			$temp_rate = $wpec_ash_xml->get( "Postage", $service );
			$rate = ( ! empty( $temp_rate ) ) ? $temp_rate[0] : 0.0;
			if ( ! empty( $service_name ) ) {
				$service_table[ $service_name ] = apply_filters( 'wpsc_usps_intl_rate', $rate, $service_name );
			}
		}
		return $service_table;
	}

	/**
	 * Returns an array using the common keys from all arrasy and the sum of those common keys values;
	 * @author Greg Gullett (greg@ecsquest.com)
	 * @since 2.0
	 * @param array $rate_tables
	 * @return array
	 */
	function _combine_rates( $rate_tables ) {
		$final_table = array();
		if ( ! is_array( $rate_tables ) ) {
			return array();
		}
		if ( count( $rate_tables ) < 2 ) {
			return $rate_tables[0];
		}
		$temp_services = call_user_func_array( "array_intersect_key", $rate_tables );

		$valid_services = array_keys( $temp_services );
		foreach ( $rate_tables as $rate_table ) {
			foreach ( $rate_table as $service => $rate ) {
				if ( in_array( $service, $valid_services ) ) {
					if ( ! array_key_exists( $service, $final_table ) ) {
						$final_table[ $service ] = 0;
					}
					$final_table[ $service ] += $rate;
				}
			}
		}
		return $final_table;
	}

	/**
	 * Merges arrays and adds the values of common keys.
	 * @author Greg Gullett (greg@ecsquest.com)
	 * @since 2.0
	 * @param array $arrays
	 * @return array
	 */
	function merge_sum_arrays( $arrays ) {
		$temp = array();
		if ( ! is_array( $arrays ) ) {
			return array();
		}
		if ( count( $arrays ) > 1 ) {
			$temp_arr = call_user_func_array( "array_intersect_key", $arrays );
			$intersect_keys = array_keys( (array) $temp_arr );
		} else {
			$intersect_keys = array_keys( $arrays[0] );
		}

		foreach ( $arrays as $arr ) {
			foreach ( $arr as $key => $value ) {
				if ( in_array( $key, (array) $intersect_keys ) ) {
					if ( ! array_key_exists( $key, $temp ) ) {
						$temp[ $key ] = 0;
					}
					$temp[ $key ] += $value;
				}
			}
		}
		return $temp;
	}

	/**
	 * Runs the quote process for a simple quote and returns the final quote table
	 * @author Greg Gullett (greg@ecsquest.com)
	 * @since 2.0
	 * @param array $data
	 * @return array
	 */
	function _quote_simple( array $data ) {
		global $wpec_ash_xml;
		//*** Build Request **\\
		$request = $this->_build_request( $data );
		if ( empty( $request ) ) {
			return array();
		}
		$this->_build_domestic_shipment( $request, $data, FALSE );
		$request_xml = $wpec_ash_xml->build_message( $request );
		//*** Make the Request ***\\
		$response = $this->_make_request( $request_xml, FALSE );
		if ( empty( $response ) || $response === FALSE ) {
			return array();
		}
		//*** Parse the response from USPS ***\
		$package_rate_table = $this->_parse_domestic_response( $response );
		$rate_table = $this->_merge_arrays( $package_rate_table );
		return $rate_table;
	}

	/**
	 * Runs the quote process for an advanced quote and returns the final quote table
	 * @author Greg Gullett (greg@ecsquest.com)
	 * @since 2.0
	 * @param array $data
	 * @return array
	 */
	function _quote_advanced( array $data ) {
		global $wpec_ash_xml;
		$rate_tables = array();
		foreach ( $this->shipment->packages as $package ) {
			$temp_data = $data;
			$request = $this->_build_request( $temp_data );
			if ( empty( $request ) ) {
				continue;
			}
			$this->_build_domestic_shipment( $request, $temp_data, $package );
			$request_xml = $wpec_ash_xml->build_message( $request );
			//*** Make the Request ***\\
			$response = $this->_make_request( $request_xml, FALSE );
			if ( empty( $response ) ) {
				continue;
			}
			//*** Parse the Response ***\\
			$package_rate_table = $this->_parse_domestic_response( $response );
			//*** Reformat the array structure ***\\
			$temp = $this->_merge_arrays( $package_rate_table );

			array_push( $rate_tables, $temp );
		}

		$rates = $this->merge_sum_arrays( $rate_tables );
		return $rates;
	}

	/**
	 * Runs the quote process for an international quote and returns the final quote table
	 * @author Greg Gullett (greg@ecsquest.com)
	 * @since 2.0
	 * @param array $data
	 * @return array
	 */
	function _quote_intl( array $data ) {
		global $wpec_ash_xml;
		$rate_tables = array();
		foreach ( $this->shipment->packages as $package ) {
			$temp_data = $data;
			$request = $this->_build_request( $temp_data );
			if ( empty( $request ) ) {
				continue;
			}
			$this->_build_intl_shipment( $request, $temp_data, $package );
			$request_xml = $wpec_ash_xml->build_message( $request );
			//*** Make the Request ***\\
			$response = $this->_make_request( $request_xml, TRUE );
			if ( empty( $response ) || $response === FALSE ) {
				continue;
			}
			$rate_table = $this->_parse_intl_response( $response );
			array_push( $rate_tables, $rate_table );
		}
		$rates = $this->_combine_rates( $rate_tables );
		return $rates;
	}

	/**
	 * Returns an updated country based on several rules that USPS has
	 * @author Greg Gullett (greg@ecsquest.com)
	 * @since 2.0
	 * @param string $full_name The countries full name
	 * @return string
	 *
	 * ::rules::
	 *   U.K. Is an invalid name, they use Great Britain and Northern Ireland
	 *   Any US Posession is rated as USA
	 */
	function _update_country( $full_name ) {
		$us_posessions = array(
			"Puerto Rico",
			"Virgin Islands (USA)",
			"USA Minor Outlying Islands",
			"Guam (USA)"
		);
		if ( in_array( $full_name, $us_posessions ) ) {
			return "USA";
		}
		if ( $full_name == "U.K.") {
			return 'Great Britain and Northern Ireland';
		}
		return $full_name;
	}

	/**
	 * Takes a rate table and returns a new table with only services selected in the back end
	 * @author Greg Gullett (greg@ecsquest.com)
	 * @since 2.0
	 * @param array $rate_table
	 * @param array $data
	 * @return array
	 */
	function _validate_services( $rate_table, $data ) {
		global $wpdb;
		if ( ! is_array( $rate_table ) ) {
			return array();
		}
		$final_table = array();
		$services = array();
		foreach ( $this->services as $service => $code ) {
			if ( in_array( $code, $data["services"] ) ) {
				$services[ $service ] = $code;
			}
		}
		$valid_services = array_intersect_key( (array) $rate_table, $services );
		return $valid_services;
	}

	/**
	 * This function handles the process of getting a quote.
	 * It is kept abstracted from the entry points so you can
	 * implement a testing framework separate from wordpress.
	 * @author Greg Gullett (greg@ecsquest.com)
	 * @since 2.0
	 * @param array $data This is an array that USPS uses to build its request
	 *
	 * Expected Values for $data:
	 * Required : String : "fcl_type"   : Is the First Class Package Type ("Package", "Envelope","Postcards or aerogrammes", "Matter for the Blind", "All")
	 * Required : Int : "base_zipcode"  : The originating zipcode where the shipment is from
	 * Required : String : "user_id"    : USPS user ID
	 * Required : Array : "services"    : List of services to get rates for, One or More services required
	 */
	function _run_quote( array $data ) {
		global $wpec_ash_tools;
		//************** These values are common to all entry points **************
		//*** Grab Total Weight from the shipment object for simple shipping
		$data["weight"] = $this->shipment->total_weight;
		//*** User/Customer Entered Values ***\\
		$data["dest_zipcode"] = $this->shipment->destination["zipcode"];
		if ( empty( $data["weight"] ) ) {
			return array();
		}

		if ( empty( $data["dest_zipcode"] ) ) {
			return array();
		}

		if ( $wpec_ash_tools->is_military_zip( $data["dest_zipcode"] ) ) {
			$data["dest_country"] = "USA";
		}
		//\\************** END common config **************\\//
		//*** Get the Quotes ***\\
		$quotes = array();
		if ( $data["dest_country"] == "USA" && $data["adv_rate"] == TRUE ){
			$quotes = $this->_quote_advanced( $data );
		} elseif ( $data["dest_country"] != "USA") {
			$quotes = $this->_quote_intl( $data );
		} else {
			$quotes = $this->_quote_simple( $data );
		}
		$rate_table = $this->_validate_services( $quotes, $data );
		return $quotes;
	}

	/**
	 * This function is used to provide rates for single items
	 * Due to the nature of external calculators it is too costly to use this
	 * @deprecated Do Not Use
	 */
	function get_item_shipping() {
	}

	/**
	 * General entry point for WPEC external shipping calculator
	 * This function expects no arguments but requires POST data
	 * and configuration from the plugin settings
	 * @return array $rate_table List of rates in "Service"=>"Rate" format
	 */
	function getQuote() {
		global $wpdb, $wpec_ash, $wpec_ash_tools;
		if ( ! is_object( $wpec_ash ) ) {
			$wpec_ash = new ASH();
		}
		if ( ! is_object( $wpec_ash_tools ) ) {
			$wpec_ash_tools = new ASHTools();
		}

		$this->shipment = $wpec_ash->get_shipment();
		$this->shipment->set_destination( $this->internal_name );
		// Check to see if the cached shipment is still accurate, if not we need new rate
		$cache = $wpec_ash->check_cache( $this->internal_name, $this->shipment );

		if ( $cache ) {
			return $cache["rate_table"];
		}

		$data = array();
		//*** WPEC Configuration values ***\\
		$settings = get_option( "wpec_usps" );
		$this->use_test_env   = ( ! isset( $settings["test_server"] ) ) ? false : ( bool ) $settings['test_server'];
		$data["fcl_type"]     = ( ! empty( $settings["fcl_type"] ) ) ? $settings["fcl_type"] : "PARCEL";
		$data["mail_type"]    = ( ! empty( $settings["intl_pkg"] ) ) ? $settings["intl_pkg"] : "Package";
		$data["base_zipcode"] = get_option( "base_zipcode" );
		$data["services"]     = ( ! empty( $settings["services"] ) ) ? $settings["services"] : array( "PRIORITY", "EXPRESS", "FIRST CLASS" );
		$data["user_id"]      = $settings["id"];
		$data["adv_rate"]     = ( ! empty( $settings["adv_rate"] ) ) ? $settings["adv_rate"] : FALSE;   // Use advanced shipping for Domestic Rates ? Not available
		//*** Set up the destination country ***\
		$country              = $this->shipment->destination["country"];
		$data["dest_country"] = $wpec_ash_tools->get_full_country( $country );
		$data["dest_country"] = $this->_update_country( $data["dest_country"] );
		//************ GET THE RATE ************\\
		$rate_table           = $this->_run_quote( $data );
		//************ CACHE the Results ************\\
		$wpec_ash->cache_results( $this->internal_name, $rate_table, $this->shipment );
		return $rate_table;
	}

	/**
	 * A testing entrypoint to run a quote without
	 * access to wordpress/wpec settings & database
	 * @see run_quote() for required $data values
	 * @param array $data
	 */
	function test( $data, $shipment ) {
		$this->shipment = $shipment;
		return $this->_run_quote( $data );
	}

}
$ash_usps = new ash_usps();
$wpsc_shipping_modules[ $ash_usps->getInternalName() ] = $ash_usps;
