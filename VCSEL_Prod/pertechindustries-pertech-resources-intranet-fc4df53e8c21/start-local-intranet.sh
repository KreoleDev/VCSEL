#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
export PERTECH_CMS_BASE_URL="${PERTECH_CMS_BASE_URL:-http://127.0.0.1:8001/}"

mkdir -p "$ROOT_DIR/html/sites/pertech/uploads"

exec "$ROOT_DIR/start-local-production-backend.sh"
