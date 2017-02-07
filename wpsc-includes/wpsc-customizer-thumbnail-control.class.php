<?php

if ( ! class_exists( 'WP_Customize_Control' ) ) {
    return;
}

/**
 * Custom control for Customizer.
 *
 * Allows us to have a width and a height input for thumbnail settings.
 *
 * @package WP eCommerce
 * @subpackage Customizer
 * @since 3.11.5
 */

/**
 * Thumbnail setting control for WxH settings in Customizer.
 *
 * @todo Move to its own file.
 * @since 3.11.5
 */
class WPSC_Customizer_Thumbnail_Control extends WP_Customize_Control {

    public $html = array();
    public $type = 'wpsc-thumbnail';

    public function build_field_html( $key, $setting, $label ) {
        $value = '';

        if ( isset( $this->settings[ $key ] ) ) {
            $value = $this->settings[ $key ]->value();
        }

        $this->html[] = '<div><label>' . $label . '<br /><input type="number" value="' . esc_attr( $value ) . '" ' . $this->get_link( $key ).' /></label>
        <p>' . $this->description . '</p></div>';
    }

	/**
	 * Op since we're using JS template.
	 *
	 * @since 4.3.0
	 * @access protected
	 */
	protected function render_content() {
        $keys = array_keys( get_object_vars( $this ) );

        foreach ( $keys as $key ) {
            if ( isset( $args[ $key ] ) ) {
                $this->$key = $args[ $key ];
            }
        }

        $output = '<label class="customize-control-title">' . esc_html( $this->label ) .'</label>';

        echo $output;

        foreach( $this->settings as $key => $value ) {
            $label = absint( $key ) ? __( 'Height', 'wp-e-commerce' ) : __( 'Width', 'wp-e-commerce' );
            $this->build_field_html( $key, $value, $label );
        }

        echo implode( '', $this->html );
    }

}
