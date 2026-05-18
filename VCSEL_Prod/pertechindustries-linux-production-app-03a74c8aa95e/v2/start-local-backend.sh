#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOCROOT="$ROOT_DIR/intranet components"
HOST="${HOST:-127.0.0.1}"
PORT="${PORT:-8000}"

if ! command -v php >/dev/null 2>&1; then
  echo "PHP is not installed. Install php-cli and php-mysql first." >&2
  exit 1
fi

mkdir -p "$DOCROOT/production/v2/analytics/uploads"

echo "Serving $DOCROOT on http://$HOST:$PORT"
php -S "$HOST:$PORT" -t "$DOCROOT"
