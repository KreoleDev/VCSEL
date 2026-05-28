# Cloud API Usage

These endpoints are designed to be served from the same hosted dashboard document root.

If the dashboard is available at:

```text
https://your-dashboard-domain.com/
```

Use this base URL from another app:

```text
https://your-dashboard-domain.com/API/apps-api/
```

## Electron/Production App Endpoints

```text
POST /API/apps-api/login/
POST /API/apps-api/version/
POST /API/apps-api/products/
POST /API/apps-api/tlas/
POST /API/apps-api/tests/
GET  /API/apps-api/tests/load-tests.php?tla_id={tla_id}
POST /API/apps-api/7680-printer-tests/
POST /API/apps-api/7680-vcsel-tests/
POST /API/apps-api/7680-board-tests/
POST /API/apps-api/shipping-tests/
```

JSON requests should use:

```text
Content-Type: application/json
```

Example:

```js
const api = 'https://your-dashboard-domain.com/API/apps-api';

const products = await axios.post(`${api}/products/`, {
  mode: 'getActiveProducts'
});
```

## CORS

By default the API allows cross-origin requests from any origin. To restrict it in the cloud, set:

```text
PERTECH_API_CORS_ORIGIN=https://your-other-app-domain.com
```

The API allows these request headers:

```text
X-Requested-With, Content-Type, Authorization
```

## Static Assets

Product images and firmware files are served from:

```text
/production/lib/images/
/production/uploads/firmwares/7680/
```

Responses such as `img_filename` are built from the current request host unless `PERTECH_PRODUCTION_BASE_URL` is set.
