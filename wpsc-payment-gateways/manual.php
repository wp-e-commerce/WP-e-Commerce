<?php

class WPSC_Payment_Gateway_Manual extends WPSC_Payment_Gateway
{
	public function get_title() {
		return __( 'Manual Payment Gateway 3.0', 'wpsc' );
	}
	
	public function setup_form() {
		?>
		<tr>
			<td colspan="2">
				<p>
					<label for="wpsc-manual-gateway-setup"><?php _e( 'Instructions', 'wpsc' ); ?></label><br />
					<textarea id="wpsc-manual-gateway-setup" cols='' rows='10' name='<?php echo esc_attr( $this->setting->get_field_name( 'payment_instructions' ) ); ?>'><?php echo esc_html( $this->setting->get( 'payment_instructions' ) ); ?></textarea><br />
					<small><?php _e('Enter the payment instructions that you wish to display to your customers when they make a purchase', 'wpsc'); ?></small><br />
					<small><?php _e('For example, this is where you the Shop Owner might enter your bank account details or address so that your customer can make their manual payment.', 'wpsc'); ?></small>;
				</p>
			</td>
		</tr>
		<?php
	}
	
	protected function __construct() {
		parent::__construct();
	}
}