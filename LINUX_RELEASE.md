# Linux Release Notes

This project builds two Linux artifacts:

- `AppImage` for portable single-file distribution.
- `.deb` for Debian/Ubuntu-style managed installs.

## Build

```sh
npm run dist
```

Artifacts are written to `dist/`.

## Recommended Signing for Website Downloads

For direct downloads from your website, sign the release files with GPG and publish checksums:

```sh
./sign-linux-release.sh
```

This script:

- signs the `.deb` internally with `dpkg-sig` when that tool is installed
- generates `dist/SHA256SUMS`
- creates `dist/SHA256SUMS.asc`
- creates detached `*.asc` signatures for each `AppImage` and `.deb`

The checksum and detached signatures are created after the embedded `.deb` signature so verification matches the final package file.

Optional environment variables:

```sh
export GPG_KEY_ID="YOUR-GPG-KEY-ID"
export DEB_SIGN_KEY_ID="YOUR-GPG-KEY-ID"
```

## One-Time Signing Tool Setup

Create or import your release key:

```sh
gpg --full-generate-key
gpg --list-secret-keys --keyid-format LONG
```

Install the Debian package signing helper when you want embedded `.deb` signatures:

```sh
sudo apt update
sudo apt install dpkg-sig
```

## Verify What You Signed

Verify the detached checksum signature:

```sh
gpg --verify dist/SHA256SUMS.asc dist/SHA256SUMS
sha256sum -c dist/SHA256SUMS
```

Verify a signed Debian package:

```sh
dpkg-sig --verify dist/*.deb
```

## AppImage-Specific Note

This project publishes detached GPG signatures for the AppImage. AppImage also supports embedded signatures, but that flow is handled with `appimagetool --sign` rather than electron-builder.

## Best Practice for Enterprise Distribution

For banks or other managed environments:

- distribute the `.deb` for normal IT deployment
- keep the `AppImage` as a fallback/manual option
- publish `SHA256SUMS` and `SHA256SUMS.asc`
- if you later host an APT repository, sign the repository `Release` metadata as well
