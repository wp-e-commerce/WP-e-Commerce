<?php _wpsc_admin_html_begin(); ?>
<title><?php esc_html_e( 'Manage Product Variations', 'wpsc' ); ?></title>
<script type="text/javascript">
addLoadEvent = function(func){if(typeof jQuery!="undefined")jQuery(document).ready(func);else if(typeof wpOnload!='function'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}};
var userSettings = {
		'url': '<?php echo SITECOOKIEPATH; ?>',
		'uid': '<?php if ( ! isset($current_user) ) $current_user = wp_get_current_user(); echo $current_user->ID; ?>',
		'time':'<?php echo time() ?>'
	},
	ajaxurl = '<?php echo admin_url( 'admin-ajax.php', 'relative' ); ?>',
	pagenow = '<?php echo $current_screen->id; ?>',
	typenow = '<?php echo $current_screen->post_type; ?>',
	adminpage = '<?php echo $admin_body_class; ?>',
	thousandsSeparator = '<?php echo addslashes( $wp_locale->number_format['thousands_sep'] ); ?>',
	decimalPoint = '<?php echo addslashes( $wp_locale->number_format['decimal_point'] ); ?>',
	isRtl = <?php echo (int) is_rtl(); ?>;
</script>
<?php
	do_action('admin_enqueue_scripts', $hook_suffix);
	do_action("admin_print_styles-$hook_suffix");
	do_action('admin_print_styles');
	do_action("admin_print_scripts-$hook_suffix");
	do_action('admin_print_scripts');
	do_action("admin_head-$hook_suffix");
	do_action('admin_head');
?>
<style type="text/css">
	html {
		background-color:transparent;
	}
</style>
</head>
<?php

$admin_body_class = ' branch-' . str_replace( array( '.', ',' ), '-', floatval( $wp_version ) );
$admin_body_class .= ' version-' . str_replace( '.', '-', preg_replace( '/^([.0-9]+).*/', '$1', $wp_version ) );
$admin_body_class .= ' admin-color-' . sanitize_html_class( get_user_option( 'admin_color' ), 'fresh' );
$admin_body_class .= ' locale-' . sanitize_html_class( strtolower( str_replace( '_', '-', get_locale() ) ) );

?>
<body class="no-js wp-admin wp-core-ui wpsc-product-variation-iframe<?php echo $admin_body_class; ?>">
<script type="text/javascript">document.body.className = document.body.className.replace('no-js','js');</script>

<div id="post-body">
	<div id="wpsc-product-variations-wrapper" class="categorydiv wpsc-categorydiv">
		<?php $this->display_tabs(); ?>
		<div class="wpsc-product-variations-tab-content tabs-panel">
			<?php $this->display_current_tab(); ?>
		</div>
	</div>
</div>

<?php
do_action('admin_print_footer_scripts');
do_action("admin_footer-" . $GLOBALS['hook_suffix']);
?>
<script type="text/javascript">if(typeof wpOnload=='function')wpOnload();</script>
</body>
</html>