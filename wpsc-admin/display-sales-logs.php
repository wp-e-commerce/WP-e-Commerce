<?php
/**
 * WP eCommerce edit and view sales page functions
 *
 * These are the main WPSC sales page functions
 *
 * @package wp-e-commerce
 * @since 3.8.8
 */

class WPSC_Purchase_Log_Page {

	private $list_table;
	private $output;
	private $cols  = 0;
	public $log_id = 0;

	/**
	 * WPSC_Purchase_Log
	 *
	 * @var WPSC_Purchase_Log object.
	 */
	public $log = null;

	/**
	 * Whether the purchase log can be modified.
	 *
	 * @var boolean
	 */
	protected $can_edit = false;

	public function __construct() {
		$controller        = 'default';
		$controller_method = 'controller_default';

		// If individual purchase log, setup ID and action links.
		if ( isset( $_REQUEST['id'] ) && is_numeric( $_REQUEST['id'] ) ) {
			$this->log_id   = (int) $_REQUEST['id'];
			$this->log      = wpsc_get_order( $this->log_id );
			$this->notes    = wpsc_get_order_notes( $this->log );
			$this->can_edit = $this->log->can_edit();
		}

		if ( isset( $_REQUEST['c'] ) && method_exists( $this, 'controller_' . $_REQUEST['c'] ) ) {
			$controller        = $_REQUEST['c'];
			$controller_method = 'controller_' . $controller;
		} elseif ( isset( $_REQUEST['id'] ) && is_numeric( $_REQUEST['id'] ) ) {
			$controller        = 'item_details';
			$controller_method = 'controller_item_details';
		}

		// Can only edit in the item details view.
		if ( 'controller_item_details' !== $controller_method ) {
			$this->can_edit = false;
		}

		$this->$controller_method();
	}

	private function needs_update() {
		global $wpdb;

		if ( get_option( '_wpsc_purchlogs_3.8_updated' ) ) {
			return false;
		}

		$c = $wpdb->get_var( "SELECT COUNT(*) FROM " . WPSC_TABLE_PURCHASE_LOGS . " WHERE plugin_version IN ('3.6', '3.7')" );

		if ( $c > 0 ) {
			return true;
		}

		update_option( '_wpsc_purchlogs_3.8_updated', true );
		return false;
	}

	public function controller_upgrade_purchase_logs_3_7() {
		add_action( 'wpsc_display_purchase_logs_page', array( $this, 'display_upgrade_purchase_logs_3_7' ) );
	}

	private function purchase_logs_fix_options( $id ) {
		?>
		<select name='<?php echo $id; ?>'>
			<option value='-1'><?php echo esc_html_x( 'Select an Option', 'Dropdown default when called in uniquename dropdown', 'wp-e-commerce' ); ?></option>
			<option value='billingfirstname'><?php esc_html_e( 'Billing First Name', 'wp-e-commerce' ); ?></option>
			<option value='billinglastname'><?php esc_html_e( 'Billing Last Name', 'wp-e-commerce' ); ?></option>
			<option value='billingaddress'><?php esc_html_e( 'Billing Address', 'wp-e-commerce' ); ?></option>
			<option value='billingcity'><?php esc_html_e( 'Billing City', 'wp-e-commerce' ); ?></option>
			<option value='billingstate'><?php esc_html_e( 'Billing State', 'wp-e-commerce' ); ?></option>
			<option value='billingcountry'><?php esc_html_e( 'Billing Country', 'wp-e-commerce' ); ?></option>
			<option value='billingemail'><?php esc_html_e( 'Billing Email', 'wp-e-commerce' ); ?></option>
			<option value='billingphone'><?php esc_html_e( 'Billing Phone', 'wp-e-commerce' ); ?></option>
			<option value='billingpostcode'><?php esc_html_e( 'Billing Post Code', 'wp-e-commerce' ); ?></option>
			<option value='shippingfirstname'><?php esc_html_e( 'Shipping First Name', 'wp-e-commerce' ); ?></option>
			<option value='shippinglastname'><?php esc_html_e( 'Shipping Last Name', 'wp-e-commerce' ); ?></option>
			<option value='shippingaddress'><?php esc_html_e( 'Shipping Address', 'wp-e-commerce' ); ?></option>
			<option value='shippingcity'><?php esc_html_e( 'Shipping City', 'wp-e-commerce' ); ?></option>
			<option value='shippingstate'><?php esc_html_e( 'Shipping State', 'wp-e-commerce' ); ?></option>
			<option value='shippingcountry'><?php esc_html_e( 'Shipping Country', 'wp-e-commerce' ); ?></option>
			<option value='shippingpostcode'><?php esc_html_e( 'Shipping Post Code', 'wp-e-commerce' ); ?></option>
		</select>
		<?php
	}

	public function display_upgrade_purchase_logs_3_7() {
		global $wpdb;
		$numChanged = 0;
		$numQueries = 0;
		$purchlog =  "SELECT DISTINCT id FROM `".WPSC_TABLE_PURCHASE_LOGS."` LIMIT 1";
		$id = $wpdb->get_var($purchlog);
		$usersql = "SELECT DISTINCT `".WPSC_TABLE_SUBMITTED_FORM_DATA."`.value, `".WPSC_TABLE_CHECKOUT_FORMS."`.* FROM `".WPSC_TABLE_CHECKOUT_FORMS."` LEFT JOIN `".WPSC_TABLE_SUBMITTED_FORM_DATA."` ON `".WPSC_TABLE_CHECKOUT_FORMS."`.id = `".WPSC_TABLE_SUBMITTED_FORM_DATA."`.`form_id` WHERE `".WPSC_TABLE_SUBMITTED_FORM_DATA."`.log_id=".$id." ORDER BY `".WPSC_TABLE_CHECKOUT_FORMS."`.`checkout_order`" ;
		$formfields = $wpdb->get_results($usersql);

		if(count($formfields) < 1){
			$usersql = "SELECT DISTINCT  `".WPSC_TABLE_CHECKOUT_FORMS."`.* FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `type` != 'heading'";
			$formfields = $wpdb->get_results($usersql);
		}

		if(isset($_POST)){
			foreach($_POST as $key=>$value){
				if($value != '-1'){
					$complete = $wpdb->update(
				 WPSC_TABLE_CHECKOUT_FORMS,
				 array(
				'unique_name' => $value
				 ),
				 array(
				'id' => $key
				  ),
				 '%s',
				 '%d'
				 );
				}
				$numChanged++;
				$numQueries++;
			}

			$sql = "UPDATE `".WPSC_TABLE_CHECKOUT_FORMS."` SET `unique_name`='delivertoafriend' WHERE `name` = '2. Shipping details'";
			$wpdb->query($sql);

			add_option('wpsc_purchaselogs_fixed',true);
		}

		include( 'includes/purchase-logs-page/upgrade.php' );
	}

	public function display_upgrade_purchase_logs_3_8() {
		?>
			<div class="wrap">
				<h2><?php echo esc_html( __('Sales', 'wp-e-commerce') ); ?> </h2>
				<div class="updated">
					<p><?php printf( __( 'Your purchase logs have been updated! <a href="%s">Click here</a> to return.' , 'wp-e-commerce' ), esc_url( remove_query_arg( 'c' ) ) ); ?></p>
				</div>
			</div>
		<?php
	}

	public function controller_upgrade_purchase_logs_3_8() {
		if ( $this->needs_update() ) {
			wpsc_update_purchase_logs();
		}

		add_action( 'wpsc_display_purchase_logs_page', array( $this, 'display_upgrade_purchase_logs_3_8' ) );
	}

	function purchase_logs_pagination() {

 		$prev_id = $this->log->get_previous_log_id();
		$next_id = $this->log->get_next_log_id();
		?>
		<span class='tablenav'><span class='tablenav-logs'><span class='pagination-links'>
			<?php if ( $prev_id ) : ?>
				<a href='<?php echo esc_url( $this->get_purchase_log_url( $prev_id ) ); ?>' class='prev-page'>&lsaquo; <?php _e( 'Previous', 'wp-e-commerce' ); ?></a>
			<?php endif; ?>

			<?php if ( $next_id ) : ?>
				<a href='<?php echo esc_url( $this->get_purchase_log_url( $next_id ) ); ?>' class='next-page'><?php _e( 'Next', 'wp-e-commerce' ); ?> &rsaquo;</a>
			<?php endif; ?>
		</span></span></span>
		<?php
	}

	public function purchase_logs_checkout_fields() {
		global $purchlogitem;

		foreach( (array) $purchlogitem->additional_fields as $value ) {
			$value['value'] = maybe_unserialize( $value['value'] );
			if ( is_array( $value['value'] ) ) {
				?>
					<p><strong><?php echo $value['name']; ?> :</strong> <?php echo implode( stripslashes( $value['value'] ), ',' ); ?></p>
				<?php
			} else {
				$thevalue = esc_html( stripslashes( $value['value'] ));
				if ( empty( $thevalue ) ) {
					$thevalue = __( '<em>blank</em>', 'wp-e-commerce' );
				}
				?>
					<p><strong><?php echo $value['name']; ?> :</strong> <?php echo $thevalue; ?></p>
				<?php
			}
		}
	}

	public function purchase_log_custom_fields() {
		$messages = wpsc_purchlogs_custommessages();
		$files    = wpsc_purchlogs_customfiles();

		if ( count( $files ) > 0 ) { ?>
			<h4><?php esc_html_e( 'Cart Items with Custom Files' , 'wp-e-commerce' ); ?>:</h4>
			<?php
			foreach( $files as $file ) {
				echo $file;
			}
		}
		if ( count( $messages ) > 0 ) { ?>
			<h4><?php esc_html_e( 'Cart Items with Custom Messages' , 'wp-e-commerce' ); ?>:</h4>
			<?php
			foreach( $messages as $message ) {
				echo esc_html( $message['title'] ) . ':<br />' . nl2br( esc_html( $message['message'] ) );
			}
		}
	}

	public function items_ordered_box() {
		?>
		<?php do_action( 'wpsc_purchlogitem_metabox_start', $this->log_id ); ?>

		<form name="wpsc_items_ordered_form" method="post">
			<table class="widefat" cellspacing="0">
				<thead>
					<tr>
						<?php print_column_headers( 'wpsc_purchase_log_item_details' ); ?>
					</tr>
				</thead>

				<tbody>
					<?php $this->purchase_log_cart_items(); ?>

					<?php if ( $this->can_edit ) : ?>
						<tr class="wpsc_purchaselog_add_product">
							<td colspan="<?php echo $this->cols + 2; ?>">
								<p class="wpsc-add-row">
									<button type="button" class="wpsc-add-item-button button"><?php esc_html_e( 'Add Item', 'wp-e-commerce' ); ?></button>
								</p>
							</td>
						</tr>
					<?php endif; ?>

					<tr class="wpsc_purchaselog_start_totals" id="wpsc_discount_data">
						<td colspan="<?php echo $this->cols; ?>">
							<?php if ( wpsc_purchlog_has_discount_data() ): ?>
								<?php esc_html_e( 'Coupon Code', 'wp-e-commerce' ); ?>: <?php echo wpsc_display_purchlog_discount_data(); ?>
							<?php endif; ?>
						</td>
						<th class='right-col'><?php esc_html_e( 'Discount', 'wp-e-commerce' ); ?> </th>
						<td><?php echo wpsc_display_purchlog_discount(); ?></td>
					</tr>

					<?php if ( ! wpec_display_product_tax() ): ?>
						<tr id="wpsc_total_taxes">
							<td colspan='<?php echo $this->cols; ?>'></td>
							<th class='right-col'><?php esc_html_e( 'Taxes', 'wp-e-commerce' ); ?> </th>
							<td><?php echo wpsc_display_purchlog_taxes(); ?></td>
						</tr>
					<?php endif; ?>

					<tr id="wpsc_total_shipping">
						<td colspan='<?php echo $this->cols; ?>'></td>
						<th class='right-col'><?php esc_html_e( 'Shipping', 'wp-e-commerce' ); ?> </th>
						<td><?php echo wpsc_display_purchlog_shipping( false, true ); ?></td>
					</tr>
					<tr id="wpsc_final_total">
						<td colspan='<?php echo $this->cols; ?>'></td>
						<th class='right-col'><?php esc_html_e( 'Total', 'wp-e-commerce' ); ?> </th>
						<td><span><?php echo wpsc_display_purchlog_totalprice(); ?></span> <div class="spinner"></div></td>
					</tr>
					<tr class="wpsc-row-actions">
						<td class="wpsc-add-row" colspan="<?php echo $this->cols + 2; ?>">
							<?php do_action( 'wpsc_order_row_actions', $this->log ); ?>
						</td>
					</tr>
					<tr class="wpsc-row-action-views">
						<td colspan="<?php echo $this->cols + 2; ?>">
							<?php do_action( 'wpsc_order_row_actions_views', $this->log ); ?>
						</td>
					</tr>
				</tbody>
			</table>
		</form>

		<?php do_action( 'wpsc_purchlogitem_metabox_end', $this->log_id ); ?>

		<?php
	}

	public function add_refund_button( $log ) {
		if ( wpsc_payment_gateway_supports( $log->get( 'gateway' ), 'refunds' ) && $log->get_remaining_refund() != 0 ) :
		?>
		<button type="button" class="button refund-items"><?php _e( 'Refund', 'wp-e-commerce' ); ?></button>
		<?php
		endif;
	}

	public function add_refund_button_ui( $log ) {
		if ( wpsc_payment_gateway_supports( $log->get( 'gateway' ), 'refunds' ) ) :
	?>
		<table class='wpsc-refund-ui'>
			<tbody>
				<tr>
					<td class="label"><?php _e( 'Amount already refunded', 'wp-e-commerce' ); ?>:</td>
					<td class="total"><?php echo wpsc_currency_display( $log->get_total_refunded() );?></td>
				</tr>
				<?php if ( wpsc_payment_gateway_supports( $log->get( 'gateway' ), 'partial-refunds' ) ) : ?>
				<tr>
					<td class="label"><label for="refund_amount"><?php _e( 'Refund amount', 'wp-e-commerce' ); ?>:</label></td>
					<td class="total">
						<input type="number" max="<?php echo floatval( $log->get_remaining_refund() ); ?>" class="text" id="refund_amount" name="refund_amount" class="wpec_input_price" />
						<div class="clear"></div>
					</td>
				</tr>
				<?php endif; // gateway supports PARTIAL refunds ?>
				<tr>
					<td class="label"><label for="refund_reason"><?php _e( 'Reason for refund (optional)', 'wp-e-commerce' ); ?>:</label></td>
					<td class="total">
						<input type="text" class="text" id="refund_reason" name="refund_reason" />
						<div class="clear"></div>
					</td>
				</tr>
				<tr>
					<td>
						<p>
							<button type="button" class="button tips button-primary do-api-refund"><?php printf( __( 'Refund via %s', 'wp-e-commerce' ), wpsc_get_payment_gateway( $log->get( 'gateway' ) )->get_title() ); ?></button>
							<button type="button" class="button button-secondary do-manual-refund tips"><?php _e( 'Manual Refund', 'wp-e-commerce' ); ?></button>
						</p>
					</td>
					<td>
						<span class="spinner"></span>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
		endif;
	}

	public function add_capture_button( $log ) {
		if ( wpsc_payment_gateway_supports( $log->get( 'gateway' ), 'auth-capture' ) && $log->is_order_received() ) :
		?>
		<button type="button" class="button-primary button capture-payment"><?php _e( 'Capture Payment', 'wp-e-commerce' ); ?></button>
		<div class="spinner"></div>
		<?php
		endif;
	}

	public function purch_notes_box() {
		?>
		<div class="wpsc-notes">
			<?php $this->notes_output(); ?>
		</div>
		<form method="post" action="" id="note-submit-form">
			<?php wp_nonce_field( 'wpsc_log_add_notes_nonce', 'wpsc_log_add_notes_nonce' ); ?>
			<input type='hidden' name='purchlog_id' value='<?php echo $this->log_id; ?>' />
			<p>
			<?php wp_editor( '', 'purchlog_notes', array(
				'textarea_name' => 'purchlog_notes',
				'textarea_rows' => 3,
				'teeny'         => true,
				'tinymce'       => false,
				'media_buttons' => false,
			) ); ?>
			</p>
			<div class="note-submit">
				<input class="button" type="submit" value="<?php _e( 'Add Note', 'wp-e-commerce' ); ?>" />
				<div class="spinner"></div>
			</div>
		</form>
		<?php
	}

	private function edit_contact_details_form() {
		$args = wpsc_get_customer_settings_form_args( $this->log->form_data() );
		$args['form_actions'][0]['class'] = 'button';
		$args['form_actions'][0]['title'] = __( 'Update', 'wp-e-commerce' );
		echo wpsc_get_form_output( $args );
	}

	private function purchase_log_cart_items() {
		while( wpsc_have_purchaselog_details() ) : wpsc_the_purchaselog_item();
			self::purchase_log_cart_item( $this->can_edit );
		endwhile;
	}

	public static function purchase_log_cart_item( $can_edit = false ) {
		?>
		<tr class="purchase-log-line-item" id="purchase-log-item-<?php echo wpsc_purchaselog_details_id(); ?>" data-id="<?php echo wpsc_purchaselog_details_id(); ?>" data-productid="<?php echo wpsc_purchaselog_product_id(); ?>">
			<td><?php echo wpsc_purchaselog_details_name(); ?></td> <!-- NAME! -->
			<td><?php echo wpsc_purchaselog_details_SKU(); ?></td> <!-- SKU! -->
			<td>
				<?php if ( $can_edit ) : ?>
					<input type="number" step="1" min="0" autocomplete="off" name="wpsc_item_qty" class="wpsc_item_qty" placeholder="0" value="<?php echo wpsc_purchaselog_details_quantity(); ?>" size="4" class="quantity">
				<?php else: ?>
					<?php echo wpsc_purchaselog_details_quantity(); ?>
				<?php endif; ?>
			</td> <!-- QUANTITY! -->
			<td>
		 <?php
		echo wpsc_currency_display( wpsc_purchaselog_details_price() );
		do_action( 'wpsc_additional_sales_amount_info', wpsc_purchaselog_details_id() );
		 ?>
	 </td> <!-- PRICE! -->
			<td><?php echo wpsc_currency_display( wpsc_purchaselog_details_shipping() ); ?></td> <!-- SHIPPING! -->
			<?php if( wpec_display_product_tax() ): ?>
				<td><?php echo wpsc_currency_display( wpsc_purchaselog_details_tax() ); ?></td> <!-- TAX! -->
			<?php endif; ?>
			<!-- <td><?php echo wpsc_currency_display( wpsc_purchaselog_details_discount() ); ?></td> --> <!-- DISCOUNT! -->
			<td class="amount"><?php echo wpsc_currency_display( wpsc_purchaselog_details_total() ); ?></td> <!-- TOTAL! -->
			<?php if ( $can_edit ) : ?>
				<td class="remove">
					<div class="wpsc-remove-row">
						<button type="button" class="wpsc-remove-button wpsc-remove-item-button"><span class="dashicons dashicons-dismiss"></span> <?php esc_html_e( 'Remove Item', 'wp-e-commerce' ); ?></button>
					</div>
				</td> <!-- REMOVE! -->
			<?php endif; ?>
		</tr>
		<?php
		do_action( 'wpsc_additional_sales_item_info', wpsc_purchaselog_details_id() );
	}

	public function notes_output() {

		foreach ( $this->notes as $note_id => $note_args ) : ?>
			<?php self::note_output( $this->notes, $note_id, $note_args ); ?>
		<?php endforeach;
	}

	public static function note_output( WPSC_Purchase_Log_Notes $notes, $note_id, array $note_args ) {
		?>
		<div class="wpsc-note" id="wpsc-note-<?php echo absint( $note_id ); ?>" data-id="<?php echo absint( $note_id ); ?>">
			<p>
				<strong class="note-date"><?php echo $notes->get_formatted_date( $note_args ); ?></strong>
				<a href="#wpsc-note-<?php echo absint( $note_id ); ?>" class="note-number">#<?php echo ( $note_id ); ?></a>
				<a href="<?php echo wp_nonce_url( add_query_arg( 'note', absint( $note_id ) ), 'delete-note', 'delete-note' ); ?>" class="wpsc-remove-button wpsc-remove-note-button"><span class="dashicons dashicons-dismiss"></span> <?php esc_html_e( 'Delete Note', 'wp-e-commerce' ); ?></a>
			</p>
			<div class="wpsc-note-content">
				<?php echo wpautop( $note_args['content'] ); ?>
			</div>
		</div>
		<?php
	}

	public static function shipping_address_output() {
		?>
		<strong>
			<?php echo ( wpsc_display_purchlog_shipping_name() != ""           ) ? wpsc_display_purchlog_shipping_name() . "<br />"               : '<span class="field-blank">' . __( 'Anonymous', 'wp-e-commerce' ) . '</span>' ; ?>
		</strong>
		<?php echo ( wpsc_display_purchlog_shipping_address() != ""            ) ? wpsc_display_purchlog_shipping_address() . "<br />"            : '' ; ?>
		<?php echo ( wpsc_display_purchlog_shipping_city() != ""               ) ? wpsc_display_purchlog_shipping_city() . ", "               : '' ; ?>
		<?php echo ( wpsc_display_purchlog_shipping_state_and_postcode() != "" ) ? wpsc_display_purchlog_shipping_state_and_postcode() . "<br />" : '' ; ?>
		<?php echo ( wpsc_display_purchlog_shipping_country() != ""            ) ? wpsc_display_purchlog_shipping_country() . "<br />"            : '<span class="field-blank">' . __( 'Country not specified', 'wp-e-commerce' ) . '</span>' ; ?>
		<?php
	}

	public static function billing_address_output() {
		?>
		<strong>
			<?php echo ( wpsc_display_purchlog_buyers_name() != ""           ) ? wpsc_display_purchlog_buyers_name() . "<br />"               : '<span class="field-blank">' . __( 'Anonymous', 'wp-e-commerce' ) . '</span>' ; ?>
		</strong>
		<?php echo ( wpsc_display_purchlog_buyers_address() != ""            ) ? wpsc_display_purchlog_buyers_address() . "<br />"            : '' ; ?>
		<?php echo ( wpsc_display_purchlog_buyers_city() != ""               ) ? wpsc_display_purchlog_buyers_city() . ", "               : '' ; ?>
		<?php echo ( wpsc_display_purchlog_buyers_state_and_postcode() != "" ) ? wpsc_display_purchlog_buyers_state_and_postcode() . "<br />" : '' ; ?>
		<?php echo ( wpsc_display_purchlog_buyers_country() != ""            ) ? wpsc_display_purchlog_buyers_country() . "<br />"            : '<span class="field-blank">' . __( 'Country not specified', 'wp-e-commerce' ) . '</span>' ; ?>
		<?php
	}

	public static function payment_details_output() {
		?>
		<strong><?php esc_html_e( 'Phone:', 'wp-e-commerce' ); ?> </strong><?php echo ( wpsc_display_purchlog_buyers_phone() != "" ) ? wpsc_display_purchlog_buyers_phone() : __( '<em class="field-blank">not provided</em>', 'wp-e-commerce' ); ?><br />
		<strong><?php esc_html_e( 'Email:', 'wp-e-commerce' ); ?> </strong>
			<a href="mailto:<?php echo wpsc_display_purchlog_buyers_email(); ?>?subject=<?php echo rawurlencode( sprintf( __( 'Message from %s', 'wp-e-commerce' ), site_url() ) ); ?>">
				<?php echo ( wpsc_display_purchlog_buyers_email() != "" ) ? wpsc_display_purchlog_buyers_email() : __( '<em class="field-blank">not provided</em>', 'wp-e-commerce' ); ?>
			</a>
		<br />
		<strong><?php esc_html_e( 'Payment Method:', 'wp-e-commerce' ); ?> </strong><?php echo wpsc_display_purchlog_paymentmethod(); ?><br />
		<?php if ( wpsc_display_purchlog_display_howtheyfoundus() ) : ?>
			<strong><?php esc_html_e( 'How User Found Us:', 'wp-e-commerce' ); ?> </strong><?php echo wpsc_display_purchlog_howtheyfoundus(); ?><br />
		<?php endif; ?>
		<?php
	}

	public function controller_item_details() {
		if (
			! isset( $_REQUEST['id'] )
			|| ( isset( $_REQUEST['id'] ) && ! is_numeric( $_REQUEST['id'] ) )
			|| ! $this->log->exists()
		) {
			wp_die( __( 'Invalid sales log ID', 'wp-e-commerce'  ) );
		}

		if ( isset( $_POST['wpsc_checkout_details'], $_POST['_wp_nonce'] ) ) {
			self::maybe_update_contact_details_for_log( $this->log, wp_unslash( $_POST['wpsc_checkout_details'] ) );
		}

		if ( isset( $_POST['wpsc_log_add_notes_nonce'], $_POST['purchlog_notes'] ) ) {
			self::maybe_add_note_to_log( $this->log, wp_unslash( $_POST['purchlog_notes'] ) );
		}

		if ( isset( $_REQUEST['delete-note'], $_REQUEST['note'] ) ) {
			self::maybe_delete_note_from_log( $this->log, absint( $_REQUEST['note'] ) );
		}

		$this->log->init_items();

		$columns = array(
			'title'    => __( 'Name', 'wp-e-commerce' ),
			'sku'      => __( 'SKU', 'wp-e-commerce' ),
			'quantity' => __( 'Quantity','wp-e-commerce' ),
			'price'    => __( 'Price', 'wp-e-commerce' ),
			'shipping' => __( 'Item Shipping', 'wp-e-commerce'),
		);

		if ( wpec_display_product_tax() ) {
			$columns['tax'] = __( 'Item Tax', 'wp-e-commerce' );
		}

		$columns['total'] = __( 'Item Total','wp-e-commerce' );

		if ( $this->can_edit ) {
			$columns['remove'] = '';

			$this->include_te_v2_resources();
			$this->enqueue_te_v2_resources();
		}

		add_filter( 'admin_title', array( $this, 'doc_title' ), 10, 2 );

		register_column_headers( 'wpsc_purchase_log_item_details', $columns );

		add_action( 'wpsc_display_purchase_logs_page', array( $this, 'display_purchase_log' ) );
		add_action( 'wpsc_purchlog_before_metaboxes' , array( $this, 'register_metaboxes' ) );

		add_action( 'wpsc_order_row_actions'         , array( $this, 'add_refund_button' ) );
		add_action( 'wpsc_order_row_actions_views'   , array( $this, 'add_refund_button_ui' ) );

		add_action( 'wpsc_order_row_actions'      , array( $this, 'add_capture_button' ) );
	}

	public function register_metaboxes() {
		global $purchlogitem;

		add_meta_box( 'wpsc_items_ordered', esc_html__( 'Items Ordered' , 'wp-e-commerce' ), array( $this, 'items_ordered_box' ), get_current_screen()->id, 'normal' );

		add_meta_box( 'purchlogs_notes', esc_html__( 'Order Notes' , 'wp-e-commerce' ), array( $this, 'purch_notes_box' ), get_current_screen()->id, 'low' );

		if ( wpsc_purchlogs_has_customfields() ) {
			add_meta_box( 'purchlogs_customfields', esc_html__( 'Users Custom Fields' , 'wp-e-commerce' ), array( $this, 'purchase_log_custom_fields' ), get_current_screen()->id, 'normal' );
		}

		if ( ! empty( $purchlogitem->additional_fields ) ) {
			add_meta_box( 'custom_checkout_fields', esc_html__( 'Additional Checkout Fields' , 'wp-e-commerce' ), array( $this, 'purchase_logs_checkout_fields' ), get_current_screen()->id, 'normal' );
		}

		do_action( 'wpsc_purchase_logs_register_metaboxes', get_current_screen(), $this );
	}

	public static function maybe_update_contact_details_for_log( WPSC_Purchase_Log $log, $details ) {
		if ( is_array( $details ) ) {

			check_admin_referer( 'wpsc-customer-settings-form', '_wp_nonce' );

			return WPSC_Checkout_Form_Data::save_form(
				$log,
				WPSC_Checkout_Form::get()->get_fields(),
				array_map( 'sanitize_text_field', $details ),
				false
			);
		}
	}

	/**
	 * Update Purchase Log Notes
	 *
	 * @param  WPSC_Purchase_Log  $log log object.
	 */
	public static function maybe_add_note_to_log( WPSC_Purchase_Log $log, $note ) {
		if ( $note ) {
			check_admin_referer( 'wpsc_log_add_notes_nonce', 'wpsc_log_add_notes_nonce' );

			$log->add_note( wp_kses_post( $note ) );

			wp_safe_redirect( esc_url_raw( remove_query_arg( 'wpsc_log_add_notes_nonce' ) ) );
			exit;
		}
	}

	public static function maybe_delete_note_from_log( WPSC_Purchase_Log $log, $note_id ) {
		if ( is_numeric( $note_id ) ) {
			check_admin_referer( 'delete-note', 'delete-note' );

			$notes = wpsc_get_order_notes( $log );

			$notes->remove( $note_id )->save();

			wp_safe_redirect( esc_url_raw( remove_query_arg( 'delete-note', remove_query_arg( 'note' ) ) ) . '#purchlogs_notes' );
			exit;
		}
	}

	/**
	 * Include files/resources from TEV2.
	 *
	 * @since  4.0.0
	 *
	 * @return void
	 */
	public function include_te_v2_resources() {
		if ( ! defined( 'WPSC_TE_V2_CLASSES_PATH' ) ) {
			require_once WPSC_FILE_PATH . '/wpsc-components/theme-engine-v2/core.php';
			_wpsc_te_v2_includes();
		}

		require_once( WPSC_TE_V2_CLASSES_PATH . '/message-collection.php' );
		require_once( WPSC_TE_V2_HELPERS_PATH . '/message-collection.php' );
		require_once( WPSC_TE_V2_HELPERS_PATH . '/template-tags/form.php' );
	}

	/**
	 * Enqueue resources from tev2.
	 *
	 * @since  4.0.0
	 *
	 * @return void
	 */
	public function enqueue_te_v2_resources() {
		_wpsc_te2_register_styles();
		wp_enqueue_style( 'wpsc-common' );
		wpsc_enqueue_script( 'wpsc-select-autocomplete' );
		wpsc_enqueue_script( 'wpsc-country-region' );
		wpsc_enqueue_script( 'wpsc-copy-billing-info' );
	}

	public function doc_title( $admin_title, $title ) {
		/* translators: #%d represents the sales log id. */
		$this_title = sprintf( esc_html__( 'Sales Log #%d', 'wp-e-commerce' ), $this->log_id );
		$admin_title = str_replace( $title, $this_title, $admin_title );

		return $admin_title;
	}

	public function controller_packing_slip() {
		if ( ! isset( $_REQUEST['id'] ) || ( isset( $_REQUEST['id'] ) && ! is_numeric( $_REQUEST['id'] ) ) ) {
			wp_die( __( 'Invalid sales log ID', 'wp-e-commerce'  ) );
		}

		$this->log->init_items();

		$columns = array(
			'title'    => __( 'Item Name', 'wp-e-commerce' ),
			'sku'      => __( 'SKU', 'wp-e-commerce' ),
			'quantity' => __( 'Quantity', 'wp-e-commerce' ),
			'price'    => __( 'Price', 'wp-e-commerce' ),
			'shipping' => __( 'Item Shipping','wp-e-commerce' ),
		);

		if ( wpec_display_product_tax() ) {
			$columns['tax'] = __( 'Item Tax', 'wp-e-commerce' );
		}

		$columns['total'] = __( 'Item Total','wp-e-commerce' );

		$this->cols = count( $columns ) - 2;

		register_column_headers( 'wpsc_purchase_log_item_details', $columns );

		if ( file_exists( get_stylesheet_directory() . '/wpsc-packing-slip.php' ) ) {
			$packing_slip_file = get_stylesheet_directory() . '/wpsc-packing-slip.php';
		} else {
			$packing_slip_file = 'includes/purchase-logs-page/packing-slip.php';
		}

		$packing_slip_file = apply_filters( 'wpsc_packing_packing_slip_path', $packing_slip_file );

		include( $packing_slip_file );

		exit;
	}

	public function controller_default() {
		// Create an instance of our package class...
		$this->list_table = new WPSC_Purchase_Log_List_Table();
		$this->process_bulk_action();
		$this->list_table->prepare_items();
		add_action( 'wpsc_display_purchase_logs_page', array( $this, 'display_list_table' ) );
	}

	public function display_purchase_log() {
		$this->cols = 4;
		if ( wpec_display_product_tax() ) {
			$this->cols++;
		}

		if ( $this->can_edit ) {
			$this->cols++;
		}

		$receipt_sent = ! empty( $_GET['sent'] );
		$receipt_not_sent = isset( $_GET['sent'] ) && ! $_GET['sent'];
		include( 'includes/purchase-logs-page/item-details.php' );

		global $wp_scripts;

		wp_enqueue_script( 'wp-backbone' );

		if ( isset( $wp_scripts->registered['wp-e-commerce-purchase-logs'] ) ) {
			// JS needed for modal
			$wp_scripts->registered['wp-e-commerce-purchase-logs']->deps[] = 'wp-backbone';
		}

		add_action( 'admin_footer', 'find_posts_div' );
	}

	public function download_csv() {
		_wpsc_download_purchase_log_csv();
	}

	public function process_bulk_action() {
		global $wpdb;
		$current_action = $this->list_table->current_action();

		do_action( 'wpsc_sales_log_process_bulk_action', $current_action );

		if ( ! $current_action || ( 'download_csv' != $current_action && empty( $_REQUEST['post'] ) ) ) {
			if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
				wp_redirect( esc_url_raw( remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action', 'action2' ), stripslashes( $_SERVER['REQUEST_URI'] ) ) ) );
				exit;
			}

			unset( $_REQUEST['post'] );
			return;
		}

		if ( 'download_csv' == $current_action ) {
			$this->download_csv();
		}

		$sendback = remove_query_arg( array(
			'_wpnonce',
			'_wp_http_referer',
			'action',
			'action2',
			'confirm',
			'post',
			'last_paged'
		) );

		if ( 'delete' == $current_action ) {

			// delete action
			if ( empty( $_REQUEST['confirm'] ) ) {
				$this->list_table->disable_search_box();
				$this->list_table->disable_bulk_actions();
				$this->list_table->disable_sortable();
				$this->list_table->disable_month_filter();
				$this->list_table->disable_views();
				$this->list_table->set_per_page(0);
				add_action( 'wpsc_purchase_logs_list_table_before', array( $this, 'action_list_table_before' ) );
				return;
			} else {
				if ( empty( $_REQUEST['post'] ) )
					return;

				$ids = array_map( 'intval', $_REQUEST['post'] );

				foreach ( $ids as $id ) {
					$log = wpsc_get_order( $id );
					$log->delete();
				}

				$sendback = add_query_arg( array(
					'paged'   => $_REQUEST['last_paged'],
					'deleted' => count( $_REQUEST['post'] ),
				), $sendback );

			}
		}

		// change status actions
		if ( is_numeric( $current_action ) && ! empty( $_REQUEST['post'] ) ) {

			foreach ( $_REQUEST['post'] as $id )
				wpsc_purchlog_edit_status( $id, $current_action );

			$sendback = add_query_arg( array(
				'updated' => count( $_REQUEST['post'] ),
			), $sendback );
		}

		wp_redirect( esc_url_raw( $sendback ) );
		exit;
	}

	public function action_list_table_before() {
		include( 'includes/purchase-logs-page/bulk-delete-confirm.php' );
	}

	public function display_list_table() {
		if ( ! empty( $this->output ) ) {
			echo $this->output;
			return;
		}

		include( 'includes/purchase-logs-page/list-table.php' );
	}

	private function get_purchase_log_url( $id ) {
		$location = add_query_arg( array(
			'page' => 'wpsc-purchase-logs',
			'c'    => 'item_details',
			'id'   => $id,
		), admin_url( 'index.php' ) );

		return esc_url( $location );
	}

}
