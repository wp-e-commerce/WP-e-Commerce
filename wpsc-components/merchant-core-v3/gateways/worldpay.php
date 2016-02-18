<?php
class WPSC_Payment_Gateway_WorldPay extends WPSC_Payment_Gateway {

	private $endpoints = array(
		'sandbox' => 'https://gwapi.demo.securenet.com/api/',
		'production' => 'https://gwapi.securenet.com/api/',
	);
	
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
		$this->sandbox			= $this->setting->get( 'sandbox_mode' ) == '1' ? true : false;
		$this->endpoint			= $this->sandbox ? $this->endpoints['sandbox'] : $this->endpoints['production'];
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
	
	public function init() {
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

	}
	

}