=== Turbo Ecommerce ===
Contributors: Turbo Team
Tags: WooCommerce, shipping, courier, antispam, anti-spam, contact form, anti spam, comment moderation, comment spam, contact form spam, spam comments
Requires at least: 5.0
Tested up to: 6.4.2
Stable tag: 2.0.0

== Description ==

Turbo plugin helps you integrate your WooCommerce website with the Turbo shipping system.

This plugin allows you to connect your WooCommerce store to the Turbo shipping system, enabling you to manage your shipping operations efficiently. It provides seamless integration between your website and the Turbo shipping system, allowing you to sync orders, track shipments, and manage shipping details easily.

Key features:

Streamlined integration with Turbo shipping system.
Manage shipping cities and regions based on Turbo delivery areas.
Connect your WooCommerce store to the Turbo shipping system.
Sync orders and shipping details effortlessly.
Track shipments and manage shipping operations efficiently.
To get started, install and activate the Turbo plugin. Then, navigate to the Turbo settings in the WordPress admin dashboard's settings tab. Enter your Turbo account settings to establish the connection between your website and the Turbo shipping system.

1, 2, 3: You're done!

For more information and to create a Turbo account, visit https://turbo-eg.com/.

== Installation ==

Upload the Turbo plugin to the /wp-content/plugins/ directory.
Activate the plugin through the 'Plugins' menu in WordPress.
Go to the Turbo settings from the settings tab and enter your account settings.

== Changelog ==

= 2.0.0 =

- ADD - :tada: compatiblity with HPOS WooCommerce feature, now you can update your site and settings to use the new amazing feature of WooCommerce HPOS refer this link for more info https://woo.com/document/high-performance-order-storage/.
- ADD - Option to generate API token for use in 'webhook updates' to secure site APIs
- TWEAK - Display send to Turbo error message if exist for better awareness
- TWEAK - Show warning if WooCommerce plugin is disabled
- TWEAK - Show warning if Checkout page is not compatible with our plugin
- DEV - Replace Post functions with WC_Order for better compatiblity with new WooCommerce features.
- DEV - Now you can use TURBO_ECOMMERCE_SANDBOX url to do your tests.
- DEV - Constant to hold plugin version, now you can check Turbo plugin version wihle you make your integration.
- DEV - Optimization.
- DEV - Remove unnecessary code.
- FIX - The whole site where crash when disable WooCommerce plugin
- FIX - Repetitive warning about empty fields
- FIX - Minor redirection problem

= 1.1.8 =

- Add new field "second phone" in checkout and send with the order to Turbo.
- Add new option to set "Return Amount" as shipping amount.
- Fix updaing order some scenarios.
- Solve some technical warnings and alerts.

= 1.1.7 =

- Solve warning problems.
- Solve customer notes if changed.
- Add more safety requests.
- Add more validation for cities feed.

= 1.1.6 =

- Add handler for skip shipping zone errors.
- Remove the forced bug tracker stop.
- Fix error messages for send orders.

= 1.1.5 =

- Fix the amount problem.
- Fix change shipping city search.
- Fix client area
- Fix admin edit order issues
- Add error message status and cities
- Add date batch in Turbo columns
- Added the ability to send shipping details if using different addresses.

= 1.1.4 =

- Fix amount proplem.
- Fix change shipping city search.

= 1.1.3 =

- Fix Shipping zones setup.
- Fix order edite cities.
- Fix change Order sync.

= 1.1.2 =

- fix shipping zones.
- Add shipping states.
- fix shipping reat.
- Fixed an issue with duplicating functions.

= 1.0.1 =

- A message waiting to bring the cities of the provinces instead of delaying the response
- Fix select options
- Easy Search in Cities
- Fix bulk actions issues
- Can Open the shipment option


Initial release.
Please refer to the plugin's documentation for detailed instructions and usage guidelines.
