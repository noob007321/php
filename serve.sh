#!/usr/bin/env bash
#
# serve.sh — start a local PHP server for this repository.
#
# Usage:
#   ./serve.sh              # the simple app   -> http://localhost:8000
#   ./serve.sh login-app    # the full app     -> http://localhost:8000
#   ./serve.sh simple 8080  # any port
#
# Why this script exists: this sandbox has no native `php` binary, so we use
# PHP compiled to WebAssembly (@php-wasm/cli from npm). The toolchain is
# installed in a temp folder, which is NOT saved between sandbox restarts —
# this script reinstalls it automatically when it is missing.
#
# On a normal machine with PHP installed, it simply uses your real `php`.
#
set -euo pipefail

APP="${1:-simple}"
PORT="${2:-8000}"

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TOOLS="${TMPDIR:-/tmp}/phpsrv"

case "$APP" in
  simple)
    DOCROOT="$ROOT/simple"
    ROUTER=""
    ;;
  login-app)
    DOCROOT="$ROOT/login-app"
    ROUTER="$ROOT/login-app/router.php"
    ;;
  *)
    echo "Unknown app: $APP  (use 'simple' or 'login-app')" >&2
    exit 1
    ;;
esac

if [ ! -d "$DOCROOT" ]; then
  echo "Missing folder: $DOCROOT" >&2
  exit 1
fi

# --- 1. Native PHP, if this machine has it -------------------------------
if command -v php >/dev/null 2>&1; then
  echo "Using native PHP $(php -r 'echo PHP_VERSION;') on port $PORT" >&2
  if [ -n "$ROUTER" ]; then
    exec php -S "0.0.0.0:$PORT" -t "$DOCROOT" "$ROUTER"
  fi
  exec php -S "0.0.0.0:$PORT" -t "$DOCROOT"
fi

# --- 2. Otherwise: PHP compiled to WebAssembly --------------------------
if [ ! -x "$TOOLS/node_modules/.bin/php-wasm-cli" ]; then
  echo "No PHP found; installing the WebAssembly build in $TOOLS ..." >&2
  mkdir -p "$TOOLS"
  (
    cd "$TOOLS"
    npm init -y >/dev/null 2>&1
    npm install --silent @php-wasm/cli >&2
  )
fi

echo "Serving $DOCROOT on http://0.0.0.0:$PORT (PHP wasm)" >&2

if [ -n "$ROUTER" ]; then
  exec "$TOOLS/node_modules/.bin/php-wasm-cli" -S "0.0.0.0:$PORT" -t "$DOCROOT" "$ROUTER"
fi

exec "$TOOLS/node_modules/.bin/php-wasm-cli" -S "0.0.0.0:$PORT" -t "$DOCROOT"
