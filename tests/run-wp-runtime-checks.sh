#!/usr/bin/env bash
set -euo pipefail

if ! command -v wp >/dev/null 2>&1; then
  echo "[FAIL] wp-cli is required for runtime checks"
  exit 1
fi

if [[ -z "${WP_PATH:-}" ]]; then
  echo "[FAIL] WP_PATH is required. Example: WP_PATH='/var/www/html' bash tests/run-wp-runtime-checks.sh"
  exit 1
fi

wp --path="$WP_PATH" eval-file tests/run-wp-runtime-checks.php
