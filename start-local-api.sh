#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOCROOT="$ROOT_DIR/local-api"
HOST="${HOST:-127.0.0.1}"
PORT="${PORT:-8010}"
SESSION_DIR="${PERTECH_SESSION_SAVE_PATH:-$ROOT_DIR/.php-sessions}"
UPLOAD_DIR="${PERTECH_PRODUCTION_UPLOAD_PATH:-$DOCROOT/production/uploads}"
BASE_URL="${PERTECH_PRODUCTION_BASE_URL:-http://$HOST:$PORT/production/}"

if ! command -v php >/dev/null 2>&1; then
  echo "PHP is not installed. Install php first, for example: brew install php" >&2
  exit 1
fi

mkdir -p "$SESSION_DIR" "$UPLOAD_DIR"

export PERTECH_SESSION_SAVE_PATH="$SESSION_DIR"
export PERTECH_PRODUCTION_UPLOAD_PATH="$UPLOAD_DIR"
export PERTECH_PRODUCTION_BASE_URL="$BASE_URL"

echo "Serving local production API from $DOCROOT"
echo "API URL: $BASE_URL"

cd "$DOCROOT"
exec php \
  -d "include_path=$DOCROOT:/opt/homebrew/share/php:/usr/local/share/php:/usr/share/php" \
  -d "session.save_path=$SESSION_DIR" \
  -S "$HOST:$PORT" \
  -t "$DOCROOT" \
  "$DOCROOT/router.php"
