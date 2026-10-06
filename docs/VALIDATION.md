# Validation scope

Version 0.1.0 is published as a GitHub pre-release.

The `Syntax and packaging` workflow runs PHP syntax lint on PHP 7.4, 8.1, 8.3 and 8.5 and builds archives. Syntax checking does not execute WordPress hooks, SQL queries, CSV analysis or WP-CLI commands. Packaging checks an explicit file allowlist and matching version declarations.

No functional test suite, actual WordPress activation check, WooCommerce integration check, performance benchmark or security audit has been performed for this release. Runtime compatibility and behavior remain unverified. Minimum versions in the plugin header are design targets, not verified compatibility claims.

Before a stable release, separately validate permissions/nonces, rendered escaping, representative synthetic inputs, error and limit behavior, and WP-CLI output in an isolated installation. The autoload tool also needs database-state and multisite checks; the CSV tool needs quoted/multiline records, duplicate identifiers and partial-update scenarios.
