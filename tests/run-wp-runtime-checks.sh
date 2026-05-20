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

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CHECK_FILE="$SCRIPT_DIR/run-wp-runtime-checks.php"

if [[ ! -f "$CHECK_FILE" ]]; then
  echo "[FAIL] Runtime checks file not found: $CHECK_FILE"
  exit 1
fi

run_checks() {
  local php_args="$1"
  shift
  if [[ -n "$php_args" ]]; then
    WP_CLI_PHP_ARGS="$php_args" wp --path="$WP_PATH" "$@" eval-file "$CHECK_FILE"
  else
    wp --path="$WP_PATH" "$@" eval-file "$CHECK_FILE"
  fi
}

run_checks_with_db_host_override() {
  local db_host_value="$1"
  local tmp_require_file
  tmp_require_file="$(mktemp)"

  cat > "$tmp_require_file" <<EOF
<?php
if (!defined('DB_HOST')) {
    define('DB_HOST', '${db_host_value}');
}
EOF

  if output="$(run_checks "" "--require=$tmp_require_file" 2>&1)"; then
    rm -f "$tmp_require_file"
    return 0
  fi

  rm -f "$tmp_require_file"
  return 1
}

add_socket_candidate() {
  local socket="$1"
  [[ -z "$socket" ]] && return 0
  [[ ! -S "$socket" ]] && return 0

  for existing in "${socket_candidates[@]}"; do
    if [[ "$existing" == "$socket" ]]; then
      return 0
    fi
  done

  socket_candidates+=("$socket")
}

echo "[INFO] Running WP runtime checks with default WP-CLI settings..."
if output="$(run_checks "" 2>&1)"; then
  echo "$output"
  exit 0
fi

initial_output="$output"

socket_candidates=()
add_socket_candidate "${WP_DB_SOCKET:-}"

for socket in \
  /var/run/mysqld/mysqld.sock \
  /run/mysqld/mysqld.sock \
  /tmp/mysql.sock \
  /tmp/mysqld.sock \
  /tmp/mysql/mysql.sock; do
  add_socket_candidate "$socket"
done

while IFS= read -r socket_path; do
  add_socket_candidate "$socket_path"
done < <(find "$WP_PATH" "$(dirname "$WP_PATH")" "$HOME/.config/Local/run" /run /var/run /tmp -maxdepth 8 -type s \( -name "mysqld.sock" -o -name "mysql.sock" \) 2>/dev/null | head -n 60)

for socket in "${socket_candidates[@]}"; do
  echo "[INFO] Retrying WP runtime checks with MySQL socket: $socket"
  if output="$(run_checks "-d mysqli.default_socket=$socket" 2>&1)"; then
    echo "$output"
    echo "[OK] Runtime checks passed using MySQL socket fallback"
    exit 0
  fi
done

db_host="${WP_DB_HOST:-127.0.0.1}"
db_port="${WP_DB_PORT:-3306}"
db_host_candidates=()

if [[ -n "$db_host" ]]; then
  if [[ -n "${WP_DB_PORT:-}" && "$db_host" != *:* && "$db_host" != */* ]]; then
    db_host_candidates+=("${db_host}:${db_port}")
  else
    db_host_candidates+=("$db_host")
  fi
fi

for socket in "${socket_candidates[@]}"; do
  db_host_candidates+=("localhost:${socket}")
done

for candidate in "${db_host_candidates[@]}"; do
  echo "[INFO] Retrying WP runtime checks with DB_HOST override: ${candidate}"
  if run_checks_with_db_host_override "$candidate"; then
    echo "$output"
    echo "[OK] Runtime checks passed using DB_HOST override fallback"
    exit 0
  fi
done

echo "[FAIL] Unable to run WP runtime checks."
echo "[INFO] Initial WP-CLI error output:"
echo "$initial_output"
echo ""
echo "[INFO] Last retry output:"
echo "$output"
echo ""
echo "[INFO] Try one of these:"
echo " - Export explicit socket: WP_DB_SOCKET='/absolute/path/to/mysql.sock'"
echo " - Export explicit DB host: WP_DB_HOST='localhost:/absolute/path/to/mysql.sock'"
echo " - Export explicit TCP host/port: WP_DB_HOST='127.0.0.1' WP_DB_PORT='3306'"
echo " - Verify DB is running and reachable from terminal user"
exit 1
