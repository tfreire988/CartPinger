# Contributing to WhatsCom

Thank you for your interest in contributing!

## Development setup

See [docs/DEVELOPMENT.md](docs/DEVELOPMENT.md).

## Workflow

1. Fork the repository.
2. Create a branch: `git checkout -b feature/your-feature`.
3. Make your changes.
4. Run all checks: `composer all-checks && npm run build`.
5. Commit with a descriptive message.
6. Open a Pull Request against `develop`.

## Coding standards

- PHP: [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) enforced via PHPCS.
- All user-facing strings must be wrapped with `__()` or `_e()` using text domain `whatscom`.
- No hardcoded credentials. Secrets go in `wp_options` or the custom settings table.
- All DB queries must use `$wpdb->prepare()`.
- PHPStan level 8 must pass.
- PHPUnit tests required for new business logic.

## Commit messages

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add abandoned cart WhatsApp notification
fix: correct phone number sanitization for Italian numbers
docs: update onboarding wizard step descriptions
```

## Branches

| Branch    | Purpose                          |
|-----------|----------------------------------|
| `main`    | Stable, tagged releases only     |
| `develop` | Integration branch for PRs       |

## License

By contributing, you agree that your contributions will be licensed under GPL-2.0-or-later.
