#!/usr/bin/env bash
# Build a distributable .zip of the plugin (without dev dependencies).
# Output: build-output/whatscom.zip
#
# Usage: bash bin/build-plugin-zip.sh [version]
# If version is omitted it is read from the plugin header.

set -e

PLUGIN_SLUG="whatscom"
BUILD_DIR="build-output"
DIST_DIR="${BUILD_DIR}/${PLUGIN_SLUG}"

# Determine version.
VERSION=${1:-$(grep -m1 "Version:" whatscom.php | awk '{print $3}')}
ZIP_NAME="${BUILD_DIR}/${PLUGIN_SLUG}.zip"

echo "Building ${PLUGIN_SLUG} v${VERSION}..."

# Clean previous build.
rm -rf "${BUILD_DIR}"
mkdir -p "${DIST_DIR}"

# Copy distributable files only (no tests, no node_modules, no dev tools).
rsync -av --exclude-from=- . "${DIST_DIR}" <<'EXCLUDES'
.git/
.github/
node_modules/
vendor/
tests/
docs/
bin/
assets/src/
.editorconfig
.gitignore
phpcs.xml.dist
phpstan.neon.dist
phpunit.xml.dist
composer.lock
package-lock.json
package.json
*.sh
*.md
CHANGELOG.md
CONTRIBUTING.md
SECURITY.md
README.md
build-output/
EXCLUDES

# Install Composer production dependencies inside the dist copy.
composer install \
    --working-dir="${DIST_DIR}" \
    --no-dev \
    --prefer-dist \
    --no-progress \
    --no-interaction \
    --optimize-autoloader

# Zip it up.
cd "${BUILD_DIR}"
zip -r "../${ZIP_NAME}" "${PLUGIN_SLUG}/" --exclude "*/.DS_Store"
cd ..

echo "Done! -> ${ZIP_NAME}"
