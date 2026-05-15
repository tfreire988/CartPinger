# WhatsCom for WooCommerce

WhatsApp Cloud API integration for WooCommerce. Free tier + Pro tier (planned).

## Requirements

| Dependency  | Minimum |
|-------------|---------|
| PHP         | 8.2+    |
| WordPress   | 6.5+    |
| WooCommerce | 9.0+    |
| Node.js     | 20+     |
| Composer    | 2.x     |

## Quick start

```bash
composer install
npm install
npm run build
```

Then activate the plugin in WordPress admin and run the setup wizard at **WhatsCom > Setup**.

## Development

See [docs/DEVELOPMENT.md](docs/DEVELOPMENT.md) for full environment setup instructions.

## CI

| Check      | Tool                  |
|------------|-----------------------|
| Lint       | PHPCS + WPCS          |
| Static     | PHPStan level 8       |
| Unit tests | PHPUnit + WP Mock     |
| Assets     | @wordpress/scripts    |

Run all checks locally:

```bash
composer all-checks
npm run build
```

## Architecture

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

GPL-2.0-or-later — see [LICENSE](LICENSE).
