# Contributing

Report a reproducible issue with synthetic input, expected behavior, PHP version and relevant WordPress/WooCommerce versions. Never attach real option values, customer data, private product files or credentials.

Keep this project read-only, dependency-free at runtime, bounded, and independently authored. Follow the existing namespaces and escape all admin output. Discuss new behavior in an issue before implementing it.

Development: PHP 7.4+ and Python 3.10+ for packaging. `php -l` checks syntax; `python3 tools/package.py` builds archives. Existing CI checks syntax and package assembly only. No functional test suite exists in version 0.1.0. Functional or integration validation should be separately scoped and documented before a stable release.

Contributions are licensed GPL-2.0-or-later.
