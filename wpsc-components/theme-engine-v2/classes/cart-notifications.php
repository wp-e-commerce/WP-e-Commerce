<?php

/**
 * Cart Notifications
 *
 * @since 4.0
 */
class WPSC_Cart_Notifications {

	/**
	 * wpsc_cart instance
	 * @since 4.0.0
	 * @var wpsc_cart
	 */
	protected $wpsc_cart;

	/**
	 * Return the singleton instance
	 * @since  4.0.0
	 * @return WPSC_Template_Engine
	 */
	public static function initiate() {

		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 4.0.0
	 */
	public function __construct( wpsc_cart $wpsc_cart, $debug = false ) {
		$this->wpsc_cart = $wpsc_cart;
		$this->debug = $debug;
	}

	/**
	 * Handles enqueueing the Cart CSS.
	 *
	 * It is ok to be in the footer since the elements are all hidden/non-existent
	 * when the page initially loads.
	 *
	 * @since  4.0.0
	 *
	 * @return $this
	 */
	public function enqueue_css() {

		// This is ok to be in the footer since the elements are all hidden/non-existent.
		wp_enqueue_style( 'wpsc-cart-notifications' );

		return $this;
	}

	/**
	 * Handles localizing the data we want to pass to the Cart JS.
	 *
	 * @since  4.0.0
	 *
	 * @return $this
	 */
	public function localize_data() {

		wpsc_localize_script( 'wpsc-cart-notifications', 'cartNotifications', array(
			'debug'     => $this->debug,
			'ajaxurl'   => admin_url( 'admin-ajax.php' ),
			'baseRoute' => esc_url( rest_url( 'wpsc/v1' ) ),
			'apiNonce'  => wp_create_nonce( 'wp_rest' ),
			'currency'  => $this->prepare_currency_vars_for_js(),
			'CartView'  => array(
				'items'  => $this->prepare_cart_items_for_js(),
				'status' => $this->prepare_cart_status_for_js(),
			),
			'strings'     => apply_filters( 'wpsc_cart_notification_strings', array(
				'status_added'   => _x( 'item(s) added', 'Number of items added to the shopping cart', 'wp-e-commerce' ),
				'status_removed' => _x( 'item(s) removed', 'Number of items removed from the shopping cart', 'wp-e-commerce' ),
				'status_none'    => __( 'Your item(s)', 'wp-e-commerce' ),
				'sure_remove'    => __( 'Are you sure you want to remove this item from your cart?', 'wp-e-commerce' ),
			) ),
		) );

		return $this;
	}

	/**
	 * Handles outputting the JS templates we'll neeed available for the Cart JS.
	 *
	 * @since  4.0.0
	 *
	 * @return $this
	 */
	public function output_js_templates() {
		$cart_button_hidden = wpsc_is_cart() || wpsc_is_checkout() || ! wpsc_cart_has_items();

		if ( apply_filters( 'wpsc_do_cart_button', true ) ) : ?>
			<button id="wpsc-view-cart-button" class="wpsc-button wpsc-button-primary wpsc-view-cart<?php if ( $cart_button_hidden ) : ?> wpsc-hide<?php endif; ?>" name=""><i class="wpsc-icon-white wpsc-icon-shopping-cart"></i><span class="wpsc-hide"><?php _e( 'Cart', 'wp-e-commerce' ); ?></span></button>
		<?php endif; ?>
		<script type="text/html" id="tmpl-wpsc-modal">
			<div class="wpsc-hide" id="wpsc-modal-overlay"></div>
			<div class="wpsc-hide" id="wpsc-cart-notification"></div>
		</script>
		<script type="text/html" id="tmpl-wpsc-modal-inner">
			<?php wpsc_get_template_part( 'js-template', 'cart-modal' ); ?>
		</script>
		<script type="text/html" id="tmpl-wpsc-modal-product">
			<?php wpsc_get_template_part( 'js-template', 'cart-modal-product' ); ?>
		</script>
		<script type="text/html" id="tmpl-wpsc-currency-format">
			<?php
			// Rejig the currency sign location
			switch ( get_option( 'currency_sign_location', 3 ) ) {
				case 1:
					?>{{ data.amount }}{{ data.code }}{{ data.symbol }}<?php
					break;

				case 2:
					?>{{ data.amount }} {{ data.code }}{{ data.symbol }}<?php
					break;

				case 4:
					?>{{ data.code }}{{ data.symbol }}  {{ data.amount }}<?php
					break;

				case 3:
				default:
					?>{{ data.code }} {{ data.symbol }}{{ data.amount }}<?php
					break;
			} ?>
		</script>
		<?php

		return $this;
	}

	/**
	 * Gets the wpsc_cart data needed for the Cart JS.
	 *
	 * @since  4.0.0
	 *
	 * @return array  Array of prepared cart items.
	 */
	protected function prepare_cart_items_for_js() {
		$prepared = array();
		foreach ( $this->wpsc_cart->cart_items as $key => $item ) {
			$prepared[] = $this->prepare_cart_item_for_js( $item, $key );
		}

		return $prepared;
	}

	/**
	 * Gets the required wpsc_cart item data.
	 *
	 * @since  4.0.0
	 *
	 * @return array  Prepared cart item array.
	 */
	protected function prepare_cart_item_for_js( $item, $key ) {

		/*
		 * @todo Most of the following is copied from WPSC_Cart_Item_Table::column_items(),
		 * but this should really be moved to a universally available functions/methods.
		 */

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
					$variations[]   = array(
						'label' => esc_html( $set_name ),
						'value' => esc_html( $variation_name ),
					);
				}
			}
		}

		$image = wpsc_has_product_thumbnail( $item->product_id )
			? wpsc_get_product_thumbnail( $item->product_id, 'archive' )
			: wpsc_product_no_thumbnail_image( 'archive', '', false );

		$remove_url = add_query_arg( '_wp_nonce', wp_create_nonce( "wpsc-remove-cart-item-{$key}" ), wpsc_get_cart_url( 'remove/' . absint( $key ) ) );

		$prepared = array(
			'id'             => $item->product_id,
			'nonce'          => wp_create_nonce( "wpsc-add-to-cart-{$item->product_id}" ),
			'deleteNonce'    => wp_create_nonce( "wpsc-remove-cart-item-{$item->product_id}" ),
			'url'            => $permalink,
			'price'          => $item->unit_price, // @todo correct property?
			'formattedPrice' => wpsc_format_currency( $item->unit_price ), // @todo correct property?
			'title'          => $product_name,
			'thumb'          => $image,
			'quantity'       => $item->quantity,
			'remove_url'     => $remove_url,
			'variations'     => $variations
		);

		return apply_filters( 'wpsc_prepared_cart_item_for_js', $prepared );
	}

	/**
	 * Gets the general cart status for the Cart JS.
	 *
	 * @since  4.0.0
	 *
	 * @return array  Prepared cart status array.
	 */
	protected function prepare_cart_status_for_js() {
		$subtotal = $this->wpsc_cart->calculate_subtotal();

		$shipping_total = '0'; // @todo figure out shipping

		$total = $subtotal + $shipping_total;

		return array(
			'subTotal'      => $shipping_total ? wpsc_format_currency( $subtotal ) : '',
			'shippingTotal' => $shipping_total ? wpsc_format_currency( $shipping_total ) : '',
			'total'         => $total,
		);
	}

	/**
	 * Gets the currency vars needed for the Cart JS.
	 *
	 * @since  4.0.0
	 *
	 * @return array  Prepared currency vars array.
	 */
	protected function prepare_currency_vars_for_js() {
		$isocode = false;

		$currency = new WPSC_Country( get_option( 'currency_type' ) );
		$display_currency_code = apply_filters( 'wpsc_format_currency_display_currency_code', false, $currency );

		if ( $display_currency_code ) {
			$currency_code = $currency->get_currency_code();
			$currency_symbol = '';
		} else {
			$currency_symbol = $currency->get_currency_symbol();
			$currency_code = '';
		}

		$currency_code = apply_filters( 'wpsc_format_currency_currency_code', $currency_code, $isocode );
		$currency_symbol = apply_filters( 'wpsc_format_currency_currency_symbol', $currency_symbol, $isocode );

		$decimal_sep = apply_filters( 'wpsc_format_currency_decimal_separator' , wpsc_get_option( 'decimal_separator' ), $isocode );
		$thousands_sep = apply_filters( 'wpsc_format_currency_thousands_separator', wpsc_get_option( 'thousands_separator' ), $isocode );

		// Maybe no decimal point, no decimals
		$currencies_without_fractions = WPSC_Payment_Gateways::currencies_without_fractions();

		$decimals = in_array( $currency_code, $currencies_without_fractions ) ? 0 : 2;
		$decimals = apply_filters( 'wpsc_modify_decimals', $decimals, $isocode );

		return array(
			'code'         => $currency_code,
			'symbol'       => $currency_symbol,
			'decimals'     => $decimals,
			'decimalSep'   => $decimal_sep,
			'thousandsSep' => $thousands_sep,
		);
	}
}
