<?php
require_once( 'paypal-express-checkout.php' );

/**
 * The PayPal Express Checkout Gateway class
 *
 */
class WPSC_Payment_Gateway_Paypal_Digital_Goods extends WPSC_Payment_Gateway_Paypal_Express_Checkout {
    const SANDBOX_URL = 'https://www.sandbox.paypal.com/incontext?token=';
    const LIVE_URL    = 'https://www.paypal.com/incontext?token=';
    protected $gateway;

    /**
     * Constructor of PayPal Express Checkout Gateway
     *
     * @param array $options
     *
     * @since 3.9.0
     */
    public function __construct( $options ) {
        require_once( 'php-merchant/gateways/paypal-digital-goods.php' );
        // Now that the gateway is created, call parent constructor
        parent::__construct( $options, true );

		$this->gateway = new PHP_Merchant_Paypal_Digital_Goods( $options );
        $this->title = __( 'PayPal Digital Goods for Express Checkout', 'wp-e-commerce' );

        $this->gateway->set_options( array(
            'api_username'     => $this->setting->get( 'api_username' ),
            'api_password'     => $this->setting->get( 'api_password' ),
            'api_signature'    => $this->setting->get( 'api_signature' ),
            'cancel_url'       => $this->get_cancel_url(),
            'currency'         => $this->get_currency_code(),
            'test'             => (bool) $this->setting->get( 'sandbox_mode' ),
            'address_override' => 1,
            'solution_type'	   => 'mark',
            'cart_logo'		   => $this->setting->get( 'cart_logo' ),
            'cart_border'	   => $this->setting->get( 'cart_border' ),
        ) );
    }

    /**
  	 * Run the gateway hooks
  	 *
  	 * @access public
  	 * @since 3.9.0
  	 *
  	 * @return void
  	 */
  	public function init() {
		parent::init();
	// Disable default selection
		add_filter(
			'wpsc_payment_method_form_fields',
			array( 'WPSC_Payment_Gateway_Paypal_Digital_Goods', 'filter_unselect_default' ), 100 , 1
		);

		// Load DG scripts and styles
		add_action( 'wp_enqueue_scripts', array( 'WPSC_Payment_Gateway_Paypal_Digital_Goods', 'dg_script' ) );

		// Express Checkout for DG Button
		add_action( 'wpsc_cart_item_table_form_actions_left', array( $this, 'add_ecs_button' ), 2, 2 );

		// Filter Digital Goods option on checkout
		add_filter( 'wpsc_payment_method_form_fields', array( &$this, 'dg_option_removal' ), 100 );
  	}

    /**
     * Toggles Digital Goods option based on whether or not shipping is being used on the given cart.
     *
     * @since  3.9.0
     *
     * @param  array $fields Payment method form fields
     *
     * @return array $fields Modified payment method form fields
     */
    public function dg_option_removal( $fields ) {
        if ( wpsc_uses_shipping() ) {
            // Remove DG option
            foreach( $fields as $index => $field ) {
                if ( $field['value'] === 'paypal-digital-goods' ) {
                    unset( $fields[ $index] );
                }
            }
        } else {
            // Remove Normal option
            foreach( $fields as $index => $field ) {
                if ( $field['value'] === 'paypal-express-checkout' ) {
                    unset( $fields[ $index ] );
                }
            }
        }
        return $fields;
    }

	/**
     * Insert the ExpessCheckout Shortcut Button
     *
     * @return void
     */
	public function add_ecs_button( $cart_table, $context ) {

		if ( wpsc_uses_shipping() || ! wpsc_is_gateway_active( 'paypal-digital-goods' ) ) {
			return;
		}

    if ( 'bottom' == $context ) {
        return;
    }

		if ( _wpsc_get_current_controller_name() === 'cart' ) {
			$url = $this->get_shortcut_url();
			echo '<a class="express-checkout-button" id="pp-ecs-dg" href="'. esc_url ( $url ) .'"><img src="https://www.paypalobjects.com/webstatic/en_US/i/buttons/checkout-logo-large.png" alt="' . __( 'Check out with PayPal', 'wp-e-commerce' ) . '" /></a>';
		}
	}

	/**
     * Return the ExpressCheckout Shortcut redirection URL
     *
     * @return void
     */
	public function get_shortcut_url( $callback = 'shortcut_process' ) {
		$location = add_query_arg( array(
			'payment_gateway'          => 'paypal-digital-goods',
			'payment_gateway_callback' => $callback,
		), home_url( 'index.php' ) );

		return apply_filters( 'wpsc_paypal_digital_goods_shortcut_url', $location );
	}

	/**
     * Sets the Review Callback for Review Order page.
     *
     * @param string $url
     * @return string
     */
    public function review_order_callback( $url ) {
        $args = array(
            'payment_gateway_callback' => 'review_transaction',
            'payment_gateway'          => 'paypal-digital-goods',
        );
        $url = add_query_arg( $args, $url );

        return $url;
    }

    /**
     * WordPress Enqueue for the Dgital Goods Script and CSS file
     *
     * @return void
     *
     * @since 3.9.0
     */
    public static function dg_script() {
		$dg_loc = array(
			'spinner_url' => wpsc_get_ajax_spinner(),
			'loading'     => __( 'Loading...', 'wp-e-commerce' ),
		);
		// Checkout Page
		if ( wpsc_is_checkout() ) {
			wp_enqueue_script( 'dg-script', 'https://www.paypalobjects.com/js/external/dg.js' );
			wp_enqueue_script( 'dg-script-internal', WPSC_URL . '/wpsc-components/merchant-core-v3/gateways/dg.js', array( 'jquery' ) );
			wp_localize_script( 'dg-script', 'dg_loc', $dg_loc );
		}
		// Cart Page
		if ( function_exists( 'wpsc_is_cart' ) && wpsc_is_cart() ) {
			wp_enqueue_script( 'dg-script', 'https://www.paypalobjects.com/js/external/dg.js' );
			wp_enqueue_script( 'dg-script-internal', WPSC_URL . '/wpsc-components/merchant-core-v3/gateways/dgs.js', array( 'jquery' ) );
			wp_localize_script( 'dg-script', 'dg_loc', $dg_loc );
		}
    }

    /**
     * No payment gateway is selected by default
     *
     * @access public
     * @since 3.9.0
     *
     * @param array $fields
     *
     * @return array
     */
    public static function filter_unselect_default( $fields ) {
        foreach ( $fields as $i => $field ) {
            $fields[ $i ][ 'checked' ] = false;
        }

        return $fields;
    }

    /**
     * Return the PayPal return URL
     *
     * @return string
     *
     * @since 3.9.0
     */
    protected function get_return_url() {
        $redirect = add_query_arg( array(
            'sessionid'                => $this->purchase_log->get( 'sessionid' ),
            'payment_gateway'          => 'paypal-digital-goods',
            'payment_gateway_callback' => 'return_url_redirect',
        ),
        get_option( 'transact_url' )
    );
        return apply_filters( 'wpsc_paypal_digital_goods_return_url_redirect', $redirect, $this );
    }

    /**
     * PayPal Lightbox Form redirection for the Return URL
     *
     * @return void
     *
     * @since 3.9.0
     */
    public function callback_return_url_redirect() {
        // Session id
        if ( ! isset( $_GET['sessionid'] ) ) {
            return;
        } else {
            $sessionid = $_GET['sessionid'];
        }

        // Page Styles
        wp_register_style( 'ppdg-iframe', plugins_url( 'dg.css', __FILE__ ) );

		// Apply any filters
		if ( wpsc_get_customer_meta( 'ecs-' . $sessionid ) ) {
			add_filter( 'wpsc_paypal_express_checkout_transact_url', array( &$this, 'review_order_url' ) );
			add_filter( 'wpsc_paypal_express_checkout_return_url', array( &$this, 'review_order_callback' ) );

			wpsc_delete_customer_meta( 'esc-' . $sessionid );
		}

        // Return a redirection page
?>
<html>
    <head>
        <title><?php _e( 'Processing...', 'wp-e-commerce' ); ?></title>
        <?php wp_print_styles( 'ppdg-iframe' ); ?>
    </head>
    <body>
        <div id="left_frame">
            <div id="right_frame">
                <p id="message">
                <?php _e( 'Processing Order', 'wp-e-commerce'); ?>

                </p>
                <img src="https://www.paypal.com/en_US/i/icon/icon_animated_prog_42wx42h.gif" alt="Processing..." />
                <div id="right_bottom">
                    <div id="left_bottom">
                    </div>
                </div>
            </div>
        </div>
        <script type="text/javascript">
        <?php $location = $this->get_original_return_url( $sessionid );  ?>
        setTimeout('if (window!=top) {top.location.replace("<?php echo $location; ?>");}else{location.replace("<?php echo $location; ?>");}', 1500);
        </script>
    </body>
</html>
<?php
        exit();
    }

    /**
     * Return the original (real) Return URL
     *
     * @param integer $session_id
     *
     * @return string
     *
     * @since 3.9.0
     */
    protected function get_original_return_url( $session_id ) {
		$transact_url = get_option( 'transact_url' );
		$transact_url = apply_filters( 'wpsc_paypal_digital_goods_transact_url', $transact_url );
        $transact_url = apply_filters( 'wpsc_paypal_express_checkout_transact_url', $transact_url );

        $location = add_query_arg( array(
            'sessionid'                => $session_id,
            'token'                    => $_REQUEST['token'],
            'PayerID'                  => $_REQUEST['PayerID'],
            'payment_gateway'          => 'paypal-digital-goods',
            'payment_gateway_callback' => 'confirm_transaction',
		),
		$transact_url
    );

        $location = wp_validate_redirect( $location );
		$location = apply_filters( 'wpsc_paypal_express_checkout_return_url', $location );

        return apply_filters( 'wpsc_paypal_digital_goods_return_url', $location );
    }

    /**
     * Return the Cancel URL
     *
     * @return string
     *
     * @since 3.9.0
     */
    protected function get_cancel_url() {
        $redirect = add_query_arg( array(
            'payment_gateway'          => 'paypal-digital-goods',
            'payment_gateway_callback' => 'cancel_url_redirect',
        ),
        get_option( 'transact_url' )
    );
        return apply_filters( 'wpsc_paypal_digital_goods_cancel_url_redirect', $redirect );
    }

    /**
     * PayPal Lightbox Form redirection for the Cancel URL
     *
     * @return void
     *
     * @since 3.9.0
     */
    public function callback_cancel_url_redirect() {
        // Page Styles
        wp_register_style( 'ppdg-iframe', plugins_url( 'dg.css', __FILE__ ) );

        // Return a redirection page
?>
    <html>
    <head>
    <title><?php _e( 'Processing...', 'wp-e-commerce' ); ?></title>
    <?php wp_print_styles( 'ppdg-iframe' ); ?>
        </head>
            <body>
            <div id="left_frame">
            <div id="right_frame">
            <p id="message">
            <?php _e( 'Cancelling Order', 'wp-e-commerce' ); ?>
        </p>
            <img src="https://www.paypal.com/en_US/i/icon/icon_animated_prog_42wx42h.gif" alt="Processing..." />
            <div id="right_bottom">
            <div id="left_bottom">
            </div>
            </div>
            </div>
            </div>
        <script type="text/javascript">
        <?php $location = $this->get_original_cancel_url() ;  ?>
        setTimeout('if (window!=top) {top.location.replace("<?php echo $location; ?>");}else{location.replace("<?php echo $location; ?>");}', 1500);
        </script>
    </body>
</html>
<?php
        exit();
    }

    /**
     * Return the original (real) Cancel URL
     *
     * @return string
     *
     * @since 3.9.0
     */
    protected function get_original_cancel_url() {
        return apply_filters( 'wpsc_paypal_digital_goods_cancel_url', $this->get_shopping_cart_payment_url() );
    }

    /**
     * Return the notify URL
     *
     * @return string
     *
     * @since 3.9.0
     */
    protected function get_notify_url() {
        $location = add_query_arg( array(
            'payment_gateway'          => 'paypal-digital-goods',
            'payment_gateway_callback' => 'ipn',
        ), home_url( 'index.php' ) );

        return apply_filters( 'wpsc_paypal_express_checkout_notify_url', $location );
    }

    /**
     * IPN Callback function
     *
     * @return void
     *
     * @since 3.9.0
     */
    public function callback_ipn() {
        $ipn = new PHP_Merchant_Paypal_IPN( false, (bool) $this->setting->get( 'sandbox_mode', false ) );

        if ( $ipn->is_verified() ) {
            $sessionid = $ipn->get( 'message_id' );
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


    /**
     * Confirm Transaction Callback
     *
     * @return null
     *
     * @since 3.9.0
     */
    public function callback_confirm_transaction() {

        if ( ! isset( $_GET['sessionid'] ) || ! isset( $_GET['token'] ) || ! isset( $_GET['PayerID'] ) ) {
            return;
        }

        $this->set_purchase_log_for_callbacks();

        $this->callback_process_confirmed_payment();
    }

    /**
     * Process the transaction through the PayPal APIs
     *
     * @since 3.9.0
     */
    public function callback_process_confirmed_payment() {
        $args = array_map( 'urldecode', $_GET );
        extract( $args, EXTR_SKIP );

        if ( ! isset( $sessionid ) || ! isset( $token ) || ! isset( $PayerID ) ) {
            return;
        }

        $this->set_purchase_log_for_callbacks();

        $total = $this->convert( $this->purchase_log->get( 'totalprice' ) );
        $options = array(
            'token'    => $token,
            'payer_id' => $PayerID,
            'message_id'    => $this->purchase_log->get( 'id' ),
            'invoice'		=> $this->purchase_log->get( 'sessionid' ),
        );
        $options += $this->checkout_data->get_gateway_data();
        $options += $this->purchase_log->get_gateway_data( parent::get_currency_code(), $this->get_currency_code() );

        if ( $this->setting->get( 'ipn', false ) ) {
            $options['notify_url'] = $this->get_notify_url();
        }

        // GetExpressCheckoutDetails
        $details = $this->gateway->get_details_for( $token );
        $this->log_payer_details( $details );

        $response = $this->gateway->purchase( $options );
        $this->log_protection_status( $response );
        $location = remove_query_arg( 'payment_gateway_callback' );

        if ( $response->has_errors() ) {
            wpsc_update_customer_meta( 'paypal_express_checkout_errors', $response->get_errors() );
            $location = add_query_arg( array( 'payment_gateway_callback' => 'display_paypal_error' ) );
        } elseif ( $response->is_payment_completed() || $response->is_payment_pending() ) {
            $location = remove_query_arg( 'payment_gateway' );

            if ( $response->is_payment_completed() ) {
                $this->purchase_log->set( 'processed', WPSC_Purchase_Log::ACCEPTED_PAYMENT );
            } else {
                $this->purchase_log->set( 'processed', WPSC_Purchase_Log::ORDER_RECEIVED );
            }

            $this->purchase_log->set( 'transactid', $response->get( 'transaction_id' ) )
                ->set( 'date', time() )
                ->save();
        } else {
            $location = add_query_arg( array( 'payment_gateway_callback' => 'display_generic_error' ) );
        }

        wp_redirect( esc_url_raw( $location ) );
        exit;

    }

    /**
     * PayPal Lightbox Form redirection for the Error Page
     *
     * @return void
     *
     * @since 3.9.0
     */
    public function callback_display_paypal_error_redirect() {
        // Redirect Location
        $location = esc_url( add_query_arg( array(
            'payment_gateway'          => 'paypal-digital-goods',
            'payment_gateway_callback' => 'display_paypal_error',
        ), base64_decode( $_GET['return_url'] ) ) );

        // Page Styles
        wp_register_style( 'ppdg-iframe', plugins_url( 'dg.css', __FILE__ ) );

        // Return a redirection page
?>
<html>
    <head>
        <title><?php __( 'Processing...', 'wp-e-commerce' ); ?></title>
        <?php wp_print_styles( 'ppdg-iframe' ); ?>
    </head>
    <body>
        <div id="left_frame">
            <div id="right_frame">
                <p id="message">
                <?php _e( 'Processing Order', 'wp-e-commerce'); ?>
                </p>
                <img src="https://www.paypal.com/en_US/i/icon/icon_animated_prog_42wx42h.gif" alt="Processing..." />
                <div id="right_bottom">
                    <div id="left_bottom">
                    </div>
                </div>
            </div>
        </div>
        <script type="text/javascript">
        setTimeout('if (window!=top) {top.location.replace("<?php echo $location; ?>");}else{location.replace("<?php echo $location; ?>");}', 1500);
        </script>
    </body>
</html>
<?php
        exit();


    }

    public function callback_display_paypal_error() {
        add_filter( 'wpsc_get_transaction_html_output', array( $this, 'filter_paypal_error_page' ) );
    }

    public function callback_display_generic_error() {
        add_filter( 'wpsc_get_transaction_html_output', array( $this, 'filter_generic_error_page' ) );
    }

    /**
     * Error Page Template
     *
     * @since 3.9.0
     */
    public function filter_paypal_error_page() {
        $errors = wpsc_get_customer_meta( 'paypal_express_checkout_errors' );
        ob_start();
?>
<p>
<?php _e( 'Sorry, your transaction could not be processed by PayPal. Please contact the site administrator. The following errors are returned:', 'wp-e-commerce' ); ?>
</p>
<ul>
    <?php foreach ( $errors as $error ): ?>
    <li><?php echo esc_html( $error['details'] ) ?> (<?php echo esc_html( $error['code'] ); ?>)</li>
    <?php endforeach; ?>
</ul>
<p><a href="<?php echo esc_url( $this->get_shopping_cart_payment_url() ); ?>"><?php _e( 'Click here to go back to the checkout page.', 'wp-e-commerce' ) ?></a></p>
<?php
        $output = apply_filters( 'wpsc_paypal_express_checkout_gateway_error_message', ob_get_clean(), $errors );
        return $output;
    }

    /**
     * Generic Error Page Template
     *
     * @since 3.9.0
     */
    public function filter_generic_error_page() {
        ob_start();
?>
<p><?php _e( 'Sorry, but your transaction could not be processed by PayPal for some reason. Please contact the site administrator.', 'wp-e-commerce' ); ?></p>
<p><a href="<?php echo esc_attr( $this->get_shopping_cart_payment_url() ); ?>"><?php _e( 'Click here to go back to the checkout page.', 'wp-e-commerce' ) ?></a></p>
<?php
        $output = apply_filters( 'wpsc_paypal_express_checkout_generic_error_message', ob_get_clean() );
        return $output;
    }

    /**
     * Settings Form Template
     *
     * @since 3.9.0
     */
    public function setup_form() {
        $paypal_currency = $this->get_currency_code();
?>
<!-- Account Credentials -->
<tr>
    <td colspan="2">
        <h4><?php _e( 'Account Credentials', 'wp-e-commerce' ); ?></h4>
    </td>
</tr>
<tr>
    <td>
        <label for="wpsc-paypal-express-api-username"><?php _e( 'API Username', 'wp-e-commerce' ); ?></label>
    </td>
    <td>
        <input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'api_username' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'api_username' ) ); ?>" id="wpsc-paypal-express-api-username" />
    </td>
</tr>
<tr>
    <td>
        <label for="wpsc-paypal-express-api-password"><?php _e( 'API Password', 'wp-e-commerce' ); ?></label>
    </td>
    <td>
        <input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'api_password' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'api_password' ) ); ?>" id="wpsc-paypal-express-api-password" />
    </td>
</tr>
<tr>
    <td>
        <label for="wpsc-paypal-express-api-signature"><?php _e( 'API Signature', 'wp-e-commerce' ); ?></label>
    </td>
    <td>
        <input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'api_signature' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'api_signature' ) ); ?>" id="wpsc-paypal-express-api-signature" />
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
<tr>
    <td>
        <label><?php _e( 'IPN', 'wp-e-commerce' ); ?></label>
    </td>
    <td>
        <label><input <?php checked( $this->setting->get( 'ipn' ) ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'ipn' ) ); ?>" value="1" /> <?php _e( 'Yes', 'wp-e-commerce' ); ?></label>&nbsp;&nbsp;&nbsp;
        <label><input <?php checked( (bool) $this->setting->get( 'ipn' ), false ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'ipn' ) ); ?>" value="0" /> <?php _e( 'No', 'wp-e-commerce' ); ?></label>
    </td>
</tr>

<!-- Cart Customization -->
<tr>
    <td colspan="2">
        <label><h4><?php _e( 'Cart Customization', 'wp-e-commerce'); ?></h4></label>
    </td>
</tr>
<tr>
    <td>
        <label for="wpsc-paypal-express-cart-logo"><?php _e( 'Merchant Logo', 'wp-e-commerce' ); ?></label>
    </td>
    <td>
		<input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'cart_logo' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'cart_logo' ) ); ?>" id="wpsc-paypal-express-cart-logo" /><br><span class="small description"><?php _e( 'The image must be stored in a HTTPS Server. Limit the image to 190 pixels wide by 60 pixels high.', 'wp-e-commerce' ); ?></span>
    </td>
</tr>
<tr>
    <td>
        <label for="wpsc-paypal-express-cart-border"><?php _e( 'Cart Border Color', 'wp-e-commerce' ); ?></label>
    </td>
    <td>
        <input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'cart_border' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'cart_border' ) ); ?>" id="wpsc-paypal-express-cart-border" />
    </td>
</tr>

<!-- Currency Conversion -->
<?php if ( ! $this->is_currency_supported() ): ?>
<tr>
    <td colspan="2">
        <h4><?php _e( 'Currency Conversion', 'wp-e-commerce' ); ?></h4>
    </td>
</tr>
<tr>
    <td colspan="2">
        <p><?php _e( "Your base currency is currently not accepted by PayPal. As a result, before a payment request is sent to PayPal, WP eCommerce has to convert the amounts into one of PayPal's supported currencies. Please select your preferred currency below.", 'wp-e-commerce' ); ?></p>
    </td>
</tr>
<tr>
    <td>
        <label for "wpsc-paypal-express-currency"><?php _e( 'PayPal Currency', 'wp-e-commerce' ); ?></label>
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

<!-- Checkout Shortcut -->
<tr>
    <td colspan="2">
        <h4><?php _e( 'Express Checkout Shortcut', 'wp-e-commerce' ); ?></h4>
    </td>
</tr>
<tr>
    <td>
        <label><?php _e( 'Enable Shortcut', 'wp-e-commerce' ); ?></label>
    </td>
    <td>
        <label><input <?php checked( $this->setting->get( 'shortcut' ) ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'shortcut' ) ); ?>" value="1" /> <?php _e( 'Yes', 'wp-e-commerce' ); ?></label>&nbsp;&nbsp;&nbsp;
        <label><input <?php checked( (bool) $this->setting->get( 'shortcut' ), false ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'shortcut' ) ); ?>" value="0" /> <?php _e( 'No', 'wp-e-commerce' ); ?></label>
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
     * Process the SetExpressCheckout API Call
     *
     * @param array $args
     * @return void
     *
     * @since 3.9.0
     */
    public function process( $args = array() ) {
        $total = $this->convert( $this->purchase_log->get( 'totalprice' ) );

        $options = array(
            'return_url' => $this->get_return_url(),
            'message_id' => $this->purchase_log->get( 'id' ),
            'invoice'    => $this->purchase_log->get( 'sessionid' ),
            'address_override' => 1,
        );
        $options += $this->checkout_data->get_gateway_data();
        $options += $this->purchase_log->get_gateway_data( parent::get_currency_code(), $this->get_currency_code() );

        if ( $this->setting->get( 'ipn', false ) ) {
            $options['notify_url'] = $this->get_notify_url();
        }

        $response = $this->gateway->setup_purchase( $options );

        if ( $response->is_successful() ) {
            $url = ( $this->setting->get( 'sandbox_mode' ) ? self::SANDBOX_URL : self::LIVE_URL ) . $response->get( 'token' );
        } else {

            // SetExpressCheckout Failure
            $this->log_error( $response );
            wpsc_update_customer_meta( 'paypal_digital_goods_errors', $response->get_errors() );

            $url = add_query_arg( array(
                'payment_gateway'          => 'paypal-digital-goods',
                'payment_gateway_callback' => 'display_paypal_error_redirect',
                'return_url'               => base64_encode( $this->get_return_url() ),
            ), $this->get_return_url() );
        }

        if( ! isset( $args['return_only'] ) || $args['return_only'] !== true ) {
            echo( $url );
            exit;
        }

        return $url;
    }
}
