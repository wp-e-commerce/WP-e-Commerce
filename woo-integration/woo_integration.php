<?php 

//Remove presentation tab on wpsc option menu
function woo_wpsc_remove_presentation_tab($default_tabs){
	unset( $default_tabs['presentation'] );
	return $default_tabs;
}

//Filter redirect url so that WPSC presentation page will loac correctly after update
function woo_wpsc_filter_redirect_url($query){
	if ($_GET['page']=='woothemes'){
		$query = remove_query_arg('tab', $query);
		$query = add_query_arg( 'page' , 'woothemes' , $query);
	}	
	return $query;
}

//Print presentation menu subpage
function woo_wpsc_filter_option($return){
	$return[1] .= '<li class="wpsc_presentation">
                    	<a title="WPSC_presentation" href="#wpsc-option-presentation">
                        	WPSC Presentation
                        </a>
                    </li>';
					
	$return[0] .= '	<div class="group" id="wpsc-option-presentation">
                		<iframe id="wpsc-presentation" src="'.get_bloginfo('wpurl').'/wp-admin/admin-ajax.php?action=print_wpsc_presentation">
                    	</iframe> 
                	</div>';
					
	return $return;
}

//Ajax respond for wp_ajax_print_wpsc_presentation
function woo_wpsc_presentation_menu(){
	require_once( WPSC_FILE_PATH . '/woo-integration/options_presentation.php' );
	die();
}

//Add main frame style sheet
function add_my_stylesheet() {
	wp_register_style('gb_admin_style', get_bloginfo('template_url') . '/wpsc/css/main_frame_style.css' );
	wp_enqueue_style('gb_admin_style');
}


function woo_wpsc_integration(){
	//add_filter( 'wpsc_settings_tabs' , 'woo_wpsc_remove_presentation_tab');
	add_filter( 'woo_before_option_page' , 'woo_wpsc_filter_option');
	add_filter( 'wpsc_settings_redirect_url' , 'woo_wpsc_filter_redirect_url');
	add_action( 'wp_ajax_print_wpsc_presentation' , 'woo_wpsc_presentation_menu');
	add_action( 'admin_init' , 'add_my_stylesheet');
}

function wpsc_detect_woo(){
	if (function_exists('woo_version')){
		 woo_wpsc_integration();
	}
}

add_action('after_setup_theme', 'wpsc_detect_woo');
?>