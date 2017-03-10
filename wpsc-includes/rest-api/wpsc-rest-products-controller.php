<?php

class WPSC_REST_Products_Controller extends WP_REST_Posts_Controller {

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {

		$base = $this->get_post_type_base( $this->post_type );

		$posts_args = array(
			'context'               => array(
				'default'           => 'view',
			),
			'page'                  => array(
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page'              => array(
				'default'           => 10,
				'sanitize_callback' => 'absint',
			),
		);

		foreach ( $this->get_allowed_query_vars() as $var ) {
			if ( ! isset( $posts_args[ $var ] ) ) {
				$posts_args[ $var ] = array();
			}
		}

		register_rest_route( 'wpsc/v1', '/' . $base, array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_items' ),
				'args'            => $posts_args,
			),
			array(
				'methods'         => WP_REST_Server::CREATABLE,
				'callback'        => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'            => $this->get_endpoint_args_for_item_schema( true ),
			),
		) );
		register_rest_route( 'wpsc/v1', '/' . $base . '/(?P<id>[\d]+)', array(
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
				'args'            => $this->get_endpoint_args_for_item_schema( false ),
			),
			array(
				'methods'  => WP_REST_Server::DELETABLE,
				'callback' => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'     => array(
					'force'    => array(
						'default'      => false,
					),
				),
			),
		) );
		register_rest_route( 'wpsc/v1', '/' . $base . '/schema', array(
			'methods'         => WP_REST_Server::READABLE,
			'callback'        => array( $this, 'get_item_schema' ),
		) );
	}
}
