<?php
/**
 * New USPS module for V4 Domestic RateRequest API and V2 International Rate Request
 *
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
	 * it requires a zip code to process the quote.
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
	public function __construct() {
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
	 * @since 2.0
	 */
	function _load_services() {
		$services = array(
			// "Online Only *"=>"ONLINE",
			// "All Services"=>"ALL",
			// "Library Mail"=>"LIBRARY",
			__( "First Class", 'wp-e-commerce' ) => "FIRST CLASS",
			__( "Standard Post *", 'wp-e-commerce' ) => "STANDARD POST",
			// "First Class Metered"=>"FIRST CLASS METERED",
			// "First Class Commercial"=>"FIRST CLASS COMMERCIAL",
			// "First Class Hold For Pickup Commercial"=>"FIRST CLASS HFP COMMERCIAL",
			__( "Priority Mail *", 'wp-e-commerce' ) => "PRIORITY",
			// "Priority Commercial"=>"PRIORITY COMMERCIAL",
			// "Priority CPP"=>"PRIORITY CPP",
			// "Priority Hold For Pickup Commercial"=>"PRIORITY HFP COMMERCIAL",
			// "Priority Hold For Pickup CPP"=>"PRIORITY HFP CPP",
			__( "Priority Express", 'wp-e-commerce' ) => "PRIORITY EXPRESS",
			// "Priority Express Commerical"=>"PRIORITY EXPRESS COMMERCIAL",
			// "Priority Express CPP"=>"PRIORITY EXPRESS CPP",
			// "Priority Express SH"=>"PRIORITY EXPRESS SH",
			// "Priority Express SH Commercial"=> "PRIORITY EXPRESS SH COMMERCIAL",
			// "Priority Express Hold for Pickup"=> "PRIORITY EXPRESS HFP",
			// "Priority Express Hold for Pickup Commercial"=>"PRIORITY EXPRESS HFP COMMERCIAL"
			// "Priority Express Hold for Pickup CPP"=>"PRIORITY EXPRESS HFP CPP"
			__( "Media Mail **", 'wp-e-commerce' ) => "MEDIA" ,
		);
		$this->services = $services;

	}

	/**
	 * Provides the appropriate endpoint for the API to use to
	 * retrieve rates from USPS
	 *
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

		$api = "API=RateV4";			//"API=" was missing https://www.usps.com/business/web-tools-apis/price-calculators.htm.
		if ( $intl ) {
			$api = "API=IntlRateV2";	//"API=" was missing https://www.usps.com/business/web-tools-apis/price-calculators.htm.
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
			<td><?php _e( 'USPS ID', 'wp-e-commerce' ); ?></td>
			<td>
				<input type='text' name='wpec_usps[id]' value='<?php echo esc_attr( $settings["id"] ); ?>' />
				<p class='description'><?php printf( __("Don't have a USPS API account? <a href='%s' target='_blank'>Register for USPS Web Tools</a>", 'wp-e-commerce' ), 'https://registration.shippingapis.com/' ); ?></p>
				<p class='description'><?php _e( "Make sure your account has been activated with USPS. If you're unsure if this applies to you, then please check with USPS.", 'wp-e-commerce' ); ?></p>
				<p class='description'><?php printf( __("Once you've completed integration, <a href='%s' target='_blank'>you'll need to submit a request to promote tools to production</a>.", 'wp-e-commerce' ), 'https://www.usps.com/business/web-tools-apis/developers-center.htm#learn-more--1-1' ); ?></p>
			</td>
		</tr>

		<tr>
			<td><?php _e( 'Shipping Settings', 'wp-e-commerce' ); ?></td>
			<td>
				<label>
					<input type='checkbox' <?php checked( isset( $settings['test_server'] ) && (bool) $settings['test_server'], 1 ); ?> name='wpec_usps[test_server]' value='1' />
					<?php _e( 'Use Test Server', 'wp-e-commerce' ); ?>
				</label>
				<br />

				<label>
					<input type='checkbox' <?php checked( isset( $settings['adv_rate'] ) && (bool) $settings['adv_rate'], 1 ); ?> name='wpec_usps[adv_rate]' value='1' />
					<?php _e( 'Advanced Rates', 'wp-e-commerce' ); ?>
				</label>
				<p class='description'><?php _e( 'This setting will provide rates based on the dimensions from each item in your cart', 'wp-e-commerce' ); ?></p>
				<label>
					<input type='checkbox' <?php checked( isset( $settings['intl_rate'] ) && (bool) $settings['intl_rate'], 1 ); ?> name='wpec_usps[intl_rate]' value='1' />
					<?php _e( 'Disable International Shipping', 'wp-e-commerce' ); ?>
				</label>
				<p class='description'><?php _e( 'No shipping rates will be displayed if the shipment destination country is different than your base country/region.', 'wp-e-commerce' ); ?></p>
			</td>
		</tr>

		<?php
			$wpec_usps_services = $settings["services"];
		?>
		<tr>
			<td><?php _e( 'Select Services', 'wp-e-commerce' ); ?></td>
			<td>
				<div class="ui-widget-content multiple-select">
					<?php foreach ( $this->services as $label => $service ): ?>
						<input type="checkbox" id="wpec_usps_srv_<?php echo sanitize_title( $service ); ?>" name="wpec_usps[services][]" value="<?php echo esc_attr( $service ); ?>" <?php checked( (bool) array_search( $service, $wpec_usps_services ) ); ?> />
				 		<label for="wpec_usps_srv_<?php echo sanitize_title( $service ); ?>"><?php echo esc_html( $label ); ?></label>
				 		<br />
					<?php endforeach; ?>
				</div>
				<p class='description'><?php _e( "* Standard Post should never be used as the sole USPS mail service provided. It's only available for destinations located far from your base zip code. In this case, and to provide shipping coverage for locations closer to your base zip code, the Priority Mail service must be selected too.", 'wp-e-commerce' ); ?></p>
				<p class='description'><?php printf( __("** Media Mail must only be used for books, printed material and sound or video recordings (CDs, DVDs, Blu-rays and other, excluding games). It may be subjected to postal inspection to enforce this. For more information, please consult the <a href='%s' target='_blank'>Media Mail's Rules & Restrictions web page.</a>", 'wp-e-commerce' ), 'https://www.usps.com/ship/media-mail.htm' ); ?></p>
			</td>
		</tr>

		<?php
			$mt_array = array(
				__( "Package", 'wp-e-commerce' ),
				__( "Envelope", 'wp-e-commerce' ),
				__( "Postcards or aerogrammes", 'wp-e-commerce' ),
				__( "Matter for the Blind", 'wp-e-commerce' )
			);

			$mt_selected = ( array_key_exists( "intl_pkg", $settings ) ) ? $settings["intl_pkg"] : __( "Package", 'wp-e-commerce' );
		?>
		<tr>
			<td><?php _e( "International Package Type", 'wp-e-commerce' ); ?></td>
			<td>
				<select id="wpec_usps_intl_pkg" name="wpec_usps[intl_pkg]">
					<?php foreach ( $mt_array as $mt ): ?>
						<option value="<?php echo esc_attr( $mt ); ?>" <?php selected( $mt, $mt_selected );?> ><?php echo $mt; ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>

		<?php
			// If First Class, Online or All is selected then we need to know what Kind of First class
			// will be used.
			$fcl_types = array(
				__( "Parcel", 'wp-e-commerce' )   => "PARCEL",
				__( "Letter", 'wp-e-commerce' )   => "LETTER",
				__( "Flat", 'wp-e-commerce' )     => "FLAT",
				__( "Postcard", 'wp-e-commerce' ) => "POSTCARD"
			);
			$type_selected = ( array_key_exists( "fcl_type", $settings ) ) ? $settings["fcl_type"] : $fcl_types["Parcel"];
		?>
		<tr>
			<td><?php _e( "First Class Mail Type", 'wp-e-commerce' ); ?></td>
			<td>
				<select id="first_cls_type" name="wpec_usps[fcl_type]">
					<?php foreach ( $fcl_types as $label => $value ): ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $type_selected ); ?>><?php echo $label; ?></option>
					<?php endforeach; ?>
				</select>
				<br />
				<p class='description'><?php _e( "Note: Only used for First Class service rates if selected", 'wp-e-commerce' ); ?></p>
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
		return true;
	}

	/**
	 * This is a temporary hack until I can
	 * build a UI to build "Service Packages" so you can designate
	 * all of these based on services
	 *
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
			case "PRIORITY EXPRESS":
				$container = "VARIABLE";
				break;
			case "STANDARD POST":
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
		if ( $package && ( (float)$package->width > 12 || (float)$package->length > 12 || (float)$package->height > 12  ) ) {
			if ( $container == "VARIABLE") {
				$container = "RECTANGULAR";
			}
			$size = "LARGE";
		}
		$base["Container"] = apply_filters( 'wpsc_usps_container', $container );
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
	 *
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
		$ounce = ceil( ( $data["weight"] - $pound ) * 16 ); //"Ounces field < 5 digits" See 1.2 -> http://pe.usps.com/text/dmm300/133.htm
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
	 *
	 * @since 2.0
	 * @param array reference $request
	 * @param array $data
	 * @param ASHPackage $package
	 * @return array
	 */
	function _build_intl_shipment( &$request, array $data, $package ) {
		$shipment = array();
		$data["size"]	= "REGULAR"; //No dimensions needed, but USPS says no GXG pricing returned.
		$data["width"]	= "";
		$data["length"]	= "";
		$data["height"]	= "";
		$data["girth"]	= "";

		if ( $package ) {
			$data["weight"]	= $package->weight;
			$data["value"]	= $package->value;
			if ( (float)$package->width > 12 || (float)$package->length > 12 || (float)$package->height > 12 ) {
				$data['size']	= "LARGE";
			}
			$data["width"]	= $package->width;
			$data["length"]	= $package->length;
			$data["height"]	= $package->height;
			$data["girth"]	= $package->girth;
		}

		$data["pounds"] = floor( $data["weight"] );
		$data["ounces"] = ceil( ( $data["weight"] - $data["pounds"] ) * 16 ); //"Ounces field < 5 digits" See 1.2 -> http://pe.usps.com/text/dmm300/133.htm

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
			"Container"       => apply_filters( 'wpsc_usps_container', "RECTANGULAR" ),
			"Size"            => $data["size"],
			"Width"           => $data["width"],
			"Length"          => $data["length"],
			"Height"          => $data["height"],
			"Girth"           => $data["girth"],
			"OriginZip"       => $data["base_zipcode"],
			"CommercialFlag"  => "Y"
		);

		if ( ! $package ) {
			unset( $base["GXG"] ); //Error returned if present and no dimensions are set.
		}
		$base["@attr"]["ID"] = 0;
		array_push( $shipment, $base );
		$request[$data["req"]]["Package"] = $shipment;
	}

	/**
	 * Used to build request to send to USPS API
	 *
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
	 *
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

		$request = wp_safe_remote_post(
			$url,
			array(
				'httpversion' => '1.1',
				'user-agent'  => 'wp-e-commerce',
				'redirection' => 10,
				'timeout'     => 120
			)
		);

		return wp_remote_retrieve_body( $request );
	}

	/**
	 * USPS seems to be not able to encode their own XML appropriately
	 * This function is used to fix their mistakes.
	 *
	 * @since 2.0
	 * @param string $response Reference to the $response string
	 */
	function _clean_response( &$response ) {
		$response = str_replace('&amp;lt;sup&amp;gt;&amp;#174;&amp;lt;/sup&amp;gt;', '&reg;', $response);
		$response = str_replace('&amp;lt;sup&amp;gt;&amp;#8482;&amp;lt;/sup&amp;gt;', '&trade;', $response);
		$response = str_replace('&amp;lt;sup&amp;gt;&amp;#xAE;&amp;lt;/sup&amp;gt;', '&reg;', $response);
		$response = wp_specialchars_decode( wp_specialchars_decode( $response ) );
		return $response;
	}

	/**
	 * Parse the service out of the package
	 *
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
	 *
	 * @since 2.0
	 * @param array $arrays
	 * @return array
	 */
	function _merge_arrays( array $arrays ) {
		$final_array = array();
		if ( ! is_array( $arrays ) || count( $arrays ) == 0 ) {
			// How did that happen, I mean really, I am specifying array as the base type
			return $final_array;
		}
		if ( count( $arrays ) != count( $arrays, COUNT_RECURSIVE ) ) {
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
		} else {
			$final_array = $arrays;
		}
		return $final_array;
	}

	/**
	 * This function parses the provided XML response from USPS to retrieve the final rates.
	 *
	 * @since 2.0
	 * @param string $response The XML response from USPS
	 * @return array
	 */
	function _parse_domestic_response( $response ) {
		global $wpec_ash_xml;

		$package_services = array();

		$this->_clean_response( $response );

		$packages = $wpec_ash_xml->get( "Package", $response );
		$errors   = '';

		if ( ! is_array( $packages ) ) {
			return array();
		}

		foreach ( $packages as $package ) {

			if ( stripos( $package, '<ERROR>') === 0 ) {

				$errors = $wpec_ash_xml->get( "Description", $package );

			} else {

				$postage_services = $wpec_ash_xml->get( "Postage", $package );

				if ( count( $postage_services ) == 1 ) {
					$postage_services = array( $package );
				}

				foreach ( $postage_services as $postage ) {
					$temp = array();

					$service_name = $this->_get_service( "MailService", $postage );
					$temp_rate    = $wpec_ash_xml->get( "Rate", $postage );

					if ( ! empty( $temp_rate ) ) {
						$rate = $temp_rate[0];
					} else {
						continue;
					}

					if ( ! empty( $service_name ) ) {
						$temp[ $service_name ] = apply_filters( 'wpsc_usps_domestic_rate', $rate, $service_name );
					}
				}

				array_push( $package_services, $temp );
			}

		}

		if ( empty( $package_services ) && ! empty( $errors ) ) {
			_wpsc_shipping_add_error_message( $errors[0] );
		}

		return $package_services;
	}

	/**
	 * This function parses the provided XML response for international requests
	 * from USPS to retrieve the final rates as an array.
	 *
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

			if ( ! empty( $temp_rate ) ) {
				$rate = $temp_rate[0];
			} else {
				continue;
			}

			if ( ! empty( $service_name ) ) {
				$service_table[ $service_name ] = apply_filters( 'wpsc_usps_intl_rate', $rate, $service_name );
			}
		}
		return $service_table;
	}

	/**
	 * Returns an array using the common keys from all arrasy and the sum of those common keys values;
	 *
	 * @since 2.0
	 * @param array $rate_tables
	 * @return array
	 */
	function _combine_rates( $rate_tables ) {
		$final_table = array();
		if ( ! is_array( $rate_tables ) || count( $rate_tables ) == 0 ) {
			return array();
		}
		if ( count( $rate_tables ) < 2 ) {
			return $rate_tables[0];
		}
		$temp_services = call_user_func_array( 'array_intersect_key', $rate_tables );

		$valid_services = array_keys( $temp_services );
		if ( count( $rate_tables ) != count( $rate_tables, COUNT_RECURSIVE ) ) {
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
		} else {
			$final_table = $rate_tables;
		}
		return $final_table;
	}

	/**
	 * Merges arrays and adds the values of common keys.
	 *
	 * @since 2.0
	 * @param array $arrays
	 * @return array
	 */
	function merge_sum_arrays( $arrays ) {
		$temp = array();
		if ( ! is_array( $arrays ) || count( $arrays ) == 0 ) {
			return array();
		}
		if ( count( $arrays ) > 1 ) {
			$temp_arr = call_user_func_array( 'array_intersect_key', $arrays );
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
	 *
	 * @since 2.0
	 * @param array $data
	 * @return array
	 */
	function _quote_simple_intl( array $data ) {
		global $wpec_ash_xml;
		//*** Build Request **\\
		$request = $this->_build_request( $data );
		if ( empty( $request ) ) {
			return array();
		}
		$this->_build_intl_shipment( $request, $data, FALSE );
		$request_xml = $wpec_ash_xml->build_message( $request );
		//*** Make the Request ***\\
		$response = $this->_make_request( $request_xml, TRUE );
		if ( empty( $response ) || $response === FALSE ) {
			return array();
		}
		//*** Parse the response from USPS ***\
		$package_rate_table = $this->_parse_intl_response( $response );
		$rate_table = $this->_merge_arrays( $package_rate_table );
		return $rate_table;
	}

	/**
	 * Runs the quote process for a simple quote and returns the final quote table
	 *
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
	 *
	 * @since 2.0
	 * @param array $data
	 * @return array
	 */
	function _quote_advanced( array $data ) {
		global $wpec_ash_xml;

		$rate_tables = array();
		$cart_shipment = apply_filters( 'wpsc_the_shipment', $this->shipment, $data ); //Filter to allow reprocesing the shipment before is quoted.
		foreach ( $cart_shipment->packages as $package ) {
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
	 *
	 * @since 2.0
	 * @param array $data
	 * @return array
	 */
	function _quote_intl( array $data ) {
		global $wpec_ash_xml;
		$rate_tables = array();
		$cart_shipment = apply_filters( 'wpsc_the_shipment', $this->shipment, $data ); //Filter to allow reprocesing the shipment before is quoted.
		foreach ( $cart_shipment->packages as $package ) {
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
	 *
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
	 *
	 * @since 2.0
	 * @param array $rate_table
	 * @param array $data
	 * @return array
	 */
	function _validate_services( $rate_table, $data ) {

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
	 *
	 * @since 2.0
	 * @param array $data This is an array that USPS uses to build its request
	 *
	 * Expected Values for $data:
	 * Required : String : "fcl_type"   : Is the First Class Package Type ("Package", "Envelope","Postcards or aerogrammes", "Matter for the Blind", "All")
	 * Required : Int : "base_zipcode"  : The originating zip code where the shipment is from
	 * Required : String : "user_id"    : USPS user ID
	 * Required : Array : "services"    : List of services to get rates for, One or More services required
	 */
	function _run_quote( array $data ) {
		global $wpec_ash_tools;

		if ( $wpec_ash_tools->is_military_zip( $data["dest_zipcode"] ) ) {
			$data["dest_country"] = "USA";
		}
		//\\************** END common config **************\\//
		//*** Get the Quotes ***\\
		$quotes = array();
		if ( $data["dest_country"] == "USA" && $data["adv_rate"] == TRUE ){
			$quotes = $this->_quote_advanced( $data );
		} elseif ( $data["dest_country"] != "USA" && $data["adv_rate"] == TRUE ) {
			$quotes = $this->_quote_intl( $data );
		} elseif ( $data["dest_country"] == "USA" && $data["adv_rate"] != TRUE ) {
			$quotes = $this->_quote_simple( $data );
		} else {
			$quotes = $this->_quote_simple_intl( $data );
		}
		$rate_table = $this->_validate_services( $quotes, $data );
		if ( ! empty( $quotes ) ) { //Don't try to sort an empty array
			asort( $quotes, SORT_NUMERIC );
		}
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
		global $wpdb, $wpec_ash, $wpec_ash_tools, $wpsc_cart;
		$data = array();
		//************** These values are common to all entry points **************
		//*** User/Customer Entered Values ***\\
		//*** Set up the destination country ***\
		$data["dest_country"] = wpsc_get_customer_meta( 'shipping_country' );
		$settings = get_option( 'wpec_usps' );
		//Disable International Shipping. Default: Enabled as it currently is.
		$data['intl_rate'] = isset( $settings['intl_rate'] ) && ! empty( $settings['intl_rate'] ) ? FALSE : TRUE;
		if ( ! $data['intl_rate'] && $data['dest_country'] != get_option( 'base_country' ) ) {
			return array();
		}

		// If ths zip code is provided via a form post use it!
		$data["dest_zipcode"] = (string) wpsc_get_customer_meta( 'shippingpostcode' );

		if ( ! is_object( $wpec_ash_tools ) ) {
			$wpec_ash_tools = new ASHTools();
		}

		if ( empty( $data["dest_zipcode"] ) && $wpec_ash_tools->needs_post_code( $data["dest_country"] ) ) {
			// We cannot get a quote without a zip code so might as well return!
			return array();
		}

		//*** Grab Total Weight from the shipment object for simple shipping
		$data["weight"] = wpsc_cart_weight_total();
		if ( empty( $data["weight"] ) ) {
			return array();
		}


		// If the region code is provided via a form post use it!
		if ( isset( $_POST['region'] ) && ! empty( $_POST['region'] ) ) {
			$data['dest_state'] = wpsc_get_region( sanitize_text_field( $_POST['region'] ) );
		} else if ( $dest_state = wpsc_get_customer_meta( 'shipping_state' ) ) {
			// Well, we have a zip code in the session and no new one provided
			$data['dest_state'] = $dest_state;
		} else {
			$data['dest_state'] = "";
		}
		$data["dest_country"] = $wpec_ash_tools->get_full_country( $data["dest_country"] );
		$data["dest_country"] = $this->_update_country( $data["dest_country"] );

		if ( ! is_object( $wpec_ash ) ) {
			$wpec_ash = new ASH();
		}
		$shipping_cache_check['state'] = $data['dest_state'];
		$shipping_cache_check['country'] = $data['dest_country'];
		$shipping_cache_check['zipcode'] = $data["dest_zipcode"];
		$this->shipment = $wpec_ash->get_shipment();
		$this->shipment->set_destination( $this->internal_name, $shipping_cache_check );
		$this->shipment->rates_expire = date('Y-m-d'); //Date will be checked against the cached date.
		$data['shipper'] = $this->internal_name;
		$data["adv_rate"] = (!empty($settings["adv_rate"])) ? $settings["adv_rate"] : FALSE; // Use advanced shipping for Domestic Rates ? Not available
		if ( $data["weight"] > 70 && ! (boolean) $data["adv_rate"] ) { //USPS has a weight limit: https://www.usps.com/send/can-you-mail-it.htm?#3.
			$over_weight_txt = apply_filters( 'wpsc_shipment_over_weight',
												__( 'Your order exceeds the standard shipping weight limit.
													Please contact us to quote other shipping alternatives.', 'wp-e-commerce' ),
												$data );
			$shipping_quotes[$over_weight_txt] = 0; // yes, a constant.
			$wpec_ash->cache_results( $this->internal_name, array($shipping_quotes), $this->shipment );
			return array($shipping_quotes);
		}

		// Check to see if the cached shipment is still accurate, if not we need new rate
		$cache = $wpec_ash->check_cache( $this->internal_name, $this->shipment );
		// We do not want to spam USPS (and slow down our process) if we already
		// have a shipping quote!
		if ( count($cache["rate_table"] ) >= 1 ) { //$cache['rate_table'] could be array(0).
			return $cache["rate_table"];
		}
		//*** WPEC Configuration values ***\\
		$this->use_test_env   = ( ! isset( $settings["test_server"] ) ) ? false : ( bool ) $settings['test_server'];
		$data["fcl_type"]     = ( ! empty( $settings["fcl_type"] ) ) ? $settings["fcl_type"] : "PARCEL";
		$data["mail_type"]    = ( ! empty( $settings["intl_pkg"] ) ) ? $settings["intl_pkg"] : "Package";
		$data["base_zipcode"] = get_option( "base_zipcode" );
		$data["services"]     = ( ! empty( $settings["services"] ) ) ? $settings["services"] : array( "STANDARD POST", "PRIORITY", "PRIORITY EXPRESS", "FIRST CLASS" );
		foreach( $data["services"] as $id => $service ) {
			if ( $service == 'PARCEL' ) {
				$data["services"][$id] = 'STANDARD POST';
			}
			if ( $service == 'EXPRESS' ) {
				$data["services"][$id] = 'PRIORITY EXPRESS';
			}
		}
		$data["user_id"]      = $settings["id"];
		$data["value"] 		  = $wpsc_cart->calculate_subtotal( true ); //Required by $this->_build_intl_shipment.
		$data = apply_filters( 'wpsc_shipment_data', $data, $this->shipment );
		if ( isset( $data['stop'] ) ) { //Do not get rates.
			return array();
		}
		//************ GET THE RATE ************\\
		$rate_table = apply_filters( 'wpsc_rates_table', $this->_run_quote( $data ), $data, $this->shipment );
		//Avoid trying getting rates again and again when the stored zip code is incorrect.

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
