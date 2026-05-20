#!/usr/bin/env bash
set -euo pipefail

if [[ -z "${SITE_URL:-}" ]]; then
  echo "[FAIL] SITE_URL is required. Example: SITE_URL='https://ocellaris.local' bash tests/run-http-smoke.sh"
  exit 1
fi

# Override with: SMOKE_PATHS='/ /shop/ /checkout/ /my-account/'
SMOKE_PATHS="${SMOKE_PATHS:-/ /shop/ /checkout/ /my-account/}"

echo "[INFO] Running HTTP smoke against: $SITE_URL"

failures=0
for path in $SMOKE_PATHS; do
  target="${SITE_URL%/}${path}"
  status="$(curl -sS -L -o /tmp/ocellaris-http-smoke-body.txt -w '%{http_code}' "$target" || true)"

  if [[ "$status" != "200" ]]; then
    echo "[FAIL] $target -> HTTP $status"
    failures=1
    continue
  fi

  if ! grep -qi "wp-content/themes/ocellaris-astra" /tmp/ocellaris-http-smoke-body.txt; then
    echo "[WARN] $target responded 200 but theme fingerprint was not found"
  else
    echo "[OK] $target -> HTTP 200"
  fi
done

if [[ $failures -ne 0 ]]; then
  exit 1
fi

echo "[OK] HTTP smoke checks passed"
