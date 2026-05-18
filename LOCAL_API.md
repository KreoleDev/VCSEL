# Local API

This Electron project now carries the production API files it needs under:

```text
local-api/production
```

For now the API still uses the existing local MySQL database settings from:

```text
local-api/production/protected/db.info.php
```

You can override them without editing the file:

```bash
PERTECH_DB_HOST=127.0.0.1 \
PERTECH_DB_USER=prinet \
PERTECH_DB_PASSWORD=Pri7680 \
PERTECH_DB_NAME=pertech \
./start-local-api.sh
```

Run the app and local API together on macOS:

```bash
./run-local-mac.sh
```

The Electron app points to:

```text
http://127.0.0.1:8010/production/
```

Later, when the dashboard and database move to the cloud, the same app can point `CFG_DATA_SERVICE_URL` at the cloud API instead.
