# Changelog

All notable changes to WhatsCom will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] — 2026-05-15

### Added
- Plugin skeleton with PSR-4 autoloading via Composer
- Admin menu (Dashboard, Setup, Settings pages)
- 5-step onboarding wizard scaffold for Meta Business verification
- Database schema: `whatscom_settings`, `whatscom_messages_log`, `whatscom_abandoned_carts`
- HPOS (High-Performance Order Storage) compatibility declaration
- PHPCS + WPCS linting configuration
- PHPStan level 8 static analysis configuration
- PHPUnit test suite with WP Mock
- GitHub Actions CI (lint, PHPStan, PHPUnit, asset build)
- GitHub Actions release workflow (build .zip + GitHub Release)
- GitHub Actions WP.org SVN deploy workflow
- GPL-2.0-or-later license

[Unreleased]: https://github.com/telmofreire/whatscom/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/telmofreire/whatscom/releases/tag/v0.1.0
