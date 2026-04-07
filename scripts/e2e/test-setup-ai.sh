#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
source "$SCRIPT_DIR/_helpers.sh"

DRUSH="${DRUSH:-drush}"

section "analyze:setup-ai"

# Test: check mode (files not installed yet).
output=$($DRUSH analyze:setup-ai --check 2>&1)
assert_has "check detects not installed" "NOT INSTALLED" "$output"

# Test: install for Claude only.
output=$($DRUSH analyze:setup-ai --host=claude 2>&1)
assert_has "install claude returns success" "success: true" "$output"
assert_has "install claude mentions SKILL.md" "SKILL.md" "$output"

# Test: install for all.
output=$($DRUSH analyze:setup-ai 2>&1)
assert_has "install all returns success" "success: true" "$output"

# Test: check mode after install.
output=$($DRUSH analyze:setup-ai --check 2>&1)
assert_has "check shows up to date" "up to date" "$output"

# Test: invalid host.
output=$($DRUSH analyze:setup-ai --host=invalid 2>&1)
assert_has "invalid host returns error" "Invalid" "$output"

# Test: host filtering.
output=$($DRUSH analyze:setup-ai --host=agents 2>&1)
assert_has "agents install returns success" "success: true" "$output"

print_summary
