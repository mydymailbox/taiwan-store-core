=== Taiwan Store Core ===
Contributors: taiwanstore
Tags: woocommerce, taiwan, checkout, invoice, shipping
Requires at least: 6.5
Tested up to: 6.6
Requires PHP: 8.1
Stable tag: 1.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional localization solution for WooCommerce in Taiwan. Features checkout optimization, tax ID lookup, postcode auto-fill, and a visual rule engine.

== Description ==

Taiwan Store Core is a professional extension designed specifically for the Taiwan e-commerce market. It provides a seamless localized checkout experience and a powerful business rule engine to help store owners manage complex local requirements effortlessly.

= Core Features =

* **Taiwan Checkout Optimization** — City/District cascading dropdowns and 3+2 digit postcode auto-fill.
* **Tax ID Integration** — Unified Business Number (UBN), Company Name, Mobile Barcode, Citizen Digital Certificate, and Donation Code support.
* **Smart Tax ID Lookup** — Automatically fetches company names from official APIs (GCIS) upon entering a valid 8-digit Tax ID.
* **Visual Rule Engine** — Manage Payment, Shipping, and Cart rules with a modern, visual interface. No coding required.
* **Custom Order Numbers** — Professional sequential formats (e.g., Prefix + YYYYMMDD + Sequence).
* **Checkout Countdown** — Create urgency and improve conversion rates with a customizable timer.
* **Mobile Sticky Bar** — Fixed "Buy Now" button for better mobile user experience.
* **Social Login** — One-click login with LINE, Google, and Facebook.
* **HPOS Ready** — Fully compatible with WooCommerce High-Performance Order Storage.

= Extension Modules =

Unlock even more power with our Pro extensions:

* **E-Invoice Pro** — Fully automated invoice issuance, preview, PDF download, and winning notifications.
* **Marketing Engine** — Free gifts, tiered discounts, bundle deals, and flash sale count-downs.
* **Smart Tracking** — Automated logistics status detection and LINE notifications.

== Installation ==

1. Upload the `taiwan-store-core` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure settings under WooCommerce -> Settings -> Taiwan Store.

== Frequently Asked Questions ==

= What are the system requirements? =

Requires PHP 8.1+, WordPress 6.5+, and WooCommerce 8.0+.

= Is it compatible with other checkout plugins? =

It uses the standard WooCommerce Additional Checkout Fields API, making it compatible with most modern themes and plugins. If you encounter issues, please check the logs tab.

== Changelog ==

= 1.0.4 =
* Improvement: Localized SweetAlert2 for WordPress.org compliance.
* Improvement: Upgraded rule management notifications to SweetAlert2.
* Fix: Removed redundant save buttons on log pages.
* Fix: Sanitized all user inputs and improved nonce verification.

= 1.0.3 =
* Added: Social Login module (LINE / Google / Facebook).
* Added: Checkout countdown timer.
* Added: Mobile sticky buy bar.

= 1.0.0 =
* Initial release.
