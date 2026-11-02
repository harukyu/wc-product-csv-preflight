=== Nakaryu Product CSV Preflight ===
Contributors: nakaryu
Tags: woocommerce, csv, products, import, wp-cli
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Check common WooCommerce product CSV mistakes without importing products.

== Description ==
Developer preview. Tools > Product CSV Preflight inspects selected English-header columns. Checks duplicate SKU/ID, plain decimal prices, Parent references, publication status and record widths. No product writes, store lookups or remote requests. Synthetic analyzer tests and isolated checks have passed; WooCommerce importer parity remains unverified.

== Installation ==
Upload the plugin ZIP from GitHub Releases on a development installation. Activate and open Tools > Product CSV Preflight. Requires manage_woocommerce.

== Frequently Asked Questions ==
= Does this import products? =
No. It reports findings only. Existing store products, images and attributes are not checked.

= Where does the uploaded CSV go? =
To your WordPress server's temporary PHP upload. The plugin does not persist it or send it to Nakaryu.

== Changelog ==
= 0.1.2 =
* Accept documented Published values 2, true and false without false error findings.

= 0.1.1 =
* Directory package naming and official Plugin Check preparation.

= 0.1.0 =
Independent initial developer preview. Syntax and packaging checks only.
