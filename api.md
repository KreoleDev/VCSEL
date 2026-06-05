# Pertech Production Cloud API Contract

This document describes the API the Electron production app needs when the `pertech` database is moved to the cloud.

The current app calls a base URL from `CFG_DATA_SERVICE_URL` in `config.js`. Today that points to:

```text
http://127.0.0.1:8010/production/
```

The cloud API should expose the same behavior under a new base URL, for example:

```text
https://api.pertechindustries.com/production/
```

The app currently sends JSON with `axios.post(...)`. Existing PHP labels it `application/x-www-form-urlencoded`, but the body is JSON. A new API should accept `Content-Type: application/json` and may also accept the current header for compatibility.

## Products Covered

The app currently tests these product/menu options:

```text
7680 Printer
7680 Vcsel
Burster Home
Shipping
```

Product, TLA, test list, instructions, and test code are database-driven. Hardware reads/writes happen locally in Electron through serial, USB, or scanner native modules. The cloud API should not talk directly to hardware.

## Database

Current default local DB config is in `local-api/production/protected/db.info.php`:

```text
host: localhost
user: prinet
database: pertech
```

When moved to cloud, the API should connect to the cloud `pertech` database or equivalent migrated schema.

Core tables used by the app:

```text
2019_prod_versions
2019_prod_products
2019_prod_tlas
2019_prod_tla_info_product_4
2019_prod_firmwares
2019_prod_tests
2019_prod_tla_test_assoc
2019_prod_7680_printer_results
2019_prod_7680_vcsel_results
2019_prod_7680_vcsel_alt_values
2019_prod_7680_board_test_results
2019_prod_pallets
2019_prod_pallet_items
2019_prod_7680_target_dates
```

There are also older product-3 support tables referenced by `tlas/index.php`; keep them if that product type still exists in the DB.

## Response Wrapper

Most list endpoints return:

```json
{
  "data": []
}
```

Some command endpoints return custom objects, for example:

```json
{ "key": [1, 2] }
{ "test_id": 123 }
{ "passed": true, "failMsg": "" }
```

Keep the response shapes stable unless the Electron app is updated at the same time.

## App Startup Endpoints

### POST `/login/`

Authenticates the operator before the app can be used. Any valid DB user is allowed into the app; the app does not apply role-based restrictions. This login identity is only for app access; the test flow still asks for `testerName` separately.

Request:

```json
{
  "mode": "login",
  "username": "operator",
  "password": "password"
}
```

Response:

```json
{
  "authenticated": true,
  "display_name": "Operator Name",
  "user_id": 123,
  "username": "operator"
}
```

Failure response:

```json
{
  "authenticated": false,
  "display_name": ""
}
```

The local PHP endpoint reads from the existing DB login table. Configure the table and columns with:

```text
PERTECH_AUTH_TABLE
PERTECH_AUTH_USERNAME_COLUMN
PERTECH_AUTH_PASSWORD_COLUMN
PERTECH_AUTH_DISPLAY_NAME_COLUMN
PERTECH_AUTH_ACTIVE_COLUMN
```

The cloud API should validate against the same DB-backed login records.

### POST `/version/`

Checks whether the app version is current.

Request:

```json
{
  "mode": "getCurrentVersion"
}
```

Reads:

```sql
2019_prod_versions.version_number
```

Response:

```json
{
  "data": "1.00.019"
}
```

### POST `/products/`

Returns active product cards for the first screen.

Request:

```json
{
  "mode": "getActiveProducts"
}
```

Reads:

```sql
2019_prod_products.product_id
2019_prod_products.title
2019_prod_products.img_filename
```

Response:

```json
{
  "data": [
    {
      "product_id": "5",
      "title": "7680 Vcsel",
      "img_filename": "https://api.example.com/production/lib/images/7680vcsel.png"
    }
  ]
}
```

The `img_filename` value should be a URL the Electron app can load.

## TLA Endpoints

### POST `/tlas/`

Returns active TLAs for the selected product.

Request:

```json
{
  "mode": "getActiveTLAs",
  "product_id": 5
}
```

Reads:

```sql
2019_prod_tlas
```

Filter:

```sql
active = 1
ext_product_id = :product_id
```

Response:

```json
{
  "data": [
    {
      "tla_id": "31",
      "tla_number": "253 - 213"
    }
  ]
}
```

### POST `/tlas/`

Returns display/config info for a selected TLA.

Request:

```json
{
  "mode": "getTLAInfo",
  "tla_id": 31
}
```

Reads:

```sql
2019_prod_tlas
```

For `ext_product_id = 4` / 7680 Printer, also reads:

```sql
2019_prod_tla_info_product_4
2019_prod_firmwares
```

Printer response example:

```json
{
  "data": [
    {
      "id": "firmware",
      "title": "Firmware",
      "value": "f3-05"
    },
    {
      "id": "wide_vault",
      "title": "Wide Vault",
      "value": "Yes"
    }
  ]
}
```

For `ext_product_id = 5` / 7680 Vcsel, the current local implementation returns an empty `data` array.

## Test Definition Endpoints

### POST `/tests/`

Returns the sidebar test list and instructions for the selected TLA.

Request:

```json
{
  "mode": "getTests",
  "tla_id": 31
}
```

Reads:

```sql
2019_prod_tests
2019_prod_tla_test_assoc
```

Join/filter:

```sql
test_id = ext_test_id
active = 1
ext_tla_id = :tla_id
ORDER BY sort_order
```

Response:

```json
{
  "data": [
    {
      "test_id": "31",
      "title": "Calibrate Vcsel Rheo + Rheo",
      "description": "",
      "instructions": "Load Vcsel into fixture",
      "is_current": "1",
      "passed_test": "false"
    }
  ]
}
```

### GET `/tests/load-tests.php?tla_id={tla_id}`

Returns executable JavaScript for the selected TLA. This is not JSON.

The generated script must define:

```js
runTest = function(test_id) { ... }
```

Reads:

```sql
2019_prod_tlas.ext_product_id
2019_prod_tests.codeset
2019_prod_tla_test_assoc
```

Behavior:

1. Determine product type from `2019_prod_tlas.ext_product_id`.
2. Include helper functions for supported products:
   - `4`: 7680 Printer helper script
   - `5`: 7680 Vcsel helper script
   - `6`: Burster Home helper script
3. Load each test's `codeset` from `2019_prod_tests`.
4. Emit a JavaScript `switch(test_id)` where each case runs that test's `codeset`.

Important migration note: because `codeset` is JavaScript stored in the DB, the cloud API must preserve this endpoint and continue returning executable JavaScript generated from the database.

## Static Assets

The API must serve product images:

```text
/production/lib/images/7680printer.png
/production/lib/images/7680vcsel.png
/production/lib/images/burster.png
/production/lib/images/box.png
```

The API must serve 7680 printer firmware files referenced by printer TLA info:

```text
/production/uploads/firmwares/7680/{filename}
```

Current examples:

```text
f3-03-900-00a.bin
f3-04-900-00a.bin
f3-05-900-00a.bin
```

## 7680 Printer API

Base endpoint:

```text
POST /7680-printer-tests/
```

Handled currently by `local-api/production/7680-printer-tests/index.php`.

### Security Key Modes

These endpoints calculate challenge/response bytes. They do not write to DB.

Supported modes:

```text
testFormsKey
fctKey
loadFormKey
feederKey
```

Request:

```json
{
  "mode": "fctKey",
  "key0": 12,
  "key1": 34
}
```

Response:

```json
{
  "key": [123, 45]
}
```

### `serialKey`

Request:

```json
{
  "mode": "serialKey",
  "key0": 0,
  "key1": 0,
  "key2": 0,
  "key3": 0,
  "key4": 0,
  "key5": 0,
  "key6": 0,
  "key7": 0,
  "key8": 0,
  "key9": 0,
  "key10": 0,
  "key11": 0,
  "key12": 0,
  "key13": 0
}
```

Response:

```json
{
  "key": 237
}
```

### `checkSerial`

Checks that a printer serial has not already shipped.

Request:

```json
{
  "mode": "checkSerial",
  "barcode": "PRINTER123"
}
```

Reads:

```sql
2019_prod_7680_printer_results
```

Response:

```json
{
  "passed": 1
}
```

`passed: 0` means the serial was already shipped.

### `saveSerial`

Creates the main printer test row.

Request:

```json
{
  "mode": "saveSerial",
  "tester": "Operator Name",
  "barcode": "PRINTER123"
}
```

Writes:

```sql
2019_prod_7680_printer_results
```

Fields:

```text
tester_name
date_time = NOW()
printer_serial_num
```

Response:

```json
{
  "test_id": 123
}
```

### `checkSerialVault`

Checks that a vault serial has not already shipped.

Request:

```json
{
  "mode": "checkSerialVault",
  "barcode": "VAULT123"
}
```

Reads:

```sql
2019_prod_7680_printer_results
```

Response:

```json
{
  "passed": 1
}
```

### `saveSerialVault`

Updates the printer test row with vault serial.

Request:

```json
{
  "mode": "saveSerialVault",
  "test_id": 123,
  "barcode": "VAULT123"
}
```

Writes:

```sql
2019_prod_7680_printer_results.vault_serial_num
```

Current PHP returns an empty body.

### `saveTalliesAndConfig`

Updates the printer test row with config and tally data read from the printer.

Request:

```json
{
  "mode": "saveTalliesAndConfig",
  "test_id": 123,
  "fct": 1,
  "ac_coupled_barcode_enabled": 0,
  "burster_sensor_cfg": 0,
  "print_line_offset": 0,
  "burst_search_max": 0,
  "burst_mark_to_perf": 0,
  "wide_vault_enabled": 0,
  "design_level": 0,
  "flash_write_protect": 0,
  "tal_dot_count": "0",
  "tal_frm_count": "0",
  "tal_void_count": "0",
  "tal_burst_count": "0",
  "tal_vault_count": "0",
  "tal_time_on": "0",
  "tal_resets_count": "0",
  "tal_firmware_updates_count": "0",
  "tal_ext_sheets_count": "0",
  "tal_ribbons_count": "0",
  "tal_ribbon_dot_count": "0",
  "additional_notes": ""
}
```

Writes:

```sql
2019_prod_7680_printer_results
```

Current PHP returns an empty body.

### Multipart `saveImg`

Uploads printer image.

Request:

```text
POST /7680-printer-tests/
Content-Type: multipart/form-data

mode=saveImg
id={test_id}
file={jpg}
```

Stores file:

```text
uploads/7680printer/{test_id}.jpg
```

### Multipart `saveImgVault`

Uploads vault image.

Request:

```text
POST /7680-printer-tests/
Content-Type: multipart/form-data

mode=saveImgVault
id={test_id}
file={jpg}
```

Stores file:

```text
uploads/7680printer/{test_id}vault.jpg
```

## 7680 Vcsel API

Base endpoint:

```text
POST /7680-vcsel-tests/
```

Handled currently by `local-api/production/7680-vcsel-tests/index.php`.

### `saveVcselResults`

Saves VCSEL calibration/test result.

Request:

```json
{
  "mode": "saveVcselResults",
  "logged_in_user_id": 123,
  "logged_in_username": "operator",
  "tester_name": "Operator Name",
  "programmer_serial_num": "PROGRAM1",
  "set_transmitter_val": 20,
  "set_collector_val": 27,
  "collector_voltage": 1.65,
  "vcselSerialNumber": 12345,
  "collected_data": [
    {
      "transmitter": 20,
      "collector": 27,
      "voltage": 1.65
    }
  ]
}
```

Writes main row:

```sql
2019_prod_7680_vcsel_results
```

Fields:

```text
tester_name
date_time = NOW()
programmer_serial_num
transmitter_val
collector_val
collector_voltage
vcselSerialNumber
```

Writes detail rows:

```sql
2019_prod_7680_vcsel_alt_values
```

Fields:

```text
ext_test_id
transmitter_val
collector_val
collector_voltage
```

Response:

```json
{
  "id": "123",
  "user_id": 123
}
```

### `getNextVcselSN`

Returns the next VCSEL serial number.

Request:

```json
{
  "mode": "getNextVcselSN"
}
```

Reads:

```sql
2019_prod_7680_vcsel_results.vcselSerialNumber
```

Response:

```text
12346
```

The current endpoint returns plain text, not JSON.

## Burster Home API

There is no separate `burster-home-tests/` PHP endpoint in the current local API.

Burster Home uses:

```text
POST /tests/
GET /tests/load-tests.php?tla_id={tla_id}
```

When `2019_prod_tlas.ext_product_id = 6`, `load-tests.php` includes:

```text
tests/burster-home-sensor-test-config.inc.php
```

That helper talks to the programmer locally over serial:

```text
findBursterOnSerial()
getIsDeviceConnected()
getLEDValue()
getCollectorValue()
```

If Burster Home saves results, that behavior is currently expected to be in `2019_prod_tests.codeset` for the selected TLA. The cloud API must preserve those `codeset` scripts and continue serving them through `tests/load-tests.php`.

## 7680 Board Test API

Base endpoint:

```text
POST /7680-board-tests/
```

Handled currently by `local-api/production/7680-board-tests/index.php`.

### `saveBoardTestResults`

Request:

```json
{
  "mode": "saveBoardTestResults",
  "testerName": "Operator Name",
  "serialNumber": "BOARD123"
}
```

Writes:

```sql
2019_prod_7680_board_test_results
```

Fields:

```text
testerName
dateTime = NOW()
serialNumber
```

Response:

```json
{
  "id": "123"
}
```

## Shipping API

Base endpoint:

```text
POST /shipping-tests/
```

Handled currently by `local-api/production/shipping-tests/index.php`.

### `getCurrentOnPallet`

Returns the current pallet label and items already scanned on the active pallet.

Request:

```json
{
  "mode": "getCurrentOnPallet"
}
```

Reads:

```sql
2019_prod_pallets
2019_prod_pallet_items
2019_prod_7680_target_dates
```

Response:

```json
{
  "palletId": 1,
  "items": [
    {
      "main": "PRINTER123",
      "vault": "VAULT123"
    }
  ]
}
```

### `startNewPallet`

Starts a new pallet, then runs the same finalization logic used by `finalizePallet`.

Request:

```json
{
  "mode": "startNewPallet"
}
```

Writes:

```sql
2019_prod_pallets.startDateTime = NOW()
```

Current PHP returns an empty body.

### `finalizePallet`

Assigns unfinished pallets to the correct target/order and friendly pallet label.

Request:

```json
{
  "mode": "finalizePallet"
}
```

Reads/writes:

```sql
2019_prod_pallets
2019_prod_pallet_items
2019_prod_7680_printer_results
2019_prod_7680_target_dates
```

Current PHP returns an empty body.

### `processScan`

Processes one shipping scan pair.

Request:

```json
{
  "mode": "processScan",
  "mainBarcode": "PRINTER123",
  "vaultBarcode": "VAULT123",
  "testerName": "Operator Name"
}
```

Reads:

```sql
2019_prod_7680_printer_results
2019_prod_pallets
```

If valid, writes in a transaction:

```sql
UPDATE 2019_prod_7680_printer_results
SET passed_shipping = 1,
    passed_shipping_date_time = NOW()
WHERE printer_serial_num = :mainBarcode
  AND vault_serial_num = :vaultBarcode
```

And:

```sql
INSERT INTO 2019_prod_pallet_items
SET extPalletId = :palletId,
    extPrinterSerialNum = :mainBarcode,
    extVaultSerialNum = :vaultBarcode,
    dateTime = NOW(),
    testerName = :testerName
```

Response:

```json
{
  "passed": true,
  "failMsg": ""
}
```

Failure examples:

```json
{
  "passed": false,
  "failMsg": "This unit has NOT been through final test!"
}
```

## Cloud API Implementation Notes

1. Keep endpoint paths and response shapes stable if the existing Electron app will be reused unchanged.
2. Use parameterized SQL for every request.
3. Add authentication before exposing this API outside the LAN.
4. Do not expose `tests/load-tests.php` publicly without access control. It returns executable JavaScript from DB `codeset`.
5. Consider returning JSON errors consistently:

```json
{
  "error": "message"
}
```

6. Preserve existing numeric/string behavior where the current app expects it. Several responses use strings for IDs.
7. Make uploaded images and firmware files available through HTTPS URLs.
8. Use transactions for multi-write operations:
   - VCSEL result plus alt values
   - Shipping scan update plus pallet item insert
9. Add indexes for common lookups:
   - `2019_prod_tlas(ext_product_id, active)`
   - `2019_prod_tla_test_assoc(ext_tla_id, sort_order)`
   - `2019_prod_7680_printer_results(printer_serial_num)`
   - `2019_prod_7680_printer_results(vault_serial_num)`
   - `2019_prod_7680_printer_results(passed_shipping)`
   - `2019_prod_pallet_items(extPalletId)`

## Hardware Boundary

The cloud API provides configuration, test scripts, firmware files, and result storage.

The Electron app still performs local hardware work:

```text
USB printer communication: lib/js/generic-usb.api.js
Serial printer/VCSEL/Burster communication: lib/js/rs232.api.js
6100 scanner communication: lib/js/pertech-6100.api.js
```

Do not move those hardware operations into the cloud API unless the physical devices are also attached to the server, which is not the current design.
