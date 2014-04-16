<?php

class Sputnik_Pointers {

	public static function bootstrap() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

	}

	public static function enqueue_scripts() {
	    $enqueue = false;

	    $dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );

	    if ( ! in_array( 'wpsc_marketplace_pointer', $dismissed ) ) {
	        $enqueue = true;
	        add_action( 'admin_print_footer_scripts', array( __CLASS__, 'print_footer_scripts' ) );
	    }

	    if ( $enqueue ) {
	        // Enqueue pointers
	        wp_enqueue_script( 'wp-pointer' );
	        wp_enqueue_style( 'wp-pointer' );
	    }
	}

	public static function print_footer_scripts() {
   		$content  = '<h3>' . __( 'New Feature: WPeC Add-Ons' ) . '</h3>';
		$content .= '<p>' .  __( 'Ever wanted to be able to find an extension for your e-commerce store, purchase, install and activate it right from WordPress? Now you can!', 'wpsc' ) . '</p>';
		$content .= '<p>' .  __( 'Find the latest and greatest free and premium plugins from the WP E-Commerce community in our <a href="' . Sputnik_Admin::build_url() . '">Add-Ons page</a>.', 'wpsc' ) . '</p>';
	?>
	<script type="text/javascript">// <![CDATA[
	jQuery(document).ready(function($) {
		var wpsc_target;

		$('#menu-posts-wpsc-product div ul li a[href$="page=sputnik"]').attr( 'id', 'marketplace-link' );

		wpsc_target = $('#menu-posts-wpsc-product').hasClass('wp-has-current-submenu') ? $('#marketplace-link') : $('#menu-posts-wpsc-product');

	   	wpsc_target.pointer({
	        content: '<?php echo $content; ?>',
	        position: {
	            edge: 'left',
	            align: 'center'
	        },
	        close: function() {
	            $.post( ajaxurl, {
	                pointer: 'wpsc_marketplace_pointer',
	                action: 'dismiss-wp-pointer'
	            });
	        }
	    }).pointer('open');
	});
	// ]]></script>
	<?php
	}
}
