#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SQL_FILE="$ROOT_DIR/intranet components/pertech.sql"
MYSQL_BIN="${MYSQL_BIN:-mysql}"
DB_HOST="${PERTECH_DB_HOST:-localhost}"
DB_USER="${PERTECH_DB_USER:-root}"
DB_NAME="${PERTECH_DB_NAME:-pertech}"
DB_PASS="${PERTECH_DB_PASSWORD-}"
DB_PORT="${PERTECH_DB_PORT:-3306}"
DB_SOCKET_AUTH="${PERTECH_DB_SOCKET_AUTH:-0}"
APP_DB_USER="${PERTECH_APP_DB_USER-}"
APP_DB_PASSWORD="${PERTECH_APP_DB_PASSWORD-}"
APP_DB_HOST="${PERTECH_APP_DB_HOST:-localhost}"

if [[ ! -f "$SQL_FILE" ]]; then
  echo "SQL dump not found: $SQL_FILE" >&2
  exit 1
fi

if ! command -v "$MYSQL_BIN" >/dev/null 2>&1; then
  echo "MySQL client not found. Install mysql-client or mariadb-client first." >&2
  exit 1
fi

sql_escape() {
  printf "%s" "$1" | sed "s/'/''/g"
}

MYSQL_CMD=("$MYSQL_BIN")
MYSQL_ARGS=()
if [[ "$DB_SOCKET_AUTH" == "1" ]]; then
  MYSQL_CMD=(sudo "$MYSQL_BIN")
else
  MYSQL_ARGS=(-h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER")
  if [[ -n "$DB_PASS" ]]; then
    MYSQL_ARGS+=(-p"$DB_PASS")
  fi
fi

"${MYSQL_CMD[@]}" "${MYSQL_ARGS[@]}" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET latin1 COLLATE latin1_swedish_ci;"

if [[ -n "$APP_DB_USER" && -n "$APP_DB_PASSWORD" ]]; then
  APP_DB_USER_ESCAPED="$(sql_escape "$APP_DB_USER")"
  APP_DB_PASSWORD_ESCAPED="$(sql_escape "$APP_DB_PASSWORD")"
  APP_DB_HOST_ESCAPED="$(sql_escape "$APP_DB_HOST")"
  "${MYSQL_CMD[@]}" "${MYSQL_ARGS[@]}" -e "CREATE USER IF NOT EXISTS '$APP_DB_USER_ESCAPED'@'$APP_DB_HOST_ESCAPED' IDENTIFIED BY '$APP_DB_PASSWORD_ESCAPED'; GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$APP_DB_USER_ESCAPED'@'$APP_DB_HOST_ESCAPED'; FLUSH PRIVILEGES;"
fi

"${MYSQL_CMD[@]}" "${MYSQL_ARGS[@]}" "$DB_NAME" < "$SQL_FILE"

echo "Imported $DB_NAME from $SQL_FILE"
