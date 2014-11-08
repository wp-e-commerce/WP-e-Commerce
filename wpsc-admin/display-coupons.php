<?php

function wpsc_display_coupons_page() {
	global $wpdb;


	/**
	 * Update / create code that will be abstracted to its own class at some point
	 */

	if ( isset( $_POST ) && is_array( $_POST ) && ! empty( $_POST ) ) {

		if ( isset( $_POST['add_coupon'] ) && (!isset( $_POST['is_edit_coupon'] ) || !($_POST['is_edit_coupon'] == 'true')) ) {

			check_admin_referer( 'wpsc_coupon', 'wpsc-coupon-add' );

			$coupon_code   = $_POST['add_coupon_code'];
			$discount      = (double)$_POST['add_discount'];
			$discount_type = (int)$_POST['add_discount_type'];
			$use_once      = (int)(bool)$_POST['add_use-once'];
			$every_product = (int)(bool)$_POST['add_every_product'];
			$is_active     = (int)(bool)$_POST['add_active'];
			$start_date    = ! empty( $_POST['add_start'] ) ? date( 'Y-m-d', strtotime( $_POST['add_start'] ) ) . " 00:00:00" : null;
			$end_date      = ! empty( $_POST['add_end'] ) ? date( 'Y-m-d', strtotime( $_POST['add_end'] ) ) . " 23:59:59" : null;
			$rules         = $_POST['rules'];
			$new_rules     = array();

			foreach ( $rules as $key => $rule ) {
				foreach ( $rule as $k => $r ) {
					$new_rules[$k][$key] = $r;
				}
			}

			foreach ( $new_rules as $key => $rule ) {
				if ( '' == $rule['value'] )
					unset( $new_rules[$key] );
			}

			$insert = $wpdb->insert(
				    WPSC_TABLE_COUPON_CODES,
				    array(
						'coupon_code' => $coupon_code,
						'value' => $discount,
						'is-percentage' => $discount_type,
						'use-once' => $use_once,
						'is-used' => 0,
						'active' => $is_active,
						'every_product' => $every_product,
						'start' => $start_date,
						'expiry' => $end_date,
						'condition' => serialize( $new_rules )
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
				    )
				);
			if ( $insert )
			    echo "<div class='updated'><p>" . __( 'The coupon has been added.', 'wpsc' ) . "</p></div>";

		}

		// update an existing coupon
		if ( isset( $_POST['is_edit_coupon'] ) && ($_POST['is_edit_coupon'] == 'true') && !(isset( $_POST['delete_condition'] )) && !(isset( $_POST['submit_condition'] )) ) {

			check_admin_referer( 'wpsc_coupon', 'wpsc-coupon-edit' );

			$rules     = isset( $_POST['rules'] ) ? $_POST['rules'] : array();
			$new_rules = array();

			foreach ( $rules as $key => $rule ) {
				foreach ( $rule as $k => $r ) {
					$new_rules[$k][$key] = $r;
				}
			}

			foreach ( $new_rules as $key => $rule ) {
				if ( '' == $rule['value'] )
					unset( $new_rules[$key] );
			}

			$update = $wpdb->update(
				WPSC_TABLE_COUPON_CODES,
				array(
					'coupon_code'   => $_POST['edit_coupon_code'],
					'value'         => $_POST['edit_coupon_amount'],
					'is-percentage' => $_POST['edit_discount_type'],
					'use-once'      => $_POST['edit_coupon_use_once'],
					'is-used'       => $_POST['edit_coupon_is_used'],
					'active'        => $_POST['edit_coupon_active'],
					'every_product' => $_POST['edit_coupon_every_product'],
					'start'         => ! empty( $_POST['edit_coupon_start'] ) ? get_gmt_from_date( $_POST['edit_coupon_start'] . ' 00:00:00' ) : null,
					'expiry'        => ! empty( $_POST['edit_coupon_end'] ) ? get_gmt_from_date( $_POST['edit_coupon_end'] . ' 23:59:59' ) : null,
					'condition'     => serialize( $new_rules )
				),
				array( 'id'         => absint( $_POST['coupon_id'] ) ),
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
					'%s'
				),
				array( '%d' )
			);

			if ( $update ) {
				echo "<div class='updated'><p>" . __( 'The coupon has been updated.', 'wpsc' ) . "</p></div>";
			}
		}
	}

	/**
	 * Load the selected view
	 */

	if( isset( $_GET['wpsc-action'] ) && $_GET['wpsc-action'] == 'add_coupon' ) {
		// load the coupon add screen
		include( dirname( __FILE__ ) . '/display-coupon-add.php' );

	} elseif( isset( $_GET['wpsc-action'] ) && $_GET['wpsc-action'] == 'edit_coupon' ) {
		// load the coupon add screen
		include( dirname( __FILE__ ) . '/display-coupon-edit.php' );

	} else {
		require_once WPSC_FILE_PATH . '/wpsc-admin/includes/coupon-list-table-class.php';
		$coupons_table = new WPSC_Coupons_List_Table();
		$coupons_table->prepare_items(); ?>
		<div class="wrap">
			<h2><?php _e( 'Coupons', 'wpsc' ); ?><a href="<?php echo add_query_arg( 'wpsc-action', 'add_coupon' ); ?>" class="add-new-h2"><?php _e( 'Add Coupon', 'wpsc' ); ?></a></h2>
			<?php do_action( 'wpsc_coupons_page_top' ); ?>
			<form id="wpsc-coupons-filter" method="get" action="<?php echo admin_url( 'edit.php?post_type=wpsc-product&page=wpsc-edit-coupons' ); ?>">

				<input type="hidden" name="post_type" value="wpsc-product" />
				<input type="hidden" name="page" value="wpsc-edit-coupons" />

				<?php $coupons_table->views() ?>
				<?php $coupons_table->display() ?>
			</form>
			<?php do_action( 'wpsc_coupons_page_bottom' ); ?>
		</div>
		<?php
	} // end view check
}
