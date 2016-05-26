<?php

/**
 * API for interfacing WP eCommerce settings with the Customizer
 *
 * @package WP eCommerce
 * @subpackage Customizer
 * @since 4.0
 */

/**
 * Class used to implement Customizer ( specifically Selective Refresh ) functionality.
 *
 * @since 4.0
 */

class WPSC_Customizer {

    public $settings = array();
    public $sections = array();

    public function __construct() {

        $this->settings = apply_filters( 'wpsc_customizer_settings', $this->settings );
        $this->sections = apply_filters( 'wpsc_customizer_sections', $this->sections );

        $this->init();
    }

    public function init() {
        add_action( 'customize_register', array( $this, 'customizer' ), 100 );
    }

    /**
     * Register selective refresh partial.
     *
     * @param \WP_Customize_Manager $wp_customize Manager.
     */
    public function customizer( WP_Customize_Manager $wp_customize ) {

        $wp_customize->register_control_type( 'WPSC_Customizer_Thumbnail_Control' );

        if ( ! isset( $wp_customize->selective_refresh ) ) {
    		return;
    	}

        $wp_customize->add_panel( 'wpsc', array(
            'title'       => __( 'Store', 'wp-e-commerce' ),
            'description' => __( 'Presentational settings for your store.' ), // Include html tags such as <p>.
            'priority'    => 160, // Mixed with top-level-section hierarchy.
        ) );

        foreach ( $this->sections as $name => $label ) {
            $wp_customize->add_section( $name, array(
                'title' => $label,
                'panel' => 'wpsc',
            ) );
        }

        foreach ( $this->settings as $name => $settings ) {

            $wp_customize->add_setting( $name, $settings['setting'] );

            if ( isset( $settings['control']['class'] ) && 'WP_Customize_Control' == get_parent_class( $settings['control']['class'] ) ) {
                $control = $settings['control']['class'];
                $wp_customize->add_control( new $control( $wp_customize, $name, $settings['control'] ) );
            } else {
                $wp_customize->add_control( $name, $settings['control'] );
            }

            if ( isset( $settings['partial'] ) ) {
                $wp_customize->selective_refresh->add_partial( $name, $settings['partial'] );
            }
        }

    }
}

function wpsc_default_customizer_settings( $settings ) {

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

        // TODO: We need to create a custom control here for thumbnails...which means some modifications will be necessary.


        /*
    	add_image_size(
    		'wpsc_product_single_thumbnail',
    		get_option( 'single_view_image_width' ),
    		get_option( 'single_view_image_height' ),
    		$crop
    	);

    	add_image_size(
    		'wpsc_product_archive_thumbnail',
    		get_option( 'product_image_width' ),
    		get_option( 'product_image_height' ),
    		$crop
    	);

    	add_image_size(
    		'wpsc_product_taxonomy_thumbnail',
    		get_option( 'category_image_width' ),
    		get_option( 'category_image_height' ),
    		$crop
    	);
*/

    $settings['wpsc_products_per_page'] = array(
        'control' => array(
            'type'            => 'number',
            'priority'        => 20,
            'section'         => 'wpsc_general',
            'label'           => __( 'Products Per Page' ),
            'default'         => get_option( 'posts_per_page' ),
            'description'     => __( 'Set the maximum number of products per page.', 'wp-e-commerce' ),
        ),
        'setting' => array(
            'type'              => 'option',
            'capability'        => 'manage_options',
            'default'           => 'auto',
            'sanitize_callback' => 'is_numeric',
        ),
        'partial' => array(
            'selector'            => '#wpsc-products',
            'render_callback'     => function() {
                wpsc_get_template_part( 'loop', 'products' );
             }
        )
    );

    $settings['wpsc_fancy_notifications'] = array(
            'control' => array(
                'type'            => 'checkbox',
                'priority'        => 10,
                'section'         => 'wpsc_general',
                'label'           => __( 'Add to Cart Notifications' ),
                'default'         => false,
                'description'     => __( 'Enable Add to Cart notifications. When adding an item to your cart, this will create a popup notification for users.' ),
            ),
            'setting' => array(
                'type'              => 'option',
                'capability'        => 'manage_options',
                'default'           => false,
                'sanitize_callback' => 'esc_attr',
            ),
            'partial' => array(
                'selector'            => '#wpsc-products',
                'render_callback'     => function() {
                    wpsc_get_template_part( 'loop', 'products' );
                 }
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
            ),
            'partial' => array(
                'selector'            => '#wpsc-products',
                'render_callback'     => function() {
                    wpsc_get_template_part( 'loop', 'products' );
                 }
            )
        );

    $settings['wpsc_products_per_row'] = array(
        'control' => array(
            'type'            => 'select',
            'priority'        => 12,
            'section'         => 'wpsc_layout',
            'label'           => __( 'Products Per Row' ),
            'default'         => 'auto',
            'description'     => __( 'Set the maximum number of products per row. Defaults to showing as many as will fit, up to six products per row', 'wp-e-commerce' ),
            'choices'         => apply_filters( 'wpsc_products_per_row_options', array(
                'auto' => __( 'Automatic', 'wp-e-commerce' ),
                '1'    => __( '1', 'wp-e-commerce' ),
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
            'default'           => 'auto',
            'sanitize_callback' => 'is_numeric',
        ),
        'partial' => array(
            'selector'            => '#wpsc-products',
            'render_callback'     => function() {
                wpsc_get_template_part( 'loop', 'products' );
             }
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

$c = new WPSC_Customizer();
$c->init();

/**
 * Custom control for Customizer.
 *
 * Allows us to have a width and a height input for thumbnail settings.
 *
 * @package WP eCommerce
 * @subpackage Customizer
 * @since 4.0
 */

add_action( 'customize_register', function() {
    /**
     * Thumbnail setting control for WxH settings in Customizer.
     *
     * @todo Move to its own file.
     * @since 4.0
     */
    class WPSC_Customizer_Thumbnail_Control extends WP_Customize_Control {

        public $html = array();

        public function build_field_html( $key, $setting ) {
            $value = '';

            if ( isset( $this->settings[ $key ] ) ) {
                $value = $this->settings[ $key ]->value();
            }

            $this->html[] = '<div><input type="text" value="' . esc_attr( $value ) . '" '.$this->get_link( $key ).' /></div>';
        }

        public function render_content() {
            $output =  '<label>' . esc_html( $this->label ) .'</label>';

            echo $output;

            foreach( $this->settings as $key => $value ) {
                $this->build_field_html( $key, $value );
            }

            echo implode( '', $this->html );
        }

    }
} );
