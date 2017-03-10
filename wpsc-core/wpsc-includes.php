<?php

if ( defined( 'WPEC_LOAD_DEPRECATED' ) && WPEC_LOAD_DEPRECATED ) {
	require_once( WPSC_FILE_PATH . '/wpsc-core/wpsc-deprecated.php' );
}

// Start including the rest of the plugin here
require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-logging.class.php'              );
require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-meta-util.php'                  );
require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-deprecated-meta.php'            );
require_once( WPSC_FILE_PATH . '/wpsc-includes/query-base.class.php'                );
require_once( WPSC_FILE_PATH . '/wpsc-includes/customer.php'                        );
require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-meta-customer.php'              );
require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-meta-visitor.php'               );
require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-meta-cart-item.php'             );
require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-meta-purchase.php'              );
require_once( WPSC_FILE_PATH . '/wpsc-includes/product-template.php'                );
require_once( WPSC_FILE_PATH . '/wpsc-includes/variations.class.php'                );
require_once( WPSC_FILE_PATH . '/wpsc-includes/ajax.functions.php'                  );
require_once( WPSC_FILE_PATH . '/wpsc-includes/misc.functions.php'                  );
require_once( WPSC_FILE_PATH . '/wpsc-includes/claimed-stock.class.php'             );
require_once( WPSC_FILE_PATH . '/wpsc-includes/cart-template-api.php'               );
require_once( WPSC_FILE_PATH . '/wpsc-includes/cart.class.php'                      );
require_once( WPSC_FILE_PATH . '/wpsc-includes/cart-item.class.php'                 );
require_once( WPSC_FILE_PATH . '/wpsc-includes/checkout.class.php'                  );
require_once( WPSC_FILE_PATH . '/wpsc-includes/display.functions.php'               );
require_once( WPSC_FILE_PATH . '/wpsc-includes/theme.functions.php'                 );
require_once( WPSC_FILE_PATH . '/wpsc-includes/coupon.class.php'                    );
require_once( WPSC_FILE_PATH . '/wpsc-includes/coupons.class.php'                   );
require_once( WPSC_FILE_PATH . '/wpsc-includes/category.functions.php'              );
require_once( WPSC_FILE_PATH . '/wpsc-includes/processing.functions.php'            );
require_once( WPSC_FILE_PATH . '/wpsc-includes/form-display.functions.php'          );
require_once( WPSC_FILE_PATH . '/wpsc-includes/merchant.class.php'                  );
require_once( WPSC_FILE_PATH . '/wpsc-includes/product.class.php'                   );
require_once( WPSC_FILE_PATH . '/wpsc-includes/stats.functions.php'                 );
require_once( WPSC_FILE_PATH . '/wpsc-includes/meta.functions.php'                  );
require_once( WPSC_FILE_PATH . '/wpsc-includes/productfeed.php'                     );
require_once( WPSC_FILE_PATH . '/wpsc-includes/image_processing.php'                );
require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-data-map.class.php'             );
require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-country.class.php'              );
require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-countries.class.php'            );
require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-region.class.php'               );
require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-currency.class.php'             );
require_once( WPSC_FILE_PATH . '/wpsc-includes/country-region-tax-util.php'         );
require_once( WPSC_FILE_PATH . '/wpsc-includes/currency.helpers.php'                );
require_once( WPSC_FILE_PATH . '/wpsc-includes/purchase-log.helpers.php'            );
require_once( WPSC_FILE_PATH . '/wpsc-includes/purchase-log-notification.class.php' );
require_once( WPSC_FILE_PATH . '/wpsc-includes/purchase-log.class.php'              );
require_once( WPSC_FILE_PATH . '/wpsc-includes/purchase-log-notes.class.php'        );
require_once( WPSC_FILE_PATH . '/wpsc-includes/checkout-form.class.php'             );
require_once( WPSC_FILE_PATH . '/wpsc-includes/checkout-form-data.class.php'        );
require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-theme-engine-bootstrap.php'     );

if ( defined( 'REST_API_VERSION' ) ) {
	require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-rest-api.class.php'         );
}

do_action( 'wpsc_loaded_module_'. basename( __FILE__ ) );

// Taxes
require_once( WPSC_FILE_PATH . '/wpsc-taxes/taxes_module.php' );
require_once( WPSC_FILE_PATH . '/wpsc-includes/upgrades.php' );

// Editor
require_once( WPSC_CORE_JS_PATH . '/tinymce3/tinymce.php' );

require_once( WPSC_FILE_PATH . '/wpsc-includes/currency_converter.inc.php' );
require_once( WPSC_FILE_PATH . '/wpsc-includes/shopping_cart_functions.php' );

// Themes
require_once( WPSC_FILE_PATH       . '/wpsc-includes/google-analytics.class.php' );

require_once( WPSC_FILE_PATH . '/wpsc-admin/admin-form-functions.php' );
require_once( WPSC_FILE_PATH . '/wpsc-shipping/library/shipwire_functions.php' );

// Widgets
include_once( WPSC_FILE_PATH . '/wpsc-widgets/admin_menu_widget.php' );

// Gregs ASH Shipping
require_once( WPSC_FILE_PATH . '/wpsc-includes/shipping.helper.php' );

// Admin
if ( is_admin() ) {
	include_once( WPSC_FILE_PATH . '/wpsc-admin/admin.php' );
}

// WP-CLI support
if ( defined( 'WP_CLI' ) && WP_CLI && version_compare( phpversion(), '5.3', '>=' ) ) {
	require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-wp-cli.php' );
}

// Tracking
require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-tracking.php' );

// Cron
require_once( WPSC_FILE_PATH . '/wpsc-includes/cron.php' );