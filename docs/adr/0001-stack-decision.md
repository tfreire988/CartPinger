# ADR 0001 — Technology Stack

**Date:** 2026-05-15
**Status:** Accepted

## Context

We needed to choose the technology stack for a WordPress/WooCommerce plugin that integrates with the Meta WhatsApp Cloud API. The plugin targets WP.org distribution (free tier) and a future Pro tier sold directly.

## Decision

| Layer         | Choice                                  | Rationale                                                                                   |
|---------------|-----------------------------------------|---------------------------------------------------------------------------------------------|
| PHP minimum   | 8.2                                     | Named arguments, fibers, readonly properties. WP.org stats show 8.2 adoption is sufficient. |
| WP minimum    | 6.5                                     | Block editor APIs stabilized, pattern library mature.                                        |
| WC minimum    | 9.0                                     | Block-based Additional Checkout Fields API (stable in 9.0).                                 |
| Autoloading   | Composer PSR-4 (`WhatsCom\`)            | Industry standard; enables PHPStan, PHPUnit without manual requires.                        |
| Linting       | PHPCS + WPCS 3.x                        | Required for WP.org compliance. WPCS 3.x supports PHP 8.x.                                 |
| Static analysis | PHPStan level 8                       | Catches type errors early; level 8 is strict without being impractical.                     |
| Unit testing  | PHPUnit 9.x + WP_Mock                   | WP_Mock allows testing WP-dependent code without a real database.                           |
| JS build      | @wordpress/scripts                      | Zero-config webpack + TypeScript + React setup aligned with Gutenberg.                      |
| CI            | GitHub Actions                          | Free for public repos; native GitHub integration.                                            |
| WP.org deploy | 10up/action-wordpress-plugin-deploy     | Official recommended action for SVN deployment.                                             |

## Alternatives considered

- **Psalm instead of PHPStan** — PHPStan has better WordPress/WooCommerce stub support via `szepeviktor/phpstan-wordpress`.
- **Vanilla JS instead of TypeScript** — TypeScript improves maintainability as the admin UI grows.
- **Integration tests only** — WP_Mock unit tests are faster in CI and catch regressions without a database.

## Consequences

- PHP 8.2 minimum may exclude ~5% of potential users still on PHP 8.0/8.1. Acceptable given long-term support horizon.
- PHPStan level 8 means all code must be fully typed, increasing initial development overhead. Justified by reduced runtime bugs.
