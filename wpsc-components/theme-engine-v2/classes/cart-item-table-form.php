<?php
require_once( WPSC_TE_V2_CLASSES_PATH . '/cart-item-table.php' );

class WPSC_Cart_Item_Table_Form extends WPSC_Cart_Item_Table {
	private static $instance;

	public static function get_instance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new WPSC_Cart_Item_Table_Form();
		}

		return self::$instance;
	}

	public function __construct() {
		parent::__construct();
	}

	protected function before_table() {
?>
<!-- WP eCommerce Cart Form Begins -->
<form class="wpsc-form wpsc-cart-form" action="<?php echo esc_url( wpsc_get_cart_url() ); ?>" method="post">
	<div class="wpsc-form-actions top">
		<?php do_action( 'wpsc_cart_item_table_form_actions_left', self::$instance, 'top' ); ?>

		<?php wpsc_begin_checkout_button(); ?>
		<?php wpsc_form_hidden( '_wp_nonce', wp_create_nonce( 'wpsc-cart-update' ) ); ?>

		<?php do_action( 'wpsc_cart_item_table_form_actions_right', self::$instance, 'top' ); ?>

	</div>
<?php
		parent::before_table();
	}

	protected function after_table() {
?>
	<div class="wpsc-form-actions bottom">
		<?php do_action( 'wpsc_cart_item_table_form_actions_left', self::$instance, 'bottom' ); ?>

		<?php wpsc_begin_checkout_button(); ?>
		<?php wpsc_form_hidden( '_wp_nonce', wp_create_nonce( 'wpsc-cart-update' ) ); ?>

		<?php do_action( 'wpsc_cart_item_table_form_actions_right', self::$instance, 'bottom' ); ?>
	</div>
	<?php parent::after_table(); ?>
</form>
<!-- WP eCommerce Cart Form Ends -->
<?php
	}

	protected function tfoot_append() {
		?>
		<tr class="wpsc-cart-item-table-actions">
			<td colspan="<?php echo count( $this->columns ); ?>">
				<?php do_action( 'wpsc_cart_item_table_tfoot_actions' ); ?>
			</td>
		</tr>
		<?php
		parent::tfoot_append();
	}

	protected function cart_item_description( $item, $key ) {
		$remove_url = add_query_arg( '_wp_nonce', wp_create_nonce( "wpsc-remove-cart-item-{$key}" ), wpsc_get_cart_url( 'remove/' . absint( $key ) ) );
		?>
		<div class="wpsc-cart-item-row-actions">
			<a alt="<?php esc_attr_e( 'Remove from cart', 'wp-e-commerce' ); ?>" class="wpsc-button wpsc-button-mini" href="<?php echo esc_url( $remove_url ); ?>"><i class="wpsc-icon-trash"></i> <?php esc_html_e( 'Remove', 'wp-e-commerce' ); ?></a>
		</div>
		<?php
		parent::cart_item_description( $item, $key );
	}

	protected function column_quantity( $item, $key ) {
		wpsc_form_input( "quantity[{$key}]", $item->quantity, array( 'class' => 'wpsc-cart-quantity-input', 'id' => "wpsc-cart-quantity-input-{$key}" ) );
	}
}