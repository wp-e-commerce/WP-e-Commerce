<?php
class WPSC_REST_Checkout_Controller extends WP_REST_Controller {

	public $namespace = 'wpsc/v1';
	protected static $codes = array(
		4000 => 'unknown-error',
		4001 => 'cannot-add-item',
		4002 => 'cart-request-expired',
		4003 => 'item-varition-unavailable',
		4004 => 'unacceptable-quantity',
		4005 => 'item-missing',
		4006 => 'item-out-of-stock',
		4007 => 'item-not-enough-stock',
		4008 => 'item-variation-missing',
	);
	protected $product_id = 0;
	protected $request;

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 * @access public
	 */
	public function __construct() {
		register_rest_route( $this->namespace, '/cart/add' . '/(?P<id>[\d]+)', array(
			array(
				'methods'         => WP_REST_Server::CREATABLE,
				'callback'        => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'            => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			),
		) );

		register_rest_route( $this->namespace, '/cart/(?P<id>[\d]+)', array(
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

		register_rest_route( $this->namespace, '/cart/schema', array(
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

		foreach( $products as $product ) {
			$data[] = $this->prepare_response_for_collection( $product );
		}

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
		global $wpsc_cart;

		try {
			if ( ! isset( $request['id'] ) ) {
				throw new Exception( __( 'Cannot add item to cart', 'wp-e-commerce' ), 4001 );
			}

			$this->request    = $request;
			$this->product_id = apply_filters( 'wpsc_add_to_cart_product_id', absint( $this->request['id'] ) );

			if ( empty( $this->request['_wp_nonce'] ) || ! wp_verify_nonce( $this->request['_wp_nonce'], "wpsc-add-to-cart-{$this->product_id}" ) ) {
				// TODO: Determine proper status code.
				throw new Exception( __( 'Request expired. Please try refreshing the page and adding the item to your cart again.', 'wp-e-commerce' ), 4002 );
			}

			$parameters = array();
			$parameters = $this->get_variation_values( $parameters );
			$parameters = $this->get_customization_values( $parameters );

			if ( ! empty( $request['quantity'] ) ) {
				$parameters['quantity'] = (int) $request['quantity'];
			}

			if ( ! empty( $request['donation_price'] ) && (float) $request['donation_price'] > 0 ) {
				$parameters['provided_price'] = (float) $request['donation_price'];
			}

			// Make sure all array keys are present and accounted for.
			$parameters = array_merge( array(
				'variation_values' => array(),
				'quantity'         => 1,
				'provided_price'   => null,
				'comment'          => null, // Needed?
				'time_requested'   => null, // Needed?
				'custom_message'   => '',
				'file_data'        => null,
				'is_customisable'  => false,
				'meta'             => null, // Needed?
			), $parameters );

			if ( $parameters['quantity'] <= 0 ) {
				throw new Exception( __( 'Sorry, but the quantity you entered is not valid. Please try again.', 'wp-e-commerce' ), 4004 );
			}

			// TODO Use WPSC_Product. Create wpsc_get_product() wrapper. Has a stock helper for L176
			$product = apply_filters( 'wpsc_add_to_cart_product_object', get_post( $this->product_id, OBJECT, 'display' ) );

			if ( ! $product ) {
				throw new Exception( __( 'Sorry, we could not find that item to add it to the cart.', 'wp-e-commerce' ), 4005 );
			}

			$this->product_id = $product->ID;

			$stock = get_post_meta( $this->product_id, '_wpsc_stock', true );

			$remaining_quantity = $wpsc_cart->get_remaining_quantity( $this->product_id, $parameters['variation_values'] );

			if ( '' !== $stock && true !== $remaining_quantity ) {
				if ( $remaining_quantity <= 0 ) {

					$message = apply_filters( 'wpsc_add_to_cart_out_of_stock_message', __( 'Sorry, the product "%s" is out of stock.', 'wp-e-commerce' ) );

					throw new Exception( $message, 4006 );
				}

				if ( $remaining_quantity < $parameters['quantity'] ) {

					$message = __( 'Sorry, but the quantity you just specified is larger than the available stock. There are only %d of the item in stock.', 'wp-e-commerce' );

					throw new Exception( $message, 4007 );
				}
			}

			if ( wpsc_product_has_variations( $this->product_id ) && null === $parameters['variation_values'] ) {

				$message = apply_filters( 'wpsc_api_add_to_cart_variation_missing_message', __( 'This product has several options to choose from. Please select one to add to cart.', 'wp-e-commerce' ), $this->product_id );

				throw new Exception( $message, 4008 );
			}

			$item_added = $wpsc_cart->set_item( $this->product_id, $parameters );

			if ( ! $item_added ) {
				throw new Exception( __( 'An unknown error just occurred. Please contact the shop administrator.', 'wp-e-commerce' ), 4000 );
			}

			$item = array(
				'id'      => $product->ID,
				'message' => sprintf( __( 'You just added %s to your cart.', 'wp-e-commerce' ), $product->post_title ),
			);

		} catch ( Exception $e ) {
			$status = substr( $e->getCode(), 3 );
			return new WP_Error( self::$codes[ $e->getCode() ], $e->getMessage(), array( 'status' => $status ) );
		}

		return new WP_REST_Response( $item, 200 );
	}

	/**
	 * Adds files and custom message to product in cart.
	 *
	 * @since 4.0.0
	 *
	 * @todo References $request, which is not passed.
	 * @access public
	 * @param array $parameters Full data about the request.
	 * @return array $parameters
	 */
	protected function get_customization_values( $parameters ) {
		if ( empty( $request['is_customisable'] ) ) {
			return;
		}

		$parameters['is_customisable'] = true;

		if ( ! empty( $request['custom_text'] ) ) {
			$parameters['custom_message'] = $request['custom_text'];
		}

		// TODO - How should we work this?
		if ( ! empty( $_FILES['custom_file'] ) ) {
			$parameters['file_data'] = $_FILES['custom_file'];
		}

		return $parameters;
	}

	/**
	 * Adds files and custom message to product in cart.
	 *
	 * @since 4.0.0
	 *
	 * @access public
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
			// TODO: Determine proper status code.
			throw new Exception( __( 'This variation combination is no longer available.  Please choose a different combination.', 'wp-e-commerce' ), 4003 );
		}

		return $parameters;
	}

	/**
	 * Update one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function update_item( $request ) {
		$this->request = $request;
		$product = $this->prepare_item_for_database( $this->request );

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
		$this->request = $request;

		if ( ! isset( $this->request['id'] ) ) {
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
