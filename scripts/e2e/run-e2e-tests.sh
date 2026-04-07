#!/usr/bin/env bash
#
# E2E test runner for Analyze module Drush commands.
# Creates a fresh Drupal install, enables the module, and runs tests.
#
set -uo pipefail

MODULE_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
FILTER="${1:-}"

echo "=== Analyze E2E Test Runner ==="
echo "Module: $MODULE_DIR"

# Install GD if not available (needed by Drupal).
GD_ENABLED=$(php -i 2>/dev/null | grep 'GD Support' | awk '{ print $4 }' || echo '')
if [ "$GD_ENABLED" != 'enabled' ]; then
  echo "Installing GD extension..."
  apk update > /dev/null 2>&1
  apk add libpng libpng-dev libjpeg-turbo-dev \
    libwebp-dev zlib-dev libxpm-dev gd > /dev/null 2>&1
  docker-php-ext-install gd > /dev/null 2>&1 || true
fi

set -e

# Create fresh Drupal install in a clean temp directory.
SITE_DIR=$(mktemp -d)
trap "rm -rf $SITE_DIR" EXIT

export HOME="$SITE_DIR"
export COMPOSER_HOME="$SITE_DIR/.composer"

echo "Creating Drupal project..."
cd "$SITE_DIR"
composer create-project drupal/recommended-project:11.x-dev site \
  --no-interaction
cd "$SITE_DIR/site"

# Symlink module.
mkdir -p web/modules/contrib
ln -s "$MODULE_DIR" web/modules/contrib/analyze

# Install Drush.
composer require drush/drush --quiet --no-interaction

# Install Drupal with SQLite.
./vendor/bin/drush site:install standard \
  --db-url=sqlite://sites/default/files/.ht.sqlite \
  --site-name="Analyze E2E Tests" \
  --site-mail="test@example.com" \
  --yes --quiet

DRUSH="$SITE_DIR/site/vendor/bin/drush"

# Enable module and rebuild.
$DRUSH en analyze --yes --quiet
$DRUSH cr --quiet

echo ""
echo "Site installed. Running tests..."

# Run test files.
TOTAL_PASS=0
TOTAL_FAIL=0
TEST_DIR="$MODULE_DIR/scripts/e2e"

for test_file in "$TEST_DIR"/test-*.sh; do
  test_name=$(basename "$test_file" .sh)

  # Apply filter if specified.
  if [ -n "$FILTER" ] && [[ "$test_name" != *"$FILTER"* ]]; then
    continue
  fi

  echo ""
  echo ">>> Running $test_name"
  export DRUSH
  if bash "$test_file"; then
    TOTAL_PASS=$((TOTAL_PASS + 1))
  else
    TOTAL_FAIL=$((TOTAL_FAIL + 1))
  fi
done

echo ""
echo "================================"
echo "Test suites: $TOTAL_PASS passed, $TOTAL_FAIL failed"
echo "================================"

if [ "$TOTAL_FAIL" -gt 0 ]; then
  exit 1
fi
