<?php

require_once( WPSC_TE_V2_CLASSES_PATH . '/table.php' );

class WPSC_Cart_Item_Table extends WPSC_Table {
	private static $instance;

	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new WPSC_Cart_Item_Table();
		}

		return self::$instance;
	}

	public $columns           = array();
	public $show_shipping     = true;
	public $show_tax          = true;
	public $show_total        = true;
	public $show_thumbnails   = true;
	public $show_coupon_field = true;

	public function __construct() {
		global $wpsc_cart;

		parent::__construct();

		if ( ! isset( $GLOBALS['wpsc_cart'] ) ) {
			$GLOBALS['wpsc_cart'] = new wpsc_cart();
		}

		$this->prepare_cache();

		$this->columns = array(
			'items'       => __( 'Items'     , 'wp-e-commerce' ),
			'unit_price'  => __( 'Unit Price', 'wp-e-commerce' ),
			'quantity'    => __( 'Quantity'  , 'wp-e-commerce' ),
			'item_total'  => __( 'Item Total', 'wp-e-commerce' ),
		);

		$this->columns = apply_filters( 'wpsc_cart_item_table_columns', $this->columns );

		$this->items = $wpsc_cart->cart_items;
	}

	private function prepare_cache() {
		$post_in = array();

		foreach ( $this->items as $item ) {
			$post_in[] = $item->product_id;
		}

		get_posts( array( 'post__in' => $post_in, 'post_type' => 'wpsc-product', 'post_status' => 'any' ) );
	}

	protected function get_table_classes() {
		$classes   = parent::get_table_classes();
		$classes[] = 'wpsc-cart-item-table';
		return $classes;
	}

	protected function get_columns() {

	}

	protected function column_default( $item, $key, $column ) {
		do_action( "wpsc_cart_item_table_column_{$column}", $item, $key );
	}

	protected function before_table() {
		do_action( 'wpsc_cart_item_table_before' );
	}

	public function display() {
		global $wpsc_cart;

		$this->before_table();
		include( WPSC_TE_V2_SNIPPETS_PATH . '/cart-item-table/display.php' );
		$this->after_table();
	}

	protected function get_total_shipping() {
		global $wpsc_cart;
		return $wpsc_cart->calculate_total_shipping();
	}

	protected function get_subtotal() {
		global $wpsc_cart;
		return $wpsc_cart->calculate_subtotal();
	}

	protected function get_total_price() {
		global $wpsc_cart;
		return $wpsc_cart->calculate_total_price();
	}

	protected function get_total_discount() {
		return wpsc_coupon_amount( false );
	}

	protected function get_tax() {
		return wpsc_cart_tax( false );
	}

	protected function tfoot_append() {
		do_action( 'wpsc_cart_item_table_tfoot' );
	}

	protected function after_table() {
		do_action( 'wpsc_cart_item_table_after' );
	}

	private function show_shipping_style() {
		if ( ! $this->show_shipping ) {
			echo 'style="display:none;"';
		}
	}

	private function show_tax_style() {
		if ( ! $this->show_tax ) {
			echo 'style="display:none;"';
		}
	}

	private function show_total_style() {
		if ( ! $this->show_total ) {
			echo 'style="display:none;"';
		}
	}

	protected function column_items( $item, $key ) {
		$product      = get_post( $item->product_id );
		$product_name = $item->product_name;

		if ( $product->post_parent ) {
			$permalink    = wpsc_get_product_permalink( $product->post_parent );
			$product_name = get_post_field( 'post_title', $product->post_parent );
		} else {
			$permalink = wpsc_get_product_permalink( $item->product_id );
		}

		$variations = array();

		if ( is_array( $item->variation_values ) ) {
			foreach ( $item->variation_values as $variation_set => $variation ) {
				$set_name       = get_term_field( 'name', $variation_set, 'wpsc-variation' );
				$variation_name = get_term_field( 'name', $variation    , 'wpsc-variation' );

				if ( ! is_wp_error( $set_name ) && ! is_wp_error( $variation_name ) ) {
					$variations[]   = '<span>' . esc_html( $set_name ) . ':</span> ' . esc_html( $variation_name );
				}
			}
		}

		$variations = implode( ', ', $variations );

		$separator = '';

		if ( ! empty( $variations ) && ! empty( $item->sku ) ) {
			$separator = ' | ';
		}

		?>
			<?php if ( $this->show_thumbnails ) : ?>
				<div class="wpsc-thumbnail wpsc-product-thumbnail">
					<?php if ( wpsc_has_product_thumbnail( $item->product_id ) ) : ?>
						<?php echo wpsc_get_product_thumbnail( $item->product_id, 'cart' ); ?>
					<?php else : ?>
						<?php wpsc_product_no_thumbnail_image( 'cart' ); ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<div class="wpsc-cart-item-description">
				<div class="wpsc-cart-item-title">
					<strong><a href="<?php echo $permalink; ?>"><?php echo esc_html( $product_name ); ?></a></strong>
				</div>
				<div class="wpsc-cart-item-details">
					<?php if ( ! empty( $item->sku ) ) : ?>
						<span class="wpsc-cart-item-sku"><span><?php esc_html_e( 'SKU', 'wp-e-commerce' ); ?>:</span> <?php echo esc_html( $item->sku ); ?></span>
					<?php endif ?>

					<?php if ( $separator ) : ?>
						<span class="separator"><?php echo $separator; ?></span>
					<?php endif ?>

					<?php if ( ! empty( $variations ) ) : ?>
						<span class="wpsc-cart-item-variations"><?php echo $variations; ?></span>
					<?php endif ?>
				</div>
				<?php $this->cart_item_description( $item, $key ); ?>
			</div>
		<?php
	}

	protected function cart_item_description( $item, $key ) {
		do_action( 'wpsc_cart_item_description', $item, $key );
	}

	protected function column_quantity( $item, $key ) {
		echo $item->quantity;
	}

	protected function column_unit_price( $item ) {
		echo wpsc_format_currency( $item->unit_price );
	}

	protected function column_item_total( $item ) {
		echo wpsc_format_currency( $item->unit_price * $item->quantity );
	}
}

