<?php
/**
 * Todo: Create a nice user sign-up flow, as a part of an overall onboarding experience
 * integrated with subscriptions
 * @todo enqueue admin script for propay, localize nonce.
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
			'sandbox'    => 'https://xmltestapi.propay.com/protectpay',
			'production' => 'https://xmlapi.propay.com/protectpay',
		)
	);

	private $payment_capture;
	private $order_handler;
	private $endpoint;
	private $sandbox;

	private $login_url = 'http://xmltest.propay.com/signup/?refid=WPECOMME';
	private $auth_token = '745ef573-6fb0-4d9e-a410-24791e3769b6';

	private $cert_string         = '511ed119b09498d93ad2ba9b40a57f';
	private $term_id             = '40a57f';
	private $biller_account_id   = '3364620760318539';
	private $account_number      = '';
	private $merchant_profile_id = '';

	/**
	 * Constructor of pro-pay Payment Gateway
	 *
	 * @access public
	 * @since 3.12.0
	 */
	public function __construct() {

		parent::__construct();

		$this->title    = __( 'ProPay (TSYS) Payment Gateway', 'wp-e-commerce' );
		$this->supports = array( 'tev1' );

		$this->order_handler	= WPSC_Pro_Pay_Payments_Order_Handler::get_instance( $this );

		// Define user set variables
		$this->account_number      = $this->setting->get( 'account_number' );
		$this->merchant_profile_id = $this->setting->get( 'merchant_profile_id' );
		$this->sandbox			   = $this->setting->get( 'sandbox_mode' ) == '1' ? true : false;
		$this->endpoint			   = $this->sandbox ? self::$endpoints['payment-processing-endpoint']['sandbox'] : self::$endpoints['payment-processing-endpoint']['production'];
		$this->payment_capture 	   = $this->setting->get( 'payment_capture' ) !== null ? $this->setting->get( 'payment_capture' ) : '';
	}

	public function init() {
		add_action( 'wp_ajax_pro-pay_order_action'             , array( $this, 'order_actions' ) );
		add_action( 'admin_enqueue_scripts'                    , array( $this, 'enqueue_admin_scripts' ) );
		add_filter( 'wpsc_gateway_checkout_form_pro-pay'       , array( $this, 'payment_fields' ) );
		add_action( 'wp_enqueue_scripts'                       , array( $this, 'checkout_scripts' ) );

		add_action( 'wp_ajax_propay_create_merchant_profile_id', array( $this, 'create_merchant_profile' ) );
		add_action( 'wp_ajax_create_payer_id'                  , array( $this, 'create_payer_id' ) );
		add_action( 'wp_ajax_nopriv_create_payer_id'           , array( $this, 'create_payer_id' ) );

		add_action( 'wpsc_gateway_v2_inside_gateway_label', array( $this, 'add_spinner' ) );
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
			wp_enqueue_script( 'pro-pay-js', WPSC_MERCHANT_V3_SDKS_URL . '/pro-pay/js/pro-pay-checkout.js', array( 'jquery' ), WPSC_VERSION );
			wp_localize_script( 'pro-pay-js', 'WPSC_Pro_Pay_Checkout', array(
					'checkout_nonce' => wp_create_nonce( 'checkout_nonce' ),
					'ajaxurl'        => admin_url( 'admin-ajax.php', 'relative' ),
				)
			);
		}

	}

	public function enqueue_admin_scripts( $hook ) {

		if ( 'settings_page_wpsc-settings' !== $hook ) {
			return;
		}

		wp_enqueue_script( 'pro-pay-admin-js', WPSC_MERCHANT_V3_SDKS_URL . '/pro-pay/js/pro-pay.js', array( 'jquery' ), WPSC_VERSION, true );
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

	/**
	 * Load gateway only if SoapClient exists
	 *
	 * @return bool Whether or not to load gateway.
	 */
	public static function load() {
		return class_exists( 'SoapClient' );
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

	public function te_v2_show_payment_fields( $args ) {

		$default = '<div class="wpsc-form-actions">';
		ob_start();

		$this->payment_fields();
		$fields = ob_get_clean();

		$args['before_form_actions'] = $fields . $default;

		return $args;
	}

	public function create_merchant_profile() {
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
			wp_send_json_success( array( 'profile_id' => $profile_id ) );
		} else {
			wp_send_json_error();
		}
	}

	public function create_payer_id() {
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

	public function process() {

		$order = $this->purchase_log;

		$status = $this->payment_capture === '' ? WPSC_Purchase_Log::ACCEPTED_PAYMENT : WPSC_Purchase_Log::ORDER_RECEIVED;

		$order->set( 'processed', $status )->save();

		$card_token = isset( $_POST['pro-pay_pay_token'] ) ? sanitize_text_field( $_POST['pro-pay_pay_token'] ) : '';

		$this->order_handler->set_purchase_log( $order->get( 'id' ) );

		switch ( $this->payment_capture ) {
			case 'authorize' :

				// Authorize only
				$result = $this->authorize_payment( $card_token );

				if ( $result ) {
					// Mark as on-hold
					$order->set( 'pro-pay-status', __( 'pro-pay order opened. Capture the payment below. Authorized payments must be captured within 7 days.', 'wp-e-commerce' ) )->save();

				} else {
					$order->set( 'processed', WPSC_Purchase_Log::PAYMENT_DECLINED )->save();
					$order->set( 'pro-pay-status', __( 'Could not authorize pro-pay payment.', 'wp-e-commerce' ) )->save();
				}

			break;
			default:

				// Capture
				$result = $this->capture_payment( $card_token );

				if ( $result ) {
					// Payment complete
					$order->set( 'pro-pay-status', __( 'pro-pay order completed.  Funds have been authorized and captured.', 'wp-e-commerce' ) );
				} else {
					$order->set( 'processed'      , WPSC_Purchase_Log::PAYMENT_DECLINED );
					$order->set( 'pro-pay-status', __( 'Could not authorize pro-pay payment.', 'wp-e-commerce' ) );
				}

			break;
		}

		$order->save();
		$this->go_to_transaction_results();

	}

	public function capture_payment( $token ) {

		if ( $this->purchase_log->get( 'gateway' ) == 'pro-pay' ) {

			$order = $this->purchase_log;

			$params = array(
				'amount'	        => $order->get( 'totalprice' ),
				'orderId'	        => $order->get( 'id' ),
				'invoiceNumber'     => $order->get( 'sessionid' ),
				"addToVault"        => false,
				"paymentVaultToken" => array(
					"paymentMethodId" => $token,
					"publicKey"       => $this->public_key
				)
			);

			$response = $this->execute( 'Payments/Charge', $params );

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}

			if ( isset( $response['ResponseBody']->transaction->transactionId ) ) {
				$transaction_id = $response['ResponseBody']->transaction->transactionId;
				$auth_code      = $response['ResponseBody']->transaction->authorizationCode;
			} else {
				return false;
			}

			// Store transaction ID and Auth code in the order
			$order->set( 'wp_transactionId', $transaction_id )->save();
			$order->set( 'wp_order_status' , 'Completed' )->save();
			$order->set( 'wp_authcode'     , $auth_code )->save();
			$order->set( 'transactid'      , $transaction_id )->save();
			$order->set( 'wp_order_token'  , $token )->save();

			return true;
		}

		return false;
	}

	public function authorize_payment( $token ) {

		if ( $this->purchase_log->get( 'gateway' ) == 'pro-pay' ) {

			$order = $this->purchase_log;

			$params = array(
				'amount'	        => $order->get( 'totalprice' ),
				'orderId'	        => $order->get( 'id' ),
				'invoiceNumber'     => $order->get( 'sessionid' ),
				"addToVault"        => false,
				"paymentVaultToken" => array(
					"paymentMethodId" => $token,
					"publicKey"       => $this->public_key,
				)
			);

			$response = $this->execute( 'Payments/Authorize', $params );

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}

			if ( isset( $response['ResponseBody']->transaction->transactionId ) ) {
				$transaction_id = $response['ResponseBody']->transaction->transactionId;
				$auth_code      = $response['ResponseBody']->transaction->authorizationCode;
			} else {
				return false;
			}

			// Store transaction ID and Auth code in the order
			$order->set( 'wp_transactionId', $transaction_id )->save();
			$order->set( 'wp_order_status' , 'Open' )->save();
			$order->set( 'wp_authcode'     , $auth_code )->save();
			$order->set( 'transactid'      , $transaction_id )->save();
			$order->set( 'wp_order_token'  , $token )->save();

			return true;
		}

		return false;
	}

	public function execute( $endpoint, $params = array(), $type = 'POST' ) {

	   // where we make the API petition
        $endpoint = $this->endpoint . $endpoint;

		if ( ! is_null( $params ) ) {
			$params += array(
				"developerApplication" => array(
					"developerId" => 10000644,
					"version"     => "1.2"
				),
			);
		}

		$data = json_encode( $params );

		$args = array(
			'timeout' => 15,
			'headers' => array(
				'Authorization' => $this->auth_token,
				'Content-Type'  => 'application/json',
			),
			'sslverify' => false,
			'body'      => $data,
		);

		$request  = $type == 'GET' ? wp_safe_remote_get( $endpoint, $args ) : wp_safe_remote_post( $endpoint, $args );
        $response = wp_remote_retrieve_body( $request );

		if ( ! is_wp_error( $request ) ) {

			$response_object = array();
			$response_object['ResponseBody'] = json_decode( $response );
			$response_object['Status']       = wp_remote_retrieve_response_code( $request );

			$request = $response_object;
		}

		return $request;
    }
}

class WPSC_Pro_Pay_Payments_Order_Handler {

	private static $instance;
	private $log;
	private $gateway;

	public function __construct( &$gateway ) {

		$this->log     = $gateway->purchase_log;
		$this->gateway = $gateway;

		$this->init();
	}

	/**
	 * Constructor
	 */
	public function init() {
		add_action( 'wpsc_purchlogitem_metabox_start', array( $this, 'meta_box' ), 8 );
	}

	public static function get_instance( $gateway ) {
		if ( is_null( self::$instance ) ) {
			self::$instance = new WPSC_Pro_Pay_Payments_Order_Handler( $gateway );
		}

		return self::$instance;
	}

	public function set_purchase_log( $id ) {
		$this->log = new WPSC_Purchase_Log( $id );
	}

	/**
	 * Perform order actions for Pro Pay
	 */
	public function order_actions() {
		check_ajax_referer( 'wp_order_action', 'security' );

		$order_id = absint( $_POST['order_id'] );
		$id       = isset( $_POST['pro-pay_id'] ) ? sanitize_text_field( $_POST['pro-pay_id'] ) : '';
		$action   = sanitize_title( $_POST['pro-pay_action'] );

		$this->set_purchase_log( $order_id );

		switch ( $action ) {
			case 'capture' :
				//Capture an AUTH
				$this->capture_payment($id);
			break;

			case 'void' :
				// void capture or auth before settled
				$this->void_payment( $id );
			break;

			case 'refund' :
				// refund a settled payment
				$this->refund_payment( $id );
			break;

			case 'void_refund' :
				// void a refund request
				$this->void_refund( $id );
			break;
		}

		echo json_encode( array( 'action' => $action, 'order_id' => $order_id, 'pro-pay_id' => $id ) );

		die();
	}

	/**
	 * meta_box function.
	 *
	 * @access public
	 * @return void
	 */
	function meta_box( $log_id ) {
		$this->set_purchase_log( $log_id );

		$gateway = $this->log->get( 'gateway' );

		if ( $gateway == 'pro-pay' ) {
			$this->authorization_box();
		}
	}

	/**
	 * pre_auth_box function.
	 *
	 * @access public
	 * @return void
	 */
	public function authorization_box() {

		$actions  = array();
		$order_id = $this->log->get( 'id' );

		// Get ids
		$wp_transaction_id 	= $this->log->get( 'wp_transactionId' );
		$wp_auth_code		= $this->log->get( 'wp_authcode' );
		$wp_order_status	= $this->log->get( 'wp_order_status' );

		//Don't change order status if a refund has been requested
		$wp_refund_set = wpsc_get_purchase_meta( $order_id, 'pro-pay_refunded', true );
		$order_info    = $this->refresh_transaction_info( $wp_transaction_id, ! (bool) $wp_refund_set );
		?>

		<div class="metabox-holder">
			<div id="wpsc-pro-pay-payments" class="postbox">
				<h3 class='hndle'><?php _e( 'pro-pay Payments' , 'wp-e-commerce' ); ?></h3>
				<div class='inside'>
					<p><?php
							_e( 'Current status: ', 'wp-e-commerce' );
							echo wp_kses_data( $this->log->get( 'pro-pay-status' ) );
						?>
					</p>
					<p><?php
							_e( 'Transaction ID: ', 'wp-e-commerce' );
							echo wp_kses_data( $wp_transaction_id );
						?>
					</p>
		<?php

		//Show actions based on order status
		switch ( $wp_order_status ) {
			case 'Open' :
				//Order is only authorized and still not captured/voided
				$actions['capture'] = array(
					'id'     => $wp_transaction_id,
					'button' => __( 'Capture funds', 'wp-e-commerce' )
				);

				//
				if ( ! $order_info['settled'] ) {
					//Void
					$actions['void'] = array(
						'id'     => $wp_transaction_id,
						'button' => __( 'Void order', 'wp-e-commerce' )
					);
				}

				break;
			case 'Completed' :
				//Order has been captured or its a direct payment
				if ( $order_info['settled'] ) {
					//Refund
					$actions['refund'] = array(
						'id'     => $wp_transaction_id,
						'button' => __( 'Refund order', 'wp-e-commerce' )
					);
				} else {
					//Void
					$actions['void'] = array(
						'id'     => $wp_transaction_id,
						'button' => __( 'Void order', 'wp-e-commerce' )
					);
				}

			break;
			case 'Refunded' :
				//Order is settled and a refund has been requested
				$wp_refund_id       = wpsc_get_purchase_meta( $order_id, 'pro-pay_refund_id', true );

				if ( $wp_refund_id ) {
					//Get refund order status to check if its eligible for a void (not settled)
					$refund_status = $this->refresh_transaction_info( $wp_refund_id, false );

					if ( ! $refund_status['settled'] ) {
						//Show void only if not settled.
						$actions['void_refund'] = array(
							'id'     => $wp_refund_id,
							'button' => __( 'Void Refund request', 'wp-e-commerce' )
						);
					}
				}

				break;
			case 'Voided' :
			break;
		}

		if ( ! empty( $actions ) ) {

			echo '<p class="buttons">';

			foreach ( $actions as $action_name => $action ) {
				echo '<a href="#" class="button" data-action="' . $action_name . '" data-id="' . $action['id'] . '">' . $action['button'] . '</a> ';
			}

			echo '</p>';

		}
		?>
		<script type="text/javascript">
		jQuery( document ).ready( function( $ ) {
			$('#wpsc-pro-pay-payments').on( 'click', 'a.button, a.refresh', function( e ) {
				var $this = $( this );
				e.preventDefault();

				var data = {
					action: 		'pro-pay_order_action',
					security: 		'<?php echo wp_create_nonce( "wp_order_action" ); ?>',
					order_id: 		'<?php echo $order_id; ?>',
					pro-pay_action: 	$this.data('action'),
					pro-pay_id: 		$this.data('id'),
					pro-pay_refund_amount: $('.pro-pay_refund_amount').val(),
				};

				// Ajax action
				$.post( ajaxurl, data, function( result ) {
						location.reload();
					}, 'json' );

				return false;
			});
		} );

		</script>
		</div>
		</div>
		</div>
		<?php
	}

    /**
     * Get the order status from API
     *
     * @param  string $transaction_id
     */
	public function refresh_transaction_info( $transaction_id, $update = true ) {

		if ( $this->log->get( 'gateway' ) == 'pro-pay' ) {

			$response = $this->gateway->execute( 'transactions/'. $transaction_id, null, 'GET' );

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}

			$response_object = array();
			$response_object['trans_type'] = $response['ResponseBody']->transactions[0]->transactionType;
			$response_object['settled']    = isset( $response['ResponseBody']->transactions[0]->settlementData ) ? true : false;

			//Recheck status and update if required
			if ( $update ) {
				switch ( $response_object['trans_type'] ) {
					case 'AUTH_ONLY' :
						$this->log->set( 'wp_order_status', 'Open' )->save();
					break;

					case 'VOID' :
						$this->log->set( 'wp_order_status', 'Voided' )->save();
					break;

					case 'REFUND' :
					case 'CREDIT' :
						$this->log->set( 'wp_order_status', 'Refunded' )->save();
					break;

					case 'AUTH_CAPTURE' :
					case 'PRIOR_AUTH_CAPTURE' :
						$this->log->set( 'wp_order_status', 'Completed' )->save();
					break;
				}
			}

			return $response_object;
		}
	}

    /**
     * Void auth/capture
     *
     * @param  string $transaction_id
     */
    public function void_payment( $transaction_id ) {

		if ( $this->log->get( 'gateway' ) == 'pro-pay' ) {

			$params = array(
				'amount'		=> $this->log->get( 'totalprice' ),
				'transactionId' => $transaction_id,
			);

			$response = $this->gateway->execute( 'Payments/Void', $params );

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}

			$this->log->set( 'wp_order_status', 'Voided' )->save();
			$this->log->set( 'pro-pay-status', sprintf( __( 'Authorization voided (Auth ID: %s)', 'wp-e-commerce' ), $response['ResponseBody']->transaction->authorizationCode ) )->save();
			$this->log->set( 'processed'      , WPSC_Purchase_Log::INCOMPLETE_SALE )->save();
			$this->log->set( 'transactid'     , $response['ResponseBody']->transaction->transactionId )->save();
		}
    }

    /**
     * Refund payment
     *
     * @param  string $transaction_id
     */
    public function refund_payment( $transaction_id ) {

		if ( $this->log->get( 'gateway' ) == 'pro-pay' ) {

			$params = array(
				'amount'		=> $this->log->get( 'totalprice' ),
				'transactionId' => $transaction_id,

			);

			$response = $this->gateway->execute( 'Payments/Refund', $params );

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}

			wpsc_add_purchase_meta( $this->log->get( 'id' ), 'pro-pay_refunded', true );
			wpsc_add_purchase_meta( $this->log->get( 'id' ), 'pro-pay_refund_id', $response['ResponseBody']->transaction->transactionId );

			$this->log->set( 'pro-pay-status', sprintf( __( 'Refunded (Transaction ID: %s)', 'wp-e-commerce' ), $response['ResponseBody']->transaction->transactionId ) )->save();
			$this->log->set( 'processed'      , WPSC_Purchase_Log::REFUNDED )->save();
			$this->log->set( 'wp_order_status', 'Refunded' )->save();
			$this->log->set( 'transactid'     , $response['ResponseBody']->transaction->transactionId )->save();
		}
    }

    /**
     * Capture authorized payment
     *
     * @param  string $transaction_id
     */
    public function capture_payment( $transaction_id ) {

		if ( $this->log->get( 'gateway' ) == 'pro-pay' ) {

			$params = array(
				'amount'		=> $this->log->get( 'totalprice' ),
				'transactionId' => $transaction_id,
			);

			$response = $this->gateway->execute( 'Payments/Capture', $params );

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}

			$this->log->set( 'wp_order_status', 'Completed' )->save();
			$this->log->set( 'pro-pay-status', sprintf( __( 'Authorization Captured (Auth ID: %s)', 'wp-e-commerce' ), $response['ResponseBody']->transaction->authorizationCode ) )->save();
			$this->log->set( 'processed'      , WPSC_Purchase_Log::ACCEPTED_PAYMENT )->save();
			$this->log->set( 'transactid'     , $response['ResponseBody']->transaction->transactionId )->save();
		}
    }

    /**
     * Void a refund request
     *
     * @param  string $transaction_id
     */
    public function void_refund( $transaction_id ) {

		if ( $this->log->get( 'gateway' ) == 'pro-pay' ) {

			$params = array(
				'amount'		=> $this->log->get( 'totalprice' ),
				'transactionId' => $transaction_id,
			);

			$response = $this->gateway->execute( 'Payments/Void', $params );

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}

			wpsc_delete_purchase_meta( $this->log->get( 'id' ), 'pro-pay_refunded' );
			wpsc_delete_purchase_meta( $this->log->get( 'id' ), 'pro-pay_refund_id' );

			$this->log->set( 'processed'      , WPSC_Purchase_Log::ACCEPTED_PAYMENT )->save();
			$this->log->set( 'wp_order_status', 'Completed' )->save();
			$this->log->set( 'pro-pay-status', sprintf( __( 'Refund Voided (Transaction ID: %s)', 'wp-e-commerce' ), $response['ResponseBody']->transaction->transactionId ) )->save();
			$this->log->set( 'transactid'     , $response['ResponseBody']->transaction->transactionId )->save();
		}
    }
}

class WPSC_ProPay_Request {

	protected $config;

	public function __construct( $config ) {
		$this->config = $config;
	}

	public function request( $resource, $args = array() ) {

		$endpoint = WPSC_Payment_Gateway_Pro_Pay::get_endpoint( 'rest-api-endpoint', $this->config->environment );

		$url = $endpoint . '/'. ltrim( $resource, '/' );

		$args = wp_parse_args( $args, array(
			'timeout' => 60,
			'method'  => 'PUT',
			'body'    => array(),
			'headers' => array(
				'content-type'  => 'application/json',
				'authorization' => self::generate_auth( $this->config->biller_account_id, $this->config->auth_token )
			)
		) );

		$args['headers']['content-length'] = strlen( $args['body'] );

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

		$success = 200 === $code && 'SUCCESS' === $response->RequestResult->ResultValue;

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
