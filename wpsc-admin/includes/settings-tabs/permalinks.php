<?php

class WPSC_Settings_Tab_Permalinks extends WPSC_Settings_Tab
{
	private $slug_settings = array(
		'catalog_slug',
		'cart_page_slug',
		'transaction_result_page_slug',
		'customer_account_page_slug',
		'product_base_slug',
	);

	public function __construct( $id ) {
		flush_rewrite_rules( false );
		$this->populate_form_array();
		parent::__construct( $id );
		add_filter( 'wpsc_settings_validation_rule_slug_not_conflicted'    , array( $this, 'callback_validation_rule_slug_not_conflicted' ), 10, 5 );
	}

	public function callback_validation_rule_slug_not_conflicted( $valid, $value, $field_name, $field_title, $field_id ) {
		global $wp_rewrite;

		if ( ! $valid )
			return $valid;

		static $existing_slugs = array();
		$internal_name = substr( $field_name, 5 );

		if ( empty( $existing_slugs ) ) {
			foreach ( $this->slug_settings as $setting ) {
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
				if ( $page->post_status == 'trash' )
					$link = admin_url( 'edit.php?post_status=trash&post_type=page' );
				else
					$link = admin_url( 'post.php?post=' . $page->ID . '&action=edit' );

				$existing_slugs[] = array(
					'value' => $page->post_name,
					'title' => sprintf(
						esc_html_x( "the slug of %s page", 'permalink slug conflict check', 'wpsc' ),
						'<a target="_blank" href="' . esc_url( $link )  . '">' . apply_filters( 'the_title', $page->post_title, $page->ID ) . '</a>'
					), // title sprintf
				);
			}

			$existing_slugs = apply_filters( 'wpsc_slug_conflict', $existing_slugs );
		}

		foreach ( $existing_slugs as $id => $slug ) {
			if ( $slug['value'] == $value && $id != $internal_name ) {
				$field_anchor = '<a href="#' . esc_attr( $field_id ) . '">' . esc_html( $field_title ) . '</a>';
				add_settings_error(
					$field_name,
					'field-slug-conflict-' . $field_name,
					sprintf(
						__( '%1$s cannot be used as %2$s because it is conflicted with %3$s.', 'wpsc' ),
						'<code>' . $value . '</code>',
						$field_anchor,
						$slug['title']
					) // sprintf
				); // add_settings_error

				$value = false;
			}
		}

		return $value;
	}

	private function check_slug_conflicts() {
		foreach ( $this->slug_settings as $setting ) {
			settings_errors( 'wpsc_' . $setting, true, true );
		}
	}

	public function display() {
		$this->check_slug_conflicts();

		// Display current slugs
		$base_shop_url      = wpsc_get_catalog_url();
		$category_base_slug = wpsc_get_option( 'category_base_slug' );
		$hierarchical_category_url = (bool) wpsc_get_option( 'hierarchical_product_category_url' );
		$product_base_slug  = wpsc_get_option( 'product_base_slug' );
		$product_prefix     = (bool) wpsc_get_option( 'prefix_product_slug' );
		$sample_category    = get_terms( 'wpsc_product_category', array( 'number' => 1 ) );
		$sample_product     = get_posts( array(	'post_type' => 'wpsc-product', 'numberposts' => 1 ) );

		if (! empty( $sample_product ) ) {
			$prefix = '';

			if ( $product_prefix )
				$prefix = $hierarchical_category_url ? 'parent-product-category/child-product-category' : 'my-product-category';

			$sample_product_link = '<code>' . esc_url( $base_shop_url . '/' . $product_base_slug . '/' . $prefix ) . '/my-product</code>';
		} else {
			$sample_product_link = make_clickable( get_permalink( $sample_product[0] ) );
		}

		if ( empty( $sample_category ) ) {
			$category_slug = $hierarchical_category_url ? 'parent-product-category/child-product-category' : 'my-product-category';
			$sample_category_link = '<code>' . esc_url( $base_shop_url . '/' . $category_base_slug . '/' . $category_slug ) . '</code>';
		}
		else {
			$sample_category_link = make_clickable( get_term_link( $sample_category[0] ) );
		}
		?>
		<h3><?php esc_html_e( 'Current settings', 'wpsc' ); ?></h3>
		<p><?php esc_html_e( "This is how your shop pages' URLs look like on the front-end:", 'wpsc' ); ?></p>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
						<?php esc_html_e( 'Main shop catalog', 'wpsc' ); ?>
					</th>
					<td>
						<?php echo make_clickable( $base_shop_url ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<?php esc_html_e( 'Sample product category archive', 'wpsc' ); ?>
					</th>
					<td>
						<?php echo $sample_category_link; ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<?php esc_html_e( 'Sample single product URL', 'wpsc' ); ?>
					</th>
					<td>
						<?php echo $sample_product_link; ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<?php echo esc_html_x( 'Cart page', 'permalinks setting', 'wpsc' ); ?>
					</th>
					<td>
						<?php echo make_clickable( home_url( wpsc_get_option( 'cart_page_slug' ) ) ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<?php echo esc_html_x( 'Transaction result page', 'permalinks setting', 'wpsc' ); ?>
					</th>
					<td>
						<?php echo make_clickable( home_url( wpsc_get_option( 'transaction_result_page_slug' ) ) ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<?php echo esc_html_x( 'Customer account page', 'permalinks setting', 'wpsc' ); ?>
					</th>
					<td>
						<?php echo make_clickable( home_url( wpsc_get_option( 'customer_account_page_slug' ) ) ); ?>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
		parent::display();
	}

	private function populate_form_array() {
		$this->sections = array(
			'archive-slugs' => array(
				'title'       => _x( 'Archive slugs', 'permalinks/archive-slugs section title', 'wpsc' ),
				'description' => __( 'You can customize slugs for your product archive pages here.', 'wpsc' ),
				'fields'      => array(
					'catalog_slug',
					'category_base_slug',
					'hierarchical_product_category_url',
				),
			),

			'single-slugs' => array(
				'title'       => _x( 'Single slugs', 'permalinks/page-slugs section title', 'wpsc' ),
				'description' => __( 'You can customize slugs for single pages / products here.', 'wpsc' ),
				'fields'      => array(
					'product_base_slug',
					'prefix_product_slug',
					'cart_page_slug',
					'checkout_page_slug',
					'login_page_slug',
					'register_page_slug',
					'password_reminder_page_slug',
					'transaction_result_page_slug',
					'customer_account_page_slug',
				),
			),
		);

		$this->form_array = array(
			'catalog_slug' => array(
				'type'        => 'textfield',
				'title'       => _x( 'Catalog base slug', 'permalinks setting', 'wpsc' ),
				'description' => __( 'This slug will prefix your product category archive and single product permalinks.', 'wpsc' ),
				'validation'  => 'required|slug_not_conflicted',
			),
			'category_base_slug' => array(
				'type'        => 'textfield',
				'title'       => _x( 'Category base slug', 'permalinks setting', 'wpsc' ),
				'description' => __( "It's recommended to have a category base slug.", 'wpsc' ),
				'validation'  => 'slug_not_conflicted'
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
			'product_base_slug' => array(
				'type'        => 'textfield',
				'title'       => _x( 'Product base slug', 'permalinks setting', 'wpsc' ),
				'description' => __( "It's recommended to have a product base slug.", 'wpsc' ),
				'validation'  => 'required|slug_not_conflicted',
			),
			'prefix_product_slug' => array(
				'type'    => 'checkboxes',
				'title'   => _x( 'Product prefix', 'permalinks setting', 'wpsc' ),
				'options' => array(
					1 => __( "Prefix your products with category slug.", 'wpsc' )
				),
			),
			'cart_page_slug' => array(
				'type'        => 'textfield',
				'title'       => _x( 'Cart page slug', 'permalinks setting', 'wpsc' ),
				'description' => __( "This page contains your customer's cart content and checkout form.", 'wpsc' ),
				'validation'  => 'required|slug_not_conflicted',
			),
			'checkout_page_slug' => array(
				'type'        => 'textfield',
				'title'       => _x( 'Checkout page slug', 'permalinks setting', 'wpsc' ),
				'description' => __( 'The checkout process happens on this page.', 'wpsc' ),
				'validation'  => 'required|slug_not_conflicted',
			),
			'transaction_result_page_slug' => array(
				'type'        => 'textfield',
				'title'       => _x( 'Transaction result page slug', 'permalinks setting', 'wpsc' ),
				'description' => __( 'When a transaction is completed, the customer will be redirected to this page, where transaction status will be displayed.', 'wpsc' ),
				'validation'  => 'required|slug_not_conflicted',
			),
			'customer_account_page_slug' => array(
				'type'        => 'textfield',
				'title'       => _x( 'Customer account page slug', 'permalinks setting', 'wpsc' ),
				'description' => __( 'This is where your customer can review previous purchases.', 'wpsc'),
				'validation'  => 'required|slug_not_conflicted',
			),
			'login_page_slug' => array(
				'type'        => 'textfield',
				'title'       => _x( 'Login page slug', 'permalinks setting', 'wpsc' ),
				'description' => __( "Leaving this field blank will disable the page.", 'wpsc' ),
				'validation'  => 'slug_not_conflicted',
			),
			'password_reminder_page_slug' => array(
				'type'        => 'textfield',
				'title'       => _x( 'Password reminder page slug', 'permalinks setting', 'wpsc' ),
				'description' => __( "Leaving this field blank will disable the page.", 'wpsc' ),
				'validation'  => 'slug_not_conflicted',
			),
			'register_page_slug' => array(
				'type'        => 'textfield',
				'title'       => _x( 'Register page slug', 'permalinks setting', 'wpsc' ),
				'description' => __( "Leaving this field blank will disable the page.", 'wpsc' ),
				'validation'  => 'slug_not_conflicted',
			),
		);

		if ( ! get_option( 'users_can_register' ) ) {
			$additional_description = '<br /> ' . __( '<strong>Note:</strong> Enable "Anyone can register" in <a href="%s">Settings -> General</a> first if you want to use this page.', 'wpsc' );
			$additional_description = sprintf( $additional_description, admin_url( 'options-general.php' ) );
			$this->form_array['login_page_slug']['description']         .= $additional_description;
			$this->form_array['password_reminder_page_slug']['description'] .= $additional_description;
			$this->form_array['register_page_slug']['description']      .= $additional_description;
		}
	}
}