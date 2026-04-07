#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
source "$SCRIPT_DIR/_helpers.sh"

DRUSH="${DRUSH:-drush}"

section "analyze:batch --list"

output=$($DRUSH analyze:batch --list 2>&1)
assert_success "batch list runs" $DRUSH analyze:batch --list

section "analyze:batch (no entities)"

output=$($DRUSH analyze:batch 2>&1 || true)
assert_not_empty "batch returns output" "$output"

print_summary
