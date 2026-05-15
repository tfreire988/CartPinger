## Summary

<!-- Describe the change and motivation -->

## Type of change

- [ ] Bug fix
- [ ] New feature (free tier)
- [ ] New feature (Pro tier)
- [ ] Refactor
- [ ] Documentation
- [ ] CI/tooling

## Checklist

- [ ] `composer lint` passes (PHPCS / WPCS)
- [ ] `composer stan` passes (PHPStan level 8)
- [ ] `composer test` passes (PHPUnit)
- [ ] `npm run build` succeeds
- [ ] All strings use `__()` / `_e()` with text domain `whatscom`
- [ ] No hardcoded credentials or tokens
- [ ] DB queries use `$wpdb->prepare()`
- [ ] Tested on WordPress 6.5+ with WooCommerce 9.0+
- [ ] CHANGELOG.md updated
