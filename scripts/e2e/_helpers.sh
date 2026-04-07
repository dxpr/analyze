#!/usr/bin/env bash
# E2E test helpers for Analyze module Drush commands.

PASS=0
FAIL=0
TOTAL=0

section() {
  echo ""
  echo "=== $1 ==="
}

assert_success() {
  local desc="$1"
  shift
  TOTAL=$((TOTAL + 1))
  if "$@" > /dev/null 2>&1; then
    PASS=$((PASS + 1))
    echo "  PASS: $desc"
  else
    FAIL=$((FAIL + 1))
    echo "  FAIL: $desc (command exited non-zero)"
  fi
}

assert_fail() {
  local desc="$1"
  shift
  TOTAL=$((TOTAL + 1))
  if "$@" > /dev/null 2>&1; then
    FAIL=$((FAIL + 1))
    echo "  FAIL: $desc (expected failure but got success)"
  else
    PASS=$((PASS + 1))
    echo "  PASS: $desc"
  fi
}

assert_has() {
  local desc="$1"
  local needle="$2"
  local haystack="$3"
  TOTAL=$((TOTAL + 1))
  if echo "$haystack" | grep -qi "$needle"; then
    PASS=$((PASS + 1))
    echo "  PASS: $desc"
  else
    FAIL=$((FAIL + 1))
    echo "  FAIL: $desc (expected '$needle' not found)"
  fi
}

assert_not_empty() {
  local desc="$1"
  local value="$2"
  TOTAL=$((TOTAL + 1))
  if [ -n "$value" ]; then
    PASS=$((PASS + 1))
    echo "  PASS: $desc"
  else
    FAIL=$((FAIL + 1))
    echo "  FAIL: $desc (value is empty)"
  fi
}

print_summary() {
  echo ""
  echo "================================"
  echo "Results: $PASS passed, $FAIL failed, $TOTAL total"
  echo "================================"
  if [ "$FAIL" -gt 0 ]; then
    exit 1
  fi
}
