<?php

/**
 * The Product Variations page class in the WordPress admin
 *
 * @package wp-e-commerce
 */

class WPSC_Product_Variations_Page {
	private $list_table;
	private $parent_id;
	private $current_tab;
	private $post;

	public function __construct() {
		require_once( WPSC_FILE_PATH . '/wpsc-admin/includes/product-variation-list-table.class.php' );
		$GLOBALS['hook_suffix'] = 'wpsc-product-variations-iframe';
		$this->parent_id = absint( $_REQUEST['product_id'] );
		set_current_screen();

		if ( ! empty( $_REQUEST['tab'] ) ) {
			$this->current_tab = $_REQUEST['tab'];
		} else {
			$args = array(
				'post_parent' => $this->parent_id,
				'post_type'   => 'wpsc-product',
				'post_status' => 'any');

			$number_of_variations = count(get_children($args));

			$this->current_tab = ($number_of_variations > 0) ? 'manage' : 'setup';
		}
	}

	private function merge_meta_deep( $original, $updated ) {
		$keys = array_merge( array_keys( $original ), array_keys( $updated ) );

		foreach ( $keys as $key ) {
			if ( ! isset( $updated[$key] ) )
				continue;

			if ( isset( $original[$key] ) && is_array( $original[$key] ) ) {
				$original[$key] = $this->merge_meta_deep( $original[$key]	, $updated[$key] );
			} else {
				$original[$key] = $updated[$key];
				if ( in_array( $key, array( 'weight', 'wpec_taxes_taxable_amount', 'height', 'width', 'length' ) ) )
					$original[$key] = (float) $original[$key];
			}

		}

		return $original;
	}

	/*   */
	private function save_variation_meta( $id, $data ) {

		$product_meta = get_product_meta( $id, 'product_metadata', true );

		if ( ! is_array( $product_meta ) ) {
			$product_meta = array();
		}

		$product_meta = $this->merge_meta_deep( $product_meta, $data['product_metadata'] );

		// convert to pound to maintain backward compat with shipping modules
		if ( isset( $data['product_metadata']['weight'] ) || isset( $data['product_metadata']['weight_unit'] ) ) {
			$product_meta['weight'] = wpsc_convert_weight( $product_meta['weight'], $product_meta['weight_unit'], 'pound', true );
		}

		update_product_meta( $id, 'product_metadata', $product_meta );

		if ( isset( $data['price'] ) ) {
			update_product_meta( $id, 'price', wpsc_string_to_float( $data['price'] ) );
		}

		if ( isset( $data['sale_price'] ) ) {

			$sale_price = wpsc_string_to_float( $data['sale_price'] );

			if ( is_numeric( $sale_price ) ) {
				update_product_meta( $id, 'special_price', wpsc_string_to_float( $data['sale_price'] ) );
			} else {
				update_product_meta( $id, 'special_price', '' );
			}
		}

		if ( isset( $data['sku'] ) ) {
			update_product_meta( $id, 'sku', sanitize_text_field( $data['sku'] ) );
		}

		if ( isset( $data['stock'] ) ) {
			if ( is_numeric( $data['stock'] ) ) {
				update_product_meta( $id, 'stock', (int) $data['stock'] );
				$parent_id = wpsc_product_is_variation( $id );

				if( $parent_id ) {
					// If product is a variatio get the notification threshold from parent product
					$parent_meta = get_product_meta( $parent_id, 'product_metadata', true );
					$notify_limit = $parent_meta['stock_limit_notify'];
					if ( (int) $data['stock'] > $notify_limit ) {
						// Check if notification has been sent
						$notify_sent = get_product_meta( $id, 'stock_limit_notify_sent', true );
						if( ! empty( $notify_sent ) ) {
							delete_product_meta( $id, 'stock_limit_notify_sent' );
						}
					}
				}
			} else {
				update_product_meta( $id, 'stock', '' );
			}
		}
	}

	private function save_variations_meta(){
		if ( empty( $_REQUEST['wpsc_variations'] ) )
			return;

		check_admin_referer( 'wpsc_save_variations_meta', '_wpsc_save_meta_nonce' );
		$post_type_object = get_post_type_object( 'wpsc-product' );
		if ( ! current_user_can( $post_type_object->cap->edit_posts ) )
			wp_die( __( 'Cheatin&#8217; uh?', 'wp-e-commerce' ) );

		/* Long-term, we should have a better saving routine here.  Can't unset these currently. *
		/* That said, the only thing that fails hard if we can't unset it is the checkbox. */
		foreach ( $_REQUEST['wpsc_variations'] as $id => $data ) {
			if ( ! isset( $data['product_metadata']['no_shipping'] ) ) {
				$data['product_metadata']['no_shipping'] = '';
			}
			$this->save_variation_meta( $id, $data );
		}
	}

	public function display() {
		global $title, $hook_suffix, $wp_locale, $pagenow, $wp_version, $is_iphone,
		$current_site, $update_title, $total_update_count, $parent_file;

		$current_screen = get_current_screen();
		$admin_body_class = $hook_suffix;
		$post_type_object = get_post_type_object( 'wpsc-product' );

		wp_enqueue_style( 'global' );
		wp_enqueue_style( 'wp-admin' );
		wp_enqueue_style( 'buttons' );
		wp_enqueue_style( 'colors' );
		wp_enqueue_style( 'ie'     );
		wp_enqueue_script( 'common'       );
		wp_enqueue_script( 'jquery-color' );
		wp_enqueue_script( 'utils'        );
		wp_enqueue_script( 'jquery-query' );
		wp_enqueue_media( array( 'post' => absint( $_REQUEST['product_id'] ) ) );


		$callback = "callback_tab_{$this->current_tab}";
		if ( ! is_callable( array( $this, "callback_tab_{$this->current_tab}" ) ) )
			$callback = "callback_tab_manage";

		$this->$callback();

		@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
		require_once( WPSC_FILE_PATH . "/wpsc-admin/includes/product-variations.page.php" );
	}

	private function display_tabs() {
		$tabs = array(
			'manage'   => _x( 'Manage', 'manage product variations', 'wp-e-commerce' ),
			'setup' => __( 'Setup', 'wp-e-commerce' ),
		);

		echo '<ul id="wpsc-product-variations-tabs" class="category-tabs">';
		foreach ( $tabs as $tab => $title ) {
			$class = ( $tab == $this->current_tab ) ? ' class="tabs"' : '';
			$item = '<li' . $class . '>';
			$item .= '<a href="' . esc_url( add_query_arg( 'tab', $tab ) ) . '">' . esc_html( $title ) . '</a></li> ';
			echo $item;
		}
		echo '</ul>';
	}

	private function callback_tab_manage() {
		$this->list_table = new WPSC_Product_Variation_List_Table( $this->parent_id );
		$this->save_variations_meta();
		$this->process_bulk_action();
		$this->list_table->prepare_items();
	}

	private function callback_tab_setup() {
		global $post;
		require_once( 'walker-variation-checklist.php' );

		$this->generate_variations();
	}

	private function generate_variations() {
		if ( ! isset( $_REQUEST['action2'] ) || $_REQUEST['action2'] != 'generate' )
			return;

		check_admin_referer( 'wpsc_generate_product_variations', '_wpsc_generate_product_variations_nonce' );

		wpsc_update_variations();

		$sendback = remove_query_arg( array(
			'_wp_http_referer',
			'updated',
		) );
		wp_redirect( esc_url_raw( add_query_arg( 'tab', 'manage', $sendback ) ) );
		exit;
	}

	public function display_current_tab() {
		require_once( WPSC_FILE_PATH . "/wpsc-admin/includes/product-variations-{$this->current_tab}.page.php" );
	}

	public function process_bulk_action_delete_all( $post_ids ) {
		$post_status = preg_replace( '/[^a-z0-9_-]+/i', '', $_REQUEST['post_status'] );
		if ( get_post_status_object( $post_status ) ) // Check the post status exists first
			$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='wpsc-product' AND post_status = %s", $post_type, $post_status ) );

		return $this->process_bulk_action_delete( $post_ids );
	}

	public function process_bulk_action_trash( $post_ids ) {
		$post_type_object = get_post_type_object( 'wpsc-product' );
		$trashed = 0;
		foreach( (array) $post_ids as $post_id ) {
			if ( !current_user_can( $post_type_object->cap->delete_post, $post_id ) )
				wp_die( __( 'You are not allowed to move this item to the Trash.', 'wp-e-commerce' ) );

			if ( !wp_trash_post( $post_id ) )
				wp_die( __( 'Error in moving to Trash.', 'wp-e-commerce' ) );

			$trashed++;
		}
		return esc_url( add_query_arg( array( 'trashed' => $trashed, 'ids' => join( ',', $post_ids ) ) ) );
	}

	public function process_bulk_action_untrash( $post_ids ) {
		$post_type_object = get_post_type_object( 'wpsc-product' );
		$untrashed = 0;
		foreach( (array) $post_ids as $post_id ) {
			if ( ! current_user_can( $post_type_object->cap->delete_post, $post_id ) )
				wp_die( __( 'You are not allowed to restore this item from the Trash.', 'wp-e-commerce' ) );

			if ( !wp_untrash_post( $post_id ) )
				wp_die( __( 'Error in restoring from Trash.', 'wp-e-commerce' ) );

			$untrashed++;
		}
		return esc_url( add_query_arg( 'untrashed', $untrashed ) );
	}

	public function process_bulk_action_delete( $post_ids ) {
		$deleted = 0;
		$post_type_object = get_post_type_object( 'wpsc-product' );
		foreach( (array) $post_ids as $post_id ) {
			$post_del = & get_post( $post_id );

			if ( ! current_user_can( $post_type_object->cap->delete_post, $post_id ) )
				wp_die( __( 'You are not allowed to delete this item.', 'wp-e-commerce' ) );

			if ( $post_del->post_type == 'attachment' ) {
				if ( ! wp_delete_attachment( $post_id ) )
					wp_die( __( 'Error in deleting...', 'wp-e-commerce' ) );
			} else {
				if ( ! wp_delete_post( $post_id ) )
					wp_die( __( 'Error in deleting...', 'wp-e-commerce' ) );
			}
			$deleted++;
		}
		return esc_url( add_query_arg( 'deleted', $deleted ) );
	}

	public function process_bulk_action_hide( $post_ids ) {
		$updated = 0;
		foreach( $post_ids as $id ) {
			wp_update_post( array(
				'ID'          => $id,
				'post_status' => 'draft',
			) );
			$updated ++;
		}
		return esc_url( add_query_arg( 'updated', $updated ) );
	}

	public function process_bulk_action_show( $post_ids ) {
		$updated = 0;
		foreach ( $post_ids as $id ) {
			wp_update_post( array(
				'ID' => $id,
				'post_status' => 'publish',
			) );
			$updated ++;
		}
		return esc_url( add_query_arg( 'updated', $updated ) );
	}

	private function save_bulk_edited_items() {
		$ids = array_map( 'absint', $_REQUEST['wpsc_bulk_edit']['post'] );
		$data = $_REQUEST['wpsc_bulk_edit'];

		if ( empty( $_REQUEST['wpsc_bulk_edit']['fields'] ) )
			return;

		$fields = $_REQUEST['wpsc_bulk_edit']['fields'];

		foreach ( array( 'stock', 'price', 'sale_price', 'sku' ) as $field ) {
			if ( empty( $fields[$field] ) )
				unset( $data[$field] );
		}

		if ( empty( $fields['shipping'] ) )
			unset( $data['product_metadata']['shipping'] );
		else {
			foreach ( array( 'local', 'international' ) as $field ) {
				if ( empty( $fields['shipping'][$field] ) )
					unset( $data['product_metadata'][$field] );
			}
		}

		if ( empty( $fields['measurements'] ) ) {
			unset( $data['product_metadata']['dimensions'] );
			unset( $data['product_metadata']['weight'] );
			unset( $data['product_metadata']['weight_unit'] );
		} else {
			if ( empty( $fields['measurements']['weight'] ) ) {
				unset( $data['product_metadata']['weight'] );
				unset( $data['product_metadata']['weight_unit'] );
			}

			if ( empty( $fields['measurements']['dimensions'] ) ) {
				foreach ( array( 'height', 'width', 'length' ) as $field ) {
					unset( $data['product_metadata']['dimensions'][$field] );
					unset( $data['product_metadata']['dimensions'][$field . '_unit'] );
				}
			} else {
				foreach ( array( 'height', 'width', 'length' ) as $field ) {
					$data['product_metadata']['dimensions'][$field . '_unit'] = "cm";
				}
			}

			unset( $data['product_metadata']['dimensions_unit'] );
		}

		unset( $data['post'] );
		unset( $data['fields'] );

		foreach ( $ids as $id ) {
			$this->save_variation_meta( $id, $data );
		}

		$sendback = $_SERVER['REQUEST_URI'];
		$sendback = remove_query_arg( array(
			'_wp_http_referer',
			'bulk_action',
			'bulk_action2',
			'bulk_action_nonce',
			'confirm',
			'post',
			'last_paged'
		), $sendback );
		$sendback = add_query_arg( 'updated', count( $ids ), $sendback );
		wp_redirect( esc_url_raw( $sendback ) );
		exit;
	}

	public function process_bulk_action_edit( $post_ids ) {
		$this->list_table->set_bulk_edited_items( $post_ids );
	}

	public function process_bulk_action() {
		if ( ! empty( $_REQUEST['wpsc_bulk_edit']['post'] ) ) {
			$this->save_bulk_edited_items();
			return;
		}

		$current_action = $this->list_table->current_action();
		if ( empty( $current_action ) )
			return;

		_wpsc_remove_refresh_variation_parent_term_hooks();

		check_admin_referer( 'wpsc_product_variations_bulk_action', 'bulk_action_nonce' );
		$sendback = $_SERVER['REQUEST_URI'];
		$callback = 'process_bulk_action_' . $current_action;

		$post_ids = isset( $_REQUEST['post'] ) ? $_REQUEST['post'] : array();
		if ( ! is_array( $post_ids ) )
			$post_ids = explode( ',', $post_ids );
		$post_ids = array_map('intval', $post_ids);
		if ( ! empty( $post_ids ) && is_callable( array( $this, $callback ) ) )
			$sendback = $this->$callback( $post_ids );

		$sendback = remove_query_arg( array(
			'_wp_http_referer',
			'bulk_action',
			'bulk_action2',
			'bulk_action_nonce',
			'confirm',
			'post',
			'last_paged'
		), $sendback );

		_wpsc_refresh_parent_product_terms( $this->parent_id );
		_wpsc_add_refresh_variation_parent_term_hooks();
		if ( $current_action != 'edit' ) {
			wp_redirect( esc_url_raw( $sendback ) );
			exit;
		}
	}
}

/**
 * Wrapper for _wp_admin_html_begin(), which might not be available on older
 * WordPress versions.
 *
 * @access private
 * @since 3.8.9.4
 */
function _wpsc_admin_html_begin() {
	if ( function_exists( '_wp_admin_html_begin' ) ) {
		_wp_admin_html_begin();
		return;
	}

	$admin_html_class = ( is_admin_bar_showing() ) ? 'wp-toolbar' : '';
	?>
<!DOCTYPE html>
<!--[if IE 8]>
<html xmlns="http://www.w3.org/1999/xhtml" class="ie8 <?php echo $admin_html_class; ?>" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 8) ]><!-->
<html xmlns="http://www.w3.org/1999/xhtml" class="<?php echo $admin_html_class; ?>" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
<!--<![endif]-->
<head>
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
<?php
}
