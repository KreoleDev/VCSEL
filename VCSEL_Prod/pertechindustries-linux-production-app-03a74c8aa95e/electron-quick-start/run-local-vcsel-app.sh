#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
RUNTIME_DIR="$ROOT_DIR/local-runtime"
APP_BIN="$ROOT_DIR/dist/linux-unpacked/pertech-production"
USER_DATA_DIR="$ROOT_DIR/.local-user-data"
ROOT_STUB_LINK="$ROOT_DIR/libPISCAN.so"
DIST_STUB_LINK="$ROOT_DIR/dist/linux-unpacked/libPISCAN.so"

if [[ ! -x "$APP_BIN" ]]; then
  echo "App binary not found: $APP_BIN" >&2
  exit 1
fi

if [[ ! -f "$RUNTIME_DIR/libPISCAN.so" ]]; then
  echo "Missing stub scanner library: $RUNTIME_DIR/libPISCAN.so" >&2
  echo "Compile it first with:" >&2
  echo "gcc -shared -fPIC -o \"$RUNTIME_DIR/libPISCAN.so\" \"$RUNTIME_DIR/libPISCAN_stub.c\"" >&2
  exit 1
fi

mkdir -p "$USER_DATA_DIR"
ln -sf "$RUNTIME_DIR/libPISCAN.so" "$ROOT_STUB_LINK"
ln -sf "$RUNTIME_DIR/libPISCAN.so" "$DIST_STUB_LINK"

if [[ -z "${XAUTHORITY:-}" ]]; then
  XAUTHORITY="$(ls /run/user/"$(id -u)"/.mutter-Xwaylandauth.* 2>/dev/null | head -n1 || true)"
fi

export DISPLAY="${DISPLAY:-:0}"
export XAUTHORITY="${XAUTHORITY:-}"
export DBUS_SESSION_BUS_ADDRESS="${DBUS_SESSION_BUS_ADDRESS:-unix:path=/run/user/$(id -u)/bus}"
export GDK_BACKEND="${GDK_BACKEND:-x11}"
export LD_LIBRARY_PATH="$RUNTIME_DIR${LD_LIBRARY_PATH:+:$LD_LIBRARY_PATH}"

cd "$ROOT_DIR"
exec "$APP_BIN" --user-data-dir="$USER_DATA_DIR"
