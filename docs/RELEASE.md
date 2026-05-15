# Release Process

## Checklist before tagging

- [ ] All CI checks pass on `develop`
- [ ] `composer all-checks` passes locally
- [ ] `npm run build` succeeds
- [ ] Version bumped in:
  - `whatscom.php` (`Version:` header and `WHATSCOM_VERSION` constant)
  - `composer.json` (`"version"` field — optional but good practice)
  - `package.json` (`"version"`)
  - `readme.txt` (`Stable tag:`)
  - `CHANGELOG.md` (new section with date)
- [ ] `readme.txt` `Tested up to:` reflects latest tested WP version
- [ ] Screenshots up to date in `assets/` (for WP.org)

## Tagging and releasing

```bash
# Merge develop → main
git checkout main
git merge develop

# Tag
git tag v0.2.0
git push origin main --tags
```

This triggers two GitHub Actions workflows automatically:

1. **release.yml** — builds `build-output/whatscom.zip` and publishes a GitHub Release.
2. **deploy-to-wordpress-org.yml** — deploys to WP.org SVN using `10up/action-wordpress-plugin-deploy`.

## WordPress.org SVN secrets

Configure these in GitHub repository Settings → Secrets:

| Secret         | Value                              |
|----------------|------------------------------------|
| `SVN_USERNAME` | Your wordpress.org username        |
| `SVN_PASSWORD` | Your wordpress.org password        |

## WP.org assets (banners, icons, screenshots)

Place them in `.wordpress-org/` (not tracked by the deploy action by default, check 10up action docs):

```
.wordpress-org/
├── banner-772x250.png
├── banner-1544x500.png
├── icon-128x128.png
├── icon-256x256.png
└── screenshot-1.png
```

## First submission to WP.org

1. Verify slug `whatscom` is available at wordpress.org/plugins.
2. Go to wordpress.org/plugins/developers/add/.
3. Upload `build-output/whatscom.zip`.
4. Wait 2–4 weeks for review.
5. Once approved, set up SVN secrets and future releases deploy automatically.
