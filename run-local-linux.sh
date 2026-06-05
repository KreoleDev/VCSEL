#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ELECTRON_BIN="$ROOT_DIR/node_modules/.bin/electron"

if [[ ! -x "$ELECTRON_BIN" ]]; then
  echo "Electron dependencies are not installed. Run npm install first." >&2
  exit 1
fi

if [[ -z "${DISPLAY:-}" && -z "${WAYLAND_DISPLAY:-}" ]]; then
  echo "No graphical display session was found." >&2
  echo "Run this app from a Linux desktop terminal with X11 or Wayland access." >&2
  exit 1
fi

if [[ -n "${DISPLAY:-}" ]] && command -v xset >/dev/null 2>&1; then
  if ! xset q >/dev/null 2>&1; then
    echo "The X11 display '$DISPLAY' is set but not accessible from this shell." >&2
    echo "Open a terminal inside the logged-in desktop session, or export the correct DISPLAY/XAUTHORITY values." >&2
    exit 1
  fi
fi

unset ELECTRON_RUN_AS_NODE

cd "$ROOT_DIR"
exec "$ELECTRON_BIN" .
