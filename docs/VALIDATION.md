# Validation scope

Version 0.1.0 is published as a GitHub pre-release.

The `Syntax and packaging` workflow runs PHP syntax lint on PHP 7.4, 8.1, 8.3 and 8.5 and builds archives. Syntax checking does not execute WordPress hooks, SQL queries, CSV analysis or WP-CLI commands. Packaging checks an explicit file allowlist and matching version declarations.

No functional test suite, actual WordPress activation check, WooCommerce integration check, performance benchmark or security audit has been performed for this release. Runtime compatibility and behavior remain unverified. Minimum versions in the plugin header are design targets, not verified compatibility claims.

Before a stable release, separately validate permissions/nonces, rendered escaping, representative synthetic inputs, error and limit behavior, and WP-CLI output in an isolated installation. The autoload tool also needs database-state and multisite checks; the CSV tool needs quoted/multiline records, duplicate identifiers and partial-update scenarios.

## Version 0.1.1 — 2026-10-07

- Official Plugin Check 2.1.0 run in new-submission mode using the full default check set with runtime checks and low-severity findings included: no outstanding findings for the plugin ZIP.
- Activation succeeded in the disposable WordPress 7.1.3 / PHP 8.5.5 installation using SQLite Database Integration 3.0.2 and synthetic data only.
- CSV Preflight WP-CLI checked the synthetic products.csv and needs-review.csv fixtures; the first returned no findings, the second returned error/warning findings.
- These checks do not establish a full compatibility matrix, complete admin upload/permission behavior, real WooCommerce importer parity, multisite behavior or production performance. Further functional QA remains necessary.
- Directory submission/review has not yet occurred.
- One narrow PHPCS annotation documents validation of PHP upload metadata via exact types, UPLOAD_ERR_OK and is_uploaded_file(); the uploaded filename is not used or stored. The source-only standalone CLI now rejects web requests and is excluded from the plugin ZIP.
