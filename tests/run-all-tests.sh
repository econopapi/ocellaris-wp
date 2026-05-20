#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"

php "$ROOT_DIR/tests/run-smoke.php"
php "$ROOT_DIR/tests/run-smoke-structure.php"
bash "$ROOT_DIR/tests/run-lint.sh"

echo "[OK] All test runners passed"
