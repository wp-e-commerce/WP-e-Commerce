=== WP e-Commerce ===
Contributors: mufasa, jghazally, valentinas, mychelle
Donate link: http://getshopped.org
Tags: e-commerce, wp-e-commerce, shop, cart, paypal, authorize, stock control, ecommerce, shipping, tax
Requires at least: 3.0
Tested up to: 3.1.1
Stable tag: 3.8.3

WP e-Commerce is a Web 2.0 application designed with usability, aesthetics, and presentation in mind. 

== Description ==

The WP e-Commerce shopping cart plugin for WordPress is an elegant easy to use fully featured shopping cart application suitable for selling your products, services, and or fees online.

WP e-Commerce is a Web 2.0 application designed with usability, aesthetics, and presentation in mind. 
 
Perfect for:

* Bands & Record Labels
* Clothing Companies
* Crafters & Artists
* Books, DVDs & MP3 files
* Memberships
* Ticketing

For more information visit [http://getshopped.org](http://getshopped.org "http://getshopped.org")

== Installation ==

1. Upload the folder 'wp-e-commerce' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress

= Updating =

Before updating please make a backup of your existing files and database. Just in case.
After upgrading from earlier versions look for link "Update Store". This will update your database structure to work with new version.


== Changelog == 
= 3.8.3 =
* New: Individual item details are sent to Paypal Express Checkout
* Change: Automatically reload database update page when PHP maximum execution time is detected
* Change: Add progress bar and estimated time remaining for database update tasks
* Change: Themes can now use taxonomy-wpsc_product_category-{$term}.php and taxonomy-wpsc_product_category.php templates, which take precedence over page.php when viewing a product category
* Change: Paypal Express Checkout API is updated to ver 71.0
* Fix: Tax is calculated incorrectly when a coupon is used
* Fix: Update a large database of products and variations take ages
* Fix: Reloading database update page makes wpec scan the records from the beginning instead of continuing where it left off
* Fix: Reactivating the plugin causes Fatal Error (PHP Timeout) if there are a lot of attached images (not just post products, but all image attachments)
* Fix: Purchase logs' statuses are not properly updated when upgrading from 3.7.x
* Fix: Billing state is not sent to checkout
* Fix: Country name is truncated when sending to payment gateway
* Fix: Billing state code is not properly converted before sending to payment gateway
* Fix: Wrong USA country code is sent to Paypal Standard Payment
* Fix: Wrong sandbox gateway URL for Paypal Pro
* Fix: SSLVERIFY error when connecting to Paypal Pro Gateway
* Fix: Template hierarchy error with child themes
* Fix: Total amount is not visible when checking out with Paypal Express Checkout
* Fix: Transaction result page is inaccurate after checking out with Paypal Express Checkout
* Fix: Incompatibility with Thesis theme's loop when viewing product category, or paginated product listing

= 3.8.2 =
* Add: Currency display for Google RSS feed
* Add: Third-party plugins can now filter 'wpsc-tax_rate' to provide their own tax solution
* Change: Merchant subclasses now have access to $this->address_keys
* Change: Grid Settings are now always visible
* Change: Total Shipping is no longer included in notification email when shipping is disabled
* Change: Thumbnail size for single product view now defaults to Single Product Page thumbnail size option
* Change: wpsc_the_product_thumbnail() defaults to 'medium-single-product' size when in single product view
* Fix: Update notice being displayed when it has already been completed
* Fix: Broken image in latest products widget
* Fix: Custom checkout field not always saved
* Fix: Downloadable file list not updated after existing files are selected
* Fix: Already attached downloadable files are duplicated each time you select an existing downloadable file
* Fix: Inconsistent behavior when adding a new field to a checkout form set
* Fix: Custom product slug not editable
* Fix: Incompatibility issues with shipping helper and modules
* Fix: Product meta are not included in Google product feed
* Fix: Incorrect variation "from" price
* Fix: Shortcode not working in single product description
* Fix: Item cost not correctly calculated in paypal-standard-merchant
* Fix: Invalid SSL URL for some images
* Fix: Select from wrong table in WPSC_Merchant::get_authcode()
* Fix: Wrong use of get_query_var() in wpsc_category_id()
* Fix: Table `wordpress.wp_wpsc_product_list` doesn't exist
* Fix: ?items_per_page=all is ignored
* Fix: Duplicate transaction result emails
* Fix: Wrong filter in wpsc_item_add_preview_file()
* Fix: Wrong display type when using advanced search view mode and viewing a category
* Fix: Category list is displayed in tag archive
* Fix: wpsc_display_products_page() outputs "Fail" when the product shortcode is used 10 times (no kidding)
* Fix: Single product view's thumbnail size is incorrect
* Fix: Wrong featured thumbnail is displayed in Single Product View when there are multiple attached product images
* Fix: Incorrect condition statements in WPSC_Coupons::compare_logic()
* Fix: Can't add new field to checkout form set in IE
* Fix: Missing trash icon when adding custom options to dropdowns in checkout form
* Fix: Custom select, checkbox and radio fields are displayed as textbox on [userlog] page
* Fix: Custom checkboxes, radios and select fields are not properly populated in Checkout form
* Fix: Attachment metadata are not properly generated when converting product thumbnails from 3.7.x to 3.8

= 3.8.1 =
* Fix: Special price mix-up when ugprade to 3.8
* Fix: Missing database update notice
* Fix: Breadcrumb markup and style fixes
* Fix: Deprecate WPSC_Query()
* Fix: Deprecate wpsc_total_product_count()
* Fix: Deprecate wpsc_print_product_list()
* Change: Warning message for PHP 4 users. GoldCart requires PHP 5 or above.
* Change: Don't display categories when there's a search

= 3.8 =
* Utilize custom post types for products
* Utilize custom taxonomy for categories and variations
* Database optimization
* Redesigned taxes and shipping systems
* New user interface
* Integrates with WordPress Media Manager
* Better template integration for designers
* Optimized for ticketing (Tikipress)

= 3.7.5.3 =
* Support for WordPress 2.9 canonical URLs for Products and Categories

= 3.7.5.2 =
* More Fixes to the Paypal Pro merchant file
* Image thumbnail size fixes
* Updated readme to mark plugin as working with 2.9
* Purchase log filtering bug fixed
* Fix for a bug when no shipping module is used where the shipping country and region were not being changed
* Remove button on checkout page now clears stock claims

= 3.7.5.1 =
* Fixes to the Paypal Pro merchant file
* Fixes to the Paypal Express Checkout merchant file
* Tracking email improvements
* HTML in descriptions does not break RSS (thanks to http://www.leewillis.co.uk)
* Category permalinks will now be regenerated properly on instalation
* Category list bug preventing viewing a product when viewing a category fixed.


= 3.7.5 =
* Added code for upgrades/additions from nielo.info and lsdev.biz,  we will be using this for new modules in the future.
* All In One SEO Pack compatibility bugfixes and improvements.
* CSV has had some work done on it, it now takes larger files, and associates a CSV file to a single category of your choice. We'd love to be able to allow users to add the categories and images as part of the CSV file. We will look into it more at a later date.
* SSL we fixed the image issue from beta1 and used James Collis recommended fix (using is_ssl() for our conditions) Thanks James!
* Show list of Product Groupsí shows all Groups <- there may be some backwards compatibility issues (we havent encountered any but nevertheless if you spot any let us know)
* When duplicating products, their tags do not get duplicated for the new product. <- Oh yes they DO!
* Google Checkout now sends off Discount Coupons As well. And we fixed the `name` vs `code` Issue people mentioned in the forum
* Category shortcode backwards compatibility
* Fix Purchlogs - We had a lot of users that somehow by passed the 'fix purchase logs' page when upgrading from 3.6, so we added some better conditions to the mix and added it on to the debug page (a powerful wp-e-commerce page that is hidden from most users as it's usage is very corrosive backing up your DB and files is strongly recommended if not necessary when you work with this page).
* Valid XHTML for front end of wpec YAY!
* Fixed adding variations when adding products
* Sender from the 'resend email to buyer' link on the purchase log details page has been fixed
* Shipping Discount Bug that stopped shipping working at all.
* Categories Widget has had numerous changes ñ
* Better MU support. 
* Canadian Tax ñ Fixes
* US Tax ñFixes
* Product Categories Caching Issue  Resolved
* Coupons ñ ëApply to all Productsí and numerous bug fixes
* ëYour Accountí  done some fixes to it.
* ëAccepted Paymentí goes straight to ëClosed Orderí
* Stock claims are now cleared when the cart is emptied
* Purchase log bulk actions now work
* PayPal gateway module fixes and improvements
* HTML Tables can now be added to product descriptions
* Flat Rate and Weight Rate improvements


= 3.7.4 =  
* Changes to shipping to fix the bugs from 3.7.3 with shipping and the new shipping_discount feature
* Fixes for variations under grid view


== Frequently Asked Questions ==

= How do I customize WP e-Commerce =

First of all you should check out the Presentation settings which are in the Settings->Store page.

Advanced users can edit the CSS (and do just about anything). Not so advanced users can hire WP consultants developers and designers from [http://getshopped.org/resources/wp-consultants/](http://getshopped.org/resources/wp-consultants/ "http://getshopped.org/resources/wp-consultants/").

== Screenshots ==

1. Products list in WordPress backend
2. Edit Product screen
3. Single product page
4. Checkout page

== Upgrade Notice ==

= 3.8.1 =
This version addresses several urgent issues when upgrading from 3.7.x to 3.8.