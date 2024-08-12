#!/bin/bash
source scripts/prepare-drupal-lint.sh

EXIT_CODE=0
PHP_VERSION="8.3-"
STANDARDS=("PHPCompatibility" "Drupal" "DrupalPractice")
FILE_EXTENSIONS="php,module,inc,install,test,profile,theme,info,txt,md,yml"
IGNORE_PATHS="node_modules,analyze/vendor,.github,vendor"

for STANDARD in "${STANDARDS[@]}"; do
  echo "---- Checking with $STANDARD standard... ----"
  
  COMMAND="phpcs --standard=$STANDARD"
  
  if [ "$STANDARD" == "PHPCompatibility" ]; then
    COMMAND="$COMMAND --runtime-set testVersion $PHP_VERSION"
  fi
  
  $COMMAND \
    --extensions=$FILE_EXTENSIONS \
    --ignore=$IGNORE \
    -v \
    .
  
  status=$?
  if [ $status -ne 0 ]; then
    EXIT_CODE=$status
  fi
done

# Exit with failure if any of the checks failed
exit $EXIT_CODE
