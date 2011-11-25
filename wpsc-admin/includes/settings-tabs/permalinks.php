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
		add_filter( 'wpsc_settings_validation_rule_slug_not_conflicted'    , array( $this, 'callback_validation_rule_slug_not_conflicted' ), 10, 4 );
	}

	public function callback_validation_rule_slug_not_conflicted( $valid, $value, $field_name, $field_title ) {
		static $existing_slugs = array();
		$internal_name = substr( $field_name, 5 );
		if ( empty( $existing_slugs ) ) {
			$wpsc_slug_settings = array(
				'catalog_slug',
				'cart_page_slug',
				'transaction_result_page_slug',
				'customer_account_page_slug',
				'category_base_slug',
				'product_base_slug',
			);

			foreach ( $wpsc_slug_settings as $setting ) {
				$existing_slugs[$setting] = array(
					'value' => wpsc_get_option( $setting ),
					'title' => esc_html( $this->form_array[$setting]['title'] ),
				);
			}

			// provide explanations for conflicts as much as we can
			$existing_slugs += array(
				array( 'value' => get_option( 'category_base' ), 'title' => esc_html__( 'Category base' ) ),
				array( 'value' => get_option( 'tag_base'      ), 'title' => esc_html__( 'Tag base' ) ),
				array( 'value' => 'page'      , 'title' => esc_html_x( 'WordPress permalink slug for post pagination', 'permalink slug conflict check', 'wpsc' ) ),
				array( 'value' => 'comments'  , 'title' => esc_html_x( 'WordPress permalink slug for post comments', 'permalink slug conflict check', 'wpsc' ) ),
				array( 'value' => 'search'    , 'title' => esc_html_x( 'WordPress permalink slug for search', 'permalink slug conflict check', 'wpsc' ) ),
				array( 'value' => 'author'    ,	'title' => esc_html_x( 'WordPress permalink slug for post author', 'permalink slug conflict check', 'wpsc' ) ),
				array( 'value' => 'attachment',	'title' => esc_html_x( 'WordPress permalink slug for post attachments', 'permalink slug conflict check', 'wpsc' ) ),
				array( 'value' => 'trackback' , 'title' => esc_html_x( 'WordPress permalink slug for trackbacks', 'permalink slug conflict check', 'wpsc' ) ),
			);

			// get root slugs from published pages
			$pages = get_pages();
			foreach ( $pages as $page ) {
				$existing_slugs[] = array(
					'value' => $page->post_name,
					'title' => sprintf(
						esc_html_x( "the slug of %s page", 'permalink slug conflict check', 'wpsc' ),
						'<a target="_blank" href="' . esc_url( admin_url( 'post.php?post=' . $page->ID . '&action=edit' ) )  . '">' . apply_filters( 'the_title', $page->post_title, $page->ID ) . '</a>'
					), // title sprintf
				);
			}

			$existing_slugs = apply_filters( 'wpsc_slug_conflict', $existing_slugs );
		}

		foreach ( $existing_slugs as $id => $slug ) {
			if ( $this->are_slugs_conflicted( $id, $slug['value'], $internal_name, $value ) ) {
				add_settings_error(
					$field_name,
					'field-slug-conflict',
					sprintf(
						__( '%1$s cannot be used as %2$s because it is conflicted with %3$s.', 'wpsc' ),
						'<code>' . $value . '</code>',
						esc_html( $field_title ),
						$slug['title']
					) // sprintf
				); // add_settings_error

				$value = false;
			}
		}

		return $value;
	}

	private function are_slugs_conflicted( $name1, $slug1, $name2, $slug2 ) {
		if ( $name1 == $name2 )
			return false;

		$slugs = array(
			$name1 => &$slug1,
			$name2 => &$slug2,
		);

		foreach( $slugs as $name => &$slug ) {
			if ( $name == 'category_base_slug' )
				$slug .= '/%wpsc_product_category%';
			elseif ( $name == 'product_base_slug' )
				$slug .= '/%wpsc-product%';
		}

		if ( $slug1 == $slug2 )
			return true;

		return false;
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
		$this->sections = array(
			'product-slugs' => array(
				'title'       => _x( 'Product slugs', 'permalinks/product-slugs section title', 'wpsc' ),
				'description' => __( 'You can customize slugs for your product pages here.', 'wpsc' ),
				'fields'      => array(
					'catalog_slug',
					'category_base_slug',
					'product_base_slug',
					'hierarchical_product_category_url',
				),
			),

			'page-slugs' => array(
				'title'       => _x( 'Page slugs', 'permalinks/page-slugs section title', 'wpsc' ),
				'description' => __( 'You can customize slugs for shop related pages here.', 'wpsc' ),
				'fields'      => array(
					'cart_page_slug',
					'transaction_result_page_slug',
					'customer_account_page_slug',
				),
			),
		);

		$this->form_array = array(
			'catalog_slug' => array(
				'type'        => 'textfield',
				'title'       => _x( 'Catalog base slug', 'permalinks setting', 'wpsc' ),
				'description' => __( 'Your main catalog URL will be %s .', 'wpsc' ),
				'validation'  => 'required|slug_not_conflicted',
			),
			'category_base_slug' => array(
				'type'        => 'textfield',
				'title'       => _x( 'Category base slug', 'permalinks setting', 'wpsc' ),
				'description' => __( "Your product categories' URLs will look like this: %s .", 'wpsc' ),
				'validation'  => 'required|slug_not_conflicted',
			),
			'product_base_slug' => array(
				'type'        => 'textfield',
				'title'       => _x( 'Product base slug', 'permalinks setting', 'wpsc' ),
				'description' => __( 'Individual product permalinks will be prepended with this product base slug. Use tag %s to include product category in the permalink.', 'wpsc' ),
				'validation'  => 'required|slug_not_conflicted',
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
			'cart_page_slug' => array(
				'type'        => 'textfield',
				'title'       => _x( 'Cart page slug', 'permalinks setting', 'wpsc' ),
				'description' => __( 'Your cart URL will be: %s .', 'wpsc' ),
				'validation'  => 'required|slug_not_conflicted',
			),
			'transaction_result_page_slug' => array(
				'type'        => 'textfield',
				'title'       => _x( 'Transaction result page slug', 'permalinks setting', 'wpsc' ),
				'description' => __( 'When a transaction is completed, the customer will be redirected to %s, where transaction status will be displayed.'),
				'validation'  => 'required|slug_not_conflicted',
			),
			'customer_account_page_slug' => array(
				'type'        => 'textfield',
				'title'       => _x( 'Customer account page slug', 'permalinks setting', 'wpsc' ),
				'description' => __( 'This is where your customer can review previous purchases. The URL to this page will be: %s.'),
				'validation'  => 'required|slug_not_conflicted',
			),
		);
	}
}