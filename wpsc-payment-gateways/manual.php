<?php

class WPSC_Payment_Gateway_Manual extends WPSC_Payment_Gateway
{
	/**
	 * Constructor of Manual Payment Gateway
	 *
	 * @access public
	 * @since 3.9
	 */
	public function __construct() {
		parent::__construct();
	}
	
	/**
	 * Returns the translated title of this payment gateway
	 *
	 * @access public
	 * @since 3.9
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Manual Payment Gateway 3.0', 'wpsc' );
	}
	
	/**
	 * Displays the setup form
	 *
	 * @access public
	 * @since 3.9
	 * @uses WPSC_Checkout_Form::get()
	 * @uses WPSC_Checkout_Form::field_drop_down_options()
	 * @uses WPSC_Checkout_Form::get_field_id_by_unique_name()
	 * @uses WPSC_Payment_Gateway_Setting::get()
	 *
	 * @return void
	 */
	public function setup_form() {
		$checkout_field_types = array(
			'billing' => __( 'Billing Fields', 'wpsc' ),
			'shipping' => __( 'Shipping Fields', 'wpsc' ),
		);
		
		$fields = array(
			'firstname' => __( 'First Name', 'wpsc' ),
			'lastname'  => __( 'Last Name', 'wpsc' ),
			'address'   => __( 'Address', 'wpsc' ),
			'city'      => __( 'City', 'wpsc' ),
			'state'     => __( 'State', 'wpsc' ),
			'country'   => __( 'Country', 'wpsc' ),
			'postcode'  => __( 'Postal Code', 'wpsc' ),
		);
		$checkout_form = WPSC_Checkout_Form::get();
		?>
		<tr>
			<td colspan="2">
				<p>
					<label for="wpsc-manual-gateway-setup"><?php _e( 'Instructions', 'wpsc' ); ?></label><br />
					<textarea id="wpsc-manual-gateway-setup" cols='' rows='10' name='<?php echo esc_attr( $this->setting->get_field_name( 'payment_instructions' ) ); ?>'><?php echo esc_html( $this->setting->get( 'payment_instructions' ) ); ?></textarea><br />
					<small><?php _e('Enter the payment instructions that you wish to display to your customers when they make a purchase.', 'wpsc'); ?></small><br />
					<small><?php _e('For example, this is where you the Shop Owner might enter your bank account details or address so that your customer can make their manual payment.', 'wpsc'); ?></small>
				</p>
			</td>
		</tr>
		<tr class='update_gateway' >
			<td colspan='2'>
				<div class='submit'>
					<input type='submit' value='<?php _e( 'Update &raquo;', 'wpsc' ); ?>' name='updateoption' />
				</div>
			</td>
		</tr>
		<?php foreach ( $checkout_field_types as $field_type => $title ): ?>
			<tr>
				<td colspan="2">
					<h4><?php echo esc_html( $title ); ?></h4>
				</td>
			</tr>
			<?php foreach ( $fields as $field_name => $field_title ):
				$unique_name = $field_type . $field_name;
				$selected_id = $this->setting->get( "checkout_field_{$unique_name}", $checkout_form->get_field_id_by_unique_name( $unique_name ) );
			?>
				<tr>
					<td>
						<label for="manual-form-<?php echo esc_attr( $unique_name ); ?>"><?php echo esc_html( $field_title ); ?></label>
					</td>
					<td>
						<select name="<?php echo $this->setting->get_field_name( "checkout_field_{$unique_name}" ); ?>" id="manual-form-<?php echo esc_attr( $unique_name ); ?>">
							<?php $checkout_form->field_drop_down_options( $selected_id ); ?>
						</select>
					</td>
				</tr>
			<?php endforeach ?>
		<?php endforeach ?>
		<?php
	}
	
	public function process() {
		
	}
}