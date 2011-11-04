<?php

class WPSC_Settings_Tab_Checkout extends WPSC_Settings_Tab
{
	public function __construct() {
		global $wpdb;

		$this->require_register = get_option( 'require_register', 0 );
		$this->shipping_same_as_billing = get_option( 'shippingsameasbilling', 0 );
		$this->force_ssl = get_option( 'wpsc_force_ssl', 0 );
		$this->checkout_sets = get_option( 'wpsc_checkout_form_sets' );
		$this->current_checkout_set = empty( $_GET['checkout-set'] ) ? 0 : $_GET['checkout-set'];

		$form_sql = $wpdb->prepare( "
			SELECT *
			FROM " . WPSC_TABLE_CHECKOUT_FORMS . "
			WHERE checkout_set = %s
			ORDER BY checkout_order
		", $this->current_checkout_set );
		$this->form_fields = $wpdb->get_results( $form_sql );

		$columns = array(
			'drag'        => __('Drag', 'wpsc'),
			'name'        => __('Title', 'wpsc'),
			'type'        => __('Type', 'wpsc'),
			'unique_name' => '&nbsp;',
			'display'     => __('Display', 'wpsc'),
			'mandatory'   => __('Mandatory', 'wpsc'),
			'trash'       => __('Trash', 'wpsc'),
		);
		register_column_headers('display-checkout-list', $columns);
	}

	public function display() {
		global $wpdb;

		//not to sure if we still need these any more - $form_types, $unique_names
		$form_types = get_option('wpsc_checkout_form_fields');
		$unique_names = get_option('wpsc_checkout_unique_names');

		do_action('wpsc_checkout_form_fields_page');
		?>

		<div class='metabox-holder' style='width:95%;'>
			<div class='postbox'>
				<input type='hidden' name='checkout_submits' value='true' />
				<h3 class='hndle'><?php _e( 'Misc Checkout Options' , 'wpsc' ); ?></h3>
				<div class='inside'>
					<table>
						<tr>
							<td><?php _e('Users must register before checking out', 'wpsc'); ?>:</td>
							<td>
							<input type='radio' value='1' name='wpsc_options[require_register]' id='require_register1' <?php checked( $this->require_register, 1 ); ?> />
							<label for='require_register1'><?php _e('Yes', 'wpsc');?></label> &nbsp;
							<input type='radio' value='0' name='wpsc_options[require_register]' id='require_register2' <?php checked( $this->require_register, 0 ); ?> />
							<label for='require_register2'><?php _e('No', 'wpsc');?></label>
							</td>
							<td>
							<a title='<?php _e('If yes then you must also turn on the wordpress option "Any one can register"', 'wpsc');?>' class='flag_email' href='#' ><img src='<?php echo WPSC_CORE_IMAGES_URL; ?>/help.png' alt='' /> </a>
							</td>
						</tr>

						<tr>
							<td scope="row"><?php _e('Enable Shipping Same as Billing Option', 'wpsc'); ?>:</td>
							<td>
								<input type='radio' value='1' name='wpsc_options[shippingsameasbilling]' id='shippingsameasbilling1' <?php checked( $this->shipping_same_as_billing, 1 ); ?> />
								<label for='shippingsameasbilling1'><?php _e('Yes', 'wpsc');?></label> &nbsp;
								<input type='radio' value='0' name='wpsc_options[shippingsameasbilling]' id='shippingsameasbilling2' <?php checked( $this->shipping_same_as_billing, 0 ); ?> />
								<label for='shippingsameasbilling2'><?php _e('No', 'wpsc');?></label>
							</td>
						</tr>
						<tr>
							<td><?php _e('Force users to use SSL', 'wpsc'); ?>:</td>
							<td>
								<input type='radio' value='1' name='wpsc_options[wpsc_force_ssl]' id='wpsc_force_ssl1' <?php checked( $this->force_ssl, 1 ); ?> />
								<label for='wpsc_force_ssl1'><?php _e('Yes', 'wpsc');?></label> &nbsp;
								<input type='radio' value='0' name='wpsc_options[wpsc_force_ssl]' id='wpsc_force_ssl2' <?php checked( $this->force_ssl, 0 ); ?> />
								<label for='wpsc_force_ssl2'><?php _e('No', 'wpsc');?></label>
							</td>
							<td>
								<a title='<?php _e('This can cause warnings for your users if you do not have a properly configured SSL certificate', 'wpsc');?>' class='flag_email' href='#' ><img src='<?php echo WPSC_CORE_IMAGES_URL; ?>/help.png' alt='' /> </a>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<h3><?php _e('Form Fields', 'wpsc'); ?></h3>
		<p><?php _e('Here you can customise the forms to be displayed in your checkout page. The checkout page is where you collect important user information that will show up in your purchase logs i.e. the buyers address, and name...', 'wpsc');?></p>

		<p>
			<label for='wpsc_form_set'><?php _e('Select a Form Set' , 'wpsc'); ?>:</label>
			<select id='wpsc_form_set' name='wpsc_form_set'>
				<?php foreach ( $this->checkout_sets as $key => $value ): ?>
					<option <?php selected( $this->current_checkout_set, $key ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
				<?php endforeach; ?>
			</select>
			<input type='submit' value='<?php esc_attr_e( 'Filter', 'wpsc' ); ?>' name='wpsc_checkout_set_filter' class='button-secondary' />
			<a href='#' class='add_new_form_set'><?php esc_html_e("+ Add New Form Set", 'wpsc'); ?></a>
		</p>

		<p class='add_new_form_set_forms'>
			<label><?php esc_html_e( "Add new Form Set", 'wpsc' ); ?>:
			<input type="text" value="" name="new_form_set" /></label>
			<input type="submit" value="<?php _e('Add', 'wpsc'); ?>" class="button-secondary" id="formset-add-sumbit"/>
		</p>

		<input type="hidden" name="selected_form_set" value="<?php echo esc_attr( $this->current_checkout_set ); ?>" />

		<table id="wpsc_checkout_list" class="widefat page fixed"  cellspacing="0">
			<thead>
				<tr>
					<?php print_column_headers( 'display-checkout-list' ); ?>
				</tr>
			</thead>

			<tfoot>
				<tr>
					<?php print_column_headers( 'display-checkout-list', false ); ?>
				</tr>
			</tfoot>

			<tbody id='wpsc_checkout_list_body'>
				<?php foreach ( $this->form_fields as $form_field ): ?>
					<tr id="checkout_<?php echo esc_attr( $form_field->id ); ?>" class="checkout_form_field">
						<td class="drag">
							<a title="<?php esc_attr_e( 'Click and Drag to Order Checkout Fields', 'wpsc' ); ?>">
								<img src="<?php echo esc_url( WPSC_CORE_IMAGES_URL . '/drag.png' ); ?>" />
							</a>
							<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-feedback" title="" alt="" />
						</td>
						<td class="namecol">
							<input type="text" name="form_name[<?php echo esc_attr( $form_field->id ); ?>]" value="<?php echo esc_attr( $form_field->name ); ?>" />
						</td>
						<td class="typecol"
							<?php
								if ( empty( $form_field->unique_name ) || $form_field->type == 'heading' )
									echo 'colspan="2"';
							 ?>
						>
							<strong><?php echo esc_html( $form_field->type ); ?></strong>
						</td>
						<?php if ( $form_field->type != 'heading' && ! empty( $form_field->unique_name ) ): ?>
							<td class="uniquenamecol">
								<small><?php echo esc_html( $form_field->unique_name ); ?></small>
							</td>
						<?php endif ?>
						<td class="displaycol">
							<input <?php checked( $form_field->active, 1 ); ?> type="checkbox" name="form_display[<?php echo esc_attr( $form_field->id ); ?>]" value="1" />
						</td>
						<td class="mandatorycol">
							<?php if ( $form_field->type != 'heading' ): ?>
								<input <?php checked( $form_field->mandatory, 1 ); ?> type="checkbox" name="form_mandatory[<?php echo esc_attr( $form_field->id ); ?>]" value="1" />
							<?php endif ?>
						</td>
						<td class="trashcol">
							<?php if ( ! $form_field->default ): ?>
								<a class="image_link" href="#">
									<img src="<?php echo esc_url( WPSC_CORE_IMAGES_URL . '/trash.gif' ); ?>" alt="<?php esc_attr_e( 'Delete', 'wpsc' ); ?>" title="<?php esc_attr_e( 'Delete', 'wpsc' ); ?>" />
								</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p><a href='#' onclick='return add_form_field();'><?php _e('Add New Form Field', 'wpsc');?></a></p>
				<?php
/*					echo "<select class='wpsc_checkout_selectboxes' name='form_type[".$form_field['id']."]'>";
					foreach($form_types as $form_type_name => $form_type) {
						$selected = '';
						if($form_type === $form_field['type']) {
							$selected = "selected='selected'";
						}
						echo "<option value='".$form_type."' ".$selected.">" . $form_type_name . "</option>";
					}

					echo "</select>";
					if(in_array($form_field['type'], array('select','radio','checkbox'))){
						echo "<a class='wpsc_edit_checkout_options' rel='form_options[".$form_field['id']."]' href=''>" . __('more options', 'wpsc') . "</a>";
					}
				} */
		  ?>
	<?php
	}
}