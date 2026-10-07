# WooCommerce Product CSV Preflight

[![Syntax and packaging](https://github.com/harukyu/wc-product-csv-preflight/actions/workflows/ci.yml/badge.svg)](https://github.com/harukyu/wc-product-csv-preflight/actions/workflows/ci.yml)

Check common product CSV mistakes **before import**. WordPress admin plugin, WP-CLI command and standalone PHP CLI by **[Nakaryu GmbH](https://nakaryu.de)**. The tool never creates, imports or changes products. **Developer preview 0.1.1: Plugin Check and isolated activation/basic CLI checks passed; broader integration remains unverified.**

[Deutsche Anleitung](docs/DE.md) · [Downloads](https://github.com/harukyu/wc-product-csv-preflight/releases) · [Validation scope](docs/VALIDATION.md)

## Install and use

Build `nakaryu-product-csv-preflight-0.1.1.zip` using `python3 tools/package.py`. Published release downloads are linked above. On an isolated development installation, upload it through **Plugins → Add New → Upload Plugin**, activate and open **Tools → Product CSV Preflight**. Choose a CSV and delimiter, then click **Check CSV**.

The screen requires `manage_woocommerce`, normally granted to administrators and shop managers by WooCommerce, plus a valid nonce. WooCommerce does not need to execute for the core analyzer; without that capability the menu is unavailable. Designed for WordPress 6.0+ and PHP 7.4+; minimums are implementation targets, not verified compatibility claims. The backend sends the file to **your WordPress server**, never to Nakaryu.

Use the plugin ZIP for installation. The `-source.zip` archive also includes build tooling and workflows. Standalone usage needs only PHP 7.4+; no WordPress or WooCommerce installation is required:

```sh
php bin/preflight.php examples/products.csv
php bin/preflight.php products.csv semicolon
cat products.csv | php bin/preflight.php -
# With the plugin loaded in WordPress:
wp nakaryu csv preflight products.csv --delimiter=comma
```

The standalone CLI accepts a local filename or `-` for stdin. JSON goes to stdout. Exit codes: **0** no findings, **1** warnings, **2** error findings, **3** invalid input/runtime error. WP-CLI uses 0/1/2 for findings and its normal error exit for invalid input. Warnings are findings requiring review, not proof that the importer will fail.

## Checks

English headers are trimmed and matched case-insensitively. Supported subset:

| Header | Check |
| --- | --- |
| ID | Positive integer up to 18 digits; duplicate IDs in this file |
| SKU | Exact duplicate nonblank identifiers; missing SKU/ID warning |
| Name | Blank-value warning when the column exists |
| Type | One of simple, variable, grouped, external, variation; optional virtual/downloadable flags |
| Parent | Missing variation parent, malformed id: reference, self-reference, duplicate/incorrect/in-file parent type and order |
| Regular price / Sale price | Non-negative plain dot decimals; sale not lower warning |
| Stock | Plain numeric quantity or parent; parent/type consistency warning |
| Published | 1, 0 or -1 |

At least one of SKU, ID or Name must be present as a header. This supports partial update files: absent Type/Name/price columns are not automatically errors. A blank Name warning may be intentional for updates. Type flags without an explicit base type are outside this checker profile.

Unknown columns generate one warning and are not validated. This includes attributes, categories, images, dates, custom meta, localized headers and extension columns. If your importer uses custom mapping, adapt a **copy** to these English headers before checking. Do not treat `regular_price` as `Regular price`.

Parent references use an exact SKU or `id:` followed by an ID. A parent absent from the file produces a **warning**, since it may already exist in the store. Store products are never queried. SKU comparison is exact and case-sensitive; database collation or importer matching may differ. A parent later in the file warns about import order.

Price values allow at most 12 integer and 6 fractional digits. Currency symbols, decimal commas, thousands separators, scientific notation and negative prices are rejected by this profile. Comparison uses normalized decimal strings, not binary floats. Sale schedules and tax settings are not evaluated.

## CSV profile and limits

UTF-8, optional UTF-8 BOM; comma, semicolon or tab selected explicitly. CRLF, LF and CR record endings are supported. Quoted fields support multiline content and doubled quote escaping. Quotes must enclose the complete field; backslash escaping and whitespace after a closing quote are outside this strict profile. Malformed quoting or NUL/non-UTF-8 input is rejected.

Limits: 5 MiB input, 10,000 data records (including blanks), 256 columns, 100,000 cells. Limit violations abort with an input error and no partial report. Empty records are skipped. Header is **record 1**; finding record numbers start at 2. Embedded newlines mean these are not physical editor line numbers.

The first 200 findings are returned; totals still count later findings and `findings_truncated` indicates omitted details. `complete` means all accepted records were inspected for this tool's subset, not that every WooCommerce property was validated. Product values, identifiers, filenames and URLs are never inserted into the report.

## Synthetic examples

`examples/products.csv` contains a variable product and two variations. `examples/needs-review.csv` illustrates a duplicate SKU, invalid prices, an unknown external parent and invalid publication status. These are authored demonstration inputs, not a test suite or actual store exports.

## Privacy and limitations

The plugin reads PHP's temporary upload; it does not copy it to the media library or persist reports. PHP normally removes the temporary upload at request end. Hosting software, WordPress and other plugins may have separate logs/backups. The CLI reads the local file without changing it. Do not attach real product exports to public issues.

A clean report is not an import guarantee. The tool does not resolve existing store identities, verify attributes, download/check images, evaluate dates, emulate extension hooks, or execute WooCommerce's importer. Independently implemented from the public CSV schema; no commercial Nakaryu plugin code or customer data is included.

## Development and validation

`php -l` checks each PHP file. `python3 tools/package.py` builds deterministic plugin/source ZIPs and SHA-256 sidecars. CI targets PHP 7.4, 8.1, 8.3 and 8.5 for syntax only. Version 0.1.1 additionally passed official Plugin Check and isolated activation/basic WP-CLI checks. Full admin-upload and WooCommerce integration QA remains incomplete. See [validation scope](docs/VALIDATION.md).

## References

- [Official WooCommerce built-in product CSV schema](https://github.com/woocommerce/woocommerce/wiki/Product-CSV-Import-Schema)
- [Official importer/exporter guide](https://woocommerce.com/document/product-csv-importer-exporter/)

## License

Copyright 2026 Nakaryu GmbH. GPL-2.0-or-later; see [LICENSE](LICENSE). This is an independent project, not an official WooCommerce product.

The installable plugin ZIP excludes the standalone `bin/` CLI. Use the source repository/package for that CLI; the WP-CLI command remains included in the plugin.
