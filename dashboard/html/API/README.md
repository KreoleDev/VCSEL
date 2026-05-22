# Pertech APIs

This folder is the source of truth for the PHP API endpoints. The older `/production/.../index.php` files are compatibility loaders that require these files.

All endpoints accept JSON POST bodies unless noted otherwise.

The Electron production app API contract lives in:

```text
/API/apps-api/
```

## Endpoints

### `products.php`
- `getActiveProducts`: returns active production products and image URLs.

### `tlas.php`
- `getActiveTLAs`: returns active TLAs for a product.
- `getTLAInfo`: returns product-specific TLA configuration details.

### `tests.php`
- `getTests`: returns active tests for a TLA.

### `version.php`
- `getCurrentVersion`: returns the latest production app version.

### `shipping-tests.php`
- `getCurrentOnPallet`: returns current pallet label and scanned items.
- `startNewPallet`: creates a pallet, then finalizes pending pallets.
- `finalizePallet`: assigns pending pallets to target orders.
- `processScan`: validates shipping scan, marks unit shipped, and records pallet item.

### `7680-printer-tests.php`
- `testFormsKey`
- `fctKey`
- `loadFormKey`
- `feederKey`
- `serialKey`
- `checkSerial`
- `saveSerial`
- `checkSerialVault`
- `saveSerialVault`
- `saveTalliesAndConfig`
- `saveImg`: accepts form POST data.
- `saveImgVault`: accepts form POST data.

### `7680-vcsel-tests.php`
- `saveVcselResults`
- `getNextVcselSN`

### `vcsel-results.php`
- `getVcselResults`: returns dashboard VCSEL result rows with notes.
- `getVcselResultsPage`: returns one server-side DataTables page of dashboard VCSEL result rows.
- `addVcselNote`: adds a VCSEL result note and writes the module action log.

### `7680-board-tests.php`
- `saveBoardTestResults`
