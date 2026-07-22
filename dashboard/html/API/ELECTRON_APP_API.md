# Electron Production App API Guide

This document explains how the Electron production app, or another app replacing it, should call the Pertech cloud API.

## Base URL

If the dashboard is hosted at:

```text
https://your-dashboard-domain.com/
```

Use this API base URL:

```text
https://your-dashboard-domain.com/API/apps-api/
```

For local testing:

```text
http://127.0.0.1:8001/API/apps-api/
```

## Request Format

Most endpoints use JSON POST bodies:

```http
Content-Type: application/json
```

Example:

```js
const apiBase = 'https://your-dashboard-domain.com/API/apps-api';

const response = await axios.post(`${apiBase}/products/`, {
  mode: 'getActiveProducts'
});

console.log(response.data);
```

Some upload endpoints use `multipart/form-data`. The test-code endpoint returns JavaScript, not JSON.

## Startup APIs

### Login

```text
POST /login/
```

Request:

```json
{
  "mode": "login",
  "username": "operator",
  "password": "password"
}
```

Success response:

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

### Current App Version

```text
POST /version/
```

Request:

```json
{
  "mode": "getCurrentVersion"
}
```

Response:

```json
{
  "data": "1.00.019"
}
```

### Active Products

```text
POST /products/
```

Request:

```json
{
  "mode": "getActiveProducts"
}
```

Response:

```json
{
  "data": [
    {
      "product_id": "5",
      "title": "7680 Vcsel",
      "img_filename": "https://your-dashboard-domain.com/production/lib/images/7680vcsel.png"
    }
  ]
}
```

## TLA APIs

### Active TLAs

```text
POST /tlas/
```

Request:

```json
{
  "mode": "getActiveTLAs",
  "product_id": 5
}
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

### TLA Info

```text
POST /tlas/
```

Request:

```json
{
  "mode": "getTLAInfo",
  "tla_id": 31
}
```

7680 Printer response example:

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

7680 Vcsel currently returns:

```json
{
  "data": []
}
```

## Test Definition APIs

### Test List

```text
POST /tests/
```

Request:

```json
{
  "mode": "getTests",
  "tla_id": 31
}
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

### Load Test JavaScript

```text
GET /tests/load-tests.php?tla_id={tla_id}
```

This endpoint returns executable JavaScript, not JSON. The returned script defines:

```js
runTest = function(test_id) {
  // test switch generated from database codeset rows
}
```

The Electron app should load/evaluate this script before running a selected test.

## 7680 Printer APIs

Base endpoint:

```text
POST /7680-printer-tests/
```

### Security Key Modes

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

### Serial Key

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

### Check Printer Serial

Request:

```json
{
  "mode": "checkSerial",
  "barcode": "PRINTER123"
}
```

Response:

```json
{
  "passed": 1
}
```

`passed: 0` means the serial has already shipped.

### Save Printer Serial

Request:

```json
{
  "mode": "saveSerial",
  "tester": "Operator Name",
  "barcode": "PRINTER123"
}
```

Response:

```json
{
  "test_id": 123
}
```

### Check Vault Serial

Request:

```json
{
  "mode": "checkSerialVault",
  "barcode": "VAULT123"
}
```

Response:

```json
{
  "passed": 1
}
```

`passed: 0` means the vault serial has already shipped.

### Save Vault Serial

Request:

```json
{
  "mode": "saveSerialVault",
  "test_id": 123,
  "barcode": "VAULT123"
}
```

Response:

```text
empty body
```

### Save Tallies And Config

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

Response:

```text
empty body
```

### Upload Printer Image

```text
POST /7680-printer-tests/
Content-Type: multipart/form-data
```

Form fields:

```text
mode=saveImg
id={test_id}
file={jpg}
```

The file is stored as:

```text
/production/uploads/7680printer/{test_id}.jpg
```

### Upload Vault Image

```text
POST /7680-printer-tests/
Content-Type: multipart/form-data
```

Form fields:

```text
mode=saveImgVault
id={test_id}
file={jpg}
```

The file is stored as:

```text
/production/uploads/7680printer/{test_id}vault.jpg
```

## 7680 Vcsel APIs

Base endpoint:

```text
POST /7680-vcsel-tests/
```

### Save VCSEL Results

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

Response:

```json
{
  "id": "123",
  "user_id": 123
}
```

### Get Next VCSEL Serial Number

Request:

```json
{
  "mode": "getNextVcselSN"
}
```

Response:

```text
12346
```

This endpoint returns plain text, not JSON.

## 7680 Board Test APIs

Base endpoint:

```text
POST /7680-board-tests/
```

### Save Board Test Results

Request:

```json
{
  "mode": "saveBoardTestResults",
  "testerName": "Operator Name",
  "serialNumber": "BOARD123"
}
```

Response:

```json
{
  "id": "123"
}
```

## Shipping APIs

Base endpoint:

```text
POST /shipping-tests/
```

### Get Current Pallet

Request:

```json
{
  "mode": "getCurrentOnPallet"
}
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

### Start New Pallet

Request:

```json
{
  "mode": "startNewPallet"
}
```

Response:

```text
empty body
```

This also runs the pallet finalization logic.

### Finalize Pallet

Request:

```json
{
  "mode": "finalizePallet"
}
```

Response:

```text
empty body
```

### Process Scan

Request:

```json
{
  "mode": "processScan",
  "mainBarcode": "PRINTER123",
  "vaultBarcode": "VAULT123",
  "testerName": "Operator Name"
}
```

Success response:

```json
{
  "passed": true,
  "failMsg": ""
}
```

Failure response example:

```json
{
  "passed": false,
  "failMsg": "This unit has NOT been through final test!"
}
```

## Burster Home

There is no separate Burster Home API endpoint. Burster Home uses:

```text
POST /tests/
GET  /tests/load-tests.php?tla_id={tla_id}
```

When the selected TLA belongs to product ID `6`, the generated test JavaScript includes the Burster Home helper code. Hardware communication still happens locally in the Electron app.

## Static Assets

Product images:

```text
/production/lib/images/7680printer.png
/production/lib/images/7680vcsel.png
/production/lib/images/burster.png
/production/lib/images/box.png
```

7680 firmware files:

```text
/production/uploads/firmwares/7680/{filename}
```

Example:

```text
https://your-dashboard-domain.com/production/uploads/firmwares/7680/f3-05-900-00a.bin
```

## CORS And Cloud Use

The API supports browser calls from another app. By default it allows any origin.

For production, restrict the allowed browser origin with:

```text
PERTECH_API_CORS_ORIGIN=https://your-other-app-domain.com
```

Allowed request headers:

```text
X-Requested-With, Content-Type, Authorization
```

Allowed methods:

```text
POST, GET, OPTIONS
```

## Tally Reader APIs

Base endpoint:

```text
POST /printer-tally-reads/
```

### Save Printer Tally Read

Request:

```json
{
  "mode": "savePrinterTallyRead",
  "logged_in_user_id": 123,
  "logged_in_username": "operator",
  "user_name": "Operator Name",
  "read_at": "2026-07-21T19:45:00.000Z",
  "printer_name": "PRI USB Printer",
  "usb_vendor_id": 5169,
  "usb_product_id": 30336,
  "usb_device_serial": "USB123",
  "manufacturer_serial_number": "76805555555555",
  "dot_count": "0018467496",
  "form_count": "0000002397",
  "void_count": "0000000006",
  "burst_count": "0000002339",
  "vault_install_count": "0000000061",
  "total_time_on_hours": "0000000812",
  "printer_resets": "0000000161",
  "firmware_updates_count": "0000000055",
  "external_sheets_loaded": "0000000036",
  "ribbon_count": "0000000003",
  "last_ribbon_change_dot_count": "0015830714",
  "read_success": true,
  "read_error": "",
  "raw_serial_ascii": "76805555555555\r",
  "raw_tally_ascii": "0018467496\r0000002397\r..."
}
```

Response:

```json
{
  "success": true,
  "id": "123"
}
```

### Read Printer Tally Rows

Dashboard/API readers can use:

```json
{
  "mode": "getPrinterTallyReads",
  "limit": 200
}
```

or a server-side DataTables request:

```json
{
  "mode": "getPrinterTallyReadsPage",
  "draw": 1,
  "start": 0,
  "length": 20
}
```

## Important Compatibility Notes

- Keep the response shapes as shown above unless the Electron app is updated at the same time.
- Several IDs are returned as strings because the legacy Electron app expects that behavior.
- `getNextVcselSN` returns plain text.
- `tests/load-tests.php` returns executable JavaScript generated from database `codeset` values.
- Hardware operations stay local in Electron. The cloud API only provides configuration, test scripts, firmware files, and result storage.
