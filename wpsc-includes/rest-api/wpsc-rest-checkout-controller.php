<?php
class WPSC_REST_Checkout_Controller extends WP_REST_Controller {
	$namespace = 'wpsc/v1/cart';

	public function __construct() {
		register_rest_route( $this->namespace, '/add' . '/(?P<id>[\d]+)', array(
			array(
				'methods'         => WP_REST_Server::CREATABLE,
				'callback'        => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'            => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			),
		) );

		register_rest_route( $this->namespace, . '/(?P<id>[\d]+)', array(
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

		register_rest_route( $this->namespace . '/schema', array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => array( $this, 'get_public_item_schema' ),
		) );

	}

	/**
	 * Get products in the cart.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {

		$products = ;

		$data = array();

		foreach( $products as $product ) {
			$data[] = $this->prepare_response_for_collection( $product );
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Get one produt from the cart.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {

		$data = $this->prepare_item( $request['id'], $request );

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
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function create_item( $request ) {

		if ( ! isset( $request['id'] ) ) {
			return new WP_Error( 'cant-add', __( 'Cannot add item to cart', 'wp-e-commerce' ), array( 'status' => 500 ) );
		}

		$product = $this->prepare_item_for_database( $request );

		// Add the item to the cart.
		$data = false;

		if ( is_array( $data ) ) {
			return new WP_REST_Response( $data, 200 );
		}

		return new WP_Error( 'cant-create', __( 'Could not create cart', 'wp-e-commerce' ), array( 'status' => 500 ) );
	}

	/**
	 * Update one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function update_item( $request ) {
		$product = $this->prepare_item_for_database( $request );

		// Update item quantity in cart.
		$data = false;

		if ( is_array( $data ) ) {
			return new WP_REST_Response( $data, 200 );
		}

		return new WP_Error( 'cant-update', __( 'Cannot update item in cart', 'wp-e-commerce' ), array( 'status' => 500 ) );

	}

	/**
	 * Delete one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function delete_item( $request ) {

		if ( ! isset( $request['id'] ) ) {
			return new WP_Error( 'cant-delete', __( 'Cannot delete item from cart', 'wp-e-commerce' ), array( 'status' => 500 ) );
		}

		$deleted = false;

		if ( $deleted ) {
			return new WP_REST_Response( true, 200 );
		}

		return new WP_Error( 'cant-delete', __( 'Cannot delete cart', 'wp-e-commerce' ), array( 'status' => 500 ) );
	}

	/**
	 * Check if a given request has access to get items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {
		return true;
	}

	/**
	 * Check if a given request has access to get a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to create items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function create_item_permissions_check( $request ) {
		return true;
	}

	/**
	 * Check if a given request has access to update a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function update_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to delete a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function delete_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Prepare the item for create or update operation
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_Error|object $prepared_item
	 */
	protected function prepare_item_for_database( $request ) {
		return array();
	}

	public function prepare_item() {

		$product = array();
		$product = wp_parse_args( $product, array(
		) );

		return apply_filters( 'wpsc_cart_rest_prepare_item', $product, $this );
	}

	/**
	 * Get the query params for collections
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			// 'page'                   => array(
			// 	'description'        => 'Current page of the collection.',
			// 	'type'               => 'integer',
			// 	'default'            => 1,
			// 	'sanitize_callback'  => 'absint',
			// ),
			// 'per_page'               => array(
			// 	'description'        => 'Maximum number of items to be returned in result set.',
			// 	'type'               => 'integer',
			// 	'default'            => 10,
			// 	'sanitize_callback'  => 'absint',
			// ),
			// 'component'              => array(
			// 	'description'        => 'Limit results to those matching a specific component.',
			// 	'type'               => 'string',
			// 	'sanitize_callback'  => 'sanitize_text_field', // @todo: limit to registered components
			// ),
			// 'is_new'                 => array(
			// 	'description'        => 'Limit results to those matching a specific component.',
			// 	'type'               => 'boolean',
			// 	'sanitize_callback'  => 'wp_validate_boolean'
			// ),
		);
	}
}
