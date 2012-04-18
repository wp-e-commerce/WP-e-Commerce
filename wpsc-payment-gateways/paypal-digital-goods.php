<?php

require_once( 'paypal-express-checkout.php' );

class WPSC_Payment_Gateway_Paypal_Digital_Goods extends WPSC_Payment_Gateway_Paypal_Express_Checkout
{
	const SANDBOX_URL = 'https://www.sandbox.paypal.com/incontext?token=';
	const LIVE_URL = 'https://www.paypal.com/incontext?token=';

	private $gateway;

	public function __construct() {

		require_once( 'php-merchant/gateways/paypal-digital-goods.php' );
		$this->gateway = new PHP_Merchant_Paypal_Digital_Goods();

		// Now that the gateway is created, call parent constructor
		parent::__construct();

		$this->title = __( 'Paypal Digital Goods for Express Checkout', 'wpsc' );

		$this->gateway->set_options( array(
			'api_username'     => $this->setting->get( 'api_username' ),
			'api_password'     => $this->setting->get( 'api_password' ),
			'api_signature'    => $this->setting->get( 'api_signature' ),
			'cancel_url'       => get_option( 'shopping_cart_url' ),
			'currency'         => $this->get_currency_code(),
			'test'             => (bool) $this->setting->get( 'sandbox_mode' )
		) );

		add_action( 'wpsc_bottom_of_shopping_cart', array( $this, 'add_iframe_script' ) );

		add_action( 'wpsc_confirm_checkout', array( $this, 'remove_iframe_script' ) );

		add_filter( 'wpsc_purchase_log_gateway_data', array( get_parent_class( $this ), 'filter_purchase_log_gateway_data' ), 10, 2 );
	}

	/**
	 * To start the incontext checkout flow, a DGFlow object needs to be created. This JavaScript creates a
	 * DGFlow object if the "paypal-digital-goods" payment gateway radio button is checked. 
	 */
	public function add_iframe_script() {
		$this->remove_iframe_script(); ?>
		<script src ="https://www.paypalobjects.com/js/external/dg.js" type="text/javascript"></script>
		<script>
		jQuery(document).ready(function($){
			$('form.wpsc_checkout_forms input[name="submit"]').click(function(){
				if($('input[name="custom_gateway"]:checked').val() === undefined || $('input[name="custom_gateway"]:checked').val() == "paypal-digital-goods" ) {
					var dg = new PAYPAL.apps.DGFlow({trigger:'submit-purchase'});
					dg.startFlow();
				}
				return false;
			});
		});
		</script>
		<?php
	}

	/**
	 * When a buyer returns from PayPal, they will still be in the iframe. This function removes the iframe
	 * and returns the buyer to the main site. 
	 * 
	 * It also hides the body to prevent the second or two or showing the entire checkout page in the iframe. 
	 */
	public function remove_iframe_script(){
		echo '<script>if (window!=top) { document.body.style.display = "none"; top.location.replace(document.location); }</script>';
	}

	protected function get_return_url() {
		$location = add_query_arg( array(
				'sessionid'                => $this->purchase_log->get( 'sessionid' ),
				'payment_gateway'          => 'paypal-digital-goods',
				'payment_gateway_callback' => 'confirm_transaction',
			),
			home_url( 'index.php' )
		);
		return apply_filters( 'wpsc_paypal_digital_goods_return_url', $location );
	}

	protected function get_notify_url() {
		$location = add_query_arg( array(
			'payment_gateway'          => 'paypal-digital-goods',
			'payment_gateway_callback' => 'ipn',
		), home_url( 'index.php' ) );

		return apply_filters( 'wpsc_paypal_express_checkout_notify_url', $location );
	}

	public function callback_ipn() {
		$ipn = new PHP_Merchant_Paypal_IPN( false, (bool) $this->setting->get( 'sandbox_mode', false ) );

		if ( $ipn->is_verified() ) {
			$sessionid = $ipn->get( 'invoice' );
			$this->set_purchase_log_for_callbacks( $sessionid );

			if ( $ipn->is_payment_denied() ) {
				$this->purchase_log->set( 'processed', WPSC_Purchase_Log::PAYMENT_DECLINED );
			} elseif ( $ipn->is_payment_refunded() ) {
				$this->purchase_log->set( 'processed', WPSC_Purchase_Log::REFUNDED );
			} elseif ( $ipn->is_payment_completed() ) {
				$this->purchase_log->set( 'processed', WPSC_Purchase_Log::ACCEPTED_PAYMENT );
			} elseif ( $ipn->is_payment_pending() ) {
				if ( $ipn->is_payment_refund_pending() )
					$this->purchase_log->set( 'processed', WPSC_Purchase_Log::REFUND_PENDING );
				else
					$this->purchase_log->set( 'processed', WPSC_Purchase_Log::ORDER_RECEIVED );
			}

			$this->purchase_log->save();
			transaction_results( $sessionid, false );
		}

		exit;
	}

	public function callback_confirm_transaction() {

		if ( ! isset( $_REQUEST['sessionid'] ) || ! isset( $_REQUEST['token'] ) || ! isset( $_REQUEST['PayerID'] ) )
			return;

		$this->set_purchase_log_for_callbacks();

		$this->callback_process_confirmed_payment();
	}

	public function callback_process_confirmed_payment() {

		$args = array_map( 'urldecode', $_GET );

		extract( $args, EXTR_SKIP );
		if ( ! isset( $sessionid ) || ! isset( $token ) || ! isset( $PayerID ) )
			return;

		$this->set_purchase_log_for_callbacks();

		$total = $this->convert( $this->purchase_log->get( 'totalprice' ) );
		$options = array(
			'token'    => $token,
			'payer_id' => $PayerID,
			'invoice'  => $this->purchase_log->get( 'sessionid' ),
		);
		$options += $this->checkout_data->get_gateway_data();
		$options += $this->purchase_log->get_gateway_data( parent::get_currency_code(), $this->get_currency_code() );

		if ( $this->setting->get( 'ipn', false ) )
			$options['notify_url'] = $this->get_notify_url();

		$response = $this->gateway->purchase( $options );

		$location = add_query_arg( array(
				'sessionid'       => $this->purchase_log->get( 'sessionid' ),
				'token'           => $token,
				'PayerID'         => $PayerID,
				'payment_gateway' => 'paypal-digital-goods'
			),
			get_option( 'transact_url' )
		);

		if ( $response->has_errors() ) {
			$_SESSION['paypal_express_checkout_errors'] = serialize( $response->get_errors() );
			$location = add_query_arg( array( 'payment_gateway_callback' => 'display_paypal_error' ), $location );
		} elseif ( $response->is_payment_completed() || $response->is_payment_pending() ) {
			$location = remove_query_arg( 'payment_gateway', $location );

			if ( $response->is_payment_completed() )
				$this->purchase_log->set( 'processed', WPSC_Purchase_Log::ACCEPTED_PAYMENT );
			else
				$this->purchase_log->set( 'processed', WPSC_Purchase_Log::ORDER_RECEIVED );

			$this->purchase_log->set( 'transactid', $response->get( 'transaction_id' ) )
			                   ->set( 'date', time() )
			                   ->save();
		} else {
			$location = add_query_arg( array( 'payment_gateway_callback' => 'display_generic_error' ), $location );
		}

		$location = apply_filters( 'wpsc_paypal_digital_goods_confirmed_payment_url', $location );

		wp_redirect( $location );
		exit;
	}

	/**
	 * Output the form on the Digital Goods settings page. 
	 */
	public function setup_form() {
		$paypal_currency = $this->get_currency_code();
		?>
		<tr>
			<td>
				<label for="wpsc-paypal-express-api-username"><?php _e( 'API Username', 'wpsc' ); ?></label>
			</td>
			<td>
				<input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'api_username' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'api_username' ) ); ?>" id="wpsc-paypal-express-api-username" />
			</td>
		</tr>
		<tr>
			<td>
				<label for="wpsc-paypal-express-api-password"><?php _e( 'API Password', 'wpsc' ); ?></label>
			</td>
			<td>
				<input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'api_password' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'api_password' ) ); ?>" id="wpsc-paypal-express-api-password" />
			</td>
		</tr>
		<tr>
			<td>
				<label for="wpsc-paypal-express-api-signature"><?php _e( 'API Signature', 'wpsc' ); ?></label>
			</td>
			<td>
				<input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'api_signature' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'api_signature' ) ); ?>" id="wpsc-paypal-express-api-signature" />
			</td>
		</tr>
		<tr>
			<td>
				<label><?php _e( 'Sandbox Mode', 'wpsc' ); ?></label>
			</td>
			<td>
				<label><input <?php checked( $this->setting->get( 'sandbox_mode' ) ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'sandbox_mode' ) ); ?>" value="1" /> <?php _e( 'Yes', 'wpsc' ); ?></label>&nbsp;&nbsp;&nbsp;
				<label><input <?php checked( $this->setting->get( 'sandbox_mode' ), false ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'sandbox_mode' ) ); ?>" value="0" /> <?php _e( 'No', 'wpsc' ); ?></label>
			</td>
		</tr>
		<tr>
			<td>
				<label><?php _e( 'IPN', 'wpsc' ); ?></label>
			</td>
			<td>
				<label><input <?php checked( $this->setting->get( 'ipn' ) ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'ipn' ) ); ?>" value="1" /> <?php _e( 'Yes', 'wpsc' ); ?></label>&nbsp;&nbsp;&nbsp;
				<label><input <?php checked( $this->setting->get( 'ipn' ), false ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'ipn' ) ); ?>" value="0" /> <?php _e( 'No', 'wpsc' ); ?></label>
			</td>
		</tr>
		<?php if ( ! $this->is_currency_supported() ): ?>
			<tr>
				<td colspan="2">
					<h4><?php _e( 'Currency Conversion', 'wpsc' ); ?></h4>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<p><?php _e( 'Your base currency is currently not accepted by PayPal. As a result, before a payment request is sent to Paypal, WP e-Commerce has to convert the amounts into one of Paypal supported currencies. Please select your preferred currency below.', 'wpsc' ); ?></p>
				</td>
			</tr>
			<tr>
				<td>
					<label for "wpsc-paypal-express-currency"><?php _e( 'Paypal Currency', 'wpsc' ); ?></label>
				</td>
				<td>
					<select name="<?php echo esc_attr( $this->setting->get_field_name( 'currency' ) ); ?>" id="wpsc-paypal-express-currency">
						<?php foreach ($this->gateway->get_supported_currencies() as $currency): ?>
							<option <?php selected( $currency, $paypal_currency ); ?> value="<?php echo esc_attr( $currency ); ?>"><?php echo esc_html( $currency ); ?></option>
						<?php endforeach ?>
					</select>
				</td>
			</tr>
		<?php endif ?>

		<?php
	}

	/**
	 * Process a purchase. 
	 */
	public function process( $args = array() ) {
		$total = $this->convert( $this->purchase_log->get( 'totalprice' ) );
		$options = array(
			'return_url' => $this->get_return_url(),
			'invoice'    => $this->purchase_log->get( 'sessionid' ),
		);
		$options += $this->checkout_data->get_gateway_data();
		$options += $this->purchase_log->get_gateway_data( parent::get_currency_code(), $this->get_currency_code() );

		if ( $this->setting->get( 'ipn', false ) )
			$options['notify_url'] = $this->get_notify_url();

		$response = $this->gateway->setup_purchase( $options );

		if ( $response->is_successful() ) {
			$url = ( $this->setting->get( 'sandbox_mode' ) ? self::SANDBOX_URL : self::LIVE_URL ) . $response->get( 'token' );
		} else {
			$_SESSION['paypal_express_checkout_errors'] = serialize( $response->get_errors() );
			$url = add_query_arg( array(
				'payment_gateway'          => 'paypal-digital-goods',
				'payment_gateway_callback' => 'display_paypal_error',
			), $this->get_return_url() );
		}

		if( ! isset( $args['return_only'] ) || $args['return_only'] !== true ) {
			wp_redirect( $url );
			exit;
		}

		return $url;
	}
}