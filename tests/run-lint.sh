#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"

mapfile -t php_files < <(find "$ROOT_DIR" \
  -type f \
  -name "*.php" \
  -not -path "*/.git/*" \
  -not -path "*/node_modules/*" \
  | sort)

if [[ ${#php_files[@]} -eq 0 ]]; then
  echo "[FAIL] No PHP files found to lint"
  exit 1
fi

failed=0
for file in "${php_files[@]}"; do
  if ! php -l "$file" >/dev/null; then
    echo "[FAIL] Syntax error in: $file"
    failed=1
  fi
done

if [[ $failed -ne 0 ]]; then
  exit 1
fi

echo "[OK] PHP lint passed (${#php_files[@]} files)"
