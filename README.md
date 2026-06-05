# Pertech Production App

Electron production test app that uses the Pertech cloud API.

Cloud API URL:

```text
https://vcsel.pertechindustries.com/API/apps-api/
```

## Run on Linux

```sh
npm install
npm start
```

Use Node 18 LTS or newer for Linux installs and builds. The current Node 12 runtime does not work with `electron-builder`.
Run `npm start` from a graphical Linux desktop terminal. If `Gtk-WARNING: cannot open display` appears, the shell does not have access to the active X11/Wayland session.

## Build Linux Packages

```sh
npm run dist
```

The Linux packages are created in `dist/`:

- `Pertech-VCSEL-...AppImage`
- `Pertech-VCSEL-...deb`

## Sign Release Files

```sh
./sign-linux-release.sh
```

Release signing steps are documented in `LINUX_RELEASE.md`.
