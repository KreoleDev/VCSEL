#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOCROOT="$ROOT_DIR/html"
HOST="${HOST:-127.0.0.1}"
PORT="${PORT:-8001}"
SESSION_DIR="${PERTECH_SESSION_SAVE_PATH:-$ROOT_DIR/.php-sessions}"

if ! command -v php >/dev/null 2>&1; then
  echo "PHP is not installed. Install php-cli and php-mysql first." >&2
  exit 1
fi

mkdir -p "$DOCROOT/production/uploads"
mkdir -p "$SESSION_DIR"

export PERTECH_SESSION_SAVE_PATH="$SESSION_DIR"

echo "Serving $DOCROOT on http://$HOST:$PORT"
cd "$DOCROOT"
php -d "include_path=$DOCROOT:/usr/share/php" -S "$HOST:$PORT" -t "$DOCROOT"
