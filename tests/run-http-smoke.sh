#!/usr/bin/env bash
set -euo pipefail

if [[ -z "${SITE_URL:-}" ]]; then
  echo "[FAIL] SITE_URL is required. Example: SITE_URL='https://ocellaris.local' bash tests/run-http-smoke.sh"
  exit 1
fi

# Local environments often use self-signed certificates.
SMOKE_ALLOW_INSECURE="${SMOKE_ALLOW_INSECURE:-1}"
curl_flags=(-sS -L)
if [[ "$SMOKE_ALLOW_INSECURE" == "1" ]]; then
  curl_flags+=(-k)
fi

# Override with: SMOKE_PATHS='/ /shop/ /checkout/ /my-account/'
SMOKE_PATHS="${SMOKE_PATHS:-/ /shop/ /checkout/}"
SMOKE_ACCOUNT_PATHS="${SMOKE_ACCOUNT_PATHS:-/my-account/ /mi-cuenta/}"

request_status() {
  local target="$1"
  curl "${curl_flags[@]}" -o /tmp/ocellaris-http-smoke-body.txt -w '%{http_code}' "$target" || true
}

echo "[INFO] Running HTTP smoke against: $SITE_URL"

failures=0
for path in $SMOKE_PATHS; do
  target="${SITE_URL%/}${path}"
  status="$(request_status "$target")"

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

account_ok=0
for account_path in $SMOKE_ACCOUNT_PATHS; do
  target="${SITE_URL%/}${account_path}"
  status="$(request_status "$target")"

  if [[ "$status" == "200" ]]; then
    echo "[OK] $target -> HTTP 200"
    account_ok=1
    break
  fi
done

if [[ $account_ok -eq 0 ]]; then
  echo "[FAIL] Account route candidates failed: $SMOKE_ACCOUNT_PATHS"
  failures=1
fi

if [[ $failures -ne 0 ]]; then
  exit 1
fi

echo "[OK] HTTP smoke checks passed"
