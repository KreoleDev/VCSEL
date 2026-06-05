#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DIST_DIR="$ROOT_DIR/dist"
CACHE_DIR="${ELECTRON_BUILDER_CACHE:-$ROOT_DIR/.cache/electron-builder}"
SYSTEM_NODE="$(command -v node || true)"
BUILD_NODE=""
NPM_CLI_JS="/usr/share/nodejs/npm/bin/npm-cli.js"

echo "============================================================"
echo " Pertech VCSEL Linux Build"
echo "============================================================"
echo ""

cd "$ROOT_DIR"

if [[ ! -f "package.json" || ! -f "main.js" ]]; then
  echo "ERROR: Run this script from the Electron project root."
  exit 1
fi

command -v node >/dev/null 2>&1 || { echo "ERROR: node is not installed."; exit 1; }
command -v npm >/dev/null 2>&1 || { echo "ERROR: npm is not installed."; exit 1; }

if [[ ! -d "node_modules" ]]; then
  echo "ERROR: node_modules is missing. Run npm install first."
  exit 1
fi

if [[ -n "$SYSTEM_NODE" ]]; then
  if "$SYSTEM_NODE" -e "const [major, minor] = process.versions.node.split('.').map(Number); process.exit(major > 14 || (major === 14 && minor >= 18) ? 0 : 1)"; then
    BUILD_NODE="$SYSTEM_NODE"
  fi
fi

if [[ -z "$BUILD_NODE" ]]; then
  CACHED_NODE="$(find "$HOME/.npm/_npx" -path '*node_modules/node/bin/node' 2>/dev/null | head -n 1 || true)"

  if [[ -n "$CACHED_NODE" ]] && "$CACHED_NODE" -e "const [major, minor] = process.versions.node.split('.').map(Number); process.exit(major > 14 || (major === 14 && minor >= 18) ? 0 : 1)"; then
    BUILD_NODE="$CACHED_NODE"
  fi
fi

if [[ -z "$BUILD_NODE" ]]; then
  echo "Downloading temporary Node 18 runtime for the build..."
  BUILD_NODE="$(npx -y node@18 -p "process.execPath" 2>/dev/null | tail -n 1 || true)"
fi

if [[ -z "$BUILD_NODE" ]] || [[ ! -x "$BUILD_NODE" ]]; then
  echo "ERROR: A Node 14.18+ runtime is required to build the Linux packages." >&2
  echo "Install Node 18 LTS or rerun this command with internet access so npx can fetch node@18." >&2
  exit 1
fi

BUILD_NODE_VERSION="$("$BUILD_NODE" -p "process.versions.node")"
echo "Using Node: $BUILD_NODE_VERSION"

mkdir -p "$CACHE_DIR"
export ELECTRON_BUILDER_CACHE="$CACHE_DIR"

if [[ "$CACHE_DIR" != "$HOME/.cache/electron-builder" ]] && [[ -d "$HOME/.cache/electron-builder" ]]; then
  if [[ ! -d "$CACHE_DIR/appimage" ]] && [[ -d "$HOME/.cache/electron-builder/appimage" ]]; then
    cp -R "$HOME/.cache/electron-builder/appimage" "$CACHE_DIR/appimage"
  fi

  if [[ ! -d "$CACHE_DIR/fpm" ]] && [[ -d "$HOME/.cache/electron-builder/fpm" ]]; then
    cp -R "$HOME/.cache/electron-builder/fpm" "$CACHE_DIR/fpm"
  fi
fi

rm -rf "$DIST_DIR"

echo "Building Linux AppImage and .deb package..."

BUILD_ARGS=(./node_modules/electron-builder/cli.js --linux AppImage deb)

ELECTRON_VERSION="$("$BUILD_NODE" -p "require('./node_modules/electron/package.json').version")"
ELECTRON_ARCH="$("$BUILD_NODE" -p "process.arch")"
CACHED_ELECTRON_ZIP="$(find "$HOME/.cache/electron" -name "electron-v${ELECTRON_VERSION}-linux-${ELECTRON_ARCH}.zip" 2>/dev/null | head -n 1 || true)"

if [[ -n "$CACHED_ELECTRON_ZIP" ]]; then
  BUILD_ARGS+=("-c.electronDist=$(dirname "$CACHED_ELECTRON_ZIP")")
fi

if [[ "$BUILD_NODE" != "$SYSTEM_NODE" ]] && [[ -f "$NPM_CLI_JS" ]]; then
  export NODE_PATH="/usr/share/nodejs${NODE_PATH:+:$NODE_PATH}"
  export npm_execpath="$NPM_CLI_JS"
  export npm_node_execpath="$BUILD_NODE"
  export NODE_EXE="$BUILD_NODE"
fi

"$BUILD_NODE" "${BUILD_ARGS[@]}"

APPIMAGE_FILE="$(find "$DIST_DIR" -maxdepth 1 -name "*.AppImage" | head -n 1)"
DEB_FILE="$(find "$DIST_DIR" -maxdepth 1 -name "*.deb" | head -n 1)"

if [[ -z "$APPIMAGE_FILE" ]]; then
  echo "ERROR: No AppImage file found in dist."
  exit 1
fi

if [[ -z "$DEB_FILE" ]]; then
  echo "ERROR: No .deb file found in dist."
  exit 1
fi

echo ""
echo "Linux packages ready:"
echo "$APPIMAGE_FILE"
echo "$DEB_FILE"
