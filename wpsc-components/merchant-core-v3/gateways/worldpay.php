<?php
class WPSC_Payment_Gateway_WorldPay extends WPSC_Payment_Gateway {

	private $endpoints = array(
		'sandbox' => 'https://gwapi.demo.securenet.com/api/',
		'production' => 'https://gwapi.securenet.com/api/',
	);
	
	private $auth;
	private $payment_capture;

	/**
	 * Constructor of WorldPay Payment Gateway
	 *
	 * @access public
	 * @since 3.9
	 */
	public function __construct() {

		parent::__construct();

		$this->title = __( 'WorldPay Payment Gateway', 'wp-e-commerce' );
		$this->supports = array( 'default_credit_card_form', 'tev1' );

		// Define user set variables
		$this->secure_net_id	= $this->setting->get( 'secure_net_id' );
		$this->secure_key  		= $this->setting->get( 'secure_key' );
		$this->public_key  		= $this->setting->get( 'public_key' );
		$this->sandbox			= $this->setting->get( 'sandbox_mode' ) == '1' ? true : false;
		$this->endpoint			= $this->sandbox ? $this->endpoints['sandbox'] : $this->endpoints['production'];
		$this->payment_capture 	= $this->setting->get( 'payment_capture' ) !== null ? $this->setting->get( 'payment_capture' ) : '';
		$this->auth				= 'Basic ' . base64_encode( $this->setting->get( 'secure_net_id' ) . ':' . $this->setting->get( 'secure_key' ) );
	}

	/**
	 * Settings Form Template
	 *
	 * @since 3.9
	 */
	public function setup_form() {
?>
		<!-- Account Credentials -->
		<tr>
			<td colspan="2">
				<h4><?php _e( 'Account Credentials', 'wp-e-commerce' ); ?></h4>
			</td>
		</tr>
		<tr>
			<td>
				<label for="wpsc-worldpay-secure-net-id"><?php _e( 'SecureNet ID', 'wp-e-commerce' ); ?></label>
			</td>
			<td>
				<input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'secure_net_id' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'secure_net_id' ) ); ?>" id="wpsc-worldpay-secure-net-id" />
				<br><span class="small description"><?php _e( 'The SecureNet ID can be obtained from the email that you should have received during the sign-up process.', 'wp-e-commerce' ); ?></span>
			</td>
		</tr>
		<tr>
			<td>
				<label for="wpsc-worldpay-secure-key"><?php _e( 'Secure Key', 'wp-e-commerce' ); ?></label>
			</td>
			<td>
				<input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'secure_key' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'secure_key' ) ); ?>" id="wpsc-worldpay-secure-key" />
				<br><span class="small description"><?php _e( 'You can obtain the Secure Key by signing into the Virtual Terminal with the login credentials that you were emailed to you during the sign-up process. You will then need to navigate to Settings and click on the Obtain Secure Key link.', 'wp-e-commerce' ); ?></span>
			</td>
		</tr>
		<tr>
			<td>
				<label for="wpsc-worldpay-public-key"><?php _e( 'Public Key', 'wp-e-commerce' ); ?></label>
			</td>
			<td>
				<input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'public_key' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'public_key' ) ); ?>" id="wpsc-worldpay-public-key" />
				<br><span class="small description"><?php _e( 'You can obtain the Public Key by signing into the Virtual Terminal. You will then need to navigate to Settings and click on the Obtain Public Key link.', 'wp-e-commerce' ); ?></span>
			</td>
		</tr>
		<tr>
			<td>
				<label for="wpsc-worldpay-payment-capture"><?php _e( 'Payment Capture', 'wp-e-commerce' ); ?></label>
			</td>
			<td>
				<select id="wpsc-worldpay-payment-capture" name="<?php echo esc_attr( $this->setting->get_field_name( 'payment_capture' ) ); ?>">
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

	/**
	 * Add scripts
	 */
	public function scripts() {

		$jsfile = $this->sandbox ? 'PayOSDev.js' : 'PayOS.js';
		wp_enqueue_script( 'worldpay_payos', WPSC_MERCHANT_V3_SDKS_URL . '/worldpay/assets/js/'.$jsfile, '', WPSC_VERSION );
	}

	public function head_script() {
		?>
		<script type='text/javascript'>

			jQuery(document).ready(function($) {
				$( ".wpsc_checkout_forms" ).submit(function( event ) {
					
					event.preventDefault();
					
					//jQuery( 'input[type="submit"]', this ).prop( { 'disabled': true } );

					var response = tokenizeCard(
						{
							"publicKey": '<?php echo $this->public_key; ?>',
							"card": {
								"number": document.getElementById('card_number').value,
								"cvv": document.getElementById('card_code').value,
							"expirationDate": document.getElementById('card_expiry_month').value + '/' + document.getElementById('card_expiry_year').value,
								"firstName": $( 'input[title="billingfirstname"]' ).val(),
								"lastName": $( 'input[title="billinglastname"]' ).val(),
								"address": {
									"zip": $( 'input[title="billingpostcode"]' ).val()
								}
							},
							"addToVault": false,
							"developerApplication": {
								"developerId": 12345678,
								"version": '1.2'

							}
						}
					).done(function (result) {

						var responseObj = $.parseJSON(JSON.stringify(result));

						if (responseObj.success) {

							var form$ = jQuery('.wpsc_checkout_forms');

							var token = responseObj.token;

							$("#worldpay_pay_token").val(token);
							// and submit
							form$.get(0).submit();

							// do something with responseObj.token
						} else {
							alert("token was not created");
							// do something with responseObj.message

						}

					}).fail(function ( response ) {
						jQuery( 'input[type="submit"]', this ).prop( { 'disabled': false } );
							console.log( response )
						// an error occurred
					});
				});

			});

		</script>
		<?php
	}
	
	public function te_v1_insert_hidden_field() {
		echo '<input type="hidden" id="worldpay_pay_token" name="worldpay_pay_token" value="" />';
	}

	public function init() {

		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
		add_action( 'wp_head'           , array( $this, 'head_script' ) );

		add_action( 'wpsc_inside_shopping_cart', array( $this, 'te_v1_insert_hidden_field' ) );

		add_filter( 'wpsc_gateway_checkout_form_worldpay', array( $this, 'payment_fields' ) );

		//add_filter( 'wpsc_get_checkout_payment_method_form_args', array( $this, 'te_v2_show_payment_fields' ) );
	}

	public function te_v2_show_payment_fields( $args ) {

		$default = '<div class="wpsc-form-actions">';
		ob_start();

		$this->payment_fields();
		$fields = ob_get_clean();

		$args['before_form_actions'] = $fields . $default;

		return $args;
	}

	public function process() {

		$order = $this->purchase_log;
		
		$status = $this->payment_capture === '' ? WPSC_Purchase_Log::ACCEPTED_PAYMENT : WPSC_Purchase_Log::ORDER_RECEIVED;
		
		$card_token = isset( $_POST['worldpay_pay_token'] ) ? sanitize_text_field( $_POST['worldpay_pay_token'] ) : '';
	
		switch ( $this->payment_capture ) {
			case 'authorize' :

				// Authorize only
				$result = $this->authorize_payment( $card_token );

				if ( $result ) {
					// Mark as on-hold
					$order->set( 'worldpay-status', __( 'WorldPay order opened. Authorize and/or capture payment below. Authorized payments must be captured within 7 days.', 'wp-e-commerce' ) )->save();

				} else {
					$order->set( 'processed', WPSC_Purchase_Log::PAYMENT_DECLINED )->save();
					$order->set( 'worldpay-status', __( 'Could not authorize WorldPay payment.', 'wp-e-commerce' ) )->save();

					//$this->handle_declined_transaction( $order );
				}

			break;
			default:
			
			break;
		}
		
		
		var_dump($_POST);
		
		exit;

	}
	
	public function authorize_payment( $token ) {

		if ( $this->purchase_log->get( 'gateway' ) == 'worldpay' ) {
			
			$order = $this->purchase_log;
			
			$params = array (
				'amount'	=> $order->get( 'totalprice' ),
				'orderId'	=> $order->get( 'sessionid' ),
				"addToVault" => false,
				"paymentVaultToken" => array(
					"paymentMethodId" => $token,
					"publicKey" => $this->public_key
				),
			);		
		}
		
		$response = $this->execute( 'Payments/Authorize', $params );

		var_dump($response);
		exit;

		
	}

	public function execute( $endpoint, $params ) {
       
	   // where we make the API petition
        $endpoint = $this->endpoint . $endpoint;
        
		$params += array(
			"developerApplication" => array(
				"developerId" => 12345678,
				"version" => "1.2"
			),
			"extendedInformation" => array(
				"typeOfGoods" => "PHYSICAL"
			),
		);
		
		$data = json_encode( $params );
		
		$args = array (
			'timeout' => 15,
			'headers' => array(
				'Authorization' => $this->auth,
				'Content-Type' => 'application/json',
			),
			'sslverify' => false,
			'body' => $data,
		);
		
		
		var_dump($args);
  	
		$request  = wp_safe_remote_post( $endpoint, $args );
        $response = wp_remote_retrieve_body( $request );
        $payload  = json_decode( $response );
		
		if ( ! is_wp_error( $request ) ) {

			$response_object = array();
			$response_object['ResponseBody'] = $response;
			$response_object['Status']       = wp_remote_retrieve_response_code( $request );

			$request = $response_object;
		}
		
		return $request;
    }

}
