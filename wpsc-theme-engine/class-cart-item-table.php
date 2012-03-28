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
		return apply_filters( 'wpsc_cart_item_table_classes', array( 'wpsc-cart-item-table', 'wpsc-table' ) );
	}

	private function get_columns() {
		$columns = array(
			'items'       => __( 'Items'     , 'wpsc' ),
			'unit_price'  => __( 'Unit Price', 'wpsc' ),
			'quantity'    => __( 'Quantity'  , 'wpsc' ),
			'item_total'  => __( 'Item Total', 'wpsc' ),
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
		$clear_cart_url = add_query_arg( array(
				'prev'      => $prev,
				'_wp_nonce' => wp_create_nonce( 'wpsc-clear-cart' ),
			),
		 	wpsc_get_cart_url( 'clear' )
		);
		?>
		<form class="wpsc-form wpsc-cart-form" action="<?php echo esc_url( wpsc_get_cart_url() ); ?>" method="post">
			<table class="<?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
				<thead>
					<tr>
						<?php $this->print_column_headers(); ?>
					</tr>
				</thead>
				<tfoot>
					<tr class="wpsc-cart-subtotal-row">
						<th scope="row" colspan="<?php echo count( $this->columns ) - 1; ?>">
							<?php esc_html_e( 'Subtotal:' ,'wpsc' ); ?><br />
						</th>
						<td>
							<?php echo wpsc_format_price( $wpsc_cart->calculate_subtotal() ); ?>
						</td>
					</tr>
					<tr class="wpsc-cart-item-actions">
						<td></td>
						<td colspan="<?php echo count( $this->columns ) - 1; ?>">
							<a class="wpsc-button wpsc-button-small wpsc-clear-cart" href="<?php echo $clear_cart_url; ?>"><?php esc_html_e( 'Clear Cart', 'wpsc' ); ?></a>
							<input type="submit" class="wpsc-button wpsc-button-small wpsc-cart-update" name="update_quantity" value="<?php esc_html_e( 'Update Quantity', 'wpsc' ); ?>" />
							<input type="hidden" name="action" value="update_quantity" />
							<input type="hidden" name="prev" value="<?php echo $prev; ?> ">
						</td>
					</tr>
				</tfoot>

				<tbody>
					<?php $this->display_rows(); ?>
				</tbody>
			</table>
			<div class="wpsc-form-actions">
				<?php wpsc_keep_shopping_button(); ?>
				<?php wpsc_form_button( '', __( 'Begin Checkout', 'wpsc' ), array( 'class' => 'wpsc-button wpsc-button-success', 'icon' => array( 'white', 'ok-sign' ) ) ); ?>
				<?php wpsc_form_hidden( '_wp_nonce', wp_create_nonce( 'wpsc-cart-update' ) ); ?>
			</div>
		</form>
		<?php
	}
}

function wpsc_cart_item_table_column_items( $item, $key ) {
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

	$remove_url = add_query_arg( '_wp_nonce', wp_create_nonce( "wpsc-remove-cart-item-{$key}" ), wpsc_get_cart_url( 'remove/' . absint( $key ) ) );
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
			<p>
				<a alt="<?php esc_attr_e( 'Remove from cart', 'wpsc' ); ?>" title="<?php esc_attr_e( 'Remove from cart', 'wpsc' ); ?>" class="wpsc-button wpsc-button-mini" href="<?php echo esc_url( $remove_url ); ?>"><i class="wpsc-icon-trash"></i> <?php esc_html_e( 'Remove', 'wpsc' ); ?></a>
			</p>
		</div>
	<?php
}

function wpsc_cart_item_table_column_quantity( $item, $key ) {
	wpsc_form_input( "quantity[{$key}]", $item->quantity, array( 'class' => 'wpsc-cart-quantity-input', 'id' => "wpsc-cart-quantity-input-{$key}" ) );
}

function wpsc_cart_item_table_column_unit_price( $item ) {
	echo wpsc_format_price( $item->unit_price );
}

function wpsc_cart_item_table_column_item_total( $item ) {
	echo wpsc_format_price( $item->unit_price * $item->quantity );
}

add_action( 'wpsc_cart_item_table_column_items'       , 'wpsc_cart_item_table_column_items'     , 10, 2 );
add_action( 'wpsc_cart_item_table_column_quantity'    , 'wpsc_cart_item_table_column_quantity'  , 10, 2 );
add_action( 'wpsc_cart_item_table_column_unit_price'  , 'wpsc_cart_item_table_column_unit_price'        );
add_action( 'wpsc_cart_item_table_column_item_total'  , 'wpsc_cart_item_table_column_item_total'        );