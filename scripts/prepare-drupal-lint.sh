#!/bin/bash

if [ -z "$TARGET_DRUPAL_CORE_VERSION" ]; then
  # default to target Drupal 8, you can override this by setting the secrets value on your github repo
  TARGET_DRUPAL_CORE_VERSION=10
fi

echo "php --version"
php --version
echo "composer --version"
composer --version

echo "\$COMPOSER_HOME: $COMPOSER_HOME"
echo "TARGET_DRUPAL_CORE_VERSION: $TARGET_DRUPAL_CORE_VERSION"

composer global require drupal/coder
composer global require phpcompatibility/php-compatibility

export PATH="$PATH:$COMPOSER_HOME/vendor/bin"

composer global require dealerdirect/phpcodesniffer-composer-installer
composer global config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true

phpcs --config-set installed_paths $COMPOSER_HOME/vendor/drupal/coder/coder_sniffer,$COMPOSER_HOME/vendor/phpcompatibility/php-compatibility

composer global show -P
phpcs -i

phpcs --config-set colors 1
# see: https://github.com/squizlabs/PHP_CodeSniffer/issues/262
# phpcs --config-set ignore_warnings_on_exit 1
# phpcs --config-set ignore_errors_on_exit 1
phpcs --config-set drupal_core_version $TARGET_DRUPAL_CORE_VERSION

phpcs --config-show
