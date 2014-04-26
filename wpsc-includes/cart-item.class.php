<?php
/**
 * WP eCommerce Cart Item class
 *
 * This is the class for WP eCommerce Cart Items,
 * The Cart Items class handles the same, but for cart items themselves.
 *
 *
 * @package wp-e-commerce
 * @since 3.8
 * @subpackage wpsc-cart-classes
*/

/**
 * The WPSC Cart Items class
 */
class wpsc_cart_item {

	// Variation Cache
	private static $variation_cache;

	// each cart item contains a reference to the cart that it is a member of
	public $cart;

	// provided values
	public $product_id;
	public $variation_values;
	public $product_variations;
	public $variation_data;
	public $quantity = 1;
	public $provided_price;


	//values from the database
	public $product_name;
	public $category_list = array();
	public $category_id_list = array();
	public $unit_price;
	public $total_price;
	public $taxable_price = 0;
	public $tax = 0;
	public $weight = 0;
	public $shipping = 0;
	public $sku = null;
	public $product_url;
	public $image_id;
	public $thumbnail_image;
	public $custom_tax_rate = null;
	public $meta = array();

	private $item_meta = array();

	public $is_donation = false;
	public $apply_tax = true;
	public $priceandstock_id;

	// user provided values
	public $custom_message = null;
	public $custom_file = null;

	/**
	 * compare cart item meta
	 * @access public
	 * @param other cart item against which this items meta will be compared
	 * @return returns true if the cart item meta for this item is the same as is in the cart item in the argument
	 */
	function item_meta_equal( $other_cart_item ) {
		$my_item_meta_key    = serialize( $this->item_meta );
		$other_item_meta_key = serialize( $other_cart_item->item_meta );

		return strcmp( $my_item_meta_key, $other_item_meta_key ) == 0;
	}

	/**
	 * Add cart item meta value
	 *
	 * @access public
	 * @param meta key name
	 * @param meta key value
	 * @return previous meta value if it existed, nothing otherwise
	 */
	function delete_meta( $key ) {

		if ( isset( $this->item_meta[ $key ] ) ) {
			$value = $this->item_meta[ $key ];
			unset( $this->item_meta[ $key ] );
			return $value;
		}

		return;
	}

	/**
	 * update or add cart item meta value
	 * @access public
	 * @param meta key name
	 * @param meta key value
	 * @return previous meta value if it existed, null otherwise
	 */
	function update_meta($key,$value=null) {

		if ( ! isset( $value ) ) {
			$result = $this->delete_meta( $key );
		} else {
			$result = isset( $this->meta[ $key ] ) ? $this->meta[ $key ] : null;
			$this->item_meta[ $key ] = $value;
		}

		return $result;
	}

	/**
	 * Get cart item meta value
	 *
	 * @access public
	 * @param meta key name, optional, empty returns all meta as an array
	 * @return previous meta value if it existed, null otherwise
	 */
	function get_meta( $key = '' ) {

		if ( empty( $key ) ) {
			$result = $this->item_meta;
		} else {
			$result = isset( $this->item_meta[ $key ] ) ? $this->item_meta[ $key ] : null;
		}

		return $result;
	}

	public static function refresh_variation_cache() {
		global $wpsc_cart;

		$variation_product_ids = array();

		foreach ( $wpsc_cart->get_items() as $cart_item ) {
			if ( ! empty( $cart_item->variation_values ) )
				$variation_product_ids[] = $cart_item->product_id;
		}

		if ( empty( $variation_product_ids ) )
			return;

		self::$variation_cache = wp_get_object_terms( $variation_product_ids, 'wpsc-variation', array( 'fields' => 'all_with_object_id' ) );

		foreach ( self::$variation_cache as $term ) {
			if ( ! array_key_exists( $term->object_id, self::$variation_cache ) )
				self::$variation_cache[$term->object_id] = array();

			self::$variation_cache[$term->object_id][$term->parent] = $term->name;
		}

		return self::$variation_cache;
	}

	/**
	 * wpsc_cart_item constructor, requires a product ID and the parameters for the product
	 * @access public
	 *
	 * @param integer the product ID
	 * @param array parameters
	 * @param objcet  the cart object
	 * @return boolean true on sucess, false on failure
	 */
	function __construct( $product_id, $parameters, $cart ) {

		// still need to add the ability to limit the number of an item in the cart at once.
		// each cart item contains a reference to the cart that it is a member of, this makes that reference
		// The cart is in the cart item, which is in the cart, which is in the cart item, which is in the cart, which is in the cart item...
		$this->cart = &$cart;

		foreach ( $parameters as $name => $value ) {
			$this->$name = $value;
		}

		$this->product_id = absint( $product_id );

		// to preserve backwards compatibility, make product_variations a reference to variations.
		$this->product_variations =& $this->variation_values;

		if ( $parameters['is_customisable'] == true && $parameters['file_data'] != null ) {
			$this->save_provided_file( $this->file_data );
		}

		$this->refresh_item();

		if ( ! has_action( 'wpsc_add_item', array( 'wpsc_cart_item', 'refresh_variation_cache' ) ) ) {
			add_action( 'wpsc_add_item', array( 'wpsc_cart_item', 'refresh_variation_cache' ) );
		}

	}

	/**
	 * update item method, currently can only update the quantity
	 * will require the parameters to update (no, you cannot change the product ID, delete the item and make a new one)
	 * @access public
	 *
	 * @param integer quantity
	 * #@param array parameters
	 * @return boolean true on sucess, false on failure
	 */
	function update_item($quantity) {
		$this->quantity = (int)$quantity;
		$this->refresh_item();
		$this->update_claimed_stock();
	}

	/**
	 * refresh_item method, refreshes the item, calculates the prices, gets the name
	 * @access public
	 *
	 * @return array array of monetary and other values
	 */
	function refresh_item() {
		global $wpdb, $wpsc_shipping_modules, $wpsc_cart;
		$product_id = $this->product_id;
		$product = get_post( $this->product_id );
		$product_meta = get_metadata( 'post', $this->product_id );
		$this->sku = get_post_meta( $product_id, '_wpsc_sku', true );
		$price = get_post_meta( $product_id, '_wpsc_price', true );
		$special_price = get_post_meta( $product_id, '_wpsc_special_price', true );
		$product_meta = get_post_meta( $product_id, '_wpsc_product_metadata' );
		$this->stock = get_post_meta( $product_id, '_wpsc_stock', true );
		$this->is_donation = get_post_meta( $product_id, '_wpsc_is_donation', true );

		if ( isset( $special_price ) && $special_price > 0 && $special_price < $price )
			$price = $special_price;
		$priceandstock_id = 0;
		$this->weight = isset( $product_meta[0]['weight'] ) ? $product_meta[0]["weight"] : 0;
		// if we are using table rate price
		if ( isset( $product_meta[0]['table_rate_price'] ) ) {
			$levels = $product_meta[0]['table_rate_price'];
			if ( ! empty( $levels['quantity'] ) ) {
				foreach((array)$levels['quantity'] as $key => $qty) {
					if ($this->quantity >= $qty) {
						$unit_price = $levels['table_price'][$key];
						if ($unit_price != '')
							$price = $unit_price;
					}
				}
			}
		}

		$price = apply_filters( 'wpsc_price', $price, $product_id, $this );

		// create the string containing the product name.
		$this->product_name = $this->get_title( 'raw' );
		$this->priceandstock_id = $priceandstock_id;
		$this->meta = $product_meta;

		// change no_shipping to boolean and invert it
		if( isset( $product_meta[0]['no_shipping'] ) && $product_meta[0]['no_shipping'] == 1)
			$this->uses_shipping = 0 ;
		else
			$this->uses_shipping = 1;

		$quantity_limited = get_product_meta($product_id, 'stock', true);
		$this->has_limited_stock = (bool)$quantity_limited;

		if($this->is_donation == 1)
			$this->unit_price = (float) $this->provided_price;
		else
			$this->unit_price = (float) $price;

		$this->total_price = $this->unit_price * $this->quantity;
		if ( $product->post_parent )
			$category_data = get_the_product_category( $product->post_parent );
		else
			$category_data = get_the_product_category( $product_id );

		$this->category_list = array();
		$this->category_id_list = array();

		foreach( (array) $category_data as $category_row ) {
			$this->category_list[] = $category_row->slug;
			$this->category_id_list[] = $category_row->term_id;
		}

		//wpec_taxes - calculate product tax and add to total price
		$wpec_taxes_controller = new wpec_taxes_controller();
		if ( $wpec_taxes_controller->wpec_taxes_isincluded() && $wpec_taxes_controller->wpec_taxes_isenabled() ){
			$taxes = $wpec_taxes_controller->wpec_taxes_calculate_included_tax($this);
			$this->tax_rate = $taxes['rate'];
			$this->tax = $taxes['tax'];
		}

		if ( $product->post_parent ) {
			$this->product_url = get_permalink( $product->post_parent );
		} else {
			$this->product_url = get_permalink( $product_id );
		}

		if( ! is_array( $this->variation_values ) )
			$attach_parent = $product_id;
		else
			$attach_parent = $wpdb->get_var( $wpdb->prepare("SELECT post_parent FROM $wpdb->posts WHERE ID = %d", $product_id ) );


		$att_img_args = array(
				'post_type'   => 'attachment',
				'numberposts' => 1,
				'post_parent' => $attach_parent,
				'orderby'     => 'menu_order',
				'order'       => 'DESC'
		);

		$attached_image = get_posts( $att_img_args );


		if ( $attached_image != null )
			$this->thumbnail_image = array_shift( $attached_image );

		$product_files = (array) get_posts( array(
				'post_type'   => 'wpsc-product-file',
				'post_parent' => $this->product_id,
				'numberposts' => -1,
				'post_status' => 'inherit'
		) );

		if(count($product_files) > 0) {
			$this->file_id = null;
			$this->is_downloadable = true;
		} else {
			$this->file_id = null;
			$this->is_downloadable = false;
		}

		if ( isset( $this->cart->selected_shipping_method ) && isset( $wpsc_shipping_modules[$this->cart->selected_shipping_method] ) && is_callable( array( $wpsc_shipping_modules[$this->cart->selected_shipping_method], "get_item_shipping" ) ) )
			$this->shipping = $wpsc_shipping_modules[$this->cart->selected_shipping_method]->get_item_shipping( $this );

		// update the claimed stock here
		$this->update_claimed_stock();

		do_action_ref_array( 'wpsc_refresh_item', array( &$this ) );
	}

	public function get_title( $mode = 'display' ) {

		if ( ! get_post_field( 'post_parent', $this->product_id ) )
			return get_post_field( 'post_title', $this->product_id );

		if ( empty( self::$variation_cache ) )
			self::refresh_variation_cache();

		$primary_product_id = get_post_field( 'post_parent', $this->product_id );
		$title = get_post_field( 'post_title', $primary_product_id );

		if ( isset( self::$variation_cache[ $this->product_id ] ) ) {
			ksort( self::$variation_cache[ $this->product_id ] );
			$vars   = implode( ', ', self::$variation_cache[ $this->product_id ] );
			$title .= ' (' . $vars . ')';
		}

		$title = apply_filters( 'wpsc_cart_product_title', $title, $this->product_id, $this );

		if ( $mode == 'display' ) {
			$title = apply_filters( 'the_title', $title, $this );
		}

		return $title;
	}

	/**
	 * Calculate shipping method
	 * if no parameter passed, takes the currently selected method
	 * @access public
	 *
	 * @param string shipping method
	 * @return boolean true on sucess, false on failure
	 */

	function calculate_shipping($method = null) {
		global $wpdb, $wpsc_cart, $wpsc_shipping_modules;
		$shipping = '';
		if($method === null)
			$method = $this->cart->selected_shipping_method;

		if( $method && isset( $wpsc_shipping_modules[$method] ) && method_exists( $wpsc_shipping_modules[$method], "get_item_shipping"  ))
			$shipping = $wpsc_shipping_modules[$method]->get_item_shipping($this);

		if($method == $this->cart->selected_shipping_method && !empty( $shipping ) )
			$this->shipping = $shipping;

		return $shipping;
	}

	/**
	 * user provided file method
	 * @access public
	 * @param string shipping method
	 * @return boolean true on sucess, false on failure
	 */

	function save_provided_file($file_data) {
		global $wpdb;
		$accepted_file_types['mime'][] = 'image/jpeg';
		$accepted_file_types['mime'][] = 'image/gif';
		$accepted_file_types['mime'][] = 'image/png';

		$accepted_file_types['mime'][] = 'image/pjpeg';  // Added for IE compatibility
		$accepted_file_types['mime'][] = 'image/x-png';  // Added for IE compatibility

		$accepted_file_types['ext'][] = 'jpeg';
		$accepted_file_types['ext'][] = 'jpg';
		$accepted_file_types['ext'][] = 'gif';
		$accepted_file_types['ext'][] = 'png';

		$accepted_file_types = apply_filters( 'wpsc_customer_upload_accepted_file_types', $accepted_file_types, $this );

		$can_have_uploaded_image = get_product_meta($this->product_id,'product_metadata',true);
		$product = get_post($this->product_id);
		if(0 != $product->post_parent ){
			$product = get_post($product->post_parent);
			$can_have_uploaded_image = get_product_meta($product->ID,'product_metadata',true);
		}
		$can_have_uploaded_image = $can_have_uploaded_image['can_have_uploaded_image'];
		if ('on' == $can_have_uploaded_image || 1 == $can_have_uploaded_image) {
			$name_parts = explode('.',basename($file_data['name']));
			$extension = array_pop($name_parts);

			if ( ( (
					array_search( strtolower( $file_data['type'] ), $accepted_file_types['mime'] ) !== false ) ||
					get_option( 'wpsc_check_mime_types' ) == 1 ) &&
					array_search( strtolower( $extension ), $accepted_file_types['ext'] ) !== false ) {

				if(is_file(WPSC_USER_UPLOADS_DIR.$file_data['name'])) {
					$name_parts = explode('.',basename($file_data['name']));
					$extension = array_pop($name_parts);
					$name_base = implode('.',$name_parts);
					$file_data['name'] = null;
					$num = 2;
					//  loop till we find a free file name, first time I get to do a do loop in yonks
					do {
						$test_name = "{$name_base}-{$num}.{$extension}";
						if(!file_exists(WPSC_USER_UPLOADS_DIR.$test_name))
							$file_data['name'] = $test_name;
						$num++;
					} while ($file_data['name'] == null);
				}

				$unique_id =  sha1(uniqid(rand(),true));
				if(move_uploaded_file($file_data['tmp_name'], WPSC_USER_UPLOADS_DIR.$file_data['name']) )
					$this->custom_file = array(
							'file_name' => $file_data['name'],
							'mime_type' => $file_data['type'],
							'unique_id' => $unique_id
					);

			}
		}
	}

	/**
	 * update_claimed_stock method
	 * Updates the claimed stock table, to prevent people from having more than the existing stock in their carts
	 * @access public
	 *
	 * no parameters, nothing returned
	 */
	function update_claimed_stock() {
		global $wpdb;

		if($this->has_limited_stock == true) {
			$claimed_query = new WPSC_Claimed_Stock( array(
				'product_id' => $this->product_id,
				'cart_id'    => $this->cart->unique_id
			) );
			$claimed_query->update_claimed_stock( $this->quantity );
		}
	}

	/**
	 * save to database method
	 * @access public
	 *
	 * @param integer purchase log id
	 */
	function save_to_db($purchase_log_id) {
		global $wpdb, $wpsc_shipping_modules;

		$method = $this->cart->selected_shipping_method;
		$shipping = 0;

		if( ! empty( $method ) && method_exists( $wpsc_shipping_modules[$method], "get_item_shipping" ) )
			$shipping = $wpsc_shipping_modules[$this->cart->selected_shipping_method]->get_item_shipping( $this );

		if( $this->cart->has_total_shipping_discount() )
			$shipping = 0;

		$shipping = apply_filters( 'wpsc_item_shipping_amount_db', $shipping, $this );

		//initialize tax variables
		$tax = 0;
		$tax_rate = 0;

		//wpec_taxes - calculate product tax and add to total price
		$wpec_taxes_controller = new wpec_taxes_controller();

		if($wpec_taxes_controller->wpec_taxes_isincluded() && $wpec_taxes_controller->wpec_taxes_isenabled()){
			$taxes = $wpec_taxes_controller->wpec_taxes_calculate_included_tax($this);
			$tax_rate = $taxes['rate'];
			$tax = $taxes['tax'];
		}

		$wpdb->insert(
				WPSC_TABLE_CART_CONTENTS,
				array(
		    'prodid' => $this->product_id,
		    'name' => $this->get_title(),
		    'purchaseid' => $purchase_log_id,
		    'price' => $this->unit_price,
		    'pnp' => $shipping,
		    'tax_charged' => $tax,
		    'gst' => $tax_rate,
		    'quantity' => $this->quantity,
		    'donation' => $this->is_donation,
		    'no_shipping' => 0,
		    'custom_message' => $this->custom_message,
		    'files' => serialize($this->custom_file),
		    'meta' => NULL
				),
				array(
		    '%d',
		    '%s',
		    '%d',
		    '%f',
		    '%f',
		    '%f',
		    '%f',
		    '%s',
		    '%d',
		    '%d',
		    '%s',
		    '%s',
		    '%s'
				)
		);

		$cart_item_id = $wpdb->get_var( "SELECT " . $wpdb->insert_id . " AS `id` FROM `".WPSC_TABLE_CART_CONTENTS."` LIMIT 1");

		wpsc_add_cart_item_meta( $cart_item_id, 'sku', $this->sku, true );

		if ( ! empty( $this->item_meta ) ) {
			foreach ( $this->item_meta as $item_meta_key => $item_meta_value ) {
				wpsc_add_cart_item_meta( $cart_item_id, $item_meta_key, $item_meta_value, true );
			}
		}

		if ( $this->is_downloadable == true ) {

			$product_files = (array) get_posts( array(
					'post_type'   => 'wpsc-product-file',
					'post_parent' => $this->product_id,
					'numberposts' => -1,
					'post_status' => 'inherit'
			) );

			$downloads = get_option( 'max_downloads' );

			foreach ( $product_files as $file ) {

				// if the file is downloadable, check that the file is real
				$unique_id = sha1( uniqid( mt_rand(), true ) );

				$wpdb->insert(
						WPSC_TABLE_DOWNLOAD_STATUS,
						array(
								'product_id' => $this->product_id,
								'fileid'     => $file->ID,
								'purchid'    => $purchase_log_id,
								'cartid'     => $cart_item_id,
								'uniqueid'   => $unique_id,
								'downloads'  => $downloads,
								'active'     => 0,
								'datetime'   => date( 'Y-m-d H:i:s' )
						),
						array(
								'%d',
								'%d',
								'%d',
								'%d',
								'%s',
								'%s',
								'%d',
								'%s',
						)
				);

				$download_id = $wpdb->get_var( "SELECT " . $wpdb->insert_id . " AS `id` FROM `".WPSC_TABLE_DOWNLOAD_STATUS."` LIMIT 1");
				wpsc_update_meta( $download_id, '_is_legacy', 'false', 'wpsc_downloads' );
			}

		}

		do_action( 'wpsc_save_cart_item', $cart_item_id, $this->product_id, $this );
	}

}


/**
 * Comparison object that helps with ordering cart items
 *
 * @since  3.8.9
 * @access private
 */
class _WPSC_Comparison {

	private $orderby = '';
	private $order = 'ASC';

	/**
	 * Constructor
	 *
	 * @param string $orderby Field to order by
	 * @param string $order   Order direction, defaults to ASC for ascending.
	 */
	public function __construct( $orderby, $order = 'ASC' ) {
		$this->orderby = $orderby;
		$this->order = $order;
	}

	/**
	 * This compare method can be passed into usort when sorting an array of object
	 *
	 * @since  3.8.9
	 *
	 * @param  object|array $a
	 * @param  object|array $b
	 * @return int          See usort() documentation for the meaning of this return value.
	 */
	public function compare( $a, $b ) {
		// cast them all to object, just in case any of them is an array
		$a = (object) $a;
		$b = (object) $b;

		$key = $this->orderby;

		$val_a = isset( $a->$key ) ? $a->$key : 0;
		$val_b = isset( $b->$key ) ? $b->$key : 0;

		$diff = $val_a - $val_b;

		if ( $this->order != 'ASC' )
			$diff = $diff * -1;

		return $diff;
	}
}

/**
 * Refreshes discount for coupons when a new product is added to the cart.
 *
 * This is a fairly generally expected workflow, though there are some instances wherein
 * one might prefer to force the customer to "Update".  In those instances, this can be unhooked.
 *
 * @since  3.8.14
 * @return void
 */
function wpsc_cart_item_refresh_coupon() {
	$coupon = wpsc_get_customer_meta( 'coupon' );

	if ( ! empty( $coupon ) ) {
		wpsc_coupon_price( $coupon );
	}
}

add_action( 'wpsc_refresh_item', 'wpsc_cart_item_refresh_coupon' );