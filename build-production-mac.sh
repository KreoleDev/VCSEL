#!/bin/bash

set -e

# ============================================================
# Pertech VCSEL - Production macOS DMG Build Script
# Builds, signs, notarizes, staples, and verifies the DMG.
# ============================================================

APP_NAME="VCSEL"
APP_ID="com.pertechindustries.vcsel"
TEAM_ID="ZC2GFZLK83"
SIGNING_IDENTITY="Developer ID Application: Pertech Industries Inc. (ZC2GFZLK83)"
PROJECT_DIR="$(pwd)"
DIST_DIR="$PROJECT_DIR/dist"

echo "============================================================"
echo " Pertech VCSEL Production Build"
echo "============================================================"
echo ""

if [ -f ".apple-notarization.env" ]; then
  echo "Loading Apple notarization variables from .apple-notarization.env..."
  source ".apple-notarization.env"
else
  echo "WARNING: .apple-notarization.env file not found."
  echo "Create it with:"
  echo "export APPLE_ID=\"development@pertechindustries.com\""
  echo "export APPLE_APP_SPECIFIC_PASSWORD=\"YOUR-APP-SPECIFIC-PASSWORD\""
  echo "export APPLE_TEAM_ID=\"ZC2GFZLK83\""
fi

# ------------------------------------------------------------
# Step 1: Confirm project folder
# ------------------------------------------------------------
echo "Step 1: Checking project folder..."

if [ ! -f "package.json" ]; then
  echo "ERROR: package.json not found."
  echo "Run this script from the Electron project root folder."
  exit 1
fi

if [ ! -f "main.js" ]; then
  echo "ERROR: main.js not found."
  echo "Run this script from the Electron project root folder."
  exit 1
fi

echo "Project folder OK: $PROJECT_DIR"
echo ""

# ------------------------------------------------------------
# Step 2: Check required commands
# ------------------------------------------------------------
echo "Step 2: Checking required tools..."

command -v node >/dev/null 2>&1 || { echo "ERROR: node is not installed."; exit 1; }
command -v npm >/dev/null 2>&1 || { echo "ERROR: npm is not installed."; exit 1; }
command -v xcrun >/dev/null 2>&1 || { echo "ERROR: Xcode command line tools are missing."; exit 1; }
command -v codesign >/dev/null 2>&1 || { echo "ERROR: codesign is missing."; exit 1; }
command -v spctl >/dev/null 2>&1 || { echo "ERROR: spctl is missing."; exit 1; }

echo "Node: $(node -v)"
echo "npm: $(npm -v)"
echo "Xcode path: $(xcode-select -p)"
echo ""

# ------------------------------------------------------------
# Step 3: Check Apple signing certificate
# ------------------------------------------------------------
echo "Step 3: Checking Developer ID certificate..."

if security find-identity -v -p codesigning | grep -q "Developer ID Application: Pertech Industries Inc. (ZC2GFZLK83)"; then
  echo "Developer ID certificate found."
else
  echo "ERROR: Developer ID Application certificate not found in Keychain."
  echo "Expected: Developer ID Application: Pertech Industries Inc. (ZC2GFZLK83)"
  exit 1
fi

echo ""

# ------------------------------------------------------------
# Step 4: Check Apple notarization environment variables
# ------------------------------------------------------------
echo "Step 4: Checking Apple notarization variables..."

if [ -z "$APPLE_ID" ]; then
  echo "ERROR: APPLE_ID is not set."
  echo "Run: export APPLE_ID=\"development@pertechindustries.com\""
  exit 1
fi

if [ -z "$APPLE_APP_SPECIFIC_PASSWORD" ]; then
  echo "ERROR: APPLE_APP_SPECIFIC_PASSWORD is not set."
  echo "Run: export APPLE_APP_SPECIFIC_PASSWORD=\"xxxx-xxxx-xxxx-xxxx\""
  exit 1
fi

if [ -z "$APPLE_TEAM_ID" ]; then
  echo "ERROR: APPLE_TEAM_ID is not set."
  echo "Run: export APPLE_TEAM_ID=\"ZC2GFZLK83\""
  exit 1
fi

if [ "$APPLE_TEAM_ID" != "$TEAM_ID" ]; then
  echo "ERROR: APPLE_TEAM_ID does not match expected Team ID."
  echo "Expected: $TEAM_ID"
  echo "Found: $APPLE_TEAM_ID"
  exit 1
fi

echo "APPLE_ID: $APPLE_ID"
echo "APPLE_TEAM_ID: $APPLE_TEAM_ID"
echo "Apple app-specific password: set"
echo ""

# ------------------------------------------------------------
# Step 5: Check package.json configuration
# ------------------------------------------------------------
echo "Step 5: Checking package.json..."

PACKAGE_APP_ID=$(node -p "require('./package.json').build && require('./package.json').build.appId")
PACKAGE_PRODUCT_NAME=$(node -p "require('./package.json').build && require('./package.json').build.productName")
PACKAGE_DIST_SCRIPT=$(node -p "require('./package.json').scripts && require('./package.json').scripts.dist")

if [ "$PACKAGE_APP_ID" != "$APP_ID" ]; then
  echo "ERROR: package.json build.appId is wrong."
  echo "Expected: $APP_ID"
  echo "Found: $PACKAGE_APP_ID"
  exit 1
fi

if [ "$PACKAGE_PRODUCT_NAME" != "$APP_NAME" ]; then
  echo "ERROR: package.json build.productName is wrong."
  echo "Expected: $APP_NAME"
  echo "Found: $PACKAGE_PRODUCT_NAME"
  exit 1
fi

if [[ "$PACKAGE_DIST_SCRIPT" != *"electron-builder"* ]]; then
  echo "ERROR: package.json scripts.dist does not use electron-builder."
  exit 1
fi

echo "package.json OK"
echo "App ID: $PACKAGE_APP_ID"
echo "Product name: $PACKAGE_PRODUCT_NAME"
echo "Dist script: $PACKAGE_DIST_SCRIPT"
echo ""

# ------------------------------------------------------------
# Step 6: Check icon
# ------------------------------------------------------------
echo "Step 6: Checking app icon..."

if [ -f "build/icons/icon.icns" ]; then
  echo "Icon found: build/icons/icon.icns"
else
  echo "WARNING: build/icons/icon.icns not found."
  echo "The app may build with default icon or fail if package.json requires it."
fi

echo ""

# ------------------------------------------------------------
# Step 7: Clean old output
# ------------------------------------------------------------
echo "Step 7: Cleaning old dist folder..."

rm -rf "$DIST_DIR"

echo "dist cleaned."
echo ""

# ------------------------------------------------------------
# Step 8: Install dependencies if needed
# ------------------------------------------------------------
echo "Step 8: Checking node_modules..."

if [ ! -d "node_modules" ]; then
  echo "node_modules not found. Running npm install..."
  npm install
else
  echo "node_modules exists. Skipping npm install."
fi

echo ""

# ------------------------------------------------------------
# Step 9: Build DMG
# ------------------------------------------------------------
echo "Step 9: Building DMG using npm run dist..."

npm run dist

echo ""
echo "Build command finished."
echo ""

# ------------------------------------------------------------
# Step 10: Find DMG
# ------------------------------------------------------------
echo "Step 10: Locating DMG file..."

DMG_FILE=$(find "$DIST_DIR" -maxdepth 1 -name "*.dmg" | head -n 1)

if [ -z "$DMG_FILE" ]; then
  echo "ERROR: No DMG file found in dist."
  exit 1
fi

echo "DMG found: $DMG_FILE"
echo ""

# ------------------------------------------------------------
# Step 11: Sign DMG file
# ------------------------------------------------------------
echo "Step 11: Signing DMG file..."

codesign --force \
  --sign "$SIGNING_IDENTITY" \
  --timestamp \
  "$DMG_FILE"

echo "DMG signed."
echo ""

# ------------------------------------------------------------
# Step 12: Verify DMG signature
# ------------------------------------------------------------
echo "Step 12: Verifying DMG signature..."

codesign --verify --verbose "$DMG_FILE"

echo "DMG signature verified."
echo ""

# ------------------------------------------------------------
# Step 13: Submit DMG to Apple notarization
# ------------------------------------------------------------
echo "Step 13: Submitting DMG to Apple notarization..."
echo "This can take a few minutes."

xcrun notarytool submit "$DMG_FILE" \
  --apple-id "$APPLE_ID" \
  --password "$APPLE_APP_SPECIFIC_PASSWORD" \
  --team-id "$APPLE_TEAM_ID" \
  --wait

echo "Apple notarization completed."
echo ""

# ------------------------------------------------------------
# Step 14: Staple notarization ticket
# ------------------------------------------------------------
echo "Step 14: Stapling notarization ticket..."

xcrun stapler staple "$DMG_FILE"

echo "Stapling completed."
echo ""

# ------------------------------------------------------------
# Step 15: Validate stapled DMG
# ------------------------------------------------------------
echo "Step 15: Validating stapled DMG..."

xcrun stapler validate "$DMG_FILE"

echo "Stapled DMG validated."
echo ""

# ------------------------------------------------------------
# Step 16: Gatekeeper check
# ------------------------------------------------------------
echo "Step 16: Checking Gatekeeper acceptance..."

spctl -a -vvv -t install "$DMG_FILE"

echo ""
echo "============================================================"
echo " Production DMG is ready"
echo "============================================================"
echo ""
echo "File:"
echo "$DMG_FILE"
echo ""
echo "Next step:"
echo "Upload this DMG to your server downloads folder."
echo ""



