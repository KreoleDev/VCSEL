# Pertech Production Apps API

This folder exposes the API contract used by the Electron production app.

Base path:

```text
/API/apps-api/
```

Endpoints:

- `POST /API/apps-api/login/`
- `POST /API/apps-api/version/`
- `POST /API/apps-api/products/`
- `POST /API/apps-api/tlas/`
- `POST /API/apps-api/tests/`
- `GET /API/apps-api/tests/load-tests.php?tla_id={tla_id}`
- `POST /API/apps-api/7680-printer-tests/`
- `POST /API/apps-api/7680-vcsel-tests/`
- `POST /API/apps-api/7680-board-tests/`
- `POST /API/apps-api/shipping-tests/`
- `POST /API/apps-api/camera-testing/`
- `POST /API/apps-api/printer-tally-reads/`

The implementation preserves the response shapes from the existing production API. Static product images and firmware files remain available under `/production/lib/images/` and `/production/uploads/firmwares/7680/`.

Login defaults to `core_users` with the existing salted SHA1 password format. These can be configured with:

```text
PERTECH_AUTH_TABLE
PERTECH_AUTH_USERNAME_COLUMN
PERTECH_AUTH_PASSWORD_COLUMN
PERTECH_AUTH_DISPLAY_NAME_COLUMN
PERTECH_AUTH_ACTIVE_COLUMN
```
