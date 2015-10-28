<?php

require_once( WPSC_TE_V2_CLASSES_PATH . '/cart-item-table.php' );

class WPSC_Cart_Item_Table_Order extends WPSC_Cart_Item_Table {
	private $log;

	public function __construct( $id ) {
		parent::__construct();

		$this->show_tax        = true;
		$this->show_total      = true;
		$this->show_thumbnails = true;
		$this->show_shipping   = true;

		$this->log   = new WPSC_Purchase_Log( $id );
		$this->items = $this->get_items();
	}

	private function get_items() {
		$cart = $this->log->get_cart_contents();

		$items = array();

		foreach ( $cart as $item ) {

			$obj = new stdClass();
			$obj->product_id = $item->prodid;

			$variations = wpsc_get_product_terms( $item->prodid, 'wpsc-variation' );

			$obj->variation_values = array();

			foreach ( $variations as $term ) {
				$obj->variation_values[ (int) $term->parent ] = (int) $term->term_id;
			}

			$obj->sku          = get_post_meta( $item->prodid, '_wpsc_sku', true );
			$obj->quantity     = $item->quantity;
			$obj->unit_price   = $item->price;
			$obj->product_name = $item->name;
			$obj->id           = $item->id;

			$items[] = $obj;
		}

		return $items;
	}

	protected function get_total_shipping() {
		return $this->log->get( 'total_shipping' );
	}

	protected function get_subtotal() {
		$data = $this->log->get_gateway_data();
		return $data['subtotal'];
	}

	protected function get_tax() {
		$data = $this->log->get_gateway_data();
		return $data['tax'];
	}

	protected function get_total_price() {
		$data = $this->log->get_gateway_data();
		return $data['amount'];
	}

	protected function cart_item_description( $item, $key ) {
		parent::cart_item_description( $item, $key );
		if ( ! $this->log->is_transaction_completed() )
			return;

		$links = _wpsc_get_cart_item_downloadable_links( $item, $this->log );
?>
		<div class="wpsc-cart-item-downloadable">
<?php 	if ( count( $links ) === 1 ): ?>
			<strong><?php esc_html_e( 'Download link: ', 'wp-e-commerce'); ?></strong><br />
			<a href="<?php echo esc_url( $links[0]['url'] ); ?>"><?php echo esc_html( $links[0]['name'] ); ?></a>
<?php 	else: ?>
			<strong><?php esc_html_e( 'Digital Contents', 'wp-e-commerce' ); ?></strong>
			<ul>
<?php 		foreach ( $links as $link ): ?>
				<li><a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['name'] ); ?></a></li>
<?php 		endforeach; ?>
			</ul>
<?php	endif; ?>
		</div>
<?php
	}
}