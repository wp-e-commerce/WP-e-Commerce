<?php
require_once( WPSC_TE_V2_CLASSES_PATH . '/cart-item-table-form.php' );

class WPSC_Cart_Item_Table_Widget_Form extends WPSC_Cart_Item_Table_Form {
	public function __construct() {
		parent::__construct();

		$this->show_shipping     = false;
		$this->show_tax          = false;
		$this->show_total        = false;
		$this->show_thumbnails   = false;
		$this->show_coupon_field = false;

		$this->columns['quantity'] = _x( 'Qty', 'quantity abbreviation', 'wp-e-commerce' );
		unset( $this->columns['unit_price'] );
	}

	protected function before_table() {
?>
<!-- WP eCommerce Cart Widget Form Begins -->
<form class="wpsc-form wpsc-cart-form" action="<?php echo esc_url( wpsc_get_cart_url() ); ?>" method="post">
<?php
	}

	protected function after_table() {
?>
	<div class="wpsc-form-actions bottom">
		<?php wpsc_begin_checkout_button(); ?>
		<?php wpsc_form_hidden( '_wp_nonce', wp_create_nonce( 'wpsc-cart-update' ) ); ?>
	</div>
</form>
<?php
	}

	protected function tfoot_append() {
		$prev = isset( $_REQUEST['prev'] ) ? esc_attr( $_REQUEST['prev'] ) : '';
		$clear_cart_url = add_query_arg( array(
				'prev'      => $prev,
				'_wp_nonce' => wp_create_nonce( 'wpsc-clear-cart' ),
			),
		 	wpsc_get_cart_url( 'clear' )
		);
		?>
		<tr class="wpsc-cart-item-table-actions">
			<td></td>
			<td colspan="<?php echo count( $this->columns ) - 1; ?>">
				<a class="wpsc-button wpsc-button-small wpsc-clear-cart" href="<?php echo esc_url( $clear_cart_url ); ?>"><?php esc_html_e( 'Clear Cart', 'wp-e-commerce' ); ?></a>
				<input type="hidden" name="action" value="update_quantity" />
				<input type="hidden" name="prev" value="<?php echo $prev; ?>">
			</td>
		</tr>
		<?php
	}
}
