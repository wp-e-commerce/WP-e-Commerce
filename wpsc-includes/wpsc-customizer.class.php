<?php

/**
 * API for interfacing WP eCommerce settings with the Customizer
 *
 * @package WP eCommerce
 * @subpackage Customizer
 * @since 3.11.5
 */

/**
 * Class used to implement Customizer ( specifically Selective Refresh ) functionality.
 *
 * @since 3.11.5
 */

 /** WPSC_Customizer_Thumbnail_Control class */

class WPSC_Customizer {

    public $settings = array();
    public $sections = array();

    public function __construct() {

        $this->settings = apply_filters( 'wpsc_customizer_settings', $this->settings );
        $this->sections = apply_filters( 'wpsc_customizer_sections', $this->sections );

        $this->init();
    }

    public function init() {
        add_action( 'customize_register', array( $this, 'include_components' ), 0   );
        add_action( 'customize_register', array( $this, 'customizer' )        , 200 );
    }

    public function include_components() {

        do_action( 'wpsc_customizer_include_components' );

        require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-customizer-thumbnail-control.class.php' );
    }

    /**
     * Register Customizer routine.
     *
     * @param \WP_Customize_Manager $wp_customize Manager.
     */
    public function customizer( WP_Customize_Manager $wp_customize ) {

        if ( ! isset( $wp_customize->selective_refresh ) ) {
    		return;
    	}

        $wp_customize->add_panel( 'wpsc', array(
            'title'       => __( 'Store', 'wp-e-commerce' ),
            'description' => __( 'Presentational settings for your store.', 'wp-e-commerce' ),
            'priority'    => 150,
        ) );

        foreach ( $this->sections as $name => $label ) {
            $wp_customize->add_section( $name, array(
                'title' => $label,
                'panel' => 'wpsc',
            ) );
        }

        foreach ( $this->settings as $name => $settings ) {

            $wp_customize->add_setting( $name, $settings['setting'] );

            if ( isset( $settings['control']['class'] ) && 'WP_Customize_Control' === get_parent_class( $settings['control']['class'] ) ) {

                $class = $settings['control']['class'];

                foreach ( $settings['control']['settings'] as $s ) {
                    $wp_customize->add_setting( $s, $settings['setting'] );
                }

                $wp_customize->add_control(
                    new $class(
                        $wp_customize,
                        $name,
                        array(
                            'label'    => $settings['control']['label'],
                            'section'  => $settings['control']['section'],
                            'settings' => $settings['control']['settings']
                        )
                    )
                );
            } else {
                $wp_customize->add_control( $name, $settings['control'] );
            }

            if ( isset( $settings['partial'] ) ) {
                $wp_customize->selective_refresh->add_partial( $name, $settings['partial'] );
            }
        }
    }
}

new WPSC_Customizer();
