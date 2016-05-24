<?php

/**
 * API for interfacing WP eCommerce settings with the Customizer
 *
 * @package WP eCommerce
 * @subpackage Customizer
 * @since 4.0
 */

/**
 * Class used to implement Customizer, and specifically Selective Refresh, functionality.
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

    private function init() {
        add_action( 'customize_register', array( $this, 'register_partials' ), 100 );
    }

    /**
     * Register selective refresh partial.
     *
     * @param \WP_Customize_Manager $wp_customize Manager.
     */
    public function register_partials( WP_Customize_Manager $wp_customize ) {

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

        foreach ( $this->settings as $name => $setting ) {

            $wp_customize->selective_refresh->add_partial( $name, $setting );
        }

    }
}

function wpsc_default_customizer_settings( $settings ) {
    $settings['setting'] = array(
        array(
            'selector'            => '.site-description',
            'container_inclusive' => false,
            'render_callback'     => function() {
                    bloginfo( 'description' );
            }
        )
    );
}

add_filter( 'wpsc_customizer_settings', 'wpsc_default_customizer_settings' );

function wpsc_default_customizer_sections( $sections ) {
    return array(
        'general' => __( 'General', 'wp-e-commerce' ),
        'layout'  => __( 'Layout', 'wp-e-commerce' ),
    );
}

add_filter( 'wpsc_customizer_settings', 'wpsc_default_customizer_sections' );
