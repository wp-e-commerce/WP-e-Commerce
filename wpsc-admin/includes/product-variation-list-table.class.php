<?php

require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
require_once( ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php' );

/**
 * Product Variation List Table class
 *
 * @package wp-e-commerce
 */
class WPSC_Product_Variation_List_Table extends WP_List_Table {
	private $product_id;
	private $object_terms_cache = array();
	private $args = array();
	private $is_trash             = false;
	private $is_draft             = false;
	private $is_publish           = false;
	private $is_all               = true;
	private $is_bulk_edit         = false;
	private $bulk_edited_items    = array();
	private $bulk_edited_item_ids = array();

	public function __construct( $product_id ) {
		WP_List_Table::__construct( array(
			'plural' => 'variations',
		) );

		$this->product_id = $product_id;
		if ( isset( $_REQUEST['post_status'] ) ) {
			$this->is_trash = $_REQUEST['post_status'] == 'trash';
			$this->is_draft = $_REQUEST['post_status'] == 'draft';
			$this->is_publish = $_REQUEST['post_status'] == 'publish';
			$this->is_all = $_REQUEST['post_status'] == 'all';
		} else {
			$this->is_all = true;
		}
	}

	public function prepare_items() {
		if ( ! empty( $this->items ) )
			return;

		$per_page = $this->get_items_per_page( 'edit_wpsc-product-variations_per_page' );
		$per_page = apply_filters( 'edit_wpsc_product_variations_per_page', $per_page );
		$this->args = array(
			'post_type'      => 'wpsc-product',
			'orderby'        => 'menu_order title',
			'post_parent'    => $this->product_id,
			'post_status'    => 'publish, inherit',
			'numberposts'    => -1,
			'order'          => "ASC",
			'posts_per_page' => $per_page,
		);

		if ( isset( $_REQUEST['post_status'] ) )
			$this->args['post_status'] = $_REQUEST['post_status'];

		if ( isset( $_REQUEST['s'] ) )
			$this->args['s'] = $_REQUEST['s'];

		if ( isset( $_REQUEST['paged'] ) )
			$this->args['paged'] = $_REQUEST['paged'];

		$query = new WP_Query( $this->args );

		$this->items = $query->posts;
		$total_items = $query->found_posts;
		$total_pages = $query->max_num_pages;

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'per_page' => $per_page
		) );

		if ( empty( $this->items ) )
			return;

		$ids = wp_list_pluck( $this->items, 'ID' );
		$object_terms = wp_get_object_terms( $ids, 'wpsc-variation', array( 'fields' => 'all_with_object_id' ) );

		foreach ( $object_terms as $term ) {
			if ( ! array_key_exists( $term->object_id, $this->object_terms_cache ) )
				$this->object_terms_cache[$term->object_id] = array();

			$this->object_terms_cache[$term->object_id][$term->parent] = $term->name;
		}
	}

	public function get_hidden_columns() {
		return array();
	}

	/**
	 * Get a list of all, hidden and sortable columns, with filter applied
	 *
	 * @since 3.1.0
	 * @access protected
	 *
	 * @return array
	 */
	public function get_column_info() {
		if ( isset( $this->_column_headers ) )
			return $this->_column_headers;

		$screen = get_current_screen();

		$columns = $this->get_columns();
		$hidden = $this->get_hidden_columns();
		$_sortable = $this->get_sortable_columns();

		$sortable = array();
		foreach ( $_sortable as $id => $data ) {
			if ( empty( $data ) )
				continue;

			$data = (array) $data;
			if ( !isset( $data[1] ) )
				$data[1] = false;

			$sortable[$id] = $data;
		}

		$this->_column_headers = array( $columns, $hidden, $sortable );

		return $this->_column_headers;
	}

	public function get_columns() {
		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'title'      => __( 'Title', 'wpsc' ),
			'sku'        => __( 'SKU', 'wpsc' ),
			'price'      => __( 'Price', 'wpsc' ),
			'sale_price' => __( 'Sale Price', 'wpsc' ),
			'stock'      => __( 'Stock', 'wpsc' ),
		);

		if ( get_option( 'wpec_taxes_enabled' ) )
			$columns['tax'] = __( 'Taxable Amount', 'wpsc' );

		return apply_filters( 'wpsc_variation_column_headers', $columns );
	}

	public function get_sortable_columns() {
		return array();
	}

	public function column_cb( $item ) {
		$checked = isset( $_REQUEST['variations'] ) ? checked( in_array( $item->ID, $_REQUEST['variations'] ), true, false ) : '';
		return sprintf(
			'<input type="checkbox" %1$s name="%2$s[]" value="%3$s" />',
			/*$1%s*/ $checked,
			/*$2%s*/ 'post',
			/*$3%s*/ $item->ID
		);
	}

	private function get_row_actions( $item ) {
		$post_type_object = get_post_type_object( 'wpsc-product' );
		$can_edit_post = current_user_can( $post_type_object->cap->edit_post, $item->ID );

		$actions = array();
		if ( apply_filters( 'wpsc_show_product_variations_edit_action', true, $item ) && $can_edit_post && 'trash' != $item->post_status )
			$actions['edit'] = '<a target="_blank" href="' . get_edit_post_link( $item->ID, true ) . '" title="' . esc_attr( __( 'Edit this item' ), 'wpsc' ) . '">' . __( 'Edit' ) . '</a>';

		$actions['stock hide-if-no-js'] = '<a class="wpsc-variation-stock-editor-link" href="#" title="' . __( 'Show shipping editor', 'wpsc' ) . '">' . __( 'Edit Shipping', 'wpsc' ) . '</a>';

		if ( $item->post_status == 'draft' ) {
			$show_url = add_query_arg( array(
				'bulk_action'       => 'show',
				'post'              => $item->ID,
				'bulk_action_nonce' => wp_create_nonce( 'wpsc_product_variations_bulk_action' ),
			) );
			$actions['show'] = '<a class="wpsc-variation-show-link" href="' . $show_url . '" title="' . __( 'Show this variation on the front-end', 'wpsc' ) . '">' . __( 'Publish', 'wpsc' ) . '</a>';
		} elseif ( in_array( $item->post_status, array( 'publish', 'inherit' ) ) ) {
			$hide_url = add_query_arg( array(
				'bulk_action'       => 'hide',
				'post'              => $item->ID,
				'bulk_action_nonce' => wp_create_nonce( 'wpsc_product_variations_bulk_action' ),
			) );
			$actions['hide'] = '<a class="wpsc-variation-hide-link" href="' . $hide_url . '" title="' . __( 'Mark this variation as draft to hide from the front-end', 'wpsc' ) . '">' . __( 'Mark as Draft', 'wpsc' ) . '</a>';
		}

		if ( current_user_can( $post_type_object->cap->delete_post, $item->ID ) ) {
			$force_delete = 'trash' == $item->post_status || ! EMPTY_TRASH_DAYS;
			$redirect_url = urlencode( _wpsc_get_product_variation_form_url( $this->product_id ) );
			$delete_link = add_query_arg( '_wp_http_referer', $redirect_url, get_delete_post_link( $item->ID, '', $force_delete ) );

			if ( 'trash' == $item->post_status ) {
				$restore_url = admin_url( sprintf( $post_type_object->_edit_link, $item->ID ) );
				$restore_url = add_query_arg(
					array(
						'action'           => 'untrash',
						'_wp_http_referer' => $redirect_url,
					),
					$restore_url
				);
				$restore_url = wp_nonce_url( $restore_url, 'untrash-post_' . $item->ID );
				$actions['untrash'] = "<a title='" . esc_attr( __( 'Restore this item from the Trash' ) ) . "' href='" . $restore_url . "'>" . __( 'Restore' ) . "</a>";
			} elseif ( EMPTY_TRASH_DAYS ) {
				$actions['trash'] = "<a class='submitdelete' title='" . esc_attr( __( 'Move this item to the Trash' ) ) . "' href='" . $delete_link . "'>" . __( 'Trash' ) . "</a>";
			}

			if ( $force_delete )
				$actions['delete'] = "<a class='submitdelete' title='" . esc_attr( __( 'Delete this item permanently' ) ) . "' href='" . $delete_link . "'>" . __( 'Delete Permanently' ) . "</a>";
		}

		return $actions;
	}

	public function column_title( $item ) {
		$title = implode( ', ', $this->object_terms_cache[$item->ID] );
		$thumbnail = wpsc_the_product_thumbnail( false, false, $item->ID, 'manage-products' );
		$show_edit_link = apply_filters( 'wpsc_show_product_variations_edit_action', true, $item );

		$nonce = wp_create_nonce( "wpsc_ajax_get_variation_gallery_{$item->ID}" );
		$save_gallery_nonce = wp_create_nonce( "wpsc_ajax_update_gallery_{$item->ID}" );
		$get_gallery_nonce = wp_create_nonce( "wpsc_ajax_get_gallery_{$item->ID}" );

		if ( ! $thumbnail )
			$thumbnail = WPSC_CORE_IMAGES_URL . '/no-image-uploaded.gif';
		?>
			<div class="wpsc-product-variation-thumbnail">
				<a
					target="_blank"
					data-featured-nonce="<?php echo esc_attr( wp_create_nonce( "update-post_{$item->ID}" ) ); ?>"
					data-nonce="<?php echo esc_attr( $nonce ); ?>"
					data-save-gallery-nonce="<?php echo esc_attr( $save_gallery_nonce ); ?>"
					data-get-gallery-nonce="<?php echo esc_attr( $get_gallery_nonce ); ?>"
					data-image-id="<?php echo get_post_thumbnail_id( $item->ID ); ?>"
					data-id="<?php echo $item->ID; ?>"
					data-title="<?php echo esc_attr( $title ); ?>"
					href="<?php echo esc_url( admin_url( 'media-upload.php?post_id=' . $item->ID . '&width=640&height=566&product_variation=1' ) ) ?>"
				>
					<img id="wpsc-variation-thumbnail-<?php echo $item->ID; ?>" src="<?php echo esc_url( $thumbnail ); ?>" alt="" />
				</a>
			</div>
			<div class="wpsc-product-variation-title">
				<strong class="row-title">
					<?php if ( $show_edit_link ): ?>
						<a target="_blank" href="<?php echo esc_url( get_edit_post_link( $item->ID, true ) ); ?>" title="<?php esc_attr_e( __( 'Edit this item' ), 'wpsc' ); ?>">
					<?php endif; ?>
					<?php echo esc_html( apply_filters( 'wpsc_variation_name', $title, $item ) ); ?>
					<?php if ( $show_edit_link ): ?>
						</a>
					<?php endif; ?>
				</strong>
				<?php echo $this->row_actions( $this->get_row_actions( $item ) ); ?>
			</div>
		<?php
	}

	/**
	 * Stock Column
	 *
	 * @uses   get_product_meta  Get product meta data.
     *
     * @param  object $item      Product
	 */
	public function column_stock( $item ) {
		$stock = get_product_meta( $item->ID, 'stock', true );
		if ( is_numeric( $stock ) )
			$stock = absint( $stock );
		?>
		<input type="text" name="wpsc_variations[<?php echo $item->ID; ?>][stock]" value="<?php echo esc_attr( $stock ); ?>" />
		<?php
	}

	public function column_price( $item ) {
		$price = get_product_meta( $item->ID, 'price', true );
		$price = wpsc_format_number( $price );
		?>
			<input type="text" name="wpsc_variations[<?php echo $item->ID; ?>][price]" value="<?php echo esc_attr( $price ); ?>" />
		<?php
	}

	public function column_sale_price( $item ) {
		$sale_price = get_product_meta( $item->ID, 'special_price', true );
		if ( is_numeric( $sale_price ) )
			$sale_price = wpsc_format_number( $sale_price );
		?>
			<input type="text" name="wpsc_variations[<?php echo $item->ID; ?>][sale_price]" value="<?php echo esc_attr( $sale_price ); ?>">
		<?php
	}

	public function column_sku( $item ) {
		$sku = get_product_meta( $item->ID, 'sku', true );
		?>
			<input type="text" name="wpsc_variations[<?php echo $item->ID; ?>][sku]" value="<?php echo esc_attr( $sku ); ?>" />
		<?php
	}

	public function column_tax( $item ) {
		$meta = get_post_meta( $item->ID, '_wpsc_product_metadata', true );
		if ( ! $meta || ! isset( $meta['wpec_taxes_taxable_amount'] ) )
			$tax = '';
		else
			$tax = wpsc_format_number( $meta['wpec_taxes_taxable_amount'] );
		?>
			<input type="text" name="wpsc_variations[<?php echo $item->ID; ?>][product_metadata][wpec_taxes_taxable_amount]" value="<?php echo esc_attr( $tax ); ?>" />
		<?php
	}

	public function column_default( $item, $column_name ) {
		$output = apply_filters( 'wpsc_manage_product_variations_custom_column', '', $column_name, $item );
		return $output;
	}

	private function shipping_editor( $item = false ) {
		static $alternate = '';

		if ( ! $item )
			$alternate = '';
		else
			$alternate = ( $alternate == '' ) ? ' alternate' : '';

		$row_class = $alternate;

		$style = '';
		$bulk = false;
		if ( ! $item ) {
			$item = get_post( $this->product_id );
			$field_name = "wpsc_bulk_edit[product_metadata]";
			$row_class .= " wpsc_bulk_edit_shipping";
			if ( $this->is_bulk_edit )
				$style = ' style="display:table-row;"';
			else
				$style = ' style="display:none;"';
			$bulk = true;
		} else {
			$field_name = "wpsc_variations[{$item->ID}][product_metadata]";
		}
		$colspan = count( $this->get_columns() ) - 1;
		?>
		<tr class="wpsc-stock-editor-row inline-edit-row<?php echo $row_class; ?>"<?php echo $style; ?> id="wpsc-stock-editor-row-<?php echo $item->ID; ?>">
			<td></td>
			<td colspan="<?php echo $colspan; ?>" class="colspanchange">
				<h4><?php esc_html_e( 'Variation Stock Editor', 'wpsc' ); ?></h4>
				<?php wpsc_product_shipping_forms( $item, $field_name, $bulk ); ?>
			</td>
		</tr>
		<?php
	}

	public function single_row( $item ) {
		static $count = 0;
		$count ++;
		$item->index = $count;
		parent::single_row( $item );
		$this->shipping_editor( $item );
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 3.1.0
	 * @access protected
	 */
	public function display_tablenav( $which ) {
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<div class="alignleft actions">
				<?php $this->bulk_actions( $which ); ?>
			</div>
		<?php
		$this->extra_tablenav( $which );
		$this->pagination( $which );
		?>
			<br class="clear" />
		</div>
		<?php
	}

	public function display_rows() {
		$this->display_bulk_edit_row();
		if ( ! $this->is_bulk_edit )
			parent::display_rows();
	}

	public function display_messages() {
		if ( isset($_REQUEST['locked']) || isset($_REQUEST['skipped']) || isset($_REQUEST['updated']) || isset($_REQUEST['deleted']) || isset($_REQUEST['trashed']) || isset($_REQUEST['untrashed']) )
			$messages = array();
		else
			return;

		if ( isset($_REQUEST['updated']) && (int) $_REQUEST['updated'] ) {
			$messages[] = sprintf( _n( '%s post updated.', '%s posts updated.', $_REQUEST['updated'] ), number_format_i18n( $_REQUEST['updated'] ) );
			unset($_REQUEST['updated']);
		}

		if ( isset($_REQUEST['skipped']) && (int) $_REQUEST['skipped'] )
			unset($_REQUEST['skipped']);

		if ( isset($_REQUEST['locked']) && (int) $_REQUEST['locked'] ) {
			$messages[] = sprintf( _n( '%s item not updated, somebody is editing it.', '%s items not updated, somebody is editing them.', $_REQUEST['locked'] ), number_format_i18n( $_REQUEST['locked'] ) );
			unset($_REQUEST['locked']);
		}

		if ( isset($_REQUEST['deleted']) && (int) $_REQUEST['deleted'] ) {
			$messages[] = sprintf( _n( 'Item permanently deleted.', '%s items permanently deleted.', $_REQUEST['deleted'] ), number_format_i18n( $_REQUEST['deleted'] ) );
			unset($_REQUEST['deleted']);
		}

		if ( isset($_REQUEST['trashed']) && (int) $_REQUEST['trashed'] ) {
			$messages[] = sprintf( _n( 'Item moved to the Trash.', '%s items moved to the Trash.', $_REQUEST['trashed'] ), number_format_i18n( $_REQUEST['trashed'] ) );
			$ids = isset($_REQUEST['ids']) ? $_REQUEST['ids'] : 0;
			$undo_url = wp_nonce_url( add_query_arg( array( 'doaction' => 'undo', 'action' => 'untrash', 'ids' => $ids ) ), 'bulk-posts' );
			$messages[] = '<a href="' . esc_url( $undo_url ) . '">' . __('Undo') . '</a>';
			unset($_REQUEST['trashed']);
		}

		if ( isset($_REQUEST['untrashed']) && (int) $_REQUEST['untrashed'] ) {
			$messages[] = sprintf( _n( 'Item restored from the Trash.', '%s items restored from the Trash.', $_REQUEST['untrashed'] ), number_format_i18n( $_REQUEST['untrashed'] ) );
			unset($_REQUEST['undeleted']);
		}
		?>
		<div id="message" class="updated"><p>
		<?php
		echo join( ' ', $messages ); unset( $messages );
		$_SERVER['REQUEST_URI'] = remove_query_arg( array('locked', 'skipped', 'updated', 'deleted', 'trashed', 'untrashed'), $_SERVER['REQUEST_URI'] );
		echo '</p></div>';
	}

	public function get_bulk_actions() {
		$actions = array();

		if ( $this->is_trash )
			$actions['untrash'] = __( 'Restore' );

		if ( $this->is_draft )
			$actions['show'] = __( 'Publish', 'wpsc' );
		elseif ( $this->is_all || $this->is_publish )
			$actions['hide'] = __( 'Mark as Draft', 'wpsc' );

		$actions['edit'] = __( 'Edit', 'wpsc' );

		if ( $this->is_trash || !EMPTY_TRASH_DAYS )
			$actions['delete'] = __( 'Delete Permanently' );
		else
			$actions['trash'] = __( 'Move to Trash' );

		return $actions;
	}

	public function bulk_actions( $which = '' ) {
		$screen = get_current_screen();

		if ( is_null( $this->_actions ) ) {
			$no_new_actions = $this->_actions = $this->get_bulk_actions();
			// This filter can currently only be used to remove actions.
			$this->_actions = apply_filters( 'bulk_actions-' . $screen->id, $this->_actions );
			$this->_actions = array_intersect_assoc( $this->_actions, $no_new_actions );
			$two = '';
		} else {
			$two = '2';
		}

		if ( empty( $this->_actions ) )
			return;

		echo '<input type="hidden" name="bulk_action_nonce" value="' . wp_create_nonce( 'wpsc_product_variations_bulk_action' ) .'" />';
		echo "<select name='bulk_action$two'>\n";
		echo "<option value='-1' selected='selected'>" . __( 'Bulk Actions' ) . "</option>\n";

		foreach ( $this->_actions as $name => $title ) {
			$class = 'edit' == $name ? ' class="hide-if-no-js"' : '';

			echo "\t<option value='$name'$class>$title</option>\n";
		}

		echo "</select>\n";

		submit_button( __( 'Apply' ), 'action', false, false, array( 'id' => "doaction$two" ) );
		echo "\n";
	}

	public function current_action() {
		if ( isset( $_REQUEST['bulk_action'] ) && -1 != $_REQUEST['bulk_action'] )
			return $_REQUEST['bulk_action'];

		if ( isset( $_REQUEST['bulk_action2'] ) && -1 != $_REQUEST['bulk_action2'] )
			return $_REQUEST['bulk_action2'];

		return false;
	}

	private function count_variations() {
		global $wpdb;
		$query = $wpdb->prepare( "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = 'wpsc-product' AND post_parent = %d GROUP BY post_status", $this->product_id );
		$results = $wpdb->get_results( $query );

		$return = array();
		foreach ( get_post_stati() as $state )
			$stats[$state] = 0;

		foreach ( $results as $row ) {
			$return[$row->post_status] = $row->num_posts;
		}

		return (object) $return;
	}

	public function get_views() {
		global $locked_post_status;
		$parent = get_post( $this->product_id );
		$avail_post_stati = get_available_post_statuses( 'wpsc-product' );
		$post_type_object = get_post_type_object( 'wpsc-product' );
		$post_type = $post_type_object->name;
		$url_base = add_query_arg( array(
				'action' => 'wpsc_product_variations_table',
				'product_id' => $_REQUEST['product_id'],
				'_wpnonce' => wp_create_nonce( 'wpsc_product_variations_table' ),
			), admin_url( 'admin-ajax.php' ) );

		if ( !empty($locked_post_status) )
			return array();

		$status_links = array();
		$num_posts = $this->count_variations();
		$class = '';

		$current_user_id = get_current_user_id();

		if ( isset( $num_posts->inherit ) ) {
			$key = $parent->post_status;
			if ( ! isset( $num_posts->$key ) )
				$num_posts->$key = 0;

			$num_posts->$key += $num_posts->inherit;
			unset( $num_posts->inherit );
		}

		$total_posts = array_sum( (array) $num_posts );

		// Subtract post types that are not included in the admin all list.
		foreach ( get_post_stati( array('show_in_admin_all_list' => false) ) as $state ) {
			if ( isset( $num_posts->$state ) )
				$total_posts -= $num_posts->$state;
		}

		$class = empty( $class ) && empty( $_REQUEST['post_status'] ) && empty( $_REQUEST['show_sticky'] ) ? ' class="current"' : '';
		$status_links['all'] = "<a href='{$url_base}'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_posts, 'posts' ), number_format_i18n( $total_posts ) ) . '</a>';

		foreach ( get_post_stati(array('show_in_admin_status_list' => true), 'objects') as $status ) {
			$class = '';

			$status_name = $status->name;

			if ( $status_name == 'publish' )
				continue;

			if ( !in_array( $status_name, $avail_post_stati ) )
				continue;

			if ( empty( $num_posts->$status_name ) ) {
				if ( isset( $_REQUEST['post_status'] ) && $status_name == $_REQUEST['post_status'] )
					$num_posts->$_REQUEST['post_status'] = 0;
				else
					continue;
			}

			if ( isset($_REQUEST['post_status']) && $status_name == $_REQUEST['post_status'] )
				$class = ' class="current"';

			$status_links[$status_name] = "<a href='" . esc_url( add_query_arg( 'post_status', $status_name, $url_base ) ) ."'$class>" . sprintf( translate_nooped_plural( $status->label_count, $num_posts->$status_name ), number_format_i18n( $num_posts->$status_name ) ) . '</a>';
		}

		return $status_links;
	}

	public function set_bulk_edited_items( $item_ids ) {
		$this->prepare_items();
		$this->is_bulk_edit = true;
		foreach ( $this->items as $key => $item ) {
			if ( in_array( $item->ID, $item_ids ) ) {
				$this->bulk_edited_items[] = $item;
				unset( $this->items[$key] );
			}
		}
		$this->bulk_edited_item_ids = $item_ids;
	}

	private function display_bulk_edit_row() {
		$style = $this->is_bulk_edit ? '' : ' style="display:none";';
		$classes = 'wpsc-bulk-edit';
		if ( $this->is_bulk_edit )
			$classes .= ' active';
		echo "<tr{$style} class='{$classes}'>";
		list( $columns, $hidden ) = $this->get_column_info();
		foreach ( $columns as $column_name => $column_display_name ) {
			$class = "class='$column_name column-$column_name inline-edit-row'";
			$style = '';

			if ( in_array( $column_name, $hidden ) )
				$style = ' style="display:none;"';

			$attributes = "$class$style";

			if ( $column_name == 'cb' )
				echo '<td></td>';
			elseif ( method_exists( $this, 'bulk_edit_column_' . $column_name ) ) {
				echo "<td $attributes>";
				echo call_user_func( array( &$this, 'bulk_edit_column_' . $column_name ) );
				echo "</td>";
			}
		}
		echo '</tr>';
		$this->shipping_editor();
	}

	public function bulk_edit_column_title() {
		?>
		<div class="wpsc-bulk-edit-items">
			<?php foreach ( $this->bulk_edited_items as $item ):
					$title = implode( ', ', $this->object_terms_cache[$item->ID] );
			?>
				<div class="wpsc-bulk-edit-item">
					<span>
						<input type="checkbox" name="wpsc_bulk_edit[post][]" checked="checked" value="<?php echo $item->ID; ?>" />
					</span>
					<strong>
						<a class="row-title" href="<?php echo get_edit_post_link( $item->ID ); ?>" title="<?php esc_attr_e( 'Edit this variation', 'wpsc' ) ?>"><?php echo esc_html( $title ); ?></a>
					</strong>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	public function bulk_edit_column_stock() {
		?>
			<input class="wpsc-bulk-edit-fields" type="checkbox" name="wpsc_bulk_edit[fields][stock]" value="1" />
			<input tabindex="101" type="text" name="wpsc_bulk_edit[stock]" value="" />
		<?php
	}

	public function bulk_edit_column_price() {
		?>
			<input class="wpsc-bulk-edit-fields" type="checkbox" name="wpsc_bulk_edit[fields][price]" value="1" />
			<input tabindex="103" type="text" name="wpsc_bulk_edit[price]" value="" />
		<?php
	}

	public function bulk_edit_column_tax() {
		?>
			<input class="wpsc-bulk-edit-fields" type="checkbox" name="wpsc_bulk_edit[fields][wpec_taxes_taxable_amount]" value="1" />
			<input tabindex="102" type="text" name="wpsc_bulk_edit[product_metadata][wpec_taxes_taxable_amount]" value="" />
		<?php
	}

	public function bulk_edit_column_sku() {
		?>
			<input class="wpsc-bulk-edit-fields" type="checkbox" name="wpsc_bulk_edit[fields][sku]" value="1" />
			<input tabindex="105" type="text" name="wpsc_bulk_edit[sku]" value="" />
		<?php
	}

	public function bulk_edit_column_sale_price() {
		$sale_price = get_product_meta( $this->product_id, 'special_price', true );
		?>
			<input class="wpsc-bulk-edit-fields" type="checkbox" name="wpsc_bulk_edit[fields][sale_price]" value="1" />
			<input tabindex="104" type="text" name="wpsc_bulk_edit[sale_price]" value="">
		<?php
	}

	public function extra_tablenav( $which ) {
		$post_type_object = get_post_type_object( 'wpsc-product' );
		?><div class="alignleft actions"><?php
		if ( $this->is_trash && current_user_can( $post_type_object->cap->edit_others_posts ) ) {
			submit_button( __( 'Empty Trash' ), 'button-secondary apply', 'delete_all', false );
		}
		?></div><?php
	}
}
