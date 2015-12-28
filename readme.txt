=== WP eCommerce ===
Contributors: JustinSainton
Donate link: https://wpecommerce.org
Tags: e-commerce, digital downloads, wp-e-commerce, shop, cart, paypal, authorize, stock control, ecommerce, shipping, tax
Requires at least: 4.1
Tested up to: 4.4
Stable tag: 3.11.2

WP eCommerce is a free, powerful plugin that empowers you to sell anything online, quickly and easily.

== Description ==

Since 2006, we've been helping entrepreneurs just like you realize their goal of selling online.  Whether you're hoping to sell digital downloads, physical inventory, or subscriptions and memberships - with WP eCommerce, you're only minutes away from your next sale.

 * Make the plugin your own with CSS and HTML customizations
 * Learn quickly with our [video tutorials](http://docs.wpecommerce.org/category/resources/videos/)
 * Integration with [dozens of different payment gateways](http://wpecommerce.org/store/premium-plugins/)
 * Integrate with many popular WordPress plugins
 * Use built-in marketing tools to sell more
 * Shipping is easy with built in shipping tools and integration with popular couriers
 * Secure check-out with SSL
 * Manage your orders and catalogues with powerful built-in tools
 * Extend your eCommerce site with [additional modules](http://wpecommerce.org/store/premium-plugins/)
 * Integrate with WordPress multisite
 * With hundreds of available hooks, developers can make WP eCommerce do anything you can imagine

== Installation ==

1. Upload the folder 'wp-e-commerce' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress

= Updating =

Before updating please make a backup of your existing files and database. Just in case.
After upgrading from earlier versions look for link "Update Store". This will update your database structure to work with new version.

== Changelog ==

= 3.11.2 =

* Fix: WordPress 4.4 introduced changes to the WPDB class that caused loosely typed data to fail upon insert, causing empty purchase logs.

= 3.11.1 =

* Fix: Amazon Payments caused fatal errors on PHP 5.2. See [#2026](https://github.com/wp-e-commerce/WP-e-Commerce/issues/2026).
* Fix: Our translation update was too fast, too furious. We accidentally replaced a string we didn't intend to replace. This broke pagination for products. We feel pretty bad about that.

= 3.11.0 =

* _Potentially breaking change_: wpsc_add_to_cart() now checks the `$_REQUEST` super global, rather than `$_POST`. This allows for future features, such as populating carts from a URL token, etc. Anywhere you may have code that checks for $_POST, in this context, it should now check $_REQUEST. See [#1852](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1852).
* Fix: Improvements to internationalization across the board, including updates to introduce compatibility for translate.wordpress.org.
* Fix: Show admin notice if administrator has not set any target markets. See [#711](https://github.com/wp-e-commerce/WP-e-Commerce/issues/711).
* Fix: Check post_status more stringently in single page template controller. See [#844](https://github.com/wp-e-commerce/WP-e-Commerce/issues/844).
* Fix: The Google Analytics integration was calculating products once, not once per quantity in the cart. See [#1718](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1718).
* Fix: The Google Analytics integration was incompatible with the Yoast Analytics plugin. See [#1736](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1736).
* Fix: Resolve scenarios where variation combinations for products that were unavailable could still allow for cart addition (adding the parent product, rather than the variation). See [#1373](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1373).
* Fix: Order totals on sales log page did not count Closed Orders, but did Orders Received. This has been reversed, as that is the paradigm used throughout the rest of the project. See [#1728](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1728).
* Fix: Deleting a purchase log now also deletes the purchase meta associated with that log. See [#1877](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1877).
* Fix: Deleting a purchase log now also deletes the download status entries for that log. See [#1964](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1964).
* Fix: In the rare occurrence that an insert for a purchase log entry fails, we now return an error saying so. See [#1892](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1892).
* Fix: When creating a variation, you should be limited to selecting only variation sets as parents, not other variations. See [#1893](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1893).
* Enhancement: General performance improvements surrounding meta retreival, especially for countries. Including caching, cache invalidation, etc. See [#1894](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1894).
* Enhancement: New 3.0 Payment Gateway API now has a method that checks for currencies that don't use fractions. Our code formally had several areas these currencies were hard-coded, and egregiously incomplete.  See [#1917](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1917).
* Enhancement: Mass improvements to 2.0 theme engine. Bug fixes, rich markup support, and much more. Want to test it out before 4.0? We have a filter for that.
* Enhancement: Filter bulk messages to reflect that users are updating products, not posts. See [#844](https://github.com/wp-e-commerce/WP-e-Commerce/issues/844).
* Enhancement: Improvements to code for Australia Post. See [#1337](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1337).
* Enhancement: Standardize on a single, filterable method for checking the completion status of an order. See [#1360](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1360).
* Enhancement: Significant improvements to how digital downloads are handled and made available.  See [#37](https://github.com/wp-e-commerce/WP-e-Commerce/issues/37).
* Enhancement: Improve admin performance for sorted product categories. See [#1389](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1389).
* Enhancement: Regions are now filtered. See [#1930](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1930).
* Enhancement: Improve flexibility of product duplication functionality.  Ensures that post meta tied to specific IDs have the available filters to update those IDs. See [#1672](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1672).
* Enhancement: All front-end AJAX responses are now filtered. See [#1698](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1698).
* New: Introducing Amazon Payments as an available payment gateway.
* New: Introduced better filtering mechanisms for custom purchase statuses and related bulk actions and labels for those statuses. See [#1525](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1525) and related issues/PRs.
* New: Introduced a new filter, `wpsc_checkout_get_fields`, allowing you to add, remove, and modify checkout form fields. See [#1837](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1837).


= 3.10.1 =

* Fix: Uses version_compare() for comparing wpsc_version constant in legacy code (from 5 years ago).  Using comparators was causing 3.10.0 to be "less than" 3.8, resulting in an unnecessary admin notice.
* Fix: Changes internal mechanism for detecting which theme engine is in use. The way we were checking for it before caused many core actions to be unhooked, leading to unsavory results, like Fancy Notifications no longer working.
* Fix: Patched in a few fixes for incorrect usage of esc_url(). This resolves scenarios like the selecting a custom checkout form in the Dashboard failing because of double-encoded ampersands.

= 3.10.0 =

* Enhancement: Added updated PayPal gateways.  We now support Digital Goods and Pro Hosted, and have updated the Express Checkout gateway. We now recommend all existing users of the 2.0 Express Checkout gateway update to the 3.0 version.

= 3.9.5 =

* Fix: 4.3 Compatibility w/ WP_Widget constructors.

= 3.9.4 =

* Security Fix: Harden several instances of $_POST input that were not sanitized properly. Specifically, PayPal settings and Quick Edit fields for products.
* Security Fix: Do not return visitor meta if WP eCommerce presumes a user to be a bot.
* Enhancement: Provide a notice for users to repair their WP eCommerce tables if visitor and visitor meta tables are in need of repair. See [#1901](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1901).
* Fix: Notices on stats saving for products.

= 3.9.3 =

* Fix: Fix potential reflexive XSS issue with add_query_arg() and remove_query_arg() usage.  Update is _highly_ recommended.

= 3.9.2 =

* Fix: Reverted pre_get_posts hook.  See [#1444](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1444).  This was causing issues with template redirection and selection on search templates.
* Fix: Shipping conflict with target market restrictions on specific categories.  See [#1711](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1711).
* Fix: Taxes were broken following some refactoring of the admin saving callbacks.
* Fix: Quick edit was overwriting variation SKUs and sale prices.
* Fix: RSS Feed error.

= 3.9.1 =

* Fix: Disable 3.0 payment gateway API gateways. Will be re-enabled after more vigorous testing.
* Fix: Check that $wpsc_cart->cart_items is an array, not an object.
* Fix: Improve shipping method property checks in wpsc_cart class. See [#1683](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1683).
* Fix: Add asterix class to required fields for PayPal Pro 2.0.
* Fix: Ensure taxable checkbox can be unchecked. 3.9 regression. See [#1685](https://github.com/wp-e-commerce/WP-e-Commerce/issues/1685).
* Fix: Only show completed purchases in the user account area.
* Fix: Use is_email(), rather than a faulty regex, for checkout email field validation.
* Fix: More than a [handful of faulty variables](https://github.com/wp-e-commerce/WP-e-Commerce/commit/58f2d3ad9698ef496a27a701e5a926e412d55c05) were referenced in wpsc_coupons.
* Fix: Function `wpsc_string_to_boolean` was essentially non functional.
* Fix: Send country and state through to PayPal only if they actually exist.
* Fix: Broken theme-engine activation routine.  Would always fail, running 1.0 engine.

= 3.9 =

* Fix: Eliminate most usage of number-based inputs in product UI.
* Fix: Fall back to product ID if SKU is not set for Google Analytics reporting.
* Fix: Restored inadvertently removed filter, `wpsc_add_advanced_options`.
* Fix: Ensure price entered for "Free Shipping" threshold is locale-aware.
* Fix: Generally improved metadata saving routines for quick/bulk edit areas.
* Fix: Ensure "Combination of product variants is not available" message is hidden from Grid View.
* Fix: Don't require a shipping method to be selected if you qualify for free shipping.
* Fix: When "Buy Now" button is used, it is now disabled if item is out of stock.
* New: Introduced `wpsc_get_countries` and `wpsc_get_countries_array` filters.
* New: Introduced `wpsc_google_analytics_pushed_product` filter.
* New: Introduced `wpsc_get_downloadable_links` filter.
* New: Added screen option for number of purchase logs to show on a page.
* New: Introduced WPSC_Logging class.  Based on WP_Logging.
* New: Customers may now checkout with carts that have a value of $0.
* New: Introduces `wpsc_is_free_cart` filter.
* New: Added 2.0 theme engine component.  Defaults off until the 4.0 release, but can be enabled via filter for testing.
* New: Added marketplace component.
* New: Added 3.0 payment gateway API component.
* New: PayPal Digital Goods gateway, updated PayPal Express Checkout, PayPal Pro and Manual Payment gateways.
* New: Product Tags now have proper body classes.
* New: Meta updates, kind of a big deal: We're now using X.X.Y versioning (like WordPress) and we now have unit tests.
* New: Introduce attachments method and filter for WPSC_Purchase_Log_Notification class.
* New: Single Product pages now have ID-based body classes.
* New: Supports Universal Analytics in Google Analytics.
* New: Added `wpsc_checkout_fields` filter for filtering checkout form fields.
* Change: Dates are now optional on coupons. Expiration, specifically, is no longer required.  Validation is filterable via `wpsc_coupons_validate_coupon`.
* Change: Refactored purchase log action links, introduced new API for adding links.
* Change: Product page shortcodes, e.g. [productspage], are now stripped from the_excerpt().  Helpful for cleaner looking search results.
* Change: Limit Google Product ID to 50 characters in Google Product Feed.  Introduced `wpsc_google_product_feed_product_id`.
* Change: When only one shipping rate is available, it will automatically be selected as the default rate.

= 3.8.14.4 =

 * Fix: Significant security vulnerability whereby some admin actions were exploitable by unauthorized users.  Props to Sucuri for the responsible disclosure.
 * Fix: Other less significant security hardening and minor code maintenance.

= 3.8.14.3 =

* Fix: Resolve PHP warning in stats logging class.

= 3.8.14.2 =

* Fix: Clear country cache automatically.
* Fix: Fixed notices when using quick edit.
* Fix: Fixed html in checkout form.
* Fix: Fixed shipping results not being displayed.
* Fix: Does not show errors when deprecated google checkout is used.
* Fix: Makes sure countries are validated if mandatory.
* Fix: Makes sure the payment gateway checks for first and last name.
* Fix: Only output html for shipping error page.
* Fix: Updated shipping calculator to check for new checkout information and made the calculator more efficient.
* Fix: USPS shipping now works for servers that don't support curl.
* Fix: Personalization forms are correctly initialized in database.
* Fix: Display of products in admin product gallery will display in the order admins want them to.
* New: Updated Polish and Russian language files.
* New: Added filter wpsc_uncategorized_product_category to change uncategorized section.
* New: New hook - wpsc_coupon_add_top and wpsc_coupon_add_bottom for the add coupon section.
* New: New hook - wpsc_element_is_visible to check and see if an element is visible.
* Change: Show region on shipping calculator.
* Change: Billing was accidentally referred to as shipping when billing state and region was a drop down.

= 3.8.14.1 =

* Fix: For users with older (3.7.x and older) templates copied into their theme directory, we inadvertently broke the visibility of the shipping calculator.
* Fix: Under similar circumstances, taxes would not properly calculate.
* Fix: Ensured compatibility with the popular "Weight and Destination" shipping module.  Note: You may need to go to your Target Markets and re-save the values there to properly flush the cache for this compatibility.
* Fix: Edge case where $0.00 could be returned for a shipping rate.
* Fix: Race condition on AJAX save while submitting purchase.
* Fix: Restored proper functionality on digital downloads.
* Fix: Ensure that UPS and USPS always return proper and expected values.  Incrementally improved error handling here.
* Fix: Ensure that shipping method is properly retained if checkout page is refreshed.
* Change: Added "County" as the region label for the UK.

= 3.8.14 =
* Fix: Improved visitor profiles performance, new infrastructure does not use WordPress Users.
* Fix: When page caching is being used, probability of empty or superfluous visitor profiles being created drastically diminished.
* Fix: Send appropriate addresses to PayPal via PayPal Standard.
* Fix: Ensure that sales tax is not added to non-tax items when using a coupon.
* Fix: Ensure state text field is not visible at the same time as state dropdown.
* Fix: Ensure shortcodes respect display type as overridden by categories.  Note: Requires latest Gold Cart release.
* Fix: Ensure that adding a new item to the cart after a coupon code properly reflects that coupon code.
* Fix: Don't create cron events for unknown schedules.
* Fix: Shipping options were disappearing when "same as billing address" were selected.
* Fix: Protect against "Price From: " being zero if a sales price was set without a normal price.
* Fix: Added programmatic and UX checks for conflict where grid/list view could show products with variations without the variation drop-downs, adding an improper product to the cart.
* Fix: Improved shipping rate quote retrieval performance.
* Fix: Sends proper tax amount to PayPal Standard when carts are aggregated.
* Fix: Fixed pagination bug where improper number of paged links were returned.
* Fix: Ensure UPS Environment test mode works.
* Fix: Downloadable products were not being properly added as attachments.
* Fix: "Shipping Same as Billing" could cause products to be shipped to countries which are not allowed.
* Fix: Eliminate undefined index errors on sales log page when checkout fields are removed.
* Fix: Address edge case where purchase notifications are not sent when there are multiple email fields in different checkout form sets.
* Fix: Improved bbPress compatibility.
* Fix: Ensure User Account pages are properly overridable in the template stack.
* Fix: Properly escape classes for Buy Now button classes.
* Fix: Fix IE8 javascript conflicts.
* Fix: Shored up backwards compatibility for Session usage
* Fix: Ensure cart items with variations that are out of stock (and stock limited) don't return a fatal error.
* Fix: Address malformed XML output for shipping quotes from UPS and USPS.
* Fix: Ensure PayPal Express does not show coupons as an empty line item when discounts are not used.
* Fix: A redirection error on our wpsc_scale_image fallback was resolved.
* Fix: Ensure only available countries are shown as available in PayPal Express.
* Fix: Resolved issue whereby amounts over 1,000 (in a given currency) would be incorrectly sent to Google Analytics.
* Fix: Shipping calculator uses information set in user account profile, if set.
* Fix: When PATHINFO Permalinks ("Almost Pretty"), product pages would 404.
* Fix: Ensure 3.7.x -> 3.8.x upgrade routine uses wpdb::prepare() properly.
* New: AJAX API to get and set information about customers.
* New: Visitor API to get and set information about current and past visits.
* New: Introduced WPSC_Claimed_Stock class.
* New: Introduced WPSC_Stats class.
* New: Introduced WPSC_Product/WPSC_Products class.
* New: Introduced WPSC_Countries / WPSC_Region class.
* New: Totally revamped product editing and adding user interface.
* New: Added new Purchase Meta API.
* New: Some compatibility fixes for WordPress 3.9.
* New: Improved extendibility of the Import Settings area.
* New: Improved extendibility for our conditional coupon callbacks.
* Change: Iterated upon WPSC_Country class.
* Change: Improved UK Country Display.
* Change: Improved upgrade routines for currencies and countries.
* Change: Introduced filter for themeable packing slips.
* Change: Modified calculation for total price to ensure coupons don't reduce shipping and tax.
* Change: Improved Checkout workflow and UI.
* Change: Fully remove Google Checkout from WP eCommerce, as it has been deprecated.
* Change: Don't show transaction results to anyone but the purchaser
* Change: Eliminated usage of dynamic CSS and javascript in front-end and admin.
* Change: Couldn't use decimals in product's variation sale price.
* Change: Tons of performance improvements to USPS and UPS.
* Change: Add condition as parameter to wpsc_coupon_rule_property_options action.
* Change: Improved security for our customer cookie hash.
* Change: Don't default to base country of store in checkout fields.
* Change: Taxes class previously regarded regions as unique, when they are not. We now check both region and country where applicable.
* Change: By default, quantity boxes will be shown on new installs.

= 3.8.13.3 =
* Fix: Users disappear in Network Admin -> Users page (for multisite).
* Fix: User counts are incorrect when there are thousands separators.
* Fix: "Save Product Files" button doesn't like being clicked on.


= 3.8.13.2 =
* Fix: Anonymous customers should not be visible in admin UI.
* Fix: Cronjob to purge anonymous customers doesn't work due to memory issues.
* Fix: Anonymous cart items will be lost after signing in.

= 3.8.13.1 =
* Security and maintenance release

= 3.8.13 =

* New: Product Media UI.
* New: Cart Item Meta API
* New: 'wpsc_after_checkout_cart_rows' action.
* New: Add 'wpsc_default_dimension_unit' and 'wpsc_default_weight_unit' filters.
* New: Add 'wpsc_save_product_order' action and use it to save category product order in the term_relationships table.
* New: Add logic filter for coupons.  We currently have one for properties, this should sufficiently round out the extensibility for the UI.
* New: Allow gateway images to be filtered.
* New: Allow multiple comma-separated categories in coupon conditions.
* New: Product Gallery metabox.
* Change: Better Customer API.
* Change: Prepend (WPEC) to widget names in wp-admin
* Change: Replace default noimage.png with a better image.
* Change: Updated NL language
* Change: Updated german language strings
* Change: Use new spinner image in WordPress 3.8
* Fix: Add preg_quote() to coupon regexes that intend to parse strings. Fixes issue where unexpected results occur when strings contain slashes, or really, any regex-oriented characters.
* Fix: Change wpsc_get_remaining_quantity() to call the method dynamically, rather than statically.  The previous behaviour caused a strict standards warning.
* Fix: Database Upgrade Routine to rename old _wpsc_* product metadata array keys so they no longer include the '_wpsc_' prefix
* Fix: In Purchase log admin screen, item count is sum of quantity field rather than count of rows
* Fix: Invert logic on shipping ZIP code check in core theme files.
* Fix: Modify behavior in Download CSV functionality to properly convert region IDs to regions.
* Fix: Modify wpsc_update_item_quantity() to listen for wpsc_quantity_update, reverting to $_POST['quantity'] only if it exists.
* Fix: PHP strict warnings.
* Fix: Properly quote SKUs in CSV file.
* Fix: Rename generically named function.
* Fix: Stock and sale price empty values should be preserved.
* Fix: Variation thumbnail size in admin screen.
* Fix: cart is not initialized when some shipping modules are triggered
* Fix: redundant product thumbnail is displayed on category / single product list.
* Fix: variation checkbox column width in WP 3.7.
* Fix: Out of stock message for variations now correctly shows variation of product for the title, rather than main product.
* Fix: Use proper link in checkout for variation products.
* Fix: Return tax information in switchmethod() JS function.
* Fix: Resolve conflict with Advanced Custom Fields on save_post hook for products.
* Fix: Update Bulgarian Currency symbol.
* Fix: Resolve issue where updating pages causes rewrite rule conflicts when pretty permalinks are enabled.

= 3.8.12.1 =
* Fix: Fatal error in wpsc_product_list_exclude_child_categories() due to other themes
       or plugins calling get_posts() before the main query is set up

= 3.8.12 =
* New: Filter to hide drafts on the front-end: "wpsc_product_display_status"
* New: Shipwire staging / production option
* Change: Remove deprecated and unused javascript (and by extension, CSS) from core
* Change: Remove nusoap
* Change: Improvements to Marketing admin panel
* Change: Improvements to Products List Admin Page
* Fix: WP eCommerce pagination breaks WordPress pagination
* Fix: Wrong thumbnail size in Product Specials widget
* Fix: Filtering by a category also shows all products in subcategories
* Fix: admin.js throwing a Parse Error in Safari
* Fix: Product Image Links not working when over HTTPS
* Fix: If AJAX_URL returns HTTPS and Add to Cart is on HTTP, errors
* Fix: Add to Cart button doesn't work
* Fix: Can no longer upload customer attachments
* Fix: Deprecated warning for wp_convert_bytes_to_hr() function in WordPress 3.6


= 3.8.11.1 =
* Fix: Some users had theme compatibilities and other configurations that caused "Add to Cart" to no longer function as expected.
* Fix: Customer uploads for products broke with JS overhaul
* Fix: Product image links broke.

= 3.8.11 =
* New: Payment Gateway UI is revamped.
* New: Sales Log UI is revamped.
* New: wpsc_single_purchase_log_date_time_format and wpsc_single_purchase_log_date_format (Used in new Sales Log UI).
* New: Added 'wpsc_usps_domestic_rate' and 'wpsc_usps_intl_rate' filters.
* New: 'wpsc_country_get_property' filter.
* New: WPSC_Country::get_data() method and 'wpsc_country_get_data' filter.
* New: Added generic 'wpsc_save_' . $this->current_tab_id . '_settings' action.
* New: 'wpsc_shipping_module_settings_form' filter on admin shipping module form output.
* New: Choose either AND or OR for coupon conditions logic.
* Change: Reduced internal usage of get_option( 'siteurl' ) in favor of home_url().
* Change: Updated Colorbox.js to v.1.4.15.
* Change: Eliminated internal usage of 'index.php' as an AJAX callback URL.  Nginx users, rejoice.
* Change: Eliminated internal usage of eval() in javascript.
* Change: wpsc_get_country() uses WPSC_Country now.
* Change: .on() is now used in place of .livequery(), .bind(), .live() and .delegate().
* Change: Minor cleanup of Marketing settings tab UI.
* Change: wpsc_the_product_image() optimized to utilize the product thumbnail earlier in the function.
* Change: wpsc_the_variation_price() output is now filtered through wpsc_do_convert_price filter.
* Change: Submitted_Form_Data table now has a value field with a LONGTEXT data type, rather than varchar(255).
* Change: Continued internal cleanup of coding standards and documentation.
* Fix: Properly substitute placeholder in "Out of stock" email to administrator.
* Fix: Fixed PHP notices when accessing Dashboard as subscriber.
* Fix: Coupon logic improvements (edge case of using 'not_contains" logic with item_quantity).
* Fix: Automated fix when possible, and nag when not, for 3.8.10 regression in User Management templates.
* Fix: Error notices on category functions.
* Fix: fetch_feed() error on GetShopped news.
* Fix: Variation terms were not being applied to the parent product.
* Fix: If you emptied variation sale price, the stock levels would not work any more.
* Fix: A notice was thrown if you had UPS selected but had not saved settings for it.
* Fix: USPS Shipping Module now correctly respects testing environement settings.
* Fix: Correcting casting price value to float instead of integer in coupon logic conditions for sub_total.
* Fix: 'wpsc_transaction_result_content' now returns the $cart_item as an array instead of an object (which was a regression).

= 3.8.10 =
* New: Coupon UI is revamped.
* New: [Shipwire] Addition of Shipping Services, Economy, Plus & Premium.
* Change: After filling out a registration form, the user is logged in.
* Change: Limit quantity of individual products that can be added to the cart. Default value is filterable.
* Change: Remove FPDF which is not used any more.
* Change: Return cart item messages as key => value array rather than HTML.
* Change: Variation table class orderby to 'menu_order title'
* Change: When displaying packing slip, form data should be output in the same order as that of checkout page.
* Fix: "you save" prices are not correct on variations with a value over 1000
* Fix: Cart widget missing closing </div> tag.
* Fix: Display the "You save" price as a currency, not just a raw unformatted number
* Fix: Ensure all variations are deleted when deleting a product.
* Fix: Foreign characters are not displayed correctly in packing slip.
* Fix: Improved performance with variations.
* Fix: JavaScript error when no variations exist in admin.
* Fix: Make sure Add / Edit Product page respects decimal and thousands separators set in Settings->Store->General.
* Fix: Manual / Test Payment instructions don't appear on transaction results page and email.
* Fix: Only print Google Analytics scripts on transaction results footer
* Fix: PHP Warnings when bulk editing variations.
* Fix: Prevent old paypal express from hijacking new paypal express callback.
* Fix: Product category pages don't respect pagination and sort order settings.
* Fix: Re-generate products pages' IDs after plugin reactivation.
* Fix: Refactor user account page
* Fix: SQL IN error in wpsc_populate_also_bought_list().
* Fix: Switching a purchase log status to "Closed Order" doesn't reduce stocks.
* Fix: WPML compatibility
* Fix: Wrong cache set for checkout form data.
* Fix: split() deprecation notice in PHP 5.3+
* Fix: stripslashes issues when duplicating products.

= 3.8.9.5 =
* New: Polish translation.
* Fix: Attempt to regenerate customer ID if it's invalid to avoid fatal errors.
* Fix: Australia Post doesn't return any quotes if the products' dimensions are not specified.
* Fix: Billing state is set to shipping state upon checkout.
* Fix: Cannot resent purchase receipt any more.
* Fix: Custom Cart Messages on Sales Log need stripslashes.
* Fix: Error message when changing purchase log status via AJAX.
* Fix: Fatal error in merchant.class.php.
* Fix: Giving stock or price of a product a 4 figure number will screw it up when the product is updated again.
* Fix: Invalid zipcode is displayed even when the zipcode is not specified yet.
* Fix: Quick editing a product doesn't work.
* Fix: Transaction result emails are sent when an order is closed.
* Fix: More native css support for product variations in WP 3.5+.

= 3.8.9.4 =
* Fix: "No shipping quotes" error is displayed when selecting "Shipping same as billing".
* Fix: Avoid flooding transient data when site's feed is being visited by bots.
* Fix: Blank Checkout page for some certain installation when Shipping is enabled.
* Fix: Can't add to cart on some server.
* Fix: Fatal error when viewing product variation iframe, because _wp_admin_html_begin() does not exist on older WordPress versions.
* Fix: MySQL error/warning in _wpsc_filter_special_widget_where().
* Fix: Only show attachment fields when viewed on an edit product page.
* Fix: PHP Notice in cart.class.php.
* Fix: Properly escape columns in admin products page.
* Fix: Purchase log's item names are not translatable via qTranslate.
* Fix: Shipping same as billing saves "State" field label as the shipping region.
* Fix: Shipwire settings are not updated.
* Fix: Shipwire syncing doesn't take into consideration products in trash, or multiple products using the same SKUs (as in WPML)
* Fix: Specials widget is empty when there are no product with variation sales.
* Fix: Variation sets are not being sorted on the front-end.
* Fix: WPEC doesn't cooperate with WP 3.5 media UI.
* Fix: wpsc_pre_transaction_results filter is ignored.
* Make sure admin styles are enqueued for product variations iframe in older version of WordPress.

= 3.8.9.3 =
* New: Add support for quantity field in default grid view template.
* New: Let plugins add new column to product variation list table via filter 'wpsc_manage_product_variations_custom_column'.
* Fix: Blank transaction results page when using paypal gateways.
* Fix: Comment form is displayed even though comment is closed on products page.
* Fix: Fatal error in formatting.php for some sites.
* Fix: New countries in 3.8.9 automatically added to target market list.
* Fix: PayPal Standard doesnt' take care of coupons.
* Fix: Punctuation in pending transaction email.

= 3.8.9.2 =
* Fix: Admin cannot download file from product edit page.
* Fix: Compatibility issues with Jetpack.
* Fix: Google Analytics is printed out even when it's not active. Also, PHP notice when the product doesn't have a category.
* Fix: Make sure PayPal Standard works with 100% discount.
* Fix: Product page title is not properly replaced with the category or tag title in WP 3.5.
* Fix: Products are sometimes displayed even when products page is set to only display list of categories.
* Fix: Products thumbnail sizes are inconsistent with the settings.
* Fix: Properly flush outdated customer meta from the transient table.
* Fix: Typo in wpsc_cart::update_location().
* Fix: Wrong class attribute for price display.

= 3.8.9.1 =
* Fix: Fatal error in Shipwire.
* Fix: Fatal error with customer meta on multisite.
* Fix: Fatal error with get_the_product_tags().
* Fix: Pagination in admin product list doesn't work.
* Fix: Permalinks are incorrect when products page is set as home page and permalink scheme is set to %post_name%.
* Fix: Rewrite rules are not regenerated correctly when switching from "Default" to "Post name".
* Fix: Shipwire and Google Analytics are not working properly.
* Fix: Shipwire request is sent even when Shipwire is not active.
* Fix: States are displayed as numbers in admin purchase report.
* Fix: Transaction results displaying cart content twice as well as "Oops, there is nothing in your cart".
* Fix: Use customer meta instead of $_SESSION for eway and payflow message.
* Fix: Variation pagination doesn't work.

= 3.8.9 =
* New: Additional columns can now be added to Store Sales page via filter hook.
* New: Additional filters for purchase log notification classes.
* New: Allow devs to filter the list of accepted credit cards in PayPal Pro.
* New: Filters to enable image scaling (versus cropping).
* New: Hook to modify the product table on transaction results and purchase receipts.
* New: Make buy now button's html output filterable.
* New: More flexibility in role management.
* New: New Hook for cancelling subscriptions with Memebers Access.
* New: Pagination UI for product variations.
* New: Users can bulk edit products' stock, price, sales price etc.
* New: Users can now choose to "Notify site owner" and "Unpublish product" separately when stock runs out.
* New: Users can now delete checkout form sets.
* New: Users can now set product sorting direction in Settings->Presentation.
* New: Variation UI enhancements.
* Change: "Registration required" and "Anyone can register" are now synchronized.
* Change: Default checkout shipping option to lowest shipping cost of all choices.
* Fix: "Variation Controls" anchor in metabox leads nowhere.
* Fix: AJAX code audit (security)
* Fix: Activating WP eCommerce on a vanilla WordPress installation with pretty permalinks enabled messes up the rewrite rules.
* Fix: Add a space after tilde character to avoid confusion in admin product list.
* Fix: Add new variation sets and terms from Edit Product page doesn't work properly.
* Fix: Additional description is double escaped in product edit page.
* Fix: Alignment of checkboxes and radios in product category edit page is a bit off.
* Fix: Avoid using query_posts() which could break themes.
* Fix: Backslashes are sometimes added to UPS and USPS settings.
* Fix: Base_region option remains set if base country is switched.
* Fix: Billing phone number not accessible via wpsc_merchant::$cart_data.
* Fix: Buy Now feature doesn't properly create and update sales log.
* Fix: Can't add variations on new products.
* Fix: Can't delete coupon conditions.
* Fix: Can't order products properly in Products admin page.
* Fix: Can't upload product category image when adding a new category.
* Fix: Cannot set thumbnail for variations.
* Fix: Cart item name is not updated when corresponding variation name is changed.
* Fix: Cart items are not translated for qTranslate.
* Fix: Cart mix up when multisite is enabled.
* Fix: Category image size metadata are not used properly on templates.
* Fix: Category title is replaced with the first product's title, and pagination does not work correctly on Category page.
* Fix: Changing an order status in Sales Log page incorrectly updates the views and pagination links.
* Fix: Changing checkout field sort order doesn't work.
* Fix: Checkout- Parent Product Thumbnail Displayed Instead of Product Variation Thumbnail.
* Fix: Claimed stock cronjob doesn't take into consideration timezone.
* Fix: Collapse product variation sets by default on Manage tab.
* Fix: Colorbox Gallery doesn't work.
* Fix: Country dropdown lists disregard Target Market option.
* Fix: Coupon is not invalid is displayed even when there's no coupon applied.
* Fix: Coupon start and expiry date don't take into account local timezone.
* Fix: Currency converter doesn't work for some particular currencies.
* Fix: Custom fields are missing in purchase log if they have the same name as other fields.
* Fix: Customers are able able to purchase scheduled but unpublished products.
* Fix: Database upgrade routines.
* Fix: Division by zero in product-template.php.
* Fix: Dollar signs are used in flat rate settings regardless of the main currency.
* Fix: Duplicating a Product doesn't duplicate the images correctly.
* Fix: Email information in "Admin" settings tab is not displayed properly.
* Fix: Enabling shipping but not selecting any shipping methods causes frustrating unexpected issues.
* Fix: Fatal error in PayPal Pro settings page.
* Fix: Fatal error in checkout page form validation.
* Fix: Fatal error when quick editing products.
* Fix: Fatal error when upgrading from 3.7.x.
* Fix: Final breadcrumb id tag is not W3C Compliant.
* Fix: Free shipping discount is not updated when shipping method is changed.
* Fix: Free shipping doesn't reset individual cart items' shipping amounts when submitted to PayPal.
* Fix: Free-shipping doesn't work on PayPal Pro if the discount amount > item total.
* Fix: Google Analytics is now tracking correctly.
* Fix: Great Britain is redundant in country list (we already have U.K. and North Ireland).
* Fix: Improper escaping of user input.
* Fix: Inaccurate dimension calculation in Australia Post.
* Fix: Incompatibility with $_SESSION.
* Fix: Incorrect i18n in Presentation tab.
* Fix: Increase gateway timeout settings across the board.
* Fix: Inefficient pinging when product is updated.
* Fix: Infinite loop on single product page.
* Fix: Infinite loop when using PayPal Pro.
* Fix: Issue with SSL and Share This URL.
* Fix: Latest product widget doesn't show image.
* Fix: Logic error in function wpsc_product_has_stock.
* Fix: Make it clear that the sidebar widget doesn't include discount.
* Fix: More flexibility in role management.
* Fix: New coupon conditions are added below the first condition instead of the last.
* Fix: Number of products per page field in shortcode generator doesn't work.
* Fix: On user details page, switching country to a country without region won't display the State (Region) text field.
* Fix: Only display permalink double save warning if WordPress version is earlier than 3.3.
* Fix: Outdated country codes and currency codes.
* Fix: Pagination does not work with price range widget.
* Fix: Pagination for category short codes does not work.
* Fix: Pagination for tags does not work.
* Fix: Pagination links generated with unnecessary "page/" portion for WP 3.4.
* Fix: Pagination links in Category shortcode page are not consistent.
* Fix: Parent product is still treated as if it had variations even though all its variations have been moved to trash or hidden (set to draft).
* Fix: PayPal Express Checkout doesn't take into account free shipping.
* Fix: PayPal Standard Subscriptions produce unexpected product title in PayPal cart and receipts.
* Fix: Performance improvement for dynamic stylesheet.
* Fix: Placeholder image is missing for products without thumbnails.
* Fix: Potential height issue with variation edit iframe (parent hidden overflow, expand/collapse checkboxes).
* Fix: Prevent segmentation fault when using wpsc-products shortcode.
* Fix: Product Specials Widget does not work with product variations on sale.
* Fix: Product page URLs are sometimes not updated properly.
* Fix: Product permalinks in single product view are not using current category path.
* Fix: Products page pagination fails if the products page is set as the homepage.
* Fix: Products with variations on sale does not display the prices correctly.
* Fix: Purchase logs list table doesn't have Total line as before 3.8.8.
* Fix: Refactor purchase log notifications to fix various issues:
* Fix: Region field is not consistently updated or displayed when selecting a country without regions on checkout form.
* Fix: SKU should be sent to PayPal Standard instead of Product ID if that's available.
* Fix: Shipping error is reported when "Shipping same as billing" is selected on the checkout page, even when shipping is disabled.
* Fix: Shipping location error message is displayed before the customer has a chance to specify state and zip code.
* Fix: Shipping method and option are not displayed on Sales Log single page if there is no shipping form fields.
* Fix: Shipping options not refreshed when "shipping same as billing" causes new quotes.
* Fix: Shipping rate choice not correctly encoded during checkout.
* Fix: Shipwire settings are broken.
* Fix: Shortcode button doesn't work in visual mode if WP folder configuration is different from default.
* Fix: Table rate shipping doesn't accept $0 amount.
* Fix: Target makret restrictions for product categories is broken.
* Fix: Tax bands not working.
* Fix: Template tags for product tags don't work.
* Fix: Terms & Conditions validation code is broken.
* Fix: Total Price not properly reflective of the total after $1,000.
* Fix: Transaction results refactor.
* Fix: Updating purchase log status doesn't update pagination count
* Fix: Unnecessary thumbnail regeneration on product single page.
* Fix: Update message is displayed even when the db has bene updated.
* Fix: Updating region when "Same shipping as billing" is checked doesn't update the shipping quote.
* Fix: Use $cart_item->get_title() instead of $cart_item->product_name.
* Fix: Use array instead of strings when calling WP_Query in wpsc_the_variation_price.
* Fix: Users are now warned that setting products per row for grid view to 0 would probably lead to layout breakage.
* Fix: Using 2 checkout sets shows incorrect order on Purchase History and Your details pages.
* Fix: Valid checkout fields are not preserved when there are invalid fields.
* Fix: Variation drag'n'drop sorting is not working.
* Fix: Various bugs and inconsistencies with coupon conditions.
* Fix: WPEC loads product image then scales to thumbnail size in the backend products list.
* Fix: Weird Taxable Amount column.
* Fix: When "Free Shipping" is enabled in Settings->Shipping, shipping is always set to 0 even when shipping discount value is set to 0 or empty.
* Fix: Wrong alternate row class for variation inline shipping editor.
* Fix: Wrong documentation link for Chronopay.
* Fix: Wrong logic in deprecated function nzshpcrt_currency_display().
* Fix: _wpsc_process_transaction_coupon() contains typos.
* Fix: also bought image path is supposed to be fixed already.
* Fix: i18n audit.
* Fix: improper escaping in Price metabox.
* Fix: post_status is not formatted correctly in wpsc_start_the_query().
* Fix: private products are listed on the /products/ page.
* Fix: wpsc_list_dir() should return empty array if directory is empty.
* Fix: wpsc_shopping_cart() function call doesn't work.
* Fix: wpsc_the_product_thumbnail() ignores custom width and height.

= 3.8.8.5 =
* Fix: Order Closed status does not count in the sales log totals on dashboard widget.
* Fix: PHP preg_replace warning.

= 3.8.8.4 =
* Fix: Also bought product image doesn't display correctly.
* Fix: Pagination fails when hierarchical category URL is enabled.
* Fix: Product Specials Widget causes 404 errors. Props Chris Jean.
* Fix: Product category slug beginning with a digit doesn't work.
* Updated localization.

= 3.8.8.3 =
* Fix: Incompatibility between WPEC products page pagination and WordPress 3.4.

= 3.8.8.2 =
* Change: CSV sales export now puts the item quanity in a separate column from the product title.
* Fix: Core checkout fields cannot be restored if they were deleted before upgrading to 3.8.8.
* Fix: Insecure SSL resources when WordPress is using SSL, or "Force SSL Checkout" is enabled.
* Fix: Issue with WPML and Variations.
* Fix: Shipping & Total Order values are wrong on Sales Log page.
* Fix: Terms and Conditions - Checkout page breaks if you read the terms and conditions.
* Fix: Total shipping value and total price incorrect in transaction results.
* Fix: Variation sales prices are inaccurate in Product Specials Widget

= 3.8.8.1 =
* Fix: CSV import not working in 3.8.8.
* Fix: Category page display setting does not override default Presentation settings.
* Fix: Clicking All/None in Category target market settings doesn't work.
* Fix: Non-SSL stylesheet is loaded even when force SSL for checkout page is on.
* Fix: PHP Warning and Notice in sales log page.
* Fix: PayPal currency converter is wrong.
* Fix: Sales logs are not displayed for some installations.
* Fix: Sales logs page sometimes display empty customers' names.
* Fix: Saving payment gateway settings erase Shipping settings.
* Fix: Sometimes clicking Save doesn't save tracking ID on sales log page.

= 3.8.8 =
* New: Actions for bulk actions on sales page: wpsc_sales_log_process_bulk_action, wpsc_sales_log_extra_tablenav
* New: Allow variation checkboxes to be collapsed & expanded without having to tick the variation set checkbox itself.
* New: Digital Download UI improvement.
* New: Extra hooks during checkout cart display: wpsc_before_checkout_cart_row, wpsc_before_checkout_cart_item_image, wpsc_after_checkout_cart_item_image, wpsc_before_checkout_cart_item_name, wpsc_after_checkout_cart_item_name, wpsc_after_checkout_cart_row
* New: Filter for ordering the sales logs: wpsc_purchase_logs_orderby.
* New: Filter wpsc_cart_shipping.
* New: Filter wpsc_default_shipping_quote.
* New: Filter wpsc_item_shipping_amount_db.
* New: Filter wpsc_paypal_standard_post_data.
* New: Filter wpsc_product_permalink_cat_slug.
* New: Filter wpsc_product_postage_and_packaging.
* New: Filter wpsc_shipping_quote_value.
* New: Filters wpsc_calculate_total_tax, wpsc_coupons_amount.
* New: 4 new filters for user meta in profile page and during checkout.class.php - wpsc_checkout_user_profile_get - wpsc_checkout_user_profile_update - wpsc_user_log_get - wpsc_user_log_update.
* New: Settings Page API.
* New: Some helpful filters to download_csv function: wpsc_purchase_log_start_end_csv, wpsc_purchase_log_month_year_csv, wpsc_purchase_log_month_year_csv, wpsc_purchase_log_csv_headers, wpsc_purchas_log_csv_output
* New: Variation Drag & Drop sorting.
* Change: Display file names instead of the product name on the downloads page.
* Change: Improved variation UI in Product Edit page.
* Change: In General Settings page, when changing country, load region / state list using AJAX instead of page reload.
* Change: Only display variants' associated terms in Product Edit page rather than including the parent product name, which is redundant and cluttered.
* Change: PayPal Standard settings now just offer dropdown of "live" / "sandbox" rather than URL entry.
* Change: Subtle UX tweaks for Store Settings page.
* Fix: %-based shipping cost is not working internationally.
* Fix: $wpsc_query->query_vars['wpsc_product_category'] not always set on product page with hierarchical category permalinks.
* Fix: Cannot add new Checkout field.
* Fix: Cannot re-re-send buyer receipt.
* Fix: 3.7 -> 3.8 Database Upgrade Routine causes some products with variations to display a $0.00 price.
* Fix: Double <p> tag with wpec_taxes_display_tax_bands() on product edit page.
* Fix: Fatal error when trying to include a non-existent admin file.
* Fix: Hide Google Feed information from display
* Fix: Incompatibility with Genesis framework in Product Edit page.
* Fix: Make the WP eCommerce 3.8.x activation/installation routine much more efficient.
* Fix: Mandatory fields in user_log_functions.php are not properly validated.
* Fix: Memory improvement for productfeed.
* Fix: Purchase logs have incorrect time if a timezone is specified in Settings->General.
* Fix: Summary line does not get updated when sales log status is changed.
* Fix: Total Quantity check fails for coupon codes.
* Fix: USPS: "FLATE RATE ENVELOPE" should be changed to "VARIABLE" to allow for shipping quotes to be based on weight.
* Fix: Use ->add_help_tab() to support WP 3.3 admin screen API.
* Fix: Using discount causes Paypal Express to calculate wrong total amount.
* Fix: get_the_content() is not enough in product feed.
* Fix: settings page JS compatibility issue with Firefox.
* Fix: wpsc_get_template_file_url() function is inefficient and causes 10 extra SQL queries per page load.

= 3.8.7.6.1 =
* Fix: PHP Warning for 'wpsc_load_settings_page' callback.
* Fix: PHP Warning in wpsc-transaction_results_functions.php.
* Fix: get_current_screen() is not available in WP 3.0.

= 3.8.7.6 =
* New: Default hook to filter sessionid for previously selected payment gateways.
* Change: Stock notification emails are now sent to "purchase log email address" rather than admin address.
* Fix: SQL injection vulnerability.
* Fix: 3.7 -> 3.8 Database Upgrade Routine fails when importing variations sets with the same name.
* Fix: Category link structure is not correct in pagination links with hierarchical category permalink.
* Fix: Fix SQL error when using "?items_per_page=all" query.
* Fix: Missing "Use as product thumbnail" on WordPress 3.3.
* Fix: Products Page does not support custom page template - uses page.php instead.
* Fix: Products page, category items per page is broken, relies on 'posts per page' setting in Settings -> reading.
* Fix: Sticky post view broken due to deprecated query_string filter.
* Fix: Sub-pages of Products Page aren't supported.
* Fix: Total Quantity check for coupons.
* Fix: Use ->add_help_tab() to support WP 3.3 admin screen API.
* Fix: [wpsc_products] shortcode does not use 'Sort Product By' setting - it defaults to date-based ordering.
* Fix: dashboard.css is loaded for WordPress > 3.3 (404 error).

= 3.8.7.5 =
* Fix XSS vulnerability.

= 3.8.7.4 =
* Fix: "Session expired" error when viewing Customer Account page.

= 3.8.7.3 =
* Security fixes.

= 3.8.7.2 =
* New: Support for g:availability to Google Merchant Centre feed. Props bbaskets & longercat
* Fix: Admin product page sorting by column was not working.
* Fix: Call to undefined function wpsc_clear_stock_claims().
* Fix: Checkbox and Radio buttons have unexpected issues in checkout.
* Fix: Checkout field options' values are mutilated before getting inserted into the database.
* Fix: PayPal standard IPN fails if data used to validate the IPN POST contains ' or ".
* Fix: Price incorrectly updated when multiple products with variations exist on the same page.
* Fix: Security vulnerability.
* Fix: ShareThis integration does not properl respect HTTPS connections.
* Fix: The Terms and conditions checkbox on the checkout page should have a required field asterisk, just like all other required checkout fields do.
* Fix: Wrong login URL for [userlog] page.
* Fix: Zero shipping price doesn't work in flatrate.php.
* Fix: invalid HTML on checkout page for default theme.
* Fix: wpsc_coupons::uses_coupons() needs optimization.

= 3.8.7.1 =
* Fix: Fancy notifications not being displayed on single product page.
* Fix: Sale and normal prices are switched around.

= 3.8.7 =
* New: 'insert_child_product_meta' filter to allow customising of meta data when a variation product (child product) is created.
* New: 'wpsc_variation_groups' and 'wpsc_all_associated_variations' filters. Allows customising order of variation menu items etc.
* New: Add hook "wpsc_product_form_fields_end" and rename "wpsc_product_form_fields" to "wpsc_product_form_fields_begin" to offer more flexibility when adding new fields to the product form.
* New: Add hooks "wpsc_add_to_cart_button_form_begin" and "wpsc_add_to_cart_button_form_end" when outputting [add_to_cart] shortcode. wpsc_add_to_cart_button() is also refactored to get rid of ugly repetitive "$output .= ".
* New: Additional hooks for adding extra FORM fields to the add to cart form.
* New: Additional filter hooks for product price and transaction result messages.
* New: Allow hierarchical product category URL.
* New: Hooks 'wpsc_product_form_fields_begin' and 'wpsc_product_form_fields_end' for list and grid views.
* New: Option to set claimed stock clearance interval (Store Settings -> General).
* New: filterable g:shipping_weight to google product feed. Thanks to Rudy Hassall.
* Change: Proper post updated messages - now says "Product updated" instead or "Post updated".
* Change: Settings tabs are restyled to conform with WordPress UI. Props Pippin.
* Fix: A product is displayed as "sold out" when its variations' stock control options are turned off.
* Fix: Additional Checkout Form Fields Not Showing with Variation. Props jRayx.
* Fix: Australia Post shipping quote caching by reducing the transient key length from 51 to 41 characters.
* Fix: AJAX error when changing country with a coupon applied.
* Fix: Breadcrumbs not showing for empty product categories.
* Fix: Discounts / coupons not passing to Paypal Standard.
* Fix: Error with merging image metadata in Media popup. Props Ben Huson.
* Fix: Fancy notifications are sometimes output twice.
* Fix: Fatal memory allocation error in Add Product page when WPEC downloadable folder is not created yet.
* Fix: Incompatibility with WP 3.3-dev (dashboard.css is merged into wp-admin.css).
* Fix: JavaScript globals not properly escaped, causing fatal JS errors due to internationalized strings that have single quotes in them.
* Fix: Paypal Express Checkout fails if product names contain unicode special characters.
* Fix: Paypal Standard passes wrong discount amount.
* Fix: PHP Notice when activating wpec, resulting in 'unexpected output' error message.
* Fix: PHP Notice when editing product category.
* Fix: PHP notice in wpsc-functions.php.
* Fix: Product categories sometimes disappear in admin (but still show on front end).
* Fix: Product variations not deleted when deleting a product from the trash.
* Fix: Products with variations display 'from' even if all prices are the same. Change: Product that have variations will display "from" price regardless of whether the variations are in stock or not (to avoid inconsistency).
* Fix: Removing additional description of a product doesn't have any effect. Props groques.
* Fix: SQL error when updating from 3.7.
* Fix: Saving thumbnail settings caused PHP timeout.
* Fix: Single product image is not re-generated properly sometimes.
* Fix: User submitted data is double-escaped, causing slashes to be stored in database table and sent to payment gateways.
* Fix: Variations are not cloned properly when duplicating a product, causing error / hang in wp-admin.
* Fix: WP Menus items don't apply current-menu-item (and ancestor) for product categories.
* Fix: When on the edit products page, the filter by category menu does not show subcategories if the parent has no products. Props benjaminhuson.
* Fix: for paypal express, coupons being included when there are none
* Fix: is_home() is true when viewing default category on products page.
* Fix: login redirect on checkout page if WordPress files are in a subfolder.
* Fix: $wpsc_cart->use_shipping() returns true even when shipping is disabled.
* Fix: wpsc_display_purchlog_totalprice() returns wrong value. Props dlingren for initial patch.
* Fix: wpsc_is_single_product() returns false even when viewing a single product. Props Ben Huson for initial patch.
* Fix: wpsc_the_product_thumbnail() sometimes returns relative URL instead of absolute URL, which causes image failure in Google Product Feed.


= 3.8.6.1 =
* Fix: Security vulnerability in chronopay.

= 3.8.6 =
* New: Filter for 'wpsc_display_product_multicurrency'.
* New: Additional filters for compatibility with WPML.
* Change: Discount information is now displayed on the Packing Slip.
* Fix: Sometimes wpsc_product_has_multicurrency() returns true when it should return false.
* Fix: Edit product page makes many database queries if you have lots of variations.
* Fix: Variation prices are not calculated correctly.
* Fix: Request-URI Too Large error when searching on the Admin Products page with lots of products.
* Fix: Add to cart shortcode doesn't display fancy notifications, and also doesn't check whether variations are selected.
* Fix: Infinite AJAX loop on checkout page when Same as Billing is checked.
* Fix: Per-item shipping total is calculated incorrectly in various places (Packing Slip, Sales Report etc.).
* Fix: Wrong product link when the product is assigned multiple categories.
* Fix: Canonical tags are not generated correctly for products with multiple categories.
* Fix: Edit variations inline messes up columns when stock limitation is disabled.
* Fix: Product with no category selected is not automatically assigned a default category when sort by drag&drop is enabled.
* Fix: Unnecessary use of livequery in variations.js causes Product edit page to freeze when there are many variation sets.
* Fix: Slashes added to checkout form field that has single quotes in it (e.g: O\'Connor).
* Fix: Multicurrency price is truncated when displayed.
* Fix: Checkout page keeps refreshing without displaying Google Checkout Button.
* Fix: Discount doesn't work with Paypal Standard.
* Fix: wpsc_single_template is not removed from the_content after it's run, causing subsequent the_content() calls to output the single product again.
* Fix: Sorting product categories is broken.
* Fix: Coupon rule "In Category" not taken into consideration.
* Fix: WPEC default stylesheet imposes #content font-size.
* Fix: Incompatibility with Prototype JS library.
* Fix: Checking (or unchecking) Stock checkbox when editing product causes variation table columns to break.
* Fix: jQuery 1.6 incompatibility with attr( 'className' ).

== Frequently Asked Questions ==

= How do I customize WP eCommerce =

First of all, you should check out the Presentation settings which are in the Settings -> Store page.

For advanced users, all of our eCommerce templates and CSS can be moved into your active theme folder for customization. Less advanced users have [several fantastic WP eCommerce consultants](https://wpecommerce.org/hire-an-expert/) to get help from.

= What payment gateways does WP eCommerce integrate with? =

Our [Gold Cart plugin](https://wpecommerce.org/store/premium-plugins/gold-cart/) contains nearly a dozen payment gateway options, including Authorize.net, LinkPoint and DPS.  Our [Mirijeh integration plugin](https://wpecommerce.org/store/premium-plugins/mijireh-99-payment-gateways-pack/) provides access to nearly one hundred different gateway options.  Beyond that, we have individual integration plugins for [Braintree](https://wpecommerce.org/store/premium-plugins/braintree-payment-gateway/), [Stripe](https://wpecommerce.org/store/premium-plugins/stripe/), [Authorize.net SIM](https://wpecommerce.org/store/premium-plugins/authorize-net-sim-payment-gateway-for-wordpress/) and more.

= I want to integrate a payment gateway that you don't support. Can you help with that? =

Absolutely!  We have a [growing list of trusted consultants](https://wpecommerce.org/hire-an-expert/) who would be happy to partner with you to build a custom gateway.  Alternatively, you're more than welcome to request that we build it as a plugin or part of our core plugin.  The more requests we get, the more likely we are to build it.  If you happen to be a talented developer, we have a great documentation site with helpful information on building payment gateways

= Which couriers does the plugin integrate with? =

We have table rate, weight rate and flat rate shipping calculators.

We also currently integrate with UPS, USPS, Australia Post and Shipwire for real-time shipping rates.  We have a premium plugin for [FedEx](https://wpecommerce.org/store/premium-plugins/fedex-shipping-module/) as well.

= Which multi-lingual plugins is WP eCommerce compatible with? =

We currently provide basic support for WPML and qTranslate.  We're excited about an up-and-coming multi-lingual project called [Babble](https://github.com/cftp/babble).  We intend to fully and canonically support Babble as our primary multi-lingual partner in releases to come.

= Which additional modules are available for use with WP eCommerce? =

* [Gold Cart](https://wpecommerce.org/store/premium-plugins/gold-cart/) - adds more options, gateways and functionality to your store
* [MP3 Audio Player](https://wpecommerce.org/store/premium-plugins/mp3-player-plugin/) - Preview audio clips on your website
* [NextGen Gallery Buy Now Buttons](https://wpecommerce.org/store/premium-plugins/nextgen-gallery-buy-now-buttons/) - turns your NextGen gallery into an eCommerce solution
* [Members Plugin](https://wpecommerce.org/store/premium-plugins/membership-subscriptions/) - Integrates with PayPal Standard and Authorize.Net, allows you to create a Subscription-Based Product, as well as a "Members-Only" website with paid access to restricted content.

= How do you provide support? =

For users who have purchased a premium plugin from us (or a have purchased a [premium support token](https://wpecommerce.org/store/premium-plugins/premium-support-token/)), we offer high-touch, 1-on-1 support from our trained support staff.  If you submit a support request here, you'll get a helpful response within 24 hours or less (during business hours).

For the rest of our users, we do our best to monitor the WordPress.org support forums and respond in as timely a manner as we are able.  We appreciate the countless helpful volunteers who also take time to respond to forum posts here.

== Screenshots ==

1. Products list in WordPress backend
2. Edit Product screen
3. Single product page
4. Checkout page

== Upgrade Notice ==

= 3.8.1 =
This version addresses several urgent issues when upgrading from 3.7.x to 3.8.
