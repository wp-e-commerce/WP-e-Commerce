<?php

class WPSC_Settings_Tab_Checkout extends WPSC_Settings_Tab {

	private $require_register;
	private $shipping_same_as_billing;
	private $force_ssl;
	private $checkout_sets;
	private $current_checkout_set;
	private $field_types;
	private $user_field_types;
	private $form_fields;

	public function __construct() {
		global $wpdb;

		$this->require_register         = get_option( 'require_register', 0 );
		$this->shipping_same_as_billing = get_option( 'shippingsameasbilling', 0 );
		$this->force_ssl                = get_option( 'wpsc_force_ssl', 0 );
		$this->checkout_sets            = get_option( 'wpsc_checkout_form_sets' );
		$this->current_checkout_set     = empty( $_GET['checkout_set'] ) ? 0 : (int) $_GET['checkout_set'];
		$this->field_types              = get_option( 'wpsc_checkout_form_fields' );
		$this->user_field_types         = array('text','textarea','heading','select','radio','checkbox');

		if ( ! isset( $this->checkout_sets[$this->current_checkout_set] ) ) {
			wp_redirect( esc_url_raw( remove_query_arg( 'checkout_set' ) ) );
			exit;
		}

		$form_sql = $wpdb->prepare( "
			SELECT *
			FROM " . WPSC_TABLE_CHECKOUT_FORMS . "
			WHERE checkout_set = %s
			ORDER BY checkout_order
		", $this->current_checkout_set );

		$this->form_fields = $wpdb->get_results( $form_sql );

		$columns = array(
			'drag'        => __( 'Drag', 'wp-e-commerce' ),
			'name'        => __( 'Title', 'wp-e-commerce' ),
			'type'        => __( 'Type', 'wp-e-commerce' ),
			'unique_name' => '&nbsp;',
			'display'     => __( 'Display', 'wp-e-commerce' ),
			'mandatory'   => __( 'Mandatory', 'wp-e-commerce' ),
			'actions'     => '&nbsp;',
		);
		register_column_headers( 'display-checkout-list', $columns );
	}

	private function delete_form_set() {
		global $wpdb;

		$selected_checkout_set = $_REQUEST['checkout_set'];

		if ( empty( $selected_checkout_set ) )
			return;

		$form_sets = get_option( 'wpsc_checkout_form_sets' );
		unset( $form_sets[$selected_checkout_set] );
		update_option( 'wpsc_checkout_form_sets', $form_sets );

		$sql = $wpdb->prepare( 'DELETE FROM ' . WPSC_TABLE_CHECKOUT_FORMS . ' WHERE checkout_set = %s', $selected_checkout_set );
		$wpdb->query( $sql );
	}

	public function callback_submit_options() {
		global $wpdb;

		if ( ! empty( $_POST['wpsc_checkout_set_delete'] ) ) {
			$this->delete_form_set();
			return;
		}

		if ( ! empty( $_POST['new_form_set'] ) ) {
			$checkout_sets = get_option( 'wpsc_checkout_form_sets' );
			$checkout_sets[] = sanitize_text_field( $_POST['new_form_set'] );
			update_option( 'wpsc_checkout_form_sets', $checkout_sets );
			add_settings_error( 'wpsc-settings', 'wpsc_form_set_added', __( 'New form set successfully created.', 'wp-e-commerce' ), 'updated' );
		}

		if ( isset( $_POST['checkout_set'] ) ) {
			$_SERVER['REQUEST_URI'] = esc_url_raw( add_query_arg( 'checkout_set', $_POST['checkout_set'] ) );
		}

		if ( ! isset( $_POST['form_name'] ) && ! isset( $_POST['new_field_name'] ) )
			return;

		$existing_orders = array();
		$new_field_orders = array();
		if ( ! empty( $_POST['sort_order'] ) ) {
			foreach ( $_POST['sort_order'] as $order => $field_id ) {
				$id = absint( preg_replace('/[^0-9]+/', '', $field_id) );

				if ( strpos( $field_id, 'new-field' ) === 0 )
					$new_field_orders[$id] = $order;
				else
					$existing_orders[$id] = $order;
			}
		}

		$sql = $wpdb->prepare( "SELECT id FROM " . WPSC_TABLE_CHECKOUT_FORMS . " WHERE checkout_set = %s", $this->current_checkout_set );
		$ids = $wpdb->get_col( $sql );

		if ( ! empty( $_POST['form_name'] ) ) {
			foreach ( $_POST['form_name'] as $field_id => $name ) {
				$data = array(
					'name'      => $name,
					'active'    => empty( $_POST['form_display'][$field_id] ) ? 0 : 1,
					'mandatory' => empty( $_POST['form_mandatory'][$field_id] ) ? 0 : 1,
				);

				$data_format = array(
					'%s', // name
					'%s', // active
					'%s', // mandatory
				);

				$where = array( 'id' => $field_id );

				if ( isset( $_POST['form_type'][$field_id] ) ) {
					$data['type'] = $_POST['form_type'][$field_id];
					$data_format[] = '%s';
				}

				if ( isset( $existing_orders[$field_id] ) ) {
					$data['checkout_order'] = $existing_orders[$field_id];
					$data_format[] = '%d';
				}

				if ( isset( $_POST['form_options'][$field_id]['label'] ) )  {
					$options = array();
					foreach( $_POST['form_options'][$field_id]['label'] as $key => $label ) {
						$value = $_POST['form_options'][$field_id]['value'][$key];
						if ( $label === '' && $value === '')
							continue;
						$label           = sanitize_text_field( $label );
						$options[$label] = sanitize_text_field( $value );
					}
					$data['options'] = serialize( $options );
					$data_format[] = '%s';
				}

				$index = array_search( $field_id, $ids );
				if ( $index !== false ) {
					unset( $ids[$index] );
				}

				$wpdb->update( WPSC_TABLE_CHECKOUT_FORMS, $data, $where, $data_format, '%d' );
			}
		}

		// delete all other fields that are not present in the submitted form
		if ( ! empty( $ids ) ) {
			$sql = "DELETE FROM " . WPSC_TABLE_CHECKOUT_FORMS . " WHERE id IN (" . implode( ', ', $ids ) . ")";
			$wpdb->query( $sql );
		}

		foreach ( $_POST['new_field_name'] as $key => $name ) {
			if ( $key === 0 || empty( $name ) )
				continue;

			$data = array(
				'name'         => $name,
				'type'         => $_POST['new_field_type'][$key],
				'active'       => empty( $_POST['new_field_display'][$key] ) ? 0 : 1,
				'mandatory'    => empty( $_POST['new_field_mandatory'][$key] ) ? 0 : 1,
				'checkout_set' => $this->current_checkout_set,
				'unique_name'  => sanitize_title( $name ),
			);

			$data_format = array(
				'%s', // name
				'%s', // type
				'%s', // active
				'%s', // mandatory
				'%s', // checkout set
				'%s', // unique name
			);

			if ( isset( $new_field_orders[$key] ) ) {
				$data['checkout_order'] = $new_field_orders[$key];
				$data_format[] = '%d';
			}

			if ( isset( $_POST['new_field_options'][$key]['label'] ) )  {
				$options = array();
				foreach( $_POST['new_field_options'][$key]['label'] as $index => $label ) {
					$value = $_POST['new_field_options'][$key]['value'][$index];
					if ( $label === '' && $value === '')
						continue;
					$options[$label] = $value;
				}

				$data['options'] = serialize( $options );
				$data_format[] = '%s';
			}

			$wpdb->insert( WPSC_TABLE_CHECKOUT_FORMS, $data, $data_format );
		}
	}

	/**
	 * Determine whether this field is default or not.
	 *
	 * We do not let default fields to be deleted from 3.8.8. However, if the user upgrades from
	 * 3.7.x, the "default" column of the checkout form table does not correctly specify
	 * whether the fields are default or not.
	 *
	 * Also, if in any case the user has deleted a default field in versions older than 3.8.8,
	 * the field's "active" column will be set to 0. We should let users delete those fields as well.
	 *
	 * As a result, to determine whether a field is default or not, we have to rely on the field's
	 * unique name and "active" status.
	 *
	 * @param  object $field Field object
	 * @return boolean       True if the field is default.
	 */
	private function is_field_default( $field ) {
		global $wpdb;

		if ( $field->default == 1 ) {
			return true;
		}

		if ( empty( $field->unique_name) || $this->current_checkout_set !== 0 || empty( $field->active ) ) {
			return false;
		}

		$default_fields = array(
			'billingfirstname',
			'billinglastname',
			'billingaddress',
			'billingcity',
			'billingstate',
			'billingcountry',
			'billingpostcode',
			'billingemail',
			'billingphone',
			'shippingfirstname',
			'shippinglastname',
			'shippingaddress',
			'shippingcity',
			'shippingstate',
			'shippingcountry',
			'shippingpostcode',
			'shippingemail',
		);
		return in_array( $field->unique_name, $default_fields );
	}

	private function prototype_field( $mode = 'hidden' ) {
		$row_id = 'field-prototype';
		$row_class = 'new-field';
		$data = '';
		$new_field_id = 0;
		$style = '';

		if ( $mode == 'new' ) {
			$new_field_id = 1;
			$row_id = 'new-field-1';
			$row_class .= ' checkout_form_field';
			$data = 'data-new-field-id="1"';
			$style = 'style="display:table-row;"';
		}
		?>
		<tr id="<?php echo $row_id; ?>" class="<?php echo $row_class; ?>" <?php echo $data; ?> <?php echo $style; ?>>
			<td class="drag">
				<div class="cell-wrapper">
					<a title="<?php esc_attr_e( 'Click and Drag to Order Checkout Fields', 'wp-e-commerce' ); ?>">
						<img src="<?php echo esc_url( WPSC_CORE_IMAGES_URL . '/drag.png' ); ?>" />
					</a>
					<img src="<?php echo esc_url( wpsc_get_ajax_spinner() ); ?>" class="ajax-feedback" title="" alt="" />
				</div>
			</td>
			<td class="namecol">
				<div class="cell-wrapper">
					<input type="text" name="new_field_name[<?php echo $new_field_id; ?>]" value="" /><br />
				</div>
			</td>
			<td class="typecol">
				<div class="cell-wrapper">
					<select name="new_field_type[<?php echo $new_field_id; ?>]">
						<?php foreach ( $this->field_types as $name => $type ): ?>
							<?php if( in_array($type, $this->user_field_types) ): ?>
										<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $name ); ?></option>
							<?php endif ?>
						<?php endforeach ?>
					</select>
				</div>
			</td>
			<td class="uniquenamecol">
				<a class="edit-options" href="#"><?php esc_html_e( 'Edit Options', 'wp-e-commerce' ); ?></a>
			</td>
			<td class="displaycol">
				<div class="cell-wrapper">
					<input checked="checked" type="checkbox" name="new_field_display[<?php echo $new_field_id; ?>]" value="1" />
				</div>
			</td>
			<td class="mandatorycol">
				<div class="cell-wrapper">
					<input type="checkbox" name="new_field_mandatory[<?php echo $new_field_id; ?>]" value="1" />
				</div>
			</td>
			<td class="actionscol">
				<div class="cell-wrapper">
					<a tabindex="-1" title="<?php _e( 'Delete Field', 'wp-e-commerce' ); ?>" class="button-secondary wpsc-button-round wpsc-button-minus" href="#"><?php echo _x( '&ndash;', 'delete item', 'wp-e-commerce' ); ?></a>
					<a tabindex="-1" title="<?php _e( 'Add Field', 'wp-e-commerce' ); ?>" class="button-secondary wpsc-button-round wpsc-button-plus" href="#"><?php echo _x( '+', 'add item', 'wp-e-commerce' ); ?></a>
				</div>
			</td>
		</tr>
		<tr id="field-options-prototype" class="form-field-options">
			<td></td>
			<td>
				<div class="cell-wrapper">
					<h4></h4>
					<table class="wpsc-field-options-table">
						<thead>
							<th class="column-labels"><?php echo esc_html_x( 'Label', "checkout field's options", 'wp-e-commerce' ); ?></th>
							<th class="column-values"><?php echo esc_html_x( 'Value', "checkout field's options", 'wp-e-commerce' ); ?></th>
							<th class="column-actions">&nbsp;</th>
						</thead>
						<tbody>
							<tr class="new-option">
								<td class="column-labels">
									<div class="field-option-cell-wrapper">
										<input type="text" name="form_options[<?php echo $new_field_id; ?>][labels][]" value="" />
									</div>
								</td>
								<td class="column-values">
									<div class="field-option-cell-wrapper">
										<input type="text" name="form_options[<?php echo $new_field_id; ?>][values][]" value="" />
									</div>
								</td>
								<td class="column-actions">
									<div class="field-option-cell-wrapper">
										<a tabindex="-1" title="<?php _e( 'Delete Field', 'wp-e-commerce' ); ?>" class="button-secondary wpsc-button-round wpsc-button-minus" href="#"><?php echo _x( '&ndash;', 'delete item', 'wp-e-commerce' ); ?></a>
										<a tabindex="-1" title="<?php _e( 'Add Field', 'wp-e-commerce' ); ?>" class="button-secondary wpsc-button-round wpsc-button-plus" href="#"><?php echo _x( '+', 'add item', 'wp-e-commerce' ); ?></a>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</td>
			<td colspan="5"></td>
		</tr>
		<?php
	}

	public function display() {
		global $wpdb;

		//not to sure if we still need these any more - $form_types, $unique_names
		$form_types = get_option( 'wpsc_checkout_form_fields' );
		$unique_names = get_option( 'wpsc_checkout_unique_names' );

		do_action( 'wpsc_checkout_form_fields_page' );
		?>

		<input type='hidden' name='checkout_submits' value='true' />
		<h3><?php esc_html_e( 'Checkout Settings' , 'wp-e-commerce' ); ?></h3>
		<table class='form-table'>
			<tr>
				<th scope="row"><?php esc_html_e('Force User Registration', 'wp-e-commerce'); ?></th>
				<td>
					<input type='radio' value='0' name='wpsc_options[require_register]' id='require_register2' <?php checked( $this->require_register, 0 ); ?> />
					<label for='require_register2'><?php _e( 'Users can checkout without a user account', 'wp-e-commerce' );?></label><br />
					<input type='radio' value='1' name='wpsc_options[require_register]' id='require_register1' <?php checked( $this->require_register, 1 ); ?> />
					<label for='require_register1'><?php _e( 'Users must register before checking out', 'wp-e-commerce' );?></label>
					<p class='description'><?php esc_attr_e( 'Enabling this will also turn on "Membership: Anyone can register" from within WordPress Settings > General if it\'s not already enabled', 'wp-e-commerce' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e('Shipping Same as Billing', 'wp-e-commerce' ); ?></th>
				<td>
					<input type='radio' value='1' name='wpsc_options[shippingsameasbilling]' id='shippingsameasbilling1' <?php checked( $this->shipping_same_as_billing, 1 ); ?> />
					<label for='shippingsameasbilling1'><?php _e( 'Enable "Same as billing address" checkbox with Shipping Address fields', 'wp-e-commerce' );?></label><br />
					<input type='radio' value='0' name='wpsc_options[shippingsameasbilling]' id='shippingsameasbilling2' <?php checked( $this->shipping_same_as_billing, 0 ); ?> />
					<label for='shippingsameasbilling2'><?php _e( 'Users must re-enter Shipping Address', 'wp-e-commerce' );?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Security and Encryption', 'wp-e-commerce'); ?></th>
				<td>
					<input type='radio' value='1' name='wpsc_options[wpsc_force_ssl]' id='wpsc_force_ssl1' <?php checked( $this->force_ssl, 1 ); ?> />
					<label for='wpsc_force_ssl1'><?php _e( 'Force users to use SSL Encryption', 'wp-e-commerce' );?></label><br />
					<input type='radio' value='0' name='wpsc_options[wpsc_force_ssl]' id='wpsc_force_ssl2' <?php checked( $this->force_ssl, 0 ); ?> />
					<label for='wpsc_force_ssl2'><?php _e( 'Allow site to be used insecurely and unencrypted', 'wp-e-commerce' );?></label>
					<p class='description'><?php esc_html_e( 'This can cause warnings for your users if you do not have a properly configured SSL certificate', 'wp-e-commerce' );?></p>
				</td>
			</tr>
		</table>


		<h3><?php esc_html_e( 'Checkout Form Fields', 'wp-e-commerce' ); ?></h3>
		<p><?php esc_html_e( 'Here you can customise the forms to be displayed in your checkout page. The checkout page is where you collect important user information that will show up in your purchase logs i.e. the buyer\'s address, and name...', 'wp-e-commerce' );?></p>

		<p>
			<label for='wpsc_form_set'><?php esc_html_e( 'Select a Form Set' , 'wp-e-commerce' ); ?>:</label>
			<select id='wpsc_form_set' name='checkout_set'>
				<?php foreach ( $this->checkout_sets as $key => $value ): ?>
					<option <?php selected( $this->current_checkout_set, $key ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
				<?php endforeach; ?>
			</select>

			<input type='submit' value='<?php esc_attr_e( 'Filter', 'wp-e-commerce' ); ?>' name='wpsc_checkout_set_filter' class='button-secondary' />
			<input <?php if (! $this->current_checkout_set) echo 'style="display:none;"'; ?> id="wpsc-delete-checkout-set" type='submit' value='<?php esc_attr_e( 'Delete', 'wp-e-commerce' ); ?>' name='wpsc_checkout_set_delete' class='button-secondary' />
		</p>

		<p><a class="add_new_form_set button-secondary" href='#'><?php esc_html_e("Add New Form Set", 'wp-e-commerce'); ?></a></p>

		<p class='add_new_form_set_forms'>
			<label><?php esc_html_e( "Add new Form Set", 'wp-e-commerce' ); ?>:
			<input type="text" value="" name="new_form_set" /></label>
			<input type="submit" value="<?php esc_attr_e( 'Add', 'wp-e-commerce' ); ?>" class="button-primary" id="formset-add-sumbit"/>
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
				<?php if ( empty( $this->form_fields ) ): ?>
					<?php $this->prototype_field( 'new' ); ?>
				<?php else: ?>
					<?php foreach ( $this->form_fields as $form_field ): ?>

						<tr data-field-id="<?php echo esc_attr( $form_field->id ); ?>" data-field-type="<?php echo $form_field->type; ?>" id="checkout_<?php echo esc_attr( $form_field->id ); ?>" class="checkout_form_field field_type_<?php echo $form_field->type; ?>">
							<td class="drag">
								<div class="cell-wrapper">
									<a title="<?php esc_attr_e( 'Click and Drag to Order Checkout Fields', 'wp-e-commerce' ); ?>">
										<img src="<?php echo esc_url( WPSC_CORE_IMAGES_URL . '/drag.png' ); ?>" />
									</a>
									<img src="<?php echo esc_url( wpsc_get_ajax_spinner() ); ?>" class="ajax-feedback" title="" alt="" />
								</div>
							</td>
							<td class="namecol">
								<div class="cell-wrapper">
									<input type="text" name="form_name[<?php echo esc_attr( $form_field->id ); ?>]" value="<?php echo esc_attr( $form_field->name ); ?>" />
								</div>
							</td>
							<td class="typecol">
								<div class="cell-wrapper">
									<?php if ( $this->is_field_default( $form_field ) ): ?>
										<strong><?php echo esc_html( $form_field->type ); ?></strong>
									<?php else: ?>
										<select name="form_type[<?php echo esc_attr( $form_field->id ); ?>]">
											<?php foreach ($this->field_types as $label => $name): ?>
												<option <?php selected( $form_field->type, $name ); ?> value="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></option>
											<?php endforeach ?>
										</select>
										<?php
										$field_options = unserialize( $form_field->options );
										if ( empty( $field_options ) )
											$field_options = array();

										$i = 0;
										foreach ( $field_options as $label => $value ):
											$i ++;
											?>
											<input type="hidden" name="form_options[<?php echo esc_attr( $form_field->id ); ?>][label][]" value="<?php echo esc_attr( $label ); ?>" />
											<input type="hidden" name="form_options[<?php echo esc_attr( $form_field->id ); ?>][value][]" value="<?php echo esc_attr( $value ); ?>" />
										<?php endforeach; ?>
									<?php endif; ?>
								</div>
							</td>

							<td class="uniquenamecol">
								<div class="cell-wrapper">
									<?php if ( $form_field->type != 'heading' && ! empty( $form_field->unique_name ) ): ?>
										<small><?php echo esc_html( $form_field->unique_name ); ?></small>
									<?php endif ?>
									<?php
										$style = '';
										if ( in_array( $form_field->type, array( 'select', 'radio', 'checkbox' ) ) ) {
											$style = 'style="display: inline;"';
										}
									?>
									<a class="edit-options" href="#" <?php echo $style; ?> ><?php esc_html_e( 'Edit Options', 'wp-e-commerce' ); ?></a>
								</div>
							</td>
							<td class="displaycol">
								<div class="cell-wrapper">
									<input <?php checked( $form_field->active, 1 ); ?> type="checkbox" name="form_display[<?php echo esc_attr( $form_field->id ); ?>]" value="1" />
								</div>
							</td>
							<td class="mandatorycol">
								<div class="cell-wrapper">
									<?php if ( $form_field->type != 'heading' ): ?>
										<input <?php checked( $form_field->mandatory, 1 ); ?> type="checkbox" name="form_mandatory[<?php echo esc_attr( $form_field->id ); ?>]" value="1" />
									<?php endif ?>
								</div>
							</td>
							<td class="actionscol">
								<div class="cell-wrapper">
									<?php if ( ! $this->is_field_default( $form_field ) ): ?>
										<a tabindex="-1" title="<?php esc_attr_e( 'Delete Field', 'wp-e-commerce' ); ?>" class="button-secondary wpsc-button-round wpsc-button-minus" href="#"><?php echo _x( '&ndash;', 'delete item', 'wp-e-commerce' ); ?></a>
									<?php else: ?>
										<span title="<?php _e( 'Cannot Delete Default Fields', 'wp-e-commerce' ); ?>" class="button-secondary wpsc-button-round wpsc-button-minus"><?php echo _x( '&ndash;', 'delete item', 'wp-e-commerce' ); ?></span>
									<?php endif; ?>
									<a tabindex="-1" title="<?php _e( 'Add Field', 'wp-e-commerce' ); ?>" class="button-secondary wpsc-button-round wpsc-button-plus" href="#"><?php echo _x( '+', 'add item', 'wp-e-commerce' ); ?></a>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				<?php $this->prototype_field(); ?>
			</tbody>
		</table>
	<?php
	}
}
