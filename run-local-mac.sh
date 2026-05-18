#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
API_LOG="$ROOT_DIR/.local-api.log"
ELECTRON_BIN="$ROOT_DIR/node_modules/.bin/electron"

if [[ ! -x "$ELECTRON_BIN" ]]; then
  echo "Electron dependencies are not installed. Run npm install first." >&2
  exit 1
fi

"$ROOT_DIR/start-local-api.sh" >"$API_LOG" 2>&1 &
API_PID=$!

cleanup() {
  kill "$API_PID" >/dev/null 2>&1 || true
}
trap cleanup EXIT

sleep 1

unset ELECTRON_RUN_AS_NODE

cd "$ROOT_DIR"
exec "$ELECTRON_BIN" .
