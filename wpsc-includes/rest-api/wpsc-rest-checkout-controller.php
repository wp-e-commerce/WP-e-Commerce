<?php
class WPSC_REST_Checkout_Controller extends WP_REST_Controller {

	protected static $codes = array(
		4000  => 'unknown-error',
		4001  => 'missing-cart-item-id',
		4002  => 'cart-request-expired',
		4003  => 'item-varition-unavailable',
		4004  => 'unacceptable-quantity',
		4005  => 'item-missing',
		4006  => 'item-out-of-stock',
		4007  => 'item-not-enough-stock',
		4008  => 'item-variation-missing',
		4009  => 'cannot-remove-item',
	);
	protected $product_id = 0;
	protected $product = null;
	protected $request;
	protected static $cart_item_defaults = array(
		'variation_values' => array(),
		'quantity'         => 1,
		'provided_price'   => null,
		'comment'          => null, // Needed?
		'time_requested'   => null, // Needed?
		'custom_message'   => '',
		'file_data'        => null,
		'is_customisable'  => false,
		'meta'             => null, // Needed?
	);

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 * @access public
	 */
	public function __construct() {
		$this->namespace = 'wpsc/v1';
		$this->rest_base = 'cart';
	}

	public function register_routes() {

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/add' . '/(?P<id>[\d]+)', array(
			array(
				'methods'         => WP_REST_Server::EDITABLE,
				'callback'        => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'            => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'            => array(
					'context'          => array(
						'default'      => 'view',
					),
				),
			),
			array(
				'methods'         => WP_REST_Server::EDITABLE,
				'callback'        => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'            => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			array(
				'methods'  => WP_REST_Server::DELETABLE,
				'callback' => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'     => array(
					'force' => array(
						'default' => true,
					),
				),
			),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/schema', array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => array( $this, 'get_public_item_schema' ),
		) );

	}

	/**
	 * Get products in the cart.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$this->request = $request;
		// $products = ;

		$data = array();

		// foreach( $products as $product ) {
		// 	$data[] = $this->prepare_response_for_collection( $product );
		// }

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Get one product from the cart.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$this->request = $request;
		// $data = $this->prepare_item( $request['id'], $request );

		// return a response or error based on some conditional
		if ( ! empty( $data ) && ! is_wp_error( $data ) ) {
			return new WP_REST_Response( $data, 200 );
		} else {
			return new WP_Error( 'product-not-found', __( 'Could not find product.', 'wp-e-commerce' ) );
		}
	}

	/**
	 * Add a product to the cart. Product ID is required.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function create_item( $request ) {
		$this->request = $request;
		return $this->request( 'add_item_to_cart' );
	}

	/**
	 * Add a product to the cart. Product ID is required.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @return WP_Error|WP_REST_Request
	 */
	protected function add_item_to_cart( $edit = false ) {
		global $wpsc_cart;

		if ( ! isset( $this->request['id'] ) ) {
			throw new Exception( __( 'Missing cart item id.', 'wp-e-commerce' ), 4001 );
		}

		$this->product_id = absint( $this->request['id'] );
		$this->product_id = $edit
			? apply_filters( 'wpsc_edit_cart_product_id', $this->product_id )
			: apply_filters( 'wpsc_add_to_cart_product_id', $this->product_id ); // filter needed for back-compat.

		$this->verify_nonce();

		$parameters = array();
		$parameters = $this->get_variation_values( $parameters );
		$parameters = $this->get_customization_values( $parameters );

		if ( ! empty( $this->request['quantity'] ) ) {
			$parameters['quantity'] = (int) $this->request['quantity'];
		}

		if ( ! empty( $this->request['donation_price'] ) && (float) $this->request['donation_price'] > 0 ) {
			$parameters['provided_price'] = (float) $this->request['donation_price'];
		}

		// Make sure all array keys are present and accounted for.
		$parameters = array_merge( self::$cart_item_defaults, $parameters );

		if ( $parameters['quantity'] <= 0 ) {
			throw new Exception( __( 'The quantity you entered is not valid. Please try again.', 'wp-e-commerce' ), 4004 );
		}

		$this->set_product( $edit ? 'wpsc_add_to_cart_product_object' : 'wpsc_edit_cart_item_product_object' );

		$this->check_quantitity_and_stock( $parameters );
		$this->check_variations( $parameters );

		$item_added = $edit
			? $wpsc_cart->edit_item_by_id( $this->product_id, $parameters )
			: $wpsc_cart->set_item( $this->product_id, $parameters );

		$message = $edit
			? __( '%s successfully edited.', 'wp-e-commerce' )
			: __( '%s added to your cart.', 'wp-e-commerce' );

		return $this->prepare_response( $item_added, $message );
	}

	/**
	 * Check if proper nonce is given.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 */
	protected function verify_nonce( $nonce_prefix = 'wpsc-add-to-cart-' ) {
		if ( empty( $this->request['_wp_nonce'] ) || ! wp_verify_nonce( $this->request['_wp_nonce'], "{$nonce_prefix}{$this->product_id}" ) ) {

			throw new Exception( __( 'Request expired. Please refresh the page and try again.', 'wp-e-commerce' ), 4002 );
		}
	}

	/**
	 * Adds files and custom message to product in cart.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @param array $parameters Full data about the request.
	 * @return array $parameters
	 */
	protected function get_variation_values( $parameters ) {
		if ( empty( $this->request['wpsc_product_variations'] ) ) {
			return $parameters;
		}

		$parameters['variation_values'] = array();

		foreach ( $this->request['wpsc_product_variations'] as $key => $variation ) {
			$parameters['variation_values'][ (int) $key ] = (int) $variation;
		}

		$variation_product_id = wpsc_get_child_object_in_terms( $this->product_id, $parameters['variation_values'], 'wpsc-variation' );

		if ( $variation_product_id > 0 ) {
			$this->product_id = $variation_product_id;
		} else {
			throw new Exception( __( 'This variation combination is no longer available.  Please choose a different combination.', 'wp-e-commerce' ), 4003 );
		}

		return $parameters;
	}

	/**
	 * Adds files and custom message to product in cart.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @param array $parameters Full data about the request.
	 * @return array $parameters
	 */
	protected function get_customization_values( $parameters ) {
		if ( empty( $this->request['is_customisable'] ) ) {
			return $parameters;
		}

		$parameters['is_customisable'] = true;

		if ( ! empty( $this->request['custom_text'] ) ) {
			$parameters['custom_message'] = $this->request['custom_text'];
		}

		// TODO - How should we work this?
		if ( ! empty( $_FILES['custom_file'] ) ) {
			$parameters['file_data'] = $_FILES['custom_file'];
		}

		return $parameters;
	}

	/**
	 * Attempt to set the product object.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @param string $filter_name The name of the filter used to filter the post object.
	 */
	protected function set_product( $filter_name ) {
		$this->product = get_post( $this->product_id, OBJECT, 'display' );

		// TODO Use WPSC_Product. Create wpsc_get_product() wrapper. Has a stock helper for L176
		$this->product = apply_filters( $filter_name, $this->product );

		if ( ! $this->product ) {
			throw new Exception( __( 'Sorry, we could not find that item.', 'wp-e-commerce' ), 4005 );
		}
	}

	/**
	 * Checks if quantity requested is acceptable.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @param array $parameters Full data about the request.
	 */
	protected function check_quantitity_and_stock( $parameters ) {
		global $wpsc_cart;

		$stock = get_post_meta( $this->product_id, '_wpsc_stock', true );

		$remaining_quantity = $wpsc_cart->get_remaining_quantity( $this->product_id, $parameters['variation_values'] );

		if ( '' !== $stock && true !== $remaining_quantity ) {
			if ( $remaining_quantity <= 0 ) {

				$message = apply_filters( 'wpsc_add_to_cart_out_of_stock_message', __( 'Sorry, the product "%s" is out of stock.', 'wp-e-commerce' ) );

				throw new Exception( sprintf( $message, $this->product->post_title ), 4006 );
			}

			if ( $remaining_quantity < $parameters['quantity'] ) {

				$message = __( 'Sorry, but the quantity you just specified is larger than the available stock. There are only %d of the item in stock.', 'wp-e-commerce' );

				throw new Exception( sprintf( $message, $remaining_quantity ), 4007 );
			}
		}
	}

	/**
	 * Checks if quantity requested is acceptable.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @param array $parameters Full data about the request.
	 */
	protected function check_variations( $parameters ) {
		if ( null === $parameters['variation_values'] && wpsc_product_has_variations( $this->product_id ) ) {

			$message = apply_filters( 'wpsc_api_add_to_cart_variation_missing_message', __( 'This product has several options to choose from. Please select one to add to cart.', 'wp-e-commerce' ), $this->product_id );

			throw new Exception( $message, 4008 );
		}
	}

	/**
	 * If successful result, prepares the response array.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @param mixed  $result       Wether cart operation was successful.
	 * @param string $message_tmpl Message/message template.
	 */
	protected function prepare_response( $result, $message_tmpl ) {
		if ( ! $result ) {
			throw new Exception( __( 'An unknown error just occurred. Please contact the shop administrator.', 'wp-e-commerce' ), 4000 );
		}

		return array(
			'id'          => $this->product_id,
			'message'     => sprintf( $message_tmpl, $this->product->post_title ),
			'deleteNonce' => wp_create_nonce( "wpsc-remove-cart-item-{$this->product_id}" ),
		);
	}

	/**
	 * Update one item from the collection
	 *
	 * @access public
	 * @since 4.0.0
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function update_item( $request ) {
		$this->request = $request;
		return $this->request( 'update_item_in_cart' );
	}

	/**
	 * Edit item in the collection.
	 *
	 * @access protected
	 * @since 4.0.0
	 * @return WP_Error|WP_REST_Request
	 */
	protected function update_item_in_cart() {
		return $this->add_item_to_cart( true );
	}

	/**
	 * Delete one item from the collection
	 *
	 * @access public
	 * @since 4.0.0
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function delete_item( $request ) {
		$this->request = $request;
		return $this->request( 'delete_item_from_cart' );
	}

	/**
	 * Delete one item from the collection
	 *
	 * @access protected
	 * @since 4.0.0
	 * @return WP_Error|WP_REST_Request
	 */
	protected function delete_item_from_cart() {
		global $wpsc_cart;

		if ( ! isset( $this->request['id'] ) ) {
			throw new Exception( __( 'Cannot remove item from cart', 'wp-e-commerce' ), 4009 );
		}

		$this->product_id = apply_filters( 'wpsc_remove_from_cart_product_id', absint( $this->request['id'] ) );

		$this->verify_nonce( 'wpsc-remove-cart-item-' );

		$this->set_product( 'wpsc_remove_from_cart_product_object' );

		$item_removed = $wpsc_cart->remove_item_by_id( $this->product_id );

		return $this->prepare_response( $item_removed, __( 'You just removed %s from your cart.', 'wp-e-commerce' ) );
	}

	/**
	 * Check if a given request has access to get items
	 *
	 * @access public
	 * @since 4.0.0
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {
		return true;
	}

	/**
	 * Check if a given request has access to get a specific item
	 *
	 * @access public
	 * @since 4.0.0
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to create items
	 *
	 * @access public
	 * @since 4.0.0
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function create_item_permissions_check( $request ) {
		return true;
	}

	/**
	 * Check if a given request has access to update a specific item
	 *
	 * @access public
	 * @since 4.0.0
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function update_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to delete a specific item
	 *
	 * @access public
	 * @since 4.0.0
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function delete_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Prepare the item for create or update operation
	 *
	 * @access public
	 * @since 4.0.0
	 * @param WP_REST_Request $request Request object
	 * @return WP_Error|object $prepared_item
	 */
	protected function prepare_item_for_database( $request ) {
		return array();
	}

	/**
	 * [prepare_item description]
	 * @return [type] [description]
	 */
	public function prepare_item() {

		$product = array();
		$product = wp_parse_args( $product, array(
		) );

		return apply_filters( 'wpsc_cart_rest_prepare_item', $product, $this );
	}

	public function request( $callback ) {
		// error_log( '$_REQUEST: '. print_r( $_REQUEST, true ) );
		// error_log( '$callback: '. print_r( $callback, true ) );
		try {

			$item = $this->$callback();
			// error_log( 'try $item: '. print_r( $item, true ) );
			$result = new WP_REST_Response( $item, 200 );

		} catch ( Exception $e ) {

			$status = substr( $e->getCode(), 0, 3 );
			$result = new WP_Error( self::$codes[ $e->getCode() ], $e->getMessage(), array( 'status' => $status ) );
			// error_log( 'try $result: '. print_r( $result, true ) );
		}

		return $result;
	}

	public function get_item_schema() {
		// TODO: Add proper schema.
		$schema = array(
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => 'WPSC',
			'type'                 => 'object',
			'properties'           => array(
				'description' => array(
					'description' => __( 'A human-readable description of the object.', 'wp-e-commerce' ),
					'type'        => 'string',
					'context'     => array(
						'view',
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
