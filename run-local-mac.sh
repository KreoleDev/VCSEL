#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ELECTRON_BIN="$ROOT_DIR/node_modules/.bin/electron"

if [[ ! -x "$ELECTRON_BIN" ]]; then
  echo "Electron dependencies are not installed. Run npm install first." >&2
  exit 1
fi

unset ELECTRON_RUN_AS_NODE

cd "$ROOT_DIR"
exec "$ELECTRON_BIN" .
