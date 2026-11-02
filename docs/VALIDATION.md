# Validation scope

Version 0.1.0 was released after syntax and packaging checks only. Later validation is recorded below. Minimum versions in the plugin header are design targets, not a verified compatibility matrix.

The current CI workflow runs PHP syntax, synthetic analyzer behavior and allowlisted package assembly on PHP 7.4, 8.1, 8.3 and 8.5. The release workflow repeats the analyzer checks before tagging and attaching archives.

## Version 0.1.2 — 2026-11-02

- Fixed false errors for documented `Published` values `2`, `true` and `false`; the boolean words are accepted without regard to letter case, matching the built-in importer parser. Regression cases cover all six supported forms and reject unsupported examples.
- Local PHP 8.4.26 run: 24 synthetic assertions passed; all project PHP files passed syntax lint; deterministic plugin and source ZIPs built from explicit manifests.
- The installable ZIP passed official Plugin Check 2.1.0 in `update` mode with the default full check set, runtime checks (via `--require=wp-content/plugins/plugin-check/cli.php`), and low-severity errors and warnings included: no findings. The disposable environment used WordPress 7.1.4, PHP 8.4.26 and SQLite Database Integration 3.1.2.
- The ZIP activated there. WP-CLI and the standalone CLI each processed a synthetic three-row CSV containing `2`, `TRUE` and `false`; both returned version 0.1.2 with no findings.
- This does not prove parity with the real WooCommerce importer, MySQL/MariaDB behavior, production performance, or a broad WordPress/PHP compatibility matrix. Browser upload behavior was not repeated for 0.1.2 because the changed code is in the shared analyzer.

## Version 0.1.1 — 2026-10-07

- Official Plugin Check 2.1.0 run in new-submission mode using the full default check set with runtime checks and low-severity findings included: no outstanding findings for the plugin ZIP.
- Activation succeeded in the disposable WordPress 7.1.3 / PHP 8.5.5 installation using SQLite Database Integration 3.0.2 and synthetic data only.
- CSV Preflight WP-CLI checked the synthetic products.csv and needs-review.csv fixtures; the first returned no findings, the second returned error/warning findings.
- These checks did not establish a full compatibility matrix, WooCommerce importer parity, multisite behavior or production performance. Further functional QA remains necessary.
- A later disposable WordPress 7.1.3 / PHP 8.4 check of the 0.1.1 ZIP covered the admin upload flow with synthetic good and bad CSV files plus absent-file, invalid-nonce and insufficient-capability denial. No real store data was used.
- The WordPress.org submission was approved on 2026-10-15 but its assigned SVN remained empty on 2026-11-02; approval is not public directory publication.
- One narrow PHPCS annotation documents validation of PHP upload metadata via exact types, UPLOAD_ERR_OK and is_uploaded_file(); the uploaded filename is not used or stored. The source-only standalone CLI now rejects web requests and is excluded from the plugin ZIP.

## Functional regression checks — 2026-10-19

- Added a dependency-free analyzer suite with synthetic CSV input. It checks quoted multiline fields, delimiters, partial updates, duplicate identifiers, parent references, exact decimal prices, invalid input, privacy of findings, size limits and bounded reports.
- Local PHP 8.4.26 run: 22 assertions passed; all project PHP files passed syntax lint. [CI run 39260104920](https://github.com/harukyu/wc-product-csv-preflight/actions/runs/39260104920) passed the same suite, syntax and packaging on PHP 7.4, 8.1, 8.3 and 8.5.
- This checks the standalone analyzer, not the browser upload flow or parity with a live WooCommerce importer. The earlier Plugin Check and isolated WordPress checks above were run before this change; this change does not claim they were rerun.
