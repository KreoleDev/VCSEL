#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PRODUCTION_SQL_FILE="$ROOT_DIR/pertech.sql"
SITES_SQL_FILE="$ROOT_DIR/sites.sql"
MYSQL_BIN="${MYSQL_BIN:-mysql}"
DB_HOST="${PERTECH_DB_HOST:-localhost}"
DB_USER="${PERTECH_DB_USER:-root}"
DB_PASS="${PERTECH_DB_PASSWORD-}"
DB_PORT="${PERTECH_DB_PORT:-3306}"
DB_SOCKET_AUTH="${PERTECH_DB_SOCKET_AUTH:-0}"
DB_RECREATE="${PERTECH_DB_RECREATE:-0}"
PRODUCTION_DB_NAME="${PERTECH_DB_NAME:-pertech}"
SITES_DB_NAME="${PERTECH_SITES_DB_NAME:-sites}"
APP_DB_USER="${PERTECH_APP_DB_USER:-prinet}"
APP_DB_PASSWORD="${PERTECH_APP_DB_PASSWORD:-Pri7680}"
APP_DB_HOST="${PERTECH_APP_DB_HOST:-localhost}"
CREATE_LOCAL_ADMIN="${PERTECH_CREATE_LOCAL_ADMIN:-1}"
LOCAL_ADMIN_USER="${PERTECH_LOCAL_ADMIN_USER:-localadmin}"
LOCAL_ADMIN_PASSWORD="${PERTECH_LOCAL_ADMIN_PASSWORD:-localadmin}"

if [[ ! -f "$PRODUCTION_SQL_FILE" ]]; then
  echo "Production SQL dump not found: $PRODUCTION_SQL_FILE" >&2
  exit 1
fi

if [[ ! -f "$SITES_SQL_FILE" ]]; then
  echo "Sites SQL dump not found: $SITES_SQL_FILE" >&2
  exit 1
fi

if ! command -v "$MYSQL_BIN" >/dev/null 2>&1; then
  echo "MySQL client not found. Install mysql-client or mariadb-client first." >&2
  exit 1
fi

if ! command -v php >/dev/null 2>&1; then
  echo "PHP CLI is not installed. Install php-cli first." >&2
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

run_sql() {
  "${MYSQL_CMD[@]}" "${MYSQL_ARGS[@]}" -e "$1"
}

import_sql_file() {
  local database_name="$1"
  local sql_file="$2"
  "${MYSQL_CMD[@]}" "${MYSQL_ARGS[@]}" "$database_name" < "$sql_file"
}

if [[ "$DB_RECREATE" == "1" ]]; then
  run_sql "DROP DATABASE IF EXISTS \`$PRODUCTION_DB_NAME\`; DROP DATABASE IF EXISTS \`$SITES_DB_NAME\`;"
fi

run_sql "CREATE DATABASE IF NOT EXISTS \`$PRODUCTION_DB_NAME\` CHARACTER SET latin1 COLLATE latin1_swedish_ci;"
run_sql "CREATE DATABASE IF NOT EXISTS \`$SITES_DB_NAME\` CHARACTER SET latin1 COLLATE latin1_swedish_ci;"

if [[ -n "$APP_DB_USER" && -n "$APP_DB_PASSWORD" ]]; then
  APP_DB_USER_ESCAPED="$(sql_escape "$APP_DB_USER")"
  APP_DB_PASSWORD_ESCAPED="$(sql_escape "$APP_DB_PASSWORD")"
  APP_DB_HOST_ESCAPED="$(sql_escape "$APP_DB_HOST")"
  run_sql "CREATE USER IF NOT EXISTS '$APP_DB_USER_ESCAPED'@'$APP_DB_HOST_ESCAPED' IDENTIFIED BY '$APP_DB_PASSWORD_ESCAPED'; GRANT ALL PRIVILEGES ON \`$PRODUCTION_DB_NAME\`.* TO '$APP_DB_USER_ESCAPED'@'$APP_DB_HOST_ESCAPED'; GRANT ALL PRIVILEGES ON \`$SITES_DB_NAME\`.* TO '$APP_DB_USER_ESCAPED'@'$APP_DB_HOST_ESCAPED'; FLUSH PRIVILEGES;"
fi

import_sql_file "$PRODUCTION_DB_NAME" "$PRODUCTION_SQL_FILE"
import_sql_file "$SITES_DB_NAME" "$SITES_SQL_FILE"

if [[ "$CREATE_LOCAL_ADMIN" == "1" ]]; then
  LOCAL_ADMIN_SALT="$(php -r 'echo str_replace("+", ".", base64_encode(random_bytes(16)));')"
  LOCAL_ADMIN_HASH="$(php -r 'echo sha1($argv[1] . $argv[2]);' "$LOCAL_ADMIN_PASSWORD" "$LOCAL_ADMIN_SALT")"
  LOCAL_ADMIN_USER_ESCAPED="$(sql_escape "$LOCAL_ADMIN_USER")"
  LOCAL_ADMIN_SALT_ESCAPED="$(sql_escape "$LOCAL_ADMIN_SALT")"
  LOCAL_ADMIN_HASH_ESCAPED="$(sql_escape "$LOCAL_ADMIN_HASH")"

  run_sql "USE \`$PRODUCTION_DB_NAME\`;
SET @existing_user_id := (SELECT user_id FROM core_users WHERE username='$LOCAL_ADMIN_USER_ESCAPED' LIMIT 1);
SET @next_user_id := COALESCE(@existing_user_id, (SELECT COALESCE(MAX(user_id), 0) + 1 FROM core_users));
INSERT INTO core_users (user_id, username, password, first_name, last_name, position_title, phone, email, active, creation_date, account_expires, expiry_date, password_set_date, salt)
VALUES (@next_user_id, '$LOCAL_ADMIN_USER_ESCAPED', '$LOCAL_ADMIN_HASH_ESCAPED', 'Local', 'Admin', 'Local Administrator', '5555555555', 'localadmin@localhost', 1, NOW(), 0, '1969-12-31 00:00:00', NOW(), '$LOCAL_ADMIN_SALT_ESCAPED')
ON DUPLICATE KEY UPDATE password=VALUES(password), first_name=VALUES(first_name), last_name=VALUES(last_name), position_title=VALUES(position_title), phone=VALUES(phone), email=VALUES(email), active=1, account_expires=0, expiry_date='1969-12-31 00:00:00', password_set_date=NOW(), salt=VALUES(salt);
INSERT INTO core_user_group_lookup (ext_user_id, ext_group_id)
SELECT @next_user_id, 2 FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM core_user_group_lookup WHERE ext_user_id=@next_user_id AND ext_group_id=2
);"
fi

echo "Imported $PRODUCTION_DB_NAME from $PRODUCTION_SQL_FILE"
echo "Imported $SITES_DB_NAME from $SITES_SQL_FILE"
if [[ "$CREATE_LOCAL_ADMIN" == "1" ]]; then
  echo "Local intranet login:"
  echo "  site: pertech"
  echo "  username: $LOCAL_ADMIN_USER"
  echo "  password: $LOCAL_ADMIN_PASSWORD"
fi
