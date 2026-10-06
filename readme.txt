=== Nakaryu Product CSV Preflight ===
Contributors: harukyu
Tags: woocommerce, csv, products, import, wp-cli
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Check common WooCommerce product CSV mistakes without importing products.

== Description ==
Developer preview. Tools > Product CSV Preflight inspects selected English-header columns. Checks duplicate SKU/ID, plain decimal prices, Parent references and record widths. No product writes, store lookups or remote requests. No functional or integration validation yet.

== Installation ==
Upload the plugin ZIP from GitHub Releases on a development installation. Activate and open Tools > Product CSV Preflight. Requires manage_woocommerce.

== Frequently Asked Questions ==
= Does this import products? =
No. It reports findings only. Existing store products, images and attributes are not checked.

= Where does the uploaded CSV go? =
To your WordPress server's temporary PHP upload. The plugin does not persist it or send it to Nakaryu.

== Changelog ==
= 0.1.0 =
Independent initial developer preview. Syntax and packaging checks only.
