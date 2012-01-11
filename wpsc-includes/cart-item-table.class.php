<?php

class WPSC_Cart_Item_Table
{
	private static $instance;

	public static function get_instance() {
		if ( empty( self::$instance ) )
			self::$instance = new WPSC_Cart_Item_Table();

		return self::$instance;
	}

	private $columns = array();

	private function __construct() {
		if (! isset($GLOBALS['wpsc_cart'] ) )
			$GLOBALS['wpsc_cart'] = new wpsc_cart();

		$this->columns = $this->get_columns();
		$this->prepare_cache();
	}

	private function prepare_cache() {
		global $wpsc_cart;
		$post_in = array();
		$parent_post_in = array();
		foreach ( $wpsc_cart->cart_items as $item ) {
			$post_in[] = $item->product_id;
		}

		get_posts( array( 'post__in' => $post_in, 'post_type' => 'wpsc-product', 'post_status' => 'any' ) );
	}

	private function get_table_classes() {
		return apply_filters( 'wpsc_cart_item_table_classes', array( 'wpsc-cart-item-table' ) );
	}

	private function get_columns() {
		$columns = array(
			'items'       => __( 'Items'     , 'wpsc' ),
			'unit_price'  => __( 'Unit Price', 'wpsc' ),
			'quantity'    => __( 'Quantity'  , 'wpsc' ),
			'item_total'  => __( 'Item Total', 'wpsc' ),
			'delete'      => '',
		);

		return apply_filters( 'wpsc_cart_item_table_columns', $columns );
	}

	public function print_column_headers() {
		foreach ( $this->columns as $name => $title ) {
			$title = apply_filters( 'wpsc_cart_item_table_column_title', $title, $name );
			$class = str_replace( '_', '-', $name );
			echo "<th class='{$class}' scope='col' id='wpsc-cart-item-table-{$name}'>" . esc_html( $title ) . "</th>";
		}
	}

	public function display_rows() {
		global $wpsc_cart;

		foreach ( $wpsc_cart->cart_items as $key => $item ) {
			$classes = apply_filters( 'wpsc_cart_item_classes', array( 'wpsc-cart-item' ), $item ) ;
			echo '<tr class="' . implode( ' ', $classes ) . '">';
			foreach( array_keys( $this->columns ) as $column ) {
				$class = str_replace( '_', '-', $column );
				echo '<td class="' . $class . '" id="wpsc-cart-item-' . absint( $item->product_id ) . '">';
				do_action( "wpsc_cart_item_table_column_{$column}", $item, $key );
				echo '</td>';
			}
			echo '</tr>';
		}
	}

	public function display() {
		global $wpsc_cart;
		$prev = isset( $_REQUEST['prev'] ) ? esc_attr( $_REQUEST['prev'] ) : '';
		$clear_cart_url = esc_attr( add_query_arg( 'prev', $prev, wpsc_get_cart_url( 'clear' ) ) );
		?>
			<table class="<?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
				<thead>
					<tr>
						<?php $this->print_column_headers(); ?>
					</tr>
				</thead>
				<tfoot>
					<tr class="wpsc-cart-subtotal-row">
						<th scope="row" colspan="<?php echo count( $this->columns ) - 2; ?>">
							<?php esc_html_e( 'Subtotal:' ,'wpsc' ); ?><br />
						</th>
						<td>
							<?php echo wpsc_format_price( $wpsc_cart->calculate_subtotal() ); ?>
						</td>
						<td></td>
					</tr>
					<tr class="wpsc-cart-item-actions">
						<td colspan="<?php echo count( $this->columns ) - 1; ?>">
							<a class="wpsc-clear-cart" href="<?php echo $clear_cart_url; ?>"><?php esc_html_e( 'Clear Cart', 'wpsc' ); ?></a>
							<input type="submit" class="wpsc-cart-update" value="<?php esc_html_e( 'Update Quantity', 'wpsc' ); ?>" />
							<input type="hidden" name="action" value="update_quantity" />
							<input type="hidden" name="prev" value="<?php echo $prev; ?> ">
						</td>
						<td></td>
					</tr>
				</tfoot>

				<tbody>
					<?php $this->display_rows(); ?>
				</tbody>
			</table>
		<?php
	}
}

function wpsc_cart_item_table_column_items( $item ) {
	$product = get_post( $item->product_id );

	if ( $product->post_parent )
		$permalink = wpsc_get_product_permalink( $product->post_parent );
	else
		$permalink = wpsc_get_product_permalink( $item->product_id );

	$variations = array();

	foreach ( $item->variation_values as $variation_set => $variation ) {
		$set_name = get_term_field( 'name', $variation_set, 'wpsc-variation' );
		$variation_name = get_term_field( 'name', $variation, 'wpsc-variation' );
		$variations[] = '<span>' . esc_html( $set_name ) . ':</span> ' . esc_html( $variation_name );
	}

	$variations = implode( ', ', $variations );

	$separator = '';
	if ( ! empty( $variations ) && ! empty( $item->sku ) )
		$separator = ' | ';

	?>
		<div class="wpsc-thumbnail wpsc-product-thumbnail">
			<?php if ( wpsc_has_product_thumbnail( $item->product_id ) ): ?>
				<?php echo wpsc_get_product_thumbnail( $item->product_id, 'cart' ); ?>
			<?php else: ?>
				<?php wpsc_product_no_thumbnail_image( 'cart' ); ?>
			<?php endif; ?>
		</div>
		<div class="wpsc-cart-item-description">
			<p><strong><a href="<?php echo $permalink; ?>"><?php echo esc_html( $item->product_name ); ?></a></strong></p>
			<p class="wpsc-cart-item-details">
				<?php if ( ! empty( $item->sku ) ): ?>
					<span class="wpsc-cart-item-sku"><span><?php esc_html_e( 'SKU', 'wpsc' ); ?>:</span> <?php echo esc_html( $item->sku ); ?></span>
				<?php endif ?>

				<?php if ( $separator ): ?>
					<span class="separator"><?php echo $separator; ?></span>
				<?php endif ?>

				<?php if ( ! empty( $variations ) ): ?>
					<span class="wpsc-cart-item-variations"><?php echo $variations; ?></span>
				<?php endif ?>
			</p>
		</div>
	<?php
}

function wpsc_cart_item_table_column_quantity( $item, $key ) {
	?>
	<input size="3" type="text" name="quantity[<?php echo absint( $key ); ?>]" value="<?php echo absint( $item->quantity ); ?>" />
	<?php
}

function wpsc_cart_item_table_column_unit_price( $item ) {
	echo wpsc_format_price( $item->unit_price );
}

function wpsc_cart_item_table_column_item_total( $item ) {
	echo wpsc_format_price( $item->unit_price * $item->quantity );
}

function wpsc_cart_item_table_column_delete( $item, $key ) {
	$text = esc_html_x( 'Delete', 'cart item table', 'wpsc' );
	?>
	<a href="<?php wpsc_cart_url( 'delete/' . absint( $key ) ); ?>"><img src="<?php echo wpsc_locate_theme_file_uri( 'images/delete-cart-item.gif' ); ?>" alt="<?php echo $text; ?>" title="<?php echo $text; ?>" /></a>
	<?php
}

add_action( 'wpsc_cart_item_table_column_items'       , 'wpsc_cart_item_table_column_items'             );
add_action( 'wpsc_cart_item_table_column_quantity'    , 'wpsc_cart_item_table_column_quantity'  , 10, 2 );
add_action( 'wpsc_cart_item_table_column_unit_price'  , 'wpsc_cart_item_table_column_unit_price'        );
add_action( 'wpsc_cart_item_table_column_item_total'  , 'wpsc_cart_item_table_column_item_total'        );
add_action( 'wpsc_cart_item_table_column_delete'      , 'wpsc_cart_item_table_column_delete'    , 10, 2 );