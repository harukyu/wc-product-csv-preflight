# Contributing

Report a reproducible issue with synthetic input, expected behavior, PHP version and relevant WordPress/WooCommerce versions. Never attach real option values, customer data, private product files or credentials.

Keep this project read-only, dependency-free at runtime, bounded, and independently authored. Follow the existing namespaces and escape all admin output. Discuss new behavior in an issue before implementing it.

Development: PHP 7.4+ and Python 3.10+ for packaging. `php -l` checks syntax; `php tests/run.php` checks synthetic analyzer behavior; `python3 tools/package.py` builds archives. CI runs these checks across a PHP matrix. WordPress admin and WooCommerce importer integration require separate validation before a stable release.

Contributions are licensed GPL-2.0-or-later.
