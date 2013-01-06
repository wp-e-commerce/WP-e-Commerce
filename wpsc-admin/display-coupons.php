<?php

function wpsc_display_coupons_page() {
	global $wpdb;

	if( isset( $_GET['view'] ) && $_GET['view'] == 'add' ) {
		// load the coupon add screen
		include( dirname( __FILE__ ) . '/display-coupon-add.php' );

	} elseif( isset( $_GET['view'] ) && $_GET['view'] == 'edit' ) {
		// load the coupon add screen
		include( dirname( __FILE__ ) . '/display-coupon-edit.php' );

	} else {

		if ( isset( $_POST ) && is_array( $_POST ) && !empty( $_POST ) ) {

			if ( isset( $_POST['add_coupon'] ) && (!isset( $_POST['is_edit_coupon'] ) || !($_POST['is_edit_coupon'] == 'true')) ) {

				$coupon_code   = $_POST['add_coupon_code'];
				$discount      = (double)$_POST['add_discount'];
				$discount_type = (int)$_POST['add_discount_type'];
				$free_shipping_details = serialize( (array)$_POST['free_shipping_options'] );
				$use_once      = (int)(bool)$_POST['add_use-once'];
				$every_product = (int)(bool)$_POST['add_every_product'];
				$is_active     = (int)(bool)$_POST['add_active'];
				$use_x_times   = (int)$_POST['add_use-x-times'];
				$start_date    = date( 'Y-m-d', strtotime( $_POST['add_start'] ) ) . " 00:00:00";
				$end_date      = date( 'Y-m-d', strtotime( $_POST['add_end'] ) ) . " 00:00:00";
				$rules         = $_POST['rules'];

				foreach ( $rules as $key => $rule ) {
					foreach ( $rule as $k => $r ) {
						$new_rule[$k][$key] = $r;
					}
				}

				foreach ( $new_rule as $key => $rule ) {
					if ( '' == $rule['value'] ) {
						unset( $new_rule[$key] );
					}
				}
				$insert = $wpdb->insert(
					    WPSC_TABLE_COUPON_CODES,
					    array(
							'coupon_code' => $coupon_code,
							'value' => $discount,
							'is-percentage' => $discount_type,
							'use-once' => $use_once,
							'use-x-times' => $use_x_times,
							'free-shipping' => $free_shipping_details,
							'is-used' => 0,
							'active' => $is_active,
							'every_product' => $every_product,
							'start' => $start_date,
							'expiry' => $end_date,
							'condition' => serialize( $new_rule )
					    ),
					    array(
							'%s',
							'%f',
							'%s',
							'%s',
							'%s',
							'%s',
							'%s',
							'%s',
							'%s',
							'%s',
							'%s',
							'%s'
					    )
					);
				if ( $insert )
				    echo "<div class='updated'><p align='center'>" . __( 'Thanks, the coupon has been added.', 'wpsc' ) . "</p></div>";

			}

			if ( isset( $_POST['delete_condition'] ) ) {

				$conditions = $wpdb->get_var( $wpdb->prepare( "SELECT `condition` FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE `id` = %d LIMIT 1", $_POST['coupon_id'] ) );
				$conditions = unserialize( $conditions );

				unset( $conditions[(int)$_POST['delete_condition']] );

				$wpdb->update(
					WPSC_TABLE_COUPON_CODES,
					array(
					    'condition' => serialize( $conditions ),

					),
					array(
					    'id' => $_POST['coupon_id']
					),
					'%s',
					'%d'
				    );
			}

			if ( isset( $_POST['submit_condition'] ) ) {
				$conditions = $wpdb->get_var( "SELECT `condition` FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE `id` = '" . (int)$_POST['coupon_id'] . "' LIMIT 1" );
				$conditions = unserialize( $conditions );

				$new_cond             = array();
				$new_cond['property'] = $_POST['rules']['property'][0];
				$new_cond['logic']    = $_POST['rules']['logic'][0];
				$new_cond['value']    = $_POST['rules']['value'][0];
				$conditions[]         = $new_cond;

				$wpdb->update(
					    WPSC_TABLE_COUPON_CODES,
					    array(
						'condition' => serialize( $conditions )
					    ),
					    array(
						'id' => $_POST['coupon_id']
					    ),
					    '%s',
					    '%d'
					);
			}
		} ?>

		<script type='text/javascript'>
			jQuery(".pickdate").datepicker();
			/* jQuery datepicker selector */
			if (typeof jQuery('.pickdate').datepicker != "undefined") {
				jQuery('.pickdate').datepicker({ dateFormat: 'yy-mm-dd' });
			}
		</script>

		<div class="wrap">
			<h2>
				<?php _e( 'Coupons', 'wpsc' ); ?>
				<a href="<?php echo add_query_arg( 'view', 'add' ); ?>" id="add_coupon_box_link" class="add_item_link button add-new-h2">
					<?php _e( 'Add New', 'wpsc' ); ?>
				</a>
			</h2>

			<?php
				$columns = array(
					'coupon_code' => __( 'Coupon Code', 'wpsc' ),
					'discount' => __( 'Discount', 'wpsc' ),
					'start' => __( 'Start', 'wpsc' ),
					'expiry' => __( 'Expiry', 'wpsc' ),
					'active' => __( 'Active', 'wpsc' ),
					'apply_on_prods' => __( 'Apply On All Products', 'wpsc' ),
					'edit' => __( 'Edit', 'wpsc' )
				);
				register_column_headers( 'display-coupon-details', $columns );
			?>

			<table class="wp-list-table widefat fixed coupon-list" cellspacing="0">
				<thead>
					<tr>
						<?php print_column_headers( 'display-coupon-details' ); ?>
					</tr>
				</thead>

				<tfoot>
					<tr>
						<?php print_column_headers( 'display-coupon-details', false ); ?>
					</tr>
				</tfoot>

				<tbody>
					<?php
						$i = 1;
						$coupon_data = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_COUPON_CODES . "` ", ARRAY_A );
						if( $coupon_data ) :
							foreach ( (array)$coupon_data as $coupon ) {
								$alternate = "";
								$i++;
								if ( ($i % 2) != 0 ) {
									$alternate = "class='alternate'";
								}
								echo "<tr $alternate>\n\r";

								echo "    <td>\n\r";
								esc_attr_e( $coupon['coupon_code'] );
								echo "    </td>\n\r";

								echo "    <td>\n\r";
								if ( $coupon['is-percentage'] == 1 )
									echo esc_attr( $coupon['value'] ) . "%";

								else if ( $coupon['is-percentage'] == 2 ){
									if(!empty($coupon['free-shipping']))
										echo __("Free Shipping - With Conditions ", 'wpsc');
									else
										echo __("Free Shipping - Global", 'wpsc');

								}
								else
									echo wpsc_currency_display( esc_attr( $coupon['value'] ) );

								echo "    </td>\n\r";

								echo "    <td>\n\r";
								echo date( "d/m/Y", strtotime( esc_attr( $coupon['start'] ) ) );
								echo "    </td>\n\r";

								echo "    <td>\n\r";
								echo date( "d/m/Y", strtotime( esc_attr( $coupon['expiry'] ) ) );
								echo "    </td>\n\r";

								echo "    <td>\n\r";
								switch ( $coupon['active'] ) {
									case 1:
										echo "<img src='" . WPSC_CORE_IMAGES_URL . "/yes_stock.gif' alt='' title='' />";
										break;

									case 0: default:
										echo "<img src='" . WPSC_CORE_IMAGES_URL . "/no_stock.gif' alt='' title='' />";
										break;
								}
								echo "    </td>\n\r";

								echo "    <td>\n\r";
								switch ( $coupon['every_product'] ) {
									case 1:
										echo "<img src='" . WPSC_CORE_IMAGES_URL . "/yes_stock.gif' alt='' title='' />";
										break;

									case 0: default:
										echo "<img src='" . WPSC_CORE_IMAGES_URL . "/no_stock.gif' alt='' title='' />";
										break;
								}

								echo "    </td>\n\r";
								echo "    <td>\n\r";
								echo "<a title='" . esc_attr( $coupon['coupon_code'] ). "' href='" . add_query_arg( array( 'coupon' => $coupon['id'], 'view' => 'edit' ) ) . "' rel='" . $coupon['id'] . "'>" . __( 'Edit', 'wpsc' ) . "</a>";
								echo "    </td>\n\r";
								echo "  </tr>\n\r";
							}
						else : ?>
						<tr>
							<td colspn="7"><?php _e( 'No coupon codes created yet.', 'wpsc' ); ?></td>
						</tr>
						<?php
						endif;
					?>
				</tbody>
			</table>

			<p style='margin: 10px 0px 5px 0px;'>
				<?php _e( '<strong>Note:</strong> Due to a current PayPal limitation, when a purchase is made using a coupon we cannot send a detailed list of items through for processing. Instead we send the total amount of the purchase so the customer will see your shop name and the total within PayPal.', 'wpsc' ); ?>
			</p>

		</div>

	<?php

	} // end view check

}