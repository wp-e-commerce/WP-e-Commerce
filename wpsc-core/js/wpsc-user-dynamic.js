/**
 * WP eCommerce dynamic user functions functions
 *
 * These are core functions for wp-eCommerce
 * Things like registering custom post types and taxonomies, rewrite rules, wp_query modifications, link generation and some basic theme finding code is located here
 *
 * @package wp-e-commerce
 * @since 3.8.13
 */

jQuery.noConflict();

/* For backwards compatibility we are assigning the 'var' values from the structure created
 * with wp_localize_script
 */

/* base url */
var base_url             = wpsc_ajax.base_url;
var WPSC_URL             = wpsc_ajax.WPSC_URL;
var WPSC_IMAGE_URL       = wpsc_ajax.WPSC_IMAGE_URL;
var WPSC_DIR_NAME        = wpsc_ajax.WPSC_DIR_NAME;
var WPSC_CORE_IMAGES_URL = wpsc_ajax.WPSC_CORE_IMAGES_URL;

/* LightBox Configuration start*/
var fileLoadingImage         = wpsc_ajax.fileLoadingImage;
var fileBottomNavCloseImage  = wpsc_ajax.fileBottomNavCloseImage;
var fileThickboxLoadingImage = wpsc_ajax.fileThickboxLoadingImage;
var resizeSpeed              = wpsc_ajax.resizeSpeed; // controls the speed of the image resizing (1=slowest and 10=fastest)
var borderSize               = wpsc_ajax.borderSize;  //if you adjust the padding in the CSS, you will need to update this variable