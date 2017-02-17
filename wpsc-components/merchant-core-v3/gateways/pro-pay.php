<?php
/**
 * @todo: Later,  Create a nice user sign-up flow, as a part of an overall onboarding experience
 * @todo: Later, integrate with subscriptions
 * @todo: Later, support capturing a different amount than originally authorized.
 * @todo: Abstract out config files, API objects, etc.
 */
class WPSC_Payment_Gateway_Pro_Pay extends WPSC_Payment_Gateway {

	private static $endpoints = array(
		// Posting URL for ProPay API real time processing
		'account-creation-endpoint' => array(
			'sandbox'    => 'https://xmltest.propay.com/api/propayapi.aspx',
			'production' => 'https://xml.propay.com/api/propayapi.aspx'
		),
		// (WSDL) URL for ProtectPay API (Calls preparatory to the PMI and for processing against a token once it is created)
		'wsdl-endpoint' => array(
			'sandbox'    => 'https://xmltestapi.propay.com/api/sps.svc?wsdl',
			'production' => 'https://xmlapi.propay.com/api/sps.svc?wsdl'
		),
		// URL for ProtectPay API (Calls preparatory to the PMI and for processing against a token once it is created)
		'payment-processing-endpoint' => array(
			'sandbox'    => 'https://xmltestapi.propay.com/api/sps.svc',
			'production' => 'https://xmlapi.propay.com/api/sps.svc'
		),
		// URL for ProtectPay PMI (tokenizing card numbers)
		'tokenization-endpoint-num-only' => array(
			'sandbox'    => 'https://protectpaytest.propay.com/pmi/cardnumonly.aspx',
			'production' => 'https://protectpay.propay.com/pmi/cardnumonly.aspx'
		),
		// URL for ProtectPay PMI (tokenizing cards)
		'tokenization-endpoint' => array(
			'sandbox'    => 'https://protectpaytest.propay.com/pmi/spr.aspx',
			'production' => 'https://protectpay.propay.com/pmi/spr.aspx'
		),
		// REST API endpoint
		'rest-api-endpoint' => array(
			'sandbox'    => 'https://sb01api.propay.com/protectpay',
			'production' => 'https://api.propay.com/protectpay',
		)
	);

	private $payment_capture;
	private $endpoint;
	private $sandbox;

	private $login_url = 'https://www.propay.com/?refid=WPECOMM';
	private $auth_token = 'd30280d2-74de-4418-ab3f-0f58055ee421';

	private $cert_string         = '2df3f9620654ee8a52b5b6d411c760';
	private $term_id             = '5b6d411c760';
	private $biller_account_id   = '7929812732866007';
	private $account_number      = '';
	private $merchant_profile_id = '';

	/**
	 * Constructor of ProPay Payment Gateway
	 *
	 * @access public
	 * @since 3.12.0
	 */
	public function __construct() {

		parent::__construct();

		$this->title    = __( 'ProPay (TSYS) Payment Gateway', 'wp-e-commerce' );
		$this->supports = array( 'tev1', 'refunds', 'partial-refunds', 'auth-capture' );

		// Define user set variables
		$this->account_number      = $this->setting->get( 'account_number' );
		$this->merchant_profile_id = $this->setting->get( 'merchant_profile_id' );
		$this->sandbox			   = $this->setting->get( 'sandbox_mode' ) == '1' ? true : false;
		$this->endpoint			   = $this->sandbox ? self::$endpoints['payment-processing-endpoint']['sandbox'] : self::$endpoints['payment-processing-endpoint']['production'];
		$this->payment_capture 	   = $this->setting->get( 'payment_capture' ) !== null ? $this->setting->get( 'payment_capture' ) : '';

		$this->admin_scripts();
	}

	public function admin_scripts() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	public function init() {

		parent::init();

		add_action( 'wp_enqueue_scripts'                  , array( $this, 'checkout_scripts' ) );
		add_action( 'wpsc_gateway_v2_inside_gateway_label', array( $this, 'add_spinner' ) );
		add_action( 'wpsc_inside_shopping_cart'           , array( $this, 'add_propay_iframe' ) );

		add_action( 'wp_ajax_propay_create_merchant_profile_id'  , array( $this, 'create_merchant_profile' ) );

		add_action( 'wp_ajax_create_payer_id'                    , array( $this, 'create_payer_id' ) );
		add_action( 'wp_ajax_nopriv_create_payer_id'             , array( $this, 'create_payer_id' ) );

		add_action( 'wp_ajax_create_hosted_transaction_id'       , array( $this, 'create_hosted_transaction_id' ) );
		add_action( 'wp_ajax_nopriv_create_hosted_transaction_id', array( $this, 'create_hosted_transaction_id' ) );

		add_action( 'wp_ajax_create_hosted_results'       , array( $this, 'create_hosted_results' ) );
		add_action( 'wp_ajax_nopriv_create_hosted_results', array( $this, 'create_hosted_results' ) );

		add_action( 'wpsc_get_form_output_after_form_fields', array( $this, 'add_propay_iframe' ) );

		add_filter( 'wpsc_form_input_append_to_label', array( $this, 'tev2_pro_pay_spinner' ), 10, 2 );
	}

	public function tev2_pro_pay_spinner( $label, $atts ) {
		$method  = isset( $atts['name'] )  && 'wpsc_payment_method' === $atts['name'];
		$pro_pay = isset( $atts['value'] ) && 'pro-pay' === $atts['value'];

		if ( $method && $pro_pay ) {
			ob_start();

			$this->add_spinner( 'pro-pay' );
			$spinner = ob_get_clean();
			$label = $spinner . $label;
		}

		return $label;
	}

	public function add_propay_iframe( $r = '' ) {

		$is_tev2_payment_page = ! empty( $r ) && 'wpsc-checkout-form' === $r['id'] && 'payment' === _wpsc_get_current_controller_slug();
		$is_tev1_payment_page = empty( $r );

		if ( ! $is_tev1_payment_page && ! $is_tev2_payment_page ) {
			return;
		}
		$this->loader();
		?>
		<style>
		.pro-pay-iframe {
			height: 640px;
			overflow:hidden;
			border: none;
			width: 100%;
			background: url(<?php echo admin_url( 'images/spinner.gif' ) ?>) no-repeat 50% 50%;
		}

		.pro-pay-frame.loaded {
			background: transparent;
		}

		</style>
		<iframe scrolling="no"  id="pro_pay_iframe" name="pro_pay_iframe" class="pro-pay-iframe"></iframe>
		<?php if ( defined( 'WPSC_DEBUG' ) && WPSC_DEBUG ) : ?>
			<div id="MessageLog" class="BrowserMessageBox"></div>
			<div id="BrowserLog" class="BrowserMessageBox"></div>
			<div id="ConsoleLog" class="BrowserMessageBox"></div>
		<?php
			endif;
	}

	public function loader() {
		?>
		<style>
		.wpsc-purchase-loader-container {
			position:absolute;
			display: none;
		}

		.wpsc-purchase-loader {
		  position: absolute;
		  top: calc( 50% - 18px );
		  width: 100%;
		}

		.blob {
		  position: absolute;
		  left: 50%;
		  top: 18px;
		  width: 3px;
		  height: 3px;
		  border-radius: 1.5px;
		  background-color: #00ffeb;
		  content: "";
		  -webkit-filter: blur(1px);
		          filter: blur(1px);
		  -webkit-transform: translateY(-10px);
		          transform: translateY(-10px);
		}
		.blob:nth-child(1) {
		  -webkit-animation: spin 1.25s infinite ease-in-out;
		          animation: spin 1.25s infinite ease-in-out;
		  -webkit-animation-delay: 0.1s;
		          animation-delay: 0.1s;
		}
		.blob:nth-child(2) {
		  -webkit-animation: spin 1.25s infinite ease-in-out;
		          animation: spin 1.25s infinite ease-in-out;
		  -webkit-animation-delay: 0.2s;
		          animation-delay: 0.2s;
		}
		.blob:nth-child(3) {
		  -webkit-animation: spin 1.25s infinite ease-in-out;
		          animation: spin 1.25s infinite ease-in-out;
		  -webkit-animation-delay: 0.3s;
		          animation-delay: 0.3s;
		}
		.blob:nth-child(4) {
		  -webkit-animation: spin 1.25s infinite ease-in-out;
		          animation: spin 1.25s infinite ease-in-out;
		  -webkit-animation-delay: 0.4s;
		          animation-delay: 0.4s;
		}
		.blob:nth-child(5) {
		  -webkit-animation: spin 1.25s infinite ease-in-out;
		          animation: spin 1.25s infinite ease-in-out;
		  -webkit-animation-delay: 0.5s;
		          animation-delay: 0.5s;
		}
		.blob:nth-child(6) {
		  -webkit-animation: spin 1.25s infinite ease-in-out;
		          animation: spin 1.25s infinite ease-in-out;
		  -webkit-animation-delay: 0.6s;
		          animation-delay: 0.6s;
		}
		.blob:nth-child(7) {
		  -webkit-animation: spin 1.25s infinite ease-in-out;
		          animation: spin 1.25s infinite ease-in-out;
		  -webkit-animation-delay: 0.7s;
		          animation-delay: 0.7s;
		}

		@-webkit-keyframes spin {
		  0% {
		    -webkit-transform: rotate(0deg) translateY(-10px) rotate(0deg);
		            transform: rotate(0deg) translateY(-10px) rotate(0deg);
		  }
		  70% {
		    -webkit-transform: rotate(360deg) translateY(-10px) rotate(-360deg);
		            transform: rotate(360deg) translateY(-10px) rotate(-360deg);
		  }
		}

		@keyframes spin {
		  0% {
		    -webkit-transform: rotate(0deg) translateY(-10px) rotate(0deg);
		            transform: rotate(0deg) translateY(-10px) rotate(0deg);
		  }
		  70% {
		    -webkit-transform: rotate(360deg) translateY(-10px) rotate(-360deg);
		            transform: rotate(360deg) translateY(-10px) rotate(-360deg);
		  }
		}
		</style>
		<div class='wpsc-purchase-loader-container'>
			<div class='wpsc-purchase-loader'>
			  <div class='blob'></div>
			  <div class='blob'></div>
			  <div class='blob'></div>
			  <div class='blob'></div>
			  <div class='blob'></div>
			  <div class='blob'></div>
			  <div class='blob'></div>
			</div>
		</div>
	<?php
	}

	/**
	 * Currently only functional for US.
	 *
	 * @return [type] [description]
	 */
	public function load() {
		return 'USD' === wpsc_get_currency_code() && 'US' === wpsc_get_base_country();
	}

	public function add_spinner( $gateway ) {
		if ( 'pro-pay' !== $gateway ) {
			return;
		}

		?>
		<div class="spinner"></div>
		<style>
		.spinner {
			background: url(<?php echo admin_url( 'images/spinner.gif' ) ?>) no-repeat;
			-webkit-background-size: 20px 20px;
			background-size: 20px 20px;
			display: inline-block;
			vertical-align: middle;
			opacity: .7;
			filter: alpha(opacity=70);
			width: 20px;
			height: 20px;
			margin: 4px 10px 0;
			display: none;
		}
		@media print, (-webkit-min-device-pixel-ratio: 1.25), (min-resolution: 120dpi) {
			.spinner {
				background-image: url(<?php echo admin_url( 'images/spinner-2x.gif' ) ?>);
			}
		}
		</style>
		<?php
	}

	public function checkout_scripts() {

		$is_cart = wpsc_is_theme_engine( '1.0' ) ? wpsc_is_checkout() : ( wpsc_is_checkout() || wpsc_is_cart() );

		if ( $is_cart ) {
			wp_enqueue_script( 'pro-pay-signal-r', WPSC_MERCHANT_V3_SDKS_URL . '/pro-pay/js/signal-r.js', array( 'jquery' ), WPSC_VERSION );
			wp_enqueue_script( 'pro-pay-hpp', WPSC_MERCHANT_V3_SDKS_URL . '/pro-pay/js/hpp.js', array( 'jquery', 'pro-pay-signal-r' ), WPSC_VERSION );
			wp_enqueue_script( 'pro-pay-js', WPSC_MERCHANT_V3_SDKS_URL . '/pro-pay/js/pro-pay-checkout.js', array( 'jquery', 'pro-pay-hpp' ), WPSC_VERSION );

			wp_localize_script( 'pro-pay-hpp', 'WPSC_Pro_Pay_Checkout', array(
					'checkout_nonce' => wp_create_nonce( 'checkout_nonce' ),
					'ajaxurl'        => admin_url( 'admin-ajax.php', 'relative' ),
					'iframe_id'      => 'pro_pay_iframe',
					'base_uri'       => $this->get_hpp_base_uri(),
					'debug'          => WPSC_DEBUG,
					'checkout_data' => $this->get_checkout_details()
				)
			);
		}
	}

	private function get_checkout_details() {
		$details  = wpsc_get_customer_meta( 'checkout_details' );
		$checkout = WPSC_Checkout_Form::get();

		return array(
			'billingemail'     => $details[ $checkout->get_field_id_by_unique_name( 'billingemail' ) ],
			'billingfirstname' => $details[ $checkout->get_field_id_by_unique_name( 'billingfirstname' ) ],
			'billinglastname'  => $details[ $checkout->get_field_id_by_unique_name( 'billinglastname' ) ],
			'billingaddress'   => $details[ $checkout->get_field_id_by_unique_name( 'billingaddress' ) ],
			'billingcity'      => $details[ $checkout->get_field_id_by_unique_name( 'billingcity' ) ],
			'billingregion'    => wpsc_get_state_by_id( wpsc_get_customer_meta( '_wpsc_cart.delivery_region' ), 'code' ),
			'billingpostcode'  => $details[ $checkout->get_field_id_by_unique_name( 'billingpostcode' ) ],
			'billingcountry'   => $details[ $checkout->get_field_id_by_unique_name( 'billingcountry' ) ]
		);
	}

	private function get_hpp_base_uri() {

		$base_url = $this->sandbox ? 'https://sbprotectpay.propay.com' : 'https://protectpay.propay.com';

		return $base_url;
	}

	public function enqueue_admin_scripts( $hook ) {

		if ( 'settings_page_wpsc-settings' !== $hook ) {
			return;
		}

		wp_enqueue_script( 'pro-pay-admin-js', WPSC_MERCHANT_V3_SDKS_URL . '/pro-pay/js/pro-pay-admin.js', array( 'jquery' ), WPSC_VERSION, true );
		wp_localize_script( 'pro-pay-admin-js', 'WPSC_Pro_Pay', array(
				'merchant_profile_nonce'  => wp_create_nonce( 'wpsc_merchant_profile' ),
				'profile_id_success_text' => __( 'Congratulations, you now have a functional merchant profile ID!', 'wp-e-commerce' ),
				'profile_id_error_text'   => __( 'Unfortunately, there was an error with this process. Try again later.', 'wp-e-commerce' )
			)
		);

	}

	public static function get_endpoint( $type, $environment ) {
		// Default to a sane assumption of sandbox payment processing;
		$endpoint = self::$endpoints['payment-processing-endpoint']['sandbox'];

		if ( ! isset( self::$endpoints[ $type ] ) ) {
			return $endpoint;
		}

		if ( ! isset( self::$endpoints[ $type ][ $environment ] ) ) {
			return $endpoint;
		}

		return self::$endpoints[ $type ][ $environment ];
	}

	public function get_account_number_row( $hide = false ) {
		$hidden = $hide ? ' style="display:none;"' : '';
	?>
		<tr id="pro-pay-account-row"<?php echo $hidden; ?>>
			<td>
				<label for="wpsc-pro-pay-merchant-profile-id"><?php _e( 'Account Number', 'wp-e-commerce' ); ?></label>
			</td>
			<td>
				<input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'account_number' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'account_number' ) ); ?>" id="wpsc-pro-pay-account-number" />
				<br><span class="small description"><?php _e( 'The Account Number can be obtained from the email that you should have received during the sign-up process.', 'wp-e-commerce' ); ?></span>
			</td>
		</tr>
		<?php
	}

	/**
	 * Settings Form Template
	 *
	 * @since 3.12.0
	 */
	public function setup_form() {
		if ( empty( $this->account_number ) ) {
			?>
			<tr id="account-creation-pro-pay">
				<td></td>
				<td>
					<a class="button-primary" href="<?php echo esc_url( $this->login_url ); ?>"><?php _e( 'Create an Account?', 'wp-e-commerce' ); ?></a>
					<a class="button-secondary" href="#" onclick="jQuery( '#pro-pay-account-row' ).slideDown( 300 ); jQuery('#account-creation-pro-pay').slideUp(400); return false; "><?php _e( 'Already Have One?', 'wp-e-commerce' ); ?></a>
				</td>
			</tr>
		<?php
			$this->get_account_number_row( true );
		} else {
?>
		<!-- Account Credentials -->
		<tr>
			<td colspan="2">
				<h4><?php _e( 'Account Credentials', 'wp-e-commerce' ); ?></h4>
			</td>
		</tr>
		<?php $this->get_account_number_row(); ?>
		<tr>
			<td>
				<label for="wpsc-pro-pay-merchant-profile-id"><?php _e( 'Merchant Profile ID', 'wp-e-commerce' ); ?></label>
			</td>
			<td>
				<input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'merchant_profile_id' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'merchant_profile_id' ) ); ?>" id="wpsc-pro-pay-merchant-profile-id" />
				<?php if ( empty( $this->merchant_profile_id ) ) : ?>
				<div id="wpsc-propay-merchant-profile-create">
					<p><span class="small description"><?php _e( 'If you have not yet received a merchant profile ID, create one below.', 'wp-e-commerce' ); ?></span></p>
					<br /><a href="#" class="button-primary create-merchant-profile"><?php _e( 'Create Merchant Profile ID' ); ?></a><div class="spinner" style="float:none"></div>
				</div>
			<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td>
				<label for="wpsc-pro-pay-payment-capture"><?php _e( 'Payment Capture', 'wp-e-commerce' ); ?></label>
			</td>
			<td>
				<select id="wpsc-pro-pay-payment-capture" name="<?php echo esc_attr( $this->setting->get_field_name( 'payment_capture' ) ); ?>">
					<option value='' <?php selected( '', $this->setting->get( 'payment_capture' ) ); ?>><?php _e( 'Authorize and capture the payment when the order is placed.', 'wp-e-commerce' )?></option>
					<option value='authorize' <?php selected( 'authorize', $this->setting->get( 'payment_capture' ) ); ?>><?php _e( 'Authorize the payment when the order is placed.', 'wp-e-commerce' )?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<label><?php _e( 'Sandbox Mode', 'wp-e-commerce' ); ?></label>
			</td>
			<td>
				<label><input <?php checked( $this->setting->get( 'sandbox_mode' ) ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'sandbox_mode' ) ); ?>" value="1" /> <?php _e( 'Yes', 'wp-e-commerce' ); ?></label>&nbsp;&nbsp;&nbsp;
				<label><input <?php checked( (bool) $this->setting->get( 'sandbox_mode' ), false ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'sandbox_mode' ) ); ?>" value="0" /> <?php _e( 'No', 'wp-e-commerce' ); ?></label>
			</td>
		</tr>
		<!-- Error Logging -->
		<tr>
			<td colspan="2">
				<h4><?php _e( 'Error Logging', 'wp-e-commerce' ); ?></h4>
			</td>
		</tr>
		<tr>
			<td>
				<label><?php _e( 'Enable Debugging', 'wp-e-commerce' ); ?></label>
			</td>
			<td>
				<label><input <?php checked( $this->setting->get( 'debugging' ) ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'debugging' ) ); ?>" value="1" /> <?php _e( 'Yes', 'wp-e-commerce' ); ?></label>&nbsp;&nbsp;&nbsp;
				<label><input <?php checked( (bool) $this->setting->get( 'debugging' ), false ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'debugging' ) ); ?>" value="0" /> <?php _e( 'No', 'wp-e-commerce' ); ?></label>
			</td>
		</tr>
<?php
		}
	}

	public function create_merchant_profile() {

		if ( ! wp_verify_nonce( $_POST['nonce'] , 'wpsc_merchant_profile' ) ) {
			wp_send_json_error();
		}

		if ( ! wpsc_is_store_admin() ) {
			wp_send_json_error();
		}

		$config = new WPSC_Pro_Pay_Merchant_Profile_Config(
			array(
				'cert_string'       => $this->cert_string,
				'account_number'    => $this->account_number,
				'term_id'           => $this->term_id,
				'environment'       => $this->sandbox ? 'sandbox' : 'production',
				'biller_account_id' => $this->biller_account_id,
				'auth_token'        => $this->auth_token
			)
		);

		$profile = new WPSC_ProPay_Merchant_Profile( $config );

		$profile_id   = $profile->create()->get_profile_id();

		if ( $profile_id ) {
			$this->setting->set( 'merchant_profile_id', $profile_id );
			wp_send_json_success( array( 'profile_id' => $profile_id ) );
		} else {
			wp_send_json_error();
		}
	}

	public function create_payer_id() {

		if ( ! wp_verify_nonce( $_POST['nonce'] , 'checkout_nonce' ) ) {
			wp_send_json_error();
		}

		$payer_id = wpsc_get_customer_meta( 'pro_pay_payer_id' );

		if ( $payer_id ) {
			wp_send_json_success( array( 'payer' => $payer_id ) );
		}

		$name  = sanitize_text_field( $_POST['name'] );
		$email = sanitize_email( $_POST['email'] );

		$config = new WPSC_Pro_Pay_Payer_Id_Config(
			array(
				'environment'       => $this->sandbox ? 'sandbox' : 'production',
				'biller_account_id' => $this->biller_account_id,
				'auth_token'        => $this->auth_token,
				'name'              => $name,
				'email'             => $email
			)
		);

		$payer = new WPSC_ProPay_Payer_Id( $config );

		$payer_id = $payer->create()->get_payer_id();

		if ( $payer_id ) {
			wpsc_update_customer_meta( 'pro_pay_payer_id', $payer_id );
			wp_send_json_success( array( 'payer' => $payer_id ) );
		} else {
			wp_send_json_error();
		}
	}

	public function create_hosted_transaction_id() {

		if ( ! wp_verify_nonce( $_POST['nonce'] , 'checkout_nonce' ) ) {
			wp_send_json_error();
		}

		$name        = sanitize_text_field( $_POST['name'] );
		$address1    = sanitize_text_field( $_POST['address1'] );
		$address2    = sanitize_text_field( $_POST['address2'] );
		$city        = sanitize_text_field( $_POST['city'] );
		$state       = is_numeric( $_POST['state'] ) ? wpsc_get_state_by_id( $_POST['state'], 'code' ) : sanitize_text_field( $_POST['state'] );
		$zip         = sanitize_text_field( $_POST['zip'] );
		$country     = 'USA'; // Check if we can do international BUYERS. If so, we need 3-character ISO

		$config = new WPSC_Pro_Pay_Hosted_Transaction_Id_Config(
			array(
				'environment'         => $this->sandbox ? 'sandbox' : 'production',
				'biller_account_id'   => $this->biller_account_id,
				'auth_token'          => $this->auth_token,
				'name'                => $name,
				'address1'            => $address1,
				'address2'            => $address2,
				'city'                => $city,
				'state'               => $state,
				'zip'                 => $zip,
				'country'             => $country,
				'description'         => sprintf( __( 'Order from %s', 'wp-e-commerce' ), get_bloginfo( 'blogname' ) ),
				'merchant_profile_id' => $this->merchant_profile_id,
				'auth_only'           => 'authorize' === $this->payment_capture,
			)
		);

		$hosted = new WPSC_Pro_Pay_Hosted_Transaction_Id( $config );

		$token = $hosted->create()->get_transaction_id();

		if ( $token ) {
			wp_send_json_success( array( 'token' => $token ) );
		} else {
			wp_send_json_error();
		}
	}

	public function create_hosted_results() {

		if ( ! wp_verify_nonce( $_POST['nonce'] , 'checkout_nonce' ) ) {
			wp_send_json_error();
		}

		$config = new WPSC_Pro_Pay_Hosted_Transaction_Results_Config(
			array(
				'environment'         => $this->sandbox ? 'sandbox' : 'production',
				'biller_account_id'   => $this->biller_account_id,
				'auth_token'          => $this->auth_token,
				'id'                  => sanitize_text_field( $_POST['hosted_id'] )
			)
		);

		$hosted = new WPSC_Pro_Pay_Hosted_Transaction_Results( $config );

		$results = $hosted->create()->get_response();

		if ( $results ) {
			wp_send_json_success( array( 'results' => $results ) );
		} else {
			wp_send_json_error();
		}
	}

	public function process() {

		$token          = sanitize_text_field( $_POST['pro_pay_payment_method_token'] );
		$transaction_id = sanitize_text_field( $_POST['pro_pay_transaction_id'] );
		$last_four      = absint( substr( $_POST['pro_pay_obfs_acct_number'], 0, -4 ) );
		$type           = sanitize_text_field( $_POST['pro_pay_card_type'] );
		$order          = $this->purchase_log;

		$status = $this->payment_capture === '' ? WPSC_Purchase_Log::ACCEPTED_PAYMENT : WPSC_Purchase_Log::ORDER_RECEIVED;

		$order->set( 'processed', $status )->save();
		$order->set( 'token', $token )->save();
		$order->set( 'transactid', $transaction_id )->save();
		$order->set( 'last_four', $last_four )->save();
		$order->set( 'type', $type )->save();

		$this->go_to_transaction_results();
	}

	public function capture_payment( $log, $transaction_id ) {

		if ( $log->get( 'gateway' ) == 'pro-pay' ) {

			$config = new WPSC_Pro_Pay_Hosted_Capture_Payment_Config(
				array(
					'environment'         => $this->sandbox ? 'sandbox' : 'production',
					'biller_account_id'   => $this->biller_account_id,
					'auth_token'          => $this->auth_token,
					'merchant_profile_id' => $this->merchant_profile_id,
					'transaction_id'      => sanitize_text_field( $transaction_id ),
					'amount'              => $log->get( 'totalprice' ),
				)
			);

			$capture = new WPSC_Pro_Pay_Hosted_Capture_Payment( $config );

			$results = $capture->capture()->get_transaction_id();

			if ( empty( $results ) ) {
				throw new Exception( __( 'Could not generate a captured payment transaction ID.', 'wp-e-commerce' ) );
			}

			$log->set( 'processed', WPSC_Purchase_Log::ACCEPTED_PAYMENT )->save();
			$log->set( 'transactid', $results )->save();
			$log->set( 'pro_pay_capt_transactid', $transaction_id )->save();

			return true;
		}

		return false;
	}

	public function process_refund( $log, $amount = 0.00, $reason = '', $manual = false ) {

		if ( 0.00 == $amount ) {
			return new WP_Error( 'propay_refund_error', __( 'Refund Error: You need to specify a refund amount.', 'wp-e-commerce' ) );
		}

		$log = wpsc_get_order( $log );

		if ( ! $log->get( 'transactid' ) ) {
			return new WP_Error( 'error', __( 'Refund Failed: No transaction ID', 'wp-e-commerce' ) );
		}

		$max_refund  = $log->get( 'totalprice' ) - $log->get_total_refunded();

		if ( $amount && $max_refund < $amount || 0 > $amount ) {
			throw new Exception( __( 'Invalid refund amount', 'wp-e-commerce' ) );
		}

		if ( $manual ) {
			$current_refund = $log->get_total_refunded();

			// Set a log meta entry, and save log before adding refund note.
			$log->set( 'total_order_refunded' , $amount + $current_refund )->save();

			$log->add_refund_note(
				sprintf( __( 'Refunded %s via Manual Refund', 'wp-e-commerce' ), wpsc_currency_display( $amount ) ),
				$reason
			);

			return true;
		}

		$transaction_id = $log->get( 'transactid' );

		$options = new WPSC_Pro_Pay_Refund_Config( array(
			'amount'            => $amount,
			'reason'            => $reason,
			'transaction_id'    => $transaction_id,
			'merchant_id'       => $this->merchant_profile_id,
			'environment'       => $this->sandbox ? 'sandbox' : 'production',
			'biller_account_id' => $this->biller_account_id,
			'auth_token'        => $this->auth_token,
		) );

		// do API call
		$refund = new WPSC_Pro_Pay_Refund( $options );
		$refund = $refund->create()->get_refund();

		if ( $refund ) {

			if ( 'Success' == $refund->TransactionResult ) {

				$current_refund = $log->get_total_refunded();

				// Set a log meta entry, and save log before adding refund note.
				$log->set( 'total_order_refunded' , $amount + $current_refund )->save();

				$log->add_refund_note(
					sprintf( __( 'Refunded %s - Refund ID: %s', 'wp-e-commerce' ), wpsc_currency_display( $refund->CurrencyConvertedAmount / 100 ), $refund->TransactionHistoryId ),
					$reason
				);

				return true;
			}
		} else {
			return false;
		}
	}
}

class WPSC_ProPay_Request {

	protected $config;
	protected $method;

	public function __construct( $config, $method = 'PUT' ) {
		$this->config = $config;
		$this->method = $method;
	}

	public function request( $resource, $args = array() ) {

		$endpoint = WPSC_Payment_Gateway_Pro_Pay::get_endpoint( 'rest-api-endpoint', $this->config->environment );

		$url = $endpoint . '/'. ltrim( $resource, '/' );

		$args = wp_parse_args( $args, array(
			'timeout' => 60,
			'method'  => $this->method,
			'body'    => array(),
			'headers' => array(
				'content-type'  => 'application/json',
				'authorization' => self::generate_auth( $this->config->biller_account_id, $this->config->auth_token )
			)
		) );

		if ( ! empty( $args['body'] ) ) {
			$args['headers']['content-length'] = strlen( $args['body'] );
		}

		return new WPSC_ProPay_Response( wp_safe_remote_request( $url, $args ) );
	}

	private static function generate_auth( $id, $auth ) {
		return 'Basic ' . base64_encode( "{$id}:{$auth}" );
	}
}

class WPSC_ProPay_Response {

	public $response = null;
	protected $success = false;

	public function __construct( $response ) {
		$this->response = $response;
		$this->prepare_response();
	}

	public function prepare_response() {

		$response = json_decode( wp_remote_retrieve_body( $this->response ) );
		$code     = wp_remote_retrieve_response_code( $this->response );

		if ( isset( $response->RequestResult ) ) {
			$transaction_success = 'SUCCESS' === $response->RequestResult->ResultValue;
		} else {
			$transaction_success = 'SUCCESS' === $response->Result->ResultValue;
		}

		$success = 200 === $code && $transaction_success;

		if ( ! is_wp_error( $this->response ) && $success ) {
			$this->success = true;
			$this->response = $response;
		}

		return $this->response;
	}

	public function is_successful() {
		return $this->success;
	}

	public function get( $variable ) {

		if ( isset( $this->response->$variable ) ) {
			return $this->response->$variable;
		}

		return '';
	}

	/**
	 * Temp debug function.
	 *
	 * @return string [description]
	 */
	public function __toString() {
		return '<pre>' . print_r( $this->response, 1 ) . '</pre>';
	}
}

class WPSC_ProPay_Merchant_Profile {

	protected $config;

	public function __construct( WPSC_Pro_Pay_Merchant_Profile_Config $config ) {
		$this->config = $config;
	}

	public function create() {
		$request = new WPSC_ProPay_Request( $this->config );

		$body = json_encode( array(
			'ProfileName' => '',
			'PaymentProcessor' => 'LegacyProPay',
			'ProcessorData' => array(
				array(
					'ProcessorField' => 'certStr',
					'Value'          => $this->config->cert_string
				),
				array(
					'ProcessorField' => 'accountNum',
					'Value'          => $this->config->account_number
				),
				array(
					'ProcessorField' => 'termId',
					'Value'          => $this->config->term_id
				)
			),
		) );

		$this->response = $request->request( '/MerchantProfiles/', array( 'body' => $body ) );

		return $this;
	}

	public function get_profile_id() {

		if ( $this->response->is_successful() ) {
			return $this->response->get( 'ProfileId' );
		}

		return '';
	}
}

class WPSC_Pro_Pay_Merchant_Profile_Config {

	public $cert_string;
	public $account_number;
	public $term_id;
	public $environment;
	public $biller_account_id;
	public $auth_token;

	public function __construct( $args ) {
		$this->args = (object) $args;

		$this->cert_string       = $this->args->cert_string;
		$this->account_number    = $this->args->account_number;
		$this->term_id           = $this->args->term_id;
		$this->environment       = $this->args->environment;
		$this->biller_account_id = $this->args->biller_account_id;
		$this->auth_token        = $this->args->auth_token;
	}
}

class WPSC_ProPay_Payer_Id {

	protected $config;
	protected $response;

	public function __construct( WPSC_Pro_Pay_Payer_Id_Config $config ) {
		$this->config = $config;
	}

	public function create() {
		$request = new WPSC_ProPay_Request( $this->config );

		$body = json_encode( array(
			'Name'             => $this->config->name,
			'EmailAddress'     => $this->config->email,
		) );

		$this->response = $request->request( '/Payers/', array( 'body' => $body ) );

		return $this;
	}

	public function get_payer_id() {
		if ( $this->response->is_successful() ) {
			return $this->response->get( 'ExternalAccountID' );
		}

		return '';
	}
}

class WPSC_Pro_Pay_Payer_Id_Config {

	public $environment;
	public $biller_account_id;
	public $auth_token;
	public $name;
	public $email;

	public function __construct( $args ) {
		$this->args = (object) $args;

		$this->environment       = $this->args->environment;
		$this->biller_account_id = $this->args->biller_account_id;
		$this->auth_token        = $this->args->auth_token;
		$this->name              = $this->args->name;
		$this->email             = $this->args->email;
	}
}

class WPSC_Pro_Pay_Hosted_Transaction_Id {

	protected $config;
	protected $response;

	public function __construct( WPSC_Pro_Pay_Hosted_Transaction_Id_Config $config ) {
		$this->config = $config;
	}

	public function create() {
		$request = new WPSC_ProPay_Request( $this->config );

		$body = json_encode( apply_filters( 'wpsc_pro_pay_default_hosted_transaction_args', array(
			'Name'              => $this->config->name,
			'Address1'          => $this->config->address1,
			'Address2'          => $this->config->address2,
			'City'              => $this->config->city,
			'State'             => $this->config->state,
			'ZipCode'           => $this->config->zip,
			'Country'           => $this->config->country,
			'Description'       => $this->config->description,
			'MerchantProfileId' => $this->config->merchant_profile_id,
			'AuthOnly'          => $this->config->auth_only,
			'ProcessCard'       => ! $this->config->auth_only,
			'Amount'            => wpsc_cart_total( false ) * 100,
			'PayerAccountId'    => wpsc_get_customer_meta( 'pro_pay_payer_id' ),
			'PaymentTypeId'     => '0',
			'CurrencyCode'      => 'USD',
			'InvoiceNumber'     => uniqid(),
			'AvsRequirementType'               => 1,
			'CardHolderNameRequirementType'    => 1,
			'SecurityCodeRequirementType'      => 1,
			'OnlyStoreCardOnSuccessfulProcess' => true,
		) ) );

		$this->response = $request->request( '/HostedTransactions/', array( 'body' => $body ) );

		return $this;
	}

	public function get_transaction_id() {
		if ( $this->response->is_successful() ) {
			return $this->response->get( 'HostedTransactionIdentifier' );
		}

		return '';
	}
}

class WPSC_Pro_Pay_Hosted_Transaction_Id_Config {

	public $environment;
	public $biller_account_id;
	public $auth_token;
	public $name;

	public function __construct( $args ) {
		$this->args = (object) $args;

		$this->environment         = $this->args->environment;
		$this->biller_account_id   = $this->args->biller_account_id;
		$this->auth_token          = $this->args->auth_token;
		$this->name                = $this->args->name;
		$this->address1            = $this->args->address1;
		$this->address2            = $this->args->address2;
		$this->city                = $this->args->city;
		$this->state               = $this->args->state;
		$this->zip                 = $this->args->zip;
		$this->country             = $this->args->country;
		$this->description         = $this->args->description;
		$this->merchant_profile_id = $this->args->merchant_profile_id;
		$this->auth_only           = $this->args->auth_only;
	}
}

class WPSC_Pro_Pay_Hosted_Transaction_Results {

	protected $config;
	protected $response;

	public function __construct( WPSC_Pro_Pay_Hosted_Transaction_Results_Config $config ) {
		$this->config = $config;
	}

	public function create() {
		$request = new WPSC_ProPay_Request( $this->config, 'GET' );

		$this->response = $request->request( "/HostedTransactionResults/{$this->config->id}" );

		return $this;
	}

	public function get_response() {
		if ( $this->response->is_successful() ) {
			return $this->response;
		}

		return '';
	}
}

class WPSC_Pro_Pay_Hosted_Transaction_Results_Config {

	public $environment;
	public $biller_account_id;
	public $auth_token;
	public $id;

	public function __construct( $args ) {
		$this->args = (object) $args;

		$this->environment       = $this->args->environment;
		$this->biller_account_id = $this->args->biller_account_id;
		$this->auth_token        = $this->args->auth_token;
		$this->id                = $this->args->id;
	}
}

class WPSC_Pro_Pay_Refund {

	protected $config;
	protected $response;

	public function __construct( WPSC_Pro_Pay_Refund_Config $config ) {
		$this->config = $config;
	}

	public function create() {
		$request = new WPSC_ProPay_Request( $this->config );

		$body = json_encode( array(
			'CurrencyCode'         => 'USD',
			'TransactionHistoryId' => $this->config->transaction_id,
			'Comment1'             => $this->config->reason,
			'Amount'               => $this->config->amount * 100,
			'MerchantProfileId'    => $this->config->merchant_id
		) );

		$this->response = $request->request( '/RefundTransaction/', array( 'body' => $body ) );

		return $this;
	}

	public function get_refund() {
		if ( $this->response->is_successful() ) {
			return $this->response->get( 'TransactionDetail' );
		}

		return '';
	}
}

class WPSC_Pro_Pay_Refund_Config {

	public $environment;
	public $biller_account_id;
	public $auth_token;
	public $name;

	public function __construct( $args ) {
		$this->args                = (object) $args;
		$this->environment         = $this->args->environment;
		$this->biller_account_id   = $this->args->biller_account_id;
		$this->auth_token          = $this->args->auth_token;
		$this->transaction_id      = $this->args->transaction_id;
		$this->reason              = $this->args->reason;
		$this->amount              = $this->args->amount;
		$this->merchant_id         = $this->args->merchant_id;

	}
}

class WPSC_Pro_Pay_Hosted_Capture_Payment {

	protected $config;
	protected $response;

	public function __construct( WPSC_Pro_Pay_Hosted_Capture_Payment_Config $config ) {
		$this->config = $config;
	}

	public function capture() {
		$request = new WPSC_ProPay_Request( $this->config );

		$body = json_encode( array(
			'TransactionHistoryId' => $this->config->transaction_id,
			'MerchantProfileId'    => $this->config->merchant_profile_id,
			'Amount'               => $this->config->amount * 100,
			'CurrencyCode'         => 'USD'
		) );

		$this->response = $request->request( '/PaymentMethods/CapturedTransactions/', array( 'body' => $body ) );

		trigger_error( var_export( $this->response, 1 ) );

		return $this;
	}

	public function get_transaction_id() {

		if ( $this->response->is_successful() ) {
			return $this->response->get( 'Transaction' )->TransactionHistoryId;
		}

		return '';
	}
}

class WPSC_Pro_Pay_Hosted_Capture_Payment_Config {

	public $environment;
	public $biller_account_id;
	public $auth_token;
	public $id;

	public function __construct( $args ) {
		$this->args = (object) $args;

		$this->environment         = $this->args->environment;
		$this->biller_account_id   = $this->args->biller_account_id;
		$this->auth_token          = $this->args->auth_token;
		$this->transaction_id      = $this->args->transaction_id;
		$this->merchant_profile_id = $this->args->merchant_profile_id;
		$this->amount              = $this->args->amount;
	}
}
