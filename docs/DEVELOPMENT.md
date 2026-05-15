# Development Guide

## Prerequisites

- PHP 8.2+
- Composer 2.x
- Node.js 20+ and npm
- A local WordPress environment (see options below)

## Setup

```bash
# 1. Clone
git clone https://github.com/telmofreire/whatscom.git
cd whatscom

# 2. PHP dependencies
composer install

# 3. JS dependencies
npm install

# 4. Build assets
npm run build
# Or watch mode during development:
npm run start
```

## Local WordPress environment options

### Option A — wp-env (recommended, zero config)

```bash
npm install -g @wordpress/env
wp-env start
```

Then activate the plugin:

```bash
wp-env run cli wp plugin activate whatscom
```

### Option B — LocalWP

1. Create a new site in LocalWP.
2. Symlink or copy the plugin folder into `wp-content/plugins/whatscom/`.
3. Activate via WP admin.

### Option C — Lando

```bash
lando start
lando wp plugin activate whatscom
```

## Running checks

```bash
# Lint (PHPCS + WPCS)
composer lint

# Auto-fix lint issues
composer lint:fix

# Static analysis (PHPStan level 8)
composer stan

# Unit tests (WP Mock)
composer test

# All checks in sequence
composer all-checks

# JS linting
npm run lint:js
npm run lint:css
```

## Running integration tests (requires a real WordPress DB)

```bash
# Install WordPress test suite (one-time)
bash bin/install-wp-tests.sh wordpress_test root '' 127.0.0.1 latest

# Run only integration suite
vendor/bin/phpunit --testsuite Integration
```

## Generating the .pot file

```bash
wp i18n make-pot . languages/whatscom.pot --domain=whatscom --exclude=vendor,node_modules,tests,assets/build
```

## Building a release .zip

```bash
bash bin/build-plugin-zip.sh
# Output: build-output/whatscom.zip
```
