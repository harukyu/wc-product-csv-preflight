# WooCommerce importer parity check

The `importer-parity` CI job starts a disposable WordPress 7.1.4 installation with MySQL 8 and WooCommerce 11.2.2. It runs `tests/woocommerce-importer.php` through WP-CLI. The script checks six synthetic `Published` forms in the read-only analyzer, imports those rows with WooCommerce's real CSV importer, and compares the stored product statuses.

To repeat it locally, install those versions on an **isolated test site**, activate WooCommerce, and run:

```sh
wp eval-file tests/woocommerce-importer.php --path=/path/to/test-wordpress
```

The script creates six products in the disposable site and removes its CSV from that site's uploads directory. It must not be run against a production or customer store. It uses no store exports or external requests. It does not cover the browser import wizard, other CSV rules, older WooCommerce versions or MySQL versions other than the one pinned in CI. The manual SQLite check of the same six values is recorded in [validation scope](../docs/VALIDATION.md).
