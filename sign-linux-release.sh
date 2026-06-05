#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DIST_DIR="$ROOT_DIR/dist"
GPG_KEY_ARGS=()
DEB_KEY_ARGS=()

if [[ ! -d "$DIST_DIR" ]]; then
  echo "ERROR: dist directory not found. Run npm run dist first." >&2
  exit 1
fi

ARTIFACTS=()

while IFS= read -r file; do
  ARTIFACTS+=("$file")
done < <(find "$DIST_DIR" -maxdepth 1 -type f \( -name "*.AppImage" -o -name "*.deb" \) | sort)

if [[ "${#ARTIFACTS[@]}" -eq 0 ]]; then
  echo "ERROR: No .AppImage or .deb artifacts found in dist." >&2
  exit 1
fi

if ! command -v gpg >/dev/null 2>&1; then
  echo "ERROR: gpg is required to sign release files." >&2
  exit 1
fi

if [[ -n "${GPG_KEY_ID:-}" ]]; then
  GPG_KEY_ARGS=(--local-user "$GPG_KEY_ID")
fi

if [[ -n "${DEB_SIGN_KEY_ID:-}" ]]; then
  DEB_KEY_ARGS=(-k "$DEB_SIGN_KEY_ID")
elif [[ -n "${GPG_KEY_ID:-}" ]]; then
  DEB_KEY_ARGS=(-k "$GPG_KEY_ID")
fi

if command -v dpkg-sig >/dev/null 2>&1; then
  for file in "${ARTIFACTS[@]}"; do
    if [[ "$file" == *.deb ]]; then
      echo "Embedding Debian signature in $(basename "$file")..."
      dpkg-sig "${DEB_KEY_ARGS[@]}" --sign builder "$file"
    fi
  done
else
  echo "dpkg-sig not found; skipped embedded .deb signatures."
fi

echo "Generating SHA256SUMS..."
(
  cd "$DIST_DIR"
  sha256sum -- *.AppImage *.deb > SHA256SUMS
)

echo "Signing SHA256SUMS..."
gpg "${GPG_KEY_ARGS[@]}" --armor --detach-sign --output "$DIST_DIR/SHA256SUMS.asc" "$DIST_DIR/SHA256SUMS"

for file in "${ARTIFACTS[@]}"; do
  echo "Signing $(basename "$file")..."
  gpg "${GPG_KEY_ARGS[@]}" --armor --detach-sign --output "$file.asc" "$file"
done

echo ""
echo "Signed files:"
printf '%s\n' "$DIST_DIR/SHA256SUMS" "$DIST_DIR/SHA256SUMS.asc"
for file in "${ARTIFACTS[@]}"; do
  printf '%s\n' "$file.asc"
done
