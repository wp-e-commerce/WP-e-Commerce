<?php

class WPSC_Settings_Tab_Permalinks extends WPSC_Settings_Tab
{
	public function __construct( $id ) {
		$this->populate_form_array();
		parent::__construct( $id );

		add_filter( 'wpsc_catalog_slug_setting_description'                , array( $this, 'filter_make_clickable_description'     ), 10, 2 );
		add_filter( 'wpsc_cart_page_slug_setting_description'              , array( $this, 'filter_make_clickable_description'     ), 10, 2 );
		add_filter( 'wpsc_transaction_result_page_slug_setting_description', array( $this, 'filter_make_clickable_description'     ), 10, 2 );
		add_filter( 'wpsc_customer_account_page_slug_setting_description'  , array( $this, 'filter_make_clickable_description'     ), 10, 2 );
		add_filter( 'wpsc_category_base_slug_setting_description'          , array( $this, 'filter_category_base_slug_description' ), 10, 2 );
		add_filter( 'wpsc_product_base_slug_setting_description'           , array( $this, 'filter_product_base_slug_description'  ), 10    );
	}

	public function filter_category_base_slug_description( $description, $field_array ) {
		$example_url = '<code>' . home_url( $field_array['value'] ) . '/product-category' . '</code>';
		return sprintf( $description, $example_url );
	}

	public function filter_product_base_slug_description( $description ) {
		return sprintf( $description, '<code>%wpsc_product_category%</code>' );
	}

	public function filter_make_clickable_description( $description, $field_array ) {
		$link = make_clickable( home_url( $field_array['value'] ) );
		return sprintf( $description, $link );
	}

	private function populate_form_array() {
		$this->form_array = array(
			// Product Slugs section
			'product-slugs' => array(
				'title'       => _x( 'Product slugs', 'permalinks/product-slugs section title', 'wpsc' ),
				'description' => __( 'You can customize slugs for your product pages here.', 'wpsc' ),
				'fields'      => array(
					'catalog_slug' => array(
						'type'        => 'textfield',
						'title'       => _x( 'Catalog base slug', 'permalinks setting', 'wpsc' ),
						'description' => __( 'Your main catalog URL will be %s .', 'wpsc' ),
					),
					'category_base_slug' => array(
						'type'        => 'textfield',
						'title'       => _x( 'Category base slug', 'permalinks setting', 'wpsc' ),
						'description' => __( "Your product categories' URLs will look like this: %s .", 'wpsc' ),
					),
					'product_base_slug' => array(
						'type'        => 'textfield',
						'title'       => _x( 'Product base slug', 'permalinks setting', 'wpsc' ),
						'description' => __( 'Individual product permalinks will be prepended with this product base slug. Use tag %s to include product category in the permalink.', 'wpsc' ),
					),
					'hierarchical_product_category_url' => array(
						'type'    => 'radios',
						'title'   => _x( 'Hierarchical product category URL', 'permalinks setting', 'wpsc' ),
						'options' => array(
							1 => _x( 'Yes', 'permalinks setting / hierarchical product category URL', 'wpsc' ),
							0 => _x( 'No', 'permalinks setting / hierarchical product category URL', 'wpsc' ),
						),
						'description' => __( 'When hierarchical product category URL is enabled, parent product categories are also included in the product URL.', 'wpsc' ),
					),
				) // end form fields
			), // end product-slugs section

			// Page Slugs section
			'page-slugs' => array(
				'title' => _x( 'Page slugs', 'permalinks/page-slugs section title', 'wpsc' ),
				'description' => __( 'You can customize slugs for shop related pages here.', 'wpsc' ),
				'fields' => array(
					'cart_page_slug' => array(
						'type'        => 'textfield',
						'title'       => _x( 'Cart page slug', 'permalinks setting', 'wpsc' ),
						'description' => __( 'Your cart URL will be: %s .', 'wpsc' ),
					),
					'transaction_result_page_slug' => array(
						'type'        => 'textfield',
						'title'       => _x( 'Transaction result page slug', 'permalinks setting', 'wpsc' ),
						'description' => __( 'When a transaction is completed, the customer will be redirected to %s, where transaction status will be displayed.'),
					),
					'customer_account_page_slug' => array(
						'type'        => 'textfield',
						'title'       => _x( 'Customer account page slug', 'permalinks setting', 'wpsc' ),
						'description' => __( 'This is where your customer can review previous purchases. The URL to this page will be: %s.'),
					),
				), // end form fields
			), // end product-slugs section
		);
	}
}