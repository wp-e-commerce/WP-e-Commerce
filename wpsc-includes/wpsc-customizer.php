<?php

function wpsc_default_customizer_settings( $settings ) {

    $settings['wpsc_product_width_thumbnails'] = array(
        'control' => array(
            'class'           => 'WPSC_Customizer_Thumbnail_Control',
            'settings'        => array( 'single_view_image_width', 'single_view_image_height' ),
            'priority'        => 15,
            'type'            => 'wpsc-thumbnail',
            'section'         => 'wpsc_thumbnails',
            'label'           => __( 'Single Product Thumbnails' ),
            'default'         => '',
            'description'     => __( 'Sets thumbnail size for single product view.', 'wp-e-commerce' ),
        ),
        'setting' => array(
            'type'              => 'option',
            'capability'        => 'manage_options',
            'sanitize_callback' => 'absint',
        )
    );

    $settings['wpsc_product_width_archive_thumbnails'] = array(
        'control' => array(
            'class'           => 'WPSC_Customizer_Thumbnail_Control',
            'settings'        => array( 'product_image_width', 'product_image_height' ),
            'priority'        => 20,
            'type'            => 'wpsc-thumbnail',
            'section'         => 'wpsc_thumbnails',
            'label'           => __( 'Archive Product Thumbnails' ),
            'default'         => '',
            'description'     => __( 'Sets thumbnail size for archive product view.', 'wp-e-commerce' ),
        ),
        'setting' => array(
            'type'              => 'option',
            'capability'        => 'manage_options',
            'sanitize_callback' => 'absint',
        )
    );

    $settings['wpsc_product_width_taxonomy_thumbnails'] = array(
        'control' => array(
            'class'           => 'WPSC_Customizer_Thumbnail_Control',
            'settings'        => array( 'category_image_width', 'category_image_height' ),
            'priority'        => 30,
            'type'            => 'wpsc-thumbnail',
            'section'         => 'wpsc_thumbnails',
            'label'           => __( 'Product Category Thumbnails' ),
            'default'         => '',
            'description'     => __( 'Sets thumbnail size for category images.', 'wp-e-commerce' ),
        ),
        'setting' => array(
            'type'              => 'option',
            'capability'        => 'manage_options',
            'sanitize_callback' => 'absint',
        )
    );

    $settings['wpsc_crop_thumbnails'] = array(
        'control' => array(
            'type'            => 'checkbox',
            'priority'        => 10,
            'section'         => 'wpsc_thumbnails',
            'label'           => __( 'Crop Thumbnails' ),
            'default'         => false,
            'description'     => __( 'Crop images to the specified dimensions using center positions.' ),
        ),
        'setting' => array(
            'type'              => 'option',
            'capability'        => 'manage_options',
            'default'           => false,
            'sanitize_callback' => 'esc_attr',
        )
    );

    $settings['wpsc_products_per_page'] = array(
        'control' => array(
            'type'            => 'number',
            'priority'        => 20,
            'section'         => 'wpsc_general',
            'label'           => __( 'Products Per Page' ),
            'default'         => wpsc_get_option( 'products_per_page' ),
            'description'     => __( 'Set the maximum number of products per page.', 'wp-e-commerce' ),
        ),
        'setting' => array(
            'type'              => 'option',
            'capability'        => 'manage_options',
            'sanitize_callback' => 'absint',
        ),
        'partial' => array(
            'selector'            => '#wpsc-products',
            'render_callback'     => 'wpsc_customizer_render_products'
        )
    );

    $settings['wpsc_fancy_notifications'] = array(
        'control' => array(
            'type'            => 'checkbox',
            'priority'        => 10,
            'section'         => 'wpsc_general',
            'label'           => __( 'Add to Cart Notifications' ),
            'default'         => false,
            'description'     => __( 'Enable Add to Cart notifications. When adding an item to your cart, this will create a popup notification for users.', 'wp-e-commerce' ),
        ),
        'setting' => array(
            'type'              => 'option',
            'capability'        => 'manage_options',
            'default'           => false,
            'sanitize_callback' => 'esc_attr',
        ),
        'partial' => array(
            'selector'            => '#wpsc-products',
            'render_callback'     => 'wpsc_customizer_render_products'
        )
    );

    $settings['wpsc_layout'] = array(
        'control' => array(
            'type'            => 'select',
            'priority'        => 10,
            'section'         => 'wpsc_layout',
            'label'           => __( 'Layout' ),
            'default'         => 'grid',
            'description'     => __( 'Change the layout of your store.' ),
            'choices'         => apply_filters( 'wpsc_layouts', array(
                'grid' => __( 'Grid', 'wp-e-commerce' ),
                'list' => __( 'List', 'wp-e-commerce' )
            ) )
        ),
        'setting' => array(
            'type'              => 'option',
            'capability'        => 'manage_options',
            'default'           => 'grid',
            'sanitize_callback' => 'sanitize_text_field',
        )
    );

    $settings['wpsc_products_per_row'] = array(
        'control' => array(
            'type'            => 'select',
            'priority'        => 12,
            'section'         => 'wpsc_layout',
            'label'           => __( 'Products Per Row' ),
            'default'         => '4',
            'description'     => __( 'Set the maximum number of products per row. Defaults to showing 4 per row, up to six products per row', 'wp-e-commerce' ),
            'choices'         => apply_filters( 'wpsc_products_per_row_options', array(
                '2'    => __( '2', 'wp-e-commerce' ),
                '3'    => __( '3', 'wp-e-commerce' ),
                '4'    => __( '4', 'wp-e-commerce' ),
                '5'    => __( '5', 'wp-e-commerce' ),
                '6'    => __( '6', 'wp-e-commerce' ),
            ) )
        ),
        'setting' => array(
            'type'              => 'option',
            'capability'        => 'manage_options',
            'default'           => '4',
            'sanitize_callback' => 'absint',
        ),
        'partial' => array(
            'selector'            => '#wpsc-products',
            'render_callback'     => 'wpsc_customizer_render_products'
        )
    );

    return $settings;
}

add_filter( 'wpsc_customizer_settings', 'wpsc_default_customizer_settings' );

function wpsc_default_customizer_sections( $sections ) {
    return array_merge( array(
        'wpsc_general'    => __( 'General', 'wp-e-commerce' ),
        'wpsc_layout'     => __( 'Layout', 'wp-e-commerce' ),
        'wpsc_thumbnails' => __( 'Thumbnails', 'wp-e-commerce' ),
    ), $sections );
}

add_filter( 'wpsc_customizer_sections', 'wpsc_default_customizer_sections' );

function wpsc_customizer_assets() {
    _wpsc_te2_mvc_init();

    $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

    wp_enqueue_script( 'wpsc-customizer', wpsc_locate_asset_uri( 'js/customizer.js' ), array( 'jquery' ), WPSC_VERSION, true );
    wp_enqueue_style( 'wpsc-customizer' , wpsc_locate_asset_uri( "css/customizer{$suffix}.css" ), array(), WPSC_VERSION );
}

add_action( 'customize_controls_enqueue_scripts', 'wpsc_customizer_assets' );

function wpsc_customizer_render_products() {
    wpsc_get_template_part( 'loop', 'products' );
 }

require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-customizer.class.php' );
