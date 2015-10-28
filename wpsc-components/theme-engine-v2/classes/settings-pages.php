<?php

class WPSC_Settings_Tab_Pages extends _WPSC_Settings_Tab_Form {
	private $slug_settings = array(
		'store_slug',
		'cart_page_slug',
		'customer_account_page_slug',
		'product_base_slug',
	);

	public function __construct() {
		flush_rewrite_rules( false );
		$this->populate_form_array();
		parent::__construct();
		$this->hide_submit_button();
	}

	private function check_slug_conflicts() {
		foreach ( $this->slug_settings as $setting ) {
			settings_errors( 'wpsc_' . $setting, true, true );
		}
	}

	public function display() {
		$this->check_slug_conflicts();
		parent::display();
	}

	/**
	 * Generate the form configuration array for this tab
	 *
	 * @since  0.1
	 * @access private
	 */
	private function populate_form_array() {
		// define the sections
		$this->sections = array(
			// Locations and Slugs of pages
			'locations' => array(
				'title' => _x(
					'Page Slugs',
					'page locations section title',
					'wp-e-commerce'
				),
				'fields' => array(
					'store_slug',
					'store_as_front_page',
					'category_base_slug',
					'product_base_slug',
					'cart_page_slug',
					'checkout_page_slug',
					'customer_account_page_slug',
					'login_page_slug',
					'password_reminder_page_slug',
					'register_page_slug',
					'prefix_product_slug',
					'hierarchical_product_category_url',
				),
			),

			// Page Titles
			'titles' => array(
				'title' => _x(
					'Page Titles',
					'page titles section title',
					'wp-e-commerce'
				),
				'fields' => array(
					'store_title',
					'cart_page_title',
					'checkout_page_title',
					'customer_account_page_title',
					'login_page_title',
					'password_reminder_page_title',
					'register_page_title',
				),
			),
		);

		// Shortcut variables for buttons and messages
		$view_button  = '<a class="button button-secondary button-view-page" href="%1$s">%2$s</a>';
		$view_message = _x( 'View', 'view page', 'wp-e-commerce' );
		$view_category_message = _x( 'Sample Category', 'view page', 'wp-e-commerce' );
		$view_product_message  = _x( 'Sample Product', 'view page', 'wp-e-commerce' );

		// generate sample URLs for single product and product category
		$base_shop_url   = '<small>' . esc_url( wpsc_get_store_url( '/' ) ) . '</small>';
		$sample_category = get_terms( 'wpsc_product_category', array( 'number' => 1 ) );
		$sample_product  = get_posts( array(	'post_type' => 'wpsc-product', 'numberposts' => 1 ) );

		// generate form fields
		$this->form_array = array(
			// Slug for the main store
			'store_slug' => array(
				'type'    => 'textfield',
				'prepend' => '<small>' . esc_url( home_url( '/' ) ) . '</small>',
				'title'   => _x(
					'Main store',
					'page slug setting',
					'wp-e-commerce'
				),
				'append' => sprintf(
					$view_button,
					wpsc_get_store_url(),
					$view_message
				),
				'validation' => 'required',
				'class' => 'regular-text',
			),

			// Whether to display the store as front page
			'store_as_front_page' => array(
				'type'    => 'radios',
				'title'   => _x( 'Display main store on front page', 'page settings', 'wp-e-commerce' ),
				'options' => array(
					1 => _x( 'Yes', 'settings', 'wp-e-commerce' ),
					0 => _x( 'No', 'settings', 'wp-e-commerce' ),
				),
			),

			// Store title
			'store_title' => array(
				'type'       => 'textfield',
				'title'      => _x( 'Main store title', 'page slug title', 'wp-e-commerce' ),
				'validation' => 'required',
			),

			// Base slug for product category
			'category_base_slug' => array(
				'type'        => 'textfield',
				'prepend'     => $base_shop_url,
				'append'      =>   empty( $sample_category )
				                 ? ''
				                 : sprintf(
				                 	$view_button,
				                 	get_term_link( $sample_category[0] ),
				                 	$view_category_message
				                 ),
				'title'       => _x(
					'Product category base slug',
					'permalinks setting',
					'wp-e-commerce'
				),
				'validation'  => 'required',
				'class' => 'regular-text',
			),

			// Base slug for single product pages
			'product_base_slug' => array(
				'type'        => 'textfield',
				'prepend'     => $base_shop_url,
				'append'      =>   empty( $sample_product )
				                 ? ''
				                 : sprintf(
				                 	$view_button,
				                 	get_permalink( $sample_product[0] ),
				                 	$view_product_message
				                 ),
				'title'       => _x(
					'Single product base slug',
					'permalinks setting',
					'wp-e-commerce'
				),
				'validation'  => 'required',
				'class' => 'regular-text',
			),

			// Whether to include category slug in product permalinks
			'prefix_product_slug' => array(
				'type'    => 'checkboxes',
				'title'   => _x( 'Product prefix', 'permalinks setting', 'wp-e-commerce' ),
				'options' => array(
					1 => __(
						'Include category slug in product URL.',
						'wp-e-commerce'
					)
				),
			),

			// Hierarchical product category URL
			'hierarchical_product_category_url' => array(
				'type'    => 'radios',
				'title'   => _x(
					'Hierarchical product category URL',
					'permalinks setting',
					'wp-e-commerce'
				),
				'options' => array(
					1 => _x(
						'Yes',
						'settings',
						'wp-e-commerce'
					),
					0 => _x(
						'No',
						'settings',
						'wp-e-commerce'
					),
				),
				'description' => __(
					'When hierarchical product category URL is enabled, parent product categories are also included in the product URL.',
					'wp-e-commerce'
				),
			),

			// Slug for cart page
			'cart_page_slug' => array(
				'type'        => 'textfield',
				'prepend'     => $base_shop_url,
				'append'      => sprintf(
					$view_button,
					wpsc_get_cart_url(),
					$view_message
				),
				'title'       => _x( 'Cart page', 'page settings', 'wp-e-commerce' ),
				'validation'  => 'required',
				'class' => 'regular-text',
			),

			// Cart page title
			'cart_page_title' => array(
				'type'        => 'textfield',
				'title'       => _x( 'Cart page', 'page settings', 'wp-e-commerce' ),
				'validation'  => 'required',
			),

			// Slug for checkout page
			'checkout_page_slug' => array(
				'type'        => 'textfield',
				'prepend'     => $base_shop_url,
				'title'       => _x( 'Checkout page', 'page setting', 'wp-e-commerce' ),
				'validation'  => 'required',
				'class' => 'regular-text',
			),

			// Checkout page title
			'checkout_page_title' => array(
				'type' => 'textfield',
				'title' => _x( 'Checkout page', 'page settings', 'wp-e-commerce' ),
				'validation' => 'required',
			),

			// Slug for customer account page
			'customer_account_page_slug' => array(
				'type'        => 'textfield',
				'prepend'     => $base_shop_url,
				'append'      => sprintf(
					$view_button,
					wpsc_get_customer_account_url(),
					$view_message
				),
				'title'       => _x( 'Customer account page', 'permalinks setting', 'wp-e-commerce' ),
				'validation'  => 'required|slug_not_conflicted',
				'class' => 'regular-text',
			),

			// Customer account page title
			'customer_account_page_title' => array(
				'type' => 'textfield',
				'title' => _x( 'Customer account page', 'page settings', 'wp-e-commerce' ),
				'validation' => 'required',
			),

			// Slug for login page
			'login_page_slug' => array(
				'type'        => 'textfield',
				'prepend'     => $base_shop_url,
				'title'       => _x( 'Login page', 'permalinks setting', 'wp-e-commerce' ),
				'description' => __( 'Leaving this field blank will disable the page.', 'wp-e-commerce' ),
				'validation'  => 'slug_not_conflicted',
				'class' => 'regular-text',
			),

			// Login page title
			'login_page_title' => array(
				'type' => 'textfield',
				'title' => _x( 'Login page', 'page settings', 'wp-e-commerce' ),
				'validation' => 'required',
			),

			// Slug for password reminder
			'password_reminder_page_slug' => array(
				'type'        => 'textfield',
				'prepend'     => $base_shop_url,
				'title'       => _x( 'Password reminder page', 'permalinks setting', 'wp-e-commerce' ),
				'description' => __( "Leaving this field blank will disable the page.", 'wp-e-commerce' ),
				'validation'  => 'slug_not_conflicted',
				'class' => 'regular-text',
			),

			// Title for password reminder page
			'password_reminder_page_title' => array(
				'type'       => 'textfield',
				'title'      => _x( 'Password reminder page', 'page settings', 'wp-e-commerce' ),
				'validation' => 'required',
			),

			// Slug for register page
			'register_page_slug' => array(
				'type'        => 'textfield',
				'prepend'     => $base_shop_url,
				'title'       => _x( 'Register page', 'permalinks setting', 'wp-e-commerce' ),
				'description' => __( "Leaving this field blank will disable the page.", 'wp-e-commerce' ),
				'validation'  => 'slug_not_conflicted',
				'class' => 'regular-text',
			),

			// Register page title
			'register_page_title' => array(
				'type'       => 'textfield',
				'title'      => _x( 'Register page', 'page settings', 'wp-e-commerce' ),
				'validation' => 'required',
			),
		);

		// display warnings for login, register and password reminder pages when
		// "Anyone can register" is disabled.
		if ( ! get_option( 'users_can_register' ) ) {
			$additional_description = '<br /> ' . __( '<strong>Note:</strong> Enable "Anyone can register" in <a href="%s">Settings -> General</a> first if you want to use this page.', 'wp-e-commerce' );
			$additional_description = sprintf( $additional_description, admin_url( 'options-general.php' ) );
			$this->form_array['login_page_slug']['description']         .= $additional_description;
			$this->form_array['password_reminder_page_slug']['description'] .= $additional_description;
			$this->form_array['register_page_slug']['description']      .= $additional_description;
		}
	}
}