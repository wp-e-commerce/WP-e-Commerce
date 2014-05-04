<?php
require_once( WPSC_TE_V2_CLASSES_PATH . '/cart-item-table.php' );

class WPSC_Cart_Item_Table_Form extends WPSC_Cart_Item_Table {
	private static $instance;

	public static function get_instance() {
		if ( empty( self::$instance ) )
			self::$instance = new WPSC_Cart_Item_Table_Form();

		return self::$instance;
	}

	public function __construct() {
		parent::__construct();
	}

	protected function before_table() {
?>
<!-- WP e-Commerce Cart Form Begins -->
<form class="wpsc-form wpsc-cart-form" action="<?php echo esc_url( wpsc_get_cart_url() ); ?>" method="post">
	<div class="wpsc-form-actions top">
		<?php wpsc_keep_shopping_button(); ?>
		<?php wpsc_begin_checkout_button(); ?>
		<?php wpsc_form_hidden( '_wp_nonce', wp_create_nonce( 'wpsc-cart-update' ) ); ?>
	</div>
<?php
		parent::before_table();
	}

	protected function after_table() {
?>
	<div class="wpsc-form-actions bottom">
		<?php wpsc_keep_shopping_button(); ?>
		<?php wpsc_begin_checkout_button(); ?>
		<?php wpsc_form_hidden( '_wp_nonce', wp_create_nonce( 'wpsc-cart-update' ) ); ?>
	</div>
	<?php parent::after_table(); ?>
</form>
<!-- WP e-Commerce Cart Form Ends -->
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
				<a class="wpsc-button wpsc-button-small wpsc-clear-cart" href="<?php echo $clear_cart_url; ?>"><?php esc_html_e( 'Clear Cart', 'wpsc' ); ?></a>
				<input type="submit" class="wpsc-button wpsc-button-small wpsc-cart-update" name="update_quantity" value="<?php esc_html_e( 'Update Quantity', 'wpsc' ); ?>" />
				<input type="hidden" name="action" value="update_quantity" />
				<input type="hidden" name="prev" value="<?php echo $prev; ?> ">
			</td>
		</tr>
		<?php
		parent::tfoot_append();
	}

	protected function cart_item_description( $item, $key ) {
		$remove_url = add_query_arg( '_wp_nonce', wp_create_nonce( "wpsc-remove-cart-item-{$key}" ), wpsc_get_cart_url( 'remove/' . absint( $key ) ) );
		?>
		<div class="wpsc-cart-item-row-actions">
			<a alt="<?php esc_attr_e( 'Remove from cart', 'wpsc' ); ?>" title="<?php esc_attr_e( 'Remove from cart', 'wpsc' ); ?>" class="wpsc-button wpsc-button-mini" href="<?php echo esc_url( $remove_url ); ?>"><i class="wpsc-icon-trash"></i> <?php esc_html_e( 'Remove', 'wpsc' ); ?></a>
		</div>
		<?php
		parent::cart_item_description( $item, $key );
	}

	protected function column_quantity( $item, $key ) {
		wpsc_form_input( "quantity[{$key}]", $item->quantity, array( 'class' => 'wpsc-cart-quantity-input', 'id' => "wpsc-cart-quantity-input-{$key}" ) );
	}
}