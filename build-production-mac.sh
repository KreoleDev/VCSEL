#!/bin/bash

set -e

echo "Cleaning old dist..."
rm -rf dist

echo "Building signed and notarized DMG..."
npm run dist

DMG_FILE=$(find dist -name "*.dmg" | head -n 1)

if [ -z "$DMG_FILE" ]; then
  echo "No DMG file found."
  exit 1
fi

echo "Signing DMG file..."
codesign --force --sign "Developer ID Application: Pertech Industries Inc. (ZC2GFZLK83)" --timestamp "$DMG_FILE"

echo "Submitting DMG for notarization..."
xcrun notarytool submit "$DMG_FILE" \
  --apple-id "development@pertechindustries.com" \
  --password "omsk-dvqr-srcm-jkfa" \
  --team-id "ZC2GFZLK83" \
  --wait

echo "Stapling notarization ticket..."
xcrun stapler staple "$DMG_FILE"

echo "Validating stapled DMG..."
xcrun stapler validate "$DMG_FILE"

echo "Checking Gatekeeper..."
spctl -a -vvv -t install "$DMG_FILE"

echo "Production DMG ready:"
echo "$DMG_FILE"